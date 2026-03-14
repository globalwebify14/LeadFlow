<?php
$pageTitle = 'Reports & Analytics';
require_once '../../config/auth.php';
requireLogin();
require_once '../../config/db.php';
require_once '../../models/Report.php';
require_once '../../models/User.php';

$orgId = getOrgId();
$reportModel = new Report($pdo);

$userRole = getUserRole();
$isAgent = ($userRole === 'agent');
$agentIdFilter = $isAgent ? getUserId() : ($_GET['agent_id'] ?? null);

// Date filters
$dateFilter = $_GET['date_filter'] ?? 'this_month';
$dateFrom = $_GET['date_from'] ?? null;
$dateTo = $_GET['date_to'] ?? null;

if ($dateFilter !== 'custom') {
    $dateTo = date('Y-m-d');
    switch ($dateFilter) {
        case 'today':
            $dateFrom = date('Y-m-d');
            break;
        case 'yesterday':
            $dateFrom = date('Y-m-d', strtotime('-1 day'));
            $dateTo = $dateFrom;
            break;
        case 'last_7_days':
            $dateFrom = date('Y-m-d', strtotime('-7 days'));
            break;
        case 'last_30_days':
            $dateFrom = date('Y-m-d', strtotime('-30 days'));
            break;
        case 'this_month':
            $dateFrom = date('Y-m-01');
            break;
        case 'last_month':
            $dateFrom = date('Y-m-01', strtotime('first day of last month'));
            $dateTo = date('Y-m-t', strtotime('last day of last month'));
            break;
        case 'all_time':
            $dateFrom = null;
            $dateTo = null;
            break;
        default:
            $dateFrom = date('Y-m-01'); // this_month
    }
}

// Fetch all agents for dropdown
$agents = [];
if (!$isAgent) {
    $stmtAgents = $pdo->prepare("SELECT id, name FROM users WHERE organization_id = :org AND role IN ('org_admin','team_lead','agent') AND is_active = 1 ORDER BY name");
    $stmtAgents->execute(['org' => $orgId]);
    $agents = $stmtAgents->fetchAll();
}

// Get metrics
$summary = $reportModel->getLeadSummary($orgId, $agentIdFilter, $dateFrom, $dateTo);
$conversion = $reportModel->getConversionRate($orgId, $agentIdFilter, $dateFrom, $dateTo);
$leadsBySource = $reportModel->getLeadsBySource($orgId, $agentIdFilter, $dateFrom, $dateTo);
$leadsByStatus = $reportModel->getLeadsByStatus($orgId, $agentIdFilter, $dateFrom, $dateTo);
$dealsRev = $reportModel->getDealsRevenueReport($orgId, $agentIdFilter, $dateFrom, $dateTo);
$followUps = $reportModel->getFollowUpStatusReport($orgId, $agentIdFilter, $dateFrom, $dateTo);

// Only for Admins
$agentPerf = [];
$leadDist = [];
$agentResp = [];
$campaigns = [];
if (!$isAgent) {
    $agentPerf = $reportModel->getAgentAdvancedPerformance($orgId, $dateFrom, $dateTo);
    $leadDist = $reportModel->getLeadDistribution($orgId, $dateFrom, $dateTo);
    $agentResp = $reportModel->getAgentResponseTime($orgId, $dateFrom, $dateTo);
    $campaigns = $reportModel->getFacebookCampaignReport($orgId, $dateFrom, $dateTo);
}

$monthlyGrowth = $reportModel->getMonthlyGrowth($orgId, $agentIdFilter);
$pipelinePerf = $reportModel->getPipelinePerformance($orgId, $agentIdFilter);

include '../../includes/header.php';
?>

