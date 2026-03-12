<?php
$pageTitle = 'Edit Organization';
require_once '../../config/auth.php';
requireLogin();
requireRole(['super_admin']);
require_once '../../config/db.php';
require_once '../../models/Organization.php';
require_once '../../models/Plan.php';
require_once '../../models/ActivityLog.php';

$orgModel = new Organization($pdo);
$planModel = new Plan($pdo);
$id = (int)($_GET['id'] ?? 0);
$org = $orgModel->getById($id);
if (!$org) { header('Location: ' . BASE_URL . 'modules/organizations/'); exit; }
$plans = $planModel->getActive();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'name'       => trim($_POST['name'] ?? ''),
        'owner_name' => trim($_POST['owner_name'] ?? ''),
        'email'      => trim($_POST['email'] ?? ''),
        'phone'      => trim($_POST['phone'] ?? ''),
        'address'    => trim($_POST['address'] ?? ''),
        'plan_id'    => (int)($_POST['plan_id'] ?? 0) ?: null,
        'status'     => $_POST['status'] ?? 'active',
    ];
    if (!$data['name']) $errors[] = 'Name is required.';
    if (empty($errors)) {
        $orgModel->update($id, $data);
        ActivityLog::write($pdo, 'org_updated', "Organization '{$data['name']}' updated");
        redirect(BASE_URL . 'modules/organizations/', 'Organization updated!', 'success');
    }
}

include '../../includes/header.php';
?>

<div class="mb-4">
    <a href="<?= BASE_URL ?>modules/organizations/" class="text-muted text-decoration-none small"><i class="bi bi-arrow-left me-1"></i>Back</a>
    <h4 class="fw-bold mt-2 mb-1"><i class="bi bi-pencil me-2 text-primary"></i>Edit Organization</h4>
</div>

<?php if ($errors): ?>
<div class="alert alert-danger border-0 rounded-3">
    <ul class="mb-0 ps-3"><?php foreach ($errors as $e): ?><li><?= e($e) ?></li><?php endforeach; ?></ul>
</div>
<?php endif; ?>

<form method="POST">
    <div class="card shadow-sm border-0">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Organization Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" value="<?= e($_POST['name'] ?? $org['name']) ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Owner Name</label>
                    <input type="text" name="owner_name" class="form-control" value="<?= e($_POST['owner_name'] ?? $org['owner_name']) ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Email</label>
                    <input type="email" name="email" class="form-control" value="<?= e($_POST['email'] ?? $org['email']) ?>">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Phone</label>
                    <input type="text" name="phone" class="form-control" value="<?= e($_POST['phone'] ?? $org['phone']) ?>">
                </div>
                <div class="col-md-12">
                    <label class="form-label fw-semibold">Address</label>
                    <textarea name="address" class="form-control" rows="2"><?= e($_POST['address'] ?? $org['address']) ?></textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Subscription Plan</label>
                    <select name="plan_id" class="form-select">
                        <option value="">— No Plan —</option>
                        <?php foreach ($plans as $p): ?>
                        <option value="<?= $p['id'] ?>" <?= ($org['subscription_plan_id'] == $p['id']) ? 'selected' : '' ?>>
                            <?= e($p['name']) ?> (₹<?= number_format($p['price']) ?>/mo)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-semibold">Status</label>
                    <select name="status" class="form-select">
                        <option value="active" <?= $org['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="inactive" <?= $org['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                        <option value="suspended" <?= $org['status'] === 'suspended' ? 'selected' : '' ?>>Suspended</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="card-footer bg-white border-0 pb-4 d-flex gap-2">
            <button type="submit" class="btn btn-primary fw-semibold px-4"><i class="bi bi-check-lg me-1"></i>Save Changes</button>
            <a href="<?= BASE_URL ?>modules/organizations/" class="btn btn-outline-secondary px-4">Cancel</a>
        </div>
    </div>
</form>

<?php include '../../includes/footer.php'; ?>
