<?php
$pageTitle = 'Manage Lead Tags';
require_once '../../config/auth.php';
requireLogin();
requireRole(['super_admin', 'org_owner', 'org_admin']);
require_once '../../config/db.php';

$orgId = getOrgId();

// Handle Create
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    $name = trim($_POST['name']);
    $color = trim($_POST['color']) ?: '#6366f1';
    
    if (!empty($name)) {
        $stmt = $pdo->prepare("INSERT INTO lead_tags (organization_id, name, color) VALUES (:org, :name, :color)");
        $stmt->execute(['org' => $orgId, 'name' => $name, 'color' => $color]);
        redirect('tags.php', 'Tag created successfully.', 'success');
    }
}

// Handle Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    $id = (int)$_POST['id'];
    $name = trim($_POST['name']);
    $color = trim($_POST['color']) ?: '#6366f1';
    
    if (!empty($name) && $id) {
        $stmt = $pdo->prepare("UPDATE lead_tags SET name = :name, color = :color WHERE id = :id AND organization_id = :org");
        $stmt->execute(['name' => $name, 'color' => $color, 'id' => $id, 'org' => $orgId]);
        redirect('tags.php', 'Tag updated successfully.', 'success');
    }
}

// Handle Delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM lead_tags WHERE id = :id AND organization_id = :org");
    $stmt->execute(['id' => $id, 'org' => $orgId]);
    redirect('tags.php', 'Tag deleted successfully.', 'success');
}

// Fetch existing tags
$stmt = $pdo->prepare("SELECT * FROM lead_tags WHERE organization_id = :org ORDER BY name");
$stmt->execute(['org' => $orgId]);
$tags = $stmt->fetchAll();

include '../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-tags-fill me-2 text-primary"></i>Lead Tags</h4>
        <p class="text-muted small mb-0">Create and manage custom tags to categorize your leads.</p>
    </div>
    <button class="btn btn-primary fw-semibold shadow-sm" data-bs-toggle="modal" data-bs-target="#tagModal">
        <i class="bi bi-plus-lg me-1"></i> Create Tag
    </button>
</div>

<div class="card shadow-sm border-0 rounded-4 overflow-hidden bg-white">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light text-muted small text-uppercase fw-semibold">
                    <tr>
                        <th class="ps-4">Preview</th>
                        <th>Name</th>
                        <th>Color Hex</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($tags)): ?>
                        <tr><td colspan="4" class="text-center text-muted py-5"><i class="bi bi-tag fs-1 d-block mb-2 text-black-50"></i>No tags defined yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($tags as $t): ?>
                        <tr>
                            <td class="ps-4">
                                <span class="badge rounded-pill" style="background:<?= e($t['color']) ?>18;color:<?= e($t['color']) ?>;border:1px solid <?= e($t['color']) ?>35;padding:6px 12px;font-size:12px;font-weight:600;">
                                    <i class="bi bi-circle-fill me-1" style="font-size:6px;"></i><?= e($t['name']) ?>
                                </span>
                            </td>
                            <td class="fw-bold text-dark"><?= e($t['name']) ?></td>
                            <td class="text-muted small"><code><?= e($t['color']) ?></code></td>
                            <td class="text-end pe-4">
                                <div class="btn-group btn-group-sm">
                                    <button class="btn btn-outline-primary shadow-sm" title="Edit Tag" 
                                        onclick="editTag(<?= $t['id'] ?>, '<?= e($t['name']) ?>', '<?= e($t['color']) ?>')">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <a href="?delete=<?= $t['id'] ?>" class="btn btn-outline-danger shadow-sm" title="Delete Tag" onclick="return confirm('Delete this tag?');">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Tag Modal -->
<div class="modal fade" id="tagModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" class="modal-content" style="border-radius:20px;border:none;box-shadow:0 10px 40px rgba(0,0,0,0.1);">
            <div class="modal-header border-0 pb-0 pt-4 px-4">
                <h5 class="modal-title fw-bold" id="modalTitle">Create Tag</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body px-4 py-3">
                <input type="hidden" name="action" id="formAction" value="create">
                <input type="hidden" name="id" id="tagId" value="">
                
                <div class="mb-3">
                    <label class="form-label fw-semibold small">Tag Name</label>
                    <input type="text" class="form-control" name="name" id="tagName" required style="border-radius:12px;" placeholder="e.g. VIP Customer">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold small">Color</label>
                    <div class="d-flex align-items-center gap-2">
                        <input type="color" class="form-control form-control-color border-0 p-0" name="color" id="tagColor" value="#6366f1" title="Choose your color" style="width:40px;height:40px;border-radius:10px;">
                        <span class="text-muted small">Pick a color for the badge</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0 pb-4 px-4">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold" style="background:linear-gradient(135deg,#6366f1,#4f46e5);border:none;">Save</button>
            </div>
        </form>
    </div>
</div>

<script>
function editTag(id, name, color) {
    document.getElementById('modalTitle').innerText = 'Edit Tag';
    document.getElementById('formAction').value = 'update';
    document.getElementById('tagId').value = id;
    document.getElementById('tagName').value = name;
    document.getElementById('tagColor').value = color;
    var modal = new bootstrap.Modal(document.getElementById('tagModal'));
    modal.show();
}

document.getElementById('tagModal').addEventListener('hidden.bs.modal', function () {
    document.getElementById('modalTitle').innerText = 'Create Tag';
    document.getElementById('formAction').value = 'create';
    document.getElementById('tagId').value = '';
    document.getElementById('tagName').value = '';
    document.getElementById('tagColor').value = '#6366f1';
});
</script>

<?php include '../../includes/footer.php'; ?>
