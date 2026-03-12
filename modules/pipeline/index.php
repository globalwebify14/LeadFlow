<?php
$pageTitle = 'Sales Pipeline';
require_once '../../config/auth.php';
requireLogin();
require_once '../../config/db.php';
require_once '../../models/Lead.php';

$orgId = getOrgId();
$leadModel = new Lead($pdo);

// Get pipeline stages
$stagesStmt = $pdo->prepare("SELECT * FROM pipeline_stages WHERE organization_id = :org ORDER BY position");
$stagesStmt->execute(['org' => $orgId]);
$stages = $stagesStmt->fetchAll();

// Get leads grouped by pipeline stage
$pipelineData = [];
$agentId = (getUserRole() === 'agent') ? getUserId() : null;

foreach ($stages as $stage) {
    $leads = $leadModel->getLeadsByStage($orgId, $stage['id'], $agentId);
    $pipelineData[] = ['stage' => $stage, 'leads' => $leads];
}

// Also get unassigned leads (no pipeline stage)
$unassignedSql = "SELECT l.*, u.name as agent_name FROM leads l LEFT JOIN users u ON l.assigned_to = u.id WHERE l.organization_id = :org AND (l.pipeline_stage_id IS NULL OR l.pipeline_stage_id = 0)";
$unap = ['org' => $orgId];
if ($agentId) {
    $unassignedSql .= " AND l.assigned_to = :user_id";
    $unap['user_id'] = $agentId;
}
$unassignedSql .= " ORDER BY l.id DESC LIMIT 20";
$unassignedStmt = $pdo->prepare($unassignedSql);
$unassignedStmt->execute($unap);
$unassignedLeads = $unassignedStmt->fetchAll();

include '../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <p class="text-muted mb-0 small">Drag and drop leads between pipeline stages</p>
    </div>
    <a href="<?= BASE_URL ?>modules/leads/add.php" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>Add Lead</a>
</div>

<div class="pipeline-board d-flex gap-3 pb-3" style="min-height:70vh;">
    <?php foreach ($pipelineData as $pd): ?>
    <div class="pipeline-column" data-stage-id="<?= $pd['stage']['id'] ?>">
        <div class="pipeline-header rounded-top p-3 text-white fw-bold d-flex justify-content-between align-items-center" style="background:<?= e($pd['stage']['color']) ?>;">
            <span><?= e($pd['stage']['name']) ?></span>
            <span class="badge bg-white bg-opacity-25 rounded-pill"><?= count($pd['leads']) ?></span>
        </div>
        <div class="pipeline-cards p-2 rounded-bottom" style="background:#f1f5f9;min-height:400px;" 
             ondragover="event.preventDefault();this.classList.add('drag-over')" 
             ondragleave="this.classList.remove('drag-over')" 
             ondrop="dropLead(event,<?= $pd['stage']['id'] ?>)">
            <?php foreach ($pd['leads'] as $lead): ?>
            <div class="pipeline-card card border-0 shadow-sm mb-2" draggable="true" id="lead-<?= $lead['id'] ?>" data-lead-id="<?= $lead['id'] ?>" ondragstart="dragLead(event)">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between mb-1">
                        <a href="<?= BASE_URL ?>modules/leads/view.php?id=<?= $lead['id'] ?>" class="fw-semibold text-dark text-decoration-none small"><?= e($lead['name']) ?></a>
                        <span class="badge <?= getPriorityBadgeClass($lead['priority'] ?? 'Warm') ?>" style="font-size:10px;"><?= e($lead['priority'] ?? '') ?></span>
                    </div>
                    <div class="text-muted" style="font-size:12px;">
                        <i class="bi bi-telephone me-1"></i><?= e($lead['phone']) ?>
                    </div>
                    <?php if ($lead['company']): ?>
                    <div class="text-muted" style="font-size:12px;"><i class="bi bi-building me-1"></i><?= e($lead['company']) ?></div>
                    <?php endif; ?>
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
<div class="card shadow-sm border-0 mt-4">
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
.pipeline-board { overflow-x: hidden; }
.pipeline-column { flex: 1; min-width: 0; }
.pipeline-card { cursor: grab; transition: transform 0.15s, box-shadow 0.15s; border-radius: 10px !important; }
.pipeline-card:active { cursor: grabbing; }
.pipeline-card:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.1) !important; }
.drag-over { background: #dbeafe !important; border: 2px dashed #3b82f6; }
.pipeline-header { font-size: 13px; border-radius: 12px 12px 0 0 !important; }
.pipeline-cards { border-radius: 0 0 12px 12px !important; }
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


