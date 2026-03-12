<?php
$pageTitle = 'Activity Logs';
require_once '../../config/auth.php';
requireLogin();
requireRole(['super_admin']);
require_once '../../config/db.php';
require_once '../../models/ActivityLog.php';

$logModel = new ActivityLog($pdo);
$search = trim($_GET['search'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 25; $offset = ($page - 1) * $limit;
$total = $logModel->count($search);
$totalPages = ceil($total / $limit);
$logs = $logModel->getAll($limit, $offset, $search);

// Icon map for action types
$actionIcons = [
    'org_created'  => 'bi-building text-success',
    'org_updated'  => 'bi-pencil text-primary',
    'plan_created' => 'bi-wallet2 text-purple',
    'plan_updated' => 'bi-pencil-square text-warning',
    'plan_deleted' => 'bi-trash text-danger',
    'user_created' => 'bi-person-plus text-success',
    'login'        => 'bi-box-arrow-in-right text-info',
    'logout'       => 'bi-box-arrow-right text-secondary',
    'default'      => 'bi-journal-text text-muted',
];

include '../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-journal-text me-2 text-primary"></i>Activity Logs</h4>
        <p class="text-muted small mb-0"><?= number_format($total) ?> total log entries</p>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-header bg-white border-0 pt-4 pb-3">
        <form method="GET" class="d-flex gap-2">
            <div class="input-group" style="max-width:400px;">
                <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                <input type="text" name="search" value="<?= e($search) ?>" class="form-control border-start-0 bg-light" placeholder="Search action, description, user...">
            </div>
            <button type="submit" class="btn btn-primary">Search</button>
            <?php if ($search): ?><a href="<?= BASE_URL ?>modules/superadmin/activity_logs.php" class="btn btn-outline-secondary">Clear</a><?php endif; ?>
        </form>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr><th>Action</th><th>Description</th><th>User</th><th>Organization</th><th>IP</th><th>When</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                    <?php $icon = $actionIcons[$log['action']] ?? $actionIcons['default']; ?>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi <?= $icon ?> fs-5"></i>
                                <span class="small fw-semibold"><?= e(str_replace('_', ' ', ucfirst($log['action']))) ?></span>
                            </div>
                        </td>
                        <td class="small text-muted" style="max-width:300px;"><?= e(truncate($log['description'] ?? '', 80)) ?></td>
                        <td class="small"><?= e($log['user_name'] ?? 'System') ?></td>
                        <td class="small text-muted"><?= e($log['org_name'] ?? '—') ?></td>
                        <td class="small text-muted"><?= e($log['ip_address'] ?? '—') ?></td>
                        <td class="small text-muted"><?= timeAgo($log['created_at']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($logs)): ?>
                    <tr><td colspan="6" class="text-center py-5 text-muted"><i class="bi bi-journal fs-1 d-block mb-2"></i>No log entries found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php if ($totalPages > 1): ?>
    <div class="card-footer bg-white border-0 d-flex justify-content-between align-items-center py-3">
        <small class="text-muted">Showing <?= min($offset+1,$total) ?>–<?= min($offset+$limit,$total) ?> of <?= $total ?></small>
        <nav><ul class="pagination pagination-sm mb-0">
            <?php for($p=1;$p<=$totalPages;$p++): ?><li class="page-item <?= $p==$page?'active':'' ?>"><a class="page-link" href="?<?= http_build_query(array_merge($_GET,['page'=>$p])) ?>"><?= $p ?></a></li><?php endfor; ?>
        </ul></nav>
    </div>
    <?php endif; ?>
</div>

<?php include '../../includes/footer.php'; ?>
