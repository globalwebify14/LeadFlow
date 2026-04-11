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

// Security: Agent can only view their own leads
if (getUserRole() === 'agent' && $lead['assigned_to'] != getUserId()) {
    redirect(BASE_URL . 'modules/leads/', 'Access denied. You can only view leads assigned to you.', 'danger');
}

// Handle add task
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_task'])) {
    $taskModel = new Task($pdo);
    $taskModel->createTask([
        'organization_id' => $orgId,
        'lead_id'         => $lead['id'],
        'assigned_to'     => !empty($_POST['assigned_to']) ? (int)$_POST['assigned_to'] : getUserId(),
        'task_title'      => trim($_POST['task_title']),
        'description'     => trim($_POST['description']),
        'due_date'        => $_POST['due_date'],
        'status'          => 'pending'
    ]);
    $leadModel->logActivity($lead['id'], 'note', 'Task created: ' . trim($_POST['task_title']), null, null, getUserId());
    redirect(BASE_URL . 'modules/leads/view.php?id=' . $lead['id'], 'Task added!', 'success');
}

// Handle add followup
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_followup'])) {
    $followupModel = new Followup($pdo);
    $followupModel->create([
        'organization_id' => $orgId,
        'lead_id'         => $lead['id'],
        'deal_id'         => null,
        'user_id'         => getUserId(),
        'title'           => trim($_POST['title']),
        'description'     => trim($_POST['description']),
        'followup_date'   => $_POST['followup_date'],
        'followup_time'   => $_POST['followup_time'] ?: null,
        'priority'        => $_POST['priority'] ?? 'medium'
    ]);
    $leadModel->logActivity($lead['id'], 'note', 'Follow-up scheduled: ' . trim($_POST['title']), null, null, getUserId());
    redirect(BASE_URL . 'modules/leads/view.php?id=' . $lead['id'], 'Follow-up scheduled!', 'success');
}

// Handle sync tags
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sync_tags'])) {
    $tagIds = $_POST['tag_ids'] ?? [];
    $leadModel->syncTags($lead['id'], $tagIds);
    redirect(BASE_URL . 'modules/leads/view.php?id=' . $lead['id'], 'Tags updated!', 'success');
}

// Handle add note
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_note'])) {
    $note = trim($_POST['note']);
    if ($note) {
        $leadModel->addNote($lead['id'], $note, getUserId());
        
        // Also create as a follow-up if checked
        if (!empty($_POST['create_followup']) && !empty($_POST['followup_date'])) {
            $followupModel = new Followup($pdo);
            $followupModel->create([
                'organization_id' => $orgId,
                'lead_id'         => $lead['id'],
                'deal_id'         => null,
                'user_id'         => getUserId(),
                'title'           => $lead['name'],
                'description'     => $note,
                'followup_date'   => $_POST['followup_date'],
                'followup_time'   => !empty($_POST['followup_time']) ? $_POST['followup_time'] : null,
                'priority'        => 'medium'
            ]);
        }
        
        redirect(BASE_URL . 'modules/leads/view.php?id=' . $lead['id'], 'Note added!', 'success');
    }
}

// Handle edit note
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_note'])) {
    $noteId = (int)$_POST['note_id'];
    $noteText = trim($_POST['note_text']);
    if ($noteId && $noteText) {
        $leadModel->editNote($noteId, $noteText, $lead['id'], getUserId());
        redirect(BASE_URL . 'modules/leads/view.php?id=' . $lead['id'], 'Note updated!', 'success');
    }
}

// Handle delete note
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_note'])) {
    $noteId = (int)$_POST['note_id'];
    if ($noteId) {
        $leadModel->deleteNote($noteId, $lead['id'], getUserId());
        redirect(BASE_URL . 'modules/leads/view.php?id=' . $lead['id'], 'Note deleted!', 'success');
    }
}

