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

<style>
/* ============================================================
   DEALS MODULE — Compact Mobile Cards
   ============================================================ */
@media (max-width: 768px) {
    /* ---- COMPACT CARD REDESIGN ---- */
    .mobile-card-table tr {
        display: flex;
        flex-direction: column;
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 14px !important;
        margin-bottom: 12px !important;
        box-shadow: 0 4px 15px rgba(0,0,0,0.02);
    }

    /* Hide the table header completely */
    .mobile-card-table thead { display: none !important; }

    /* All cells: clean vertical stacking, no borders */
    .mobile-card-table td {
        display: flex !important;
        flex-direction: row !important;
        align-items: center !important;
        text-align: left !important;
        padding: 3px 0 !important;
        min-height: unset !important;
        border-bottom: none !important;
        width: 100% !important;
    }

    /* DEAL NAME cell: compact row */
    .mobile-card-table td[data-label="Deal"] {
        padding: 0 0 8px 0 !important;
        border-bottom: 1px solid rgba(0,0,0,0.05) !important;
        margin-bottom: 6px;
    }
    .mobile-card-table td[data-label="Deal"] a {
        font-size: 15px !important;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 100%;
        color: #0f172a !important;
        font-weight: 700 !important;
    }

    /* VALUE cell: make it prominent */
    .mobile-card-table td[data-label="Value"] { margin-bottom: 4px; }
    .mobile-card-table td[data-label="Value"] .text-success { font-size: 16px !important; font-weight: 800 !important; }

    /* Hide Close Date and Lead text to save space on mobile */
    .mobile-card-table td[data-label="Close Date"],
    .mobile-card-table td[data-label="Lead"] {
        display: none !important;
    }

    /* STAGE, STATUS, AGENT: inline badges row */
    .mobile-card-table td[data-label="Stage"],
    .mobile-card-table td[data-label="Status"],
    .mobile-card-table td[data-label="Agent"] {
        display: inline-flex !important;
        padding: 6px 0 !important;
        margin-right: 8px;
        width: auto !important;
    }
    
    /* Actions pushed to bottom */
    .mobile-card-table td[data-label="Actions"] { 
        justify-content: flex-end; 
        width: 100% !important; 
        margin-top: 8px; 
        border-top: 1px dashed rgba(0,0,0,0.08) !important; 
        padding-top: 14px !important; 
    }
    
    /* Overflow prevention */
    .table-responsive { overflow-x: hidden !important; min-height: auto !important; border: none !important; margin-bottom: 2rem; }
    .table-responsive table { border-collapse: separate; border-spacing: 0; }
}
</style>

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
            <table class="table table-hover align-middle mb-0 text-nowrap mobile-card-table">
                <thead><tr><th>Deal</th><th>Lead</th><th>Value</th><th>Stage</th><th>Status</th><th>Agent</th><th>Close Date</th><th>Actions</th></tr></thead>
                <tbody>
                    <?php foreach ($deals as $deal): ?>
                    <tr>
                        <td data-label="Deal"><a href="<?= BASE_URL ?>modules/deals/view.php?id=<?= $deal['id'] ?>" class="fw-semibold text-dark text-decoration-none"><i class="bi bi-briefcase text-primary me-2 d-inline-block d-md-none"></i><?= e($deal['name']) ?></a></td>
                        <td data-label="Lead" class="small"><?= e($deal['lead_name'] ?: '—') ?></td>
                        <td data-label="Value" class="fw-bold text-success"><?= formatCurrency($deal['value']) ?></td>
                        <td data-label="Stage"><?php if ($deal['stage_name']): ?><span class="badge rounded-pill px-2 py-1" style="background:<?= e($deal['stage_color'] ?? '#6366f1') ?>20;color:<?= e($deal['stage_color'] ?? '#6366f1') ?>;border:1px solid <?= e($deal['stage_color'] ?? '#6366f1') ?>30;"><?= e($deal['stage_name']) ?></span><?php else: ?>—<?php endif; ?></td>
                        <td data-label="Status"><span class="badge bg-<?= $deal['status']==='won'?'success':($deal['status']==='lost'?'danger':'primary') ?> bg-opacity-10 text-<?= $deal['status']==='won'?'success':($deal['status']==='lost'?'danger':'primary') ?>"><?= ucfirst($deal['status']) ?></span></td>
                        <td data-label="Agent" class="small"><?= e($deal['agent_name'] ?: 'Unassigned') ?></td>
                        <td data-label="Close Date" class="small text-muted"><?= $deal['expected_close_date'] ? formatDate($deal['expected_close_date']) : '—' ?></td>
                        <td data-label="Actions">
                            <div class="btn-group btn-group-sm">
                                <a href="<?= BASE_URL ?>modules/deals/view.php?id=<?= $deal['id'] ?>" class="btn btn-outline-primary px-3"><i class="bi bi-eye"></i> View</a>
                                <a href="<?= BASE_URL ?>modules/deals/edit.php?id=<?= $deal['id'] ?>" class="btn btn-outline-secondary px-3"><i class="bi bi-pencil"></i> Edit</a>
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