<!-- Global Date Filter Bar -->
<div class="card shadow-sm border-0 mb-4 bg-white">
    <div class="card-body p-3">
        <form method="GET" class="row g-2 align-items-end" id="filterForm">
            <?php if (!$isAgent): ?>
            <div class="col-md-3">
                <label class="form-label small fw-semibold text-muted mb-1">Agent Filter</label>
                <select name="agent_id" class="form-select form-select-sm" onchange="document.getElementById('filterForm').submit()">
                    <option value="">All Agents</option>
                    <?php foreach ($agents as $a): ?>
                        <option value="<?= $a['id'] ?>" <?= $agentIdFilter == $a['id'] ? 'selected' : '' ?>><?= e($a['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            <div class="col-md-3">
                <label class="form-label small fw-semibold text-muted mb-1">Date Range</label>
                <select name="date_filter" id="dateFilter" class="form-select form-select-sm" onchange="toggleCustomDates(); document.getElementById('filterForm').submit()">
                    <option value="today" <?= $dateFilter === 'today' ? 'selected' : '' ?>>Today</option>
                    <option value="yesterday" <?= $dateFilter === 'yesterday' ? 'selected' : '' ?>>Yesterday</option>
                    <option value="last_7_days" <?= $dateFilter === 'last_7_days' ? 'selected' : '' ?>>Last 7 Days</option>
                    <option value="last_30_days" <?= $dateFilter === 'last_30_days' ? 'selected' : '' ?>>Last 30 Days</option>
                    <option value="this_month" <?= $dateFilter === 'this_month' ? 'selected' : '' ?>>This Month</option>
                    <option value="last_month" <?= $dateFilter === 'last_month' ? 'selected' : '' ?>>Last Month</option>
                    <option value="all_time" <?= $dateFilter === 'all_time' ? 'selected' : '' ?>>All Time</option>
                    <option value="custom" <?= $dateFilter === 'custom' ? 'selected' : '' ?>>Custom Range...</option>
                </select>
            </div>
            <div class="col-md-2 custom-dates" style="<?= $dateFilter !== 'custom' ? 'display:none;' : '' ?>">
                <label class="form-label small fw-semibold text-muted mb-1">From</label>
                <input type="date" name="date_from" class="form-control form-control-sm" value="<?= e($dateFrom) ?>">
            </div>
            <div class="col-md-2 custom-dates" style="<?= $dateFilter !== 'custom' ? 'display:none;' : '' ?>">
                <label class="form-label small fw-semibold text-muted mb-1">To</label>
                <input type="date" name="date_to" class="form-control form-control-sm" value="<?= e($dateTo) ?>">
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm w-100"><i class="bi bi-funnel me-1"></i> Apply</button>
                <?php if (!$isAgent): ?>
                <a href="<?= BASE_URL ?>modules/reports/export.php?<?= http_build_query($_GET) ?>" class="btn btn-success btn-sm w-100"><i class="bi bi-download me-1"></i> Export</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- Summary Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card shadow-sm border-0 h-100 bg-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-muted fw-semibold small text-uppercase tracking-wide">Total Leads Data</span>
                    <div class="rounded-circle d-flex align-items-center justify-content-center" style="width:32px;height:32px;background:rgba(99,102,241,0.1);color:#4f46e5;">
                        <i class="bi bi-people-fill"></i>
                    </div>
                </div>
                <h3 class="fw-bold mb-1"><?= number_format($summary['total_leads'] ?? 0) ?></h3>
                <div class="small <?= ($summary['leads_today'] ?? 0) > 0 ? 'text-success' : 'text-muted' ?>">
                    <i class="bi <?= ($summary['leads_today'] ?? 0) > 0 ? 'bi-arrow-up-right' : 'bi-dash' ?>"></i> <?= number_format($summary['leads_today'] ?? 0) ?> arrived today
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm border-0 h-100 bg-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-muted fw-semibold small text-uppercase tracking-wide">Converted Leads</span>
                    <div class="rounded-circle d-flex align-items-center justify-content-center" style="width:32px;height:32px;background:rgba(16,185,129,0.1);color:#059669;">
                        <i class="bi bi-check-circle-fill"></i>
                    </div>
                </div>
                <h3 class="fw-bold mb-1"><?= number_format($conversion['converted']) ?></h3>
                <div class="small fw-semibold <?= $conversion['rate'] >= 10 ? 'text-success' : 'text-warning' ?>">
                    <i class="bi bi-bullseye"></i> <?= $conversion['rate'] ?>% Conversion Rate
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm border-0 h-100 bg-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-muted fw-semibold small text-uppercase tracking-wide">Total Revenue</span>
                    <div class="rounded-circle d-flex align-items-center justify-content-center" style="width:32px;height:32px;background:rgba(245,158,11,0.1);color:#d97706;">
                        <i class="bi bi-currency-dollar"></i>
                    </div>
                </div>
                <h3 class="fw-bold mb-1"><?= formatCurrency($dealsRev['total_revenue'] ?? 0) ?></h3>
                <div class="small text-muted">
                    <i class="bi bi-trophy-fill text-warning"></i> <?= $dealsRev['total_closed_deals'] ?? 0 ?> Deals Won
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm border-0 h-100 bg-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-muted fw-semibold small text-uppercase tracking-wide">Follow-Up Action</span>
                    <div class="rounded-circle d-flex align-items-center justify-content-center" style="width:32px;height:32px;background:rgba(239,68,68,0.1);color:#dc2626;">
                        <i class="bi bi-clock-history"></i>
                    </div>
                </div>
                <h3 class="fw-bold mb-1 text-danger"><?= number_format($followUps['overdue_tasks'] ?? 0) ?> <span class="fs-6 fw-normal text-muted">Overdue</span></h3>
                <div class="small text-muted">
                    <?= number_format($followUps['pending_tasks'] ?? 0) ?> Pending | <?= number_format($followUps['completed_tasks'] ?? 0) ?> Completed
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <!-- Monthly Lead Trend -->
    <div class="col-lg-8">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white border-0 pt-4 pb-0"><h6 class="fw-bold"><i class="bi bi-graph-up me-2 text-primary"></i>Daily Lead Trend (Last 30 Days)</h6></div>
            <div class="card-body"><canvas id="monthlyChart" height="280"></canvas></div>
        </div>
    </div>
    <!-- Leads by Status (Pie) -->
    <div class="col-lg-4">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white border-0 pt-4 pb-0"><h6 class="fw-bold"><i class="bi bi-pie-chart me-2 text-info"></i>Leads by Pipeline Stage</h6></div>
            <div class="card-body d-flex flex-column align-items-center justify-content-center">
                <?php if (empty($leadsByStatus)): ?>
                    <p class="text-muted small">No data for selected period</p>
                <?php else: ?>
                    <canvas id="statusChart" height="240"></canvas>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <!-- Leads by Source -->
    <div class="col-lg-6">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white border-0 pt-4 pb-0"><h6 class="fw-bold"><i class="bi bi-bar-chart me-2 text-success"></i>Leads by Source</h6></div>
            <div class="card-body">
                <?php if (empty($leadsBySource)): ?>
                    <p class="text-muted small text-center mt-3">No data for selected period</p>
                <?php else: ?>
                    <canvas id="sourceChart" height="250"></canvas>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <!-- Pipeline Performance -->
    <div class="col-lg-6">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white border-0 pt-4 pb-0"><h6 class="fw-bold"><i class="bi bi-funnel me-2 text-warning"></i>Pipeline Values</h6></div>
            <div class="card-body">
                <?php if (empty($pipelinePerf)): ?>
                    <p class="text-muted small text-center mt-3">No data for selected period</p>
                <?php else: ?>
                    <canvas id="pipelineChart" height="250"></canvas>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php if (!$isAgent): ?>
<div class="row g-4 mb-4">
    <!-- Agent Performance Table -->
    <div class="col-lg-7">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white border-0 pt-4 d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0"><i class="bi bi-people me-2 text-primary"></i>Agent Performance Leaderboard</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead><tr class="small text-uppercase text-muted fw-semibold"><th>Agent</th><th>Assigned</th><th>Contacted</th><th>Deals</th><th>Conv Ratio</th><th>Avg Resp Time</th></tr></thead>
                        <tbody>
                            <?php foreach ($agentPerf as $ap): ?>
                            <?php 
                                // Find response time for this agent
                                $respStr = "N/A";
                                foreach ($agentResp as $ar) {
                                    if ($ar['agent_name'] === $ap['name'] && $ar['avg_response_minutes'] !== null) {
                                        $rmins = round($ar['avg_response_minutes']);
                                        if ($rmins < 60) $respStr = $rmins . "m";
                                        else $respStr = round($rmins/60, 1) . "h";
                                        break;
                                    }
                                }
                            ?>
                            <tr>
                                <td class="fw-semibold text-dark"><?= e($ap['name']) ?></td>
                                <td><?= number_format($ap['total_leads']) ?></td>
                                <td><?= number_format($ap['contacted_leads']) ?></td>
                                <td class="fw-bold text-success"><?= number_format($ap['converted_deals']) ?></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <span class="me-2 fw-semibold" style="width: 32px;"><?= $ap['conv_rate'] ?>%</span>
                                        <div class="progress" style="height:6px;width:60px;">
                                            <div class="progress-bar bg-<?= $ap['conv_rate'] > 20 ? 'success' : ($ap['conv_rate'] > 5 ? 'warning' : 'danger') ?>" style="width:<?= min(100, $ap['conv_rate']) ?>%"></div>
                                        </div>
                                    </div>
                                </td>
                                <td><span class="badge bg-light text-dark border"><?= $respStr ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($agentPerf)): ?><tr><td colspan="6" class="text-center text-muted py-4">No agents active in this timeframe</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Lead Distribution (Auto-Assign audit) -->
    <div class="col-lg-5">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white border-0 pt-4">
                <h6 class="fw-bold mb-0"><i class="bi bi-diagram-3 me-2 text-info"></i>Lead Distribution Verification</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead><tr class="small text-uppercase text-muted fw-semibold"><th>Agent</th><th>Leads Assigned</th><th>Fairness Gap</th></tr></thead>
                        <tbody>
                            <?php 
                            $maxLeads = 0;
                            if (count($leadDist) > 0) {
                                $maxLeads = max(array_column($leadDist, 'assigned_count'));
                            }
                            ?>
                            <?php foreach ($leadDist as $ld): ?>
                            <tr>
                                <td class="fw-semibold"><?= e($ld['agent_name']) ?></td>
                                <td class="fw-bold"><?= number_format($ld['assigned_count']) ?></td>
                                <td>
                                    <?php $pct = $maxLeads > 0 ? ($ld['assigned_count'] / $maxLeads) * 100 : 0; ?>
                                    <div class="progress" style="height:6px;">
                                        <div class="progress-bar bg-primary" style="width:<?= $pct ?>%"></div>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Facebook Campaign ROI -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white border-0 pt-4">
        <h6 class="fw-bold mb-0"><i class="bi bi-facebook me-2 text-primary" style="color:#1877f2 !important;"></i>Facebook Ads Campaign ROI</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead><tr class="small text-uppercase text-muted fw-semibold"><th>Facebook Page</th><th>Campaign Name</th><th>Leads Generated</th><th>Performance</th></tr></thead>
                <tbody>
                    <?php 
                    $maxCampLeads = count($campaigns) > 0 ? max(array_column($campaigns, 'lead_count')) : 0;
                    foreach ($campaigns as $camp): 
                    ?>
                    <tr>
                        <td class="fw-semibold text-dark"><?= e($camp['page_name'] ?? 'Disconnected Page') ?></td>
                        <td><?= e($camp['campaign_name']) ?></td>
                        <td class="fw-bold text-primary"><?= number_format($camp['lead_count']) ?></td>
                        <td style="width: 200px;">
                            <?php $cpct = $maxCampLeads > 0 ? ($camp['lead_count'] / $maxCampLeads) * 100 : 0; ?>
                            <div class="progress" style="height:8px;">
                                <div class="progress-bar" style="background-color: #1877f2; width:<?= $cpct ?>%"></div>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($campaigns)): ?><tr><td colspan="4" class="text-center text-muted py-4">No Facebook Ad leads generated in this period</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
