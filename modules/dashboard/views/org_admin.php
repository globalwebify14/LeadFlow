<?php
// org_admin.php view
$stats = $dashboard->getStatistics($orgId, null, 'org_admin');
$recentLeads = $dashboard->getRecentLeads($orgId, 6, null, 'org_admin');
$todayFollowups = $dashboard->getTodayFollowups($orgId, null);
$pipelineOverview = $dashboard->getPipelineOverview($orgId, null, 'org_admin');
$monthlyGrowth = $dashboard->getMonthlyLeadGrowth($orgId, null, 'org_admin');
$agentPerf = $dashboard->getAgentPerformance($orgId);

// Build source data for chart
$sourceData = $stats['leads_by_source'] ?? [];
?>

<div class="welcome-banner mb-4">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h4 class="fw-bold text-white mb-1">Welcome, <?= e(getUserName()) ?></h4>
            <p class="text-white-50 mb-0 small">Organization Admin Dashboard</p>
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
                <span class="stat-card-change text-primary"><i class="bi bi-person-plus me-1"></i><?= $stats['new_leads'] ?> New Today</span>
            </div>
        </div>
    </div>
    <div class="col-xl-4 col-md-6">
        <div class="stat-card">
            <div class="stat-card-icon" style="background:linear-gradient(135deg,#f59e0b,#d97706);"><i class="bi bi-kanban"></i></div>
            <div class="stat-card-info">
                <span class="stat-card-label">In Progress</span>
                <h3 class="stat-card-number"><?= $stats['follow_up'] ?></h3>
                <span class="stat-card-change text-warning"><i class="bi bi-clock me-1"></i>Active</span>
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
            <div class="stat-card-icon" style="background:linear-gradient(135deg,#10b981,#059669);"><i class="bi bi-trophy"></i></div>
            <div class="stat-card-info">
                <span class="stat-card-label">Deals Won</span>
                <h3 class="stat-card-number"><?= $stats['won_deals'] ?></h3>
                <span class="stat-card-change text-success"><i class="bi bi-check-circle me-1"></i>Closed</span>
            </div>
        </div>
    </div>
    <div class="col-xl-4 col-md-6">
        <div class="stat-card">
            <div class="stat-card-icon" style="background:linear-gradient(135deg,#f43f5e,#e11d48);"><i class="bi bi-x-circle"></i></div>
            <div class="stat-card-info">
                <span class="stat-card-label">Deals Lost</span>
                <h3 class="stat-card-number"><?= $stats['lost_deals'] ?></h3>
                <span class="stat-card-change text-danger"><i class="bi bi-dash-circle me-1"></i>Lost</span>
            </div>
        </div>
    </div>
    <div class="col-xl-4 col-md-6">
        <div class="stat-card">
            <div class="stat-card-icon" style="background:linear-gradient(135deg,#84cc16,#65a30d);"><i class="bi bi-person-badge"></i></div>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================
     TODAY'S SCHEDULE
     ============================================================ -->
<div class="row g-4 mb-4">
    <div class="col-12">
        <div class="dash-card shadow-sm border-0">
            <div class="card-header bg-white border-0 pt-4 pb-0 d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0"><i class="bi bi-calendar2-week me-2 text-danger"></i>Team Schedule (Today)</h6>
                <a href="<?= BASE_URL ?>modules/followups/" class="text-primary small text-decoration-none fw-semibold">View All</a>
            </div>
            <div class="card-body p-3 scroll-y" style="max-height: 420px;">
                <div class="row g-3">
                <?php if (!empty($todayFollowups)):
                    foreach ($todayFollowups as $f):
                        $pColor = $f['priority'] === 'high' ? '#ef4444' : ($f['priority'] === 'medium' ? '#f59e0b' : '#3b82f6');
                        $pLabel = strtoupper($f['priority'] ?? 'MEDIUM');
                ?>
                    <div class="col-xl-4 col-md-6">
                        <a href="<?= BASE_URL ?>modules/leads/view.php?id=<?= $f['lead_id'] ?>" class="schedule-card mb-0">
                            <span class="sc-priority" style="background:<?= $pColor ?>15;color:<?= $pColor ?>;">
                                <?= $pLabel ?>
                            </span>
                            
                            <div class="d-flex align-items-center gap-3">
                                <div class="sc-icon" style="background:<?= $pColor ?>10;color:<?= $pColor ?>;">
                                    <i class="bi bi-telephone"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="sc-title"><?= e($f['lead_name'] ?? 'General') ?></div>
                                    <div class="sc-desc"><?= e($f['title']) ?></div>
                                </div>
                            </div>
                            
                            <div class="sc-meta">
                                <div class="sc-meta-item">
                                    <i class="bi bi-person-badge text-primary"></i>
                                    <span><?= e($f['agent_name'] ?? 'Admin') ?></span>
                                </div>
                                <div class="sc-meta-item">
                                    <i class="bi bi-clock"></i>
                                    <span><?= date('h:i A', strtotime($f['followup_time'])) ?></span>
                                </div>
                                <div class="sc-view-link">
                                    View Lead <i class="bi bi-arrow-right"></i>
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
    <div class="col-xl-4 col-lg-6">
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
    
    <div class="col-xl-4 col-lg-6 col-md-12">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white border-0 pt-4 pb-0 d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0"><i class="bi bi-bar-chart-steps me-2 text-info"></i>Leads by Source</h6>
            </div>
            <div class="card-body d-flex flex-column justify-content-center" style="height:340px;">
                <canvas id="leadsBySourceChart"></canvas>
            </div>
        </div>
    </div>

    <div class="col-xl-4 col-lg-12 col-md-12">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white border-0 pt-4 pb-0">
                <h6 class="fw-bold mb-0"><i class="bi bi-people me-2 text-success"></i>Agent Performance</h6>
            </div>
            <div class="card-body" style="max-height:340px;overflow-y:auto;">
                <?php if (count($agentPerf) > 0): ?>
                    <?php foreach ($agentPerf as $ap): ?>
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
</div>

<div class="row g-4 mb-4">
    <div class="col-xl-8 col-lg-12">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white border-0 pt-4 pb-0 d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0"><i class="bi bi-graph-up me-2 text-primary"></i>Lead Growth</h6>
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

const colors = ['#6366f1', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#06b6d4', '#ec4899'];

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

// Leads by Source Pie Chart
const sourceData = <?= json_encode($sourceData) ?>;
const sourceCtx = document.getElementById('leadsBySourceChart');
if (sourceCtx && sourceData.length > 0) {
    new Chart(sourceCtx, {
        type: 'pie',
        data: {
            labels: sourceData.map(d => d.source || 'Other'),
            datasets: [{
                data: sourceData.map(d => d.count),
                backgroundColor: colors.slice(0, sourceData.length).reverse(),
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
