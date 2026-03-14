<?php
$pageTitle = 'Manage Leads';
require_once '../../config/auth.php';
requireLogin();
require_once '../../config/db.php';
require_once '../../models/Lead.php';
require_once '../../models/User.php';

$orgId = getOrgId();
$leadModel = new Lead($pdo);
$userModel = new User($pdo);

// Handle bulk actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['bulk_action'])) {
        $ids = $_POST['lead_ids'] ?? [];
        if (!empty($ids)) {
            switch ($_POST['bulk_action']) {
                case 'delete':
                    $leadModel->bulkDelete($ids);
                    redirect(BASE_URL . 'modules/leads/', count($ids) . ' leads deleted.', 'success');
                    break;
                case 'assign':
                    if (!empty($_POST['bulk_agent'])) {
                        $leadModel->bulkAssign($ids, $_POST['bulk_agent'], getUserId());
                        redirect(BASE_URL . 'modules/leads/', count($ids) . ' leads assigned.', 'success');
                    }
                    break;
                default:
                    // Status change
                    if ($_POST['bulk_action']) {
                        $leadModel->bulkUpdateStatus($ids, $_POST['bulk_action'], getUserId());
                        redirect(BASE_URL . 'modules/leads/', count($ids) . ' leads updated.', 'success');
                    }
            }
        }
    } elseif (isset($_POST['single_assign'])) {
        $leadModel->bulkAssign([$_POST['lead_id']], $_POST['agent_id'] ?: null, getUserId());
        redirect(BASE_URL . 'modules/leads/', 'Lead assigned successfully.', 'success');
    }
}

// Filters
$filters = [
    'search'      => $_GET['search'] ?? '',
    'status'      => $_GET['status'] ?? '',
    'priority'    => $_GET['priority'] ?? '',
    'source'      => $_GET['source'] ?? '',
    'assigned_to' => $_GET['assigned_to'] ?? '',
    'date_from'   => $_GET['date_from'] ?? '',
    'date_to'     => $_GET['date_to'] ?? '',
    'tag_id'          => $_GET['tag_id'] ?? '',
    'facebook_page_id' => $_GET['facebook_page_id'] ?? '',
];

$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 15;
$offset = ($page - 1) * $limit;

$tags = $leadModel->getOrgTags($orgId);
$sources = $leadModel->getSources($orgId);
$fbPages = $leadModel->getFacebookPages($orgId);

// Restrict Agents to only see their own leads
$userRole = getUserRole();
if ($userRole === 'agent') {
    $filters['enforce_assigned_to'] = getUserId();
}

$leads = $leadModel->getAllLeads($orgId, $filters, $limit, $offset);
$totalLeads = $leadModel->getTotalLeadsCount($orgId, $filters);
$totalPages = ceil($totalLeads / $limit);
$sources = $leadModel->getSources($orgId);
$fbPages = $leadModel->getFacebookPages($orgId);

// Fetch agents for the dropdowns
$agentStmt = $pdo->prepare("SELECT id, name FROM users WHERE organization_id = :org AND role IN ('org_admin', 'team_lead', 'agent') AND is_active = 1 ORDER BY name");
$agentStmt->execute(['org' => $orgId]);
$agents = $agentStmt->fetchAll();

include '../../includes/header.php';
?>

