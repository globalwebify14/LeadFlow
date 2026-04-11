<?php
// team_lead.php view
/** @var Dashboard $dashboard */
/** @var int $orgId */
/** @var array $stats */

$stats = $dashboard->getStatistics($orgId, null, 'team_lead');
$todayFollowups = $dashboard->getTodayFollowups($orgId, null);
$agentPerf = $dashboard->getAgentPerformance($orgId);
$recentActivities = $dashboard->getRecentActivities($orgId, 8, null, 'team_lead');
?>

<div class="welcome-banner mb-4">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h4 class="fw-bold text-white mb-1">Hi, <?= e(getUserName()) ?></h4>
            <p class="text-white-50 mb-0 small">Team Performance Overview</p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= BASE_URL ?>modules/leads/" class="btn btn-light btn-sm fw-semibold"><i class="bi bi-people me-1"></i>Team Leads</a>
            <a href="<?= BASE_URL ?>modules/reports/" class="btn btn-outline-light btn-sm fw-semibold"><i class="bi bi-bar-chart me-1"></i>Reports</a>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="stat-card">
            <div class="stat-card-icon" style="background:linear-gradient(135deg,#6366f1,#4f46e5);"><i class="bi bi-people-fill"></i></div>
            <div class="stat-card-info">
                <span class="stat-card-label">Total Team Leads</span>
                <h3 class="stat-card-number"><?= $stats['total_leads'] ?></h3>
                <span class="stat-card-change text-primary"><i class="bi bi-arrow-up-short"></i></span>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="stat-card">
            <div class="stat-card-icon" style="background:linear-gradient(135deg,#8b5cf6,#7c3aed);"><i class="bi bi-person-plus"></i></div>
            <div class="stat-card-info">
                <span class="stat-card-label">Assigned Today</span>
                <h3 class="stat-card-number"><?= $stats['assigned_today'] ?></h3>
                <span class="stat-card-change text-primary">New Assignments</span>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="stat-card">
            <div class="stat-card-icon" style="background:linear-gradient(135deg,#f59e0b,#d97706);"><i class="bi bi-kanban"></i></div>
            <div class="stat-card-info">
                <span class="stat-card-label">In Progress</span>
                <h3 class="stat-card-number"><?= $stats['follow_up'] ?></h3>
                <span class="stat-card-change text-warning"><i class="bi bi-clock me-1"></i>Active</span>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="stat-card">
            <div class="stat-card-icon" style="background:linear-gradient(135deg,#06b6d4,#0891b2);"><i class="bi bi-telephone"></i></div>
            <div class="stat-card-info">
                <span class="stat-card-label">Follow-ups Today</span>
                <h3 class="stat-card-number"><?= count($todayFollowups) ?></h3>
                <span class="stat-card-change text-info">Due Today</span>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="stat-card">
            <div class="stat-card-icon" style="background:linear-gradient(135deg,#10b981,#059669);"><i class="bi bi-trophy"></i></div>
            <div class="stat-card-info">
                <span class="stat-card-label">Deals Won</span>
                <h3 class="stat-card-number"><?= $stats['won_deals'] ?></h3>
                <span class="stat-card-change text-success"><i class="bi bi-check-circle me-1"></i>Closed</span>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="stat-card">
            <div class="stat-card-icon" style="background:linear-gradient(135deg,#ef4444,#dc2626);"><i class="bi bi-x-circle"></i></div>
            <div class="stat-card-info">
                <span class="stat-card-label">Deals Lost</span>
                <h3 class="stat-card-number"><?= $stats['lost_deals'] ?></h3>
                <span class="stat-card-change text-danger"><i class="bi bi-dash-circle me-1"></i>Lost</span>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="stat-card">
            <div class="stat-card-icon" style="background:linear-gradient(135deg,#ec4899,#db2777);"><i class="bi bi-percent"></i></div>
            <div class="stat-card-info">
                <span class="stat-card-label">Team Conversion</span>
                <h3 class="stat-card-number"><?= $stats['conversion_rate'] ?>%</h3>
                <span class="stat-card-change text-success">Win Rate</span>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="stat-card">
            <div class="stat-card-icon" style="background:linear-gradient(135deg,#84cc16,#65a30d);"><i class="bi bi-person-badge"></i></div>
            <div class="stat-card-info">
                <span class="stat-card-label">Active Agents</span>
                <h3 class="stat-card-number"><?= count($agentPerf) ?></h3>
                <a href="<?= BASE_URL ?>modules/users/" class="stat-card-change text-success text-decoration-none"><i class="bi bi-arrow-right-short"></i>Manage</a>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================
     TODAY'S SCHEDULE
     ============================================================ -->
