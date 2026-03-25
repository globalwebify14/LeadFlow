<?php
$pageTitle = 'Team Management';
require_once '../../config/auth.php';
requireLogin();
requireRole(['super_admin', 'org_owner', 'org_admin']);
require_once '../../config/db.php';

// MODULE ACCESS CHECK
if (!hasModuleAccess('users')) {
    die(header("HTTP/1.0 403 Forbidden") . 'Access Denied: Your organization does not have access to the Team module.');
}

$isSuperAdmin = ($_SESSION['user_role'] ?? '') === 'super_admin';
$orgId = getOrgId(); // null for super_admin

// Build query based on role
$search = trim($_GET['search'] ?? '');
$orgFilter = (int)($_GET['org'] ?? 0);
$roleFilter = $_GET['role'] ?? '';

if ($isSuperAdmin) {
    // Super Admin: see ALL users with their organization
    $sql = "SELECT u.*, o.name as org_name 
            FROM users u
            LEFT JOIN organizations o ON u.organization_id = o.id
            WHERE 1=1";
    $params = [];
    if ($search) {
        $sql .= " AND (u.name LIKE :s OR u.email LIKE :s2)";
        $params['s'] = "%$search%"; $params['s2'] = "%$search%";
    }
    if ($orgFilter) {
        $sql .= " AND u.organization_id = :org";
        $params['org'] = $orgFilter;
    }
    if ($roleFilter) {
        $sql .= " AND u.role = :role";
        $params['role'] = $roleFilter;
    }
    $sql .= " ORDER BY o.name, u.name";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $users = $stmt->fetchAll();
    $orgs = $pdo->query("SELECT id, name FROM organizations ORDER BY name")->fetchAll();
} else {
    // Org-level admin: only their org's users
    require_once '../../models/User.php';
    $userModel = new User($pdo);
    $users = $userModel->getAllUsers($orgId, $search, $roleFilter);
    $orgs = [];
}

include '../../includes/header.php';
?>

<style>
@media (max-width: 768px) {
    .team-header {
        flex-direction: column !important;
        align-items: flex-start !important;
        gap: 10px;
    }
    .team-header h4 { font-size: 1.1rem; }
    .team-header a.btn { width: 100%; text-align: center; }

    /* Table to card */
    .team-card-table { display: block; width: 100%; }
    .team-card-table thead { display: none; }
    .team-card-table tbody, .team-card-table tr, .team-card-table td { display: block; }
    .team-card-table tr {
        background: #fff;
        border: 1px solid rgba(0,0,0,0.06);
        border-radius: 12px;
        margin-bottom: 10px;
        padding: 12px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.03);
    }
    .team-card-table td {
        padding: 5px 0 !important;
        border: none !important;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .team-card-table td::before {
        content: attr(data-label);
        font-weight: 700;
        font-size: 10px;
        text-transform: uppercase;
        color: #94a3b8;
        white-space: nowrap;
        margin-right: 12px;
        flex-shrink: 0;
    }

    /* User cell: full width, no label */
    .team-card-table td.cell-user::before { display: none; }
    .team-card-table td.cell-user {
        display: flex !important;
        padding-bottom: 8px !important;
        border-bottom: 1px solid rgba(0,0,0,0.05) !important;
    }

    /* Email */
    .team-card-table td.cell-email { font-size: 12px; }

    /* Hide verbose columns */
    .team-card-table td.cell-login,
    .team-card-table td.cell-account { display: none !important; }

    /* Availability dropdown: take remaining space */
    .team-card-table td.cell-avail select { width: auto !important; flex: 1; max-width: 200px; }

    /* Actions: align right, no label */
    .team-card-table td.cell-actions::before { display: none; }
    .team-card-table td.cell-actions {
        justify-content: flex-end !important;
        padding-top: 8px !important;
        border-top: 1px solid rgba(0,0,0,0.05) !important;
    }

    .table-responsive { overflow-x: hidden !important; }
}
</style>

<div class="d-flex justify-content-between align-items-center mb-4 team-header">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-people me-2 text-primary"></i>Team Management</h4>
        <p class="text-muted small mb-0"><?= count($users) ?> member<?= count($users) !== 1 ? 's' : '' ?> found</p>
    </div>
    <a href="<?= BASE_URL ?>modules/users/add.php" class="btn btn-primary btn-sm fw-semibold">
        <i class="bi bi-person-plus me-1"></i> Add Member
    </a>
</div>