// Handle add alternate phone
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_phone'])) {
    $newPhone = trim($_POST['new_phone'] ?? '');
    $phoneLabel = trim($_POST['phone_label'] ?? 'Alternate');
    if ($newPhone) {
        $stmt = $pdo->prepare("INSERT INTO lead_phones (lead_id, phone, label, is_primary) VALUES (?, ?, ?, 0)");
        $stmt->execute([$lead['id'], $newPhone, $phoneLabel]);
        $leadModel->logActivity($lead['id'], 'note', 'Added alternate phone: ' . $newPhone, null, null, getUserId());
        redirect(BASE_URL . 'modules/leads/view.php?id=' . $lead['id'], 'Phone number added!', 'success');
    }
}

// Handle set primary phone
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['set_primary_phone'])) {
    $phoneId = (int)($_POST['phone_id'] ?? 0);
    $phoneNumber = trim($_POST['phone_number'] ?? '');
    if ($phoneId && $phoneNumber) {
        // Save current main phone as alternate first
        $currentPhone = $lead['phone'];
        $stmt = $pdo->prepare("INSERT INTO lead_phones (lead_id, phone, label, is_primary) VALUES (?, ?, 'Previous Main', 0)");
        $stmt->execute([$lead['id'], $currentPhone]);
        
        // Set the new main phone on the leads table
        $stmt = $pdo->prepare("UPDATE leads SET phone = ? WHERE id = ?");
        $stmt->execute([$phoneNumber, $lead['id']]);
        
        // Remove the alternate entry that's now the main
        $pdo->prepare("DELETE FROM lead_phones WHERE id = ?")->execute([$phoneId]);
        
        $leadModel->logActivity($lead['id'], 'note', 'Changed main phone to: ' . $phoneNumber . ' (previous: ' . $currentPhone . ')', null, null, getUserId());
        redirect(BASE_URL . 'modules/leads/view.php?id=' . $lead['id'], 'Main phone number updated!', 'success');
    }
}

