<?php
$pageTitle = 'Edit Team Member';
require_once '../../config/auth.php';
requireLogin();
requireRole(['super_admin', 'org_owner', 'org_admin']);
require_once '../../config/db.php';
require_once '../../models/User.php';



$orgId = getOrgId();
$userModel = new User($pdo);
$currentUserRole = getUserRole();

if (!isset($_GET['id'])) { redirect(BASE_URL . 'modules/users/'); }
$user = $userModel->getUserById((int)$_GET['id']);
if (!$user) { redirect(BASE_URL . 'modules/users/', 'Team member not found.', 'danger'); }

if ($currentUserRole === 'org_admin' && in_array($user['role'], ['super_admin', 'org_owner'])) {
    redirect(BASE_URL . 'modules/users/', 'Permission denied.', 'danger');
}

// Determine allowed roles for moving users into
$allowedRoles = ['agent' => 'Sales Agent', 'team_lead' => 'Team Lead'];
if (in_array($currentUserRole, ['super_admin', 'org_owner'])) {
    $allowedRoles['org_admin'] = 'Organization Admin';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'name' => trim($_POST['name']),
        'email' => trim($_POST['email']),
        'phone' => trim($_POST['phone'] ?? ''),
        'role' => $_POST['role'] ?? 'agent',
        'password' => $_POST['password'] ?? '',
        'is_active' => isset($_POST['is_active']) ? 1 : 0,
        'availability_status' => $_POST['availability_status'] ?? 'active',
    ];
    
    if (!array_key_exists($data['role'], $allowedRoles)) {
        $error = 'Invalid role selected.';
    } elseif ($userModel->emailExists($data['email'], $user['id'])) {
        $error = 'Email already exists.';
    } else {
        $userModel->updateUser($user['id'], $data);
        redirect(BASE_URL . 'modules/users/', 'Team member updated!', 'success');
    }
}
include '../../includes/header.php';
?>
<div class="row justify-content-center"><div class="col-lg-6">
<div class="card shadow-sm border-0">
    <div class="card-header bg-white border-0 pt-4"><h5 class="fw-bold mb-0"><i class="bi bi-pencil text-primary me-2"></i>Edit Team Member</h5></div>
    <div class="card-body p-4">
        <?php if (!empty($error)): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
        <form method="POST">
            <div class="mb-3"><label class="form-label">Full Name *</label><input type="text" class="form-control" name="name" value="<?= e($user['name']) ?>" required></div>
            <div class="mb-3"><label class="form-label">Email *</label><input type="email" class="form-control" name="email" value="<?= e($user['email']) ?>" required></div>
            <div class="mb-3"><label class="form-label">Phone</label><input type="text" class="form-control" name="phone" value="<?= e($user['phone']) ?>"></div>
            <div class="mb-3"><label class="form-label">New Password <small class="text-muted">(leave blank to keep current)</small></label><input type="password" class="form-control" name="password" minlength="6"></div>
            <div class="mb-3">
                <label class="form-label">Role</label>
                <select class="form-select" name="role">
                    <?php foreach ($allowedRoles as $val => $label): ?>
                        <option value="<?= $val ?>" <?= $user['role'] === $val ? 'selected' : '' ?>><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Availability Status</label>
                <select class="form-select" name="availability_status">
                    <option value="active" <?= ($user['availability_status']??'active') === 'active' ? 'selected' : '' ?>>Active (Receives Leads)</option>
                    <option value="inactive" <?= ($user['availability_status']??'') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                    <option value="absent" <?= ($user['availability_status']??'') === 'absent' ? 'selected' : '' ?>>Absent Today</option>
                </select>
            </div>
            <div class="mb-3 form-check"><input type="checkbox" class="form-check-input" name="is_active" id="isActive" <?= $user['is_active']?'checked':'' ?>><label class="form-check-label" for="isActive">Active</label></div>
            <div class="d-flex gap-2"><button type="submit" class="btn btn-primary"><i class="bi bi-check-circle me-1"></i>Update</button><a href="<?= BASE_URL ?>modules/users/" class="btn btn-outline-secondary">Cancel</a></div>
        </form>
    </div>
</div>
</div></div>
<?php include '../../includes/footer.php'; ?>


