<?php
// Facebook Lead Sync Script (Cron / Manual)
// Fetches missed leads across all organizations.
// CLI: C:\xampp\php\php.exe c:/xampp/htdocs/lead/modules/facebook_integration/facebook_sync.php
// Web: ?manual_sync=1

if (php_sapi_name() !== 'cli' && !isset($_GET['manual_sync'])) {
    die("This script can only be run via CLI or with explicit manual_sync flag.");
}

require_once __DIR__ . '/../../config/db.php';

// Define BASE_URL for notification links (handles both CLI and web contexts)
if (!defined('BASE_URL')) {
    if (php_sapi_name() === 'cli') {
        define('BASE_URL', '/');
    } else {
        $docRoot = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? '');
        $dirRoot = str_replace('\\', '/', dirname(dirname(__DIR__)));
        $basePath = str_replace($docRoot, '', $dirRoot);
        $basePath = ($basePath === '') ? '/' : $basePath . '/';
        define('BASE_URL', $basePath);
    }
}

echo "[Start] Discovering disconnected leads across organizations...\n";

// Fetch all forms tied to pages
$stmt = $pdo->query("SELECT f.organization_id, f.form_id, f.page_id, p.page_access_token FROM facebook_forms f INNER JOIN facebook_pages p ON f.page_id = p.page_id AND f.organization_id = p.organization_id");
$forms = $stmt->fetchAll();

foreach ($forms as $form) {
    echo "  -> Inspecting Form ID {$form['form_id']} for Org {$form['organization_id']} ...\n";

    // Ask Graph API for leads created on this form. We pull the last 25.
    $url = "https://graph.facebook.com/v19.0/{$form['form_id']}/leads?access_token=" . urlencode($form['page_access_token']) . "&limit=25";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        echo "     [ERROR] Failed to fetch leads (HTTP $httpCode).\n";
        continue;
    }

    $data = json_decode($response, true);
    $leads = $data['data'] ?? [];

    $count = 0;
    foreach ($leads as $leadRaw) {
        $leadgenId = $leadRaw['id'];
        
        // Does the lead exist in `facebook_leads`?
        $stmtChk = $pdo->prepare("SELECT id FROM facebook_leads WHERE leadgen_id = ?");
        $stmtChk->execute([$leadgenId]);
        if (!$stmtChk->fetch()) {
            // It's missing (webhook dropped). Inject.
            $count++;
            
            // D. Parse ALL form fields
            $parsed = parseAllLeadFields($leadRaw);
            $campaign = $leadRaw['campaign_name'] ?? 'Facebook Ads';
            $source = 'facebook_ads';

            // Route to Random Agent
            $stmtAgent = $pdo->prepare("SELECT id FROM users WHERE organization_id = ? AND role = 'agent' AND is_active = 1 ORDER BY RAND() LIMIT 1");
            $stmtAgent->execute([$form['organization_id']]);
            $agentId = $stmtAgent->fetchColumn() ?: null;

            $pdo->beginTransaction();
            try {
                // Store trace
                $pdo->prepare("INSERT INTO facebook_leads (organization_id, page_id, form_id, leadgen_id, raw_data) VALUES (?, ?, ?, ?, ?)")->execute([$form['organization_id'], $form['page_id'], $form['form_id'], $leadgenId, json_encode($leadRaw)]);

                // Store inside CRM pipeline, including exact Facebook submission time
                $createdAt = isset($leadRaw['created_time']) ? date('Y-m-d H:i:s', strtotime($leadRaw['created_time'])) : date('Y-m-d H:i:s');
                $stmtLead = $pdo->prepare("INSERT INTO leads (organization_id, name, phone, email, company, source, status, priority, assigned_to, note, meta_campaign, meta_form_id, created_at) VALUES (:org, :name, :phone, :email, :company, :source, 'New Lead', 'Hot', :assign, :note, :campaign, :form, :created)");
                $stmtLead->execute([
                    'org' => $form['organization_id'],
                    'name' => $parsed['name'],
                    'phone' => $parsed['phone'],
                    'email' => $parsed['email'],
                    'company' => $parsed['company'],
                    'source' => $source,
                    'assign' => $agentId,
                    'note' => $parsed['note'],
                    'campaign' => $campaign,
                    'form' => $form['form_id'],
                    'created' => $createdAt
                ]);

                $leadDbId = $pdo->lastInsertId();

                if ($agentId) {
                    require_once __DIR__ . '/../../models/Notification.php';
                    $notifier = new Notification($pdo);
                    $notifier->create(
                        $form['organization_id'], 
                        $agentId, 
                        'lead_assigned', 
                        'New Facebook Lead Assigned', 
                        "You have been assigned a new lead: {$parsed['name']} from campaign {$campaign}.", 
                        BASE_URL . "modules/leads/view.php?id={$leadDbId}"
                    );
                }

                $pdo->commit();
            } catch (Exception $e) {
                $pdo->rollBack();
                echo "     [ERROR] Database ingest fail for {$leadgenId}: {$e->getMessage()}\n";
            }
        }
    }
    
    echo "     Loaded {$count} missing leads.\n";
}

echo "[Finish] Cron Sync Complete.\n";
if (isset($_GET['manual_sync'])) {
    redirect(BASE_URL . 'modules/facebook_integration/facebook_integration_settings.php', 'Manual Sync Loop Completed.', 'success');
}

/**
 * Parse ALL lead field_data into structured fields.
 */
function parseAllLeadFields($leadRaw) {
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

            if (in_array($n, ['full_name', 'name', 'first_name'])) {
                $name = $value;
            } elseif (in_array($n, ['email', 'work_email'])) {
                $email = $value;
            } elseif (in_array($n, ['phone_number', 'phone'])) {
                $phone = $value;
            } elseif (in_array($n, ['company_name', 'company', 'business_name'])) {
                $company = $value;
            }

            $label = ucwords(str_replace('_', ' ', $fieldName));
            $allFields[] = "{$label}: {$value}";
        }
    }

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
