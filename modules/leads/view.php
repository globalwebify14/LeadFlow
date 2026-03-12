<?php
$pageTitle = 'Lead Details';
require_once '../../config/auth.php';
requireLogin();
require_once '../../config/db.php';
require_once '../../models/Lead.php';
require_once '../../models/Followup.php';





$orgId = getOrgId();
$leadModel = new Lead($pdo);

if (!isset($_GET['id'])) { redirect(BASE_URL . 'modules/leads/'); }
$lead = $leadModel->getLeadById((int)$_GET['id'], $orgId);
if (!$lead) { redirect(BASE_URL . 'modules/leads/', 'Lead not found.', 'danger'); }

// Handle add note
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_note'])) {
    $note = trim($_POST['note']);
    if ($note) {
        $leadModel->addNote($lead['id'], $note, getUserId());
        redirect(BASE_URL . 'modules/leads/view.php?id=' . $lead['id'], 'Note added!', 'success');
    }
}

// Handle quick status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['quick_status'])) {
    $leadModel->updateStatus($lead['id'], $_POST['quick_status'], '', getUserId());
    redirect(BASE_URL . 'modules/leads/view.php?id=' . $lead['id'], 'Status updated!', 'success');
}

// Handle delete
if (isset($_GET['delete']) && $_GET['delete'] === 'confirm') {
    $leadModel->deleteLead($lead['id']);
    redirect(BASE_URL . 'modules/leads/', 'Lead deleted.', 'success');
}

$activities = $leadModel->getActivities($lead['id']);
$notes = $leadModel->getNotes($lead['id']);
$tags = $leadModel->getTags($lead['id']);

// Get followups for this lead
$followupModel = new Followup($pdo);
$followupsStmt = $pdo->prepare("SELECT * FROM followups WHERE lead_id = :lead AND organization_id = :org ORDER BY followup_date ASC");
$followupsStmt->execute(['lead' => $lead['id'], 'org' => $orgId]);
$followups = $followupsStmt->fetchAll();

// Get deals for this lead
$dealsStmt = $pdo->prepare("SELECT d.*, ps.name as stage_name, ps.color as stage_color FROM deals d LEFT JOIN pipeline_stages ps ON d.stage_id = ps.id WHERE d.lead_id = :lead AND d.organization_id = :org ORDER BY d.id DESC");
$dealsStmt->execute(['lead' => $lead['id'], 'org' => $orgId]);
$deals = $dealsStmt->fetchAll();

// Pipeline stage name
$stageName = null;
if ($lead['pipeline_stage_id']) {
    $stageStmt = $pdo->prepare("SELECT name, color FROM pipeline_stages WHERE id = :id");
    $stageStmt->execute(['id' => $lead['pipeline_stage_id']]);
    $stage = $stageStmt->fetch();
    $stageName = $stage ? $stage : null;
}

include '../../includes/header.php';
?>

