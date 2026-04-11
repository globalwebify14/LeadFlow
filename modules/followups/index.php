<?php
$pageTitle = 'Follow-ups';
require_once '../../config/auth.php';
requireLogin();
require_once '../../config/db.php';
require_once '../../models/Followup.php';
require_once '../../models/Lead.php';

$orgId = getOrgId();
$followupModel = new Followup($pdo);
$leadModel = new Lead($pdo);

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['complete_followup'])) {
        $id = (int)$_POST['followup_id'];
        $f = $followupModel->getById($id);
        if ($f && !isAdmin() && $f['user_id'] != getUserId()) {
            redirect('index.php', 'Access denied.', 'danger');
        }
        if ($f) {
            $followupModel->complete($id);
            
            // Add note if provided
            $noteText = trim($_POST['outcome_note'] ?? '');
            if ($noteText && $f['lead_id']) {
                $leadModel->addNote($f['lead_id'], $noteText, getUserId());
            }
            
            // Schedule next followup if requested
            if (!empty($_POST['schedule_next'])) {
                $data = [
                    'organization_id' => $orgId,
                    'lead_id' => $f['lead_id'],
                    'deal_id' => $f['deal_id'],
                    'user_id' => getUserId(),
                    'title' => trim($_POST['next_title']),
                    'description' => '',
                    'followup_date' => $_POST['next_date'],
                    'followup_time' => $_POST['next_time'] ?: null,
                    'priority' => $_POST['next_priority'] ?? 'medium',
                ];
                $followupModel->create($data);
            }
            
            redirect('index.php', 'Follow-up marked as completed!', 'success');
        }
    } else {
        // Handle add followup
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

// Capture custom filters from report dashboard
$userIdFilter = $_GET['user_id'] ?? null;
$dateFrom     = $_GET['date_from'] ?? null;
$dateTo       = $_GET['date_to'] ?? null;
$isReportView = ($userIdFilter || $dateFrom || $dateTo);

if ($isReportView) {
    if ($dateFrom) $currentFilter['date_from'] = $dateFrom;
    if ($dateTo)   $currentFilter['date_to']   = $dateTo;
    // If coming from report, we usually want to see either pending or all, 
    // but the card specifically sent 'date=overdue' which is already in $currentFilter if requested.
}

$followups = $followupModel->getAll($orgId, $currentFilter, $userIdFilter ?: (isAdmin() ? null : getUserId()));

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
    <div class="col-xl-3 col-md-4 col-6">
        <div class="stat-card">
            <div class="stat-card-icon" style="background:linear-gradient(135deg,#f59e0b,#d97706);"><i class="bi bi-calendar-event"></i></div>
            <div class="stat-card-info">
                <span class="stat-card-label">Today</span>
                <h3 class="stat-card-number"><?= $todayCount ?></h3>
                <span class="stat-card-change text-warning"><i class="bi bi-clock me-1"></i>Pending</span>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-4 col-6">
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
    <div class="col-xl-8 col-lg-7">
        <!-- Filter Tabs -->
        <ul class="nav nav-pills mb-3">
            <?php foreach (['today'=>'Today','upcoming'=>'Upcoming','overdue'=>'Overdue','completed'=>'Completed','all'=>'All'] as $key => $label): ?>
                <li class="nav-item"><a class="nav-link <?= $filter === $key ? 'active' : '' ?>" href="?filter=<?= $key ?>"><?= $label ?></a></li>
            <?php endforeach; ?>
        </ul>

        <?php if ($isReportView): ?>
        <div class="alert alert-info border-0 shadow-sm d-flex justify-content-between align-items-center py-2 mb-3" style="border-radius:12px;">
            <div class="small fw-semibold">
                <i class="bi bi-funnel-fill me-1"></i> Showing report results 
                <?php if($dateFrom || $dateTo): ?> for <?= e($dateFrom ?: '...') ?> to <?= e($dateTo ?: '...') ?><?php endif; ?>
                <?php if($userIdFilter): ?> for agent ID: <?= e($userIdFilter) ?><?php endif; ?>
            </div>
            <a href="index.php" class="btn btn-sm btn-info text-white py-0 fs-6" style="border-radius:8px;">Clear</a>
        </div>
        <?php endif; ?>

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
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <div class="fw-bold text-dark" style="font-size:16px; letter-spacing:-0.2px;"><?= e($f['lead_name'] ?? 'General Task') ?></div>
                                <?php if ($f['status'] === 'completed'): ?>
                                    <span class="badge bg-success bg-opacity-10 text-success border-0 px-2 py-1" style="font-size:9px;">COMPLETED</span>
                                <?php endif; ?>
                            </div>
                            <div class="fw-semibold text-secondary mb-2" style="font-size:13.5px; opacity:0.85; line-height:1.4;"><?= e($f['title']) ?></div>
                            
                            <div class="d-flex flex-wrap align-items-center gap-3 text-muted small">
                                <div class="d-flex align-items-center gap-1">
                                    <i class="bi bi-calendar-event" style="color:#6366f1;"></i>
                                    <span style="font-weight:600;"><?= formatDate($f['followup_date']) ?></span>
                                    <?php if ($f['followup_time']): ?>
                                        <span>at <?= date('h:i A', strtotime($f['followup_time'])) ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="d-flex align-items-center gap-1">
                                    <i class="bi bi-person-badge" style="color:#0ea5e9;"></i>
                                    <span style="font-weight:600;"><?= e($f['user_name'] ?? 'Admin') ?></span>
                                </div>
                                <div class="d-flex align-items-center gap-1">
                                    <i class="bi bi-flag-fill <?= $f['priority']==='high'?'text-danger':($f['priority']==='medium'?'text-warning':'text-info') ?>"></i>
                                    <span style="font-weight:700; text-transform:uppercase; font-size:10px;"><?= $f['priority'] ?></span>
                                </div>
                            </div>

                            <?php if ($f['description']): ?>
                                <div class="bg-light rounded-3 p-2 mt-2 border-start border-4 border-light-subtle" style="font-size:12.5px; color:#475569;">
                                    <i class="bi bi-quote text-muted me-1"></i><?= e($f['description']) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <?php if ($f['status'] === 'pending'): ?>
                        <button type="button" class="btn btn-sm btn-outline-success ms-2" title="Mark Complete" onclick="openCompleteFollowupModal(<?= $f['id'] ?>, '<?= e(addslashes($f['title'])) ?>')"><i class="bi bi-check-lg"></i></button>
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
    <div class="col-xl-4 col-lg-5">
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

<!-- Complete Followup Wrapper -->
<!-- ═══════════════ COMPLETE FOLLOW-UP MODAL ═══════════════ -->
<div class="modal fade" id="completeFollowupModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form action="" method="POST" class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <div class="modal-header border-0 pb-0 pt-4 px-4">
                <h5 class="modal-title fw-bold text-success"><i class="bi bi-check-circle-fill me-2"></i>Complete Follow-up</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body px-4 py-3">
                <input type="hidden" name="complete_followup" value="1">
                <input type="hidden" name="followup_id" id="comp_followup_id" value="">
                
                <p class="small text-muted mb-3">Marking <strong id="comp_followup_title" class="text-dark"></strong> as complete.</p>
                
                <div class="mb-4">
                    <label class="form-label fw-semibold small">Interaction Notes (Optional)</label>
                    <textarea class="form-control" name="outcome_note" rows="2" placeholder="What was the result of this follow-up?" style="border-radius:10px;"></textarea>
                </div>
                
                <div class="bg-light rounded-3 p-3 mb-2 border">
                    <div class="form-check form-switch mb-0">
                        <input class="form-check-input" type="checkbox" id="scheduleNextToggle" name="schedule_next" value="1" onchange="document.getElementById('nextFollowupFields').classList.toggle('d-none', !this.checked); document.getElementById('nextModeFDate').required = this.checked; document.getElementById('nextModeFTitle').required = this.checked;">
                        <label class="form-check-label fw-semibold text-dark ms-1" for="scheduleNextToggle">Schedule next Follow-up</label>
                    </div>
                    
                    <div id="nextFollowupFields" class="d-none mt-3">
                        <div class="mb-2">
                            <label class="form-label small fw-semibold text-muted mb-1">Follow-up Topic <span class="text-danger">*</span></label>
                            <input type="text" id="nextModeFTitle" name="next_title" class="form-control form-control-sm" style="border-radius:8px;">
                        </div>
                        <div class="row g-2">
                            <div class="col-md-5">
                                <label class="form-label small fw-semibold text-muted mb-1">Date <span class="text-danger">*</span></label>
                                <input type="date" id="nextModeFDate" name="next_date" class="form-control form-control-sm" style="border-radius:8px;" min="<?= date('Y-m-d') ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold text-muted mb-1">Time</label>
                                <input type="time" name="next_time" class="form-control form-control-sm" style="border-radius:8px;">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold text-muted mb-1">Priority</label>
                                <select class="form-select form-select-sm" name="next_priority" style="border-radius:8px;">
                                    <option value="high">High</option>
                                    <option value="medium" selected>Med</option>
                                    <option value="low">Low</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0 pb-4 px-4">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-success rounded-pill px-4 fw-bold shadow-sm">Mark Complete</button>
            </div>
        </form>
    </div>
</div>

<script>
function openCompleteFollowupModal(id, title) {
    document.getElementById('comp_followup_id').value = id;
    document.getElementById('comp_followup_title').textContent = title;
    
    // Reset fields
    document.getElementById('scheduleNextToggle').checked = false;
    document.getElementById('nextFollowupFields').classList.add('d-none');
    document.getElementById('nextModeFDate').required = false;
    document.getElementById('nextModeFTitle').required = false;
    document.getElementById('nextModeFTitle').value = '';
    
    var modal = new bootstrap.Modal(document.getElementById('completeFollowupModal'));
    modal.show();
}
</script>

<?php include '../../includes/footer.php'; ?>


