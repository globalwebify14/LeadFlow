<?php
$pageTitle = 'Automation Sequences';
require_once '../../config/auth.php';
requireLogin();
require_once '../../config/db.php';

// MODULE ACCESS CHECK
if (!hasModuleAccess('automation')) {
    die(header("HTTP/1.0 403 Forbidden") . 'Access Denied: Your organization does not have access to the Automation module.');
}
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

<style>
@media (max-width: 768px) {
    .auto-header {
        flex-direction: column !important;
        align-items: flex-start !important;
        gap: 10px;
    }
    .auto-header h4 { font-size: 1.1rem; }
    .auto-header .btn { width: 100%; }

    /* Table to card */
    .auto-card-table { display: block; width: 100%; }
    .auto-card-table thead { display: none; }
    .auto-card-table tbody, .auto-card-table tr, .auto-card-table td { display: block; }
    .auto-card-table tr {
        background: #fff;
        border: 1px solid rgba(0,0,0,0.06);
        border-radius: 12px;
        margin-bottom: 10px;
        padding: 12px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.03);
    }
    .auto-card-table td {
        padding: 4px 0 !important;
        border: none !important;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .auto-card-table td::before {
        content: attr(data-label);
        font-weight: 700;
        font-size: 10px;
        text-transform: uppercase;
        color: #94a3b8;
        margin-right: 12px;
        flex-shrink: 0;
    }
    /* Name cell: no label, full width */
    .auto-card-table td.cell-name::before { display: none; }
    .auto-card-table td.cell-name {
        padding-bottom: 6px !important;
        border-bottom: 1px solid rgba(0,0,0,0.05) !important;
    }
    .auto-card-table td.cell-name a { font-size: 14px; }

    /* Actions: right-aligned, no label */
    .auto-card-table td.cell-actions::before { display: none; }
    .auto-card-table td.cell-actions {
        justify-content: flex-end !important;
        padding-top: 6px !important;
        border-top: 1px solid rgba(0,0,0,0.05) !important;
    }

    .table-responsive { overflow-x: hidden !important; }
}
</style>

<div class="d-flex justify-content-between align-items-center mb-4 auto-header">
    <h4 class="fw-bold mb-0">Automation Sequences</h4>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createModal">
        <i class="bi bi-plus-lg me-2"></i>New Sequence
    </button>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 auto-card-table">
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
                        <td class="ps-4 cell-name" data-label="">
                            <a href="edit.php?id=<?= $s['id'] ?>" class="fw-bold text-decoration-none text-dark"><?= e($s['name']) ?></a>
                        </td>
                        <td data-label="Status">
                            <span class="badge rounded-pill <?= $s['is_active'] ? 'bg-success' : 'bg-secondary' ?> bg-opacity-10 text-<?= $s['is_active'] ? 'success' : 'secondary' ?> px-3">
                                <?= $s['is_active'] ? 'Active' : 'Inactive' ?>
                            </span>
                        </td>
                        <td data-label="Created"><?= formatDate($s['created_at']) ?></td>
                        <td class="text-end pe-4 cell-actions">
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