<!-- Lead Header -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-body p-4">
        <div class="d-flex justify-content-between align-items-start">
            <div>
                <h4 class="fw-bold mb-1"><?= e($lead['name']) ?></h4>
                <div class="d-flex flex-wrap align-items-center gap-2 mt-2">
                    <span class="badge <?= getStatusBadgeClass($lead['status']) ?> rounded-pill px-3 py-1"><?= e($lead['status']) ?></span>
                    <span class="badge <?= getPriorityBadgeClass($lead['priority'] ?? 'Warm') ?> rounded-pill px-2 py-1"><i class="bi <?= getPriorityIcon($lead['priority'] ?? 'Warm') ?> me-1"></i><?= e($lead['priority'] ?? 'Warm') ?></span>
                    <?php if ($stageName): ?>
                        <span class="badge rounded-pill px-2 py-1" style="background:<?= e($stageName['color']) ?>15;color:<?= e($stageName['color']) ?>;border:1px solid <?= e($stageName['color']) ?>30;"><i class="bi bi-funnel me-1"></i><?= e($stageName['name']) ?></span>
                    <?php endif; ?>
                    <?php foreach ($tags as $tag): ?>
                        <span class="badge rounded-pill px-2 py-1" style="background:<?= e($tag['color']) ?>15;color:<?= e($tag['color']) ?>;border:1px solid <?= e($tag['color']) ?>30;"><?= e($tag['name']) ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="d-flex gap-2">
                <a href="<?= BASE_URL ?>modules/leads/edit.php?id=<?= $lead['id'] ?>" class="btn btn-primary btn-sm"><i class="bi bi-pencil me-1"></i>Edit</a>
                <a href="<?= BASE_URL ?>modules/deals/add.php?lead_id=<?= $lead['id'] ?>" class="btn btn-success btn-sm"><i class="bi bi-trophy me-1"></i>Create Deal</a>
                <a href="?id=<?= $lead['id'] ?>&delete=confirm" class="btn btn-outline-danger btn-sm" onclick="return confirm('Delete this lead?')"><i class="bi bi-trash"></i></a>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <!-- Lead Info -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white border-0 pt-4"><h6 class="fw-bold"><i class="bi bi-person me-2 text-primary"></i>Contact Information</h6></div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6"><div class="p-3 bg-light rounded"><div class="small text-muted mb-1">Phone</div><a href="tel:<?= e($lead['phone']) ?>" class="fw-semibold text-dark text-decoration-none"><i class="bi bi-telephone me-1 text-primary"></i><?= e($lead['phone']) ?></a></div></div>
                    <div class="col-md-6"><div class="p-3 bg-light rounded"><div class="small text-muted mb-1">Email</div><a href="mailto:<?= e($lead['email']) ?>" class="fw-semibold text-dark text-decoration-none"><i class="bi bi-envelope me-1 text-primary"></i><?= e($lead['email'] ?: '—') ?></a></div></div>
                    <div class="col-md-6"><div class="p-3 bg-light rounded"><div class="small text-muted mb-1">Company</div><div class="fw-semibold"><i class="bi bi-building me-1 text-primary"></i><?= e($lead['company'] ?: '—') ?></div></div></div>
                    <div class="col-md-6"><div class="p-3 bg-light rounded"><div class="small text-muted mb-1">Source</div><div class="fw-semibold"><i class="bi bi-diagram-3 me-1 text-primary"></i><?= e($lead['source'] ?: '—') ?></div></div></div>
                    <div class="col-md-6"><div class="p-3 bg-light rounded"><div class="small text-muted mb-1">Assigned To</div><div class="fw-semibold"><i class="bi bi-person-check me-1 text-primary"></i><?= e($lead['agent_name'] ?: 'Unassigned') ?></div></div></div>
                    <div class="col-md-6"><div class="p-3 bg-light rounded"><div class="small text-muted mb-1">Created</div><div class="fw-semibold"><i class="bi bi-calendar me-1 text-primary"></i><?= formatDateTime($lead['created_at']) ?></div></div></div>
                </div>
            </div>
        </div>

        <!-- Meta Form Details -->
        <?php if (!empty($lead['note']) && strpos($lead['note'], '--- Facebook Lead Form Data ---') !== false): ?>
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white border-0 pt-4"><h6 class="fw-bold"><i class="bi bi-ui-checks-grid me-2 text-primary"></i>Submitted Form Details</h6></div>
            <div class="card-body pt-0">
                <div class="row g-0">
                    <?php 
                    $lines = explode("\n", trim(str_replace('--- Facebook Lead Form Data ---', '', $lead['note'])));
                    foreach ($lines as $line):
                        if (trim($line) === '') continue;
                        $parts = explode(':', $line, 2);
                        if (count($parts) === 2):
                    ?>
                    <div class="col-12 border-bottom py-3">
                        <div class="small text-muted mb-1"><?= e(trim($parts[0])) ?></div>
                        <div class="fw-semibold text-dark fs-6"><?= e(trim($parts[1])) ?></div>
                    </div>
                    <?php else: ?>
                    <div class="col-12 border-bottom py-3">
                        <div class="fw-semibold text-dark fs-6"><?= e(trim($line)) ?></div>
                    </div>
                    <?php endif; endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Quick Status Change -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white border-0 pt-4"><h6 class="fw-bold"><i class="bi bi-arrow-repeat me-2 text-info"></i>Quick Status Change</h6></div>
            <div class="card-body">
                <form method="POST" class="d-flex flex-wrap gap-2">
                    <?php foreach (['New Lead','Contacted','Working','Qualified','Follow Up','Done','Rejected'] as $s): ?>
                        <button type="submit" name="quick_status" value="<?= $s ?>" class="btn btn-sm <?= $lead['status'] === $s ? 'btn-primary' : 'btn-outline-secondary' ?>"><?= $s ?></button>
                    <?php endforeach; ?>
                </form>
            </div>
        </div>

        <!-- Linked Deals -->
        <?php if (count($deals) > 0): ?>
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white border-0 pt-4"><h6 class="fw-bold"><i class="bi bi-trophy me-2 text-success"></i>Deals</h6></div>
            <div class="card-body">
                <?php foreach ($deals as $d): ?>
                <div class="d-flex align-items-center py-2 border-bottom">
                    <div class="flex-grow-1">
                        <a href="<?= BASE_URL ?>modules/deals/view.php?id=<?= $d['id'] ?>" class="fw-semibold text-decoration-none"><?= e($d['name']) ?></a>
                        <div class="text-muted small"><?= formatCurrency($d['value']) ?> • <?= ucfirst($d['status']) ?></div>
                    </div>
                    <?php if ($d['stage_name']): ?><span class="badge rounded-pill" style="background:<?= e($d['stage_color']) ?>15;color:<?= e($d['stage_color']) ?>;"><?= e($d['stage_name']) ?></span><?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Notes -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white border-0 pt-4"><h6 class="fw-bold"><i class="bi bi-journal-text me-2 text-warning"></i>Notes</h6></div>
            <div class="card-body">
                <form method="POST" class="mb-3">
                    <div class="d-flex gap-2">
                        <input type="text" class="form-control" name="note" placeholder="Add a note..." required>
                        <button type="submit" name="add_note" value="1" class="btn btn-primary"><i class="bi bi-plus"></i></button>
                    </div>
                </form>
                <?php foreach ($notes as $note): ?>
                <div class="d-flex py-2 border-bottom">
                    <div class="me-2 mt-1"><i class="bi bi-journal text-warning"></i></div>
                    <div class="flex-grow-1">
                        <div class="small"><?= nl2br(e($note['note'])) ?></div>
                        <div class="text-muted" style="font-size:11px;"><?= e($note['user_name'] ?? 'System') ?> • <?= timeAgo($note['created_at']) ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php if (empty($notes)): ?><p class="text-muted text-center small">No notes yet</p><?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Sidebar: Activity Timeline + Follow-ups -->
    <div class="col-lg-4">
        <!-- Follow-ups -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white border-0 pt-4"><h6 class="fw-bold"><i class="bi bi-clock me-2 text-info"></i>Follow-ups</h6></div>
            <div class="card-body">
                <?php foreach ($followups as $f): ?>
                <div class="d-flex align-items-start py-2 border-bottom">
                    <i class="bi bi-<?= $f['status']==='completed'?'check-circle-fill text-success':'clock text-warning' ?> me-2 mt-1"></i>
                    <div>
                        <div class="small fw-semibold"><?= e($f['title']) ?></div>
                        <div class="text-muted" style="font-size:12px;"><?= formatDate($f['followup_date']) ?><?= $f['followup_time'] ? ' at ' . date('h:i A', strtotime($f['followup_time'])) : '' ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php if (empty($followups)): ?><p class="text-muted text-center small">No follow-ups</p><?php endif; ?>
                <a href="<?= BASE_URL ?>modules/followups/" class="btn btn-sm btn-outline-primary w-100 mt-2">Schedule Follow-up</a>
            </div>
        </div>

        <!-- Activity Timeline -->
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white border-0 pt-4"><h6 class="fw-bold"><i class="bi bi-activity me-2 text-primary"></i>Activity</h6></div>
            <div class="card-body" style="max-height:600px;overflow-y:auto;">
                <?php foreach ($activities as $a): ?>
                <div class="d-flex py-2 border-bottom">
                    <div class="me-2 mt-1">
                        <?php
                        $icons = ['status_change'=>'bi-arrow-repeat text-primary','note'=>'bi-journal text-warning','call'=>'bi-telephone text-success','assignment'=>'bi-person-check text-info'];
                        $icon = $icons[$a['activity_type']] ?? 'bi-circle text-muted';
                        ?>
                        <i class="bi <?= $icon ?>"></i>
                    </div>
                    <div class="flex-grow-1">
                        <div class="small"><?= e(truncate($a['description'] ?? '', 80)) ?></div>
                        <div class="text-muted" style="font-size:11px;"><?= e($a['user_name'] ?? 'System') ?> • <?= timeAgo($a['created_at']) ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php if (empty($activities)): ?><p class="text-muted text-center small">No activity yet</p><?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>


