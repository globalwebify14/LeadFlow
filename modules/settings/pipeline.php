<?php
$pageTitle = 'Pipeline Settings';
require_once '../../config/auth.php';
requireLogin();
requireRole(['org_owner', 'org_admin']);
require_once '../../config/db.php';
require_once '../../models/ActivityLog.php';

$orgId = getOrgId();
$success = $error = '';

// Handle Reorder/Update/Delete/Add
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];

        if ($action === 'add') {
            $name = trim($_POST['name'] ?? '');
            $color = trim($_POST['color'] ?? '#6366f1');
            if ($name) {
                // Get next position
                $posStmt = $pdo->prepare("SELECT MAX(position) FROM pipeline_stages WHERE organization_id = ?");
                $posStmt->execute([$orgId]);
                $pos = (int)$posStmt->fetchColumn() + 1;

                $stmt = $pdo->prepare("INSERT INTO pipeline_stages (organization_id, name, color, position) VALUES (?, ?, ?, ?)");
                if ($stmt->execute([$orgId, $name, $color, $pos])) {
                    ActivityLog::write($pdo, 'pipeline_stage_added', "Added pipeline stage: $name");
                    $success = "Stage added successfully.";
                }
            } else {
                $error = "Stage name is required.";
            }

        } elseif ($action === 'edit') {
            $id = (int)($_POST['id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $color = trim($_POST['color'] ?? '#000000');
            if ($id && $name) {
                $stmt = $pdo->prepare("UPDATE pipeline_stages SET name = ?, color = ? WHERE id = ? AND organization_id = ?");
                if ($stmt->execute([$name, $color, $id, $orgId])) {
                    ActivityLog::write($pdo, 'pipeline_stage_edited', "Edited pipeline stage: $name");
                    $success = "Stage updated successfully.";
                }
            }

        } elseif ($action === 'delete') {
            $id = (int)($_POST['id'] ?? 0);
            if ($id) {
                // Check if any leads exist with this stage
                $chk = $pdo->prepare("SELECT COUNT(*) FROM leads WHERE pipeline_stage_id = ? AND organization_id = ?");
                $chk->execute([$id, $orgId]);
                if ($chk->fetchColumn() > 0) {
                    $error = "Cannot delete this stage because it is actively used by existing leads. Reassign them first.";
                } else {
                    $pdo->prepare("DELETE FROM pipeline_stages WHERE id = ? AND organization_id = ?")->execute([$id, $orgId]);
                    ActivityLog::write($pdo, 'pipeline_stage_deleted', "Deleted a pipeline stage");
                    $success = "Stage deleted successfully.";
                }
            }

        } elseif ($action === 'reorder') {
            $positions = $_POST['positions'] ?? []; // format: [ id => position ]
            if (!empty($positions) && is_array($positions)) {
                $stmt = $pdo->prepare("UPDATE pipeline_stages SET position = ? WHERE id = ? AND organization_id = ?");
                foreach ($positions as $id => $pos) {
                    $stmt->execute([(int)$pos, (int)$id, $orgId]);
                }
                ActivityLog::write($pdo, 'pipeline_stages_reordered', "Reordered pipeline stages");
                $success = "Stages reordered successfully.";
            }
        }
    }
}

// Fetch current stages
$stmt = $pdo->prepare("SELECT * FROM pipeline_stages WHERE organization_id = ? ORDER BY position ASC");
$stmt->execute([$orgId]);
$stages = $stmt->fetchAll();

include '../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-funnel me-2 text-primary"></i>Pipeline Settings</h4>
        <p class="text-muted small mb-0">Manage your custom lead statuses and pipeline stages</p>
    </div>
    <button class="btn btn-primary fw-semibold rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#addStageModal">
        <i class="bi bi-plus-lg me-1"></i> Add Stage
    </button>
</div>

<?php if ($success): ?>
<div class="alert alert-success border-0 rounded-3"><i class="bi bi-check-circle-fill me-2"></i><?= e($success) ?></div>
<?php endif; ?>
<?php if ($error): ?>
<div class="alert alert-danger border-0 rounded-3"><i class="bi bi-exclamation-triangle-fill me-2"></i><?= e($error) ?></div>
<?php endif; ?>

