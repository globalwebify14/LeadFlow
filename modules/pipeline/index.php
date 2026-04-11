<?php
$pageTitle = 'Sales Pipeline';
require_once '../../config/auth.php';
requireLogin();
require_once '../../config/db.php';
if (getUserRole() === 'super_admin') {
    redirect(BASE_URL . 'modules/dashboard/', 'No permission.', 'danger');
}
require_once '../../models/Lead.php';

$orgId = getOrgId();
$leadModel = new Lead($pdo);

// Get pipeline stages
$stages = $leadModel->getOrInitializeStages($orgId);

// Filters
$filterAgentId = (isset($_GET['agent_id']) && $_GET['agent_id'] !== '') ? (int)$_GET['agent_id'] : null;
if (getUserRole() === 'agent') {
    $filterAgentId = getUserId();
}

$datePreset = $_GET['date_preset'] ?? '';
$dateFrom = $_GET['date_from'] ?? null;
$dateTo = $_GET['date_to'] ?? null;

if ($datePreset) {
    if ($datePreset === 'today') {
        $dateFrom = date('Y-m-d');
        $dateTo   = date('Y-m-d');
    } elseif ($datePreset === 'yesterday') {
        $dateFrom = date('Y-m-d', strtotime('-1 day'));
        $dateTo   = date('Y-m-d', strtotime('-1 day'));
    } elseif ($datePreset === '7days') {
        $dateFrom = date('Y-m-d', strtotime('-7 days'));
        $dateTo   = date('Y-m-d');
    } elseif ($datePreset === 'month') {
        $dateFrom = date('Y-m-01');
        $dateTo   = date('Y-m-t');
    } elseif ($datePreset === '90days') {
        $dateFrom = date('Y-m-d', strtotime('-90 days'));
        $dateTo   = date('Y-m-d');
    }
}

$pipelineData = [];
foreach ($stages as $stage) {
    $leads = $leadModel->getLeadsByStage($orgId, $stage['id'], $filterAgentId, $dateFrom, $dateTo);
    $pipelineData[] = ['stage' => $stage, 'leads' => $leads];
}

// Also get unassigned leads (no pipeline stage)
$unassignedSql = "SELECT l.*, u.name as agent_name FROM leads l LEFT JOIN users u ON l.assigned_to = u.id WHERE l.organization_id = :org AND (l.pipeline_stage_id IS NULL OR l.pipeline_stage_id = 0)";
$unap = ['org' => $orgId];
if ($filterAgentId) {
    $unassignedSql .= " AND l.assigned_to = :user_id";
    $unap['user_id'] = $filterAgentId;
}
$unassignedSql .= " ORDER BY l.id DESC LIMIT 20";
$unassignedStmt = $pdo->prepare($unassignedSql);
$unassignedStmt->execute($unap);
$unassignedLeads = $unassignedStmt->fetchAll();

// ── Followup Column Data ─────────────────────────────────
try {
    $fuSql = "SELECT f.id, f.lead_id, f.followup_date, f.followup_time, f.status, f.description,
                     l.name as lead_name, l.phone as lead_phone,
                     u.name as agent_name
              FROM followups f
              JOIN leads l ON f.lead_id = l.id
              LEFT JOIN users u ON f.user_id = u.id
              WHERE f.organization_id = :org";
    $fuParams = ['org' => $orgId];
    if ($filterAgentId) {
        $fuSql .= " AND f.user_id = :uid";
        $fuParams['uid'] = $filterAgentId;
    }
    $fuSql .= " ORDER BY f.followup_date ASC, f.followup_time ASC LIMIT 100";
    $fuStmt = $pdo->prepare($fuSql);
    $fuStmt->execute($fuParams);
    $allFollowups = $fuStmt->fetchAll();

    $today = date('Y-m-d');
    $fuToday     = array_filter($allFollowups, fn($f) => $f['status'] !== 'completed' && $f['followup_date'] <= $today);
    $fuUpcoming  = array_filter($allFollowups, fn($f) => $f['status'] !== 'completed' && $f['followup_date'] > $today);
    $fuCompleted = array_filter($allFollowups, fn($f) => $f['status'] === 'completed');
} catch (Exception $e) {
    $fuToday = $fuUpcoming = $fuCompleted = [];
}

