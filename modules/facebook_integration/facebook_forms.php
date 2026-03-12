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
$errors = [];

$pdo->beginTransaction();
try {
    // Clear old forms
    $pdo->prepare("DELETE FROM facebook_forms WHERE organization_id = ?")->execute([$orgId]);
    
    $stmtForm = $pdo->prepare("INSERT INTO facebook_forms (organization_id, page_id, form_id, form_name) VALUES (:org, :page, :form, :name)");

    foreach ($pages as $page) {
        // STEP 1: Fetch leadgen_forms for each page
        $url = "https://graph.facebook.com/v19.0/{$page['page_id']}/leadgen_forms?access_token=" . urlencode($page['page_access_token']);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        // Log errors for this page
        if ($httpCode !== 200) {
            $errData = json_decode($response, true);
            $errMsg = $errData['error']['message'] ?? $response;
            $errors[] = "Page {$page['page_name']}: {$errMsg}";
            
            $pdo->prepare("INSERT INTO webhook_logs (event_type, payload) VALUES ('form_sync_error', ?)")->execute([json_encode([
                'page_id' => $page['page_id'],
                'page_name' => $page['page_name'],
                'http_code' => $httpCode,
                'curl_error' => $curlError,
                'response' => $response
            ])]);
            continue;
        }

        $data = json_decode($response, true);
        $forms = $data['data'] ?? [];

        foreach ($forms as $f) {
            $stmtForm->execute([
                'org' => $orgId,
                'page' => $page['page_id'],
                'form' => $f['id'],
                'name' => $f['name']
            ]);
            $totalForms++;

            // STEP 2: Fetch existing leads for this form
            $leadsUrl = "https://graph.facebook.com/v19.0/{$f['id']}/leads?access_token=" . urlencode($page['page_access_token']) . "&limit=50";
            
            $ch2 = curl_init();
            curl_setopt($ch2, CURLOPT_URL, $leadsUrl);
            curl_setopt($ch2, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch2, CURLOPT_TIMEOUT, 30);
            $leadsResponse = curl_exec($ch2);
            $leadsHttpCode = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
            curl_close($ch2);

            if ($leadsHttpCode === 200) {
                $leadsData = json_decode($leadsResponse, true);
                $rawLeads = $leadsData['data'] ?? [];

                foreach ($rawLeads as $leadRaw) {
                    $leadgenId = $leadRaw['id'];

                    // Bypass duplicate check for testing
                    $chkStmt = $pdo->prepare("SELECT id FROM facebook_leads WHERE leadgen_id = ?");
                    $chkStmt->execute([$leadgenId]);
                    if ($chkStmt->fetch()) continue;

                    // Parse ALL form fields
                    $parsed = parseLeadFields($leadRaw);

                    // Store raw trace (use INSERT IGNORE to prevent unique constraint crash)
                    $pdo->prepare("INSERT IGNORE INTO facebook_leads (organization_id, page_id, form_id, leadgen_id, raw_data) VALUES (?, ?, ?, ?, ?)")->execute([
                        $orgId, $page['page_id'], $f['id'], $leadgenId, json_encode($leadRaw)
                    ]);

                    // Route to random active agent
                    $stmtAgent = $pdo->prepare("SELECT id FROM users WHERE organization_id = ? AND role = 'agent' AND is_active = 1 ORDER BY RAND() LIMIT 1");
                    $stmtAgent->execute([$orgId]);
                    $agentId = $stmtAgent->fetchColumn() ?: null;

                    // Insert into CRM leads table with ALL fields, including exact Facebook submission time
                    $createdAt = isset($leadRaw['created_time']) ? date('Y-m-d H:i:s', strtotime($leadRaw['created_time'])) : date('Y-m-d H:i:s');
                    $stmtLead = $pdo->prepare("INSERT INTO leads (organization_id, name, phone, email, company, source, status, priority, assigned_to, note, meta_campaign, meta_form_id, created_at) VALUES (:org, :name, :phone, :email, :company, :source, 'New Lead', 'Hot', :assign, :note, :campaign, :form, :created)");
                    $stmtLead->execute([
                        'org' => $orgId,
                        'name' => $parsed['name'],
                        'phone' => $parsed['phone'],
                        'email' => $parsed['email'],
                        'company' => $parsed['company'],
                        'source' => 'facebook_ads',
                        'assign' => $agentId,
                        'note' => $parsed['note'],
                        'campaign' => $leadRaw['campaign_name'] ?? $f['name'],
                        'form' => $f['id'],
                        'created' => $createdAt
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

// Build success message
$msg = "Synced {$totalForms} form(s) and imported {$totalLeads} lead(s) from Facebook!";
if (!empty($errors)) {
    $msg .= " Errors on " . count($errors) . " page(s): " . $errors[0];
}

redirect(BASE_URL . 'modules/facebook_integration/facebook_integration_settings.php', $msg, empty($errors) ? 'success' : 'warning');
exit;

/**
 * Parse ALL lead field_data into structured fields.
 * Returns: name, email, phone, company, note (formatted string of ALL fields)
 */
function parseLeadFields($leadRaw) {
    $name = 'Unknown Meta Lead';
    $email = null;
    $phone = '';
    $company = '';
    $allFields = [];

    if (isset($leadRaw['field_data'])) {
        foreach ($leadRaw['field_data'] as $field) {
            $value = $field['values'][0] ?? null;
            if (!$value) continue;

            $fieldName = $field['name'];
            $n = strtolower($fieldName);

            // Map known fields
            if (in_array($n, ['full_name', 'name', 'first_name'])) {
                $name = $value;
            } elseif (in_array($n, ['email', 'work_email'])) {
                $email = $value;
            } elseif (in_array($n, ['phone_number', 'phone'])) {
                $phone = $value;
            } elseif (in_array($n, ['company_name', 'company', 'business_name'])) {
                $company = $value;
            }

            // Collect ALL fields for the note (including ones already mapped)
            $label = ucwords(str_replace('_', ' ', $fieldName));
            $allFields[] = "{$label}: {$value}";
        }
    }

    // Build a formatted note with all form data
    $note = "--- Facebook Lead Form Data ---\n";
    $note .= implode("\n", $allFields);
    if (isset($leadRaw['created_time'])) {
        $note .= "\nSubmitted: " . $leadRaw['created_time'];
    }

    return [
        'name' => $name,
        'email' => $email,
        'phone' => $phone,
        'company' => $company,
        'note' => $note
    ];
}
?>