// Handle Follow-up Completion from this page
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['complete_followup'])) {
    $fId = (int)$_POST['followup_id'];
    $followupModel = new Followup($pdo);
    $f = $followupModel->getById($fId);
    
    if ($f && (isAdmin() || $f['user_id'] == getUserId())) {
        $followupModel->complete($fId);
        
        $noteText = trim($_POST['outcome_note'] ?? '');
        if ($noteText && $f['lead_id']) {
            $leadModel->addNote($f['lead_id'], $noteText, getUserId());
        }
        
        if (!empty($_POST['schedule_next'])) {
            $data = [
                'organization_id' => getOrgId(),
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
        redirect(BASE_URL . 'modules/leads/view.php?id=' . $lead['id'], 'Follow-up marked as completed!', 'success');
    }
}

// Handle delete alternate phone
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_phone'])) {
    $phoneId = (int)($_POST['phone_id'] ?? 0);
    if ($phoneId) {
        $pdo->prepare("DELETE FROM lead_phones WHERE id = ? AND lead_id = ?")->execute([$phoneId, $lead['id']]);
        redirect(BASE_URL . 'modules/leads/view.php?id=' . $lead['id'], 'Phone number removed!', 'success');
    }
}

// Handle delete
if (isset($_GET['delete']) && $_GET['delete'] === 'confirm') {
    $leadModel->deleteLead($lead['id']);
    redirect(BASE_URL . 'modules/leads/', 'Lead deleted.', 'success');
}

$activities = $leadModel->getActivities($lead['id']);
$notes      = $leadModel->getNotes($lead['id']);
$currentTags = $leadModel->getTags($lead['id']);
$allOrgTags = $leadModel->getOrgTags($orgId);

// Fetch alternate phone numbers
$altPhonesStmt = $pdo->prepare("SELECT * FROM lead_phones WHERE lead_id = ? ORDER BY created_at ASC");
$altPhonesStmt->execute([$lead['id']]);
$altPhones = $altPhonesStmt->fetchAll();

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

// No longer merging them, we want them separate
// But we can still sort them for their respective sections if needed
usort($followups, function($a, $b) { return strtotime($a['followup_date']) - strtotime($b['followup_date']); });
usort($leadTasks, function($a, $b) { return strtotime($a['due_date']) - strtotime($b['due_date']); });

// Get pipeline stages for quick status
// Get pipeline stages for quick status
$stagesStmt = $pdo->prepare("SELECT name FROM pipeline_stages WHERE organization_id = :org ORDER BY position");
$stagesStmt->execute(['org' => $orgId]);
$pipelineStages = $stagesStmt->fetchAll(PDO::FETCH_COLUMN);

// Fetch agents for task assignment
$orgAgents = [];
$agentsStmt = $pdo->prepare("SELECT id, name FROM users WHERE organization_id = :org AND is_active = 1 ORDER BY name");
$agentsStmt->execute(['org' => $orgId]);
$orgAgents = $agentsStmt->fetchAll();

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

/* ============================================================
   LEAD DETAIL — Mobile Responsive
   ============================================================ */
@media (max-width: 1100px) {
    /* Hero card: compress */
    .ld-hero {
        padding: 16px !important;
        border-radius: 14px;
        margin-bottom: 16px;
    }

    /* Avatar: smaller */
    .lead-avatar-lg {
        width: 44px; height: 44px;
        border-radius: 12px;
        font-size: 16px;
    }

    /* Name: scale down */
    .ld-name {
        font-size: 1.15rem !important;
        letter-spacing: -0.3px;
    }
    .ld-sub { font-size: 11px; }

    /* Badges: tighter */
    .ld-badge {
        font-size: 10px;
        padding: 3px 9px;
    }

    /* Avatar + name gap */
    .ld-hero .d-flex.align-items-center.gap-4 {
        gap: 12px !important;
    }

    /* Top row: stack vertically */
    .ld-hero .d-flex.align-items-start.justify-content-between {
        flex-direction: column !important;
        gap: 12px;
    }

    /* Action buttons: full width row */
    .ld-hero .d-flex.flex-wrap.gap-2.align-items-center {
        width: 100%;
    }
    .btn-wa, .btn-call, .btn-email {
        flex: 1;
        justify-content: center;
        padding: 9px 10px !important;
        font-size: 12px !important;
        border-radius: 10px;
    }
    .btn-icon-ghost {
        width: 36px !important;
        height: 36px !important;
    }

    /* Quick Status pills: scrollable row */
    .ld-hero .mt-4.pt-3 {
        margin-top: 12px !important;
        padding-top: 10px !important;
    }
    .status-pill-btn {
        font-size: 11px;
        padding: 5px 10px;
    }

    /* Info cards: tighter padding */
    .ld-card { border-radius: 14px; margin-bottom: 14px; }
    .ld-card-header { padding: 14px 16px 0; }
    .ld-card-body { padding: 12px 16px 14px; }

    /* Info rows */
    .info-row { gap: 10px; padding: 10px 0; }
    .info-icon { width: 30px; height: 30px; border-radius: 8px; font-size: 13px; }
    .info-value { font-size: 13px; word-break: break-word; }

    /* Note textarea */
    .note-input { font-size: 12px; padding: 10px 12px; }
    .note-submit { font-size: 12px; padding: 8px 14px; }

    /* Timeline */
    .tl-dot { width: 28px; height: 28px; font-size: 12px; }
    .tl-desc { font-size: 12px; }

    /* Back link */
    .back-link { font-size: 12px; margin-bottom: 10px; }

    /* Scrollable sections: shorter on mobile */
    .scroll-y { max-height: 300px; }
}
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
                <?php foreach ($pipelineStages as $s): ?>
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
    <div class="col-xl-8 col-lg-12">

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
                    <div class="w-100">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div class="info-label mb-0" style="font-size: 11px; letter-spacing: 0.5px; text-transform: uppercase; color: #64748b; font-weight: 700;">Phone Numbers</div>
                            <button class="btn btn-sm" style="background:#eef2ff; color:#4f46e5; border-radius:6px; font-size:11px; font-weight:600; padding: 4px 10px; border:none; transition:all 0.2s;" onmouseover="this.style.background='#e0e7ff'" onmouseout="this.style.background='#eef2ff'" data-bs-toggle="modal" data-bs-target="#addPhoneModal">
                                <i class="bi bi-plus-circle me-1"></i> Add Number
                            </button>
                        </div>
                        
                        <!-- Main Phone -->
                        <div class="d-flex justify-content-between align-items-center bg-light rounded-2 px-2 py-1 mb-1 border border-primary border-opacity-25">
                            <div class="info-value d-flex align-items-center gap-2 mb-0">
                                <a href="tel:<?= e($lead['phone']) ?>" class="fw-bold"><?= e($lead['phone']) ?></a>
                                <span class="badge bg-primary bg-opacity-10 text-primary" style="font-size:10px;">Primary</span>
                            </div>
                            <div class="d-flex gap-1">
                                <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $lead['phone']) ?>" target="_blank" class="text-success p-1" title="WhatsApp"><i class="bi bi-whatsapp"></i></a>
                            </div>
                        </div>

                        <!-- Alt Phones -->
                        <?php foreach($altPhones as $ap): ?>
                        <div class="d-flex justify-content-between align-items-center bg-light rounded-2 px-2 py-1 mb-1 border">
                            <div class="info-value d-flex flex-column mb-0">
                                <div class="d-flex align-items-center gap-2">
                                    <a href="tel:<?= e($ap['phone']) ?>" class="text-dark"><?= e($ap['phone']) ?></a>
                                    <span class="badge bg-secondary bg-opacity-10 text-secondary" style="font-size:10px; font-weight:normal;"><?= e($ap['label']) ?></span>
                                </div>
                            </div>
                            <div class="d-flex gap-1 align-items-center">
                                <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $ap['phone']) ?>" target="_blank" class="text-success p-1" title="WhatsApp"><i class="bi bi-whatsapp"></i></a>
                                
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Make this the main phone?');">
                                    <input type="hidden" name="set_primary_phone" value="1">
                                    <input type="hidden" name="phone_id" value="<?= $ap['id'] ?>">
                                    <input type="hidden" name="phone_number" value="<?= e($ap['phone']) ?>">
                                    <button type="submit" class="btn btn-link text-primary p-1 border-0" title="Set as main"><i class="bi bi-arrow-bar-up"></i></button>
                                </form>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this phone?');">
                                    <input type="hidden" name="delete_phone" value="1">
                                    <input type="hidden" name="phone_id" value="<?= $ap['id'] ?>">
                                    <button type="submit" class="btn btn-link text-danger p-1 border-0" title="Delete"><i class="bi bi-trash"></i></button>
                                </form>
                            </div>
                        </div>
                        <?php endforeach; ?>
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
                    
                    <div class="mt-3 bg-light rounded-3 p-3 border">
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input" type="checkbox" id="create_followup_note" name="create_followup" onchange="document.getElementById('note_followup_fields').classList.toggle('d-none', !this.checked); document.getElementById('note_f_date').required = this.checked;">
                            <label class="form-check-label fw-semibold text-dark ms-1" for="create_followup_note">Also create this note as a Follow-up</label>
                        </div>
                        
                        <div id="note_followup_fields" class="d-none mt-3 row g-2">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold text-muted mb-1">Follow-up Date <span class="text-danger">*</span></label>
                                <input type="date" id="note_f_date" name="followup_date" class="form-control form-control-sm" style="border-radius:8px;" min="<?= date('Y-m-d') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold text-muted mb-1">Time (Optional)</label>
                                <input type="time" name="followup_time" class="form-control form-control-sm" style="border-radius:8px;">
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end mt-3">
                        <button type="submit" name="add_note" value="1" class="note-submit">
                            <i class="bi bi-plus-circle-fill me-1"></i> Save Note
                        </button>
                    </div>
                </form>
                <!-- Notes Timeline -->
                <?php if (!empty($notes)):
                    $noteColors = ['#6366f1','#10b981','#f59e0b','#ec4899','#8b5cf6','#06b6d4','#ef4444','#f97316','#14b8a6','#3b82f6'];
                ?>
                <div class="position-relative" style="padding-left: 28px;">
                    <!-- Vertical line -->
                    <div style="position:absolute; left:10px; top:0; bottom:0; width:2px; background: linear-gradient(to bottom, #e2e8f0, transparent);"></div>

                    <?php foreach ($notes as $idx => $n):
                        $authorName = $n['user_name'] ?? 'System';
                        $initial = strtoupper(substr($authorName, 0, 1));
                        $color = $noteColors[$idx % count($noteColors)];
                    ?>
                    <div class="position-relative mb-4">
                        <!-- Timeline dot with avatar -->
                        <div class="position-absolute d-flex align-items-center justify-content-center text-white fw-bold"
                             style="left:-28px; top:4px; width:22px; height:22px; border-radius:50%; background:<?= $color ?>; font-size:10px; box-shadow:0 0 0 3px #fff, 0 0 0 5px <?= $color ?>33;">
                            <?= $initial ?>
                        </div>

                        <!-- Note Card -->
                        <div style="background:#fff; border:1px solid #e2e8f0; border-radius:12px; padding:12px 14px; box-shadow:0 1px 4px rgba(0,0,0,0.04); border-left: 3px solid <?= $color ?>;">
                            <!-- Header -->
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="fw-semibold" style="font-size:12px; color:<?= $color ?>;"><?= e($authorName) ?></span>
                                <span class="text-muted" style="font-size:10.5px;">
                                    <i class="bi bi-clock me-1"></i><?= date('d M Y · h:i A', strtotime($n['created_at'])) ?>
                                </span>
                            </div>

                            <!-- Note text -->
                            <div class="note-text mb-2" id="note-text-<?= $n['id'] ?>" style="font-size:13px; color:#374151; line-height:1.6;"><?= nl2br(e($n['note'])) ?></div>

                            <!-- Edit form (hidden) -->
                            <form method="POST" id="note-edit-form-<?= $n['id'] ?>" class="d-none mt-2">
                                <input type="hidden" name="note_id" value="<?= $n['id'] ?>">
                                <textarea class="note-input" name="note_text" rows="2" required><?= e($n['note']) ?></textarea>
                                <div class="d-flex justify-content-end gap-2 mt-2">
                                    <button type="button" class="btn btn-sm btn-light rounded-pill px-3" onclick="toggleEditNote(<?= $n['id'] ?>)">Cancel</button>
                                    <button type="submit" name="edit_note" value="1" class="btn btn-sm rounded-pill px-3 text-white" style="background:<?= $color ?>;border:none;">Save</button>
                                </div>
                            </form>

                            <!-- Actions -->
                            <?php if (getUserId() == $n['user_id'] || getUserRole() !== 'agent'): ?>
                            <div class="d-flex gap-3">
                                <button type="button" onclick="toggleEditNote(<?= $n['id'] ?>)" style="background:none;border:none;padding:0;font-size:11px;font-weight:600;color:#6366f1;opacity:0.8;transition:opacity 0.2s;" onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='0.8'">
                                    <i class="bi bi-pencil me-1"></i>Edit
                                </button>
                                <form method="POST" onsubmit="return confirm('Delete this note?');" class="m-0 p-0">
                                    <input type="hidden" name="note_id" value="<?= $n['id'] ?>">
                                    <button type="submit" name="delete_note" value="1" style="background:none;border:none;padding:0;font-size:11px;font-weight:600;color:#ef4444;opacity:0.8;transition:opacity 0.2s;" onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='0.8'">
                                        <i class="bi bi-trash me-1"></i>Delete
                                    </button>
                                </form>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
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
    <div class="col-xl-4 col-lg-12">



        <!-- ── Follow-ups ── -->
        <div class="ld-card">
            <div class="ld-card-header">
                <div class="ld-section-title">
                    <div class="icon-wrap" style="background:#ecfeff;color:#06b6d4;"><i class="bi bi-telephone-outbound"></i></div>
                    Follow-ups <span style="background:#f1f5f9;color:#64748b;font-size:10px;font-weight:700;padding:2px 8px;border-radius:20px;margin-left:6px;"><?= count($followups) ?></span>
                </div>
                <button type="button" class="btn-add-floating" data-bs-toggle="modal" data-bs-target="#addFollowupModal">
                    <i class="bi bi-plus"></i> Add
                </button>
            </div>
            <div class="ld-card-body">
                <?php if (!empty($followups)): ?>
                    <?php foreach ($followups as $f):
                        $isDone = $f['status'] === 'completed';
                        $isOverdue = !$isDone && strtotime($f['followup_date']) < strtotime('today');
                        $isUpcoming = !$isDone && !$isOverdue;
                    ?>
                    <div class="reminder-item" <?= $isUpcoming ? 'style="border-left: 3px solid #f97316; padding-left: 12px; margin-left: -15px; border-radius: 4px;"' : '' ?>>
                        <?php if (!$isDone): ?>
                        <div class="rem-check" style="cursor: pointer; transition: 0.2s; border-color: <?= $isUpcoming ? '#f97316' : '#ef4444' ?>; <?php if($isUpcoming): ?>background-color:#fff7ed;<?php else: ?>background-color:#fef2f2;<?php endif; ?>" 
                             onmouseover="this.style.backgroundColor='<?= $isUpcoming ? '#f97316' : '#ef4444' ?>'; this.nextElementSibling.style.color='<?= $isUpcoming ? '#f97316' : '#ef4444' ?>'" 
                             onmouseout="this.style.backgroundColor='<?= $isUpcoming ? '#fff7ed' : '#fef2f2' ?>'; this.nextElementSibling.style.color='inherit'" 
                             onclick="openCompleteFollowupModal(<?= $f['id'] ?>, '<?= e(addslashes($f['title'])) ?>')"></div>
                        <?php else: ?>
                        <div class="rem-check done">
                            <i class="bi bi-check"></i>
                        </div>
                        <?php endif; ?>
                        <div class="flex-grow-1">
                            <div style="font-size:13px;font-weight:600;color:<?= $isDone ? '#94a3b8' : '#0f172a' ?>;<?= $isDone ? 'text-decoration:line-through;' : '' ?>">
                                <?= e($f['title']) ?>
                            </div>
                            <div class="d-flex align-items-center gap-2 mt-1">
                                <span class="rem-due <?= $isOverdue ? 'rem-overdue' : '' ?>" style="<?= $isUpcoming ? 'color: #f97316; font-weight: 500;' : '' ?>">
                                    <?php if ($isOverdue): ?><i class="bi bi-exclamation-triangle-fill me-1"></i><?php endif; ?>
                                    <i class="bi bi-calendar3 me-1"></i><?= formatDate($f['followup_date']) ?>
                                    <?php if ($f['followup_time']): ?> &bull; <i class="bi bi-clock me-1"></i><?= date('h:i A', strtotime($f['followup_time'])) ?><?php endif; ?>
                                    <?php if ($isUpcoming): ?>
                                        <span class="ms-1" style="background:#ffedd5; color:#c2410c; padding:2px 6px; border-radius:10px; font-size:9px; font-weight:700; text-transform:uppercase;">Upcoming</span>
                                    <?php endif; ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                <div class="text-center py-4">
                    <i class="bi bi-telephone-x" style="font-size:2rem;color:#e2e8f0;display:block;margin-bottom:8px;"></i>
                    <span style="font-size:13px;color:#94a3b8;">No follow-ups scheduled</span>
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
        <form action="" method="POST" class="modal-content" style="border-radius:20px;border:none;box-shadow:0 10px 40px rgba(0,0,0,0.1);">
            <div class="modal-header border-0 pb-0 pt-4 px-4">
                <h5 class="modal-title fw-bold">Internal Task</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body px-4 py-3">
                <input type="hidden" name="add_task" value="1">
                <div class="mb-3">
                    <label class="form-label fw-semibold small">Task Title</label>
                    <input type="text" class="form-control" name="task_title" placeholder="e.g. Prepare proposal" required style="border-radius:12px;">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold small">Due Date</label>
                    <input type="date" class="form-control" name="due_date" value="<?= date('Y-m-d') ?>" required style="border-radius:12px;">
                </div>
                <?php if (!empty($orgAgents)): ?>
                <div class="mb-3">
                    <label class="form-label fw-semibold small">Assign To</label>
                    <select class="form-select" name="assigned_to" style="border-radius:12px;">
                        <option value="<?= getUserId() ?>">Myself</option>
                        <?php foreach ($orgAgents as $ag): ?>
                            <?php if ($ag['id'] != getUserId()): ?>
                            <option value="<?= $ag['id'] ?>"><?= e($ag['name']) ?></option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
                <div class="mb-0">
                    <label class="form-label fw-semibold small">Notes</label>
                    <textarea class="form-control" name="description" rows="2" style="border-radius:12px;"></textarea>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0 pb-4 px-4">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold" style="background:linear-gradient(135deg,#6366f1,#4f46e5);border:none;">Save Task</button>
            </div>
        </form>
    </div>
