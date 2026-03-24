<?php
require_once '../../config/auth.php';
requireLogin();
requireRole('org_owner');
require_once '../../config/db.php';

// Fetch App ID
$stmt = $pdo->query("SELECT setting_value FROM system_settings WHERE setting_key = 'facebook_app_id'");
$appId = $stmt->fetchColumn();

if (empty($appId)) {
    redirect(BASE_URL . 'modules/facebook_integration/facebook_integration_settings.php', 'System missing Facebook App ID Config', 'danger');
}

// Generate the unique state to prevent CSRF attacks
$stateStr = bin2hex(random_bytes(16));
$_SESSION['fb_oauth_state'] = $stateStr;

// The Callback URI must match EXACTLY what is saved in the Facebook Developer Portal App settings.
$redirectUri = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . BASE_URL . 'modules/facebook_integration/facebook_callback.php';

$permissions = ['email', 'public_profile', 'pages_show_list', 'pages_read_engagement', 'leads_retrieval', 'pages_manage_ads'];
$scope = implode(',', $permissions);

$loginUrl = "https://www.facebook.com/v19.0/dialog/oauth" . 
    "?client_id=" . urlencode($appId) . 
    "&redirect_uri=" . urlencode($redirectUri) . 
    "&scope=" . urlencode($scope) . 
    "&state=" . urlencode($stateStr) .
    "&auth_type=rerequest";

// Redirect to Meta's login page
header('Location: ' . $loginUrl);
exit;
?>
