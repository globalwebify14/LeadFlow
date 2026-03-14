<?php
$pageTitle = 'Automation Sequences';
require_once '../../config/auth.php';
requireLogin();
require_once '../../config/db.php';
if (!in_array(getUserRole(), ['org_owner', 'org_admin'])) {
    redirect(BASE_URL . 'modules/dashboard/', 'No permission.', 'danger');
}
require_once '../../models/Automation.php';

$orgId = getOrgId();
$automation = new Automation($pdo);

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_sequence'])) {
        $name = trim($_POST['name']);
        if ($name) {
            $automation->createSequence($orgId, $name);
            redirect(BASE_URL . 'modules/automation/', 'Sequence created.', 'success');
        }
    }
    if (isset($_POST['delete_sequence'])) {
        $automation->deleteSequence((int)$_POST['id']);
        redirect(BASE_URL . 'modules/automation/', 'Sequence deleted.', 'success');
    }
}

$sequences = $automation->getSequences($orgId);

include '../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0">Automation Sequences</h4>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createModal">
        <i class="bi bi-plus-lg me-2"></i>New Sequence
    </button>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4">Sequence Name</th>
                        <th>Status</th>
                        <th>Created At</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sequences as $s): ?>
                    <tr>
                        <td class="ps-4">
                            <a href="edit.php?id=<?= $s['id'] ?>" class="fw-bold text-decoration-none text-dark"><?= e($s['name']) ?></a>
                        </td>
                        <td>
                            <span class="badge rounded-pill <?= $s['is_active'] ? 'bg-success' : 'bg-secondary' ?> bg-opacity-10 text-<?= $s['is_active'] ? 'success' : 'secondary' ?> px-3">
                                <?= $s['is_active'] ? 'Active' : 'Inactive' ?>
                            </span>
                        </td>
                        <td><?= formatDate($s['created_at']) ?></td>
                        <td class="text-end pe-4">
                            <a href="edit.php?id=<?= $s['id'] ?>" class="btn btn-sm btn-light border me-1"><i class="bi bi-pencil"></i></a>
                            <form action="" method="POST" class="d-inline" onsubmit="return confirm('Delete this sequence?')">
                                <input type="hidden" name="id" value="<?= $s['id'] ?>">
                                <button type="submit" name="delete_sequence" class="btn btn-sm btn-light border text-danger"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($sequences)): ?>
                    <tr>
                        <td colspan="4" class="text-center py-5 text-muted">
                            <i class="bi bi-robot fs-1 d-block mb-3 opacity-25"></i>
                            No automation sequences found. Create one to get started.
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Create Modal -->
<div class="modal fade" id="createModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="" method="POST" class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">New Sequence</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="create_sequence" value="1">
                <div class="mb-3">
                    <label class="form-label">Sequence Name</label>
                    <input type="text" class="form-control" name="name" placeholder="e.g. New Lead Welcome" required>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary px-4">Create</button>
            </div>
        </form>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
