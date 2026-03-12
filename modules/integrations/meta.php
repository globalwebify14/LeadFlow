<?php
$pageTitle = 'Meta Ads Integration';
require_once '../../config/auth.php';
requireLogin();
requireRole(['super_admin', 'org_owner']);
require_once '../../config/db.php';
require_once '../../models/MetaIntegration.php';
require_once '../../models/User.php';


$orgId = getOrgId();
$metaModel = new MetaIntegration($pdo);
$userModel = new User($pdo);
$agents = $userModel->getAgents($orgId);

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $metaModel->create([
            'organization_id' => $orgId,
            'page_name'       => trim($_POST['page_name']),
            'page_id'         => trim($_POST['page_id']),
            'access_token'    => trim($_POST['access_token']),
            'form_id'         => trim($_POST['form_id']),
            'form_name'       => trim($_POST['form_name'] ?? ''),
            'auto_assign_to'  => $_POST['auto_assign_to'] ?: null,
            'is_active'       => isset($_POST['is_active']) ? 1 : 0,
        ]);
        redirect(BASE_URL . 'modules/integrations/meta.php', 'Integration added!', 'success');
    }

    if ($action === 'update') {
        $metaModel->update((int)$_POST['integration_id'], [
            'page_name'      => trim($_POST['page_name']),
            'page_id'        => trim($_POST['page_id']),
            'access_token'   => trim($_POST['access_token']),
            'form_id'        => trim($_POST['form_id']),
            'form_name'      => trim($_POST['form_name'] ?? ''),
            'auto_assign_to' => $_POST['auto_assign_to'] ?: null,
            'is_active'      => isset($_POST['is_active']) ? 1 : 0,
        ]);
        redirect(BASE_URL . 'modules/integrations/meta.php', 'Integration updated!', 'success');
    }

    if ($action === 'delete') {
        $metaModel->delete((int)$_POST['integration_id']);
        redirect(BASE_URL . 'modules/integrations/meta.php', 'Integration deleted.', 'success');
    }

    if ($action === 'toggle') {
        $metaModel->toggleActive((int)$_POST['integration_id']);
        redirect(BASE_URL . 'modules/integrations/meta.php', 'Integration toggled.', 'success');
    }
}

$integrations = $metaModel->getAll($orgId);
$metaLeadCount = $metaModel->getMetaLeadCount($orgId);
$metaLeads = $metaModel->getMetaLeads($orgId, 10);

// Webhook URL for this org
$webhookUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . BASE_URL . 'meta_webhook.php';

include '../../includes/header.php';
?>

