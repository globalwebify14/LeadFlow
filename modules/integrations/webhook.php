<?php
/**
 * Meta Webhook Endpoint
 * Receives leads from Facebook/Instagram Lead Ads
 */
require_once '../../config/db.php';
require_once '../../models/MetaIntegration.php';



// Meta Verification Token (Set this to a secure random string and use it in Meta App dashboard)
define('META_VERIFY_TOKEN', 'crm_leads_meta_integration_secret_' . date('Y'));

// 1. Webhook Verification (GET request)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['hub_mode']) && $_GET['hub_mode'] === 'subscribe') {
    if ($_GET['hub_verify_token'] === META_VERIFY_TOKEN) {
        echo $_GET['hub_challenge'];
        http_response_code(200);
        exit;
    } else {
        http_response_code(403);
        exit;
    }
}

// 2. Webhook Event Handling (POST request)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    // Log incoming webhook for debugging
    file_put_contents(__DIR__ . '/meta_webhook.log', date('[Y-m-d H:i:s] ') . $input . PHP_EOL, FILE_APPEND);

    if ($data && $data['object'] === 'page') {
        $metaModel = new MetaIntegration($pdo);

        foreach ($data['entry'] as $entry) {
            foreach ($entry['changes'] as $change) {
                if ($change['field'] === 'leadgen') {
                    $leadgenId = $change['value']['leadgen_id'];
                    $formId = $change['value']['form_id'];
                    
                    // Find active integration for this form
                    $integration = $metaModel->getActiveByFormId($formId);
                    
                    if ($integration) {
                        try {
                            // Fetch lead details using Graph API
                            $url = "https://graph.facebook.com/v19.0/{$leadgenId}?access_token=" . $integration['access_token'];
                            $ch = curl_init($url);
                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                            $response = curl_exec($ch);
                            curl_close($ch);
                            
                            $leadData = json_decode($response, true);
                            
                            if (isset($leadData['field_data'])) {
                                $parsedLead = ['campaign_name' => $integration['page_name']];
                                foreach ($leadData['field_data'] as $field) {
                                    $name = $field['name'];
                                    $val = $field['values'][0] ?? '';
                                    if (in_array($name, ['full_name', 'first_name', 'name'])) $parsedLead['name'] = $val;
                                    if (in_array($name, ['phone_number', 'phone'])) $parsedLead['phone'] = $val;
                                    if (in_array($name, ['email'])) $parsedLead['email'] = $val;
                                }
                                
                                // Insert lead into database
                                $metaModel->createLeadFromMeta($integration['organization_id'], $parsedLead, $integration);
                                $metaModel->updateLastSynced($integration['id']);
                            }
                        } catch (Exception $e) {
                            file_put_contents(__DIR__ . '/meta_webhook.log', date('[Y-m-d H:i:s] ERROR: ') . $e->getMessage() . PHP_EOL, FILE_APPEND);
                        }
                    } else {
                        file_put_contents(__DIR__ . '/meta_webhook.log', date('[Y-m-d H:i:s] SKIPPED: ') . "No active integration found for Form ID: {$formId}" . PHP_EOL, FILE_APPEND);
                    }
                }
            }
        }
        http_response_code(200);
        exit;
    }
    
    http_response_code(404);
}
?>


