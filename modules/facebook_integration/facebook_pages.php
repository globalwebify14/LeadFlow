<?php
require_once '../../config/auth.php';
requireLogin();
requireRole('org_owner');
require_once '../../config/db.php';

$orgId = getOrgId();

// Verify Integration Exists
$stmt = $pdo->prepare("SELECT access_token FROM facebook_integrations WHERE organization_id = ?");
$stmt->execute([$orgId]);
$accessToken = $stmt->fetchColumn();

if (!$accessToken) {
    redirect(BASE_URL . 'modules/facebook_integration/facebook_integration_settings.php', 'Please connect your Facebook account first.', 'danger');
}

// Ping Graph API to get all pages the user can manage
$url = "https://graph.facebook.com/v19.0/me/accounts?access_token=" . urlencode($accessToken);
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
$response = curl_exec($ch);
curl_close($ch);

$data = json_decode($response, true);
if (isset($data['error'])) {
    redirect(BASE_URL . 'modules/facebook_integration/facebook_integration_settings.php', 'Meta API Error: ' . ($data['error']['message'] ?? 'Unknown Error'), 'danger');
}

$pages = $data['data'] ?? [];

$pdo->beginTransaction();
try {
    // Clear old pages
    $pdo->prepare("DELETE FROM facebook_pages WHERE organization_id = ?")->execute([$orgId]);

    // Insert new pages
    $stmt = $pdo->prepare("INSERT INTO facebook_pages (organization_id, page_id, page_name, page_access_token) VALUES (:org, :pid, :name, :token)");

    foreach ($pages as $p) {
        $stmt->execute([
            'org' => $orgId,
            'pid' => $p['id'],
            'name' => $p['name'],
            'token' => $p['access_token']
        ]);

        // [CRITICAL] Subscribe Page to Webhooks
        subscribePageToApp($p['id'], $p['access_token']);
    }
    $pdo->commit();
} catch (Exception $e) {
    $pdo->rollBack();
    error_log("FB Sync Error: " . $e->getMessage());
    redirect(BASE_URL . 'modules/facebook_integration/facebook_integration_settings.php', 'Database error syncing pages: ' . $e->getMessage(), 'danger');
}

/**
 * Automates the POST /PAGE_ID/subscribed_apps call to activate webhooks.
 */
function subscribePageToApp($pageId, $pageToken) {
    $url = "https://graph.facebook.com/v19.0/{$pageId}/subscribed_apps";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'subscribed_fields' => 'leadgen',
        'access_token' => $pageToken
    ]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $response = curl_exec($ch);
    $info = curl_getinfo($ch);
    curl_close($ch);

    $res = json_decode($response, true);
    if ($info['http_code'] !== 200 || !($res['success'] ?? false)) {
        error_log("FB Sync Error: Failed to subscribe page {$pageId} to webhooks: " . $response);
    } else {
        error_log("FB Sync Success: Successfully subscribed page {$pageId} to 'leadgen' webhook.");
    }
}

// Instantly cascade and fetch forms now that pages exist
header('Location: ' . BASE_URL . 'modules/facebook_integration/facebook_forms.php');
exit;
?>
