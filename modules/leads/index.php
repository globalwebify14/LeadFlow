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

// Get pipeline stages for status dropdowns/filters
$stages = $leadModel->getOrInitializeStages($orgId);
$pipelineStages = array_column($stages, 'name');

$filterStatus  = $_GET['status'] ?? '';

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
    'status'      => $filterStatus,
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
/* Modern Minimalist Leads Table Styles */
.leads-table-card {
    background: #ffffff;
    border-radius: 12px;
    border: 1px solid #eef2f6;
    box-shadow: 0 4px 24px rgba(0,0,0,0.02);
    overflow: hidden;
}
.table-modern {
    margin-bottom: 0;
}
.table-modern th {
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 1px;
    color: #8a99af;
    background-color: #fcfdfe;
    border-bottom: 1px solid #edf2f7;
    padding: 16px 20px;
    font-weight: 700;
}
.table-modern td {
    padding: 16px 20px;
    vertical-align: middle;
    border-bottom: 1px solid #f8fafc;
    color: #475569;
    background-color: transparent;
}
.table-modern tbody tr {
    transition: background-color 0.2s;
    border-left: 3px solid transparent;
}
.table-modern tbody tr:hover {
    background-color: #f9fbfe;
    border-left-color: #3b82f6;
}
.lead-avatar-small {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: #f1f5f9;
    color: #6366f1;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 14px;
    flex-shrink: 0;
    border: 1px solid #e2e8f0;
}
.lead-name-modern {
    color: #1e293b;
    font-weight: 600;
    font-size: 14.5px;
    text-decoration: none;
    display: block;
    margin-bottom: 2px;
}
.lead-name-modern:hover {
    color: #2563eb;
}
.phone-number {
    color: #1e293b;
    font-weight: 500;
    font-size: 14px;
    letter-spacing: -0.2px;
}
.agent-status-modern {
    appearance: none;
    -webkit-appearance: none;
    background-color: #f1f5f9;
    border: 1px solid #e2e8f0;
    color: #475569;
    padding: 5px 28px 5px 12px;
    border-radius: 20px;
    font-size: 11.5px;
    font-weight: 600;
    cursor: pointer;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 20 20' fill='%2364748b'%3E%3Cpath fill-rule='evenodd' d='M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z' clip-rule='evenodd'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 8px center;
    background-size: 14px 14px;
    transition: all 0.2s;
}
.agent-status-modern:hover {
    background-color: #e2e8f0;
    border-color: #cbd5e1;
}
.priority-badge-hot {
    background: #fff1f2;
    color: #e11d48;
    border: 1px solid #ffe4e6;
    padding: 1px 6px;
    border-radius: 4px;
    font-size: 9px;
    font-weight: 700;
    text-transform: uppercase;
}
.pagination-modern .page-link {
    border: 1px solid #e2e8f0;
    color: #64748b;
    font-size: 12.5px;
    padding: 5px 12px;
    margin: 0 2px;
    border-radius: 6px;
    transition: all 0.2s;
}
.pagination-modern .page-link:hover {
    background-color: #f8fafc;
    color: #2563eb;
    border-color: #bfdbfe;
}
.pagination-modern .page-item.active .page-link {
    background-color: #2563eb;
    color: #ffffff;
    border-color: #2563eb;
    font-weight: 600;
}
.agent-action-pill {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background-color: #ffffff;
    border: 1px solid #e2e8f0;
    color: #64748b;
    transition: all 0.2s ease;
    text-decoration: none;
}
.agent-action-pill:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 6px rgba(0,0,0,0.05);
}
.agent-action-pill.call { color: #2563eb; background-color: #eff6ff; border-color: #dbeafe; }
.agent-action-pill.wa { color: #16a34a; background-color: #f0fdf4; border-color: #dcfce7; }
.agent-action-pill.email { color: #7c3aed; background-color: #f5f3ff; border-color: #ede9fe; }

.btn-add-note-modern {
    color: #3b82f6;
    font-weight: 600;
    font-size: 11.5px;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 0;
    transition: color 0.2s;
}
.btn-add-note-modern:hover {
    color: #1d4ed8;
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
                    <?php foreach ($pipelineStages as $ps): ?>
                        <option value="<?= $ps ?>" <?= $filterStatus === $ps ? 'selected' : '' ?>><?= $ps ?></option>
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
<div class="card leads-table-card border-0 bg-white" id="leadsTableCard">
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
                            <?php foreach ($pipelineStages as $ps): ?>
                                <option value="<?= $ps ?>"><?= $ps ?></option>
                            <?php endforeach; ?>
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
                <table class="table table-modern table-hover align-middle mb-0 w-100">
                    <thead>
                        <tr>
                            <th width="40" class="ps-4"><input type="checkbox" id="selectAll" class="form-check-input custom-checkbox"></th>
                            <th>Lead Name</th>
                            <th>Contact Info</th>
                            <th>Context / Notes</th>
                            <?php if ($userRole !== 'agent'): ?>
                                <th width="120">Pipeline</th>
                            <?php endif; ?>
                            <?php if ($userRole !== 'agent'): ?>
                                <th width="150">Assignment</th>
                            <?php endif; ?>
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
                                    <div class="lead-avatar-small">
                                        <?= strtoupper(substr($lead['name'], 0, 1)) ?>
                                    </div>
                                    <div>
                                        <div class="d-flex align-items-center gap-2 mb-1">
                                            <a href="<?= BASE_URL ?>modules/leads/view.php?id=<?= $lead['id'] ?>" class="lead-name-modern text-truncate" style="max-width: 180px;">
                                                <?= e($lead['name']) ?>
                                            </a>
                                            <?php if($lead['priority']): ?>
                                                <span class="<?= strtolower($lead['priority']) === 'hot' ? 'priority-badge-hot' : 'badge bg-light text-muted border' ?>" style="font-size: 9px;"><?= strtoupper($lead['priority']) ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="text-muted d-flex align-items-center" style="font-size: 11px;">
                                            <i class="bi bi-calendar3 me-1"></i><?= date('M d, Y', strtotime($lead['created_at'])) ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="d-flex flex-column gap-1">
                                    <div class="phone-number">
                                        <?= trim(e($lead['phone'] ?: '—')) ?>
                                    </div>
                                    <?php if($lead['email']): ?>
                                        <div class="text-muted text-truncate" style="font-size: 11px; max-width: 180px;" title="<?= e($lead['email']) ?>">
                                            <?= e($lead['email']) ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($lead['phone']): ?>
                                        <div class="d-flex gap-2 mt-2">
                                            <?php $waPhone = preg_replace('/[^0-9]/', '', $lead['phone']); ?>
                                            <a href="tel:<?= e($lead['phone']) ?>" class="agent-action-pill call" title="Call"><i class="bi bi-telephone"></i></a>
                                            <a href="https://wa.me/<?= e($waPhone) ?>" target="_blank" class="agent-action-pill wa" title="WhatsApp"><i class="bi bi-whatsapp"></i></a>
                                            <?php if($lead['email']): ?>
                                                <a href="mailto:<?= e($lead['email']) ?>" class="agent-action-pill email" title="Email"><i class="bi bi-envelope"></i></a>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <?php 
                                $previewNote = $lead['note'] ?? '';
                                $previewNote = trim(str_replace("--- Facebook Lead Form Data ---", "", $previewNote));
                                $previewNote = preg_replace('/(Full Name|Phone|Email):\s*.*?\n/i', '', $previewNote); // Strip redundant FB fields
                                $previewNote = str_replace("\n", " • ", trim($previewNote));
                                ?>
                                <div class="text-muted mb-1" style="font-size: 12.5px; max-width: 240px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" id="note_text_<?= $lead['id'] ?>" title="<?= e($previewNote) ?>">
                                    <?= e($previewNote ?: '—') ?>
                                </div>
                                <div class="d-flex align-items-center gap-3">
                                    <?php if ($lead['company']): ?>
                                        <div class="text-dark fw-semibold" style="font-size: 11.5px;"><i class="bi bi-building me-1 text-primary-emphasis"></i><?= e($lead['company']) ?></div>
                                    <?php endif; ?>
                                    
                                    <a href="javascript:void(0)" class="btn-add-note-modern" onclick="openQuickNote(<?= $lead['id'] ?>)">
                                        <i class="bi bi-plus-circle"></i> Add Note
                                    </a>
                                </div>
                            </td>
                            <?php if ($userRole !== 'agent'): ?>
                            <td>
                                <?php if ($lead['stage_name']): ?>
                                    <span class="badge rounded-pill text-white" style="background: <?= e($lead['stage_color'] ?: '#64748b') ?>; font-size: 9px; padding: 2px 8px; font-weight: 600;">
                                        <?= e($lead['stage_name']) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted small">—</span>
                                <?php endif; ?>
                            </td>
                            <?php endif; ?>
                            <?php if ($userRole !== 'agent'): ?>
                            <td>
                                <?php 
                                $assignedAgentName = 'Unassigned';
                                foreach($agents as $ag) { if($ag['id'] == $lead['assigned_to']) { $assignedAgentName = $ag['name']; break; } }
                                ?>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-light bg-white border w-100 text-start d-flex justify-content-between align-items-center" type="button" data-bs-toggle="dropdown" style="font-size: 11.5px; border-radius: 6px;">
                                        <span class="text-truncate">
                                            <?php if($lead['assigned_to']): ?>
                                                <span class="badge bg-light text-dark border-0 p-0 me-1" style="width: 18px; height: 18px; display: inline-flex; align-items: center; justify-content: center; border-radius: 50%; font-size: 9px;"><?= strtoupper(substr($assignedAgentName,0,1)) ?></span><?= e($assignedAgentName) ?>
                                            <?php else: ?>
                                                <span class="text-muted">Unassigned</span>
                                            <?php endif; ?>
                                        </span>
                                        <i class="bi bi-chevron-down text-muted" style="font-size: 8px;"></i>
                                    </button>
                                    <ul class="dropdown-menu shadow border-0" style="font-size: 12px;">
                                        <li><a class="dropdown-item" href="#" onclick="event.preventDefault(); document.getElementById('assign_<?= $lead['id'] ?>_null').submit();">Unassigned</a></li>
                                        <?php foreach ($agents as $agent): ?>
                                            <li><a class="dropdown-item" href="#" onclick="event.preventDefault(); document.getElementById('assign_<?= $lead['id'] ?>_<?= $agent['id'] ?>').submit();"><?= e($agent['name']) ?></a></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                                <form id="assign_<?= $lead['id'] ?>_null" method="POST" style="display:none;"><input type="hidden" name="single_assign" value="1"><input type="hidden" name="lead_id" value="<?= $lead['id'] ?>"><input type="hidden" name="agent_id" value=""></form>
                                <?php foreach ($agents as $agent): ?>
                                <form id="assign_<?= $lead['id'] ?>_<?= $agent['id'] ?>" method="POST" style="display:none;"><input type="hidden" name="single_assign" value="1"><input type="hidden" name="lead_id" value="<?= $lead['id'] ?>"><input type="hidden" name="agent_id" value="<?= $agent['id'] ?>"></form>
                                <?php endforeach; ?>
                            </td>
                            <?php endif; ?>
                            <td>
                                <?php if ($userRole === 'agent'): ?>
                                    <select class="agent-status-modern agent-quick-status" data-lead-id="<?= $lead['id'] ?>">
                                        <?php foreach ($pipelineStages as $ps): ?>
                                            <option value="<?= $ps ?>" <?= ($lead['status'] ?? '') === $ps ? 'selected' : '' ?>><?= e($ps) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                <?php else: ?>
                                    <span class="badge <?= getStatusBadgeClass($lead['status']) ?> rounded-pill" style="font-size: 10px; padding: 4px 10px;"><?= e($lead['status']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end pe-4">
                                <a href="<?= BASE_URL ?>modules/leads/view.php?id=<?= $lead['id'] ?>" class="btn btn-light btn-sm rounded-circle d-inline-flex align-items-center justify-content-center border" style="width: 28px; height: 28px;" title="View Detail">
                                    <i class="bi bi-arrow-right text-primary" style="font-size: 12px;"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <?php if (empty($leads)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-5">
                                <div class="py-4">
                                    <div class="bg-primary bg-opacity-10 d-inline-flex align-items-center justify-content-center rounded-circle mb-3" style="width: 60px; height: 60px;">
                                        <i class="bi bi-inbox text-primary" style="font-size: 24px;"></i>
                                    </div>
                                    <h6 class="fw-bold text-dark">No leads found</h6>
                                    <p class="text-muted small mb-0">Try adjusting your filters or importing new leads.</p>
                                </div>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </form>

        <?php if ($totalPages > 1): ?>
        <div class="d-flex justify-content-between align-items-center py-4 px-4 border-top">
            <div class="text-muted" style="font-size: 12px; font-weight: 500;">
                Showing <span class="text-dark"><?= $offset + 1 ?></span> to <span class="text-dark"><?= min($totalLeads, $offset + $limit) ?></span> of <span class="text-dark"><?= $totalLeads ?></span> entries
            </div>
            <nav>
                <ul class="pagination pagination-modern mb-0">
                    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>"><i class="bi bi-chevron-left"></i></a>
                    </li>
                    <li class="page-item active"><a class="page-link" href="#"><?= $page ?></a></li>
                    <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>"><i class="bi bi-chevron-right"></i></a>
                    </li>
                </ul>
            </nav>
        </div>
        <?php else: ?>
            <div class="py-3"></div> <!-- Bottom spacing when no pagination -->
        <?php endif; ?>
        <script>
function rebindLeadEvents() {
    // Select All
    const selectAll = document.getElementById('selectAll');
    if (selectAll) {
        selectAll.addEventListener('change', function() {
            document.querySelectorAll('.lead-check').forEach(cb => cb.checked = this.checked);
            updateBulkBar();
        });
    }
    document.querySelectorAll('.lead-check').forEach(cb => cb.addEventListener('change', updateBulkBar));

    // Agent Quick Actions
    document.querySelectorAll('.agent-quick-status').forEach(select => {
        select.addEventListener('change', function() {
            const leadId = this.getAttribute('data-lead-id');
            const status = this.value;
            const selectEl = this;
            selectEl.disabled = true;
            
            fetch('<?= BASE_URL ?>modules/leads/ajax_agent_actions.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ 'action': 'update_status', 'lead_id': leadId, 'status': status })
            })
            .then(res => res.json())
            .then(data => {
                selectEl.disabled = false;
                if (data.success) {
                    selectEl.style.boxShadow = '0 0 0 0.25rem rgba(25, 135, 84, 0.25)';
                    selectEl.style.borderColor = '#198754';
                    setTimeout(() => { selectEl.style.boxShadow = ''; selectEl.style.borderColor = ''; }, 1000);
                } else { alert(data.message || 'Error updating status'); }
            })
            .catch(err => { selectEl.disabled = false; alert('A network error occurred'); });
        });
    });
}

function updateBulkBar() {
    const checked = document.querySelectorAll('.lead-check:checked').length;
    const bar = document.getElementById('bulkBar');
    if (!bar) return;
    
    if (checked > 0) {
        bar.style.display = 'flex';
        bar.style.setProperty('display', 'flex', 'important');
        setTimeout(() => { bar.classList.add('active'); }, 10);
    } else {
        bar.classList.remove('active');
        setTimeout(() => { bar.style.display = 'none'; }, 300);
    }
    document.getElementById('selectedCount').textContent = checked + ' selected';
}

function refreshLeadData() {
    const card = document.getElementById('leadsTableCard');
    if (!card) return;
    
    card.style.opacity = '0.6';
    card.style.transition = 'opacity 0.3s ease';
    
    fetch(window.location.href)
        .then(res => res.text())
        .then(html => {
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const newContent = doc.getElementById('leadsTableCard');
            if (newContent) {
                card.innerHTML = newContent.innerHTML;
                rebindLeadEvents();
                // Play a subtle notification sound if you like
            }
            card.style.opacity = '1';
        })
        .catch(err => {
            console.error("Refresh failed:", err);
            card.style.opacity = '1';
        });
}

// Initial binding
rebindLeadEvents();

// Fix z-index overlap for assignment dropdowns in table
document.addEventListener('show.bs.dropdown', function (event) {
    let tr = event.target.closest('tr');
    if (tr) { tr.style.position = 'relative'; tr.style.zIndex = '1050'; }
});
document.addEventListener('hide.bs.dropdown', function (event) {
    let tr = event.target.closest('tr');
    if (tr) { tr.style.zIndex = ''; setTimeout(() => { tr.style.position = ''; }, 300); }
});

function openQuickNote(leadId) {
    const noteText = prompt("Add a quick note for this lead:");
    if (noteText && noteText.trim().length > 0) {
        fetch('<?= BASE_URL ?>modules/leads/ajax_agent_actions.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({ 'action': 'add_note', 'lead_id': leadId, 'note': noteText.trim() })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const noteDiv = document.getElementById('note_text_' + leadId);
                if (noteDiv) { noteDiv.textContent = noteText.trim(); noteDiv.title = noteText.trim(); }
            } else { alert(data.message || 'Error adding note'); }
        })
        .catch(err => alert('A network error occurred. Note not saved.'));
    }
}
</script>

<?php include '../../includes/footer.php'; ?>


