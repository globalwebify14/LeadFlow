<?php
// Global Facebook Webhook Endpoint
// Public unguarded endpoint - no auth required

require_once '../../config/db.php';

// Safe Logging Directory Setup
$logDir = __DIR__ . '/logs';
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}
$logFile = $logDir . '/facebook_sync.log';

function writeLog($msg) {
    global $logFile;
    file_put_contents($logFile, date('Y-m-d H:i:s') . " - " . $msg . "\n", FILE_APPEND);
}

// Define BASE_URL locally (cannot use auth.php because session_start() conflicts with raw webhook)
$docRoot = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? '');
$dirRoot = str_replace('\\', '/', dirname(dirname(__DIR__)));
$basePath = str_replace($docRoot, '', $dirRoot);
$basePath = ($basePath === '') ? '/' : $basePath . '/';
if (!defined('BASE_URL')) define('BASE_URL', $basePath);

// 1. Fetch Webhook Verify Token from DB
$stmt = $pdo->query("SELECT setting_value FROM system_settings WHERE setting_key = 'webhook_verify_token'");
$verifyTokenDB = $stmt->fetchColumn() ?: 'rand0m_v3r1fy_t0k3n_2024';

// 2. Handle Meta Webhook Verification (GET request)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $hubVerifyToken = $_GET['hub_verify_token'] ?? '';
    $hubChallenge   = $_GET['hub_challenge']    ?? '';
    $hubMode        = $_GET['hub_mode']         ?? '';

    if ($hubMode === 'subscribe' && $hubVerifyToken === $verifyTokenDB) {
        writeLog("WEBHOOK VERIFIED successfully via hub_challenge.");
        http_response_code(200);
        echo $hubChallenge;
        exit;
    } else {
        writeLog("WEBHOOK VERIFICATION FAILED. Token mismatch.");
        http_response_code(403);
        echo 'Forbidden';
        exit;
    }
}

// 3. Handle Webhook Event (POST request)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $inputRaw = file_get_contents('php://input');
    $data = json_decode($inputRaw, true);

    writeLog("WEBHOOK PAYLOAD RECEIVED: " . $inputRaw);
    
    // Also log to DB if needed
    $pdo->prepare("INSERT INTO webhook_logs (event_type, payload) VALUES ('leadgen', ?)")->execute([$inputRaw]);

    if (!isset($data['object']) || $data['object'] !== 'page') {
        writeLog("WEBHOOK ERROR: Invalid object type.");
        http_response_code(400);
        exit('Invalid object type');
    }

    foreach ($data['entry'] as $entry) {
        $pageId = $entry['id'];
        
        if (isset($entry['changes'])) {
            foreach ($entry['changes'] as $change) {
                if ($change['field'] === 'leadgen') {
                    $value = $change['value'];
                    $leadgenId = $value['leadgen_id'] ?? null;
                    $formId = $value['form_id'] ?? null;

                    if ($leadgenId && $formId) {
                        try {
                            writeLog("Processing Leadgen ID: {$leadgenId} from Form: {$formId}");
                            processLead($pdo, $leadgenId, $formId, $pageId);
                            writeLog("Successfully processed Leadgen ID: {$leadgenId}");
                        } catch (Exception $e) {
                            writeLog("WEBHOOK PROCESSING ERROR: " . $e->getMessage());
                            $pdo->prepare("INSERT INTO webhook_logs (event_type, payload) VALUES ('error', ?)")->execute([json_encode(['error' => $e->getMessage(), 'leadgen_id' => $leadgenId])]);
                        }
                    }
                }
            }
        }
    }

    // Always return 200 OK so Facebook knows we received the payload
    http_response_code(200);
    echo 'OK';
    exit;
}

