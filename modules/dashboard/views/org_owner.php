<?php
// org_owner.php view
$stats = $dashboard->getStatistics($orgId, null, 'org_owner');
$recentLeads = $dashboard->getRecentLeads($orgId, 8, null, 'org_owner');
$todayFollowups = $dashboard->getTodayFollowups($orgId, null);
$recentActivities = $dashboard->getRecentActivities($orgId, 10, null, 'org_owner');
$monthlyGrowth = $dashboard->getMonthlyLeadGrowth($orgId, null, 'org_owner');
$pipelineOverview = $dashboard->getPipelineOverview($orgId, null, 'org_owner');
$agentPerf = $dashboard->getAgentPerformance($orgId);
$overdueCount = $followupModel->getOverdueCount($orgId, null);
?>

<div class="welcome-banner mb-4">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h4 class="fw-bold text-white mb-1">Welcome back, <?= e(getUserName()) ?> 👋</h4>
            <p class="text-white-50 mb-0 small">Organization Owner Dashboard</p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= BASE_URL ?>modules/leads/add.php" class="btn btn-light btn-sm fw-semibold"><i class="bi bi-plus-lg me-1"></i>Add Lead</a>
            <a href="<?= BASE_URL ?>modules/reports/" class="btn btn-outline-light btn-sm fw-semibold"><i class="bi bi-bar-chart me-1"></i>Reports</a>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-xl-4 col-md-6">
        <div class="stat-card">
            <div class="stat-card-icon" style="background:linear-gradient(135deg,#6366f1,#4f46e5);"><i class="bi bi-people-fill"></i></div>
            <div class="stat-card-info">
                <span class="stat-card-label">Total Leads</span>
                <h3 class="stat-card-number"><?= $stats['total_leads'] ?></h3>
                <span class="stat-card-change text-primary"><i class="bi bi-person-plus me-1"></i><?= $stats['new_leads'] ?> New</span>
            </div>
        </div>
    </div>
    <div class="col-xl-4 col-md-6">
        <div class="stat-card">
            <div class="stat-card-icon" style="background:linear-gradient(135deg,#10b981,#059669);"><i class="bi bi-currency-rupee"></i></div>
            <div class="stat-card-info">
                <span class="stat-card-label">Deal Value</span>
                <h3 class="stat-card-number"><?= formatCurrency($stats['deal_value']) ?></h3>
                <span class="stat-card-change text-success"><i class="bi bi-trophy-fill me-1"></i><?= $stats['won_deals'] ?> Won</span>
            </div>
        </div>
    </div>
    <div class="col-xl-4 col-md-6">
        <div class="stat-card">
            <div class="stat-card-icon" style="background:linear-gradient(135deg,#f59e0b,#d97706);"><i class="bi bi-bullseye"></i></div>
            <div class="stat-card-info">
                <span class="stat-card-label">Conversion Rate</span>
                <h3 class="stat-card-number"><?= $stats['conversion_rate'] ?>%</h3>
                <span class="stat-card-change text-warning"><i class="bi bi-graph-up-arrow me-1"></i>Ratio</span>
            </div>
        </div>
    </div>
    <div class="col-xl-4 col-md-6">
        <div class="stat-card">
            <div class="stat-card-icon" style="background:linear-gradient(135deg,#ef4444,#dc2626);"><i class="bi bi-clock-history"></i></div>
            <div class="stat-card-info">
                <span class="stat-card-label">Due Follow-ups</span>
                <h3 class="stat-card-number"><?= $stats['pending_followups'] ?></h3>
                <a href="<?= BASE_URL ?>modules/followups/" class="stat-card-change text-danger text-decoration-none"><i class="bi bi-arrow-right-short"></i><?= $stats['missed_followups'] ?> Missed</a>
            </div>
        </div>
    </div>
    <div class="col-xl-4 col-md-6">
        <div class="stat-card">
            <div class="stat-card-icon" style="background:linear-gradient(135deg,#8b5cf6,#7c3aed);"><i class="bi bi-person-lines-fill"></i></div>
            <div class="stat-card-info">
                <span class="stat-card-label">Assigned Today</span>
                <h3 class="stat-card-number"><?= $stats['assigned_today'] ?></h3>
                <span class="stat-card-change" style="color:#7c3aed;"><i class="bi bi-arrow-down-right-circle me-1"></i>New</span>
            </div>
        </div>
    </div>
    <div class="col-xl-4 col-md-6">
        <div class="stat-card">
            <div class="stat-card-icon" style="background:linear-gradient(135deg,#84cc16,#65a30d);"><i class="bi bi-person-badge"></i></div>
            <div class="stat-card-info">
                <span class="stat-card-label">Team Members</span>
                <h3 class="stat-card-number"><?= $stats['team_members'] ?></h3>
                <a href="<?= BASE_URL ?>modules/users/" class="stat-card-change text-success text-decoration-none"><i class="bi bi-arrow-right-short"></i>User Mgmt</a>
            </div>
        </div>
    </div>
