<?php
$pageTitle = 'Organizations';
require_once '../../config/auth.php';
requireLogin();
requireRole(['super_admin']);
require_once '../../config/db.php';
require_once '../../models/Organization.php';
require_once '../../models/Plan.php';

$orgModel = new Organization($pdo);
$search = trim($_GET['search'] ?? '');
$statusFilter = $_GET['status'] ?? '';

// Handle status change actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $aid = (int)$_GET['id'];
    if ($_GET['action'] === 'suspend') $orgModel->updateStatus($aid, 'suspended');
    if ($_GET['action'] === 'activate') $orgModel->updateStatus($aid, 'active');
    if ($_GET['action'] === 'delete') $orgModel->delete($aid);
    header('Location: ' . BASE_URL . 'modules/organizations/'); exit;
}

$organizations = $orgModel->getAll($search, $statusFilter);
$stats = $orgModel->getPlatformStats();

include '../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-building me-2 text-primary"></i>Organizations</h4>
        <p class="text-muted small mb-0">Manage all organizations on the platform</p>
    </div>
    <a href="<?= BASE_URL ?>modules/organizations/create.php" class="btn btn-primary fw-semibold">
        <i class="bi bi-building-add me-1"></i> Create Organization
    </a>
</div>

<!-- Stat Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-card-icon" style="background:linear-gradient(135deg,#6366f1,#4f46e5);"><i class="bi bi-building"></i></div>
            <div class="stat-card-info">
                <span class="stat-card-label">Total</span>
                <h3 class="stat-card-number"><?= $stats['total'] ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-card-icon" style="background:linear-gradient(135deg,#10b981,#059669);"><i class="bi bi-check-circle"></i></div>
            <div class="stat-card-info">
                <span class="stat-card-label">Active</span>
                <h3 class="stat-card-number"><?= $stats['active'] ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-card-icon" style="background:linear-gradient(135deg,#ef4444,#dc2626);"><i class="bi bi-slash-circle"></i></div>
            <div class="stat-card-info">
                <span class="stat-card-label">Suspended</span>
                <h3 class="stat-card-number"><?= $stats['suspended'] ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-card-icon" style="background:linear-gradient(135deg,#f59e0b,#d97706);"><i class="bi bi-pause-circle"></i></div>
            <div class="stat-card-info">
                <span class="stat-card-label">Inactive</span>
                <h3 class="stat-card-number"><?= $stats['inactive'] ?></h3>
            </div>
        </div>
    </div>
</div>

<!-- Search & Filter -->
<div class="card shadow-sm border-0">
    <div class="card-header bg-white border-0 pt-4 pb-3">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-6">
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" name="search" value="<?= e($search) ?>" class="form-control border-start-0 bg-light" placeholder="Search organizations...">
                </div>
            </div>
            <div class="col-md-3">
                <select name="status" class="form-select bg-light">
                    <option value="">All Status</option>
                    <option value="active" <?= $statusFilter === 'active' ? 'selected' : '' ?>>Active</option>
                    <option value="inactive" <?= $statusFilter === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                    <option value="suspended" <?= $statusFilter === 'suspended' ? 'selected' : '' ?>>Suspended</option>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-grow-1">Filter</button>
                <a href="<?= BASE_URL ?>modules/organizations/" class="btn btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Organization</th>
                        <th>Owner</th>
                        <th>Plan</th>
                        <th>Users</th>
                        <th>Leads</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($organizations)): ?>
                        <?php foreach ($organizations as $i => $org): ?>
                        <tr>
                            <td class="text-muted small"><?= $i + 1 ?></td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="rounded-circle d-flex align-items-center justify-content-center text-white fw-bold me-2 flex-shrink-0" style="width:36px;height:36px;background:linear-gradient(135deg,#6366f1,#4f46e5);font-size:13px;">
                                        <?= strtoupper(substr($org['name'], 0, 1)) ?>
                                    </div>
                                    <div>
                                        <div class="fw-semibold small"><?= e($org['name']) ?></div>
                                        <div class="text-muted" style="font-size:11px;"><?= e($org['email'] ?? '') ?></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="fw-semibold small"><?= e($org['owner_name'] ?? $org['owner_name_user'] ?? '—') ?></div>
                                <?php if (!empty($org['owner_email_user'])): ?>
                                    <div class="text-muted" style="font-size:11px;"><i class="bi bi-person-badge me-1"></i><?= e($org['owner_email_user']) ?></div>
                                <?php else: ?>
                                    <div class="text-muted" style="font-size:11px;">No linked owner</div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($org['plan_name']): ?>
                                    <span class="badge bg-primary bg-opacity-10 text-primary"><?= e($org['plan_name']) ?></span>
                                <?php else: ?>
                                    <span class="text-muted small">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="small"><?= $org['total_users'] ?></td>
                            <td class="small"><?= $org['total_leads'] ?></td>
                            <td>
                                <?php
                                $sc = ['active' => 'success', 'inactive' => 'secondary', 'suspended' => 'danger'];
                                $s = $org['status'];
                                ?>
                                <span class="badge bg-<?= $sc[$s] ?? 'secondary' ?> bg-opacity-10 text-<?= $sc[$s] ?? 'secondary' ?> fw-semibold"><?= ucfirst($s) ?></span>
                            </td>
                            <td class="text-muted small"><?= formatDate($org['created_at']) ?></td>
                            <td>
                                <div class="d-flex gap-1">
                                    <a href="<?= BASE_URL ?>modules/organizations/view.php?id=<?= $org['id'] ?>" class="btn btn-sm btn-light" title="View"><i class="bi bi-eye"></i></a>
                                    <a href="<?= BASE_URL ?>modules/organizations/edit.php?id=<?= $org['id'] ?>" class="btn btn-sm btn-light" title="Edit"><i class="bi bi-pencil"></i></a>
                                    <?php if ($s === 'active'): ?>
                                        <a href="?action=suspend&id=<?= $org['id'] ?>" class="btn btn-sm btn-warning" title="Suspend" onclick="return confirm('Suspend this organization?')"><i class="bi bi-pause"></i></a>
                                    <?php else: ?>
                                        <a href="?action=activate&id=<?= $org['id'] ?>" class="btn btn-sm btn-success" title="Activate"><i class="bi bi-play"></i></a>
                                    <?php endif; ?>
                                    <a href="?action=delete&id=<?= $org['id'] ?>" class="btn btn-sm btn-danger" title="Delete" onclick="return confirm('Permanently delete this organization and ALL its data?')"><i class="bi bi-trash"></i></a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="9" class="text-center py-5 text-muted">
                            <i class="bi bi-building fs-1 d-block mb-2"></i>
                            No organizations found. <a href="<?= BASE_URL ?>modules/organizations/create.php" class="fw-semibold text-primary">Create the first one →</a>
                        </td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
