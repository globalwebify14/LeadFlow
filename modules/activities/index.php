<?php
$pageTitle = 'Activities';
require_once '../../config/auth.php';
requireLogin();
require_once '../../config/db.php';
require_once '../../models/Dashboard.php';

$orgId = getOrgId();
$userId = getUserId();
$userRole = getUserRole();

$dashboard = new Dashboard($pdo);

$limit = 100;
// Pass the role constraint to get recent activities for the active user if they're an agent.
// For higher roles, we optionally don't filter by user, but since the previous request was specific to Agent history, we primarily support their view.
$activities = $dashboard->getRecentActivities($orgId, $limit, ($userRole === 'agent' || $userRole === 'team_lead') ? $userId : null, $userRole);

include '../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-activity text-primary me-2"></i>Recent Activities</h4>
        <p class="text-muted small mb-0">Timeline of all your recent lead interactions and system changes.</p>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4">Activity Description</th>
                        <th>Lead Reference</th>
                        <?php if ($userRole !== 'agent'): ?>
                        <th>Agent</th>
                        <?php endif; ?>
                        <th>Date & Time</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($activities)): ?>
                        <?php foreach($activities as $act): 
                            $icon = 'bi-record-circle';
                            $color = 'text-primary';
                            switch($act['activity_type']) {
                                case 'status_change': $icon = 'bi-arrow-right-circle'; $color = 'text-info'; break;
                                case 'note': $icon = 'bi-journal-text'; $color = 'text-warning'; break;
                                case 'followup': $icon = 'bi-calendar-event'; $color = 'text-success'; break;
                                case 'email': $icon = 'bi-envelope'; $color = 'text-primary'; break;
                                case 'call': $icon = 'bi-telephone'; $color = 'text-secondary'; break;
                                case 'deal': $icon = 'bi-trophy'; $color = 'text-success'; break;
                            }
                        ?>
                        <tr>
                            <td class="ps-4 py-3">
                                <div class="d-flex align-items-center">
                                    <div class="me-3 <?= $color ?> bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                                        <i class="bi <?= $icon ?> fs-5"></i>
                                    </div>
                                    <div>
                                        <div class="fw-semibold text-dark"><?= e($act['description']) ?></div>
                                        <?php if ($act['activity_type'] === 'status_change' && $act['old_value'] && $act['new_value']): ?>
                                            <div class="text-muted small mt-1">
                                                <span class="badge bg-light text-dark border"><?= e($act['old_value']) ?></span> &rarr; <span class="badge bg-light text-dark border"><?= e($act['new_value']) ?></span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <a href="<?= BASE_URL ?>modules/leads/view.php?id=<?= $act['lead_id'] ?>" class="text-decoration-none fw-semibold">
                                    <i class="bi bi-person me-1"></i><?= e($act['lead_name']) ?>
                                </a>
                            </td>
                            <?php if ($userRole !== 'agent'): ?>
                            <td class="text-muted small">
                                <i class="bi bi-person-badge me-1"></i><?= e($act['user_name'] ?? 'System') ?>
                            </td>
                            <?php endif; ?>
                            <td class="text-muted small">
                                <div class="fw-medium text-dark"><i class="bi bi-calendar me-1"></i><?= date('M j, Y', strtotime($act['created_at'])) ?></div>
                                <div><i class="bi bi-clock me-1"></i><?= date('g:i A', strtotime($act['created_at'])) ?> &bull; <?= timeAgo($act['created_at']) ?></div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="<?= ($userRole !== 'agent') ? 4 : 3 ?>" class="text-center py-5">
                                <div class="mb-3"><i class="bi bi-clipboard-x text-muted ps-1" style="font-size: 3rem;"></i></div>
                                <span class="text-muted fw-semibold">No recent activities found in your timeline.</span>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