</div>

<?php if ($overdueCount > 0): ?>
<div class="alert alert-warning border-0 shadow-sm d-flex align-items-center mb-4" style="border-radius:14px;background:linear-gradient(135deg,#fffbeb,#fef3c7);border-left:4px solid #f59e0b !important;">
    <div class="rounded-circle d-flex align-items-center justify-content-center me-3 flex-shrink-0" style="width:42px;height:42px;background:rgba(245,158,11,0.15);"><i class="bi bi-exclamation-triangle-fill text-warning fs-5"></i></div>
    <div class="flex-grow-1">
        <strong>Attention!</strong> You have <strong><?= $overdueCount ?></strong> overdue follow-up<?= $overdueCount > 1 ? 's' : '' ?> across the organization.
        <a href="<?= BASE_URL ?>modules/followups/?filter=overdue" class="ms-2 fw-semibold text-decoration-none text-warning">View Now →</a>
    </div>
</div>
<?php endif; ?>

<div class="row g-4 mb-4">
    <div class="col-xl-8">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white border-0 pt-4 pb-0 d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0"><i class="bi bi-graph-up me-2 text-primary"></i>Lead Growth</h6>
                <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill px-3 py-2 small">Last 6 Months</span>
            </div>
            <div class="card-body" style="height:340px;">
                <canvas id="leadGrowthChart"></canvas>
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white border-0 pt-4 pb-0 d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0"><i class="bi bi-pie-chart me-2 text-primary"></i>Leads by Status</h6>
            </div>
            <div class="card-body d-flex flex-column justify-content-center" style="height:340px;">
                <canvas id="leadsByStatusChart"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-xl-4">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white border-0 pt-4 pb-0 d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0"><i class="bi bi-funnel me-2 text-primary"></i>Pipeline Overview</h6>
                <a href="<?= BASE_URL ?>modules/pipeline/" class="text-primary small text-decoration-none fw-semibold">Board</a>
            </div>
            <div class="card-body pt-2">
                <?php if (count($pipelineOverview) > 0): ?>
                    <?php 
                    $totalPipeline = array_sum(array_column($pipelineOverview, 'count'));
                    foreach ($pipelineOverview as $stage): 
                        $pct = $totalPipeline > 0 ? round(($stage['count'] / $totalPipeline) * 100) : 0;
                    ?>
                    <div class="pipeline-row mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <div class="d-flex align-items-center">
                                <span class="pipeline-dot" style="background:<?= e($stage['color']) ?>;"></span>
                                <span class="small fw-medium"><?= e($stage['name']) ?></span>
                            </div>
                            <span class="badge rounded-pill px-2 py-1" style="background:<?= e($stage['color']) ?>15;color:<?= e($stage['color']) ?>;font-size:11px;"><?= $stage['count'] ?></span>
                        </div>
                        <div class="progress" style="height:5px;">
                            <div class="progress-bar" role="progressbar" style="width:<?= $pct ?>%;background:<?= e($stage['color']) ?>;"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center py-4"><i class="bi bi-inbox text-muted fs-1 d-block mb-2"></i><span class="text-muted small">No pipeline data</span></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-xl-6">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white border-0 pt-4 pb-0">
                <h6 class="fw-bold mb-0"><i class="bi bi-people me-2 text-success"></i>Agent Performance</h6>
            </div>
            <div class="card-body">
                <?php if (count($agentPerf) > 0): ?>
                    <?php foreach (array_slice($agentPerf, 0, 5) as $ap): ?>
                    <?php $convRate = $ap['total_leads'] > 0 ? round(($ap['converted'] / $ap['total_leads']) * 100) : 0; ?>
                    <div class="agent-item">
                        <div class="agent-avatar" style="background:linear-gradient(135deg,#6366f1,#4f46e5);">
                            <?= getInitials($ap['name']) ?>
                        </div>
                        <div class="agent-info">
                            <div class="small fw-semibold"><?= e($ap['name']) ?></div>
                            <div class="text-muted" style="font-size:12px;"><?= $ap['total_leads'] ?> leads • <?= $ap['converted'] ?> won</div>
                            <div class="progress mt-1" style="height:4px;width:100px;">
                                <div class="progress-bar bg-success" style="width:<?= min(100, $convRate) ?>%;"></div>
                            </div>
                        </div>
                        <span class="badge bg-success bg-opacity-10 text-success fw-bold small"><?= $convRate ?>%</span>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center py-4"><span class="text-muted small">No agent data</span></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-xl-6">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white border-0 pt-4 pb-0">
                <h6 class="fw-bold mb-0"><i class="bi bi-activity me-2 text-info"></i>Recent Organization Activity</h6>
            </div>
            <div class="card-body" style="max-height:340px;overflow-y:auto;">
                <?php if (!empty($recentActivities)): ?>
                    <?php foreach (array_slice($recentActivities, 0, 8) as $a): ?>
                    <div class="activity-item">
                        <div class="activity-icon-wrap">
                            <div class="activity-icon" style="background:#06b6d415;color:#06b6d4;"><i class="bi bi-journal"></i></div>
                        </div>
                        <div class="activity-content">
                            <div class="small"><span class="fw-semibold"><?= e($a['lead_name']) ?></span> <span class="text-muted">by <?= e($a['user_name']) ?></span></div>
                            <div class="text-muted" style="font-size:12px;"><?= e(truncate($a['description'] ?? '', 55)) ?></div>
                            <div class="text-muted" style="font-size:11px;"><?= timeAgo($a['created_at']) ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center py-4"><span class="text-muted small">No recent activities</span></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
