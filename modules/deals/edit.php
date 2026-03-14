<?php
$pageTitle = 'Edit Deal';
require_once '../../config/auth.php';
requireLogin();
require_once '../../config/db.php';
require_once '../../models/User.php';
require_once '../../models/Deal.php';




$orgId = getOrgId();
$dealModel = new Deal($pdo);
$userModel = new User($pdo);
if (!isset($_GET['id'])) { redirect(BASE_URL . 'modules/deals/'); }
$deal = $dealModel->getDealById((int)$_GET['id'], $orgId);
if (!$deal) { redirect(BASE_URL . 'modules/deals/', 'Deal not found.', 'danger'); }

$agents = $userModel->getAgents($orgId);
$stagesStmt = $pdo->prepare("SELECT id, name FROM pipeline_stages WHERE organization_id = :org ORDER BY position");
$stagesStmt->execute(['org' => $orgId]);
$stages = $stagesStmt->fetchAll();
$leadsStmt = $pdo->prepare("SELECT id, name, phone FROM leads WHERE organization_id = :org ORDER BY name LIMIT 200");
$leadsStmt->execute(['org' => $orgId]);
$leads = $leadsStmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'name' => trim($_POST['name']),
        'lead_id' => $_POST['lead_id'] ?? '',
        'value' => $_POST['value'] ?? 0,
        'stage_id' => $_POST['stage_id'] ?? '',
        'assigned_to' => $_POST['assigned_to'] ?? '',
        'expected_close_date' => $_POST['expected_close_date'] ?? '',
        'status' => $_POST['status'] ?? 'open',
        'description' => trim($_POST['description'] ?? ''),
        'user_id' => getUserId(),
    ];
    $dealModel->updateDeal($deal['id'], $data);
    redirect(BASE_URL . 'modules/deals/view.php?id=' . $deal['id'], 'Deal updated!', 'success');
}
include '../../includes/header.php';
?>
<div class="row justify-content-center">
<div class="col-lg-8">
<div class="card shadow-sm border-0">
    <div class="card-header bg-white border-0 pt-4"><h5 class="fw-bold mb-0"><i class="bi bi-pencil text-primary me-2"></i>Edit Deal</h5></div>
    <div class="card-body p-4">
        <form method="POST">
            <div class="row g-3">
                <div class="col-md-6"><label class="form-label">Deal Name *</label><input type="text" class="form-control" name="name" value="<?= e($deal['name']) ?>" required></div>
                <div class="col-md-6"><label class="form-label">Value (₹)</label><input type="number" step="0.01" class="form-control" name="value" value="<?= $deal['value'] ?>"></div>
                <div class="col-md-6"><label class="form-label">Linked Lead</label><select class="form-select" name="lead_id"><option value="">None</option><?php foreach ($leads as $l): ?><option value="<?= $l['id'] ?>" <?= $deal['lead_id']==$l['id']?'selected':'' ?>><?= e($l['name']) ?> — <?= e($l['phone']) ?></option><?php endforeach; ?></select></div>
                <div class="col-md-6"><label class="form-label">Stage</label><select class="form-select" name="stage_id"><option value="">Select</option><?php foreach ($stages as $s): ?><option value="<?= $s['id'] ?>" <?= $deal['stage_id']==$s['id']?'selected':'' ?>><?= e($s['name']) ?></option><?php endforeach; ?></select></div>
                <div class="col-md-4"><label class="form-label">Status</label><select class="form-select" name="status"><option value="open" <?= $deal['status']==='open'?'selected':'' ?>>Open</option><option value="won" <?= $deal['status']==='won'?'selected':'' ?>>Won</option><option value="lost" <?= $deal['status']==='lost'?'selected':'' ?>>Lost</option></select></div>
                <div class="col-md-4"><label class="form-label">Assigned To</label><select class="form-select" name="assigned_to"><option value="">Unassigned</option><?php foreach ($agents as $a): ?><option value="<?= $a['id'] ?>" <?= $deal['assigned_to']==$a['id']?'selected':'' ?>><?= e($a['name']) ?></option><?php endforeach; ?></select></div>
                <div class="col-md-4"><label class="form-label">Close Date</label><input type="date" class="form-control" name="expected_close_date" value="<?= $deal['expected_close_date'] ?>"></div>
                <div class="col-12"><label class="form-label">Description</label><textarea class="form-control" name="description" rows="3"><?= e($deal['description']) ?></textarea></div>
            </div>
            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle me-1"></i>Update Deal</button>
                <a href="<?= BASE_URL ?>modules/deals/view.php?id=<?= $deal['id'] ?>" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
</div>
</div>
<?php include '../../includes/footer.php'; ?>


