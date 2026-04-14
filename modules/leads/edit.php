<?php
$pageTitle = 'Edit Lead';
require_once '../../config/auth.php';
requireLogin();
require_once '../../config/db.php';
require_once '../../models/User.php';
require_once '../../models/Lead.php';




$orgId = getOrgId();
$leadModel = new Lead($pdo);
$userModel = new User($pdo);

if (!isset($_GET['id'])) { redirect(BASE_URL . 'modules/leads/'); }
$lead = $leadModel->getLeadById((int)$_GET['id'], $orgId);
if (!$lead) { redirect(BASE_URL . 'modules/leads/', 'Lead not found.', 'danger'); }

// Security: Agent can only edit their own leads
if (getUserRole() === 'agent' && $lead['assigned_to'] != getUserId()) {
    redirect(BASE_URL . 'modules/leads/', 'Access denied. You can only edit leads assigned to you.', 'danger');
}

$agents = $userModel->getAgents($orgId);
$tags = $leadModel->getOrgTags($orgId);
$leadTags = array_column($leadModel->getTags($lead['id']), 'id');

// Pipeline stages
$stagesStmt = $pdo->prepare("SELECT id, name FROM pipeline_stages WHERE organization_id = :org ORDER BY position");
$stagesStmt->execute(['org' => $orgId]);
$stages = $stagesStmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'name'        => trim($_POST['name']),
        'phone'       => trim($_POST['phone']),
        'email'       => trim($_POST['email'] ?? ''),
        'company'     => trim($_POST['company'] ?? ''),
        'source'      => trim($_POST['source'] ?? ''),
        'status'      => $_POST['status'] ?? 'New Lead',
        'priority'    => $_POST['priority'] ?? 'Warm',
        'assigned_to' => $_POST['assigned_to'] ?? '',
        'note'        => trim($_POST['note'] ?? ''),
        'tags'        => $_POST['tags'] ?? [],
        'user_id'     => getUserId(),
    ];
    $leadModel->updateLead($lead['id'], $data);

    // Update pipeline stage if provided
    if (isset($_POST['pipeline_stage_id'])) {
        $leadModel->updatePipelineStage($lead['id'], $_POST['pipeline_stage_id'] ?: null, getUserId());
    }

    redirect(BASE_URL . 'modules/leads/view.php?id=' . $lead['id'], 'Lead updated!', 'success');
}
include '../../includes/header.php';
?>
<div class="row justify-content-center"><div class="col-lg-8">
<div class="card shadow-sm border-0">
    <div class="card-header bg-white border-0 pt-4"><h5 class="fw-bold mb-0"><i class="bi bi-pencil text-primary me-2"></i>Edit Lead</h5></div>
    <div class="card-body p-4">
        <form method="POST">
            <div class="row g-3">
                <div class="col-md-6"><label class="form-label">Full Name *</label><input type="text" class="form-control" name="name" value="<?= e($lead['name']) ?>" required></div>
                <div class="col-md-6"><label class="form-label">Phone *</label><input type="text" class="form-control" name="phone" value="<?= e($lead['phone']) ?>" required></div>
                <div class="col-md-6"><label class="form-label">Email</label><input type="email" class="form-control" name="email" value="<?= e($lead['email']) ?>"></div>
                <div class="col-md-6"><label class="form-label">Company</label><input type="text" class="form-control" name="company" value="<?= e($lead['company']) ?>"></div>
                <div class="col-md-6"><label class="form-label">Source</label>
                    <select class="form-select" name="source">
                        <option value="">Select Source</option>
                        <?php foreach (['Website','Meta Ads','Google Ads','Referral','Walk-in','Phone Call','Email','Other'] as $s): ?>
                            <option value="<?= $s ?>" <?= $lead['source'] === $s ? 'selected' : '' ?>><?= $s ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6"><label class="form-label">Status</label>
                    <select class="form-select" name="status">
                        <?php foreach ($stages as $s): ?>
                            <option value="<?= $s['name'] ?>" <?= $lead['status'] === $s['name'] ? 'selected' : '' ?>><?= e($s['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6"><label class="form-label">Priority</label>
                    <select class="form-select" name="priority">
                        <option value="Hot" <?= ($lead['priority']??'') === 'Hot' ? 'selected' : '' ?>>🔥 Hot</option>
                        <option value="Warm" <?= ($lead['priority']??'Warm') === 'Warm' ? 'selected' : '' ?>>☀️ Warm</option>
                        <option value="Cold" <?= ($lead['priority']??'') === 'Cold' ? 'selected' : '' ?>>❄️ Cold</option>
                    </select>
                </div>
                <div class="col-md-12"><label class="form-label">Assign To</label>
                    <select class="form-select" name="assigned_to">
                        <option value="">Unassigned</option>
                        <?php foreach ($agents as $agent): ?>
                            <option value="<?= $agent['id'] ?>" <?= $lead['assigned_to'] == $agent['id'] ? 'selected' : '' ?>><?= e($agent['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12"><label class="form-label">Tags</label>
                    <div class="d-flex flex-wrap gap-2">
                        <?php foreach ($tags as $tag): ?>
                            <label class="btn btn-sm btn-outline-secondary rounded-pill <?= in_array($tag['id'], $leadTags) ? 'active' : '' ?>" style="border-color:<?= e($tag['color']) ?>;color:<?= e($tag['color']) ?>;">
                                <input type="checkbox" name="tags[]" value="<?= $tag['id'] ?>" class="d-none" <?= in_array($tag['id'], $leadTags) ? 'checked' : '' ?>>
                                <?= e($tag['name']) ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="col-12"><label class="form-label">Note</label><textarea class="form-control" name="note" rows="3"><?= e($lead['note']) ?></textarea></div>
            </div>
            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle me-1"></i>Update Lead</button>
                <a href="<?= BASE_URL ?>modules/leads/view.php?id=<?= $lead['id'] ?>" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
</div></div>
<script>document.querySelectorAll('.btn-outline-secondary.rounded-pill').forEach(btn => { btn.addEventListener('click', function() { this.classList.toggle('active'); }); });</script>
<?php include '../../includes/footer.php'; ?>


