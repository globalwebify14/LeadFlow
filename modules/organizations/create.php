<?php
$pageTitle = 'Create Organization';
require_once '../../config/auth.php';
requireLogin();
requireRole(['super_admin']);
require_once '../../config/db.php';
require_once '../../models/Organization.php';
require_once '../../models/Plan.php';
require_once '../../models/ActivityLog.php';

$orgModel = new Organization($pdo);
$planModel = new Plan($pdo);
$plans = $planModel->getActive();
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name'] ?? '');
    $oName    = trim($_POST['owner_name'] ?? '');
    $oEmail   = trim($_POST['owner_email'] ?? '');
    $oPhone   = trim($_POST['owner_phone'] ?? '');
    $oPassword= trim($_POST['owner_password'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');
    $address  = trim($_POST['address'] ?? '');
    $planId   = (int)($_POST['plan_id'] ?? 0);
    $status   = $_POST['status'] ?? 'active';

    if (!$name) $errors[] = 'Organization name is required.';
    if (!$oName) $errors[] = 'Owner name is required.';
    if (!$oEmail || !filter_var($oEmail, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid owner email is required.';
    if (!$oPassword || strlen($oPassword) < 6) $errors[] = 'Owner password must be at least 6 characters.';

    // Check email uniqueness
    $chk = $pdo->prepare("SELECT id FROM users WHERE email = :email");
    $chk->execute(['email' => $oEmail]);
    if ($chk->fetch()) $errors[] = 'A user with this email already exists.';

    if (empty($errors)) {
        // Create organization
        $orgId = $orgModel->create([
            'name' => $name, 'owner_name' => $oName, 'email' => $oEmail,
            'phone' => $phone, 'address' => $address, 'plan_id' => $planId ?: null, 'status' => $status
        ]);

        // Create owner user
        $hash = password_hash($oPassword, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (organization_id, name, email, password, phone, role, is_active) VALUES (:org, :name, :email, :pass, :phone, 'org_owner', 1)");
        $stmt->execute(['org' => $orgId, 'name' => $oName, 'email' => $oEmail, 'pass' => $hash, 'phone' => $oPhone]);
        $ownerId = $pdo->lastInsertId();
        $orgModel->setOwner($orgId, $ownerId);

        // Log activity
        ActivityLog::write($pdo, 'org_created', "Organization '{$name}' created with owner '{$oName}'");

        redirect(BASE_URL . 'modules/organizations/', "Organization '{$name}' created successfully!", 'success');
    }
}

include '../../includes/header.php';
?>

<div class="mb-4">
    <a href="<?= BASE_URL ?>modules/organizations/" class="text-muted text-decoration-none small"><i class="bi bi-arrow-left me-1"></i>Back to Organizations</a>
    <h4 class="fw-bold mt-2 mb-1"><i class="bi bi-building-add me-2 text-primary"></i>Create Organization</h4>
</div>

<?php if ($errors): ?>
<div class="alert alert-danger border-0 rounded-3">
    <ul class="mb-0 ps-3"><?php foreach ($errors as $e): ?><li><?= e($e) ?></li><?php endforeach; ?></ul>
</div>
<?php endif; ?>

<form method="POST">
    <div class="row g-4">
        <!-- Organization Info -->
        <div class="col-lg-6">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white border-0 pt-4 pb-0">
                    <h6 class="fw-bold mb-0"><i class="bi bi-building me-2 text-primary"></i>Organization Info</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Organization Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" value="<?= e($_POST['name'] ?? '') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Phone</label>
                        <input type="text" name="phone" class="form-control" value="<?= e($_POST['phone'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Address</label>
                        <textarea name="address" class="form-control" rows="3"><?= e($_POST['address'] ?? '') ?></textarea>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Subscription Plan</label>
                            <select name="plan_id" class="form-select">
                                <option value="">— No Plan —</option>
                                <?php foreach ($plans as $p): ?>
                                <option value="<?= $p['id'] ?>" <?= ($_POST['plan_id'] ?? '') == $p['id'] ? 'selected' : '' ?>>
                                    <?= e($p['name']) ?> (₹<?= number_format($p['price']) ?>/mo)
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Status</label>
                            <select name="status" class="form-select">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Owner Account -->
        <div class="col-lg-6">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white border-0 pt-4 pb-0">
                    <h6 class="fw-bold mb-0"><i class="bi bi-person-plus me-2 text-success"></i>Owner Account</h6>
                    <p class="text-muted small mb-0 mt-1">An org_owner user will be created automatically</p>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Owner Name <span class="text-danger">*</span></label>
                        <input type="text" name="owner_name" class="form-control" value="<?= e($_POST['owner_name'] ?? '') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Owner Email <span class="text-danger">*</span></label>
                        <input type="email" name="owner_email" class="form-control" value="<?= e($_POST['owner_email'] ?? '') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Owner Phone</label>
                        <input type="text" name="owner_phone" class="form-control" value="<?= e($_POST['owner_phone'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Temporary Password <span class="text-danger">*</span></label>
                        <input type="text" name="owner_password" class="form-control" value="<?= e($_POST['owner_password'] ?? '') ?>" required minlength="6">
                        <div class="form-text">Share this with the owner as their login.</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-4 d-flex gap-2">
        <button type="submit" class="btn btn-primary px-4 fw-semibold"><i class="bi bi-check-lg me-1"></i>Create Organization</button>
        <a href="<?= BASE_URL ?>modules/organizations/" class="btn btn-outline-secondary px-4">Cancel</a>
    </div>
</form>

<?php include '../../includes/footer.php'; ?>
