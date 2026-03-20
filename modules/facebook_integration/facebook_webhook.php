<?php
// Global Facebook Webhook Endpoint
// Public unguarded endpoint - no auth required

require_once '../../config/db.php';

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
        // Verification success
        http_response_code(200);
        echo $hubChallenge;
        exit;
    } else {
        http_response_code(403);
        echo 'Forbidden';
        exit;
    }
}

// 3. Handle Webhook Event (POST request)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $inputRaw = file_get_contents('php://input');
    $data = json_decode($inputRaw, true);

    // Log the raw webhook for debugging
    $logStmt = $pdo->prepare("INSERT INTO webhook_logs (event_type, payload) VALUES ('leadgen', ?)");
    $logStmt->execute([$inputRaw]);

    if (!isset($data['object']) || $data['object'] !== 'page') {
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
                            processLead($pdo, $leadgenId, $formId, $pageId);
                        } catch (Exception $e) {
                            // Log processing fail but keep 200 to prevent Facebook from backing off
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
    if ($stmt->fetch()) return; // Already processed

    // B. Find the Page Access Token and Org ID for this form
    $stmt = $pdo->prepare("SELECT p.page_access_token, f.organization_id FROM facebook_forms f INNER JOIN facebook_pages p ON f.page_id = p.page_id AND f.organization_id = p.organization_id WHERE f.form_id = ? AND f.page_id = ? LIMIT 1");
    $stmt->execute([$formId, $pageId]);
    $orgBinding = $stmt->fetch();

    if (!$orgBinding) {
        throw new Exception("Form/Page pair not registered in CRM databases.");
    }
    
    $accessToken = $orgBinding['page_access_token'];
    $orgId = $orgBinding['organization_id'];

    // C. Fetch raw lead data from Meta Graph API
    // [SIMULATION BYPASS] For testing, skip real API call if id starts with 'sim_'
    if (strpos($leadgenId, 'sim_') === 0) {
        $response = json_encode([
            'id' => $leadgenId,
            'created_time' => date('c'),
            'field_data' => [
                ['name' => 'full_name', 'values' => ['Simulated Test Lead']],
                ['name' => 'phone_number', 'values' => ['+10000000000']],
                ['name' => 'email', 'values' => ['test@simulation.com']]
            ]
        ]);
        $httpCode = 200;
    } else {
        $graphUrl = "https://graph.facebook.com/v19.0/{$leadgenId}?access_token=" . urlencode($accessToken);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $graphUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
    }

    if ($httpCode !== 200) {
        throw new Exception("Graph API returned {$httpCode}: {$response}");
    }

    $leadData = json_decode($response, true);
    
    // Store exact raw JSON backup
    $pdo->prepare("INSERT INTO facebook_leads (organization_id, page_id, form_id, leadgen_id, raw_data) VALUES (?, ?, ?, ?, ?)")->execute([$orgId, $pageId, $formId, $leadgenId, $response]);

    // D. Parse ALL form fields
    $parsed = parseAllLeadFields($leadData);
    $campaign = $leadData['campaign_name'] ?? 'Facebook Ads';

    // E + F + G. Inject into CRM leads table using Model (Handles Auto-Assign, Notifications, Logs, tags, synced pipeline)
    require_once '../../models/Lead.php';
    $leadModel = new Lead($pdo);
    
    $createdAt = isset($leadData['created_time']) ? date('Y-m-d H:i:s', strtotime($leadData['created_time'])) : date('Y-m-d H:i:s');
    
    $leadModel->addLead([
        'organization_id'    => $orgId,
        'name'               => $parsed['name'],
        'phone'              => $parsed['phone'],
        'email'              => $parsed['email'] ?: null,
        'company'            => $parsed['company'] ?: null,
        'source'             => 'facebook_ads',
        'status'             => 'New Lead',
        'priority'           => 'Hot',
        'assigned_to'        => null, // Handle Auto-Assignment (Round Robin)
        'ignore_auto_assign' => (strpos($leadgenId, 'sim_') === 0), // [SIM_FIX] Keep test leads unassigned
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

