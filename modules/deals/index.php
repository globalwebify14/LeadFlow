<?php
$pageTitle = 'Deals';
require_once '../../config/auth.php';
requireLogin();
require_once '../../config/db.php';
require_once '../../models/Deal.php';
require_once '../../models/User.php';


$orgId = getOrgId();
$dealModel = new Deal($pdo);
$userModel = new User($pdo);

$filters = [
    'search' => $_GET['search'] ?? '',
    'status' => $_GET['status'] ?? '',
    'stage_id' => $_GET['stage_id'] ?? '',
    'assigned_to' => $_GET['assigned_to'] ?? '',
];

if (getUserRole() === 'agent') {
    $filters['enforce_assigned_to'] = getUserId();
}
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 15;
$offset = ($page - 1) * $limit;

$deals = $dealModel->getAllDeals($orgId, $filters, $limit, $offset);
$totalDeals = $dealModel->getTotalCount($orgId, $filters);
$totalPages = ceil($totalDeals / $limit);
$agents = $userModel->getAgents($orgId);
$agentId = (getUserRole() === 'agent') ? getUserId() : null;
$revenueStats = $dealModel->getRevenueStats($orgId, $agentId);

// Pipeline stages for filter
$stagesStmt = $pdo->prepare("SELECT id, name FROM pipeline_stages WHERE organization_id = :org ORDER BY position");
$stagesStmt->execute(['org' => $orgId]);
$stages = $stagesStmt->fetchAll();

include '../../includes/header.php';
?>

<!-- Revenue Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-card-icon" style="background:linear-gradient(135deg,#10b981,#059669);"><i class="bi bi-currency-rupee"></i></div>
            <div class="stat-card-info">
                <span class="stat-card-label">Won Revenue</span>
                <h3 class="stat-card-number"><?= formatCurrency($revenueStats['won_revenue']) ?></h3>
                <span class="stat-card-change text-success"><i class="bi bi-trophy-fill me-1"></i>Closed</span>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-card-icon" style="background:linear-gradient(135deg,#6366f1,#4f46e5);"><i class="bi bi-graph-up-arrow"></i></div>
            <div class="stat-card-info">
                <span class="stat-card-label">Pipeline Value</span>
                <h3 class="stat-card-number"><?= formatCurrency($revenueStats['pipeline_value']) ?></h3>
                <span class="stat-card-change text-primary"><i class="bi bi-funnel-fill me-1"></i>Open</span>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-card-icon" style="background:linear-gradient(135deg,#f59e0b,#d97706);"><i class="bi bi-bullseye"></i></div>
            <div class="stat-card-info">
                <span class="stat-card-label">Win Rate</span>
                <h3 class="stat-card-number"><?= $revenueStats['win_rate'] ?>%</h3>
                <span class="stat-card-change text-warning"><i class="bi bi-graph-up-arrow me-1"></i>Ratio</span>
            </div>
        </div>
    </div>
</div>

<!-- Deals Table -->
<div class="card shadow-sm border-0">
    <div class="card-header bg-white border-0 pt-4 d-flex justify-content-between">
        <h6 class="fw-bold mb-0"><i class="bi bi-trophy me-2 text-primary"></i>Deals <span class="badge bg-primary bg-opacity-10 text-primary ms-2"><?= $totalDeals ?></span></h6>
        <a href="<?= BASE_URL ?>modules/deals/add.php" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>New Deal</a>
    </div>
    <div class="card-body">
        <form method="GET" class="row g-2 mb-3">
            <div class="col-md-3"><input type="text" class="form-control form-control-sm" name="search" placeholder="Search..." value="<?= e($filters['search']) ?>"></div>
            <div class="col-md-2">
                <select class="form-select form-select-sm" name="status">
                    <option value="">All Status</option>
                    <option value="open" <?= $filters['status']==='open'?'selected':'' ?>>Open</option>
                    <option value="won" <?= $filters['status']==='won'?'selected':'' ?>>Won</option>
                    <option value="lost" <?= $filters['status']==='lost'?'selected':'' ?>>Lost</option>
                </select>
            </div>
            <div class="col-md-2">
                <select class="form-select form-select-sm" name="stage_id">
                    <option value="">All Stages</option>
                    <?php foreach ($stages as $s): ?>
                        <option value="<?= $s['id'] ?>" <?= $filters['stage_id']==$s['id']?'selected':'' ?>><?= e($s['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2"><button class="btn btn-primary btn-sm w-100"><i class="bi bi-funnel me-1"></i>Filter</button></div>
        </form>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 text-nowrap">
                <thead><tr><th>Deal</th><th>Lead</th><th>Value</th><th>Stage</th><th>Status</th><th>Agent</th><th>Close Date</th><th>Actions</th></tr></thead>
                <tbody>
                    <?php foreach ($deals as $deal): ?>
                    <tr>
                        <td><a href="<?= BASE_URL ?>modules/deals/view.php?id=<?= $deal['id'] ?>" class="fw-semibold text-dark text-decoration-none"><?= e($deal['name']) ?></a></td>
                        <td class="small"><?= e($deal['lead_name'] ?: '—') ?></td>
                        <td class="fw-bold text-success"><?= formatCurrency($deal['value']) ?></td>
                        <td><?php if ($deal['stage_name']): ?><span class="badge rounded-pill px-2 py-1" style="background:<?= e($deal['stage_color'] ?? '#6366f1') ?>20;color:<?= e($deal['stage_color'] ?? '#6366f1') ?>;border:1px solid <?= e($deal['stage_color'] ?? '#6366f1') ?>30;"><?= e($deal['stage_name']) ?></span><?php else: ?>—<?php endif; ?></td>
                        <td><span class="badge bg-<?= $deal['status']==='won'?'success':($deal['status']==='lost'?'danger':'primary') ?> bg-opacity-10 text-<?= $deal['status']==='won'?'success':($deal['status']==='lost'?'danger':'primary') ?>"><?= ucfirst($deal['status']) ?></span></td>
                        <td class="small"><?= e($deal['agent_name'] ?: 'Unassigned') ?></td>
                        <td class="small text-muted"><?= $deal['expected_close_date'] ? formatDate($deal['expected_close_date']) : '—' ?></td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="<?= BASE_URL ?>modules/deals/view.php?id=<?= $deal['id'] ?>" class="btn btn-outline-primary"><i class="bi bi-eye"></i></a>
                                <a href="<?= BASE_URL ?>modules/deals/edit.php?id=<?= $deal['id'] ?>" class="btn btn-outline-secondary"><i class="bi bi-pencil"></i></a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($deals)): ?>
                    <tr><td colspan="8" class="text-center text-muted py-4">No deals yet. <a href="<?= BASE_URL ?>modules/deals/add.php">Create your first deal</a></td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>