<div class="row g-4 mb-4">
    <div class="col-12">
        <div class="dash-card">
            <div class="dash-card-header">
                <div class="section-title">
                    <div class="st-icon" style="background:#fff1f2;color:#e11d48;"><i class="bi bi-calendar2-check-fill"></i></div>
                    Team Schedule (Today)
                </div>
                <div class="d-flex gap-2">
                    <a href="<?= BASE_URL ?>modules/followups/" class="btn btn-sm btn-light border" style="font-size:11px;font-weight:700;">View All</a>
                </div>
            </div>
            <div class="dash-card-body p-3 scroll-y" style="max-height: 400px;">
                <div class="row g-3">
                <?php if (!empty($todayFollowups)):
                    foreach ($todayFollowups as $f):
                        $pColor = $f['priority'] === 'high' ? '#ef4444' : ($f['priority'] === 'medium' ? '#f59e0b' : '#3b82f6');
                        $pLabel = strtoupper($f['priority'] ?? 'MEDIUM');
                ?>
                    <div class="col-xl-4 col-md-6 mb-3">
                        <a href="<?= BASE_URL ?>modules/leads/view.php?id=<?= $f['lead_id'] ?>" class="schedule-card h-100">
                            <div class="sc-main">
                                <div class="sc-icon-wrap" style="background:<?= $pColor ?>10;color:<?= $pColor ?>;">
                                    <i class="bi bi-telephone-fill"></i>
                                </div>
                                <div class="sc-content">
                                    <div class="sc-header">
                                        <h6 class="sc-title"><?= e($f['lead_name'] ?? 'General') ?></h6>
                                        <span class="sc-priority" style="background:<?= $pColor ?>15;color:<?= $pColor ?>;">
                                            <?= $pLabel ?>
                                        </span>
                                    </div>
                                    <?php 
                                        $displayDesc = !empty($f['description']) ? $f['description'] : $f['title'];
                                        if (trim(strtolower($f['title'])) === trim(strtolower($f['lead_name'])) && !empty($f['description'])) {
                                            $displayDesc = $f['description'];
                                        }
                                    ?>
                                    <p class="sc-desc"><?= e($displayDesc) ?></p>
                                </div>
                            </div>
                            
                            <div class="sc-divider"></div>
                            
                            <div class="sc-footer">
                                <div class="sc-meta-item">
                                    <i class="bi bi-person-badge"></i>
                                    <span><?= e($f['agent_name'] ?? 'Agent') ?></span>
                                </div>
                                <div class="sc-meta-item">
                                    <i class="bi bi-clock"></i>
                                    <span><?= date('h:i A', strtotime($f['followup_time'])) ?></span>
                                </div>
                                <div class="sc-view-link">
                                    Lead <i class="bi bi-arrow-right"></i>
                                </div>
                            </div>
                        </a>
                    </div>
                <?php endforeach; else: ?>
                    <div class="col-12 text-center py-5">
                        <i class="bi bi-calendar-x fs-2 text-muted d-block mb-2"></i>
                        <span class="text-muted small">No follow-ups scheduled for today.</span>
                    </div>
                <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-xl-8">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white border-0 pt-4 pb-0 d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0"><i class="bi bi-bar-chart-steps me-2 text-primary"></i>Agent Performance (Leads vs Won)</h6>
            </div>
            <div class="card-body" style="height:340px;">
                <canvas id="agentPerfChart"></canvas>
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white border-0 pt-4 pb-0 d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0"><i class="bi bi-pie-chart me-2 text-primary"></i>Team Leads by Status</h6>
            </div>
            <div class="card-body d-flex flex-column justify-content-center" style="height:340px;">
                <canvas id="leadsByStatusChart"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-xl-6">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white border-0 pt-4 pb-0 d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0"><i class="bi bi-pie-chart-fill me-2 text-info"></i>Follow-up Tracker</h6>
                <a href="<?= BASE_URL ?>modules/followups/" class="text-primary small text-decoration-none fw-semibold">View All</a>
            </div>
            <div class="card-body d-flex flex-column justify-content-center" style="height:340px;">
                <canvas id="followupChart"></canvas>
            </div>
        </div>
    </div>
    
    <div class="col-xl-6">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white border-0 pt-4 pb-0">
                <h6 class="fw-bold mb-0"><i class="bi bi-activity me-2 text-success"></i>Recent Team Activity</h6>
            </div>
            <div class="card-body" style="max-height:340px;overflow-y:auto;">
                <?php if (count($recentActivities) > 0): ?>
                    <div class="activity-timeline">
                        <?php foreach ($recentActivities as $act): ?>
                        <div class="activity-item pb-3 mb-3 border-bottom">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="fw-semibold small"><?= e($act['user_name'] ?? 'System') ?></span>
                                <small class="text-muted"><?= timeAgo($act['created_at']) ?></small>
                            </div>
                            <div class="text-muted small">
                                <?= e($act['action']) ?> on lead <span class="fw-medium text-dark"><?= e($act['lead_name']) ?></span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4"><span class="text-muted small">No recent activity</span></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
