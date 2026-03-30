<?php
/**
 * AUTO PULL LEADS (BACKUP SYSTEM)
 * Designed to be executed via Cron every 1-2 minutes.
 * Scans all connected pages, fetches forms, and paginates through leads to find any missed via webhook.
 */

// If running from CLI, ensure we can include normal config files properly
$dirRoot = dirname(dirname(__DIR__));
require_once $dirRoot . '/config/db.php';

// Safe Logging Directory Setup
$logDir = __DIR__ . '/logs';
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}
$logFile = $logDir . '/facebook_sync.log';

function writeSyncLog($msg) {
    global $logFile;
    file_put_contents($logFile, date('Y-m-d H:i:s') . " - [CRON AUTO-PULL] " . $msg . "\n", FILE_APPEND);
}

writeSyncLog("Starting Auto Pull Cycle...");

try {
    // 1. Fetch ALL connected pages across ALL organizations
    $stmt = $pdo->query("SELECT * FROM facebook_pages");
    $pages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($pages)) {
        writeSyncLog("No Facebook pages connected. Exiting.");
        exit;
    }

    $totalForms = 0;
    $totalLeads = 0;
    $skippedLeads = 0;

    foreach ($pages as $page) {
        $pageId = $page['page_id'];
        $pageToken = $page['page_access_token'];
        $orgId = $page['organization_id'];

        writeSyncLog("Scanning Page: {$page['page_name']} ({$pageId}) for Org {$orgId}");

        $activeFormsList = []; 

        // 2A. Try fetching forms natively via API
        $url = "https://graph.facebook.com/v19.0/{$pageId}/leadgen_forms?access_token=" . urlencode($pageToken);
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        unset($ch);

        $stmtFormInsert = $pdo->prepare("INSERT IGNORE INTO facebook_forms (organization_id, page_id, form_id, form_name, created_at) VALUES (?, ?, ?, ?, NOW())");

        if ($httpCode === 200) {
            $formsData = json_decode($response, true);
            if (!empty($formsData['data'])) {
                foreach ($formsData['data'] as $f) {
                    $stmtFormInsert->execute([$orgId, $pageId, $f['id'], $f['name']]);
                    $activeFormsList[] = ['form_id' => $f['id'], 'form_name' => $f['name']];
                }
            }
        } else {
            writeSyncLog("Permission Error (leadgen_forms) on Page {$pageId}. HTTP {$httpCode}. Falling back to Known DB Forms.");
        }

        // 2B. Fallback: Pre-load auto-detected webhook forms from DB if API was blocked
        if (empty($activeFormsList)) {
            $stmtGetKnown = $pdo->prepare("SELECT form_id, form_name FROM facebook_forms WHERE page_id = ?");
            $stmtGetKnown->execute([$pageId]);
            $activeFormsList = $stmtGetKnown->fetchAll(PDO::FETCH_ASSOC);
        }

        if (empty($activeFormsList)) {
            writeSyncLog("No forms discovered for Page {$pageId}. Skipping.");
            continue;
        }

        // 3. Loop through forms and pull leads
        foreach ($activeFormsList as $f) {
            $totalForms++;
            $formId = $f['form_id'];
            
            writeSyncLog("Querying Form ID: {$formId}");

            $leadsUrl = "https://graph.facebook.com/v19.0/{$formId}/leads?access_token=" . urlencode($pageToken) . "&fields=id,created_time,campaign_name,ad_name,field_data&limit=100";
            
            $pagesFetched = 0;

            // PAGINATION (Loop up to 10 cursor pages to fetch historical missed leads, max 1000 per form sync to avoid timeouts)
            while ($leadsUrl && $pagesFetched < 10) {
                $ch2 = curl_init($leadsUrl);
                curl_setopt($ch2, CURLOPT_RETURNTRANSFER, 1);
                $leadsResponse = curl_exec($ch2);
                $leadsHttpCode = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
                unset($ch2);

                if ($leadsHttpCode === 200) {
                    $leadsData = json_decode($leadsResponse, true);
                    $rawLeads = $leadsData['data'] ?? [];

                    foreach ($rawLeads as $leadRaw) {
                        $leadgenId = $leadRaw['id'];

                        // 3A. EXACT DUPLICATE CHECK: Skip if it exists in facebook_leads raw table
                        $chkStmt = $pdo->prepare("SELECT id FROM facebook_leads WHERE leadgen_id = ?");
                        $chkStmt->execute([$leadgenId]);
                        if ($chkStmt->fetch()) {
                            // It's already in the DB! Skip silently.
                            $skippedLeads++;
                            continue; 
                        }

                        // We found a brand new lead!
                        writeSyncLog("Discovered NEW MISSING LEAD: {$leadgenId}");

                        // Parse Data
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
                        $createdAt = isset($leadRaw['created_time']) ? date('Y-m-d H:i:s', strtotime($leadRaw['created_time'])) : date('Y-m-d H:i:s');
                        $note = "--- Facebook Lead Form Data ---\n" . implode("\n", $allFields);

                        // 3B. Insert into raw trace parallel table
                        $stmtInsertRaw = $pdo->prepare("INSERT IGNORE INTO facebook_leads (organization_id, page_id, form_id, leadgen_id, raw_data) VALUES (?, ?, ?, ?, ?)");
                        $stmtInsertRaw->execute([$orgId, $pageId, $formId, $leadgenId, json_encode($leadRaw)]);

                        // 3C. Random Active Agent Routing
                        $stmtAgent = $pdo->prepare("SELECT id FROM users WHERE organization_id = ? AND role = 'agent' AND is_active = 1 ORDER BY RAND() LIMIT 1");
                        $stmtAgent->execute([$orgId]);
                        $agentId = $stmtAgent->fetchColumn() ?: null;

                        // 3D. Inject directly into standard CRM leads
                        require_once $dirRoot . '/models/Lead.php';
                        $leadModel = new Lead($pdo);
                        $leadModel->addLead([
                            'organization_id'  => $orgId,
                            'name'             => $name ?: 'Unknown Meta Lead',
                            'phone'            => $phone,
                            'email'            => $email,
                            'company'          => $company,
                            'source'           => 'facebook',
                            'assigned_to'      => $agentId,
                            'note'             => $note,
                            'meta_campaign'    => $campaign,
                            'meta_form_id'     => $formId,
                            'facebook_page_id' => $pageId,
                            'created_at'       => $createdAt,
                            'status'           => 'New Lead'
                        ]);

                        $totalLeads++;
                    }

                    // Move to the next cursor page of leads
                    $leadsUrl = $leadsData['paging']['next'] ?? null;
                    $pagesFetched++;

                } else {
                    $errData = json_decode($leadsResponse, true);
                    $errMsg = $errData['error']['message'] ?? "HTTP {$leadsHttpCode}";
                    writeSyncLog("Error fetching leads for Form {$formId}: {$errMsg}");
                    break; // Stop paginating if error
                }
            } // end pagination while loop
        } // end foreach activeForms
    } // end foreach pages

    writeSyncLog("Auto Pull Cycle Completed. Extracted {$totalLeads} New Leads from {$totalForms} Forms. (Processed and Skipped {$skippedLeads} known leads).");

} catch (Exception $e) {
    writeSyncLog("CRITICAL SYSTEM ERROR during Auto Pull: " . $e->getMessage());
}
?>
