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

<style>
/* Premium SaaS Leads Table Styles */
.page-header-bg {
    background: linear-gradient(135deg, #1e1e2f 0%, #2a2a40 100%);
    border-radius: 16px;
    padding: 24px 32px;
    margin-bottom: 24px;
    color: white;
    box-shadow: 0 10px 30px rgba(0,0,0,0.05);
    position: relative;
    overflow: hidden;
}
.page-header-bg::after {
    content: '';
    position: absolute;
    top: 0; right: 0; bottom: 0; left: 0;
    background: url('data:image/svg+xml;utf8,<svg width="100" height="100" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg"><circle cx="100" cy="0" r="40" fill="rgba(255,255,255,0.03)"/><circle cx="0" cy="100" r="60" fill="rgba(255,255,255,0.02)"/></svg>') no-repeat top right / cover;
    pointer-events: none;
}
.filter-card {
    background: #ffffff;
    border-radius: 12px;
    border: 1px solid rgba(0,0,0,0.05);
    box-shadow: 0 4px 15px rgba(0,0,0,0.02);
}
.filter-input {
    background-color: #f8fafc;
    border: 1px solid #e2e8f0;
    font-size: 13px;
    border-radius: 8px;
    padding: 8px 12px;
    color: #475569;
    transition: all 0.2s;
}
.filter-input:focus {
    background-color: #fff;
    border-color: #6366f1;
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
}
.leads-table-card {
    border-radius: 16px;
    border: 1px solid rgba(0,0,0,0.05);
    box-shadow: 0 8px 30px rgba(0,0,0,0.03);
    overflow: hidden;
}
.table-premium th {
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #94a3b8;
    background-color: #f8fafc;
    border-bottom: 2px solid #e2e8f0;
    padding: 16px;
    font-weight: 600;
}
.table-premium td {
    padding: 16px;
    vertical-align: middle;
    border-bottom: 1px solid #f1f5f9;
    color: #334155;
}
.table-premium tbody tr {
    transition: all 0.2s ease;
    /* Removed translate as it creates a new stacking context that breaks z-index */
}
.table-premium tbody tr:hover {
    background-color: #f8fafc;
    box-shadow: 0 4px 12px rgba(0,0,0,0.02);
}
.lead-avatar {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    background: linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 100%);
    color: #4f46e5;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 14px;
    flex-shrink: 0;
}
.lead-name-link {
    color: #0f172a;
    font-weight: 600;
    font-size: 14.5px;
    text-decoration: none;
    transition: color 0.2s;
}
.lead-name-link:hover {
    color: #4f46e5;
}
.agent-avatar {
    width: 24px;
    height: 24px;
    border-radius: 50%;
    background: #f1f5f9;
    color: #64748b;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 11px;
    font-weight: bold;
    margin-right: 6px;
}
.status-badge {
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
    letter-spacing: 0.3px;
}
.bulk-bar {
    background: #1e293b;
    border-radius: 8px;
    padding: 12px 20px;
    color: white;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    transform: translateY(-10px);
    opacity: 0;
    transition: all 0.3s ease;
}
.bulk-bar.active {
    transform: translateY(0);
    opacity: 1;
}
.custom-checkbox {
    width: 18px;
    height: 18px;
    border-radius: 4px;
    border-color: #cbd5e1;
    cursor: pointer;
}
.custom-checkbox:checked {
    background-color: #4f46e5;
    border-color: #4f46e5;
}
</style>

<!-- Hero Header -->
<div class="page-header-bg d-flex justify-content-between align-items-center">
    <div style="z-index: 1;">
        <h4 class="fw-bold mb-1 text-white">Lead Management</h4>
        <p class="mb-0 text-white-50" style="font-size: 14px;">View, organize, and assign your leads to drive conversions.</p>
    </div>
    <div class="d-flex gap-2" style="z-index: 1;">
        <a href="<?= BASE_URL ?>modules/leads/export.php?<?= http_build_query($filters) ?>" class="btn btn-outline-light bg-white bg-opacity-10 border-0 shadow-sm" style="font-weight: 500;">
            <i class="bi bi-file-earmark-excel me-1"></i> Export
        </a>
        <a href="<?= BASE_URL ?>modules/leads/add.php" class="btn btn-light text-primary shadow-sm" style="font-weight: 600;">
            <i class="bi bi-plus-lg me-1"></i> Add Lead
        </a>
    </div>
</div>

