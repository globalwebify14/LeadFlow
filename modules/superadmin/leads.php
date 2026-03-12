<?php
$pageTitle = 'Global Lead Monitoring';
require_once '../../config/auth.php';
requireLogin();
requireRole(['super_admin']);
require_once '../../config/db.php';

$search = trim($_GET['search'] ?? '');
$orgFilter = (int)($_GET['org'] ?? 0);
$statusFilter = $_GET['status'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 20; $offset = ($page - 1) * $limit;

$where = "WHERE 1=1";
$params = [];
if ($search) { $where .= " AND (l.name LIKE :s OR l.phone LIKE :s2)"; $params['s'] = "%$search%"; $params['s2'] = "%$search%"; }
if ($orgFilter) { $where .= " AND l.organization_id = :org"; $params['org'] = $orgFilter; }
if ($statusFilter) { $where .= " AND l.status = :status"; $params['status'] = $statusFilter; }

$totalRows = $pdo->prepare("SELECT COUNT(*) FROM leads l $where");
$totalRows->execute($params);
$totalRows = $totalRows->fetchColumn();
$totalPages = ceil($totalRows / $limit);

$stmt = $pdo->prepare("SELECT l.*, o.name as org_name, u.name as agent_name
    FROM leads l
    LEFT JOIN organizations o ON l.organization_id = o.id
    LEFT JOIN users u ON l.assigned_to = u.id
    $where ORDER BY l.created_at DESC LIMIT :lim OFFSET :off");
$stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
$stmt->bindValue(':off', $offset, PDO::PARAM_INT);
foreach ($params as $k=>$v) $stmt->bindValue(":$k", $v);
$stmt->execute();
$leads = $stmt->fetchAll();

$orgs = $pdo->query("SELECT id, name FROM organizations ORDER BY name")->fetchAll();
$statuses = $pdo->query("SELECT DISTINCT status FROM leads ORDER BY status")->fetchAll(PDO::FETCH_COLUMN);

include '../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-people me-2 text-primary"></i>Lead Monitoring</h4>
        <p class="text-muted small mb-0">Read-only view of all leads across all organizations</p>
    </div>
    <a href="?<?= http_build_query(array_merge($_GET, ['export'=>1])) ?>" class="btn btn-outline-success btn-sm fw-semibold">
        <i class="bi bi-download me-1"></i>Export CSV
    </a>
</div>

<div class="card shadow-sm border-0">
    <div class="card-header bg-white border-0 pt-4 pb-3">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-4">
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" name="search" value="<?= e($search) ?>" class="form-control border-start-0 bg-light" placeholder="Name or phone...">
                </div>
            </div>
            <div class="col-md-3">
                <select name="org" class="form-select bg-light">
                    <option value="">All Organizations</option>
                    <?php foreach ($orgs as $o): ?>
                    <option value="<?= $o['id'] ?>" <?= $orgFilter == $o['id'] ? 'selected' : '' ?>><?= e($o['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select bg-light">
                    <option value="">All Status</option>
                    <?php foreach ($statuses as $st): ?>
                    <option value="<?= $st ?>" <?= $statusFilter === $st ? 'selected' : '' ?>><?= e(ucfirst($st)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-grow-1">Filter</button>
                <a href="<?= BASE_URL ?>modules/superadmin/leads.php" class="btn btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr><th>Lead Name</th><th>Phone</th><th>Organization</th><th>Status</th><th>Agent</th><th>Created</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($leads as $l): ?>
                    <tr>
                        <td><div class="fw-semibold small"><?= e($l['name']) ?></div></td>
                        <td class="small text-muted"><?= e($l['phone']) ?></td>
                        <td><span class="badge bg-primary bg-opacity-10 text-primary small"><?= e($l['org_name'] ?? '—') ?></span></td>
                        <td><span class="badge <?= getStatusBadgeClass($l['status']) ?>"><?= e($l['status']) ?></span></td>
                        <td class="small text-muted"><?= e($l['agent_name'] ?? 'Unassigned') ?></td>
                        <td class="small text-muted"><?= formatDate($l['created_at']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($leads)): ?>
                    <tr><td colspan="6" class="text-center py-5 text-muted"><i class="bi bi-people fs-1 d-block mb-2"></i>No leads found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php if ($totalPages > 1): ?>
    <div class="card-footer bg-white border-0 d-flex justify-content-between align-items-center py-3">
        <small class="text-muted">Showing <?= min($offset+1,$totalRows) ?>–<?= min($offset+$limit,$totalRows) ?> of <?= $totalRows ?> leads</small>
        <nav><ul class="pagination pagination-sm mb-0">
            <?php for($p=1;$p<=$totalPages;$p++): ?><li class="page-item <?= $p==$page?'active':'' ?>"><a class="page-link" href="?<?= http_build_query(array_merge($_GET,['page'=>$p])) ?>"><?= $p ?></a></li><?php endfor; ?>
        </ul></nav>
    </div>
    <?php endif; ?>
</div>

<?php include '../../includes/footer.php'; ?>