include '../../includes/header.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
    <form class="d-flex flex-wrap gap-2 align-items-center w-100 w-md-auto">
        <?php if (getUserRole() !== 'agent'): ?>
        <?php 
        $stmtUsers = $pdo->prepare("SELECT id, name FROM users WHERE organization_id = ? AND role IN ('agent', 'team_lead')");
        $stmtUsers->execute([$orgId]);
        $orgUsers = $stmtUsers->fetchAll();
        ?>
        <select name="agent_id" class="form-select form-select-sm" style="width:160px;">
            <option value="">All Agents</option>
            <?php foreach ($orgUsers as $u): ?>
            <option value="<?= $u['id'] ?>" <?= $filterAgentId == $u['id'] ? 'selected' : '' ?>><?= e($u['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <?php endif; ?>
        <select name="date_preset" class="form-select form-select-sm" style="width:140px;" onchange="toggleCustomDates(this.value)">
            <option value="">All Time</option>
            <option value="today" <?= $datePreset === 'today' ? 'selected' : '' ?>>Today</option>
            <option value="yesterday" <?= $datePreset === 'yesterday' ? 'selected' : '' ?>>Yesterday</option>
            <option value="7days" <?= $datePreset === '7days' ? 'selected' : '' ?>>Last 7 Days</option>
            <option value="month" <?= $datePreset === 'month' ? 'selected' : '' ?>>This Month</option>
            <option value="90days" <?= $datePreset === '90days' ? 'selected' : '' ?>>Last 90 Days</option>
            <option value="custom" <?= $datePreset === 'custom' ? 'selected' : '' ?>>Custom Range</option>
        </select>
        
        <div id="custom-dates" class="<?= $datePreset === 'custom' ? 'd-flex' : 'd-none' ?> gap-2">
            <input type="date" name="date_from" class="form-control form-control-sm" value="<?= e($_GET['date_from'] ?? '') ?>" style="width:130px;">
            <input type="date" name="date_to" class="form-control form-control-sm" value="<?= e($_GET['date_to'] ?? '') ?>" style="width:130px;">
        </div>

        <button type="submit" class="btn btn-sm btn-light border fw-bold text-secondary"><i class="bi bi-filter me-1"></i>Filter</button>
        <?php if ($filterAgentId || $datePreset || $dateFrom || $dateTo): ?>
        <a href="index.php" class="btn btn-sm btn-link text-decoration-none text-danger">Clear</a>
        <?php endif; ?>
    </form>
    <a href="<?= BASE_URL ?>modules/leads/add.php" class="btn btn-primary d-flex align-items-center fw-bold px-3 py-2" style="border-radius: 10px; font-size: 13px; box-shadow: 0 4px 12px rgba(99, 102, 241, 0.2);"><i class="bi bi-plus-lg me-2"></i>Add Lead</a>
</div>

<div class="pipeline-board d-flex gap-4 pb-3">
    <?php foreach ($pipelineData as $pd): ?>
    <div class="pipeline-column" data-stage-id="<?= $pd['stage']['id'] ?>">
        <div class="pipeline-header rounded-top p-3 text-white fw-bold d-flex justify-content-between align-items-center" style="background:<?= e($pd['stage']['color']) ?>; flex: 0 0 auto;">
            <span><?= e($pd['stage']['name']) ?></span>
            <span class="badge bg-white bg-opacity-25 rounded-pill"><?= count($pd['leads']) ?></span>
        </div>
        <div class="pipeline-cards p-2 rounded-bottom" style="background:#f1f5f9; flex: 1; overflow-y: auto;" 
             ondragover="event.preventDefault();this.classList.add('drag-over')" 
             ondragleave="this.classList.remove('drag-over')" 
             ondrop="dropLead(event,<?= $pd['stage']['id'] ?>)">
            <?php foreach ($pd['leads'] as $lead): ?>
            <div class="pipeline-card card border-0 shadow-sm mb-2" draggable="true" id="lead-<?= $lead['id'] ?>" data-lead-id="<?= $lead['id'] ?>" ondragstart="dragLead(event)">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between mb-1">
                        <a href="<?= BASE_URL ?>modules/leads/view.php?id=<?= $lead['id'] ?>" class="fw-semibold text-dark text-decoration-none small"><?= e($lead['name']) ?></a>
                    </div>
                    <div class="text-muted" style="font-size:12px;">
                        <i class="bi bi-telephone me-1"></i><?= e($lead['phone']) ?>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mt-2">
                        <span class="text-muted" style="font-size:11px;"><?= e($lead['agent_name'] ?: 'Unassigned') ?></span>
                        <span class="text-muted" style="font-size:11px;"><?= timeAgo($lead['updated_at'] ?? $lead['created_at']) ?></span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endforeach; ?>

    <!-- ══ FOLLOW-UPS COLUMN ════════════════════════════════ -->
    <div class="pipeline-column fu-column" style="flex:0 0 270px;">
        <div class="pipeline-header rounded-top p-3 text-white fw-bold d-flex justify-content-between align-items-center" style="background:linear-gradient(135deg,#f59e0b,#d97706);flex:0 0 auto;">
            <span><i class="bi bi-calendar-check me-2"></i>Follow-ups</span>
            <span class="badge bg-white bg-opacity-25 rounded-pill"><?= count($fuToday) + count($fuUpcoming) + count($fuCompleted) ?></span>
        </div>
        <div class="pipeline-cards p-2 rounded-bottom" style="background:#fffbeb;flex:1;overflow-y:auto;">

            <?php if (!empty($fuToday)): ?>
            <div class="fu-section-label">📌 Today &amp; Overdue (<?= count($fuToday) ?>)</div>
            <?php foreach ($fuToday as $fu):
                $fuTs = @strtotime($fu['followup_date'] . ' ' . ($fu['followup_time'] ?? '00:00:00'));
                $isOd = $fuTs && $fuTs < strtotime('today');
            ?>
            <div class="fu-card <?= $isOd ? 'fu-overdue' : 'fu-today' ?>">
                <div class="fu-lead-name"><a href="<?= BASE_URL ?>modules/leads/view.php?id=<?= $fu['lead_id'] ?>" class="text-decoration-none text-dark fw-semibold" style="font-size:13px;"><?= e($fu['lead_name']) ?></a></div>
                <div class="fu-meta"><i class="bi bi-telephone me-1"></i><?= e($fu['lead_phone']) ?></div>
                <div class="fu-time">
                    <i class="bi bi-<?= $isOd ? 'alarm-fill text-danger' : 'clock text-warning' ?> me-1"></i>
                    <strong><?= $fuTs ? date('M d', $fuTs) : e($fu['followup_date']) ?></strong>
                    <?php if (!empty($fu['followup_time'])): ?> · <?= $fuTs ? date('h:i A', $fuTs) : '' ?><?php endif; ?>
                    <?php if ($isOd): ?><span class="text-danger fw-bold ms-1" style="font-size:10px;">OVERDUE</span><?php endif; ?>
                </div>
                <?php if (!empty($fu['description'])): ?><div class="fu-note"><?= e(mb_substr($fu['description'], 0, 60)) ?><?= mb_strlen($fu['description']) > 60 ? '…' : '' ?></div><?php endif; ?>
                <?php if ($fu['agent_name']): ?><div class="fu-agent"><i class="bi bi-person me-1"></i><?= e($fu['agent_name']) ?></div><?php endif; ?>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>

            <?php if (!empty($fuUpcoming)): ?>
            <div class="fu-section-label">🗓️ Upcoming (<?= count($fuUpcoming) ?>)</div>
            <?php foreach ($fuUpcoming as $fu):
                $fuTs = @strtotime($fu['followup_date'] . ' ' . ($fu['followup_time'] ?? '00:00:00'));
            ?>
            <div class="fu-card fu-upcoming">
                <div class="fu-lead-name"><a href="<?= BASE_URL ?>modules/leads/view.php?id=<?= $fu['lead_id'] ?>" class="text-decoration-none text-dark fw-semibold" style="font-size:13px;"><?= e($fu['lead_name']) ?></a></div>
                <div class="fu-meta"><i class="bi bi-telephone me-1"></i><?= e($fu['lead_phone']) ?></div>
                <div class="fu-time">
                    <i class="bi bi-calendar-event text-primary me-1"></i>
                    <strong><?= $fuTs ? date('M d', $fuTs) : e($fu['followup_date']) ?></strong>
                    <?php if (!empty($fu['followup_time'])): ?> · <?= $fuTs ? date('h:i A', $fuTs) : '' ?><?php endif; ?>
                </div>
                <?php if (!empty($fu['description'])): ?><div class="fu-note"><?= e(mb_substr($fu['description'], 0, 60)) ?><?= mb_strlen($fu['description']) > 60 ? '…' : '' ?></div><?php endif; ?>
                <?php if ($fu['agent_name']): ?><div class="fu-agent"><i class="bi bi-person me-1"></i><?= e($fu['agent_name']) ?></div><?php endif; ?>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>

            <?php if (!empty($fuCompleted)): ?>
            <div class="fu-section-label">✅ Completed (<?= count($fuCompleted) ?>)</div>
            <?php foreach (array_slice($fuCompleted, 0, 20) as $fu):
                $fuTs = @strtotime($fu['followup_date'] . ' ' . ($fu['followup_time'] ?? '00:00:00'));
            ?>
            <div class="fu-card fu-completed">
                <div class="fu-lead-name"><a href="<?= BASE_URL ?>modules/leads/view.php?id=<?= $fu['lead_id'] ?>" class="text-decoration-none fw-semibold" style="font-size:13px;color:#64748b;"><?= e($fu['lead_name']) ?></a></div>
                <div class="fu-time" style="color:#94a3b8;">
                    <i class="bi bi-check2-all me-1 text-success"></i>
                    <strong><?= $fuTs ? date('M d', $fuTs) : e($fu['followup_date']) ?></strong>
                    <?php if (!empty($fu['followup_time'])): ?> · <?= $fuTs ? date('h:i A', $fuTs) : '' ?><?php endif; ?>
                </div>
                <?php if ($fu['agent_name']): ?><div class="fu-agent"><?= e($fu['agent_name']) ?></div><?php endif; ?>
            </div>
            <?php endforeach; ?>
            <?php if (count($fuCompleted) > 20): ?><div class="text-center text-muted" style="font-size:11px;padding:6px;">+ <?= count($fuCompleted) - 20 ?> more completed</div><?php endif; ?>
            <?php endif; ?>

            <?php if (empty($fuToday) && empty($fuUpcoming) && empty($fuCompleted)): ?>
            <div class="text-center text-muted py-4" style="font-size:12px;">
                <i class="bi bi-calendar2-x d-block mb-2" style="font-size:1.6rem;"></i>No follow-ups scheduled
            </div>
            <?php endif; ?>
        </div>
    </div>

</div>

<?php if (count($unassignedLeads) > 0): ?>
<div class="card shadow-sm border-0 mt-4 mb-5">
    <div class="card-header bg-white border-0 pt-4"><h6 class="fw-bold"><i class="bi bi-inbox me-2 text-muted"></i>Unassigned to Pipeline (<?= count($unassignedLeads) ?>)</h6></div>
    <div class="card-body">
        <div class="d-flex flex-wrap gap-2">
            <?php foreach ($unassignedLeads as $lead): ?>
            <div class="pipeline-card card border shadow-sm" draggable="true" id="lead-<?= $lead['id'] ?>" data-lead-id="<?= $lead['id'] ?>" ondragstart="dragLead(event)" style="width:260px;">
                <div class="card-body p-2">
                    <a href="<?= BASE_URL ?>modules/leads/view.php?id=<?= $lead['id'] ?>" class="fw-semibold text-dark text-decoration-none small d-block"><?= e($lead['name']) ?></a>
                    <small class="text-muted"><?= e($lead['phone']) ?></small>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<style>
/* Constrain the main wrapper to prevent dashboard horizontal scroll */
#page-content-wrapper { overflow-x: hidden; }

.pipeline-board { 
    display: flex;
    gap: 1.25rem;
    overflow-x: auto; 
    height: calc(100vh - 210px);
    padding-bottom: 15px;
    align-items: stretch;
    width: 100%; /* Ensure it doesn't exceed container */
}
.pipeline-board::-webkit-scrollbar {
    height: 8px;
}
.pipeline-board::-webkit-scrollbar-thumb {
    background-color: #cbd5e1;
    border-radius: 10px;
}
.pipeline-column { 
    flex: 0 0 240px; /* Further reduced width from 270px */
    display: flex; 
    flex-direction: column; 
    height: 100%;
}
.pipeline-card { cursor: grab; transition: transform 0.15s, box-shadow 0.15s; border-radius: 12px !important; }
.pipeline-card:active { cursor: grabbing; }
.pipeline-card:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.1) !important; }
.drag-over { background: #dbeafe !important; border: 2px dashed #3b82f6; }
.pipeline-header { font-size: 13px; border-radius: 12px 12px 0 0 !important; }
.pipeline-cards { border-radius: 0 0 12px 12px !important; }
/* Custom scrollbar for cards */
.pipeline-cards::-webkit-scrollbar {
    width: 6px;
}
.pipeline-cards::-webkit-scrollbar-thumb {
    background-color: #cbd5e1;
    border-radius: 10px;
}

/* ── Follow-up Column Cards ─────────────────────────────── */
.fu-section-label {
    font-size: 10px; font-weight: 800; letter-spacing: 0.8px;
    text-transform: uppercase; color: #94a3b8;
    padding: 8px 4px 4px; margin-bottom: 4px;
}
.fu-card {
    border-radius: 10px; padding: 10px 12px;
    margin-bottom: 8px; transition: box-shadow 0.15s;
}
.fu-card:hover { box-shadow: 0 3px 10px rgba(0,0,0,0.1); }
.fu-today    { background: #fff; border: 1px solid #fde68a; }
.fu-overdue  { background: #fff5f5; border: 1px solid #fca5a5; }
.fu-upcoming { background: #f0f9ff; border: 1px solid #bae6fd; }
.fu-completed{ background: #f8fafc; border: 1px solid #e2e8f0; opacity: 0.75; }
.fu-lead-name { margin-bottom: 2px; }
.fu-meta  { font-size: 11px; color: #94a3b8; margin-bottom: 4px; }
.fu-time  { font-size: 12px; color: #475569; margin-bottom: 4px; }
.fu-note  { font-size: 11px; color: #64748b; background: rgba(0,0,0,0.03); border-radius: 6px; padding: 3px 6px; margin-bottom: 4px; }
.fu-agent { font-size: 10px; color: #94a3b8; }


@media (max-width: 991px) {
    /* Board: allow horizontal scroll on tablets, stack on small mobile if needed */
    .pipeline-board {
        gap: 1rem !important;
        height: calc(100vh - 240px) !important;
    }
    .pipeline-column {
        flex: 0 0 260px !important;
    }
}

@media (max-width: 576px) {
    /* Filter bar: stack full width */
    .d-flex.flex-column.flex-md-row.justify-content-between form select,
    .d-flex.flex-column.flex-md-row.justify-content-between form input[type="date"] {
        flex: 1 1 120px;
    }
    .btn-primary.btn-sm { width: 100%; }

    /* Pipeline board: stack vertically on very small mobile */
    .pipeline-board {
        flex-direction: column !important;
        overflow-x: hidden !important;
        height: auto !important;
        gap: 16px !important;
    }

    .pipeline-column {
        width: 100% !important;
        flex: 0 0 auto !important;
    }

    .pipeline-cards {
        max-height: 320px;
    }
}
</style>

<script>
function toggleCustomDates(val) {
    const cd = document.getElementById('custom-dates');
    if (val === 'custom') {
        cd.classList.remove('d-none');
        cd.classList.add('d-flex');
    } else {
        cd.classList.add('d-none');
        cd.classList.remove('d-flex');
    }
}

function dragLead(e) {
    e.dataTransfer.setData('text/plain', e.target.closest('[data-lead-id]').dataset.leadId);
    e.target.closest('[data-lead-id]').style.opacity = '0.5';
}

function dropLead(e, stageId) {
    e.preventDefault();
    e.currentTarget.classList.remove('drag-over');
    const leadId = e.dataTransfer.getData('text/plain');
    const el = document.getElementById('lead-' + leadId);
    if (el) {
        el.style.opacity = '1';
        e.currentTarget.appendChild(el);
        // AJAX update
        fetch('pipeline_api.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({lead_id: leadId, stage_id: stageId})
        }).then(r => r.json()).then(data => {
            if (!data.success) alert('Failed to update pipeline stage');
        });
    }
}
document.addEventListener('dragend', function(e) {
    if (e.target.closest('[data-lead-id]')) e.target.closest('[data-lead-id]').style.opacity = '1';
});
</script>

<?php include '../../includes/footer.php'; ?>