<!-- Filter Bar -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-body py-3">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-2">
                <input type="text" class="form-control form-control-sm" name="search" placeholder="Search..." value="<?= e($filters['search']) ?>">
            </div>
            <div class="col-md-2">
                <select class="form-select form-select-sm" name="status">
                    <option value="">All Status</option>
                    <?php foreach (['New Lead','Contacted','Working','Qualified','Processing','Proposal Sent','Follow Up','Negotiation','Not Picked','Done','Closed Won','Closed Lost','Rejected'] as $s): ?>
                        <option value="<?= $s ?>" <?= $filters['status'] === $s ? 'selected' : '' ?>><?= $s ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-1">
                <select class="form-select form-select-sm" name="priority">
                    <option value="">Priority</option>
                    <option value="Hot" <?= $filters['priority']==='Hot'?'selected':'' ?>>🔥 Hot</option>
                    <option value="Warm" <?= $filters['priority']==='Warm'?'selected':'' ?>>☀️ Warm</option>
                    <option value="Cold" <?= $filters['priority']==='Cold'?'selected':'' ?>>❄️ Cold</option>
                </select>
            </div>
            <div class="col-md-2">
                <select class="form-select form-select-sm" name="facebook_page_id">
                    <option value="">All Pages</option>
                    <?php foreach ($fbPages as $fbPage): ?>
                        <option value="<?= $fbPage['page_id'] ?>" <?= $filters['facebook_page_id'] == $fbPage['page_id'] ? 'selected' : '' ?>><?= e($fbPage['page_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php if ($userRole !== 'agent'): ?>
            <div class="col-md-2">
                <select class="form-select form-select-sm" name="assigned_to">
                    <option value="">All Agents</option>
                    <?php foreach ($agents as $agent): ?>
                        <option value="<?= $agent['id'] ?>" <?= $filters['assigned_to'] == $agent['id'] ? 'selected' : '' ?>><?= e($agent['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            <div class="col-md-2">
                <select class="form-select form-select-sm" name="tag_id">
                    <option value="">All Tags</option>
                    <?php foreach ($tags as $tag): ?>
                        <option value="<?= $tag['id'] ?>" <?= $filters['tag_id'] == $tag['id'] ? 'selected' : '' ?>><?= e($tag['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <input type="date" class="form-control form-control-sm" name="date_from" value="<?= e($filters['date_from']) ?>" placeholder="From">
            </div>
            <div class="col-md-2">
                <input type="date" class="form-control form-control-sm" name="date_to" value="<?= e($filters['date_to']) ?>" placeholder="To">
            </div>
            <div class="col-md-1">
                <button type="submit" class="btn btn-primary btn-sm w-100"><i class="bi bi-funnel"></i> Filter</button>
            </div>
        </form>
    </div>
</div>

<!-- Leads Table -->
<div class="card shadow-sm border-0">
    <div class="card-header bg-white border-0 pt-4 d-flex justify-content-between align-items-center">
        <h6 class="fw-bold mb-0"><i class="bi bi-people me-2 text-primary"></i>Leads <span class="badge bg-primary bg-opacity-10 text-primary ms-2"><?= $totalLeads ?></span></h6>
        <div class="d-flex gap-2">
            <a href="<?= BASE_URL ?>modules/leads/export.php?<?= http_build_query($filters) ?>" class="btn btn-outline-success btn-sm"><i class="bi bi-file-earmark-excel me-1"></i>Export to Excel</a>
            <a href="<?= BASE_URL ?>modules/leads/add.php" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>Add Lead</a>
        </div>
    </div>
    <div class="card-body pt-0">
        <form method="POST" id="bulkForm">
            <!-- Bulk Actions Bar -->
            <div class="d-flex align-items-center gap-2 mb-3 p-2 rounded bg-light" id="bulkBar" style="display:none !important;">
                <span class="small fw-semibold text-muted" id="selectedCount">0 selected</span>
                <select name="bulk_action" class="form-select form-select-sm" style="width:160px;">
                    <option value="">Bulk Action</option>
                    <?php if ($userRole !== 'agent'): ?>
                        <option value="delete">Delete Selected</option>
                    <?php endif; ?>
                    <optgroup label="Change Status">
                        <option value="New Lead">New Lead</option>
                        <option value="Working">Working</option>
                        <option value="Follow Up">Follow Up</option>
                        <option value="Done">Done</option>
                        <option value="Rejected">Rejected</option>
                    </optgroup>
                </select>
                <?php if ($userRole !== 'agent'): ?>
                <select name="bulk_agent" class="form-select form-select-sm" style="width:160px;">
                    <option value="">Assign To...</option>
                    <?php foreach ($agents as $a): ?>
                        <option value="<?= $a['id'] ?>"><?= e($a['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php endif; ?>
                <button type="submit" class="btn btn-dark btn-sm" onclick="return confirm('Are you sure?')"><i class="bi bi-check2-all me-1"></i>Apply</button>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th width="30"><input type="checkbox" id="selectAll" class="form-check-input"></th>
                            <th class="text-secondary fw-semibold small text-uppercase">NAME</th>
                            <th class="text-secondary fw-semibold small text-uppercase">NOTES</th>
                            <th width="140" class="text-secondary fw-semibold small text-uppercase">PHONE NUMBER</th>
                            <th width="120" class="text-secondary fw-semibold small text-uppercase">DATE</th>
                            <th width="140" class="text-secondary fw-semibold small text-uppercase">ASSIGNED TO</th>
                            <th width="100" class="text-secondary fw-semibold small text-uppercase">STATUS</th>
                            <th width="60" class="text-secondary fw-semibold small text-uppercase">ACTIONS</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($leads as $lead): ?>
                        <tr>
                            <td><input type="checkbox" name="lead_ids[]" value="<?= $lead['id'] ?>" class="form-check-input lead-check"></td>
                            <td>
                                <a href="<?= BASE_URL ?>modules/leads/view.php?id=<?= $lead['id'] ?>" class="fw-bold text-dark text-decoration-none" style="font-size: 14.5px;">
                                    <i class="bi bi-stars text-primary me-1" style="font-size: 12px;"></i><?= e($lead['name']) ?>
                                </a>
                                <?php if ($lead['company']): ?>
                                <br><small class="text-muted ps-3"><?= e($lead['company']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td class="small text-muted">
                                <?php 
                                $previewNote = $lead['note'] ?? '';
                                $previewNote = trim(str_replace("--- Facebook Lead Form Data ---", "", $previewNote));
                                $previewNote = str_replace("\n", " • ", $previewNote);
                                if (empty($previewNote) && $lead['source'] === 'facebook_ads') {
                                    $previewNote = "Facebook Lead via " . ($lead['meta_campaign'] ?? 'Unknown');
                                }
                                ?>
                                <div style="max-width: 280px; font-size: 13px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; color: #6e7a91;" class="fw-normal" title="<?= e($previewNote) ?>">
                                    <?= e($previewNote ?: 'No details available') ?>
                                </div>
                            </td>
                            <td class="fw-semibold text-primary" style="font-size: 14px;"><?= trim(e($lead['phone'] ?: '—')) ?></td>
                            <td class="small">
                                <?php 
                                $dt = strtotime($lead['created_at']);
                                echo '<div class="fw-semibold text-dark">' . date('M d, Y', $dt) . '</div>';
                                echo '<div class="text-muted" style="font-size: 11px;">' . date('h:i A', $dt) . '</div>';
                                ?>
                            </td>
                            <td>
                                <?php if ($userRole !== 'agent'): ?>
                                <form method="POST" action="" class="m-0 p-0">
                                    <input type="hidden" name="lead_id" value="<?= $lead['id'] ?>">
                                    <div class="d-flex align-items-center">
                                        <div class="bg-primary text-white rounded me-2 d-flex align-items-center justify-content-center" style="width:24px; height:24px;">
                                            <i class="bi bi-person-check small"></i>
                                        </div>
                                        <select name="agent_id" class="form-select border-0 bg-transparent text-primary fw-semibold p-0 m-0" onchange="this.form.submit()" style="cursor:pointer; box-shadow: none; font-size: 13.5px; width: auto;">
                                            <option value="">Unassigned</option>
                                            <?php foreach ($agents as $agent): ?>
                                                <option value="<?= $agent['id'] ?>" <?= $lead['assigned_to'] == $agent['id'] ? 'selected' : '' ?>>
                                                    <?= e($agent['name']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <input type="hidden" name="single_assign" value="1">
                                </form>
                                <?php else: ?>
                                    <span class="text-muted fw-semibold small">You</span>
                                <?php endif; ?>
                            </td>
                            <td><span class="badge <?= getStatusBadgeClass($lead['status']) ?> rounded-pill px-2 py-1" style="font-size: 11px;"><?= e($lead['status']) ?></span></td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="<?= BASE_URL ?>modules/leads/view.php?id=<?= $lead['id'] ?>" class="btn btn-light border btn-sm text-primary" title="View"><i class="bi bi-eye"></i></a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($leads)): ?>
                        <tr><td colspan="9" class="text-center text-muted py-4">No leads found. <a href="<?= BASE_URL ?>modules/leads/add.php">Add a lead</a></td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </form>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
        <nav class="mt-3">
            <ul class="pagination pagination-sm justify-content-center mb-0">
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                        <a class="page-link" href="?<?= http_build_query(array_merge($filters, ['page' => $i])) ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
        <?php endif; ?>
    </div>
</div>

<script>
document.getElementById('selectAll').addEventListener('change', function() {
    document.querySelectorAll('.lead-check').forEach(cb => cb.checked = this.checked);
    updateBulkBar();
});
document.querySelectorAll('.lead-check').forEach(cb => cb.addEventListener('change', updateBulkBar));

function updateBulkBar() {
    const checked = document.querySelectorAll('.lead-check:checked').length;
    const bar = document.getElementById('bulkBar');
    bar.style.display = checked > 0 ? 'flex' : 'none';
    bar.style.setProperty('display', checked > 0 ? 'flex' : 'none', 'important');
    document.getElementById('selectedCount').textContent = checked + ' selected';
}
</script>

<?php include '../../includes/footer.php'; ?>


