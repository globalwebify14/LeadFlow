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

$dateFrom = $_GET['date_from'] ?? null;
$dateTo = $_GET['date_to'] ?? null;

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

include '../../includes/header.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-center mb-4 gap-3">
    <form class="d-flex flex-wrap gap-2 align-items-center">
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
        <input type="date" name="date_from" class="form-control form-control-sm" value="<?= e($dateFrom) ?>" style="width:140px;">
        <input type="date" name="date_to" class="form-control form-control-sm" value="<?= e($dateTo) ?>" style="width:140px;">
        <button type="submit" class="btn btn-sm btn-light border"><i class="bi bi-filter"></i></button>
        <?php if ($filterAgentId || $dateFrom || $dateTo): ?>
        <a href="index.php" class="btn btn-sm btn-link text-decoration-none text-danger">Clear</a>
        <?php endif; ?>
    </form>
    <a href="<?= BASE_URL ?>modules/leads/add.php" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>Add Lead</a>
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

/* ============================================================
   PIPELINE — Mobile Responsive Overhaul
   ============================================================ */
@media (max-width: 768px) {
    /* Filter bar: stack full width */
    .d-flex.flex-column.flex-md-row.justify-content-between {
        gap: 10px !important;
    }
    .d-flex.flex-column.flex-md-row.justify-content-between form {
        width: 100%;
    }
    .d-flex.flex-column.flex-md-row.justify-content-between form select,
    .d-flex.flex-column.flex-md-row.justify-content-between form input[type="date"] {
        width: 100% !important;
        flex: 1;
    }

    /* Pipeline board: stack vertically */
    .pipeline-board {
        flex-direction: column !important;
        overflow-x: hidden !important;
        height: auto !important;
        gap: 16px !important;
        padding-bottom: 0 !important;
    }

    /* Each column: full width, auto height */
    .pipeline-column {
        flex: 0 0 auto !important;
        width: 100% !important;
        height: auto !important;
    }

    /* Cards container: limited height with scroll */
    .pipeline-cards {
        max-height: 300px;
        overflow-y: auto !important;
    }

    /* Pipeline cards: smaller padding */
    .pipeline-card .card-body {
        padding: 10px !important;
    }
    .pipeline-card .fw-semibold {
        font-size: 13px !important;
    }

    /* Unassigned leads: wrap nicely */
    .d-flex.flex-wrap.gap-2 .pipeline-card {
        width: 100% !important;
    }
}
</style>

<script>
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


