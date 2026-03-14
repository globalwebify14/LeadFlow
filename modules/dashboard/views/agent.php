<?php
// agent.php view
$stats = $dashboard->getStatistics($orgId, $userId, 'agent');
$todayFollowups = $dashboard->getTodayFollowups($orgId, $userId);
$recentActivities = $dashboard->getRecentActivities($orgId, 15, $userId, 'agent');
?>

<div class="welcome-banner mb-4">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h4 class="fw-bold text-white mb-1">Hello, <?= e(getUserName()) ?></h4>
            <p class="text-white-50 mb-0 small">Sales Agent Dashboard</p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= BASE_URL ?>modules/deals/add.php" class="btn btn-outline-light btn-sm fw-semibold"><i class="bi bi-trophy me-1"></i>New Deal</a>
            <a href="<?= BASE_URL ?>modules/leads/add.php" class="btn btn-light btn-sm fw-semibold"><i class="bi bi-plus-lg me-1"></i>Add Lead</a>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <!-- My Leads -->
    <div class="col-xl-2 col-md-4 col-sm-6">
        <div class="stat-card">
            <div class="stat-card-icon" style="background:linear-gradient(135deg,#6366f1,#4f46e5);"><i class="bi bi-people"></i></div>
            <div class="stat-card-info">
                <span class="stat-card-label">My Leads</span>
                <h3 class="stat-card-number"><?= number_format($stats['total_leads']) ?></h3>
            </div>
        </div>
    </div>
    <!-- Contacted Leads -->
    <div class="col-xl-2 col-md-4 col-sm-6">
        <div class="stat-card">
            <div class="stat-card-icon" style="background:linear-gradient(135deg,#0ea5e9,#0284c7);"><i class="bi bi-chat-dots"></i></div>
            <div class="stat-card-info">
                <span class="stat-card-label">Contacted</span>
                <h3 class="stat-card-number"><?= number_format($stats['contacted_leads']) ?></h3>
            </div>
        </div>
    </div>
    <!-- Deals In Progress -->
    <div class="col-xl-2 col-md-4 col-sm-6">
        <div class="stat-card">
            <div class="stat-card-icon" style="background:linear-gradient(135deg,#8b5cf6,#7c3aed);"><i class="bi bi-graph-up text-white"></i></div>
            <div class="stat-card-info">
                <span class="stat-card-label">Deals In Progress</span>
                <h3 class="stat-card-number"><?= number_format($stats['deals_in_progress'] ?? 0) ?></h3>
            </div>
        </div>
    </div>
    <!-- Deals Won -->
    <div class="col-xl-2 col-md-4 col-sm-6">
        <div class="stat-card">
            <div class="stat-card-icon" style="background:linear-gradient(135deg,#10b981,#059669);"><i class="bi bi-trophy"></i></div>
            <div class="stat-card-info">
                <span class="stat-card-label">Deals Won</span>
                <h3 class="stat-card-number"><?= number_format($stats['won_deals']) ?></h3>
            </div>
        </div>
    </div>
    <!-- Follow-ups Today -->
    <div class="col-xl-2 col-md-4 col-sm-6">
        <div class="stat-card">
            <div class="stat-card-icon" style="background:linear-gradient(135deg,#f59e0b,#d97706);"><i class="bi bi-clock-history"></i></div>
            <div class="stat-card-info">
                <span class="stat-card-label">Follow-ups Today</span>
                <h3 class="stat-card-number"><?= $stats['pending_followups'] ?></h3>
            </div>
        </div>
    </div>
    <!-- Missed Follow-ups -->
    <div class="col-xl-2 col-md-4 col-sm-6">
        <div class="stat-card">
            <div class="stat-card-icon" style="background:linear-gradient(135deg,#ef4444,#dc2626);"><i class="bi bi-exclamation-triangle"></i></div>
            <div class="stat-card-info">
                <span class="stat-card-label">Missed Follow-ups</span>
                <h3 class="stat-card-number"><?= $stats['missed_followups'] ?></h3>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Action Items (Today's Followups) -->
    <div class="col-xl-4 col-lg-5">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white border-0 pt-4 pb-0 d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0"><i class="bi bi-calendar-check me-2 text-warning"></i>My Action Items</h6>
                <a href="<?= BASE_URL ?>modules/followups/" class="btn btn-link btn-sm text-decoration-none p-0">View All</a>
            </div>
            <div class="card-body">
                <?php if (!empty($todayFollowups)): ?>
                    <?php foreach ($todayFollowups as $f): ?>
                    <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-2">
                        <div>
                            <div class="fw-semibold small"><?= e($f['title']) ?></div>
                            <div class="text-muted" style="font-size:12px;">With: <?= e($f['lead_name']) ?></div>
                        </div>
                        <a href="<?= BASE_URL ?>modules/followups/?complete=<?= $f['id'] ?>" class="btn btn-sm btn-outline-success"><i class="bi bi-check2"></i> Done</a>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center py-5">
                        <div class="mb-3"><i class="bi bi-check-circle text-success" style="font-size: 3rem;"></i></div>
                        <span class="text-muted small fw-semibold">All caught up for today!</span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Recent Activity Feed -->
    <div class="col-xl-8 col-lg-7">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white border-0 pt-4 pb-0 d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0"><i class="bi bi-activity me-2 text-primary"></i>My Recent Activity</h6>
                <a href="<?= BASE_URL ?>modules/activities/" class="btn btn-link btn-sm text-decoration-none p-0">View All</a>
            </div>
            <div class="card-body">
                <?php if (!empty($recentActivities)): ?>
                    <div class="activity-timeline mt-2">
                        <?php foreach($recentActivities as $act): 
                            $icon = 'bi-record-circle';
                            $color = 'text-primary';
                            switch($act['activity_type']) {
                                case 'status_change': $icon = 'bi-arrow-right-circle'; $color = 'text-info'; break;
                                case 'note': $icon = 'bi-journal-text'; $color = 'text-warning'; break;
                                case 'followup': $icon = 'bi-calendar-event'; $color = 'text-success'; break;
                                case 'email': $icon = 'bi-envelope'; $color = 'text-primary'; break;
                                case 'call': $icon = 'bi-telephone'; $color = 'text-secondary'; break;
                            }
                        ?>
                        <div class="d-flex mb-3 position-relative">
                            <div class="me-3 mt-1 <?= $color ?>">
                                <i class="bi <?= $icon ?> fs-5"></i>
                            </div>
                            <div>
                                <div class="fw-semibold" style="font-size: 14px;">
                                    <?= e($act['description']) ?>
                                    <?php if ($act['activity_type'] === 'status_change' && $act['old_value'] && $act['new_value']): ?>
                                        <span class="fw-normal text-muted ms-1">(<?= e($act['old_value']) ?> &rarr; <?= e($act['new_value']) ?>)</span>
                                    <?php endif; ?>
                                </div>
                                <div class="text-muted small">
                                    <i class="bi bi-person me-1"></i><a href="<?= BASE_URL ?>modules/leads/view.php?id=<?= $act['lead_id'] ?>" class="text-decoration-none text-muted fw-medium"><?= e($act['lead_name']) ?></a>
                                    <span class="mx-1">&bull;</span>
                                    <i class="bi bi-clock me-1"></i><?= timeAgo($act['created_at']) ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5"><span class="text-muted small">No recent activity found.</span></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
.activity-timeline { position: relative; padding-left: 10px; }
.activity-timeline::before {
    content: ''; position: absolute; top: 0; bottom: 0; left: 22px; width: 2px;
    background-color: #e9ecef; z-index: 0;
}
.activity-timeline > div {
    position: relative; z-index: 1;
}
.activity-timeline .bi {
    background: #fff; border-radius: 50%; padding: 2px;
}
</style>
