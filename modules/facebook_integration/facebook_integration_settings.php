<?php
$pageTitle = 'Facebook Leads Integration';
require_once '../../config/auth.php';
requireLogin();
requireRole('org_owner');
require_once '../../config/db.php';

$orgId = getOrgId();

// Fetch System Configs required for OAuth
$stmt = $pdo->query("SELECT setting_key, setting_value FROM system_settings WHERE setting_key IN ('facebook_app_id', 'facebook_app_secret', 'webhook_verify_token')");
$settingsRow = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
$appId = $settingsRow['facebook_app_id'] ?? '';
$appSecret = $settingsRow['facebook_app_secret'] ?? '';
$verifyToken = $settingsRow['webhook_verify_token'] ?? 'rand0m_v3r1fy_t0k3n_2024';

// Check if System Settings are configured
if (empty($appId) || empty($appSecret)) {
    $systemError = "Facebook OAuth is not configured. Please contact the Super Admin to provide the Facebook App ID and Secret.";
}

// Fetch the current token state for the org
$stmt = $pdo->prepare("SELECT * FROM facebook_integrations WHERE organization_id = :org");
$stmt->execute(['org' => $orgId]);
$integration = $stmt->fetch();

// Fetch connected pages
$stmt = $pdo->prepare("SELECT * FROM facebook_pages WHERE organization_id = :org");
$stmt->execute(['org' => $orgId]);
$pages = $stmt->fetchAll();

// Fetch saved forms
$stmt = $pdo->prepare("SELECT * FROM facebook_forms WHERE organization_id = :org");
$stmt->execute(['org' => $orgId]);
$forms = $stmt->fetchAll();

// Handle disconnect
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'disconnect') {
    $pdo->prepare("DELETE FROM facebook_integrations WHERE organization_id = ?")->execute([$orgId]);
    redirect(BASE_URL . 'modules/facebook_integration/facebook_integration_settings.php', 'Facebook Account Disconnected.', 'info');
}

include '../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-facebook text-primary me-2"></i>Facebook Lead Ads</h4>
        <p class="text-muted small mb-0">Connect your Facebook account to automatically sync leads from your ad campaigns.</p>
    </div>
    <?php if ($integration): ?>
        <form method="POST" onsubmit="return confirm('Are you sure you want to disconnect? Auto-syncing will stop.');">
            <input type="hidden" name="action" value="disconnect">
            <button class="btn btn-outline-danger btn-sm fw-semibold"><i class="bi bi-plug me-1"></i>Disconnect Account</button>
        </form>
    <?php endif; ?>
</div>