<!-- Filters -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-body py-3">
        <form method="GET" class="row g-2 align-items-end">
            <div class="<?= $isSuperAdmin ? 'col-md-4' : 'col-md-6' ?>">
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" name="search" value="<?= e($search) ?>" class="form-control border-start-0 bg-light" placeholder="Search by name or email...">
                </div>
            </div>
            <?php if ($isSuperAdmin): ?>
            <div class="col-md-3">
                <select name="org" class="form-select bg-light">
                    <option value="">All Organizations</option>
                    <option value="-1" <?= $orgFilter === -1 ? 'selected':'' ?>>— No Organization —</option>
                    <?php foreach ($orgs as $o): ?>
                    <option value="<?= $o['id'] ?>" <?= $orgFilter == $o['id'] ? 'selected':'' ?>><?= e($o['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            <div class="<?= $isSuperAdmin ? 'col-md-2' : 'col-md-3' ?>">
                <select name="role" class="form-select bg-light">
                    <option value="">All Roles</option>
                    <?php if ($isSuperAdmin): ?><option value="super_admin" <?= $roleFilter==='super_admin'?'selected':'' ?>>Super Admin</option><?php endif; ?>
                    <option value="org_owner" <?= $roleFilter==='org_owner'?'selected':'' ?>>Org Owner</option>
                    <option value="org_admin" <?= $roleFilter==='org_admin'?'selected':'' ?>>Org Admin</option>
                    <option value="team_lead" <?= $roleFilter==='team_lead'?'selected':'' ?>>Team Lead</option>
                    <option value="agent" <?= $roleFilter==='agent'?'selected':'' ?>>Agent</option>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-grow-1"><i class="bi bi-funnel me-1"></i> Filter</button>
                <a href="<?= BASE_URL ?>modules/users/" class="btn btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 team-card-table">
                <thead class="table-light">
                    <tr>
                        <th>User</th>
                        <th>Email</th>
                        <?php if ($isSuperAdmin): ?><th>Organization</th><?php endif; ?>
                        <th>Role</th>
                        <th>Account</th>
                        <th>Availability</th>
                        <th>Last Login</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($users)): ?>
                        <?php foreach ($users as $u): ?>
                        <tr>
                            <td class="cell-user">
                                <div class="d-flex align-items-center">
                                    <div class="rounded-circle d-flex align-items-center justify-content-center me-2 text-white flex-shrink-0" style="width:36px;height:36px;font-size:13px;background:linear-gradient(135deg,#6366f1,#4f46e5);"><?= getInitials($u['name']) ?></div>
                                    <div><div class="fw-semibold small"><?= e($u['name']) ?></div></div>
                                </div>
                            </td>
                            <td class="text-muted small cell-email" data-label="Email"><?= e($u['email']) ?></td>
                            <?php if ($isSuperAdmin): ?>
                            <td data-label="Organization">
                                <?php if ($u['org_name']): ?>
                                    <a href="<?= BASE_URL ?>modules/organizations/view.php?id=<?= $u['organization_id'] ?>" class="badge bg-primary bg-opacity-10 text-primary text-decoration-none">
                                        <?= e($u['org_name']) ?>
                                    </a>
                                <?php else: ?>
                                    <span class="badge bg-danger bg-opacity-10 text-danger">Platform Admin</span>
                                <?php endif; ?>
                            </td>
                            <?php endif; ?>
                            <td data-label="Role">
                                <?php
                                $roleBadge = ['org_owner'=>'primary','org_admin'=>'info','team_lead'=>'warning','agent'=>'secondary','super_admin'=>'danger'];
                                $roleLabel = ['org_owner'=>'Org Owner','org_admin'=>'Org Admin','team_lead'=>'Team Lead','agent'=>'Agent','super_admin'=>'Super Admin'];
                                $r = $u['role'];
                                ?>
                                <span class="badge bg-<?= $roleBadge[$r] ?? 'secondary' ?> bg-opacity-10 text-<?= $roleBadge[$r] ?? 'secondary' ?>"><?= $roleLabel[$r] ?? $r ?></span>
                            </td>
                            <td class="cell-account" data-label="Account"><span class="badge bg-<?= $u['is_active'] ? 'success' : 'danger' ?> bg-opacity-10 text-<?= $u['is_active'] ? 'success' : 'danger' ?>"><?= $u['is_active'] ? 'Active' : 'Inactive' ?></span></td>
                            <td class="cell-avail" data-label="Availability">
                                <select class="form-select form-select-sm border border-secondary border-opacity-25 fw-semibold" style="width: 165px; font-size: 12px; cursor: pointer; background-color: var(--bs-light);" onchange="updateAvailability(<?= $u['id'] ?>, this.value, this)">
                                    <option value="active" <?= ($u['availability_status'] ?? 'active') === 'active' ? 'selected' : '' ?>>🟢 Receiving Leads</option>
                                    <option value="absent" <?= ($u['availability_status'] ?? 'active') === 'absent' ? 'selected' : '' ?>>🟡 Absent Today</option>
                                    <option value="inactive" <?= ($u['availability_status'] ?? 'active') === 'inactive' ? 'selected' : '' ?>>⚪ Inactive</option>
                                </select>
                            </td>
                            <td class="small text-muted cell-login" data-label="Last Login"><?= $u['last_login'] ? timeAgo($u['last_login']) : 'Never' ?></td>
                            <td class="cell-actions">
                                <div class="btn-group btn-group-sm">
                                    <a href="<?= BASE_URL ?>modules/users/edit.php?id=<?= $u['id'] ?>" class="btn btn-outline-primary" title="Edit"><i class="bi bi-pencil"></i></a>
                                    <?php if ($u['id'] != getUserId()): ?>
                                    <a href="<?= BASE_URL ?>modules/users/delete.php?id=<?= $u['id'] ?>" class="btn btn-outline-danger" title="Delete" onclick="return confirm('Delete user <?= e(addslashes($u['name'])) ?>?')"><i class="bi bi-trash"></i></a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="<?= $isSuperAdmin ? 7 : 6 ?>" class="text-center py-5 text-muted">
                            <i class="bi bi-people fs-1 d-block mb-2"></i>No team members found.
                        </td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>

<script>
function updateAvailability(userId, status, selectElement) {
    const originalValue = selectElement.getAttribute('data-original-value') || selectElement.querySelector('option[selected]')?.value || 'active';
    selectElement.disabled = true;

    fetch('<?= BASE_URL ?>modules/users/ajax_update_availability.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'user_id=' + userId + '&status=' + status
    })
    .then(response => response.json())
    .then(data => {
        if (!data.success) {
            alert(data.message || 'Failed to update availability. Please try again.');
            selectElement.value = originalValue;
        } else {
            selectElement.setAttribute('data-original-value', status);
            // Flash green border to indicate success
            selectElement.classList.add('border-success', 'text-success');
            setTimeout(() => selectElement.classList.remove('border-success', 'text-success'), 1500);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An network error occurred while updating availability.');
        selectElement.value = originalValue;
    })
    .finally(() => {
        selectElement.disabled = false;
    });
}
</script>
