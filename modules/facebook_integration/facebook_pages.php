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
    }
    $pdo->commit();
} catch (Exception $e) {
    $pdo->rollBack();
    redirect(BASE_URL . 'modules/facebook_integration/facebook_integration_settings.php', 'Database error syncing pages.', 'danger');
}

// Instantly cascade and fetch forms now that pages exist
header('Location: ' . BASE_URL . 'modules/facebook_integration/facebook_forms.php');
exit;
?>
