<?php
$pageTitle = 'Add User';
require_once '../../config/auth.php';
requireLogin();
requireRole(['super_admin', 'org_owner', 'org_admin']);
require_once '../../config/db.php';
require_once '../../models/User.php';



$orgId = getOrgId();
$userModel = new User($pdo);
$currentUserRole = getUserRole();

// Determine allowed roles for creation
$allowedRoles = ['agent' => 'Sales Agent', 'team_lead' => 'Team Lead'];
if (in_array($currentUserRole, ['super_admin', 'org_owner'])) {
    $allowedRoles['org_admin'] = 'Organization Admin';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'organization_id' => $orgId,
        'name' => trim($_POST['name']),
        'email' => trim($_POST['email']),
        'password' => $_POST['password'],
        'phone' => trim($_POST['phone'] ?? ''),
        'role' => $_POST['role'] ?? 'agent',
    ];
    
    if (!array_key_exists($data['role'], $allowedRoles)) {
        $error = 'Invalid role selected.';
    } elseif ($userModel->emailExists($data['email'])) {
        $error = 'Email already exists.';
    } elseif (strlen($data['password']) < 6) {
        $error = 'Password must be at least 6 characters.';
    } else {
        $userModel->createUser($data);
        redirect(BASE_URL . 'modules/users/', 'User created successfully!', 'success');
    }
}
include '../../includes/header.php';
?>
<div class="row justify-content-center"><div class="col-lg-6">
<div class="card shadow-sm border-0">
    <div class="card-header bg-white border-0 pt-4"><h5 class="fw-bold mb-0"><i class="bi bi-person-plus text-primary me-2"></i>Add User</h5></div>
    <div class="card-body p-4">
        <?php if (!empty($error)): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
        <form method="POST">
            <div class="mb-3"><label class="form-label">Full Name *</label><input type="text" class="form-control" name="name" required></div>
            <div class="mb-3"><label class="form-label">Email *</label><input type="email" class="form-control" name="email" required></div>
            <div class="mb-3"><label class="form-label">Phone</label><input type="text" class="form-control" name="phone"></div>
            <div class="mb-3">
                <label class="form-label">Password *</label>
                <div class="input-group">
                    <input type="password" class="form-control" name="password" id="passwordInput" required minlength="6">
                    <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                        <i class="bi bi-eye"></i>
                    </button>
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Role</label>
                <select class="form-select" name="role">
                    <?php foreach ($allowedRoles as $val => $label): ?>
                        <option value="<?= $val ?>"><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="d-flex gap-2"><button type="submit" class="btn btn-primary"><i class="bi bi-plus-circle me-1"></i>Create User</button><a href="<?= BASE_URL ?>modules/users/" class="btn btn-outline-secondary">Cancel</a></div>
        </form>
    </div>
</div>
</div></div>
<script>
document.getElementById('togglePassword').addEventListener('click', function() {
    const pwd = document.getElementById('passwordInput');
    const icon = this.querySelector('i');
    if (pwd.type === 'password') {
        pwd.type = 'text';
        icon.classList.remove('bi-eye');
        icon.classList.add('bi-eye-slash');
    } else {
        pwd.type = 'password';
        icon.classList.remove('bi-eye-slash');
        icon.classList.add('bi-eye');
    }
});
</script>
<?php include '../../includes/footer.php'; ?>


