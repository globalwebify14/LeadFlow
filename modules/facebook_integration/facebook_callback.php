<?php
require_once '../../config/auth.php';
requireLogin();
requireRole('org_owner');
require_once '../../config/db.php';

$orgId = getOrgId();

// Ensure CSRF protections match
if (!isset($_GET['state']) || $_GET['state'] !== $_SESSION['fb_oauth_state']) {
    redirect(BASE_URL . 'modules/facebook_integration/facebook_integration_settings.php', 'Invalid OAuth State (CSRF check failed).', 'danger');
}

if (isset($_GET['error'])) {
    $error = $_GET['error_description'] ?? 'OAuth Authentication failed or denied.';
    redirect(BASE_URL . 'modules/facebook_integration/facebook_integration_settings.php', $error, 'danger');
}

$code = $_GET['code'] ?? null;
if (!$code) {
    redirect(BASE_URL . 'modules/facebook_integration/facebook_integration_settings.php', 'No authorization code received.', 'danger');
}

// Fetch App Credentials to exchange the code
$stmt = $pdo->query("SELECT setting_key, setting_value FROM system_settings WHERE setting_key IN ('facebook_app_id', 'facebook_app_secret')");
$settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
$appId = $settings['facebook_app_id'] ?? '';
$appSecret = $settings['facebook_app_secret'] ?? '';

$redirectUri = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . BASE_URL . 'modules/facebook_integration/facebook_callback.php';

// STEP 1: Exchange Code for Access Token
$tokenUrl = "https://graph.facebook.com/v19.0/oauth/access_token?" . http_build_query([
    'client_id' => $appId,
    'redirect_uri' => $redirectUri,
    'client_secret' => $appSecret,
    'code' => $code
]);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $tokenUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
$response = curl_exec($ch);
curl_close($ch);

$tokenData = json_decode($response, true);

if (isset($tokenData['error'])) {
    redirect(BASE_URL . 'modules/facebook_integration/facebook_integration_settings.php', 'Token Exchange Error: ' . ($tokenData['error']['message'] ?? 'Unknown Error'), 'danger');
}

$accessToken = $tokenData['access_token'];

// STEP 2: Exchange short-lived token for LONG-LIVED token (~60 days)
$longLivedUrl = "https://graph.facebook.com/v19.0/oauth/access_token?" . http_build_query([
    'grant_type' => 'fb_exchange_token',
    'client_id' => $appId,
    'client_secret' => $appSecret,
    'fb_exchange_token' => $accessToken
]);

$ch2 = curl_init();
curl_setopt($ch2, CURLOPT_URL, $longLivedUrl);
curl_setopt($ch2, CURLOPT_RETURNTRANSFER, 1);
$response2 = curl_exec($ch2);
curl_close($ch2);

$longTokenData = json_decode($response2, true);
if (isset($longTokenData['access_token'])) {
    $accessToken = $longTokenData['access_token']; // Use long-lived token
}

// STEP 3: Use Token to get User ID
$meUrl = "https://graph.facebook.com/me?access_token=" . urlencode($accessToken);
$ch3 = curl_init();
curl_setopt($ch3, CURLOPT_URL, $meUrl);
curl_setopt($ch3, CURLOPT_RETURNTRANSFER, 1);
$response3 = curl_exec($ch3);
curl_close($ch3);

$userData = json_decode($response3, true);
$fbUserId = $userData['id'] ?? 'unknown';

// STEP 4: Save cleanly into the DB (replace any existing bindings)
$pdo->prepare("DELETE FROM facebook_integrations WHERE organization_id = ?")->execute([$orgId]);

$stmt = $pdo->prepare("INSERT INTO facebook_integrations (organization_id, facebook_user_id, access_token) VALUES (:org, :uid, :token)");
$stmt->execute([
    'org' => $orgId,
    'uid' => $fbUserId,
    'token' => $accessToken
]);

// Redirect to sync pages dynamically
header('Location: ' . BASE_URL . 'modules/facebook_integration/facebook_pages.php');
exit;
?>
