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
$skippedLeads = 0;
$fallbackUsed = false;
$apiErrors = [];

$pdo->beginTransaction();
try {
    $stmtFormInsert = $pdo->prepare("INSERT IGNORE INTO facebook_forms (organization_id, page_id, form_id, form_name, created_at) VALUES (:org, :page, :form, :name, NOW())");

    foreach ($pages as $page) {
        $pageId = $page['page_id'];
        $pageToken = $page['page_access_token'];

        $activeFormsList = []; 

        // STEP 1: Proactively test the API call itself. No flawed /permissions lookup.
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
            // Permission rejected or something else went wrong, fallback engaged!
            $fallbackUsed = true;
            $errData = json_decode($response, true);
            if (!empty($errData['error']['message'])) {
                $apiErrors[] = "Forms API: " . $errData['error']['message'];
            }
        }

        // STEP 2: Fallback mechanism - load forms from our Auto-Detection DB!
        if (empty($activeFormsList)) {
            $stmtGetKnown = $pdo->prepare("SELECT form_id, form_name FROM facebook_forms WHERE page_id = ?");
            $stmtGetKnown->execute([$pageId]);
            $activeFormsList = $stmtGetKnown->fetchAll(PDO::FETCH_ASSOC);

            // FORCE UI VISIBILITY: Map these found forms perfectly to your logged-in organization
            if (!empty($activeFormsList)) {
                $pdo->prepare("UPDATE facebook_forms SET organization_id = ? WHERE page_id = ? AND (organization_id IS NULL OR organization_id = 0)")->execute([$orgId, $pageId]);
            }
        }

        // STEP 3: Fetch Leads strictly for the activeFormsList (WITH PAGINATION)
        foreach ($activeFormsList as $f) {
            $totalForms++;
            // Note: limits higher than 50 or 100 are rarely well-handled by Facebook, using 100 max per page
            $leadsUrl = "https://graph.facebook.com/v19.0/{$f['form_id']}/leads?access_token=" . urlencode($pageToken) . "&fields=id,created_time,campaign_name,ad_name,field_data&limit=100";
            
            $pagesFetched = 0;

            while ($leadsUrl && $pagesFetched < 15) { // Stop infinitely parsing (Max 1500 per form sync cycle)
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

                        // Duplicate check
                        $chkStmt = $pdo->prepare("SELECT id FROM facebook_leads WHERE leadgen_id = ?");
                        $chkStmt->execute([$leadgenId]);
                        if ($chkStmt->fetch()) {
                            $skippedLeads++;
                            continue; // Skip existing
                        }

                        // Parse Fields
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

                        // Insert to raw table
                        $pdo->prepare("INSERT IGNORE INTO facebook_leads (organization_id, page_id, form_id, leadgen_id, raw_data) VALUES (?, ?, ?, ?, ?)")
                            ->execute([$orgId, $pageId, $f['form_id'], $leadgenId, json_encode($leadRaw)]);

                        // Route to CRM
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

                    // Proceed to next page of results (Facebook provides 'next' URL strictly if there's more)
                    $leadsUrl = $leadsData['paging']['next'] ?? null;
                    $pagesFetched++;

                } else {
                    $errData = json_decode($leadsResponse, true);
                    $apiErrors[] = "Leads API: " . ($errData['error']['message'] ?? "HTTP {$leadsHttpCode}");
                    break; // stop pagination loop for this form if blocked
                }
            } // end while (pagination)
        } // end foreach (forms)
    } // end foreach (pages)
    
    $pdo->commit();
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    redirect(BASE_URL . 'modules/facebook_integration/facebook_integration_settings.php', 'Database error: ' . $e->getMessage(), 'danger');
}

if ($fallbackUsed) {
    $msg = "Fallback Mode Used: Synced {$totalLeads} new leads (Skipped {$skippedLeads} duplicates) from {$totalForms} known auto-detected forms.";
    if (!empty($apiErrors)) $msg .= " | Errors: " . substr(implode(', ', $apiErrors), 0, 150);
    redirect(BASE_URL . 'modules/facebook_integration/facebook_integration_settings.php', $msg, (!empty($apiErrors) ? 'danger' : 'warning'));
} else {
    $msg = "Forms Synced Successfully! Auto-detected {$totalForms} forms and explicitly imported {$totalLeads} leads from Facebook. (Skipped {$skippedLeads} duplicates).";
    if (!empty($apiErrors)) $msg .= " | Errors: " . substr(implode(', ', $apiErrors), 0, 150);
    redirect(BASE_URL . 'modules/facebook_integration/facebook_integration_settings.php', $msg, 'success');
}
exit;
?>
