<?php
$pageTitle = 'Follow-ups';
require_once '../../config/auth.php';
requireLogin();
require_once '../../config/db.php';
require_once '../../models/Followup.php';

$orgId = getOrgId();
$followupModel = new Followup($pdo);

// Handle complete action
if (isset($_GET['complete'])) {
    $id = (int)$_GET['complete'];
    $f = $followupModel->getById($id);
    if ($f && !isAdmin() && $f['user_id'] != getUserId()) {
        redirect('index.php', 'Access denied.', 'danger');
    }
    if ($f) {
        $followupModel->complete($id);
        redirect('index.php', 'Follow-up completed!', 'success');
    }
}

// Handle add followup
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'organization_id' => $orgId,
        'lead_id' => $_POST['lead_id'] ?: null,
        'deal_id' => $_POST['deal_id'] ?: null,
        'user_id' => $_POST['user_id'] ?: getUserId(),
        'title' => trim($_POST['title']),
        'description' => trim($_POST['description'] ?? ''),
        'followup_date' => $_POST['followup_date'],
        'followup_time' => $_POST['followup_time'] ?: null,
        'priority' => $_POST['priority'] ?? 'medium',
    ];
    $followupModel->create($data);
    redirect('index.php', 'Follow-up scheduled!', 'success');
}

$filter = $_GET['filter'] ?? 'today';
$filterMap = [
    'today' => ['status' => 'pending', 'date' => 'today'],
    'upcoming' => ['status' => 'pending', 'date' => 'upcoming'],
    'overdue' => ['status' => 'pending', 'date' => 'overdue'],
    'completed' => ['status' => 'completed'],
    'all' => [],
];
$currentFilter = $filterMap[$filter] ?? [];
$followups = $followupModel->getAll($orgId, $currentFilter, isAdmin() ? null : getUserId());

$overdueCount = $followupModel->getOverdueCount($orgId, isAdmin() ? null : getUserId());
$todayCount = $followupModel->getTodayCount($orgId, isAdmin() ? null : getUserId());

// Get leads and agents for the add form
$leadsSql = "SELECT id, name FROM leads WHERE organization_id = :org";
$leadsParams = ['org' => $orgId];
if (!isAdmin()) {
    $leadsSql .= " AND assigned_to = :uid";
    $leadsParams['uid'] = getUserId();
}
$leadsSql .= " ORDER BY name LIMIT 200";
$leadsStmt = $pdo->prepare($leadsSql);
$leadsStmt->execute($leadsParams);
$leads = $leadsStmt->fetchAll();
$agentsStmt = $pdo->prepare("SELECT id, name FROM users WHERE organization_id = :org AND is_active = 1 ORDER BY name");
$agentsStmt->execute(['org' => $orgId]);
$agents = $agentsStmt->fetchAll();

include '../../includes/header.php';
?>

