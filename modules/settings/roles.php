<?php
$pageTitle = 'Roles & Permissions';
require_once '../../config/auth.php';
requireLogin();
requireRole(['super_admin', 'org_owner', 'org_admin']);
require_once '../../config/db.php';
require_once '../../models/Role.php';

$roleModel = new Role($pdo);
$orgId = getOrgId();
$roles = $roleModel->getAllRoles($orgId);

include '../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-shield-lock me-2 text-primary"></i>Roles & Permissions</h4>
        <p class="text-muted small mb-0">Manage custom roles and configure module access levels.</p>
    </div>
    <a href="<?= BASE_URL ?>modules/settings/role_edit.php" class="btn btn-primary fw-semibold shadow-sm">
        <i class="bi bi-plus-lg me-1"></i> Create Role
    </a>
</div>

<?php if (isset($_GET['msg'])): ?>
<div class="alert alert-success border-0 rounded-3 shadow-sm mb-4">
    <i class="bi bi-check-circle-fill me-2 fs-5"></i><?= e($_GET['msg']) ?>
</div>
<?php endif; ?>

<div class="card shadow-sm border-0 rounded-4 overflow-hidden bg-white">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-muted small text-uppercase fw-semibold">
                    <tr>
                        <th class="ps-4">Role Name</th>
                        <th>Type</th>
                        <th>Created</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($roles)): ?>
                        <tr><td colspan="4" class="text-center text-muted py-5"><i class="bi bi-shield-slash fs-1 d-block mb-2 text-black-50"></i>No roles defined yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($roles as $r): ?>
                        <tr>
                            <td class="ps-4">
                                <span class="fw-bold text-dark fs-6"><?= e($r['name']) ?></span>
                            </td>
                            <td>
                                <?php if ($r['is_system']): ?>
                                    <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 rounded-pill px-2">System Role</span>
                                <?php else: ?>
                                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill px-2">Custom Role</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-muted small">
                                <?= $r['created_at'] ? date('M d, Y', strtotime($r['created_at'])) : '—' ?>
                            </td>
                            <td class="text-end pe-4">
                                <div class="btn-group btn-group-sm">
                                    <a href="<?= BASE_URL ?>modules/settings/role_edit.php?id=<?= $r['id'] ?>" class="btn btn-outline-primary shadow-sm" title="Edit Permissions">
                                        <i class="bi <?= $r['is_system'] ? 'bi-eye' : 'bi-pencil' ?>"></i>
                                    </a>
                                    <?php if (!$r['is_system']): ?>
                                    <form method="POST" action="<?= BASE_URL ?>modules/settings/role_delete.php" style="display:inline;" onsubmit="return confirm('WARNING: Deleting this role will remove access for any users assigned to it. Proceed?');">
                                        <input type="hidden" name="id" value="<?= $r['id'] ?>">
                                        <button type="submit" class="btn btn-outline-danger shadow-sm" title="Delete Role"><i class="bi bi-trash"></i></button>
                                    </form>
                                    <?php else: ?>
                                        <button class="btn btn-outline-secondary shadow-sm" disabled title="System roles cannot be deleted"><i class="bi bi-trash"></i></button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
