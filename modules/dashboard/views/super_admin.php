<?php
// super_admin.php view
$stats = $dashboard->getSuperAdminStats();
$recentActivities = $dashboard->getPlatformRecentActivity(8);
$monthlyGrowth = $dashboard->getPlatformOrgGrowth();
?>

<div class="welcome-banner mb-4">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h4 class="fw-bold text-white mb-1">Super Admin Dashboard</h4>
            <p class="text-white-50 mb-0 small">Platform Overview & Metrics</p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= BASE_URL ?>modules/organizations/create.php" class="btn btn-light btn-sm fw-semibold"><i class="bi bi-building-add me-1"></i>New Organization</a>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="stat-card">
            <div class="stat-card-icon" style="background:linear-gradient(135deg,#6366f1,#4f46e5);"><i class="bi bi-building"></i></div>
            <div class="stat-card-info">
                <span class="stat-card-label">Organizations</span>
                <h3 class="stat-card-number"><?= $stats['total_orgs'] ?></h3>
                <span class="stat-card-change text-success"><i class="bi bi-check-circle me-1"></i><?= $stats['active_orgs'] ?> Active</span>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="stat-card">
            <div class="stat-card-icon" style="background:linear-gradient(135deg,#10b981,#059669);"><i class="bi bi-people-fill"></i></div>
            <div class="stat-card-info">
                <span class="stat-card-label">Total Users</span>
                <h3 class="stat-card-number"><?= $stats['total_users'] ?></h3>
                <span class="stat-card-change text-primary"><i class="bi bi-globe me-1"></i>Platform-wide</span>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="stat-card">
            <div class="stat-card-icon" style="background:linear-gradient(135deg,#f59e0b,#d97706);"><i class="bi bi-layers-half"></i></div>
            <div class="stat-card-info">
                <span class="stat-card-label">Total Leads & Deals</span>
                <h3 class="stat-card-number"><?= $stats['total_leads'] ?></h3>
                <span class="stat-card-change text-warning"><i class="bi bi-trophy me-1"></i><?= $stats['total_deals'] ?> Deals</span>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="stat-card">
            <div class="stat-card-icon" style="background:linear-gradient(135deg,#ec4899,#be185d);"><i class="bi bi-cash-stack"></i></div>
            <div class="stat-card-info">
                <span class="stat-card-label">Monthly SaaS Revenue</span>
                <h3 class="stat-card-number"><?= formatCurrency($stats['monthly_revenue']) ?></h3>
                <span class="stat-card-change text-danger"><i class="bi bi-graph-up me-1"></i>This Month</span>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-xl-8">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white border-0 pt-4 pb-0 d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0"><i class="bi bi-graph-up me-2 text-primary"></i>Organization Growth</h6>
                <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill px-3 py-2 small">Last 6 Months</span>
            </div>
            <div class="card-body" style="height:340px;">
                <canvas id="orgGrowthChart"></canvas>
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white border-0 pt-4 pb-0">
                <h6 class="fw-bold mb-0"><i class="bi bi-activity me-2 text-info"></i>Recent Organizations</h6>
            </div>
            <div class="card-body" style="max-height:340px;overflow-y:auto;">
                <?php if (!empty($recentActivities)): ?>
                    <?php foreach ($recentActivities as $org): ?>
                    <div class="activity-item">
                        <div class="activity-icon-wrap">
                            <div class="activity-icon" style="background:#06b6d415;color:#06b6d4;"><i class="bi bi-building"></i></div>
                        </div>
                        <div class="activity-content">
                            <div class="small"><span class="fw-semibold"><?= e($org['name']) ?></span></div>
                            <div class="text-muted" style="font-size:12px;">Registered</div>
                            <div class="text-muted" style="font-size:11px;"><?= timeAgo($org['created_at']) ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center py-4"><span class="text-muted small">No organizations yet</span></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
const monthlyData = <?= json_encode($monthlyGrowth) ?>;
const ctx = document.getElementById('orgGrowthChart');
if (ctx && monthlyData.length > 0) {
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: monthlyData.map(d => d.label),
            datasets: [{
                label: 'New Organizations',
                data: monthlyData.map(d => d.count),
                backgroundColor: '#6366f1',
                borderRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } }
        }
    });
}
</script>