// Lead Growth Chart
const monthlyData = <?= json_encode($monthlyGrowth) ?>;
const ctx = document.getElementById('leadGrowthChart');
if (ctx && monthlyData.length > 0) {
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: monthlyData.map(d => d.label),
            datasets: [{
                label: 'Leads',
                data: monthlyData.map(d => d.count),
                borderColor: '#6366f1',
                backgroundColor: (context) => {
                    const chart = context.chart;
                    const {ctx: c, chartArea} = chart;
                    if (!chartArea) return 'rgba(99,102,241,0.08)';
                    const gradient = c.createLinearGradient(0, chartArea.top, 0, chartArea.bottom);
                    gradient.addColorStop(0, 'rgba(99,102,241,0.25)');
                    gradient.addColorStop(1, 'rgba(99,102,241,0.01)');
                    return gradient;
                },
                fill: true,
                tension: 0.4,
                pointBackgroundColor: '#6366f1',
                pointBorderColor: '#fff',
                pointBorderWidth: 3,
                pointRadius: 6,
                pointHoverRadius: 8,
                borderWidth: 3,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.04)', drawBorder: false }, ticks: { font: { size: 12 } } },
                x: { grid: { display: false }, ticks: { font: { size: 12 } } }
            },
            interaction: { intersect: false, mode: 'index' }
        }
    });
}

// Leads by Status Doughnut Chart
const statusData = <?= json_encode($stats['leads_by_status'] ?? []) ?>;
const statusCtx = document.getElementById('leadsByStatusChart');
if (statusCtx && statusData.length > 0) {
    const colors = ['#6366f1', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#06b6d4', '#ec4899'];
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
</script>
