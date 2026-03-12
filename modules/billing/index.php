<?php
$pageTitle = 'Billing & Revenue';
require_once '../../config/auth.php';
requireLogin();
requireRole(['super_admin']);
require_once '../../config/db.php';

// Auto-create billing_history table if it doesn't exist
$pdo->exec("CREATE TABLE IF NOT EXISTS billing_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    organization_id INT DEFAULT NULL,
    plan_id INT DEFAULT NULL,
    amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    status ENUM('paid','pending','failed') DEFAULT 'pending',
    payment_method VARCHAR(100) DEFAULT NULL,
    transaction_id VARCHAR(255) DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    paid_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_billing_org (organization_id),
    INDEX idx_billing_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Add paid_at column if it doesn't exist (table may have been created before)
try {
    $pdo->exec("ALTER TABLE billing_history ADD COLUMN IF NOT EXISTS paid_at TIMESTAMP NULL DEFAULT NULL");
} catch (PDOException $e) {
    // Column may already exist in older MySQL that doesn't support IF NOT EXISTS
}

// Revenue stats
$totalRevenue = $pdo->query("SELECT COALESCE(SUM(amount),0) FROM billing_history WHERE status='paid'")->fetchColumn();
$monthRevenue = $pdo->query("SELECT COALESCE(SUM(amount),0) FROM billing_history WHERE status='paid' AND MONTH(COALESCE(paid_at, created_at))=MONTH(CURDATE()) AND YEAR(COALESCE(paid_at, created_at))=YEAR(CURDATE())")->fetchColumn();
$pendingRev  = $pdo->query("SELECT COALESCE(SUM(amount),0) FROM billing_history WHERE status='pending'")->fetchColumn();
$failedCount = $pdo->query("SELECT COUNT(*) FROM billing_history WHERE status='failed'")->fetchColumn();

// Billing table (paginated)
$page       = max(1, (int)($_GET['page'] ?? 1));
$limit      = 15;
$offset     = ($page - 1) * $limit;
$totalRows  = $pdo->query("SELECT COUNT(*) FROM billing_history")->fetchColumn();
$totalPages = ceil($totalRows / $limit);

$stmt = $pdo->prepare(
    "SELECT b.*, o.name as org_name, p.name as plan_name
     FROM billing_history b
     LEFT JOIN organizations o ON b.organization_id = o.id
     LEFT JOIN plans p ON b.plan_id = p.id
     ORDER BY b.created_at DESC LIMIT :lim OFFSET :off"
);
$stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
$stmt->bindValue(':off', $offset, PDO::PARAM_INT);
$stmt->execute();
$bills = $stmt->fetchAll();

// Monthly revenue chart data
$monthlyRevData = $pdo->query(
    "SELECT DATE_FORMAT(paid_at,'%Y-%m') as month, DATE_FORMAT(paid_at,'%b %Y') as label, SUM(amount) as total
     FROM billing_history WHERE status='paid' AND paid_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
     GROUP BY month, label ORDER BY month"
)->fetchAll();

include '../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-receipt me-2 text-primary"></i>Billing & Revenue</h4>
        <p class="text-muted small mb-0">Platform-wide billing management</p>
    </div>
</div>

<!-- Stat Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-card-icon" style="background:linear-gradient(135deg,#6366f1,#4f46e5);"><i class="bi bi-cash-stack"></i></div>
            <div class="stat-card-info">
                <span class="stat-card-label">Total Revenue</span>
                <h3 class="stat-card-number"><?= formatCurrency($totalRevenue) ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-card-icon" style="background:linear-gradient(135deg,#10b981,#059669);"><i class="bi bi-graph-up-arrow"></i></div>
            <div class="stat-card-info">
                <span class="stat-card-label">This Month</span>
                <h3 class="stat-card-number"><?= formatCurrency($monthRevenue) ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-card-icon" style="background:linear-gradient(135deg,#f59e0b,#d97706);"><i class="bi bi-hourglass-split"></i></div>
            <div class="stat-card-info">
                <span class="stat-card-label">Pending</span>
                <h3 class="stat-card-number"><?= formatCurrency($pendingRev) ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-card-icon" style="background:linear-gradient(135deg,#ef4444,#dc2626);"><i class="bi bi-x-circle"></i></div>
            <div class="stat-card-info">
                <span class="stat-card-label">Failed Payments</span>
                <h3 class="stat-card-number"><?= $failedCount ?></h3>
            </div>
        </div>
    </div>
</div>

<!-- Revenue Chart -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white border-0 pt-4 pb-0">
        <h6 class="fw-bold mb-0"><i class="bi bi-bar-chart me-2 text-primary"></i>Monthly Revenue</h6>
    </div>
    <div class="card-body" style="height:280px;">
        <canvas id="revenueChart"></canvas>
    </div>
</div>

<!-- Billing Table -->
<div class="card shadow-sm border-0">
    <div class="card-header bg-white border-0 pt-4 pb-3">
        <h6 class="fw-bold mb-0">All Transactions</h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Invoice</th>
                        <th>Organization</th>
                        <th>Plan</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($bills)): ?>
                        <?php foreach ($bills as $b): ?>
                        <tr>
                            <td class="small text-muted">#<?= str_pad($b['id'], 5, '0', STR_PAD_LEFT) ?></td>
                            <td class="small fw-semibold"><?= e($b['org_name'] ?? '—') ?></td>
                            <td><span class="badge bg-primary bg-opacity-10 text-primary"><?= e($b['plan_name'] ?? '—') ?></span></td>
                            <td class="fw-bold">₹<?= number_format($b['amount'], 2) ?></td>
                            <td>
                                <?php $bc = ['paid'=>'success','pending'=>'warning','failed'=>'danger']; ?>
                                <span class="badge bg-<?= $bc[$b['status']] ?? 'secondary' ?>"><?= ucfirst($b['status']) ?></span>
                            </td>
                            <td class="text-muted small"><?= formatDate($b['created_at']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="6" class="text-center py-5 text-muted"><i class="bi bi-receipt fs-1 d-block mb-2"></i>No billing records found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php if ($totalPages > 1): ?>
    <div class="card-footer bg-white border-0 d-flex justify-content-between align-items-center">
        <small class="text-muted">Showing <?= min($offset+1,$totalRows) ?>–<?= min($offset+$limit,$totalRows) ?> of <?= $totalRows ?></small>
        <nav><ul class="pagination pagination-sm mb-0">
            <?php for ($p=1;$p<=$totalPages;$p++): ?>
            <li class="page-item <?= $p==$page?'active':'' ?>"><a class="page-link" href="?page=<?= $p ?>"><?= $p ?></a></li>
            <?php endfor; ?>
        </ul></nav>
    </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
const rd = <?= json_encode($monthlyRevData) ?>;
const rCtx = document.getElementById('revenueChart');
if (rCtx && rd.length) {
    new Chart(rCtx, {
        type: 'bar',
        data: {
            labels: rd.map(d=>d.label),
            datasets: [{ label: 'Revenue (₹)', data: rd.map(d=>d.total), backgroundColor: '#6366f1', borderRadius: 6 }]
        },
        options: { responsive:true, maintainAspectRatio:false, plugins:{legend:{display:false}} }
    });
}
</script>

<?php include '../../includes/footer.php'; ?>
