<?php
$pageTitle = 'Reports & Analytics';
require_once '../../config/auth.php';
requireLogin();
require_once '../../config/db.php';
require_once '../../models/Report.php';

$orgId = getOrgId();
$reportModel = new Report($pdo);

$leadsByStatus = $reportModel->getLeadsByStatus($orgId);
$leadsBySource = $reportModel->getLeadsBySource($orgId);
$leadsByPriority = $reportModel->getLeadsByPriority($orgId);
$conversion = $reportModel->getConversionRate($orgId);
$agentPerf = $reportModel->getAgentPerformance($orgId);
$monthlyGrowth = $reportModel->getMonthlyGrowth($orgId);
$pipelinePerf = $reportModel->getPipelinePerformance($orgId);
$dealRevenue = $reportModel->getDealRevenueByMonth($orgId);

include '../../includes/header.php';
?>

<!-- Summary Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-card-icon" style="background:linear-gradient(135deg,#6366f1,#4f46e5);"><i class="bi bi-people-fill"></i></div>
            <div class="stat-card-info">
                <span class="stat-card-label">Total Leads</span>
                <h3 class="stat-card-number"><?= $conversion['total'] ?></h3>
                <span class="stat-card-change text-primary"><i class="bi bi-database me-1"></i>All</span>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-card-icon" style="background:linear-gradient(135deg,#10b981,#059669);"><i class="bi bi-check-circle-fill"></i></div>
            <div class="stat-card-info">
                <span class="stat-card-label">Converted</span>
                <h3 class="stat-card-number"><?= $conversion['converted'] ?></h3>
                <span class="stat-card-change text-success"><i class="bi bi-trophy-fill me-1"></i>Won</span>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-card-icon" style="background:linear-gradient(135deg,#f59e0b,#d97706);"><i class="bi bi-bullseye"></i></div>
            <div class="stat-card-info">
                <span class="stat-card-label">Conversion Rate</span>
                <h3 class="stat-card-number"><?= $conversion['rate'] ?>%</h3>
                <span class="stat-card-change text-warning"><i class="bi bi-graph-up-arrow me-1"></i>Ratio</span>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-card-icon" style="background:linear-gradient(135deg,#ef4444,#dc2626);"><i class="bi bi-graph-up"></i></div>
            <div class="stat-card-info">
                <span class="stat-card-label">Active Agents</span>
                <h3 class="stat-card-number"><?= count($agentPerf) ?></h3>
                <span class="stat-card-change text-danger"><i class="bi bi-people-fill me-1"></i>Team</span>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <!-- Monthly Lead Growth -->
    <div class="col-lg-8">
        <div class="card shadow-sm border-0"><div class="card-header bg-white border-0 pt-4 pb-0"><h6 class="fw-bold"><i class="bi bi-graph-up me-2 text-primary"></i>Monthly Lead Growth</h6></div>
        <div class="card-body"><canvas id="monthlyChart" height="280"></canvas></div></div>
    </div>
    <!-- Leads by Status (Pie) -->
    <div class="col-lg-4">
        <div class="card shadow-sm border-0"><div class="card-header bg-white border-0 pt-4 pb-0"><h6 class="fw-bold"><i class="bi bi-pie-chart me-2 text-info"></i>Leads by Status</h6></div>
        <div class="card-body"><canvas id="statusChart" height="280"></canvas></div></div>
    </div>
</div>

<div class="row g-4 mb-4">
    <!-- Leads by Source -->
    <div class="col-lg-6">
        <div class="card shadow-sm border-0"><div class="card-header bg-white border-0 pt-4 pb-0"><h6 class="fw-bold"><i class="bi bi-bar-chart me-2 text-success"></i>Leads by Source</h6></div>
        <div class="card-body"><canvas id="sourceChart" height="250"></canvas></div></div>
    </div>
    <!-- Pipeline Performance -->
    <div class="col-lg-6">
        <div class="card shadow-sm border-0"><div class="card-header bg-white border-0 pt-4 pb-0"><h6 class="fw-bold"><i class="bi bi-funnel me-2 text-warning"></i>Pipeline Performance</h6></div>
        <div class="card-body"><canvas id="pipelineChart" height="250"></canvas></div></div>
    </div>
</div>

<!-- Agent Performance Table -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white border-0 pt-4"><h6 class="fw-bold"><i class="bi bi-people me-2 text-primary"></i>Agent Performance</h6></div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead><tr><th>Agent</th><th>Total Leads</th><th>Converted</th><th>Conversion Rate</th><th>Performance</th></tr></thead>
                <tbody>
                    <?php foreach ($agentPerf as $ap): ?>
                    <tr>
                        <td class="fw-semibold"><?= e($ap['name']) ?></td>
                        <td><?= $ap['total_leads'] ?></td>
                        <td><?= $ap['converted'] ?></td>
                        <td><?= $ap['conv_rate'] ?? 0 ?>%</td>
                        <td>
                            <div class="progress" style="height:8px;width:120px;">
                                <div class="progress-bar bg-<?= ($ap['conv_rate'] ?? 0) > 50 ? 'success' : (($ap['conv_rate'] ?? 0) > 20 ? 'warning' : 'danger') ?>" style="width:<?= min(100, $ap['conv_rate'] ?? 0) ?>%"></div>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($agentPerf)): ?><tr><td colspan="5" class="text-center text-muted py-3">No agent data</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
const colors = ['#6366f1','#10b981','#f59e0b','#ef4444','#3b82f6','#8b5cf6','#ec4899','#14b8a6','#f97316','#64748b'];

// Monthly Chart
new Chart(document.getElementById('monthlyChart'), {
    type: 'bar',
    data: { labels: <?= json_encode(array_column($monthlyGrowth, 'label')) ?>, datasets: [{ label: 'Leads', data: <?= json_encode(array_column($monthlyGrowth, 'count')) ?>, backgroundColor: 'rgba(99,102,241,0.7)', borderRadius: 6 }] },
    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.04)' } }, x: { grid: { display: false } } } }
});

// Status Pie
new Chart(document.getElementById('statusChart'), {
    type: 'doughnut',
    data: { labels: <?= json_encode(array_column($leadsByStatus, 'status')) ?>, datasets: [{ data: <?= json_encode(array_column($leadsByStatus, 'count')) ?>, backgroundColor: colors, borderWidth: 0 }] },
    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, padding: 12 } } } }
});

// Source Chart
new Chart(document.getElementById('sourceChart'), {
    type: 'bar',
    data: { labels: <?= json_encode(array_column($leadsBySource, 'source')) ?>, datasets: [{ label: 'Leads', data: <?= json_encode(array_column($leadsBySource, 'count')) ?>, backgroundColor: colors, borderRadius: 6 }] },
    options: { responsive: true, maintainAspectRatio: false, indexAxis: 'y', plugins: { legend: { display: false } }, scales: { x: { grid: { color: 'rgba(0,0,0,0.04)' } } } }
});

// Pipeline Chart
const pipeData = <?= json_encode($pipelinePerf) ?>;
new Chart(document.getElementById('pipelineChart'), {
    type: 'bar',
    data: { labels: pipeData.map(p => p.name), datasets: [{ label: 'Deals', data: pipeData.map(p => p.deals_count), backgroundColor: pipeData.map(p => p.color + '99'), borderRadius: 6 }] },
    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.04)' } }, x: { grid: { display: false } } } }
});
</script>

<?php include '../../includes/footer.php'; ?>