<!-- Stats -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-card-icon" style="background:linear-gradient(135deg,#1877F2,#0d65d9);"><i class="bi bi-facebook"></i></div>
            <div class="stat-card-info">
                <span class="stat-card-label">Integrations</span>
                <h3 class="stat-card-number"><?= count($integrations) ?></h3>
                <span class="stat-card-change text-primary"><i class="bi bi-plug me-1"></i>Connected</span>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-card-icon" style="background:linear-gradient(135deg,#E4405F,#c13584);"><i class="bi bi-instagram"></i></div>
            <div class="stat-card-info">
                <span class="stat-card-label">Meta Leads</span>
                <h3 class="stat-card-number"><?= $metaLeadCount ?></h3>
                <span class="stat-card-change text-danger"><i class="bi bi-people-fill me-1"></i>Imported</span>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="stat-card">
            <div class="stat-card-icon" style="background:linear-gradient(135deg,#10b981,#059669);"><i class="bi bi-check-circle"></i></div>
            <div class="stat-card-info">
                <span class="stat-card-label">Active Forms</span>
                <h3 class="stat-card-number"><?= count(array_filter($integrations, fn($i) => $i['is_active'])) ?></h3>
                <span class="stat-card-change text-success"><i class="bi bi-broadcast me-1"></i>Syncing</span>
            </div>
        </div>
    </div>
</div>

<!-- Webhook URL Info -->
<div class="alert border-0 shadow-sm mb-4 d-flex align-items-start" style="border-radius:14px;background:linear-gradient(135deg,#eef2ff,#e0e7ff);border-left:4px solid #6366f1 !important;">
    <div class="rounded-circle d-flex align-items-center justify-content-center me-3 flex-shrink-0" style="width:42px;height:42px;background:rgba(99,102,241,0.15);"><i class="bi bi-webhook text-primary fs-5"></i></div>
    <div>
        <strong>Webhook URL</strong>
        <p class="mb-1 small text-muted">Use this URL in your Meta Developer App webhook settings:</p>
        <code class="d-block bg-white rounded px-3 py-2 small" style="word-break:break-all;"><?= e($webhookUrl) ?></code>
        <p class="mb-0 mt-2 small text-muted"><strong>API Endpoint:</strong> <code>GET /{FORM_ID}/leads</code> — Use your Page Access Token to fetch leads.</p>
    </div>
</div>

<div class="row g-4">
    <!-- Integrations List -->
    <div class="col-lg-8">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white border-0 pt-4 d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0"><i class="bi bi-plug me-2 text-primary"></i>Connected Forms</h6>
                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addIntegrationModal"><i class="bi bi-plus-lg me-1"></i>Add Integration</button>
            </div>
            <div class="card-body">
                <?php if (count($integrations) > 0): ?>
                    <?php foreach ($integrations as $int): ?>
                    <div class="d-flex align-items-center py-3 border-bottom">
                        <div class="me-3">
                            <div class="rounded-3 d-flex align-items-center justify-content-center" style="width:42px;height:42px;background:<?= $int['is_active'] ? 'rgba(24,119,242,0.1)' : 'rgba(100,116,139,0.1)' ?>;">
                                <i class="bi bi-facebook <?= $int['is_active'] ? 'text-primary' : 'text-muted' ?> fs-5"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1">
                            <div class="fw-semibold"><?= e($int['page_name']) ?></div>
                            <div class="text-muted small">
                                <?php if ($int['form_name']): ?><span class="badge bg-info bg-opacity-10 text-info rounded-pill me-1"><?= e($int['form_name']) ?></span><?php endif; ?>
                                <span class="text-muted">Form: <?= e($int['form_id']) ?></span>
                                <?php if ($int['agent_name']): ?> • <span class="text-muted">→ <?= e($int['agent_name']) ?></span><?php endif; ?>
                            </div>
                            <?php if ($int['last_synced_at']): ?>
                            <div class="text-muted" style="font-size:11px;"><i class="bi bi-clock me-1"></i>Last synced: <?= timeAgo($int['last_synced_at']) ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="d-flex gap-1">
                            <form method="POST" class="d-inline"><input type="hidden" name="action" value="toggle"><input type="hidden" name="integration_id" value="<?= $int['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-<?= $int['is_active'] ? 'success' : 'secondary' ?> bg-opacity-10 text-<?= $int['is_active'] ? 'success' : 'secondary' ?> border-0"><i class="bi bi-<?= $int['is_active'] ? 'check-circle' : 'x-circle' ?>"></i></button>
                            </form>
                            <button class="btn btn-sm btn-outline-primary border-0" onclick="editIntegration(<?= htmlspecialchars(json_encode($int), ENT_QUOTES) ?>)"><i class="bi bi-pencil"></i></button>
                            <form method="POST" class="d-inline" onsubmit="return confirm('Delete this integration?')"><input type="hidden" name="action" value="delete"><input type="hidden" name="integration_id" value="<?= $int['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger border-0"><i class="bi bi-trash"></i></button>
                            </form>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="bi bi-facebook text-muted fs-1 d-block mb-2"></i>
                        <div class="text-muted small">No integrations configured yet.</div>
                        <button class="btn btn-primary btn-sm mt-2" data-bs-toggle="modal" data-bs-target="#addIntegrationModal"><i class="bi bi-plus-lg me-1"></i>Connect Meta Lead Ads</button>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Recent Meta Leads -->
        <?php if (count($metaLeads) > 0): ?>
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white border-0 pt-4"><h6 class="fw-bold mb-0"><i class="bi bi-people me-2 text-info"></i>Recent Meta Leads</h6></div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead><tr><th>Name</th><th>Phone</th><th>Source</th><th>Campaign</th><th>Agent</th><th>Date</th></tr></thead>
                        <tbody>
                            <?php foreach ($metaLeads as $ml): ?>
                            <tr style="cursor:pointer;" onclick="window.location='<?= BASE_URL ?>modules/leads/view.php?id=<?= $ml['id'] ?>'">
                                <td class="fw-semibold"><?= e($ml['name']) ?></td>
                                <td class="small"><?= e($ml['phone']) ?></td>
                                <td><span class="badge bg-primary bg-opacity-10 text-primary rounded-pill px-2">Facebook</span></td>
                                <td class="small text-muted"><?= e($ml['meta_campaign'] ?: '—') ?></td>
                                <td class="small text-muted"><?= e($ml['agent_name'] ?: 'Unassigned') ?></td>
                                <td class="small text-muted"><?= formatDate($ml['created_at']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Setup Guide -->
    <div class="col-lg-4">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white border-0 pt-4"><h6 class="fw-bold mb-0"><i class="bi bi-book me-2 text-warning"></i>Setup Guide</h6></div>
            <div class="card-body">
                <div class="d-flex align-items-start mb-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center me-3 flex-shrink-0 text-white fw-bold" style="width:28px;height:28px;font-size:12px;background:#6366f1;">1</div>
                    <div><div class="small fw-semibold">Create Meta App</div><div class="text-muted" style="font-size:12px;">Go to <a href="https://developers.facebook.com" target="_blank">developers.facebook.com</a> and create a new app.</div></div>
                </div>
                <div class="d-flex align-items-start mb-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center me-3 flex-shrink-0 text-white fw-bold" style="width:28px;height:28px;font-size:12px;background:#6366f1;">2</div>
                    <div><div class="small fw-semibold">Get Page Access Token</div><div class="text-muted" style="font-size:12px;">Generate a long-lived page access token with <code>leads_retrieval</code> permission.</div></div>
                </div>
                <div class="d-flex align-items-start mb-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center me-3 flex-shrink-0 text-white fw-bold" style="width:28px;height:28px;font-size:12px;background:#6366f1;">3</div>
                    <div><div class="small fw-semibold">Get Form ID</div><div class="text-muted" style="font-size:12px;">Find your Lead Form ID from Meta Business Suite → Lead Forms.</div></div>
                </div>
                <div class="d-flex align-items-start mb-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center me-3 flex-shrink-0 text-white fw-bold" style="width:28px;height:28px;font-size:12px;background:#6366f1;">4</div>
                    <div><div class="small fw-semibold">Configure Webhook</div><div class="text-muted" style="font-size:12px;">Set the webhook URL above in your Meta App for real-time lead sync.</div></div>
                </div>
                <div class="d-flex align-items-start">
                    <div class="rounded-circle d-flex align-items-center justify-content-center me-3 flex-shrink-0 text-white fw-bold" style="width:28px;height:28px;font-size:12px;background:#10b981;">5</div>
                    <div><div class="small fw-semibold">Add Integration Here</div><div class="text-muted" style="font-size:12px;">Click "Add Integration" and enter your Page ID, Form ID, and Access Token.</div></div>
                </div>
                <hr>
                <div class="small text-muted"><strong>API Example:</strong></div>
                <code class="d-block bg-light rounded px-3 py-2 small mt-1">GET /{FORM_ID}/leads?access_token={TOKEN}</code>
            </div>
        </div>
    </div>
</div>

<!-- Add Integration Modal -->
<div class="modal fade" id="addIntegrationModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0" style="border-radius:16px;">
            <div class="modal-header border-0 pb-0"><h5 class="modal-title fw-bold"><i class="bi bi-facebook me-2 text-primary"></i>Add Meta Integration</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <form method="POST">
                    <input type="hidden" name="action" value="create">
                    <div class="mb-3"><label class="form-label">Facebook Page Name *</label><input type="text" class="form-control" name="page_name" required placeholder="My Business Page"></div>
                    <div class="mb-3"><label class="form-label">Page ID *</label><input type="text" class="form-control" name="page_id" required placeholder="123456789"></div>
                    <div class="mb-3"><label class="form-label">Page Access Token *</label><textarea class="form-control" name="access_token" rows="2" required placeholder="EAA..."></textarea></div>
                    <div class="row g-2 mb-3">
                        <div class="col-6"><label class="form-label">Lead Form ID *</label><input type="text" class="form-control" name="form_id" required placeholder="987654321"></div>
                        <div class="col-6"><label class="form-label">Form Name</label><input type="text" class="form-control" name="form_name" placeholder="Contact Form"></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Auto-Assign To</label>
                        <select class="form-select" name="auto_assign_to">
                            <option value="">Don't auto-assign</option>
                            <?php foreach ($agents as $a): ?><option value="<?= $a['id'] ?>"><?= e($a['name']) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-check mb-3"><input class="form-check-input" type="checkbox" name="is_active" checked id="intActive"><label class="form-check-label" for="intActive">Active</label></div>
                    <button type="submit" class="btn btn-primary w-100"><i class="bi bi-plug me-1"></i>Connect Integration</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Edit Integration Modal -->
<div class="modal fade" id="editIntegrationModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0" style="border-radius:16px;">
            <div class="modal-header border-0 pb-0"><h5 class="modal-title fw-bold">Edit Integration</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <form method="POST">
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="integration_id" id="editIntId">
                    <div class="mb-3"><label class="form-label">Facebook Page Name *</label><input type="text" class="form-control" name="page_name" id="editPageName" required></div>
                    <div class="mb-3"><label class="form-label">Page ID *</label><input type="text" class="form-control" name="page_id" id="editPageId" required></div>
                    <div class="mb-3"><label class="form-label">Page Access Token *</label><textarea class="form-control" name="access_token" id="editAccessToken" rows="2" required></textarea></div>
                    <div class="row g-2 mb-3">
                        <div class="col-6"><label class="form-label">Lead Form ID *</label><input type="text" class="form-control" name="form_id" id="editFormId" required></div>
                        <div class="col-6"><label class="form-label">Form Name</label><input type="text" class="form-control" name="form_name" id="editFormName"></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Auto-Assign To</label>
                        <select class="form-select" name="auto_assign_to" id="editAssignTo">
                            <option value="">Don't auto-assign</option>
                            <?php foreach ($agents as $a): ?><option value="<?= $a['id'] ?>"><?= e($a['name']) ?></option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-check mb-3"><input class="form-check-input" type="checkbox" name="is_active" id="editIntActive"><label class="form-check-label" for="editIntActive">Active</label></div>
                    <button type="submit" class="btn btn-primary w-100"><i class="bi bi-check-circle me-1"></i>Update</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function editIntegration(int) {
    document.getElementById('editIntId').value = int.id;
    document.getElementById('editPageName').value = int.page_name;
    document.getElementById('editPageId').value = int.page_id;
    document.getElementById('editAccessToken').value = int.access_token;
    document.getElementById('editFormId').value = int.form_id;
    document.getElementById('editFormName').value = int.form_name || '';
    document.getElementById('editAssignTo').value = int.auto_assign_to || '';
    document.getElementById('editIntActive').checked = int.is_active == 1;
    new bootstrap.Modal(document.getElementById('editIntegrationModal')).show();
}
</script>

<?php include '../../includes/footer.php'; ?>