</div>

<!-- ═══════════════ ADD FOLLOW-UP MODAL ═══════════════ -->
<div class="modal fade" id="addFollowupModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form action="" method="POST" class="modal-content" style="border-radius:20px;border:none;box-shadow:0 10px 40px rgba(0,0,0,0.1);">
            <div class="modal-header border-0 pb-0 pt-4 px-4">
                <h5 class="modal-title fw-bold">Schedule Follow-up</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body px-4 py-3">
                <input type="hidden" name="add_followup" value="1">
                <div class="mb-3">
                    <label class="form-label fw-semibold small">Name</label>
                    <input type="text" class="form-control" name="title" placeholder="e.g. Call to discuss pricing" required style="border-radius:12px;">
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-6">
                        <label class="form-label fw-semibold small">Date</label>
                        <input type="date" class="form-control" name="followup_date" value="<?= date('Y-m-d') ?>" required style="border-radius:12px;">
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-semibold small">Time</label>
                        <input type="time" class="form-control" name="followup_time" style="border-radius:12px;">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold small">Priority</label>
                    <select class="form-select" name="priority" style="border-radius:12px;">
                        <option value="low">Low</option>
                        <option value="medium" selected>Medium</option>
                        <option value="high">High</option>
                    </select>
                </div>
                <div class="mb-0">
                    <label class="form-label fw-semibold small">Description</label>
                    <textarea class="form-control" name="description" rows="2" style="border-radius:12px;"></textarea>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0 pb-4 px-4">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold" style="background:linear-gradient(135deg,#06b6d4,#0891b2);border:none;">Set Reminder</button>
            </div>
        </form>
    </div>
