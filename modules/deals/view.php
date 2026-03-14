<?php
$pageTitle = 'Deal Details';
require_once '../../config/auth.php';
requireLogin();
require_once '../../config/db.php';
require_once '../../models/Deal.php';



$orgId = getOrgId();
$dealModel = new Deal($pdo);

if (!isset($_GET['id'])) { redirect(BASE_URL . 'modules/deals/'); }
$deal = $dealModel->getDealById((int)$_GET['id'], $orgId);
if (!$deal) { redirect(BASE_URL . 'modules/deals/', 'Deal not found.', 'danger'); }

$activities = $dealModel->getActivities($deal['id']);

include '../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1"><?= e($deal['name']) ?></h4>
        <span class="badge bg-<?= $deal['status']==='won'?'success':($deal['status']==='lost'?'danger':'primary') ?> me-2"><?= ucfirst($deal['status']) ?></span>
        <?php if ($deal['stage_name']): ?>
        <span class="badge rounded-pill" style="background:<?= e($deal['stage_color'] ?? '#6366f1') ?>20;color:<?= e($deal['stage_color'] ?? '#6366f1') ?>;"><?= e($deal['stage_name']) ?></span>
        <?php endif; ?>
    </div>
    <div>
        <a href="<?= BASE_URL ?>modules/deals/edit.php?id=<?= $deal['id'] ?>" class="btn btn-primary btn-sm"><i class="bi bi-pencil me-1"></i>Edit</a>
        <a href="<?= BASE_URL ?>modules/deals/delete.php?id=<?= $deal['id'] ?>" class="btn btn-outline-danger btn-sm" onclick="return confirm('Delete this deal?')"><i class="bi bi-trash"></i></a>
    </div>
</div>

<div class="row g-4">
    <div class="col-md-8">
        <div class="card shadow-sm border-0">
            <div class="card-body p-4">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded"><div class="small text-muted mb-1 fw-semibold">DEAL VALUE</div><div class="fw-bold text-success fs-4"><?= formatCurrency($deal['value']) ?></div></div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded"><div class="small text-muted mb-1 fw-semibold">CLOSE DATE</div><div class="fw-bold"><?= $deal['expected_close_date'] ? formatDate($deal['expected_close_date']) : 'Not set' ?></div></div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded"><div class="small text-muted mb-1 fw-semibold">LINKED LEAD</div><div class="fw-bold"><?php if ($deal['lead_name']): ?><a href="<?= BASE_URL ?>modules/leads/view.php?id=<?= $deal['lead_id'] ?>" class="text-decoration-none"><?= e($deal['lead_name']) ?></a><?php else: ?>None<?php endif; ?></div></div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded"><div class="small text-muted mb-1 fw-semibold">ASSIGNED TO</div><div class="fw-bold"><?= e($deal['agent_name'] ?: 'Unassigned') ?></div></div>
                    </div>
                </div>
                <?php if ($deal['description']): ?>
                <div class="mt-4"><h6 class="fw-bold">Description</h6><p class="text-muted"><?= nl2br(e($deal['description'])) ?></p></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white border-0 pt-4"><h6 class="fw-bold"><i class="bi bi-activity me-2"></i>Activity</h6></div>
            <div class="card-body">
                <?php if (count($activities) > 0): ?>
                <div class="timeline">
                    <?php foreach ($activities as $a): ?>
                    <div class="timeline-item">
                        <div class="timeline-date"><?= formatDateTime($a['created_at']) ?></div>
                        <div class="fw-semibold small mt-1"><?= e($a['description']) ?></div>
                        <?php if ($a['user_name']): ?><div class="text-muted" style="font-size:12px;">by <?= e($a['user_name']) ?></div><?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <p class="text-muted text-center">No activity yet</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php include '../../includes/footer.php'; ?>