<div class="card shadow-sm border-0">
    <div class="card-header bg-white border-0 pt-4 pb-0">
        <h6 class="fw-bold"><i class="bi bi-list-ol me-2 text-primary"></i>Pipeline Stages</h6>
        <p class="text-muted small">Drag and drop to reorder. These stages will appear globally in your agent pipelines and dashboards.</p>
    </div>
    <div class="card-body">
        <div class="alert alert-info border-0 rounded-3 py-2 px-3 small d-flex align-items-center">
            <i class="bi bi-info-circle-fill me-2 fs-6"></i>
            Note: Moving a stage changes its order visually everywhere. Deletions are restricted if leads are currently assigned to the stage.
        </div>

        <form method="POST" id="reorderForm">
            <input type="hidden" name="action" value="reorder">
            <div id="sortable-stages" class="list-group mb-4">
                <?php foreach ($stages as $index => $stage): ?>
                <div class="list-group-item d-flex align-items-center py-3 border-start-0 border-end-0 stage-item" style="cursor: grab;" data-id="<?= $stage['id'] ?>">
                    <input type="hidden" name="positions[<?= $stage['id'] ?>]" class="position-input" value="<?= $index + 1 ?>">
                    <i class="bi bi-grip-vertical text-muted me-3"></i>
                    
                    <div style="width: 24px; height: 24px; border-radius: 50%; background: <?= e($stage['color']) ?>; flex-shrink: 0;" class="me-3 shadow-sm border border-white"></div>
                    
                    <div class="flex-grow-1 fw-semibold text-dark fs-6"><?= e($stage['name']) ?></div>
                    
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-sm btn-light border text-primary" data-bs-toggle="modal" data-bs-target="#editStageModal" data-id="<?= $stage['id'] ?>" data-name="<?= e($stage['name']) ?>" data-color="<?= e($stage['color']) ?>">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-light border text-danger" onclick="deleteStage(<?= $stage['id'] ?>, '<?= e(addslashes($stage['name'])) ?>')">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <button type="submit" class="btn btn-dark fw-semibold px-4 rounded-pill" id="saveOrderBtn" style="display: none;">
                Save New Order
            </button>
        </form>
    </div>
</div>

<!-- ADD MODAL -->
<div class="modal fade" id="addStageModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" class="modal-content border-0 shadow-lg" style="border-radius:20px;">
            <div class="modal-header border-0 pt-4 px-4 pb-0">
                <h5 class="fw-bold">Add Pipeline Stage</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body px-4">
                <input type="hidden" name="action" value="add">
                <div class="mb-3">
                    <label class="form-label fw-semibold small">Stage Name</label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold small">Badge Color</label>
                    <input type="color" name="color" class="form-control form-control-color w-100" value="#6366f1" required>
                </div>
            </div>
            <div class="modal-footer border-0 pb-4 px-4">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary rounded-pill px-4">Add Stage</button>
            </div>
        </form>
    </div>
</div>

<!-- EDIT MODAL -->
<div class="modal fade" id="editStageModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" class="modal-content border-0 shadow-lg" style="border-radius:20px;">
            <div class="modal-header border-0 pt-4 px-4 pb-0">
                <h5 class="fw-bold">Edit Stage</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body px-4">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="editStageId">
                <div class="mb-3">
                    <label class="form-label fw-semibold small">Stage Name</label>
                    <input type="text" name="name" id="editStageName" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold small">Badge Color</label>
                    <input type="color" name="color" id="editStageColor" class="form-control form-control-color w-100" required>
                </div>
            </div>
            <div class="modal-footer border-0 pb-4 px-4">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary rounded-pill px-4">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<!-- DELETE FORM (Hidden) -->
<form method="POST" id="deleteForm" style="display:none;">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="id" id="deleteStageId">
</form>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Sortable
    const el = document.getElementById('sortable-stages');
    if (el) {
        new Sortable(el, {
            animation: 150,
            handle: '.stage-item', // use entire row as handle
            ghostClass: 'bg-light',
            onEnd: function (evt) {
                // Update hidden position inputs
                const items = el.querySelectorAll('.stage-item');
                items.forEach((item, index) => {
                    item.querySelector('.position-input').value = index + 1;
                });
                // Show save button
                document.getElementById('saveOrderBtn').style.display = 'inline-block';
            }
        });
    }

    // Modal data binding
    const editModal = document.getElementById('editStageModal');
    if (editModal) {
        editModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            document.getElementById('editStageId').value = button.getAttribute('data-id');
            document.getElementById('editStageName').value = button.getAttribute('data-name');
            document.getElementById('editStageColor').value = button.getAttribute('data-color');
        });
    }
});

function deleteStage(id, name) {
    if (confirm(`Are you sure you want to delete the stage "${name}"? This cannot be undone.`)) {
        document.getElementById('deleteStageId').value = id;
        document.getElementById('deleteForm').submit();
    }
}
</script>

<?php include '../../includes/footer.php'; ?>
