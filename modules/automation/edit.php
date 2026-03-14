<?php
$pageTitle = 'Edit Sequence';
require_once '../../config/auth.php';
requireLogin();
require_once '../../config/db.php';
require_once '../../models/Automation.php';

if (!isset($_GET['id'])) { redirect(BASE_URL . 'modules/automation/'); }
$id = (int)$_GET['id'];
$orgId = getOrgId();
$automation = new Automation($pdo);

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_sequence'])) {
        $automation->updateSequence($id, [
            'name' => trim($_POST['name']),
            'is_active' => isset($_POST['is_active']) ? 1 : 0
        ]);
        redirect(BASE_URL . 'modules/automation/edit.php?id=' . $id, 'Sequence updated.', 'success');
    }
    if (isset($_POST['add_step'])) {
        $automation->addStep($id, [
            'day_offset' => (int)$_POST['day_offset'],
            'action_type' => $_POST['action_type'],
            'action_data' => trim($_POST['action_data'])
        ]);
        redirect(BASE_URL . 'modules/automation/edit.php?id=' . $id, 'Step added.', 'success');
    }
    if (isset($_POST['delete_step'])) {
        $automation->deleteStep((int)$_POST['step_id']);
        redirect(BASE_URL . 'modules/automation/edit.php?id=' . $id, 'Step deleted.', 'success');
    }
}

$sequence = $automation->getSequenceWithSteps($id);
if (!$sequence) { redirect(BASE_URL . 'modules/automation/'); }

include '../../includes/header.php';
?>

<div class="mb-4">
    <a href="index.php" class="text-decoration-none small"><i class="bi bi-arrow-left me-1"></i>Back to Sequences</a>
</div>

<div class="row">
    <div class="col-lg-4">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white border-0 pt-4"><h6 class="fw-bold">Sequence Settings</h6></div>
            <div class="card-body">
                <form action="" method="POST">
                    <input type="hidden" name="update_sequence" value="1">
                    <div class="mb-3">
                        <label class="form-label">Sequence Name</label>
                        <input type="text" class="form-control" name="name" value="<?= e($sequence['name']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_active" id="is_active" <?= $sequence['is_active'] ? 'checked' : '' ?>>
                            <label class="form-check-link fw-semibold" for="is_active">Active</label>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Save Settings</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white border-0 pt-4 d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0">Automation Steps</h6>
                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addStepModal">
                    <i class="bi bi-plus me-1"></i>Add Step
                </button>
            </div>
            <div class="card-body">
                <div class="automation-steps-list">
                    <?php foreach ($sequence['steps'] as $step): ?>
                    <div class="p-3 border rounded mb-3 bg-light bg-opacity-50">
                        <div class="d-flex justify-content-between">
                            <div>
                                <span class="badge bg-primary rounded-pill mb-2">Day <?= $step['day_offset'] ?></span>
                                <div class="fw-bold"><i class="bi <?= $step['action_type']==='whatsapp'?'bi-whatsapp':($step['action_type']==='task'?'bi-check2-square':'bi-envelope') ?> me-2"></i><?= ucfirst($step['action_type']) ?></div>
                                <div class="text-muted small mt-1"><?= e($step['action_data']) ?></div>
                            </div>
                            <div>
                                <form action="" method="POST" onsubmit="return confirm('Delete this step?')">
                                    <input type="hidden" name="step_id" value="<?= $step['id'] ?>">
                                    <button type="submit" name="delete_step" class="btn btn-sm btn-link text-danger p-0"><i class="bi bi-trash"></i></button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php if (empty($sequence['steps'])): ?>
                    <p class="text-muted text-center py-4">No steps added to this sequence yet.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Step Modal -->
<div class="modal fade" id="addStepModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="" method="POST" class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">Add Automation Step</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="add_step" value="1">
                <div class="mb-3">
                    <label class="form-label">Run on Day</label>
                    <input type="number" class="form-control" name="day_offset" value="0" min="0" required>
                    <div class="form-text">0 = Immediately, 1 = Next day, etc.</div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Action Type</label>
                    <select class="form-select" name="action_type" required>
                        <option value="whatsapp">WhatsApp Message Reminder (Sent to Agent)</option>
                        <option value="task">Create Task</option>
                        <option value="email">Send Email (To Lead)</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Action Data</label>
                    <textarea class="form-control" name="action_data" rows="3" placeholder="Message template or task title" required></textarea>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary px-4">Add Step</button>
            </div>
        </form>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