<!-- Stats -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-card-icon" style="background:linear-gradient(135deg,#f59e0b,#d97706);"><i class="bi bi-calendar-event"></i></div>
            <div class="stat-card-info">
                <span class="stat-card-label">Today</span>
                <h3 class="stat-card-number"><?= $todayCount ?></h3>
                <span class="stat-card-change text-warning"><i class="bi bi-clock me-1"></i>Pending</span>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-card-icon" style="background:linear-gradient(135deg,#ef4444,#dc2626);"><i class="bi bi-exclamation-circle"></i></div>
            <div class="stat-card-info">
                <span class="stat-card-label">Overdue</span>
                <h3 class="stat-card-number"><?= $overdueCount ?></h3>
                <span class="stat-card-change text-danger"><i class="bi bi-exclamation-triangle me-1"></i>Urgent</span>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <!-- Filter Tabs -->
        <ul class="nav nav-pills mb-3">
            <?php foreach (['today'=>'Today','upcoming'=>'Upcoming','overdue'=>'Overdue','completed'=>'Completed','all'=>'All'] as $key => $label): ?>
                <li class="nav-item"><a class="nav-link <?= $filter === $key ? 'active' : '' ?>" href="?filter=<?= $key ?>"><?= $label ?></a></li>
            <?php endforeach; ?>
        </ul>

        <div class="card shadow-sm border-0">
            <div class="card-body">
                <?php if (count($followups) > 0): ?>
                    <?php foreach ($followups as $f): ?>
                    <div class="d-flex align-items-start py-3 border-bottom">
                        <div class="me-3 mt-1">
                            <?php if ($f['status'] === 'completed'): ?>
                                <i class="bi bi-check-circle-fill text-success fs-5"></i>
                            <?php elseif ($f['followup_date'] < date('Y-m-d') && $f['status'] === 'pending'): ?>
                                <i class="bi bi-exclamation-circle-fill text-danger fs-5"></i>
                            <?php else: ?>
                                <i class="bi bi-circle text-<?= $f['priority']==='high'?'danger':($f['priority']==='medium'?'warning':'info') ?> fs-5"></i>
                            <?php endif; ?>
                        </div>
                        <div class="flex-grow-1">
                            <div class="fw-semibold mb-1"><?= e($f['title']) ?></div>
                            <div class="text-muted small mb-1">
                                <?php if ($f['lead_name']): ?><i class="bi bi-person me-1"></i><?= e($f['lead_name']) ?> • <?php endif; ?>
                                <i class="bi bi-calendar me-1"></i><?= formatDate($f['followup_date']) ?>
                                <?php if ($f['followup_time']): ?> at <?= date('h:i A', strtotime($f['followup_time'])) ?><?php endif; ?>
                            </div>
                            <?php if ($f['description']): ?><div class="text-muted small mt-1 mb-2"><?= e($f['description']) ?></div><?php endif; ?>
                            <div class="d-flex align-items-center gap-2 mt-2">
                                <span class="badge rounded-pill bg-light text-dark border px-2 py-1" style="font-size:10px; font-weight:600;">
                                    <i class="bi bi-person-badge text-primary me-1"></i><?= e($f['user_name'] ?? 'Admin') ?>
                                </span>
                                <span class="badge rounded-pill bg-light text-dark border px-2 py-1" style="font-size:10px; font-weight:600; text-transform: uppercase;">
                                    <i class="bi bi-flag-fill <?= $f['priority']==='high'?'text-danger':($f['priority']==='medium'?'text-warning':'text-info') ?> me-1"></i><?= $f['priority'] ?>
                                </span>
                            </div>
                        </div>
                        <?php if ($f['status'] === 'pending'): ?>
                        <a href="?complete=<?= $f['id'] ?>" class="btn btn-sm btn-outline-success ms-2" title="Mark Complete"><i class="bi bi-check-lg"></i></a>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-muted text-center py-4">No follow-ups found for this filter.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Add Follow-up -->
    <div class="col-lg-4">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white border-0 pt-4"><h6 class="fw-bold"><i class="bi bi-plus-circle me-2 text-primary"></i>Schedule Follow-up</h6></div>
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3"><label class="form-label small">Title *</label><input type="text" class="form-control form-control-sm" name="title" required></div>
                    <div class="mb-3"><label class="form-label small">Lead</label><select class="form-select form-select-sm" name="lead_id"><option value="">General</option><?php foreach ($leads as $l): ?><option value="<?= $l['id'] ?>"><?= e($l['name']) ?></option><?php endforeach; ?></select></div>
                    <input type="hidden" name="deal_id" value="">
                    <div class="row g-2 mb-3">
                        <div class="col-7"><label class="form-label small">Date *</label><input type="date" class="form-control form-control-sm" name="followup_date" value="<?= date('Y-m-d') ?>" required></div>
                        <div class="col-5"><label class="form-label small">Time</label><input type="time" class="form-control form-control-sm" name="followup_time"></div>
                    </div>
                    <div class="mb-3"><label class="form-label small">Priority</label><select class="form-select form-select-sm" name="priority"><option value="high">🔴 High</option><option value="medium" selected>🟡 Medium</option><option value="low">🔵 Low</option></select></div>
                    <?php if (isAdmin()): ?>
                    <div class="mb-3"><label class="form-label small">Assign To</label><select class="form-select form-select-sm" name="user_id"><option value="<?= getUserId() ?>">Myself</option><?php foreach ($agents as $a): ?><?php if ($a['id'] != getUserId()): ?><option value="<?= $a['id'] ?>"><?= e($a['name']) ?></option><?php endif; ?><?php endforeach; ?></select></div>
                    <?php else: ?>
                    <input type="hidden" name="user_id" value="<?= getUserId() ?>">
                    <?php endif; ?>
                    <div class="mb-3"><label class="form-label small">Notes</label><textarea class="form-control form-control-sm" name="description" rows="2"></textarea></div>
                    <button type="submit" class="btn btn-primary btn-sm w-100"><i class="bi bi-plus-circle me-1"></i>Schedule</button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php include '../../includes/footer.php'; ?>