<?php if (isset($systemError)): ?>
    <div class="alert alert-danger border-0 shadow-sm"><i class="bi bi-exclamation-triangle me-2"></i><?= $systemError ?></div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-lg-4">
        <!-- Connection Status -->
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white border-0 pt-4 pb-0">
                <h6 class="fw-bold mb-0">Account Connection</h6>
            </div>
            <div class="card-body">
                <?php if ($integration): ?>
                    <div class="text-center py-4">
                        <div class="mb-3">
                            <i class="bi bi-check-circle-fill text-success" style="font-size: 3rem;"></i>
                        </div>
                        <h5 class="fw-bold text-dark">Connected</h5>
                        <p class="text-muted small">Your Facebook user ID <span class="fw-semibold text-dark"><?= e($integration['facebook_user_id']) ?></span> is actively linked to the CRM.</p>
                        <a href="<?= BASE_URL ?>modules/facebook_integration/facebook_pages.php" class="btn btn-primary btn-sm w-100 mt-2"><i class="bi bi-arrow-clockwise me-1"></i>Refresh Pages Data</a>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4">
                        <div class="mb-3">
                            <i class="bi bi-facebook text-primary" style="font-size: 3rem;"></i>
                        </div>
                        <h5 class="fw-bold text-dark">Not Connected</h5>
                        <p class="text-muted small mb-4">Authenticate with Meta to start pulling leads from your ads straight into your pipeline.</p>
                        
                        <?php if (!empty($appId) && !empty($appSecret)): ?>
                            <a href="<?= BASE_URL ?>modules/facebook_integration/facebook_connect.php" class="btn btn-primary w-100 fw-bold"><i class="bi bi-box-arrow-in-right me-2"></i>Connect Facebook Account</a>
                        <?php else: ?>
                            <button class="btn btn-secondary w-100 fw-bold" disabled>Setup Incomplete</button>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Webhook Configuration -->
        <div class="card shadow-sm border-0 mt-4">
            <div class="card-header bg-white border-0 pt-4 pb-0">
                <h6 class="fw-bold mb-0">Webhook Configuration</h6>
            </div>
            <div class="card-body">
                <p class="text-muted small mb-3">Copy these to your <strong>Meta App Dashboard > Webhooks</strong> (Page Object) to enable real-time leads.</p>
                <div class="mb-3">
                    <label class="form-label small fw-bold text-uppercase text-muted mb-1">Callback URL</label>
                    <div class="input-group">
                        <input type="text" class="form-control form-control-sm bg-light" value="<?= BASE_URL ?>modules/facebook_integration/facebook_webhook.php" readonly id="webhookUrl">
                        <button class="btn btn-outline-secondary btn-sm" onclick="copyToClipboard('webhookUrl')"><i class="bi bi-clipboard"></i></button>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold text-uppercase text-muted mb-1">Verify Token</label>
                    <div class="input-group">
                        <input type="text" class="form-control form-control-sm bg-light" value="<?= e($verifyToken) ?>" readonly id="verifyToken">
                        <button class="btn btn-outline-secondary btn-sm" onclick="copyToClipboard('verifyToken')"><i class="bi bi-clipboard"></i></button>
                    </div>
                </div>
                <div class="alert alert-info border-0 py-2 small mb-0">
                    <i class="bi bi-info-circle me-1"></i> Ensure <code>leadgen</code> field is subscribed.
                </div>
                <div class="mt-3 text-center">
                    <a href="<?= BASE_URL ?>modules/facebook_integration/debug_webhook.php" class="text-decoration-none small fw-bold"><i class="bi bi-bug me-1"></i>Debug Configuration & Logs</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Active Configurations -->
    <div class="col-lg-8">
        <div class="card shadow-sm border-0 mb-4 h-100">
            <div class="card-header bg-white border-0 pt-4 pb-0 d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0">Discovered Pages & Forms</h6>
            </div>
            <div class="card-body">
                <?php if ($integration && !empty($pages)): ?>
                    <div class="list-group list-group-flush border-bottom mb-4">
                        <?php foreach ($pages as $p): ?>
                            <div class="list-group-item px-0 py-3 border-0 border-bottom">
                                <div class="d-flex w-100 justify-content-between align-items-center">
                                    <div>
                                        <h6 class="fw-bold mb-1"><i class="bi bi-flag-fill text-primary me-2"></i><?= e($p['page_name']) ?></h6>
                                        <div class="small text-muted">Page ID: <?= e($p['page_id']) ?></div>
                                    </div>
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill"><i class="bi bi-check2-circle me-1"></i>Token Valid</span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="mt-4">
                        <h6 class="fw-bold mb-3">Syncing Forms Database</h6>
                        <?php if (!empty($forms)): ?>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="bg-light">
                                        <tr>
                                            <th>Form Name</th>
                                            <th>Form ID</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($forms as $f): ?>
                                            <tr>
                                                <td class="fw-medium"><?= e($f['form_name']) ?></td>
                                                <td class="font-monospace small text-muted"><?= e($f['form_id']) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="text-muted small">No forms discovered yet.</p>
                            <a href="<?= BASE_URL ?>modules/facebook_integration/facebook_forms.php" class="btn btn-outline-primary btn-sm"><i class="bi bi-cloud-download me-1"></i>Fetch Forms</a>
                        <?php endif; ?>
                    </div>

                <?php elseif ($integration && empty($pages)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-flag text-muted fs-1 d-block mb-3"></i>
                        <h6 class="fw-bold">No Pages Synced</h6>
                        <p class="text-muted small">We need to download your page data to map lead forms.</p>
                        <a href="<?= BASE_URL ?>modules/facebook_integration/facebook_pages.php" class="btn btn-primary btn-sm mt-2">Fetch Pages</a>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="bi bi-lock text-muted fs-1 d-block mb-3"></i>
                        <h6 class="fw-bold text-muted">Awaiting Connection</h6>
                        <p class="text-muted small mb-0">Connect your account to view your pages and lead forms.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
function copyToClipboard(id) {
    var copyText = document.getElementById(id);
    copyText.select();
    copyText.setSelectionRange(0, 99999);
    navigator.clipboard.writeText(copyText.value);
    
    // Simple feedback
    const btn = event.currentTarget;
    const icon = btn.querySelector('i');
    icon.classList.replace('bi-clipboard', 'bi-check');
    setTimeout(() => icon.classList.replace('bi-check', 'bi-clipboard'), 2000);
}
</script>
<?php include '../../includes/footer.php'; ?>
