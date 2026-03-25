<?php
$pageTitle = 'Add Lead';
require_once '../../config/auth.php';
requireLogin();
require_once '../../config/db.php';

// MODULE ACCESS CHECK
if (!hasModuleAccess('manual_leads')) {
    die(header("HTTP/1.0 403 Forbidden") . 'Access Denied: Your organization does not have access to Manual Leads entry.');
}
require_once '../../models/User.php';
require_once '../../models/Lead.php';




$orgId = getOrgId();
$leadModel = new Lead($pdo);
$userModel = new User($pdo);
$agents = $userModel->getAgents($orgId);
$tags = $leadModel->getOrgTags($orgId);
$duplicates = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'organization_id' => $orgId,
        'name'        => trim($_POST['name'] ?? ''),
        'phone'       => trim($_POST['phone'] ?? ''),
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

    // Check for duplicates (unless user confirmed)
    if (empty($_POST['confirm_duplicate'])) {
        $duplicates = $leadModel->findDuplicates($orgId, $data['phone'], $data['email']);
    }

    if (empty($duplicates) || !empty($_POST['confirm_duplicate'])) {
        $leadId = $leadModel->addLead($data);
        if ($leadId) {
            redirect(BASE_URL . 'modules/leads/view.php?id=' . $leadId, 'Lead added successfully!', 'success');
        } else {
            $error = 'Failed to add lead.';
        }
    }
}

include '../../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <?php if (!empty($duplicates)): ?>
        <div class="alert alert-warning border-0 shadow-sm" style="border-radius:12px;">
            <h6 class="fw-bold"><i class="bi bi-exclamation-triangle me-2"></i>Possible Duplicates Found</h6>
            <p class="mb-2 small">The following leads have a matching phone number or email:</p>
            <?php foreach ($duplicates as $dup): ?>
                <div class="d-flex align-items-center py-1">
                    <span class="fw-semibold me-2"><?= e($dup['name']) ?></span>
                    <span class="text-muted small"><?= e($dup['phone']) ?> • <?= e($dup['email'] ?: 'No email') ?></span>
                    <a href="<?= BASE_URL ?>modules/leads/view.php?id=<?= $dup['id'] ?>" class="ms-auto btn btn-sm btn-outline-primary">View</a>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <div class="card shadow-sm border-0">
            <div class="card-header bg-white border-0 pt-4">
                <h5 class="fw-bold mb-0"><i class="bi bi-person-plus text-primary me-2"></i>Add New Lead</h5>
            </div>
            <div class="card-body p-4">
                <form method="POST">
                    <?php if (!empty($duplicates)): ?><input type="hidden" name="confirm_duplicate" value="1"><?php endif; ?>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Full Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="name" value="<?= e($data['name'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Phone <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="phone" value="<?= e($data['phone'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" value="<?= e($data['email'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Company</label>
                            <input type="text" class="form-control" name="company" value="<?= e($data['company'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Lead Source</label>
                            <select class="form-select" name="source">
                                <option value="manual" <?= ($data['source'] ?? '') === 'manual' ? 'selected' : '' ?>>Manual</option>
                                <option value="facebook" <?= ($data['source'] ?? '') === 'facebook' ? 'selected' : '' ?>>Facebook Ads</option>
                                <option value="import" <?= ($data['source'] ?? '') === 'import' ? 'selected' : '' ?>>Excel Import</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status">
                                <?php 
                                $stagesStmt = $pdo->prepare("SELECT name FROM pipeline_stages WHERE organization_id = ? ORDER BY position");
                                $stagesStmt->execute([getOrgId()]);
                                $pipelineStages = $stagesStmt->fetchAll(PDO::FETCH_COLUMN);
                                
                                foreach ($pipelineStages as $s): ?>
                                    <option value="<?= $s ?>" <?= ($data['status'] ?? 'New Lead') === $s ? 'selected' : '' ?>><?= $s ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Priority</label>
                            <select class="form-select" name="priority">
                                <option value="Hot" <?= ($data['priority'] ?? '') === 'Hot' ? 'selected' : '' ?>>🔥 Hot</option>
                                <option value="Warm" <?= ($data['priority'] ?? 'Warm') === 'Warm' ? 'selected' : '' ?>>☀️ Warm</option>
                                <option value="Cold" <?= ($data['priority'] ?? '') === 'Cold' ? 'selected' : '' ?>>❄️ Cold</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Assign To</label>
                            <select class="form-select" name="assigned_to">
                                <option value="">Unassigned</option>
                                <?php foreach ($agents as $agent): ?>
                                    <option value="<?= $agent['id'] ?>" <?= ($data['assigned_to'] ?? '') == $agent['id'] ? 'selected' : '' ?>><?= e($agent['name']) ?> (<?= ucfirst($agent['role']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Tags</label>
                            <div class="d-flex flex-wrap gap-2">
                                <?php foreach ($tags as $tag): ?>
                                    <label class="btn btn-sm btn-outline-secondary rounded-pill <?= in_array($tag['id'], $data['tags'] ?? []) ? 'active' : '' ?>" style="border-color:<?= e($tag['color']) ?>;color:<?= e($tag['color']) ?>;">
                                        <input type="checkbox" name="tags[]" value="<?= $tag['id'] ?>" class="d-none" <?= in_array($tag['id'], $data['tags'] ?? []) ? 'checked' : '' ?>>
                                        <?= e($tag['name']) ?>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Note</label>
                            <textarea class="form-control" name="note" rows="3"><?= e($data['note'] ?? '') ?></textarea>
                        </div>
                    </div>
                    <div class="mt-4 d-flex gap-2">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-plus-circle me-1"></i><?= !empty($duplicates) ? 'Add Anyway' : 'Add Lead' ?></button>
                        <a href="<?= BASE_URL ?>modules/leads/" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.querySelectorAll('.btn-outline-secondary.rounded-pill').forEach(btn => {
    btn.addEventListener('click', function() { this.classList.toggle('active'); });
});
</script>

<?php include '../../includes/footer.php'; ?>


