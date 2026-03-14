<?php
$pageTitle = 'Activity Logs';
require_once '../../config/auth.php';
requireLogin();
require_once '../../config/db.php';
if (getUserRole() === 'super_admin') {
    redirect(BASE_URL . 'modules/dashboard/', 'No permission.', 'danger');
}
require_once '../../models/ActivityLog.php';
require_once '../../models/Dashboard.php';

$orgId = getOrgId();
$dashboard = new Dashboard($pdo);

// Filters
$userId = (getUserRole() === 'agent') ? getUserId() : null;
$role = getUserRole();

$activities = $dashboard->getRecentActivities($orgId, 50, $userId, $role);

include '../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0">Activity Logs</h4>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4">Timestamp</th>
                        <th>User</th>
                        <th>Lead</th>
                        <th>Activity</th>
                        <th>Details</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($activities as $a): ?>
                    <tr>
                        <td class="ps-4 small text-muted"><?= formatDate($a['created_at']) ?> <br> <?= date('H:i', strtotime($a['created_at'])) ?></td>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="rounded-circle bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center me-2" style="width:30px;height:30px;font-size:11px;">
                                    <?= getInitials($a['user_name']) ?>
                                </div>
                                <span class="small fw-medium"><?= e($a['user_name']) ?></span>
                            </div>
                        </td>
                        <td>
                            <a href="<?= BASE_URL ?>modules/leads/view.php?id=<?= $a['lead_id'] ?>" class="text-decoration-none small fw-semibold"><?= e($a['lead_name']) ?></a>
                        </td>
                        <td>
                            <?php 
                            $typeClass = 'bg-secondary';
                            switch($a['activity_type']) {
                                case 'status_change': $typeClass = 'bg-info'; break;
                                case 'note': $typeClass = 'bg-warning'; break;
                                case 'followup': $typeClass = 'bg-success'; break;
                                case 'task': $typeClass = 'bg-primary'; break;
                            }
                            ?>
                            <span class="badge rounded-pill <?= $typeClass ?> bg-opacity-10 text-dark" style="font-size:10px;"><?= ucfirst($a['activity_type']) ?></span>
                        </td>
                        <td>
                            <div class="small text-muted" title="<?= e($a['description']) ?>"><?= e(truncate($a['description'], 100)) ?></div>
                            <?php if ($a['old_value'] || $a['new_value']): ?>
                            <div class="mt-1" style="font-size:10px;">
                                <span class="text-muted text-decoration-line-through"><?= e($a['old_value']) ?></span> &rarr; <span class="text-dark fw-bold"><?= e($a['new_value']) ?></span>
                            </div>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($activities)): ?>
                    <tr>
                        <td colspan="5" class="text-center py-5 text-muted">
                            <i class="bi bi-journal-text fs-1 d-block mb-3 opacity-25"></i>
                            No activities found.
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
