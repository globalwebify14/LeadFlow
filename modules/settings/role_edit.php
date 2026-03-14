<?php
$pageTitle = isset($_GET['id']) ? 'Edit Role' : 'Create Role';
require_once '../../config/auth.php';
requireLogin();
requireRole(['super_admin', 'org_owner', 'org_admin']);
require_once '../../config/db.php';
require_once '../../models/Role.php';

$roleModel = new Role($pdo);
$orgId = getOrgId();
$roleId = $_GET['id'] ?? null;

$role = null;
$permissions = [];
$error = '';

if ($roleId) {
    $role = $roleModel->getRoleById($roleId, $orgId);
    if (!$role) {
        redirect(BASE_URL . 'modules/settings/roles.php', 'Role not found.', 'danger');
    }
    $permissions = $roleModel->getRolePermissions($roleId);
}

// Available Modules
$modules = [
    'dashboard' => 'Dashboard',
    'leads' => 'Leads',
    'pipeline' => 'Pipeline',
    'deals' => 'Deals',
    'tasks' => 'Tasks',
    'reports' => 'Reports',
    'users' => 'User Management',
    'org_settings' => 'Org Settings',
    'automation' => 'Automation'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $permsInput = $_POST['permissions'] ?? [];
    
    if (!$name) {
        $error = "Role name is required.";
    } else {
        $formattedPerms = [];
        foreach ($modules as $key => $label) {
            $formattedPerms[$key] = [
                'can_view' => isset($permsInput[$key]['can_view']) ? 1 : 0,
                'can_create' => isset($permsInput[$key]['can_create']) ? 1 : 0,
                'can_edit' => isset($permsInput[$key]['can_edit']) ? 1 : 0,
                'can_delete' => isset($permsInput[$key]['can_delete']) ? 1 : 0,
            ];
            // If they can create/edit/delete, they must be able to view
            if ($formattedPerms[$key]['can_create'] || $formattedPerms[$key]['can_edit'] || $formattedPerms[$key]['can_delete']) {
                $formattedPerms[$key]['can_view'] = 1;
            }
        }

        if ($roleId) {
            if ($roleModel->updateRole($roleId, $orgId, $name, $formattedPerms)) {
                redirect(BASE_URL . 'modules/settings/roles.php', 'Role updated successfully.', 'success');
            } else {
                $error = "Failed to update role.";
            }
        } else {
            if ($roleModel->createRole($orgId, $name, $formattedPerms, getUserId())) {
                redirect(BASE_URL . 'modules/settings/roles.php', 'Role created successfully.', 'success');
            } else {
                $error = "Failed to create role.";
            }
        }
    }
}

include '../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-shield-check me-2 text-primary"></i><?= $role ? 'Edit Role' : 'Create Role' ?></h4>
        <p class="text-muted small mb-0">Define which modules and actions are permitted for this custom role.</p>
    </div>
    <a href="<?= BASE_URL ?>modules/settings/roles.php" class="btn btn-outline-secondary fw-semibold">
        <i class="bi bi-arrow-left me-1"></i> Back to Roles
    </a>
</div>

<?php if ($error): ?>
<div class="alert alert-danger border-0 rounded-3 shadow-sm mb-4"><i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i><?= e($error) ?></div>
<?php endif; ?>

<form method="POST">
    <div class="row g-4">
        <!-- Role Details -->
        <div class="col-lg-4">
            <div class="card shadow-sm border-0 rounded-4 mb-4 bg-white position-sticky" style="top: 80px;">
                <div class="card-header bg-white border-0 pt-4 pb-2 px-4">
                    <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-info-circle me-2 text-primary"></i>Role Details</h6>
                </div>
                <div class="card-body px-4 pb-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold small text-muted">Role Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control fw-bold" placeholder="e.g. Sales Manager" value="<?= e($role['name'] ?? '') ?>" required <?= ($role && $role['is_system']) ? 'readonly' : '' ?>>
                    </div>
                    <?php if ($role && $role['is_system']): ?>
                        <div class="alert alert-info border-0 bg-info bg-opacity-10 small mb-0">
                            <strong>System Role:</strong> You can modify permissions, but the role name itself cannot be changed.
                        </div>
                    <?php endif; ?>
                    
                    <div class="mt-4 pt-4 border-top">
                        <button type="submit" class="btn btn-primary w-100 fw-semibold rounded-3 py-2 shadow-sm"><i class="bi bi-check-circle me-1"></i> Save Role</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Module Permissions -->
        <div class="col-lg-8">
            <div class="card shadow-sm border-0 rounded-4 bg-white overflow-hidden">
                <div class="card-header bg-white border-0 pt-4 pb-3 px-4 d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-ui-checks-grid me-2 text-warning"></i>Module Access Matrix</h6>
                    <?php if (!$role || !$role['is_system']): ?>
                        <button type="button" class="btn btn-sm btn-outline-primary shadow-sm" onclick="document.querySelectorAll('input[type=checkbox]').forEach(cb => cb.checked = true)">Check All</button>
                    <?php endif; ?>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light text-muted small text-uppercase fw-semibold">
                                <tr>
                                    <th class="ps-4">Module Name</th>
                                    <th class="text-center">View</th>
                                    <th class="text-center">Create</th>
                                    <th class="text-center">Edit</th>
                                    <th class="text-center">Delete</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($modules as $key => $label): 
                                    $p = $permissions[$key] ?? ['can_view'=>0, 'can_create'=>0, 'can_edit'=>0, 'can_delete'=>0];
                                ?>
                                <tr>
                                    <td class="ps-4 fw-semibold text-dark"><?= $label ?></td>
                                    <td class="text-center">
                                        <div class="form-check form-switch d-flex justify-content-center">
                                            <input class="form-check-input permission-cb" type="checkbox" name="permissions[<?= $key ?>][can_view]" value="1" <?= $p['can_view'] ? 'checked' : '' ?>>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <div class="form-check form-switch d-flex justify-content-center">
                                            <input class="form-check-input permission-cb" type="checkbox" name="permissions[<?= $key ?>][can_create]" value="1" <?= $p['can_create'] ? 'checked' : '' ?>>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <div class="form-check form-switch d-flex justify-content-center">
                                            <input class="form-check-input permission-cb" type="checkbox" name="permissions[<?= $key ?>][can_edit]" value="1" <?= $p['can_edit'] ? 'checked' : '' ?>>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <div class="form-check form-switch d-flex justify-content-center">
                                            <input class="form-check-input permission-cb" type="checkbox" name="permissions[<?= $key ?>][can_delete]" value="1" <?= $p['can_delete'] ? 'checked' : '' ?>>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <p class="text-muted small mt-3 ms-2"><i class="bi bi-info-circle me-1"></i> Checking Create, Edit, or Delete will automatically grant View access upon saving.</p>
        </div>
    </div>
</form>

<?php include '../../includes/footer.php'; ?>
