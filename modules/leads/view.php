<?php
$pageTitle = 'Lead Details';
require_once '../../config/auth.php';
requireLogin();
require_once '../../config/db.php';
require_once '../../models/Lead.php';
require_once '../../models/Followup.php';
require_once '../../models/Task.php';

$orgId     = getOrgId();
$leadModel = new Lead($pdo);

if (!isset($_GET['id'])) { redirect(BASE_URL . 'modules/leads/'); }
$lead = $leadModel->getLeadById((int)$_GET['id'], $orgId);
if (!$lead) { redirect(BASE_URL . 'modules/leads/', 'Lead not found.', 'danger'); }

// Handle add task
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_task'])) {
    $taskModel = new Task($pdo);
    $taskModel->createTask([
        'organization_id' => $orgId,
        'lead_id'         => $lead['id'],
        'assigned_to'     => getUserId(),
        'task_title'      => trim($_POST['task_title']),
        'description'     => trim($_POST['description']),
        'due_date'        => $_POST['due_date'],
        'status'          => 'pending'
    ]);
    $leadModel->logActivity($lead['id'], 'note', 'Task created: ' . trim($_POST['task_title']), null, null, getUserId());
    redirect(BASE_URL . 'modules/leads/view.php?id=' . $lead['id'], 'Task added!', 'success');
}

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
$notes      = $leadModel->getNotes($lead['id']);
$tags       = $leadModel->getTags($lead['id']);

$followupModel  = new Followup($pdo);
$followupsStmt  = $pdo->prepare("SELECT * FROM followups WHERE lead_id = :lead AND organization_id = :org ORDER BY followup_date ASC");
$followupsStmt->execute(['lead' => $lead['id'], 'org' => $orgId]);
$followups = $followupsStmt->fetchAll();

$dealsStmt = $pdo->prepare("SELECT d.*, ps.name as stage_name, ps.color as stage_color FROM deals d LEFT JOIN pipeline_stages ps ON d.stage_id = ps.id WHERE d.lead_id = :lead AND d.organization_id = :org ORDER BY d.id DESC");
$dealsStmt->execute(['lead' => $lead['id'], 'org' => $orgId]);
$deals = $dealsStmt->fetchAll();

$stageName = null;
if ($lead['pipeline_stage_id']) {
    $stageStmt = $pdo->prepare("SELECT name, color FROM pipeline_stages WHERE id = :id");
    $stageStmt->execute(['id' => $lead['pipeline_stage_id']]);
    $stage     = $stageStmt->fetch();
    $stageName = $stage ?: null;
}

$taskModel = new Task($pdo);
$leadTasks = $taskModel->getAllTasks($orgId, ['lead_id' => $lead['id']]);
$allReminders = array_merge(
    array_map(function($f) { return array_merge($f, ['type' => 'followup']); }, $followups),
    array_map(function($t) { return array_merge($t, ['type' => 'task', 'followup_date' => $t['due_date'], 'title' => $t['task_title']]); }, $leadTasks)
);
usort($allReminders, function($a, $b) { return strtotime($a['followup_date']) - strtotime($b['followup_date']); });

include '../../includes/header.php';
?>

<style>
/* ═══════════════════════════════════════════════
   LEAD DETAIL PAGE — Premium Redesign
═══════════════════════════════════════════════ */

/* Hero ------------------------------------------------ */
.ld-hero {
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 55%, #1a1040 100%);
    border-radius: 20px;
    padding: 28px 32px;
    margin-bottom: 24px;
    position: relative;
    overflow: hidden;
    border: 1px solid rgba(99,102,241,0.18);
    box-shadow: 0 8px 32px rgba(0,0,0,0.16);
}
.ld-hero::before {
    content: '';
    position: absolute; inset: 0;
    background: radial-gradient(ellipse 500px 250px at 90% 50%, rgba(99,102,241,0.15), transparent);
    pointer-events: none;
}
.ld-hero-grid { position: absolute; inset: 0; background-image: linear-gradient(rgba(255,255,255,0.02) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,0.02) 1px, transparent 1px); background-size: 40px 40px; pointer-events: none; }

