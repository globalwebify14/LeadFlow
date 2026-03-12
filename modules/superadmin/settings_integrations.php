<?php
$pageTitle = 'Integration Settings';
require_once '../../config/auth.php';
requireLogin();
requireRole('super_admin');
require_once '../../config/db.php';

// Handle setting updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $pdo->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES (:key, :val) ON DUPLICATE KEY UPDATE setting_value = :val2");
    
    // Facebook App ID
    $appId = trim($_POST['facebook_app_id'] ?? '');
    $stmt->execute(['key' => 'facebook_app_id', 'val' => $appId, 'val2' => $appId]);
    
    // Facebook App Secret
    $appSecret = trim($_POST['facebook_app_secret'] ?? '');
    if (!empty($appSecret)) { // Only update if provided to avoid blanking out saved secrets
        $stmt->execute(['key' => 'facebook_app_secret', 'val' => $appSecret, 'val2' => $appSecret]);
    }
    
    // Webhook Verify Token
    $verifyToken = trim($_POST['webhook_verify_token'] ?? '');
    if (!empty($verifyToken)) {
        $stmt->execute(['key' => 'webhook_verify_token', 'val' => $verifyToken, 'val2' => $verifyToken]);
    }

    $success = "Integration settings updated successfully.";
}

// Fetch all settings
$stmt = $pdo->query("SELECT setting_key, setting_value FROM system_settings");
$settingsRow = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

$facebook_app_id = $settingsRow['facebook_app_id'] ?? '';
$webhook_verify_token = $settingsRow['webhook_verify_token'] ?? '';
$has_secret = !empty($settingsRow['facebook_app_secret']);

$globalWebhookUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . BASE_URL . 'modules/facebook_integration/facebook_webhook.php';

include '../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">System Integrations</h4>
        <p class="text-muted small mb-0">Manage global API keys and App configurations for the entire platform.</p>
    </div>
</div>

<?php if (isset($success)): ?>
    <div class="alert alert-success border-0 shadow-sm"><i class="bi bi-check-circle me-2"></i><?= $success ?></div>
<?php endif; ?>

<form method="POST">
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white border-0 pt-4 pb-0">
                    <h6 class="fw-bold mb-0"><i class="bi bi-facebook text-primary me-2"></i>Meta Lead Ads App Config</h6>
                    <p class="small text-muted mt-2 mb-0">Enter the credentials from your Meta Developer App to enable OAuth and Webhooks for all tenant organizations.</p>
                </div>
                <div class="card-body mt-3">
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Facebook App ID</label>
                        <input type="text" class="form-control" name="facebook_app_id" value="<?= e($facebook_app_id) ?>" placeholder="e.g. 1042530xxxxxxxx" required autocomplete="off">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Facebook App Secret</label>
                        <input type="password" class="form-control" name="facebook_app_secret" placeholder="<?= $has_secret ? '•••••••••••••••• (Leave blank to keep existing)' : 'Enter App Secret' ?>" <?= $has_secret ? '' : 'required' ?> autocomplete="new-password">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Webhook Verify Token</label>
                        <input type="text" class="form-control" name="webhook_verify_token" value="<?= e($webhook_verify_token) ?>" placeholder="Unique random string for verifying webhooks" required>
                        <div class="form-text">Used by Meta to verify your webhook endpoint ownership.</div>
                    </div>

                    <hr class="my-4">
                    
                    <h6 class="fw-bold mb-3">Webhook Endpoint URL</h6>
                    <p class="small text-muted">Paste this URL into your Meta App's Webhook configuration pane.</p>
                    <div class="input-group">
                        <input type="text" class="form-control bg-light" value="<?= e($globalWebhookUrl) ?>" readonly>
                        <button class="btn btn-outline-secondary" type="button" onclick="navigator.clipboard.writeText('<?= e($globalWebhookUrl) ?>'); alert('Copied!');"><i class="bi bi-clipboard"></i></button>
                    </div>
                </div>
                <div class="card-footer bg-white border-0 pb-4 text-end">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i> Save Configuration</button>
                </div>
            </div>
        </div>
    </div>
</form>

<?php include '../../includes/footer.php'; ?>