</div>

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
                        <label class="form-check-label fw-semibold text-dark ms-1" for="scheduleNextToggle">Schedule Next Follow-up</label>
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

<!-- ═══════════════ EDIT TAGS MODAL ═══════════════ -->
<div class="modal fade" id="editTagsModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form action="" method="POST" class="modal-content" style="border-radius:20px;border:none;box-shadow:0 10px 40px rgba(0,0,0,0.1);">
            <div class="modal-header border-0 pb-0 pt-4 px-4">
                <h5 class="modal-title fw-bold">Manage Tags</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body px-4 py-3">
                <input type="hidden" name="sync_tags" value="1">
                <p class="text-secondary small mb-3">Select tags to categorize this lead.</p>
                <div class="d-flex flex-wrap gap-2">
                    <?php 
                        $leadTagIds = array_column($currentTags, 'id');
                        foreach ($allOrgTags as $tag): 
                            $isActive = in_array($tag['id'], $leadTagIds);
                    ?>
                        <label class="btn btn-sm btn-outline-secondary rounded-pill tag-selector <?= $isActive ? 'active' : '' ?>" style="border-color:<?= e($tag['color']) ?>;color:<?= $isActive ? '#fff' : e($tag['color']) ?>;background:<?= $isActive ? e($tag['color']) : 'transparent' ?>;">
                            <input type="checkbox" name="tag_ids[]" value="<?= $tag['id'] ?>" class="d-none" <?= $isActive ? 'checked' : '' ?>>
                            <?= e($tag['name']) ?>
                        </label>
                    <?php endforeach; ?>
                </div>
                <?php if (empty($allOrgTags)): ?>
                    <div class="text-center py-3">
                        <p class="small text-muted mb-0">No tags configured for your organization.</p>
                        <a href="<?= BASE_URL ?>modules/settings/tags.php" class="small text-primary fw-bold">Create Tags</a>
                    </div>
                <?php endif; ?>
            </div>
            <div class="modal-footer border-0 pt-0 pb-4 px-4">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold" style="background:linear-gradient(135deg,#6366f1,#4f46e5);border:none;">Save Tags</button>
            </div>
        </form>
    </div>
</div>

<!-- ═══════════════ ADD PHONE MODAL ═══════════════ -->
<div class="modal fade" id="addPhoneModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form action="" method="POST" class="modal-content" style="border-radius:20px;border:none;box-shadow:0 10px 40px rgba(0,0,0,0.1);">
            <div class="modal-header border-0 pb-0 pt-4 px-4">
                <h5 class="modal-title fw-bold">Add Phone Number</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body px-4 py-3">
                <input type="hidden" name="add_phone" value="1">
                <div class="mb-3">
                    <label class="form-label fw-semibold small">Phone Number</label>
                    <input type="text" class="form-control" name="new_phone" placeholder="e.g. +1 555-0123" required style="border-radius:12px;">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold small">Label</label>
                    <select class="form-select" name="phone_label" required style="border-radius:12px;">
                        <option value="Alternate">Alternate</option>
                        <option value="Work">Work</option>
                        <option value="Home">Home</option>
                        <option value="Mobile">Mobile</option>
                        <option value="WhatsApp">WhatsApp</option>
                        <option value="Spouse">Spouse / Partner</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0 pb-4 px-4">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold" style="background:linear-gradient(135deg,#6366f1,#4f46e5);border:none;">Save Phone</button>
            </div>
        </form>
    </div>
</div>

<script>
document.querySelectorAll('.tag-selector').forEach(label => {
    label.addEventListener('click', function() {
        const checkbox = this.querySelector('input');
        // Toggle background and color based on active state
        setTimeout(() => {
            if (checkbox.checked) {
                this.classList.add('active');
                this.style.background = this.style.borderColor;
                this.style.color = '#fff';
            } else {
                this.classList.remove('active');
                this.style.background = 'transparent';
                this.style.color = this.style.borderColor;
            }
        }, 10);
    });
});
</script>

<script>
function toggleEditNote(id) {
    const textDiv = document.getElementById('note-text-' + id);
    const formDiv = document.getElementById('note-edit-form-' + id);
    if (formDiv.classList.contains('d-none')) {
        formDiv.classList.remove('d-none');
        textDiv.classList.add('d-none');
    } else {
        formDiv.classList.add('d-none');
        textDiv.classList.remove('d-none');
    }
}
</script>

<?php include '../../includes/footer.php'; ?>
