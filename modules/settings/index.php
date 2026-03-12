<?php
$pageTitle = 'Settings';
require_once '../../config/auth.php';
requireLogin();
$userRole = getUserRole();
if (!in_array($userRole, ['super_admin', 'org_owner', 'org_admin'])) {
    redirect(BASE_URL . 'modules/settings/profile.php');
}
require_once '../../config/db.php';

$orgId = getOrgId();

// Get organization info
$orgStmt = $pdo->prepare("SELECT * FROM organizations WHERE id = :id");
$orgStmt->execute(['id' => $orgId]);
$org = $orgStmt->fetch();

// Get subscription
$subStmt = $pdo->prepare("SELECT s.*, p.name as plan_name, p.max_users, p.max_leads, p.max_deals, p.price, p.billing_cycle, p.features FROM subscriptions s INNER JOIN plans p ON s.plan_id = p.id WHERE s.organization_id = :org ORDER BY s.id DESC LIMIT 1");
$subStmt->execute(['org' => $orgId]);
$subscription = $subStmt->fetch();

// Get API keys
$apiStmt = $pdo->prepare("SELECT * FROM api_keys WHERE organization_id = :org");
$apiStmt->execute(['org' => $orgId]);
$apiKeys = $apiStmt->fetchAll();

// Get all plans for upgrade
$plansStmt = $pdo->query("SELECT * FROM plans WHERE is_active = 1 ORDER BY price");
$plans = $plansStmt->fetchAll();

// User count
$userCountStmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE organization_id = :org");
$userCountStmt->execute(['org' => $orgId]);
$userCount = $userCountStmt->fetchColumn();

// Lead count
$leadCountStmt = $pdo->prepare("SELECT COUNT(*) FROM leads WHERE organization_id = :org");
$leadCountStmt->execute(['org' => $orgId]);
$leadCount = $leadCountStmt->fetchColumn();

include '../../includes/header.php';
?>

<div class="row g-4">
    <!-- Organization Settings -->
    <div class="col-lg-6">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white border-0 pt-4"><h6 class="fw-bold"><i class="bi bi-building me-2 text-primary"></i>Organization</h6></div>
            <div class="card-body">
                <div class="mb-3"><label class="form-label small fw-semibold">Company Name</label><div class="fw-bold"><?= e($org['name'] ?? 'My Company') ?></div></div>
                <div class="mb-3"><label class="form-label small fw-semibold">Email</label><div><?= e($org['email'] ?? '—') ?></div></div>
                <div class="mb-3"><label class="form-label small fw-semibold">Website</label><div><?= e($org['website'] ?? '—') ?></div></div>
            </div>
        </div>
    </div>

    <!-- Subscription -->
    <div class="col-lg-6">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white border-0 pt-4"><h6 class="fw-bold"><i class="bi bi-credit-card me-2 text-success"></i>Subscription</h6></div>
            <div class="card-body">
                <?php if ($subscription): ?>
                <div class="d-flex align-items-center mb-3">
                    <h4 class="fw-bold mb-0 me-2"><?= e($subscription['plan_name']) ?></h4>
                    <span class="badge bg-<?= $subscription['status']==='active'?'success':($subscription['status']==='trial'?'info':'danger') ?>"><?= ucfirst($subscription['status']) ?></span>
                </div>
                <div class="row g-3">
                    <div class="col-6">
                        <div class="p-2 bg-light rounded text-center"><div class="small text-muted">Users</div><div class="fw-bold"><?= $userCount ?> / <?= $subscription['max_users'] ?></div></div>
                    </div>
                    <div class="col-6">
                        <div class="p-2 bg-light rounded text-center"><div class="small text-muted">Leads</div><div class="fw-bold"><?= $leadCount ?> / <?= number_format($subscription['max_leads']) ?></div></div>
                    </div>
                </div>
                <?php if ($subscription['expires_at']): ?>
                <div class="mt-3 small text-muted">Expires: <?= formatDate($subscription['expires_at']) ?></div>
                <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Plans -->
    <div class="col-12">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white border-0 pt-4"><h6 class="fw-bold"><i class="bi bi-box me-2 text-info"></i>Available Plans</h6></div>
            <div class="card-body">
                <div class="row g-3">
                    <?php foreach ($plans as $plan): ?>
                    <div class="col-md-4">
                        <div class="card border <?= ($subscription && $subscription['plan_name'] === $plan['name']) ? 'border-primary' : '' ?> h-100">
                            <div class="card-body text-center p-4">
                                <h5 class="fw-bold"><?= e($plan['name']) ?></h5>
                                <h3 class="fw-bold my-3"><?= $plan['price'] > 0 ? formatCurrency($plan['price']) : 'Free' ?><small class="text-muted fw-normal">/<?= $plan['billing_cycle'] ?></small></h3>
                                <ul class="list-unstyled text-start small">
                                    <li class="py-1"><i class="bi bi-check-circle text-success me-2"></i><?= $plan['max_users'] ?> Users</li>
                                    <li class="py-1"><i class="bi bi-check-circle text-success me-2"></i><?= number_format($plan['max_leads']) ?> Leads</li>
                                    <li class="py-1"><i class="bi bi-check-circle text-success me-2"></i><?= number_format($plan['max_deals']) ?> Deals</li>
                                    <li class="py-1"><i class="bi bi-check-circle text-success me-2"></i>Pipeline Management</li>
                                </ul>
                                <?php if ($subscription && $subscription['plan_name'] === $plan['name']): ?>
                                    <button class="btn btn-outline-primary w-100" disabled>Current Plan</button>
                                <?php else: ?>
                                    <button class="btn btn-primary w-100">Upgrade</button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- API Keys -->
    <div class="col-12">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white border-0 pt-4"><h6 class="fw-bold"><i class="bi bi-key me-2 text-warning"></i>API Keys</h6></div>
            <div class="card-body">
                <?php foreach ($apiKeys as $key): ?>
                <div class="d-flex align-items-center p-3 bg-light rounded mb-2">
                    <div class="flex-grow-1">
                        <strong><?= e($key['name']) ?></strong>
                        <div class="text-muted small"><code><?= e(substr($key['api_key'], 0, 12)) ?>...<?= e(substr($key['api_key'], -8)) ?></code></div>
                    </div>
                    <span class="badge bg-<?= $key['is_active'] ? 'success' : 'danger' ?>"><?= $key['is_active'] ? 'Active' : 'Inactive' ?></span>
                </div>
                <?php endforeach; ?>
                <p class="text-muted small mt-3">Use API keys to authenticate external integrations. Include as <code>X-API-Key</code> header.</p>
            </div>
        </div>
    </div>
</div>
<?php include '../../includes/footer.php'; ?>