const colors = ['#6366f1', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#06b6d4', '#ec4899'];

// Agent Performance Bar Chart
const agentData = <?= json_encode($agentPerf) ?>;
const perfCtx = document.getElementById('agentPerfChart');
if (perfCtx && agentData.length > 0) {
    new Chart(perfCtx, {
        type: 'bar',
        data: {
            labels: agentData.map(d => d.name),
            datasets: [
                {
                    label: 'Total Leads Handled',
                    data: agentData.map(d => d.total_leads),
                    backgroundColor: 'rgba(99, 102, 241, 0.8)',
                    borderRadius: 4
                },
                {
                    label: 'Deals Won',
                    data: agentData.map(d => d.converted),
                    backgroundColor: 'rgba(16, 185, 129, 0.8)',
                    borderRadius: 4
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'top', labels: { usePointStyle: true, padding: 20 } }
            },
            scales: {
                y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.04)', drawBorder: false } },
                x: { grid: { display: false } }
            }
        }
    });
}

// Leads by Status Doughnut Chart
const statusData = <?= json_encode($stats['leads_by_status'] ?? []) ?>;
const statusCtx = document.getElementById('leadsByStatusChart');
if (statusCtx && statusData.length > 0) {
    new Chart(statusCtx, {
        type: 'doughnut',
        data: {
            labels: statusData.map(d => d.status),
            datasets: [{
                data: statusData.map(d => d.count),
                backgroundColor: colors.slice(0, statusData.length),
                borderWidth: 0,
                hoverOffset: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '70%',
            plugins: {
                legend: { position: 'bottom', labels: { boxWidth: 12, usePointStyle: true, padding: 20 } }
            }
        }
    });
}

// Follow-up Tracker Pie/Doughnut Chart
const fuCtx = document.getElementById('followupChart');
if (fuCtx) {
    new Chart(fuCtx, {
        type: 'pie',
        data: {
            labels: ['Pending', 'Missed'],
            datasets: [{
                data: [<?= $stats['pending_followups'] ?>, <?= $stats['missed_followups'] ?>],
                backgroundColor: ['#f59e0b', '#ef4444'],
                borderWidth: 0,
                hoverOffset: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom', labels: { boxWidth: 12, usePointStyle: true, padding: 20 } }
            }
        }
    });
}
</script>
