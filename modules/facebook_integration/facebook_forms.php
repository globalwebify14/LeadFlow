<?php
require_once '../../config/auth.php';
requireLogin();
requireRole('org_owner');
require_once '../../config/db.php';

$orgId = getOrgId();

// Verify Pages Exist
$stmt = $pdo->prepare("SELECT * FROM facebook_pages WHERE organization_id = ?");
$stmt->execute([$orgId]);
$pages = $stmt->fetchAll();

if (empty($pages)) {
    redirect(BASE_URL . 'modules/facebook_integration/facebook_integration_settings.php', 'No pages found to sync forms from.', 'danger');
}

$totalForms = 0;
$totalLeads = 0;
$fallbackUsed = false;
$errors = [];

$pdo->beginTransaction();
try {
    // We do NOT wipe facebook_forms anymore. We safely use INSERT IGNORE to preserve webhook auto-detected forms!
    $stmtFormInsert = $pdo->prepare("INSERT IGNORE INTO facebook_forms (organization_id, page_id, form_id, form_name, created_at) VALUES (:org, :page, :form, :name, NOW())");

    foreach ($pages as $page) {
        $pageId = $page['page_id'];
        $pageToken = $page['page_access_token'];

        // STEP 1: Proactively test if our user granted us pages_manage_ads for this specific page
        $permUrl = "https://graph.facebook.com/v19.0/{$pageId}/permissions?access_token=" . urlencode($pageToken);
        $chP = curl_init($permUrl);
        curl_setopt($chP, CURLOPT_RETURNTRANSFER, 1);
        $permRes = curl_exec($chP);
        curl_close($chP);

        $hasAdsManage = false;
        $permData = json_decode($permRes, true);
        if (isset($permData['data'])) {
            foreach ($permData['data'] as $p) {
                if ($p['permission'] === 'pages_manage_ads' && $p['status'] === 'granted') {
                    $hasAdsManage = true;
                    break;
                }
            }
        }

        $activeFormsList = []; // Array to hold forms we intend to sync leads for

        // STEP 2: Try fetching forms ONLY if permission exists
        if ($hasAdsManage) {
            $url = "https://graph.facebook.com/v19.0/{$pageId}/leadgen_forms?access_token=" . urlencode($pageToken);
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 200) {
                $formsData = json_decode($response, true);
                if (!empty($formsData['data'])) {
                    foreach ($formsData['data'] as $f) {
                        // Store it immediately into our DB
                        $stmtFormInsert->execute([
                            'org' => $orgId,
                            'page' => $pageId,
                            'form' => $f['id'],
                            'name' => $f['name']
                        ]);
                        $activeFormsList[] = ['form_id' => $f['id'], 'form_name' => $f['name']];
                    }
                }
            } else {
                // Endpoint failed despite permission check (maybe an unexpected error)
                $fallbackUsed = true;
            }
        } else {
            // No permission granted yet
            $fallbackUsed = true;
        }

        // STEP 3: Fallback mechanism - rely entirely on our database Auto-Detection!
        if (empty($activeFormsList)) {
            $stmtGetKnown = $pdo->prepare("SELECT form_id, form_name FROM facebook_forms WHERE page_id = ?");
            $stmtGetKnown->execute([$pageId]);
            $activeFormsList = $stmtGetKnown->fetchAll(PDO::FETCH_ASSOC);

            // FORCE UI VISIBILITY: Map these found forms perfectly to your logged-in organization
            if (!empty($activeFormsList)) {
                $pdo->prepare("UPDATE facebook_forms SET organization_id = ? WHERE page_id = ?")->execute([$orgId, $pageId]);
            }
        }

        // STEP 4: Fetch Leads strictly for the activeFormsList
        foreach ($activeFormsList as $f) {
            $totalForms++;
            $leadsUrl = "https://graph.facebook.com/v19.0/{$f['form_id']}/leads?access_token=" . urlencode($pageToken) . "&limit=50&fields=id,created_time,campaign_name,ad_name,field_data";
            
            $ch2 = curl_init($leadsUrl);
            curl_setopt($ch2, CURLOPT_RETURNTRANSFER, 1);
            $leadsResponse = curl_exec($ch2);
            $leadsHttpCode = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
            curl_close($ch2);

            if ($leadsHttpCode === 200) {
                $leadsData = json_decode($leadsResponse, true);
                $rawLeads = $leadsData['data'] ?? [];

                foreach ($rawLeads as $leadRaw) {
                    $leadgenId = $leadRaw['id'];

                    // Duplicate check inside our native sync table
                    $chkStmt = $pdo->prepare("SELECT id FROM facebook_leads WHERE leadgen_id = ?");
                    $chkStmt->execute([$leadgenId]);
                    if ($chkStmt->fetch()) continue; // Skip existing

                    // Parse Custom Fields
                    $name = ''; $email = ''; $phone = ''; $company = '';
                    $allFields = [];
                    if (isset($leadRaw['field_data'])) {
                        foreach ($leadRaw['field_data'] as $field) {
                            $val = $field['values'][0] ?? '';
                            if (!$val) continue;

                            $n = strtolower($field['name']);
                            if (in_array($n, ['full_name', 'name', 'first_name'])) $name = $val;
                            elseif (in_array($n, ['email', 'work_email'])) $email = $val;
                            elseif (in_array($n, ['phone_number', 'phone'])) $phone = $val;
                            elseif (in_array($n, ['company_name', 'company', 'business_name'])) $company = $val;

                            $label = ucwords(str_replace('_', ' ', $field['name']));
                            $allFields[] = "{$label}: {$val}";
                        }
                    }

                    $campaign = $leadRaw['campaign_name'] ?? 'Facebook Ads';
                    $adName = $leadRaw['ad_name'] ?? 'Organic / No Ad';
                    $createdAt = isset($leadRaw['created_time']) ? date('Y-m-d H:i:s', strtotime($leadRaw['created_time'])) : date('Y-m-d H:i:s');
                    
                    $note = "--- Facebook Lead Form Data ---\n" . implode("\n", $allFields);

                    // Insert to facebook_leads parallel sync table using native legacy schema
                    $pdo->prepare("INSERT IGNORE INTO facebook_leads (organization_id, page_id, form_id, leadgen_id, raw_data) VALUES (?, ?, ?, ?, ?)")
                        ->execute([$orgId, $pageId, $f['form_id'], $leadgenId, json_encode($leadRaw)]);

                    // Route to CRM via Lead Model directly
                    $stmtAgent = $pdo->prepare("SELECT id FROM users WHERE organization_id = ? AND role = 'agent' AND is_active = 1 ORDER BY RAND() LIMIT 1");
                    $stmtAgent->execute([$orgId]);
                    $agentId = $stmtAgent->fetchColumn() ?: null;

                    require_once '../../models/Lead.php';
                    $leadModel = new Lead($pdo);
                    $leadModel->addLead([
                        'organization_id'  => $orgId,
                        'name'             => $name ?: 'Unknown Meta Lead',
                        'phone'            => $phone,
                        'email'            => $email,
                        'company'          => $company,
                        'source'           => 'facebook_ads',
                        'assigned_to'      => $agentId,
                        'note'             => $note,
                        'meta_campaign'    => $campaign,
                        'meta_form_id'     => $f['form_id'],
                        'facebook_page_id' => $pageId,
                        'created_at'       => $createdAt,
                        'status'           => 'New Lead'
                    ]);

                    $totalLeads++;
                }
            }
        }
    }
    $pdo->commit();
} catch (Exception $e) {
    $pdo->rollBack();
    redirect(BASE_URL . 'modules/facebook_integration/facebook_integration_settings.php', 'Database error: ' . $e->getMessage(), 'danger');
}

// Ensure clean User Experience
if ($fallbackUsed) {
    $msg = "Fallback Mode Used: Synced {$totalLeads} leads from {$totalForms} known auto-detected forms. (Initial form sync skipped due to missing permissions)";
    redirect(BASE_URL . 'modules/facebook_integration/facebook_integration_settings.php', $msg, 'warning');
} else {
    $msg = "Forms Synced Successfully! Auto-detected {$totalForms} forms and explicitly imported {$totalLeads} leads from Facebook.";
    redirect(BASE_URL . 'modules/facebook_integration/facebook_integration_settings.php', $msg, 'success');
}
exit;
?>
