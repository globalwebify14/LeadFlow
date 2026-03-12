<?php
$pageTitle = 'Organization Details';
require_once '../../config/auth.php';
requireLogin();
requireRole(['super_admin']);
require_once '../../config/db.php';
require_once '../../models/Organization.php';

$orgModel = new Organization($pdo);
$id = (int)($_GET['id'] ?? 0);
$org = $orgModel->getById($id);
if (!$org) { header('Location: ' . BASE_URL . 'modules/organizations/'); exit; }

// Get recent leads for this org
$recentLeads = $pdo->prepare("SELECT l.*, u.name as agent FROM leads l LEFT JOIN users u ON l.assigned_to=u.id WHERE l.organization_id=:id ORDER BY l.created_at DESC LIMIT 8");
$recentLeads->execute(['id' => $id]);
$recentLeads = $recentLeads->fetchAll();

// Get users in org
$users = $pdo->prepare("SELECT * FROM users WHERE organization_id = :id ORDER BY created_at DESC");
$users->execute(['id' => $id]);
$users = $users->fetchAll();

include '../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-start mb-4">
    <div>
        <a href="<?= BASE_URL ?>modules/organizations/" class="text-muted text-decoration-none small"><i class="bi bi-arrow-left me-1"></i>Back to Organizations</a>
        <h4 class="fw-bold mt-2 mb-1"><?= e($org['name']) ?></h4>
        <span class="badge bg-<?= $org['status'] === 'active' ? 'success' : 'danger' ?> bg-opacity-10 text-<?= $org['status'] === 'active' ? 'success' : 'danger' ?> fw-semibold"><?= ucfirst($org['status']) ?></span>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= BASE_URL ?>modules/organizations/edit.php?id=<?= $org['id'] ?>" class="btn btn-outline-primary btn-sm"><i class="bi bi-pencil me-1"></i>Edit</a>
        <?php if ($org['status'] === 'active'): ?>
            <a href="<?= BASE_URL ?>modules/organizations/?action=suspend&id=<?= $org['id'] ?>" class="btn btn-warning btn-sm" onclick="return confirm('Suspend?')"><i class="bi bi-pause me-1"></i>Suspend</a>
        <?php else: ?>
            <a href="<?= BASE_URL ?>modules/organizations/?action=activate&id=<?= $org['id'] ?>" class="btn btn-success btn-sm"><i class="bi bi-play me-1"></i>Activate</a>
        <?php endif; ?>
    </div>
</div>

<!-- Stat Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-card-icon" style="background:linear-gradient(135deg,#6366f1,#4f46e5);"><i class="bi bi-people-fill"></i></div>
            <div class="stat-card-info">
                <span class="stat-card-label">Total Users</span>
                <h3 class="stat-card-number"><?= $org['total_users'] ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-card-icon" style="background:linear-gradient(135deg,#10b981,#059669);"><i class="bi bi-person-lines-fill"></i></div>
            <div class="stat-card-info">
                <span class="stat-card-label">Total Leads</span>
                <h3 class="stat-card-number"><?= $org['total_leads'] ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-card-icon" style="background:linear-gradient(135deg,#f59e0b,#d97706);"><i class="bi bi-trophy"></i></div>
            <div class="stat-card-info">
                <span class="stat-card-label">Total Deals</span>
                <h3 class="stat-card-number"><?= $org['total_deals'] ?></h3>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Org Details -->
    <div class="col-lg-4">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white border-0 pt-4 pb-0"><h6 class="fw-bold mb-0"><i class="bi bi-building me-2 text-primary"></i>Organization Info</h6></div>
            <div class="card-body">
                <table class="table table-borderless table-sm small mb-0">
                    <tr><td class="text-muted">Email</td><td class="fw-semibold"><?= e($org['email'] ?? '—') ?></td></tr>
                    <tr><td class="text-muted">Phone</td><td class="fw-semibold"><?= e($org['phone'] ?? '—') ?></td></tr>
                    <tr><td class="text-muted">Owner</td><td class="fw-semibold"><?= e($org['owner_name'] ?? $org['owner_name_user'] ?? '—') ?></td></tr>
                    <tr><td class="text-muted">Plan</td><td><span class="badge bg-primary bg-opacity-10 text-primary"><?= e($org['plan_name'] ?? 'None') ?></span></td></tr>
                    <tr><td class="text-muted">Created</td><td class="fw-semibold"><?= formatDate($org['created_at']) ?></td></tr>
                    <tr><td class="text-muted">Address</td><td class="fw-semibold"><?= e($org['address'] ?? '—') ?></td></tr>
                </table>
            </div>
        </div>

        <!-- Users List -->
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white border-0 pt-4 pb-0 d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0"><i class="bi bi-people me-2 text-success"></i>Users</h6>
                <span class="badge bg-success"><?= count($users) ?></span>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    <?php foreach ($users as $u): ?>
                    <div class="list-group-item border-0 py-2">
                        <div class="d-flex align-items-center">
                            <div class="rounded-circle d-flex align-items-center justify-content-center me-2 text-white fw-bold flex-shrink-0" style="width:30px;height:30px;background:linear-gradient(135deg,#6366f1,#4f46e5);font-size:11px;"><?= getInitials($u['name']) ?></div>
                            <div>
                                <div class="small fw-semibold"><?= e($u['name']) ?></div>
                                <div class="text-muted" style="font-size:11px;"><?= e($u['role']) ?> · <?= e($u['email']) ?></div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php if (empty($users)): ?><div class="list-group-item border-0 text-muted small text-center py-3">No users</div><?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Leads -->
    <div class="col-lg-8">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white border-0 pt-4 pb-0">
                <h6 class="fw-bold mb-0"><i class="bi bi-person-lines-fill me-2 text-primary"></i>Recent Leads</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light"><tr><th>Lead</th><th>Status</th><th>Agent</th><th>Date</th></tr></thead>
                        <tbody>
                            <?php foreach ($recentLeads as $l): ?>
                            <tr>
                                <td><div class="fw-semibold small"><?= e($l['name']) ?></div><div class="text-muted" style="font-size:11px;"><?= e($l['phone']) ?></div></td>
                                <td><span class="badge <?= getStatusBadgeClass($l['status']) ?>"><?= e($l['status']) ?></span></td>
                                <td class="small text-muted"><?= e($l['agent'] ?? 'Unassigned') ?></td>
                                <td class="small text-muted"><?= formatDate($l['created_at']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($recentLeads)): ?><tr><td colspan="4" class="text-center text-muted py-4">No leads yet</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
