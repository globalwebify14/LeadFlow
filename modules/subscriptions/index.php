<?php
$pageTitle = 'Subscription Plans';
require_once '../../config/auth.php';
requireLogin();
requireRole(['super_admin']);
require_once '../../config/db.php';
require_once '../../models/Plan.php';
require_once '../../models/ActivityLog.php';

$planModel = new Plan($pdo);

if (isset($_GET['action']) && isset($_GET['id'])) {
    $pid = (int)$_GET['id'];
    if ($_GET['action'] === 'delete') {
        // Check if any org uses this plan
        $cnt = $pdo->prepare("SELECT COUNT(*) FROM organizations WHERE subscription_plan_id = :id");
        $cnt->execute(['id' => $pid]);
        if ($cnt->fetchColumn() > 0) {
            redirect(BASE_URL . 'modules/subscriptions/', 'Cannot delete: organizations are using this plan.', 'danger');
        }
        $planModel->delete($pid);
        ActivityLog::write($pdo, 'plan_deleted', "Plan ID $pid deleted");
    }
    if ($_GET['action'] === 'toggle') { $planModel->toggleStatus($pid); }
    header('Location: ' . BASE_URL . 'modules/subscriptions/'); exit;
}

$plans = $planModel->getAll();
include '../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-wallet2 me-2 text-primary"></i>Subscription Plans</h4>
        <p class="text-muted small mb-0">Manage SaaS pricing plans</p>
    </div>
    <a href="<?= BASE_URL ?>modules/subscriptions/create.php" class="btn btn-primary fw-semibold">
        <i class="bi bi-plus-lg me-1"></i> Add New Plan
    </a>
</div>

<div class="row g-4">
    <?php if (!empty($plans)): ?>
        <?php foreach ($plans as $plan): ?>
        <div class="col-lg-4">
            <div class="card shadow-sm border-0 h-100 <?= !$plan['is_active'] ? 'opacity-75' : '' ?>">
                <div class="card-header border-0 pt-4 pb-2" style="background:linear-gradient(135deg,#6366f1,#4f46e5);">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="fw-bold text-white mb-0"><?= e($plan['name']) ?></h5>
                        <span class="badge bg-white text-<?= $plan['is_active'] ? 'success' : 'secondary' ?>"><?= $plan['is_active'] ? 'Active' : 'Inactive' ?></span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-baseline mb-3">
                        <h2 class="fw-bold mb-0">₹<?= number_format($plan['price']) ?></h2>
                        <span class="text-muted ms-1 small">/month</span>
                    </div>
                    <?php if ($plan['yearly_price'] ?? 0): ?>
                    <p class="text-muted small mb-3">₹<?= number_format($plan['yearly_price']) ?>/year</p>
                    <?php endif; ?>
                    <ul class="list-unstyled mb-3">
                        <li class="mb-2 small"><i class="bi bi-people me-2 text-primary"></i><?= $plan['max_users'] ?> Users</li>
                        <li class="mb-2 small"><i class="bi bi-person-lines-fill me-2 text-success"></i><?= number_format($plan['max_leads']) ?> Leads</li>
                        <li class="mb-2 small"><i class="bi bi-trophy me-2 text-warning"></i><?= number_format($plan['max_deals']) ?> Deals</li>
                        <li class="mb-2 small"><i class="bi bi-hdd me-2 text-info"></i><?= round(($plan['storage_limit'] ?? 1024) / 1024, 1) ?> GB Storage</li>
                    </ul>
                    <div class="border-top pt-3 small text-muted"><?= $plan['total_orgs'] ?> organization(s) using this plan</div>
                </div>
                <div class="card-footer bg-white border-0 pb-4 d-flex gap-2">
                    <a href="<?= BASE_URL ?>modules/subscriptions/edit.php?id=<?= $plan['id'] ?>" class="btn btn-outline-primary btn-sm flex-grow-1"><i class="bi bi-pencil me-1"></i>Edit</a>
                    <a href="?action=toggle&id=<?= $plan['id'] ?>" class="btn btn-outline-<?= $plan['is_active'] ? 'warning' : 'success' ?> btn-sm"><?= $plan['is_active'] ? 'Deactivate' : 'Activate' ?></a>
                    <a href="?action=delete&id=<?= $plan['id'] ?>" class="btn btn-outline-danger btn-sm" onclick="return confirm('Delete this plan?')"><i class="bi bi-trash"></i></a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="col-12 text-center py-5 text-muted">
            <i class="bi bi-wallet2 fs-1 d-block mb-2"></i>
            No plans found. <a href="<?= BASE_URL ?>modules/subscriptions/create.php" class="fw-semibold text-primary">Create the first plan →</a>
        </div>
    <?php endif; ?>
</div>

<?php include '../../includes/footer.php'; ?>
