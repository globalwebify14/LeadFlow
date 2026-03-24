<?php
// modules/facebook_integration/fetch_leads.php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../models/Lead.php';

$logFile = __DIR__ . '/logs.txt';
function logSync($msg) {
    global $logFile;
    file_put_contents($logFile, date('Y-m-d H:i:s') . " - " . $msg . PHP_EOL, FILE_APPEND);
}

try {
    // 1. Loop through ALL known forms previously saved by Webhook or Manual Add
    $stmt = $pdo->query("
        SELECT f.form_id, f.page_id, f.organization_id, p.page_access_token 
        FROM facebook_forms f 
        INNER JOIN facebook_pages p ON f.page_id = p.page_id
        WHERE p.page_access_token IS NOT NULL
    ");
    $forms = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$forms) {
        logSync("Pull Sync: No known forms available yet. Waiting for webhook detection.");
        exit;
    }

    foreach ($forms as $form) {
        $formId = $form['form_id'];
        $accessToken = $form['page_access_token'];
        $orgId = $form['organization_id'];
        $pageId = $form['page_id'];

        // 2. Safely pull leads from the specific form (Does NOT require pages_manage_ads)
        $graphUrl = "https://graph.facebook.com/v19.0/{$formId}/leads?access_token=" . urlencode($accessToken) . "&fields=id,created_time,campaign_name,ad_name,field_data";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $graphUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            logSync("API Warning [Form: {$formId}] - HTTP {$httpCode}: {$response}");
            continue;
        }

        $leadData = json_decode($response, true);
        if (empty($leadData['data'])) continue;

        foreach ($leadData['data'] as $leadRaw) {
            $leadId = $leadRaw['id'];

            // 3. Duplicate Protection
            $checkStmt = $pdo->prepare("SELECT id FROM facebook_leads WHERE lead_id = ?");
            $checkStmt->execute([$leadId]);
            if ($checkStmt->fetch()) continue; // Skip existing

            // 4. Parse payload
            $name = 'Unknown Meta Lead'; $email = null; $phone = ''; $company = '';
            $allFields = [];
            if (isset($leadRaw['field_data'])) {
                foreach ($leadRaw['field_data'] as $field) {
                    $n = strtolower($field['name']);
                    $val = $field['values'][0] ?? '';
                    if (!$val) continue;
                    
                    if (in_array($n, ['full_name', 'name', 'first_name'])) $name = $val;
                    elseif (in_array($n, ['email', 'work_email'])) $email = $val;
                    elseif (in_array($n, ['phone_number', 'phone'])) $phone = $val;
                    elseif (in_array($n, ['company_name', 'company', 'business_name'])) $company = $val;

                    $label = ucwords(str_replace('_', ' ', $field['name']));
                    $allFields[] = "{$label}: {$val}";
                }
            }

            $adName = $leadRaw['ad_name'] ?? '';
            $campaign = $leadRaw['campaign_name'] ?? 'Facebook Ads';
            $createdAt = isset($leadRaw['created_time']) ? date('Y-m-d H:i:s', strtotime($leadRaw['created_time'])) : date('Y-m-d H:i:s');

            $note = "--- Facebook Lead Form Data ---\n";
            $note .= implode("\n", $allFields);
            if (isset($leadRaw['created_time'])) {
                $note .= "\nSubmitted: " . $leadRaw['created_time'];
            }

            $pdo->beginTransaction();
            try {
                // 5. Insert sync log
                $insertStmt = $pdo->prepare("
                    INSERT INTO facebook_leads 
                    (lead_id, name, email, phone, ad_name, form_id, created_time, source, fetched_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, 'pull', NOW())
                ");
                $insertStmt->execute([$leadId, $name, $email, $phone, $adName, $formId, $createdAt]);

                // 6. Push to real CRM pipeline
                // Route to random active agent
                $stmtAgent = $pdo->prepare("SELECT id FROM users WHERE organization_id = ? AND role = 'agent' AND is_active = 1 ORDER BY RAND() LIMIT 1");
                $stmtAgent->execute([$orgId]);
                $agentId = $stmtAgent->fetchColumn() ?: null;

                $leadModel = new Lead($pdo);
                $leadModel->addLead([
                    'organization_id'  => $orgId,
                    'name'             => $name,
                    'phone'            => $phone,
                    'email'            => $email,
                    'company'          => $company,
                    'source'           => 'facebook_ads',
                    'assigned_to'      => $agentId,
                    'note'             => $note,
                    'meta_campaign'    => $campaign,
                    'meta_form_id'     => $formId,
                    'facebook_page_id' => $pageId,
                    'created_at'       => $createdAt,
                    'status'           => 'New Lead'
                ]);

                $pdo->commit();
                logSync("Stored new lead: {$leadId} from Pull System");
            } catch (Exception $ex) {
                $pdo->rollBack();
                logSync("DB Error processing lead {$leadId}: {$ex->getMessage()}");
            }
        }
    }
} catch (Exception $e) {
    logSync("System Exception: " . $e->getMessage());
}
?>