<!-- Advanced Filters -->
<div class="filter-card mb-4">
    <div class="card-body p-3">
        <form method="GET" class="row g-2 align-items-center flex-wrap">
            <div class="col-12 col-md-3">
                <div class="input-group">
                    <span class="input-group-text bg-transparent border-end-0 text-muted"><i class="bi bi-search"></i></span>
                    <input type="text" class="form-control filter-input border-start-0 ps-0" name="search" placeholder="Search name, phone, company..." value="<?= e($filters['search']) ?>">
                </div>
            </div>
            
            <div class="col-6 col-md-2">
                <select class="form-select filter-input" name="status">
                    <option value="">Status: All</option>
                    <?php foreach (['New Lead','Contacted','Working','Qualified','Processing','Proposal Sent','Follow Up','Negotiation','Not Picked','Done','Closed Won','Closed Lost','Rejected'] as $s): ?>
                        <option value="<?= $s ?>" <?= $filters['status'] === $s ? 'selected' : '' ?>><?= $s ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-6 col-md-2">
                <select class="form-select filter-input" name="priority">
                    <option value="">Priority: All</option>
                    <option value="Hot" <?= $filters['priority']==='Hot'?'selected':'' ?>>🔥 Hot</option>
                    <option value="Warm" <?= $filters['priority']==='Warm'?'selected':'' ?>>☀️ Warm</option>
                    <option value="Cold" <?= $filters['priority']==='Cold'?'selected':'' ?>>❄️ Cold</option>
                </select>
            </div>
            
            <?php if ($userRole !== 'agent'): ?>
            <div class="col-6 col-md-2">
                <select class="form-select filter-input" name="assigned_to">
                    <option value="">Agent: All</option>
                    <?php foreach ($agents as $agent): ?>
                        <option value="<?= $agent['id'] ?>" <?= $filters['assigned_to'] == $agent['id'] ? 'selected' : '' ?>><?= e($agent['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>

            <div class="col-6 col-md-2">
                <select class="form-select filter-input" name="facebook_page_id">
                    <option value="">Page: All FB Pages</option>
                    <?php foreach ($fbPages as $fbPage): ?>
                        <option value="<?= $fbPage['page_id'] ?>" <?= $filters['facebook_page_id'] == $fbPage['page_id'] ? 'selected' : '' ?>><?= e($fbPage['page_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-6 col-md-1 ms-auto d-flex">
                <button type="submit" class="btn btn-primary w-100" style="border-radius: 8px;"><i class="bi bi-sliders"></i></button>
            </div>
        </form>
    </div>
</div>

<!-- Leads Datatable -->
<div class="card leads-table-card border-0 bg-white">
    <div class="card-header bg-white border-bottom-0 pt-4 pb-2 d-flex justify-content-between align-items-center">
        <div>
            <span class="fs-5 fw-bold text-dark">All Leads</span>
            <span class="badge bg-primary bg-opacity-10 text-primary ms-2 rounded-pill px-2 py-1 fs-6"><?= $totalLeads ?></span>
        </div>
    </div>
    
    <div class="card-body p-0">
        <form method="POST" id="bulkForm">
            <!-- Sleek Bulk Actions Bar -->
            <div class="bulk-bar d-flex align-items-center justify-content-between mx-3 mb-3" id="bulkBar" style="display:none !important;">
                <div class="d-flex align-items-center gap-3">
                    <span class="fw-semibold badge bg-white bg-opacity-25 text-white" id="selectedCount" style="font-size: 13px;">0 selected</span>
                    <span class="text-white-50 small">Quick actions:</span>
                    <select name="bulk_action" class="form-select form-select-sm bg-dark border-secondary text-white shadow-none" style="width:160px;">
                        <option value="">Select Action...</option>
                        <?php if ($userRole !== 'agent'): ?>
                            <option value="delete">🗑️ Delete Selected</option>
                        <?php endif; ?>
                        <optgroup label="Change Status">
                            <option value="New Lead">⭐ New Lead</option>
                            <option value="Working">⏳ Working</option>
                            <option value="Follow Up">📅 Follow Up</option>
                            <option value="Done">✅ Done</option>
                            <option value="Rejected">❌ Rejected</option>
                        </optgroup>
                    </select>
                    <?php if ($userRole !== 'agent'): ?>
                    <select name="bulk_agent" class="form-select form-select-sm bg-dark border-secondary text-white shadow-none" style="width:160px;">
                        <option value="">👤 Assign To...</option>
                        <?php foreach ($agents as $a): ?>
                            <option value="<?= $a['id'] ?>"><?= e($a['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php endif; ?>
                </div>
                <button type="submit" class="btn btn-light btn-sm fw-bold px-3" onclick="return confirm('Execute bulk action on selected leads?')">Apply Action</button>
            </div>

            <!-- Removed class table-responsive as overflow-x:auto clips absolute dropdowns -->
            <div style="overflow-x: visible;">
                <table class="table table-premium align-middle mb-0 w-100">
                    <thead>
                        <tr>
                            <th width="40" class="ps-4"><input type="checkbox" id="selectAll" class="form-check-input custom-checkbox"></th>
                            <th width="250">Lead Name</th>
                            <th>Contact Info</th>
                            <th>Context / Notes</th>
                            <th width="150">Assignment</th>
                            <th width="120">Status</th>
                            <th width="80" class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($leads as $lead): ?>
                        <tr>
                            <td class="ps-4">
                                <input type="checkbox" name="lead_ids[]" value="<?= $lead['id'] ?>" class="form-check-input custom-checkbox lead-check">
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-3">
                                    <div class="lead-avatar shadow-sm">
                                        <?= strtoupper(substr($lead['name'], 0, 1)) ?>
                                    </div>
                                    <div>
                                        <a href="<?= BASE_URL ?>modules/leads/view.php?id=<?= $lead['id'] ?>" class="lead-name-link d-block">
                                            <?= e($lead['name']) ?>
                                        </a>
                                        <div class="text-muted small mt-1 d-flex align-items-center gap-2">
                                            <span><i class="bi bi-calendar-event me-1"></i><?= date('M d, g:i A', strtotime($lead['created_at'])) ?></span>
                                            <?php if($lead['priority']): ?>
                                                <span class="badge bg-<?= $lead['priority'] === 'Hot' ? 'danger' : ($lead['priority'] === 'Warm' ? 'warning' : 'info') ?> bg-opacity-10 text-<?= $lead['priority'] === 'Hot' ? 'danger' : ($lead['priority'] === 'Warm' ? 'warning' : 'info') ?> py-0 px-1" style="font-size: 10px;"><?= $lead['priority'] ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="text-dark fw-semibold" style="font-size: 13.5px;">
                                    <i class="bi bi-telephone text-primary me-2"></i><?= trim(e($lead['phone'] ?: '—')) ?>
                                </div>
                                <?php if ($lead['email']): ?>
                                <div class="text-muted mt-1" style="font-size: 12px;">
                                    <i class="bi bi-envelope me-2"></i><?= e($lead['email']) ?>
                                </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php 
                                $previewNote = $lead['note'] ?? '';
                                $previewNote = trim(str_replace("--- Facebook Lead Form Data ---", "", $previewNote));
                                $previewNote = str_replace("\n", " • ", $previewNote);
                                if (empty($previewNote) && $lead['source'] === 'facebook_ads') {
                                    $previewNote = "FB Lead: " . ($lead['meta_campaign'] ?? 'Ad Campaign');
                                }
                                ?>
                                <div class="text-muted" style="font-size: 13px; max-width: 250px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="<?= e($previewNote) ?>">
                                    <?= e($previewNote ?: '—') ?>
                                </div>
                                <?php if ($lead['company']): ?>
                                    <div class="text-dark fw-medium mt-1" style="font-size: 12px;"><i class="bi bi-building me-1 text-secondary"></i><?= e($lead['company']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($userRole !== 'agent'): ?>
                                    <?php 
                                    $assignedAgentName = 'Unassigned';
                                    foreach($agents as $ag) { if($ag['id'] == $lead['assigned_to']) { $assignedAgentName = $ag['name']; break; } }
                                    ?>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-light bg-white border shadow-sm w-100 text-start d-flex justify-content-between align-items-center" type="button" data-bs-toggle="dropdown" style="font-size: 12.5px; border-radius: 8px;">
                                            <span class="text-truncate">
                                                <?php if($lead['assigned_to']): ?>
                                                    <span class="agent-avatar"><?= strtoupper(substr($assignedAgentName,0,1)) ?></span><?= e($assignedAgentName) ?>
                                                <?php else: ?>
                                                    <i class="bi bi-person-x text-muted me-1"></i> Unassigned
                                                <?php endif; ?>
                                            </span>
                                            <i class="bi bi-chevron-down text-muted" style="font-size: 10px;"></i>
                                        </button>
                                        <ul class="dropdown-menu shadow-sm border-0" style="font-size: 13px;">
                                            <li><h6 class="dropdown-header">Reassign Lead</h6></li>
                                            <li><a class="dropdown-item" href="#" onclick="event.preventDefault(); document.getElementById('assign_<?= $lead['id'] ?>_null').submit();">Unassigned</a></li>
                                            <?php foreach ($agents as $agent): ?>
                                                <li><a class="dropdown-item <?= $lead['assigned_to'] == $agent['id'] ? 'active bg-primary bg-opacity-10 text-primary fw-bold' : '' ?>" href="#" onclick="event.preventDefault(); document.getElementById('assign_<?= $lead['id'] ?>_<?= $agent['id'] ?>').submit();"><?= e($agent['name']) ?></a></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                    <!-- Hidden assignment forms -->
                                    <form id="assign_<?= $lead['id'] ?>_null" method="POST" style="display:none;"><input type="hidden" name="single_assign" value="1"><input type="hidden" name="lead_id" value="<?= $lead['id'] ?>"><input type="hidden" name="agent_id" value=""></form>
                                    <?php foreach ($agents as $agent): ?>
                                    <form id="assign_<?= $lead['id'] ?>_<?= $agent['id'] ?>" method="POST" style="display:none;"><input type="hidden" name="single_assign" value="1"><input type="hidden" name="lead_id" value="<?= $lead['id'] ?>"><input type="hidden" name="agent_id" value="<?= $agent['id'] ?>"></form>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="d-inline-flex align-items-center bg-light border px-2 py-1 rounded" style="font-size: 12.5px;">
                                        <span class="agent-avatar bg-primary text-white"><?= strtoupper(substr(getUserName(),0,1)) ?></span> You
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge <?= getStatusBadgeClass($lead['status']) ?> status-badge"><?= e($lead['status']) ?></span>
                            </td>
                            <td class="text-end pe-4">
                                <a href="<?= BASE_URL ?>modules/leads/view.php?id=<?= $lead['id'] ?>" class="btn btn-light btn-sm rounded-circle d-inline-flex align-items-center justify-content-center text-primary border" style="width: 32px; height: 32px; transition: all 0.2s;" title="View Lead" onmouseover="this.classList.add('bg-primary', 'text-white')" onmouseout="this.classList.remove('bg-primary', 'text-white')">
                                    <i class="bi bi-arrow-right"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <?php if (empty($leads)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-5">
                                <div class="py-4">
                                    <div class="bg-primary bg-opacity-10 d-inline-flex align-items-center justify-content-center rounded-circle mb-3" style="width: 80px; height: 80px;">
                                        <i class="bi bi-inbox-fill text-primary" style="font-size: 32px;"></i>
                                    </div>
                                    <h5 class="fw-bold text-dark">No leads found</h5>
                                    <p class="text-muted mb-4">Try adjusting your filters or importing new leads.</p>
                                    <a href="<?= BASE_URL ?>modules/leads/add.php" class="btn btn-primary px-4 shadow-sm"><i class="bi bi-plus-lg me-2"></i>Create First Lead</a>
                                </div>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </form>

        <!-- Premium Pagination -->
        <?php if ($totalPages > 1): ?>
        <div class="px-4 py-3 border-top d-flex justify-content-between align-items-center bg-white rounded-bottom">
            <span class="text-muted small">Showing <?= $offset + 1 ?> to <?= min($offset + $limit, $totalLeads) ?> of <?= $totalLeads ?> leads</span>
            <nav>
                <ul class="pagination pagination-sm mb-0">
                    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                        <a class="page-link shadow-none" href="?<?= http_build_query(array_merge($filters, ['page' => $page - 1])) ?>">Previous</a>
                    </li>
                    <?php 
                    for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): 
                    ?>
                        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                            <a class="page-link shadow-none" href="?<?= http_build_query(array_merge($filters, ['page' => $i])) ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                        <a class="page-link shadow-none" href="?<?= http_build_query(array_merge($filters, ['page' => $page + 1])) ?>">Next</a>
                    </li>
                </ul>
            </nav>
        </div>
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
    
    if (checked > 0) {
        bar.style.display = 'flex';
        bar.style.setProperty('display', 'flex', 'important');
        // Small delay to allow display flex to apply before adding opacity class
        setTimeout(() => {
            bar.classList.add('active');
        }, 10);
    } else {
        bar.classList.remove('active');
        setTimeout(() => {
            bar.style.display = 'none';
        }, 300); // match transition duration
    }
    
    document.getElementById('selectedCount').textContent = checked + ' selected';
}

// Fix z-index overlap for assignment dropdowns in table
document.addEventListener('show.bs.dropdown', function (event) {
    let tr = event.target.closest('tr');
    if (tr) {
        tr.style.position = 'relative';
        tr.style.zIndex = '1050';
    }
});
document.addEventListener('hide.bs.dropdown', function (event) {
    let tr = event.target.closest('tr');
    if (tr) {
        tr.style.zIndex = '';
        setTimeout(() => { tr.style.position = ''; }, 300); // Wait for transition
    }
});
</script>

<?php include '../../includes/footer.php'; ?>