function toggleCustomDates() {
    const val = document.getElementById('dateFilter').value;
    const customDivs = document.querySelectorAll('.custom-dates');
    if (val === 'custom') {
        customDivs.forEach(el => el.style.display = 'block');
    } else {
        customDivs.forEach(el => el.style.display = 'none');
    }
}
</script>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
const colors = ['#6366f1','#10b981','#f59e0b','#ef4444','#3b82f6','#8b5cf6','#ec4899','#14b8a6','#f97316','#64748b'];

// Monthly / Daily Trend Chart
const monthlyData = <?= json_encode($monthlyGrowth) ?>;
if (document.getElementById('monthlyChart') && monthlyData.length > 0) {
    new Chart(document.getElementById('monthlyChart'), {
        type: 'line',
        data: { 
            labels: monthlyData.map(d => d.label), 
            datasets: [{ 
                label: 'Leads Received', 
                data: monthlyData.map(d => d.count), 
                borderColor: '#6366f1', 
                backgroundColor: 'rgba(99,102,241,0.1)', 
                borderWidth: 2, 
                fill: true, 
                tension: 0.3,
                pointRadius: 4,
                pointBackgroundColor: '#fff',
                pointBorderWidth: 2
            }] 
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.04)' } }, x: { grid: { display: false } } } }
    });
}

