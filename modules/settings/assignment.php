<?php
$pageTitle = 'Lead Assignment';
require_once '../../config/auth.php';
requireLogin();
requireRole(['super_admin', 'org_owner', 'org_admin', 'team_lead']);
require_once '../../config/db.php';
require_once '../../models/AssignmentRule.php';
require_once '../../models/User.php';



$orgId = getOrgId();
$ruleModel = new AssignmentRule($pdo);
$userModel = new User($pdo);
$agents = $userModel->getAgents($orgId);
$agentWorkload = $ruleModel->getLeadCountPerAgent($orgId);
$unassignedCount = $ruleModel->getUnassignedCount($orgId);

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create_rule') {
        $selectedAgents = $_POST['agents'] ?? [];
        $ruleModel->create([
            'organization_id' => $orgId,
            'name'            => trim($_POST['name']),
            'type'            => $_POST['type'],
            'source_filter'   => trim($_POST['source_filter'] ?? ''),
            'agent_ids'       => json_encode(array_map('intval', $selectedAgents)),
            'is_active'       => isset($_POST['is_active']) ? 1 : 0,
        ]);
        redirect(BASE_URL . 'modules/settings/assignment.php', 'Assignment rule created!', 'success');
    }

    if ($action === 'update_rule') {
        $selectedAgents = $_POST['agents'] ?? [];
        $ruleModel->update((int)$_POST['rule_id'], [
            'name'          => trim($_POST['name']),
            'type'          => $_POST['type'],
            'source_filter' => trim($_POST['source_filter'] ?? ''),
            'agent_ids'     => json_encode(array_map('intval', $selectedAgents)),
            'is_active'     => isset($_POST['is_active']) ? 1 : 0,
        ]);
        redirect(BASE_URL . 'modules/settings/assignment.php', 'Assignment rule updated!', 'success');
    }

    if ($action === 'delete_rule') {
        $ruleModel->delete((int)$_POST['rule_id']);
        redirect(BASE_URL . 'modules/settings/assignment.php', 'Rule deleted.', 'success');
    }

    if ($action === 'toggle_rule') {
        $ruleModel->toggleActive((int)$_POST['rule_id']);
        redirect(BASE_URL . 'modules/settings/assignment.php', 'Rule status toggled.', 'success');
    }

    if ($action === 'reassign') {
        $count = $ruleModel->reassignLeads($orgId, (int)$_POST['from_agent'], (int)$_POST['to_agent']);
        redirect(BASE_URL . 'modules/settings/assignment.php', "$count leads reassigned!", 'success');
    }

    if ($action === 'auto_assign_unassigned') {
        $unassigned = $pdo->prepare("SELECT id, source FROM leads WHERE organization_id = :org AND assigned_to IS NULL");
        $unassigned->execute(['org' => $orgId]);
        $assigned = 0;
        while ($lead = $unassigned->fetch()) {
            $userId = $ruleModel->autoAssignLead($orgId, $lead['source']);
            if ($userId) {
                $pdo->prepare("UPDATE leads SET assigned_to = :uid WHERE id = :id")->execute(['uid' => $userId, 'id' => $lead['id']]);
                $assigned++;
            }
        }
        redirect(BASE_URL . 'modules/settings/assignment.php', "$assigned leads auto-assigned!", 'success');
    }
}

$rules = $ruleModel->getAll($orgId);

include '../../includes/header.php';
?>