function processLead($pdo, $leadgenId, $formId, $pageId) {
    // A. Verify if we already processed this lead
    $stmt = $pdo->prepare("SELECT id FROM facebook_leads WHERE leadgen_id = ?");
    $stmt->execute([$leadgenId]);
    if ($stmt->fetch()) {
        writeLog("Leadgen ID {$leadgenId} skipped. Already exists in DB.");
        return; // Already processed
    }

    // B. Auto-Discovery: If form is unknown, let's save it instantly so pull-sync can track it later
    $stmtPage = $pdo->prepare("SELECT organization_id FROM facebook_pages WHERE page_id = ?");
    $stmtPage->execute([$pageId]);
    $webhookOrgId = $stmtPage->fetchColumn();

    if ($webhookOrgId) {
        $stmtForm = $pdo->prepare("INSERT IGNORE INTO facebook_forms (organization_id, page_id, form_id, form_name, created_at) VALUES (?, ?, ?, 'Auto-detected Form', NOW())");
        $stmtForm->execute([$webhookOrgId, $pageId, $formId]);
    } else {
        writeLog("Warning: Page ID {$pageId} not found in CRM. Bypassing Auto-Discovery.");
    }

    // C. Find the Page Access Token for this form
    $stmt = $pdo->prepare("SELECT p.page_access_token, f.organization_id FROM facebook_forms f INNER JOIN facebook_pages p ON f.page_id = p.page_id AND f.organization_id = p.organization_id WHERE f.form_id = ? AND f.page_id = ? LIMIT 1");
    $stmt->execute([$formId, $pageId]);
    $orgBinding = $stmt->fetch();

    if (!$orgBinding) {
        throw new Exception("Form/Page pair ({$formId}/{$pageId}) not registered in CRM databases.");
    }
    
    $accessToken = $orgBinding['page_access_token'];
    $orgId = $orgBinding['organization_id'];

    // D. Fetch raw lead data from Meta Graph API
    $graphUrl = "https://graph.facebook.com/v19.0/{$leadgenId}?access_token=" . urlencode($accessToken);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $graphUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        throw new Exception("Graph API returned {$httpCode}: {$response}");
    }

    $leadData = json_decode($response, true);
    
    // E. Parse ALL form fields BEFORE inserting into facebook_leads
    $parsed = parseAllLeadFields($leadData);
    $campaign = $leadData['campaign_name'] ?? 'Facebook Ads';
    $adName = $leadData['ad_name'] ?? 'Facebook Ads';
    $createdAt = isset($leadData['created_time']) ? date('Y-m-d H:i:s', strtotime($leadData['created_time'])) : date('Y-m-d H:i:s');

    // Store into unified parallel sync tracker
    $stmtInsert = $pdo->prepare("
        INSERT IGNORE INTO facebook_leads 
        (organization_id, page_id, form_id, leadgen_id, raw_data) 
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmtInsert->execute([$orgId, $pageId, $formId, $leadgenId, json_encode($leadData)]);

    // F. Route to random active agent
    $stmtAgent = $pdo->prepare("SELECT id FROM users WHERE organization_id = ? AND role = 'agent' AND is_active = 1 ORDER BY RAND() LIMIT 1");
    $stmtAgent->execute([$orgId]);
    $agentId = $stmtAgent->fetchColumn() ?: null;

    // G. Inject into CRM leads table using Model
    require_once '../../models/Lead.php';
    $leadModel = new Lead($pdo);
    
    $leadModel->addLead([
        'organization_id'    => $orgId,
        'name'               => $parsed['name'] ?: 'Unknown Meta Lead',
        'phone'              => $parsed['phone'],
        'email'              => $parsed['email'],
        'company'            => $parsed['company'],
        'source'             => 'facebook_ads',
        'status'             => 'New Lead',
        'assigned_to'        => $agentId,
        'note'               => $parsed['note'],
        'meta_campaign'      => $campaign,
        'meta_form_id'       => $formId,
        'facebook_page_id'   => $pageId,
        'created_at'         => $createdAt
    ]);
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