.lead-avatar-lg {
    width: 64px; height: 64px; border-radius: 18px; flex-shrink: 0;
    background: linear-gradient(135deg, #6366f1, #4f46e5);
    display: flex; align-items: center; justify-content: center;
    font-size: 22px; font-weight: 800; color: #fff;
    box-shadow: 0 6px 20px rgba(99,102,241,0.4);
}
.ld-name { font-size: 1.6rem; font-weight: 800; color: #fff; margin: 0; letter-spacing: -0.5px; }
.ld-sub  { color: rgba(255,255,255,0.45); font-size: 12px; margin-top: 3px; }

.ld-badge {
    display: inline-flex; align-items: center; gap: 5px;
    font-size: 11px; font-weight: 700; padding: 4px 12px;
    border-radius: 20px; letter-spacing: 0.3px;
}
.ld-badge.status  { background: rgba(255,255,255,0.1); color: #e2e8f0; border: 1px solid rgba(255,255,255,0.15); }
.ld-badge.hot     { background: rgba(239,68,68,0.2);   color: #fca5a5; border: 1px solid rgba(239,68,68,0.3); }
.ld-badge.warm    { background: rgba(245,158,11,0.2);  color: #fcd34d; border: 1px solid rgba(245,158,11,0.3); }
.ld-badge.cold    { background: rgba(59,130,246,0.2);  color: #93c5fd; border: 1px solid rgba(59,130,246,0.3); }

/* Hero action buttons */
.btn-wa    { background: #25d366; color: #fff; border: none; border-radius: 12px; padding: 10px 20px; font-weight: 700; font-size: 13px; display: inline-flex; align-items: center; gap: 7px; transition: all .2s; text-decoration: none; }
.btn-wa:hover { filter: brightness(1.1); transform: translateY(-1px); color: #fff; }
.btn-call  { background: linear-gradient(135deg,#6366f1,#4f46e5); color: #fff; border: none; border-radius: 12px; padding: 10px 20px; font-weight: 700; font-size: 13px; display: inline-flex; align-items: center; gap: 7px; transition: all .2s; text-decoration: none; box-shadow: 0 4px 16px rgba(99,102,241,0.35); }
.btn-call:hover { transform: translateY(-1px); box-shadow: 0 6px 24px rgba(99,102,241,0.45); color: #fff; }
.btn-email { background: rgba(255,255,255,0.08); color: rgba(255,255,255,0.8); border: 1px solid rgba(255,255,255,0.15); border-radius: 12px; padding: 10px 20px; font-weight: 700; font-size: 13px; display: inline-flex; align-items: center; gap: 7px; transition: all .2s; text-decoration: none; backdrop-filter: blur(6px); }
.btn-email:hover { background: rgba(255,255,255,0.14); color: #fff; transform: translateY(-1px); }
.btn-icon-ghost { background: rgba(255,255,255,0.07); color: rgba(255,255,255,0.7); border: 1px solid rgba(255,255,255,0.12); border-radius: 10px; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all .2s; }
.btn-icon-ghost:hover { background: rgba(255,255,255,0.14); color: #fff; }

/* Info cards ----------------------------------------- */
.ld-card {
    background: #fff; border-radius: 18px;
    border: 1px solid #f1f5f9;
    box-shadow: 0 1px 10px rgba(0,0,0,0.04);
    margin-bottom: 20px;
    overflow: hidden;
}
.ld-card-header {
    display: flex; align-items: center; justify-content: space-between;
    padding: 18px 22px 0;
}
.ld-section-title {
    font-size: 13px; font-weight: 700; color: #0f172a;
    display: flex; align-items: center; gap: 8px;
}
.ld-section-title .icon-wrap {
    width: 28px; height: 28px; border-radius: 8px;
    display: flex; align-items: center; justify-content: center; font-size: 13px;
}
.ld-card-body { padding: 16px 22px 20px; }

/* Info grid ------------------------------------------ */
.info-row {
    display: flex; align-items: flex-start; gap: 14px;
    padding: 12px 0; border-bottom: 1px solid #f8fafc;
}
.info-row:last-child { border-bottom: none; }
.info-icon {
    width: 34px; height: 34px; border-radius: 10px; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center; font-size: 14px;
}
.info-label { font-size: 11px; font-weight: 600; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.4px; margin-bottom: 2px; }
.info-value { font-size: 14px; font-weight: 600; color: #0f172a; }
.info-value a { color: #0f172a; text-decoration: none; }
.info-value a:hover { color: #6366f1; }

/* Status pill row ------------------------------------ */
.status-pill-btn {
    padding: 7px 14px; border-radius: 10px; font-size: 12px; font-weight: 600;
    border: 1.5px solid #e2e8f0; background: #fff; color: #64748b;
    cursor: pointer; transition: all .15s; white-space: nowrap;
}
.status-pill-btn:hover  { border-color: #6366f1; color: #6366f1; background: #eef2ff; }
.status-pill-btn.active { border-color: #6366f1; color: #fff; background: linear-gradient(135deg,#6366f1,#4f46e5); box-shadow: 0 3px 12px rgba(99,102,241,0.3); }

/* Notes ---------------------------------------------- */
.note-input {
    border: 1.5px solid #e2e8f0; border-radius: 12px; padding: 12px 16px;
    font-size: 13px; width: 100%; outline: none; resize: none;
    font-family: inherit; transition: border-color .2s; background: #fafafa;
}
.note-input:focus { border-color: #6366f1; background: #fff; box-shadow: 0 0 0 3px rgba(99,102,241,0.08); }
.note-submit { background: linear-gradient(135deg,#6366f1,#4f46e5); color: #fff; border: none; border-radius: 10px; padding: 10px 20px; font-weight: 700; font-size: 13px; cursor: pointer; transition: all .2s; }
.note-submit:hover { transform: translateY(-1px); box-shadow: 0 4px 16px rgba(99,102,241,0.35); }

.note-item { display: flex; gap: 12px; padding: 12px 0; border-bottom: 1px solid #f8fafc; }
.note-item:last-child { border-bottom: none; }
.note-avatar {
    width: 32px; height: 32px; border-radius: 9px; flex-shrink: 0;
    background: linear-gradient(135deg, #8b5cf6, #7c3aed);
    display: flex; align-items: center; justify-content: center;
    font-size: 11px; font-weight: 700; color: #fff;
}
.note-text { font-size: 13px; color: #334155; line-height: 1.5; }
.note-meta { font-size: 11px; color: #94a3b8; margin-top: 4px; }

/* Tasks / Follow-ups --------------------------------- */
.reminder-item { display: flex; align-items: flex-start; gap: 12px; padding: 12px 0; border-bottom: 1px solid #f8fafc; }
.reminder-item:last-child { border-bottom: none; }
.rem-check {
    width: 22px; height: 22px; border-radius: 6px; flex-shrink: 0; margin-top: 1px;
    border: 2px solid #e2e8f0; display: flex; align-items: center; justify-content: center;
    font-size: 12px; cursor: pointer;
}
.rem-check.done { background: #10b981; border-color: #10b981; color: #fff; }
.rem-type-badge { font-size: 10px; font-weight: 700; padding: 2px 8px; border-radius: 6px; }
.rem-due { font-size: 11px; color: #94a3b8; }
.rem-overdue { color: #ef4444 !important; font-weight: 600; }

/* Deals --------------------------------------------- */
.deal-row { display: flex; align-items: center; gap: 14px; padding: 12px 0; border-bottom: 1px solid #f8fafc; }
.deal-row:last-child { border-bottom: none; }
.deal-icon { width: 36px; height: 36px; border-radius: 10px; background: linear-gradient(135deg,#10b981,#059669); display: flex; align-items: center; justify-content: center; color: #fff; font-size: 15px; flex-shrink: 0; }
.deal-name { font-size: 13px; font-weight: 700; color: #0f172a; text-decoration: none; }
.deal-name:hover { color: #6366f1; }
.deal-meta { font-size: 12px; color: #64748b; margin-top: 1px; }
.deal-status-won  { color: #10b981; background: #f0fdf4; font-size: 11px; font-weight: 700; padding: 3px 10px; border-radius: 8px; }
.deal-status-lost { color: #ef4444; background: #fef2f2; font-size: 11px; font-weight: 700; padding: 3px 10px; border-radius: 8px; }
.deal-status-open { color: #6366f1; background: #eef2ff; font-size: 11px; font-weight: 700; padding: 3px 10px; border-radius: 8px; }

/* Tags ---------------------------------------------- */
.tag-chip {
    display: inline-flex; align-items: center; gap: 5px;
    font-size: 12px; font-weight: 600; padding: 5px 12px; border-radius: 20px;
}

/* Timeline ------------------------------------------ */
.tl-wrap { position: relative; }
.tl-wrap::before { content: ''; position: absolute; left: 15px; top: 10px; bottom: 10px; width: 2px; background: linear-gradient(to bottom, #e2e8f0, transparent); }
.tl-item { display: flex; gap: 14px; padding: 10px 0; position: relative; z-index: 1; }
.tl-item + .tl-item { border-top: 1px solid #f8fafc; }
.tl-dot {
    width: 32px; height: 32px; border-radius: 10px; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center; font-size: 13px;
}
.tl-desc { font-size: 13px; font-weight: 500; color: #334155; line-height: 1.4; }
.tl-meta { font-size: 11px; color: #94a3b8; margin-top: 3px; }

/* Meta form data ------------------------------------ */
.fb-field { padding: 12px 0; border-bottom: 1px solid #f8fafc; }
.fb-field:last-child { border-bottom: none; }
.fb-label { font-size: 11px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 3px; }
.fb-value { font-size: 14px; font-weight: 600; color: #0f172a; }

/* Scrollable sidebar section */
.scroll-y { overflow-y: auto; max-height: 420px; }
.scroll-y::-webkit-scrollbar { width: 4px; }
.scroll-y::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 99px; }

/* Back link */
.back-link { display: inline-flex; align-items: center; gap: 6px; font-size: 13px; font-weight: 600; color: rgba(255,255,255,0.55); text-decoration: none; margin-bottom: 16px; transition: color .2s; }
.back-link:hover { color: #fff; }

/* Add note + task btn */
.btn-add-floating {
    background: linear-gradient(135deg,#6366f1,#4f46e5);
    color: #fff; border: none; border-radius: 10px;
    padding: 6px 14px; font-size: 12px; font-weight: 700;
    display: inline-flex; align-items: center; gap: 5px; cursor: pointer; transition: all .2s;
}
.btn-add-floating:hover { transform: translateY(-1px); box-shadow: 0 4px 14px rgba(99,102,241,0.3); }
</style>

<!-- ═══════════════ BACK NAVIGATION ═══════════════ -->
<a href="<?= BASE_URL ?>modules/leads/" class="back-link">
    <i class="bi bi-arrow-left"></i> Back to Leads
</a>

<!-- ═══════════════ HERO HEADER ═══════════════ -->
<div class="ld-hero">
    <div class="ld-hero-grid"></div>
    <div style="position:relative;z-index:1;">

        <!-- Top row: Avatar + Name + Actions -->
        <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap">
            <div class="d-flex align-items-center gap-4">
                <div class="lead-avatar-lg"><?= strtoupper(substr($lead['name'], 0, 1)) ?></div>
                <div>
                    <h2 class="ld-name"><?= e($lead['name']) ?></h2>
                    <div class="ld-sub">
                        <?= e($lead['company'] ?: '') ?>
                        <?php if ($lead['company'] && $lead['source']): ?>&nbsp;·&nbsp;<?php endif; ?>
                        <?= e($lead['source'] ?: '') ?>
                    </div>
                    <!-- Badges row -->
                    <div class="d-flex flex-wrap gap-2 mt-2">
                        <span class="ld-badge status"><i class="bi bi-circle-fill" style="font-size:7px;"></i> <?= e($lead['status']) ?></span>
                        <?php
                            $pr = $lead['priority'] ?? 'Warm';
                            $prClass = strtolower($pr);
                        ?>
                        <span class="ld-badge <?= $prClass ?>">
                            <?php if ($pr === 'Hot'): ?><i class="bi bi-fire"></i><?php elseif ($pr === 'Cold'): ?><i class="bi bi-snow2"></i><?php else: ?><i class="bi bi-thermometer-half"></i><?php endif; ?>
                            <?= e($pr) ?> Priority
                        </span>
                        <?php if ($stageName): ?>
                        <span class="ld-badge status"><i class="bi bi-funnel-fill" style="font-size:9px;"></i> <?= e($stageName['name']) ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Action buttons -->
            <div class="d-flex flex-wrap gap-2 align-items-center">
                <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $lead['phone']) ?>" target="_blank" class="btn-wa">
                    <i class="bi bi-whatsapp"></i> WhatsApp
                </a>
                <a href="tel:<?= e($lead['phone']) ?>" class="btn-call">
                    <i class="bi bi-telephone-fill"></i> Call
                </a>
                <?php if ($lead['email']): ?>
                <a href="mailto:<?= e($lead['email']) ?>" class="btn-email">
                    <i class="bi bi-envelope-fill"></i> Email
                </a>
                <?php endif; ?>
                <!-- More dropdown -->
                <div class="dropdown">
                    <button class="btn-icon-ghost dropdown-toggle-none" type="button" data-bs-toggle="dropdown" style="background:rgba(255,255,255,0.07);color:rgba(255,255,255,0.7);border:1px solid rgba(255,255,255,0.12);border-radius:10px;width:40px;height:40px;display:flex;align-items:center;justify-content:center;cursor:pointer;">
                        <i class="bi bi-three-dots-vertical"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0" style="border-radius:14px;padding:8px;">
                        <li><a class="dropdown-item rounded-2 py-2" href="<?= BASE_URL ?>modules/leads/edit.php?id=<?= $lead['id'] ?>"><i class="bi bi-pencil-square me-2 text-primary"></i>Edit Lead</a></li>
                        <li><a class="dropdown-item rounded-2 py-2" href="<?= BASE_URL ?>modules/deals/add.php?lead_id=<?= $lead['id'] ?>"><i class="bi bi-trophy me-2 text-success"></i>Create Deal</a></li>
                        <li><hr class="dropdown-divider my-1"></li>
                        <li><a class="dropdown-item rounded-2 py-2 text-danger" href="?id=<?= $lead['id'] ?>&delete=confirm" onclick="return confirm('Permanently delete this lead?')"><i class="bi bi-trash me-2"></i>Delete Lead</a></li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Status quick-change pill row -->
        <div class="mt-4 pt-3" style="border-top:1px solid rgba(255,255,255,0.08);">
            <div style="font-size:11px;font-weight:600;color:rgba(255,255,255,0.4);text-transform:uppercase;letter-spacing:0.5px;margin-bottom:10px;">Quick Status</div>
            <form method="POST" class="d-flex flex-wrap gap-2">
                <?php foreach (['New Lead','Contacted','Working','Qualified','Follow Up','Done','Rejected'] as $s): ?>
                <button type="submit" name="quick_status" value="<?= $s ?>"
                    class="status-pill-btn <?= $lead['status'] === $s ? 'active' : '' ?>">
                    <?= $s ?>
                </button>
                <?php endforeach; ?>
            </form>
        </div>
    </div>
</div>

<!-- ═══════════════ MAIN CONTENT — 2 COLUMN ═══════════════ -->
<div class="row g-4">

    <!-- LEFT: Main content -->
    <div class="col-xl-8">

        <!-- ── Contact Information ── -->
        <div class="ld-card">
            <div class="ld-card-header">
                <div class="ld-section-title">
                    <div class="icon-wrap" style="background:#eef2ff;color:#6366f1;"><i class="bi bi-person-vcard-fill"></i></div>
                    Contact Information
                </div>
                <a href="<?= BASE_URL ?>modules/leads/edit.php?id=<?= $lead['id'] ?>" style="font-size:12px;font-weight:700;color:#6366f1;text-decoration:none;"><i class="bi bi-pencil me-1"></i>Edit</a>
            </div>
            <div class="ld-card-body">
                <div class="info-row">
                    <div class="info-icon" style="background:#eef2ff;color:#6366f1;"><i class="bi bi-telephone-fill"></i></div>
                    <div>
                        <div class="info-label">Phone</div>
                        <div class="info-value"><a href="tel:<?= e($lead['phone']) ?>"><?= e($lead['phone']) ?></a></div>
                    </div>
                </div>
                <div class="info-row">
                    <div class="info-icon" style="background:#f0fdf4;color:#10b981;"><i class="bi bi-envelope-fill"></i></div>
                    <div>
                        <div class="info-label">Email</div>
                        <div class="info-value"><a href="mailto:<?= e($lead['email']) ?>"><?= e($lead['email'] ?: '—') ?></a></div>
                    </div>
                </div>
                <div class="info-row">
                    <div class="info-icon" style="background:#fff7ed;color:#f97316;"><i class="bi bi-building-fill"></i></div>
                    <div>
                        <div class="info-label">Company</div>
                        <div class="info-value"><?= e($lead['company'] ?: '—') ?></div>
                    </div>
                </div>
                <div class="info-row">
                    <div class="info-icon" style="background:#fdf4ff;color:#a855f7;"><i class="bi bi-diagram-3-fill"></i></div>
                    <div>
                        <div class="info-label">Source</div>
                        <div class="info-value"><?= e($lead['source'] ?: '—') ?></div>
                    </div>
                </div>
                <div class="info-row">
                    <div class="info-icon" style="background:#eff6ff;color:#3b82f6;"><i class="bi bi-person-check-fill"></i></div>
                    <div>
                        <div class="info-label">Assigned To</div>
                        <div class="info-value"><?= e($lead['agent_name'] ?: 'Unassigned') ?></div>
                    </div>
                </div>
                <div class="info-row">
                    <div class="info-icon" style="background:#f8fafc;color:#64748b;"><i class="bi bi-calendar-check-fill"></i></div>
                    <div>
                        <div class="info-label">Created</div>
                        <div class="info-value"><?= formatDateTime($lead['created_at']) ?></div>
                    </div>
                </div>
                <?php if (!empty($lead['meta_campaign'])): ?>
                <div class="info-row">
                    <div class="info-icon" style="background:#eff6ff;color:#1877f2;"><i class="bi bi-facebook"></i></div>
                    <div>
                        <div class="info-label">Meta Campaign</div>
                        <div class="info-value"><?= e($lead['meta_campaign']) ?></div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- ── Facebook Form Data ── -->
        <?php
        $hasFbData = !empty($lead['note']) && strpos($lead['note'], '--- Facebook Lead Form Data ---') !== false;
        if ($hasFbData):
            $lines = explode("\n", trim(str_replace('--- Facebook Lead Form Data ---', '', $lead['note'])));
        ?>
        <div class="ld-card">
            <div class="ld-card-header">
                <div class="ld-section-title">
                    <div class="icon-wrap" style="background:#eff6ff;color:#1877f2;"><i class="bi bi-facebook"></i></div>
                    Facebook Form Submission
                </div>
            </div>
            <div class="ld-card-body">
                <?php foreach ($lines as $line):
                    if (trim($line) === '') continue;
                    $parts = explode(':', $line, 2);
                ?>
                <div class="fb-field">
                    <?php if (count($parts) === 2): ?>
                    <div class="fb-label"><?= e(trim($parts[0])) ?></div>
                    <div class="fb-value"><?= e(trim($parts[1])) ?></div>
                    <?php else: ?>
                    <div class="fb-value"><?= e(trim($line)) ?></div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- ── Notes ── -->
        <div class="ld-card">
            <div class="ld-card-header">
                <div class="ld-section-title">
                    <div class="icon-wrap" style="background:#fffbeb;color:#f59e0b;"><i class="bi bi-journal-text"></i></div>
                    Notes <span style="background:#f1f5f9;color:#64748b;font-size:10px;font-weight:700;padding:2px 8px;border-radius:20px;margin-left:6px;"><?= count($notes) ?></span>
                </div>
            </div>
            <div class="ld-card-body">
                <!-- Add note form -->
                <form method="POST" class="mb-4">
                    <textarea class="note-input" name="note" rows="2" placeholder="Write a note about this lead..." required></textarea>
                    <div class="d-flex justify-content-end mt-2">
                        <button type="submit" name="add_note" value="1" class="note-submit">
                            <i class="bi bi-plus-circle-fill me-1"></i> Add Note
                        </button>
                    </div>
                </form>
                <!-- Notes list -->
                <?php if (!empty($notes)): ?>
                    <?php foreach ($notes as $n): ?>
                    <div class="note-item">
                        <div class="note-avatar"><?= strtoupper(substr($n['user_name'] ?? 'S', 0, 1)) ?></div>
                        <div class="flex-grow-1">
                            <div class="note-text"><?= nl2br(e($n['note'])) ?></div>
                            <div class="note-meta"><strong><?= e($n['user_name'] ?? 'System') ?></strong> &bull; <?= timeAgo($n['created_at']) ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                <div class="text-center py-4">
                    <i class="bi bi-journal-x" style="font-size:2rem;color:#e2e8f0;display:block;margin-bottom:8px;"></i>
                    <span style="font-size:13px;color:#94a3b8;">No notes yet — add the first one above</span>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- ── Activity Timeline ── -->
        <div class="ld-card">
            <div class="ld-card-header">
                <div class="ld-section-title">
                    <div class="icon-wrap" style="background:#eff6ff;color:#3b82f6;"><i class="bi bi-activity"></i></div>
                    Activity Timeline <span style="background:#f1f5f9;color:#64748b;font-size:10px;font-weight:700;padding:2px 8px;border-radius:20px;margin-left:6px;"><?= count($activities) ?></span>
                </div>
            </div>
            <div class="ld-card-body">
                <?php if (!empty($activities)):
                    $tlStyles = [
                        'status_change' => ['bg' => '#eef2ff', 'color' => '#6366f1', 'icon' => 'bi-arrow-repeat'],
                        'note'          => ['bg' => '#fffbeb', 'color' => '#f59e0b', 'icon' => 'bi-journal-text'],
                        'call'          => ['bg' => '#f0fdf4', 'color' => '#10b981', 'icon' => 'bi-telephone-fill'],
                        'email'         => ['bg' => '#eff6ff', 'color' => '#3b82f6', 'icon' => 'bi-envelope-fill'],
                        'assignment'    => ['bg' => '#fdf4ff', 'color' => '#a855f7', 'icon' => 'bi-person-check-fill'],
                        'deal_created'  => ['bg' => '#f0fdf4', 'color' => '#059669', 'icon' => 'bi-trophy-fill'],
                    ];
                ?>
                <div class="tl-wrap">
                    <?php foreach ($activities as $a):
                        $ts = $tlStyles[$a['activity_type']] ?? ['bg' => '#f8fafc', 'color' => '#64748b', 'icon' => 'bi-dot'];
                    ?>
                    <div class="tl-item">
                        <div class="tl-dot" style="background:<?= $ts['bg'] ?>;color:<?= $ts['color'] ?>;">
                            <i class="bi <?= $ts['icon'] ?>"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="tl-desc"><?= e($a['description']) ?></div>
                            <div class="tl-meta"><strong><?= e($a['user_name'] ?? 'System') ?></strong> &bull; <?= timeAgo($a['created_at']) ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div class="text-center py-4">
                    <i class="bi bi-clock-history" style="font-size:2rem;color:#e2e8f0;display:block;margin-bottom:8px;"></i>
                    <span style="font-size:13px;color:#94a3b8;">No activities recorded yet</span>
                </div>
                <?php endif; ?>
            </div>
        </div>

    </div><!-- /col-xl-8 -->

    <!-- RIGHT: Sidebar -->
    <div class="col-xl-4">

        <!-- ── Tags ── -->
        <div class="ld-card">
            <div class="ld-card-header">
                <div class="ld-section-title">
                    <div class="icon-wrap" style="background:#f0fdf4;color:#10b981;"><i class="bi bi-tags-fill"></i></div>
                    Tags
                </div>
                <button type="button" class="btn-add-floating" data-bs-toggle="modal" data-bs-target="#editTagsModal">
                    <i class="bi bi-pencil"></i> Manage
                </button>
            </div>
            <div class="ld-card-body">
                <?php if (!empty($tags)): ?>
                <div class="d-flex flex-wrap gap-2">
                    <?php foreach ($tags as $tag): ?>
                    <span class="tag-chip" style="background:<?= e($tag['color']) ?>18;color:<?= e($tag['color']) ?>;border:1px solid <?= e($tag['color']) ?>35;">
                        <i class="bi bi-circle-fill" style="font-size:6px;"></i><?= e($tag['name']) ?>
                    </span>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div class="text-center py-3">
                    <span style="font-size:13px;color:#94a3b8;">No tags assigned</span>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- ── Tasks & Follow-ups ── -->
        <div class="ld-card">
            <div class="ld-card-header">
                <div class="ld-section-title">
                    <div class="icon-wrap" style="background:#fdf4ff;color:#a855f7;"><i class="bi bi-check2-square"></i></div>
                    Tasks & Follow-ups <span style="background:#f1f5f9;color:#64748b;font-size:10px;font-weight:700;padding:2px 8px;border-radius:20px;margin-left:6px;"><?= count($allReminders) ?></span>
                </div>
                <button type="button" class="btn-add-floating" data-bs-toggle="modal" data-bs-target="#addTaskModal">
                    <i class="bi bi-plus"></i> Add
                </button>
            </div>
            <div class="ld-card-body">
                <?php if (!empty($allReminders)): ?>
                    <?php foreach ($allReminders as $r):
                        $isDone = in_array($r['status'] ?? '', ['completed', 'Done']);
                        $isOverdue = !$isDone && strtotime($r['followup_date']) < strtotime('today');
                        $typeColor = $r['type'] === 'followup' ? '#06b6d4' : '#a855f7';
                        $typeBg    = $r['type'] === 'followup' ? '#ecfeff' : '#fdf4ff';
                    ?>
                    <div class="reminder-item">
                        <div class="rem-check <?= $isDone ? 'done' : '' ?>">
                            <?php if ($isDone): ?><i class="bi bi-check"></i><?php endif; ?>
                        </div>
                        <div class="flex-grow-1">
                            <div style="font-size:13px;font-weight:600;color:<?= $isDone ? '#94a3b8' : '#0f172a' ?>;<?= $isDone ? 'text-decoration:line-through;' : '' ?>">
                                <?= e($r['title']) ?>
                            </div>
                            <div class="d-flex align-items-center gap-2 mt-1">
                                <span class="rem-type-badge" style="background:<?= $typeBg ?>;color:<?= $typeColor ?>;"><?= ucfirst($r['type']) ?></span>
                                <span class="rem-due <?= $isOverdue ? 'rem-overdue' : '' ?>">
                                    <?php if ($isOverdue): ?><i class="bi bi-exclamation-triangle-fill me-1"></i><?php endif; ?>
                                    <?= formatDate($r['followup_date']) ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                <div class="text-center py-4">
                    <i class="bi bi-calendar-check" style="font-size:2rem;color:#e2e8f0;display:block;margin-bottom:8px;"></i>
                    <span style="font-size:13px;color:#94a3b8;">No tasks or follow-ups yet</span>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- ── Linked Deals ── -->
        <?php if (!empty($deals)): ?>
        <div class="ld-card">
            <div class="ld-card-header">
                <div class="ld-section-title">
                    <div class="icon-wrap" style="background:#f0fdf4;color:#10b981;"><i class="bi bi-trophy-fill"></i></div>
                    Deals <span style="background:#f1f5f9;color:#64748b;font-size:10px;font-weight:700;padding:2px 8px;border-radius:20px;margin-left:6px;"><?= count($deals) ?></span>
                </div>
                <a href="<?= BASE_URL ?>modules/deals/add.php?lead_id=<?= $lead['id'] ?>" class="btn-add-floating"><i class="bi bi-plus"></i> New</a>
            </div>
            <div class="ld-card-body">
                <?php foreach ($deals as $d): ?>
                <div class="deal-row">
                    <div class="deal-icon"><i class="bi bi-trophy-fill"></i></div>
                    <div class="flex-grow-1">
                        <a href="<?= BASE_URL ?>modules/deals/view.php?id=<?= $d['id'] ?>" class="deal-name"><?= e($d['name']) ?></a>
                        <div class="deal-meta"><?= formatCurrency($d['value']) ?><?php if ($d['stage_name']): ?> &bull; <?= e($d['stage_name']) ?><?php endif; ?></div>
                    </div>
                    <span class="deal-status-<?= $d['status'] ?>"><?= ucfirst($d['status']) ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php else: ?>
        <div class="ld-card">
            <div class="ld-card-header">
                <div class="ld-section-title">
                    <div class="icon-wrap" style="background:#f0fdf4;color:#10b981;"><i class="bi bi-trophy-fill"></i></div>
                    Deals
                </div>
                <a href="<?= BASE_URL ?>modules/deals/add.php?lead_id=<?= $lead['id'] ?>" class="btn-add-floating"><i class="bi bi-plus"></i> Create</a>
            </div>
            <div class="ld-card-body text-center py-3">
                <span style="font-size:13px;color:#94a3b8;">No deals linked yet</span>
            </div>
        </div>
        <?php endif; ?>

    </div><!-- /col-xl-4 -->

</div><!-- /row -->

<!-- ═══════════════ ADD TASK MODAL ═══════════════ -->
<div class="modal fade" id="addTaskModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form action="" method="POST" class="modal-content" style="border-radius:20px;border:none;">
            <div class="modal-header border-0 pb-0 pt-4 px-4">
                <h5 class="modal-title fw-bold" style="font-size:1.1rem;">Add Task</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body px-4 py-3">
                <input type="hidden" name="add_task" value="1">
                <div class="mb-3">
                    <label class="form-label fw-semibold" style="font-size:13px;">Task Title</label>
                    <input type="text" class="form-control" name="task_title" placeholder="What needs to be done?" required style="border-radius:10px;font-size:13px;padding:10px 14px;">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold" style="font-size:13px;">Due Date</label>
                    <input type="date" class="form-control" name="due_date" value="<?= date('Y-m-d') ?>" required style="border-radius:10px;font-size:13px;padding:10px 14px;">
                </div>
                <div class="mb-1">
                    <label class="form-label fw-semibold" style="font-size:13px;">Description <span style="color:#94a3b8;font-weight:400;">(optional)</span></label>
                    <textarea class="form-control" name="description" rows="2" style="border-radius:10px;font-size:13px;padding:10px 14px;"></textarea>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0 pb-4 px-4">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold" style="background:linear-gradient(135deg,#6366f1,#4f46e5);border:none;">Save Task</button>
            </div>
        </form>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
