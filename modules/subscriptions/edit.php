<?php
$pageTitle = 'Edit Plan';
require_once '../../config/auth.php';
requireLogin();
requireRole(['super_admin']);
require_once '../../config/db.php';
require_once '../../models/Plan.php';
require_once '../../models/ActivityLog.php';

$planModel = new Plan($pdo);
$id = (int)($_GET['id'] ?? 0);
$plan = $planModel->getById($id);
if (!$plan) { header('Location: ' . BASE_URL . 'modules/subscriptions/'); exit; }
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST['name'])) $errors[] = 'Plan name is required.';
    if (empty($errors)) {
        $planModel->update($id, $_POST);
        ActivityLog::write($pdo, 'plan_updated', "Plan '{$_POST['name']}' updated");
        redirect(BASE_URL . 'modules/subscriptions/', 'Plan updated!', 'success');
    }
}
include '../../includes/header.php';
?>

<div class="mb-4">
    <a href="<?= BASE_URL ?>modules/subscriptions/" class="text-muted text-decoration-none small"><i class="bi bi-arrow-left me-1"></i>Back to Plans</a>
    <h4 class="fw-bold mt-2 mb-1"><i class="bi bi-pencil me-2 text-primary"></i>Edit Plan: <?= e($plan['name']) ?></h4>
</div>

<?php if ($errors): ?><div class="alert alert-danger"><?= implode('<br>', array_map('e', $errors)) ?></div><?php endif; ?>

<div class="card shadow-sm border-0">
    <div class="card-body">
        <form method="POST">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Plan Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" value="<?= e($_POST['name'] ?? $plan['name']) ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Description</label>
                    <input type="text" name="description" class="form-control" value="<?= e($_POST['description'] ?? $plan['description'] ?? '') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Monthly Price (₹)</label>
                    <input type="number" name="monthly_price" class="form-control" value="<?= e($_POST['monthly_price'] ?? $plan['price']) ?>" min="0" step="0.01">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Yearly Price (₹)</label>
                    <input type="number" name="yearly_price" class="form-control" value="<?= e($_POST['yearly_price'] ?? $plan['yearly_price'] ?? 0) ?>" min="0" step="0.01">
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold">Max Users</label>
                    <input type="number" name="max_users" class="form-control" value="<?= e($_POST['max_users'] ?? $plan['max_users']) ?>" min="1">
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold">Max Leads</label>
                    <input type="number" name="max_leads" class="form-control" value="<?= e($_POST['max_leads'] ?? $plan['max_leads']) ?>" min="1">
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold">Max Deals</label>
                    <input type="number" name="max_deals" class="form-control" value="<?= e($_POST['max_deals'] ?? $plan['max_deals']) ?>" min="1">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Storage Limit (MB)</label>
                    <input type="number" name="storage_limit" class="form-control" value="<?= e($_POST['storage_limit'] ?? $plan['storage_limit'] ?? 1024) ?>" min="256">
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Status</label>
                    <select name="is_active" class="form-select">
                        <option value="1" <?= ($plan['is_active'] ?? 1) ? 'selected' : '' ?>>Active</option>
                        <option value="0" <?= !($plan['is_active'] ?? 1) ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>
            </div>
            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary fw-semibold px-4"><i class="bi bi-check-lg me-1"></i>Save Changes</button>
                <a href="<?= BASE_URL ?>modules/subscriptions/" class="btn btn-outline-secondary px-4">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