// Status Pipeline Pie
const statusData = <?= json_encode($leadsByStatus) ?>;
if (document.getElementById('statusChart') && statusData.length > 0) {
    new Chart(document.getElementById('statusChart'), {
        type: 'doughnut',
        data: { 
            labels: statusData.map(d => d.status), 
            datasets: [{ data: statusData.map(d => d.count), backgroundColor: colors, borderWidth: 0 }] 
        },
        options: { responsive: true, maintainAspectRatio: false, cutout: '65%', plugins: { legend: { position: 'right', labels: { usePointStyle: true, padding: 12 } } } }
    });
}

// Source Chart
const sourceData = <?= json_encode($leadsBySource) ?>;
if (document.getElementById('sourceChart') && sourceData.length > 0) {
    new Chart(document.getElementById('sourceChart'), {
        type: 'bar',
        data: { 
            labels: sourceData.map(d => d.source), 
            datasets: [{ label: 'Leads', data: sourceData.map(d => d.count), backgroundColor: colors, borderRadius: 6 }] 
        },
        options: { responsive: true, maintainAspectRatio: false, indexAxis: 'y', plugins: { legend: { display: false } }, scales: { x: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.04)' } }, y: { grid: { display: false } } } }
    });
}

// Pipeline Chart (Deals/Values)
const pipeData = <?= json_encode($pipelinePerf) ?>;
if (document.getElementById('pipelineChart') && pipeData.length > 0) {
    new Chart(document.getElementById('pipelineChart'), {
        type: 'bar',
        data: { 
            labels: pipeData.map(p => p.name), 
            datasets: [{ 
                label: 'Deals Count', 
                data: pipeData.map(p => p.deals_count), 
                backgroundColor: pipeData.map(p => p.color ? p.color : '#ccc'), 
                borderRadius: 4 
            }] 
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.04)' } }, x: { grid: { display: false } } } }
    });
}
</script>

<?php include '../../includes/footer.php'; ?>
