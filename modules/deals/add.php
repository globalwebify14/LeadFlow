<?php
$pageTitle = 'Add Deal';
require_once '../../config/auth.php';
requireLogin();
require_once '../../config/db.php';
require_once '../../models/User.php';
require_once '../../models/Deal.php';
require_once '../../models/Lead.php';

$orgId = getOrgId();
$dealModel = new Deal($pdo);
$userModel = new User($pdo);
$leadModel = new Lead($pdo);
$agents = $userModel->getAgents($orgId);

$fromLead = isset($_GET['lead_id']) && !empty($_GET['lead_id']);
$leadData = null;
if ($fromLead) {
    $leadData = $leadModel->getLeadById($_GET['lead_id'], $orgId);
}

// Pipeline stages
$stagesStmt = $pdo->prepare("SELECT id, name FROM pipeline_stages WHERE organization_id = :org ORDER BY position");
$stagesStmt->execute(['org' => $orgId]);
$stages = $stagesStmt->fetchAll();

// Leads for linking
$leadsStmt = $pdo->prepare("SELECT id, name, phone, company FROM leads WHERE organization_id = :org ORDER BY name LIMIT 200");
$leadsStmt->execute(['org' => $orgId]);
$leads = $leadsStmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'organization_id' => $orgId,
        'name' => trim($_POST['name'] ?? ''),
        'lead_id' => $_POST['lead_id'] ?? '',
        'value' => $_POST['value'] ?? 0,
        'stage_id' => $_POST['stage_id'] ?? '',
        'assigned_to' => $_POST['assigned_to'] ?? '',
        'expected_close_date' => $_POST['expected_close_date'] ?? '',
        'description' => trim($_POST['description'] ?? ''),
        'user_id' => getUserId(),
    ];
    $dealId = $dealModel->createDeal($data);
    if ($dealId) {
        redirect(BASE_URL . 'modules/deals/view.php?id=' . $dealId, 'Deal created successfully!', 'success');
    }
}
include '../../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white border-0 pt-4"><h5 class="fw-bold mb-0"><i class="bi bi-trophy text-primary me-2"></i>Create New Deal</h5></div>
            <div class="card-body p-4">
                <form method="POST">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Deal Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="name" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Deal Value (₹)</label>
                            <input type="number" step="0.01" class="form-control" name="value" value="0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Linked Lead</label>
                            <select class="form-select" name="lead_id" <?= $fromLead ? 'style="pointer-events: none; background-color: #f8f9fa;" tabindex="-1"' : '' ?>>
                                <option value="">No linked lead</option>
                                <?php foreach ($leads as $l): ?>
                                    <option value="<?= $l['id'] ?>" <?= ($_GET['lead_id'] ?? '') == $l['id'] ? 'selected' : '' ?>><?= e($l['name']) ?> — <?= e($l['phone']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php if (!$fromLead): ?>
                        <div class="col-md-6">
                            <label class="form-label">Pipeline Stage</label>
                            <select class="form-select" name="stage_id">
                                <option value="">Select Stage</option>
                                <?php foreach ($stages as $s): ?>
                                    <option value="<?= $s['id'] ?>"><?= e($s['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Assigned To</label>
                            <select class="form-select" name="assigned_to">
                                <option value="">Unassigned</option>
                                <?php foreach ($agents as $a): ?>
                                    <option value="<?= $a['id'] ?>"><?= e($a['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php else: ?>
                            <input type="hidden" name="stage_id" value="<?= e($leadData['pipeline_stage_id'] ?? '') ?>">
                            <input type="hidden" name="assigned_to" value="<?= e($leadData['assigned_to'] ?? '') ?>">
                        <?php endif; ?>
                        <div class="col-md-6">
                            <label class="form-label">Expected Close Date</label>
                            <input type="date" class="form-control" name="expected_close_date">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="mt-4 d-flex gap-2">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-plus-circle me-1"></i>Create Deal</button>
                        <a href="<?= BASE_URL ?>modules/deals/" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php include '../../includes/footer.php'; ?>