<!-- Stats -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-card-icon" style="background:linear-gradient(135deg,#6366f1,#4f46e5);"><i class="bi bi-shuffle"></i></div>
            <div class="stat-card-info">
                <span class="stat-card-label">Active Rules</span>
                <h3 class="stat-card-number"><?= count(array_filter($rules, fn($r) => $r['is_active'])) ?></h3>
                <span class="stat-card-change text-primary"><i class="bi bi-gear me-1"></i>Configured</span>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-card-icon" style="background:linear-gradient(135deg,#f59e0b,#d97706);"><i class="bi bi-person-x"></i></div>
            <div class="stat-card-info">
                <span class="stat-card-label">Unassigned Leads</span>
                <h3 class="stat-card-number"><?= $unassignedCount ?></h3>
                <?php if ($unassignedCount > 0): ?>
                <form method="POST" class="d-inline"><input type="hidden" name="action" value="auto_assign_unassigned">
                    <button type="submit" class="stat-card-change text-warning border-0 bg-transparent p-0 fw-semibold" style="font-size:12px;cursor:pointer;"><i class="bi bi-magic me-1"></i>Auto-Assign</button>
                </form>
                <?php else: ?>
                <span class="stat-card-change text-success"><i class="bi bi-check-circle me-1"></i>All assigned</span>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-card-icon" style="background:linear-gradient(135deg,#10b981,#059669);"><i class="bi bi-people-fill"></i></div>
            <div class="stat-card-info">
                <span class="stat-card-label">Active Agents</span>
                <h3 class="stat-card-number"><?= count($agents) ?></h3>
                <span class="stat-card-change text-success"><i class="bi bi-person-check me-1"></i>Available</span>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Assignment Rules -->
    <div class="col-lg-8">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white border-0 pt-4 d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0"><i class="bi bi-shuffle me-2 text-primary"></i>Assignment Rules</h6>
                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addRuleModal"><i class="bi bi-plus-lg me-1"></i>New Rule</button>
            </div>
            <div class="card-body">
                <?php if (count($rules) > 0): ?>
                    <?php foreach ($rules as $rule): ?>
                    <?php $ruleAgents = json_decode($rule['agent_ids'], true) ?: []; ?>
                    <div class="d-flex align-items-center py-3 border-bottom">
                        <div class="me-3">
                            <div class="rounded-3 d-flex align-items-center justify-content-center" style="width:42px;height:42px;background:<?= $rule['is_active'] ? 'rgba(16,185,129,0.1)' : 'rgba(100,116,139,0.1)' ?>;">
                                <i class="bi bi-<?= $rule['type'] === 'round_robin' ? 'arrow-repeat' : ($rule['type'] === 'source_based' ? 'funnel' : 'person-check') ?> <?= $rule['is_active'] ? 'text-success' : 'text-muted' ?> fs-5"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1">
                            <div class="fw-semibold"><?= e($rule['name']) ?></div>
                            <div class="text-muted small">
                                <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill me-1"><?= ucfirst(str_replace('_', ' ', $rule['type'])) ?></span>
                                <?php if ($rule['source_filter']): ?><span class="badge bg-warning bg-opacity-10 text-warning rounded-pill me-1">Source: <?= e($rule['source_filter']) ?></span><?php endif; ?>
                                <span class="text-muted"><?= count($ruleAgents) ?> agent<?= count($ruleAgents) !== 1 ? 's' : '' ?></span>
                            </div>
                        </div>
                        <div class="d-flex gap-1">
                            <form method="POST" class="d-inline"><input type="hidden" name="action" value="toggle_rule"><input type="hidden" name="rule_id" value="<?= $rule['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-<?= $rule['is_active'] ? 'success' : 'secondary' ?> bg-opacity-10 text-<?= $rule['is_active'] ? 'success' : 'secondary' ?> border-0" title="<?= $rule['is_active'] ? 'Active' : 'Inactive' ?>"><i class="bi bi-<?= $rule['is_active'] ? 'check-circle' : 'x-circle' ?>"></i></button>
                            </form>
                            <button class="btn btn-sm btn-outline-primary border-0" onclick="editRule(<?= htmlspecialchars(json_encode($rule), ENT_QUOTES) ?>)"><i class="bi bi-pencil"></i></button>
                            <form method="POST" class="d-inline" onsubmit="return confirm('Delete this rule?')"><input type="hidden" name="action" value="delete_rule"><input type="hidden" name="rule_id" value="<?= $rule['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger border-0"><i class="bi bi-trash"></i></button>
                            </form>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="bi bi-shuffle text-muted fs-1 d-block mb-2"></i>
                        <span class="text-muted small">No assignment rules yet. Create one to auto-distribute leads.</span>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Reassign Leads -->
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white border-0 pt-4"><h6 class="fw-bold mb-0"><i class="bi bi-arrow-left-right me-2 text-warning"></i>Reassign Leads</h6></div>
            <div class="card-body">
                <form method="POST" class="row g-3 align-items-end">
                    <input type="hidden" name="action" value="reassign">
                    <div class="col-md-4">
                        <label class="form-label small">From Agent</label>
                        <select class="form-select form-select-sm" name="from_agent" required>
                            <option value="">Select agent...</option>
                            <?php foreach ($agents as $a): ?><option value="<?= $a['id'] ?>"><?= e($a['name']) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small">To Agent</label>
                        <select class="form-select form-select-sm" name="to_agent" required>
                            <option value="">Select agent...</option>
                            <?php foreach ($agents as $a): ?><option value="<?= $a['id'] ?>"><?= e($a['name']) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-warning btn-sm w-100" onclick="return confirm('This will move ALL leads from one agent to another. Continue?')"><i class="bi bi-arrow-left-right me-1"></i>Reassign All</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Agent Workload -->
    <div class="col-lg-4">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white border-0 pt-4"><h6 class="fw-bold mb-0"><i class="bi bi-bar-chart me-2 text-info"></i>Agent Workload</h6></div>
            <div class="card-body">
                <?php 
                $maxLeads = max(array_column($agentWorkload, 'lead_count') ?: [1]);
                foreach ($agentWorkload as $aw): 
                    $pct = $maxLeads > 0 ? round(($aw['lead_count'] / $maxLeads) * 100) : 0;
                ?>
                <div class="agent-item">
                    <div class="agent-avatar" style="background:linear-gradient(135deg,#6366f1,#4f46e5);"><?= getInitials($aw['name']) ?></div>
                    <div class="agent-info">
                        <div class="small fw-semibold"><?= e($aw['name']) ?></div>
                        <div class="text-muted" style="font-size:12px;"><?= $aw['lead_count'] ?> leads</div>
                        <div class="progress mt-1" style="height:4px;width:100px;">
                            <div class="progress-bar bg-primary" style="width:<?= $pct ?>%;"></div>
                        </div>
                    </div>
                    <span class="badge bg-primary bg-opacity-10 text-primary fw-bold"><?= $aw['lead_count'] ?></span>
                </div>
                <?php endforeach; ?>
                <?php if (empty($agentWorkload)): ?>
                    <div class="text-center text-muted py-3 small">No agents found</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Add Rule Modal -->
<div class="modal fade" id="addRuleModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0" style="border-radius:16px;">
            <div class="modal-header border-0 pb-0"><h5 class="modal-title fw-bold">New Assignment Rule</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <form method="POST" id="addRuleForm">
                    <input type="hidden" name="action" value="create_rule">
                    <div class="mb-3"><label class="form-label">Rule Name *</label><input type="text" class="form-control" name="name" required placeholder="e.g. Website Leads Round Robin"></div>
                    <div class="mb-3">
                        <label class="form-label">Type *</label>
                        <select class="form-select" name="type" id="ruleType" onchange="toggleSourceFilter()">
                            <option value="round_robin">Round Robin</option>
                            <option value="source_based">Source Based</option>
                            <option value="manual">Manual</option>
                        </select>
                    </div>
                    <div class="mb-3" id="sourceFilterGroup" style="display:none;">
                        <label class="form-label">Source Filter</label>
                        <select class="form-select" name="source_filter">
                            <option value="">Any Source</option>
                            <option value="Website">Website</option>
                            <option value="Facebook">Facebook</option>
                            <option value="Instagram">Instagram</option>
                            <option value="Google">Google</option>
                            <option value="Referral">Referral</option>
                            <option value="Cold Call">Cold Call</option>
                            <option value="WhatsApp">WhatsApp</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Assign To Agents *</label>
                        <div class="border rounded-3 p-2" style="max-height:200px;overflow-y:auto;">
                            <?php foreach ($agents as $a): ?>
                            <div class="form-check mb-1">
                                <input class="form-check-input" type="checkbox" name="agents[]" value="<?= $a['id'] ?>" id="agent_add_<?= $a['id'] ?>">
                                <label class="form-check-label small" for="agent_add_<?= $a['id'] ?>"><?= e($a['name']) ?> <span class="text-muted">(<?= ucfirst($a['role']) ?>)</span></label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="form-check mb-3"><input class="form-check-input" type="checkbox" name="is_active" checked id="ruleActive"><label class="form-check-label" for="ruleActive">Active</label></div>
                    <button type="submit" class="btn btn-primary w-100"><i class="bi bi-plus-circle me-1"></i>Create Rule</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Edit Rule Modal -->
<div class="modal fade" id="editRuleModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0" style="border-radius:16px;">
            <div class="modal-header border-0 pb-0"><h5 class="modal-title fw-bold">Edit Assignment Rule</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <form method="POST" id="editRuleForm">
                    <input type="hidden" name="action" value="update_rule">
                    <input type="hidden" name="rule_id" id="editRuleId">
                    <div class="mb-3"><label class="form-label">Rule Name *</label><input type="text" class="form-control" name="name" id="editRuleName" required></div>
                    <div class="mb-3">
                        <label class="form-label">Type *</label>
                        <select class="form-select" name="type" id="editRuleType" onchange="toggleSourceFilterEdit()">
                            <option value="round_robin">Round Robin</option>
                            <option value="source_based">Source Based</option>
                            <option value="manual">Manual</option>
                        </select>
                    </div>
                    <div class="mb-3" id="editSourceFilterGroup" style="display:none;">
                        <label class="form-label">Source Filter</label>
                        <select class="form-select" name="source_filter" id="editSourceFilter">
                            <option value="">Any Source</option>
                            <option value="Website">Website</option>
                            <option value="Facebook">Facebook</option>
                            <option value="Instagram">Instagram</option>
                            <option value="Google">Google</option>
                            <option value="Referral">Referral</option>
                            <option value="Cold Call">Cold Call</option>
                            <option value="WhatsApp">WhatsApp</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Assign To Agents *</label>
                        <div class="border rounded-3 p-2" style="max-height:200px;overflow-y:auto;" id="editAgentsList">
                            <?php foreach ($agents as $a): ?>
                            <div class="form-check mb-1">
                                <input class="form-check-input" type="checkbox" name="agents[]" value="<?= $a['id'] ?>" id="agent_edit_<?= $a['id'] ?>">
                                <label class="form-check-label small" for="agent_edit_<?= $a['id'] ?>"><?= e($a['name']) ?></label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="form-check mb-3"><input class="form-check-input" type="checkbox" name="is_active" id="editRuleActive"><label class="form-check-label" for="editRuleActive">Active</label></div>
                    <button type="submit" class="btn btn-primary w-100"><i class="bi bi-check-circle me-1"></i>Update Rule</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function toggleSourceFilter() {
    document.getElementById('sourceFilterGroup').style.display = document.getElementById('ruleType').value === 'source_based' ? 'block' : 'none';
}
function toggleSourceFilterEdit() {
    document.getElementById('editSourceFilterGroup').style.display = document.getElementById('editRuleType').value === 'source_based' ? 'block' : 'none';
}
function editRule(rule) {
    document.getElementById('editRuleId').value = rule.id;
    document.getElementById('editRuleName').value = rule.name;
    document.getElementById('editRuleType').value = rule.type;
    document.getElementById('editSourceFilter').value = rule.source_filter || '';
    document.getElementById('editRuleActive').checked = rule.is_active == 1;
    toggleSourceFilterEdit();
    // Check agents
    const agentIds = JSON.parse(rule.agent_ids || '[]');
    document.querySelectorAll('#editAgentsList input[type=checkbox]').forEach(cb => {
        cb.checked = agentIds.includes(parseInt(cb.value));
    });
    new bootstrap.Modal(document.getElementById('editRuleModal')).show();
}
</script>

<?php include '../../includes/footer.php'; ?>


