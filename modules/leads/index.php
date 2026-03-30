<?php
$pageTitle = 'Manage Leads';
require_once '../../config/auth.php';
requireLogin();
require_once '../../config/db.php';

// MODULE ACCESS CHECK
if (!hasModuleAccess('leads')) {
    die(header("HTTP/1.0 403 Forbidden") . 'Access Denied: Your organization does not have access to the Leads module.');
}
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

/* ============================================================
   LEADS TABLE — Desktop Overflow Fix
   ============================================================ */
#page-content-wrapper {
    overflow-x: hidden;
}
.leads-table-card {
    overflow: hidden;
}
.table-modern {
    table-layout: fixed;
    width: 100%;
}
.table-modern th,
.table-modern td {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.table-modern td[data-label="Context / Notes"] {
    white-space: normal;
}
.table-modern td[data-label="Lead Name"] .d-flex {
    overflow: hidden;
}

/* ============================================================
   LEADS MODULE — Compact Mobile Cards
   ============================================================ */
@media (max-width: 768px) {
    /* Hero header: stack vertically */
    .page-header-bg {
        padding: 16px !important;
        flex-direction: column !important;
        align-items: flex-start !important;
        gap: 10px;
    }
    .page-header-bg h4 { font-size: 1rem; }
    .page-header-bg p { font-size: 11px !important; display: none; }
    .page-header-bg .d-flex.gap-2 {
        width: 100%;
        flex-wrap: wrap;
        gap: 6px !important;
    }
    .page-header-bg .d-flex.gap-2 .btn,
    .page-header-bg .d-flex.gap-2 a.btn {
        flex: 1;
        font-size: 11px !important;
        padding: 7px 8px !important;
        text-align: center;
        white-space: nowrap;
    }

    /* Filter card */
    .filter-card .card-body { padding: 10px !important; }
    .filter-card .col-6 { flex: 0 0 50% !important; max-width: 50% !important; }
    .filter-input { font-size: 12px !important; padding: 7px 10px !important; }

    /* Leads card header */
    .leads-table-card .card-header {
        padding: 12px 14px 6px !important;
    }

    /* Bulk bar */
    .bulk-bar {
        flex-direction: column !important;
        gap: 8px !important;
        padding: 10px !important;
    }
    .bulk-bar .d-flex.align-items-center.gap-3 { flex-wrap: wrap; width: 100%; }
    .bulk-bar select { width: 100% !important; }
    .bulk-bar button { width: 100%; }

    /* ---- COMPACT CARD REDESIGN ---- */
    .mobile-card-table tr {
        padding: 12px !important;
        margin-bottom: 10px !important;
    }

    /* Hide the verbose labels above each cell */
    .mobile-card-table td::before {
        display: none !important;
    }

    /* All cells: clean vertical stacking, no borders */
    .mobile-card-table td {
        flex-direction: row !important;
        align-items: center !important;
        text-align: left !important;
        padding: 4px 0 !important;
        min-height: unset !important;
        border-bottom: none !important;
    }

    /* Hide checkbox on mobile */
    .mobile-card-table td.checkbox-cell {
        display: none !important;
    }

    /* LEAD NAME cell: compact row with avatar, name, badges */
    .mobile-card-table td[data-label="Name"] {
        padding: 0 0 6px 0 !important;
        border-bottom: 1px solid rgba(0,0,0,0.05) !important;
    }
    .mobile-card-table td[data-label="Name"] .lead-name-modern {
        max-width: 100% !important;
        font-size: 13px !important;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    /* PHONE cell: inline phone + action pills */
    .mobile-card-table td[data-label="Phone"] {
        padding: 6px 0 !important;
    }
    .mobile-card-table td[data-label="Phone"] .d-flex.gap-2.mt-1 {
        margin-top: 6px !important;
    }

    /* Keep Source and Priority hidden on mobile to save space */
    .mobile-card-table td[data-label="Source"],
    .mobile-card-table td[data-label="Priority"] {
        display: none !important;
    }

    /* PIPELINE, STATUS, ASSIGNED: inline badges row */
    .mobile-card-table td[data-label="Pipeline"],
    .mobile-card-table td[data-label="Status"],
    .mobile-card-table td[data-label="Assigned"] {
        display: inline-flex !important;
        padding: 6px 0 !important;
        margin-right: 8px;
    }
    .mobile-card-table td[data-label="Actions"] .btn {
        width: 34px;
        height: 34px;
    }

    /* Pagination */
    .d-flex.justify-content-between.align-items-center.py-4.px-4 {
        flex-direction: column !important;
        gap: 10px;
        padding: 14px !important;
    }
    .pagination-modern .page-link { font-size: 11px; padding: 4px 8px; }

    /* Overflow prevention */
    .table-responsive { overflow-x: hidden !important; min-height: auto !important; }
    .leads-table-card { overflow: hidden; }
}
</style>

<!-- Hero Header -->
<div class="page-header-bg d-flex justify-content-between align-items-center">
    <div style="z-index: 1;">
        <h4 class="fw-bold mb-1 text-white">Lead Management</h4>
        <p class="mb-0 text-white-50" style="font-size: 14px;">View, organize, and assign your leads to drive conversions.</p>
    </div>
    <div class="d-flex gap-2" style="z-index: 1;">
        <?php if (hasModuleAccess('import_leads')): ?>
        <button type="button" class="btn btn-outline-light bg-white bg-opacity-10 border-0 shadow-sm" style="font-weight: 500;" data-bs-toggle="modal" data-bs-target="#importModal">
            <i class="bi bi-file-earmark-excel me-1"></i> Import Leads
        </button>
        <?php endif; ?>
        
        <a href="<?= BASE_URL ?>modules/leads/export.php?<?= http_build_query($filters) ?>" class="btn btn-outline-light bg-white bg-opacity-10 border-0 shadow-sm" style="font-weight: 500;">
            <i class="bi bi-download me-1"></i> Export
        </a>
        
        <?php if (hasModuleAccess('manual_leads')): ?>
        <a href="<?= BASE_URL ?>modules/leads/add.php" class="btn btn-light text-primary shadow-sm" style="font-weight: 600;">
            <i class="bi bi-plus-lg me-1"></i> Add Lead
        </a>
        <?php endif; ?>
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
            
            <div class="col-6 col-md-2">
                <select class="form-select filter-input" name="source">
                    <option value="">Source: All</option>
                    <option value="facebook" <?= $filters['source']==='facebook'?'selected':'' ?>>Facebook Ads</option>
                    <option value="manual" <?= $filters['source']==='manual'?'selected':'' ?>>Manual Entry</option>
                    <option value="import" <?= $filters['source']==='import'?'selected':'' ?>>Excel Import</option>
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
        <div id="bulkForm">
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
                <button type="button" class="btn btn-light btn-sm fw-bold px-3" onclick="applyBulkAction(event)">Apply Action</button>
            </div>

            <div class="table-responsive border-0" style="min-height: 400px; padding-bottom: 2rem;">
                <table class="table table-modern table-hover align-middle mb-0 w-100 mobile-card-table">
                    <thead>
                        <tr>
                            <th width="40" class="ps-4 border-0 text-muted" style="font-size:10px;font-weight:600;letter-spacing:0.5px;"><input type="checkbox" id="selectAll" class="form-check-input custom-checkbox"></th>
                            <th class="border-0 text-muted text-uppercase" style="font-size:10px;font-weight:600;letter-spacing:0.5px; width: 22%;">Name</th>
                            <th class="border-0 text-muted text-uppercase" style="font-size:10px;font-weight:600;letter-spacing:0.5px; width: 14%;">Phone</th>
                            <?php if ($userRole !== 'agent'): ?>
                                <th class="border-0 text-muted text-uppercase" style="font-size:10px;font-weight:600;letter-spacing:0.5px; width: 12%;">Status</th>
                                <th class="border-0 text-muted text-uppercase" style="font-size:10px;font-weight:600;letter-spacing:0.5px; width: 12%;">Pipeline</th>
                            <?php endif; ?>
                            <th class="border-0 text-muted text-uppercase" style="font-size:10px;font-weight:600;letter-spacing:0.5px; width: 8%;">Priority</th>
                            <th class="border-0 text-muted text-uppercase" style="font-size:10px;font-weight:600;letter-spacing:0.5px; width: 8%;">Source</th>
                            <?php if ($userRole !== 'agent'): ?>
                                <th class="border-0 text-muted text-uppercase" style="font-size:10px;font-weight:600;letter-spacing:0.5px; width: 16%;">Assigned</th>
                            <?php endif; ?>
                            <th class="border-0 text-muted text-uppercase text-end pe-4" style="font-size:10px;font-weight:600;letter-spacing:0.5px; width: 8%;">Actions</th>
                        </tr>
                    </thead>
                    <tbody style="border-top: none;">
                        <?php foreach ($leads as $lead): ?>
                        <tr>
                            <td class="ps-4 checkbox-cell border-bottom border-light py-3">
                                <input type="checkbox" name="lead_ids[]" value="<?= $lead['id'] ?>" class="form-check-input custom-checkbox lead-check">
                            </td>
                            
                            <!-- NAME -->
                            <td data-label="Name" class="border-bottom border-light py-3">
                                <div class="fw-bold" style="font-size: 13.5px; color: #1e293b;">
                                    <a href="<?= BASE_URL ?>modules/leads/view.php?id=<?= $lead['id'] ?>" class="text-decoration-none" style="color: inherit;">
                                        <?= e($lead['name']) ?>
                                    </a>
                                </div>
                                <?php if($lead['email']): ?>
                                    <div class="text-muted text-truncate mt-1" style="font-size: 11.5px; max-width: 200px;" title="<?= e($lead['email']) ?>">
                                        <?= e($lead['email']) ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            
                            <!-- PHONE -->
                            <td data-label="Phone" class="border-bottom border-light py-3">
                                <div class="d-flex flex-column gap-1">
                                    <span class="text-dark fw-medium" style="font-size: 12.5px; font-family: monospace; letter-spacing: 0.5px;"><?= trim(e($lead['phone'] ?: '—')) ?></span>
                                    <?php if ($lead['phone']): ?>
                                        <div class="d-flex gap-2 mt-1">
                                            <?php $waPhone = preg_replace('/[^0-9]/', '', $lead['phone']); ?>
                                            <a href="tel:<?= e($lead['phone']) ?>" class="btn btn-sm btn-light border d-inline-flex align-items-center justify-content-center text-primary" style="width:28px;height:28px;font-size:12px;border-radius:8px;" title="Call"><i class="bi bi-telephone-fill"></i></a>
                                            <a href="https://wa.me/<?= e($waPhone) ?>" target="_blank" class="btn btn-sm text-white d-inline-flex align-items-center justify-content-center" style="background-color:#25d366;width:28px;height:28px;font-size:13px;border-radius:8px;" title="WhatsApp"><i class="bi bi-whatsapp"></i></a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </td>

                            <!-- STATUS & PIPELINE -->
                            <?php if ($userRole !== 'agent'): ?>
                            <td data-label="Status" class="border-bottom border-light py-3" style="overflow: visible !important;">
                                <div class="dropdown">
                                    <button class="btn btn-sm border-0 bg-primary bg-opacity-10 text-primary rounded-pill d-inline-flex align-items-center gap-2" type="button" data-bs-toggle="dropdown" style="font-size: 11.5px; padding: 5px 12px; font-weight: 600;">
                                        <span style="width:6px;height:6px;border-radius:50%;background-color:currentColor;"></span>
                                        <?= e($lead['status'] ?: 'New Lead') ?>
                                        <i class="bi bi-chevron-down ms-1" style="font-size: 9px;"></i>
                                    </button>
                                    <ul class="dropdown-menu shadow-sm border-0" style="font-size: 12px; border-radius: 12px; min-width: 140px; z-index: 1050;">
                                        <?php foreach ($pipelineStages as $ps): ?>
                                            <li><a class="dropdown-item py-2" href="#" onclick="event.preventDefault(); document.getElementById('status_<?= $lead['id'] ?>_<?= md5($ps) ?>').submit();"><?= e($ps) ?></a></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                                <?php foreach ($pipelineStages as $ps): ?>
                                <form id="status_<?= $lead['id'] ?>_<?= md5($ps) ?>" method="POST" style="display:none;">
                                    <input type="hidden" name="bulk_action" value="<?= e($ps) ?>">
                                    <input type="hidden" name="lead_ids[]" value="<?= $lead['id'] ?>">
                                </form>
                                <?php endforeach; ?>
                            </td>
                            
                            <td data-label="Pipeline" class="border-bottom border-light py-3">
                                <?php if ($lead['stage_name']): ?>
                                    <span class="badge rounded-pill text-white shadow-sm" style="background: <?= e($lead['stage_color'] ?: '#3b82f6') ?>; font-size: 11.5px; padding: 5px 14px; font-weight: 600;">
                                        <?= e($lead['stage_name']) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="badge rounded-pill text-white shadow-sm bg-primary" style="font-size: 11.5px; padding: 5px 14px; font-weight: 600;">New Lead</span>
                                <?php endif; ?>
                            </td>
                            <?php endif; ?>

                            <!-- PRIORITY -->
                            <td data-label="Priority" class="border-bottom border-light py-3">
                                <?php 
                                    $pri = $lead['priority'] ?: 'Warm';
                                    $priColor = strtolower($pri) === 'hot' ? '#ef4444' : (strtolower($pri) === 'warm' ? '#f59e0b' : '#64748b');
                                ?>
                                <span class="d-inline-flex align-items-center gap-2" style="font-size: 12px; color: <?= $priColor ?>; font-weight: 500;">
                                    <span style="width:6px;height:6px;border-radius:50%;background-color:currentColor;"></span>
                                    <?= ucfirst(strtolower($pri)) ?>
                                </span>
                            </td>

                            <!-- SOURCE -->
                            <td data-label="Source" class="border-bottom border-light py-3">
                                <span class="text-secondary" style="font-size: 12px;">
                                    <?php 
                                        $displaySource = $lead['source'] ?: 'manual'; 
                                        echo $displaySource === 'facebook' ? 'facebook_ads' : e($displaySource);
                                    ?>
                                </span>
                            </td>

                            <!-- ASSIGNED -->
                            <?php if ($userRole !== 'agent'): ?>
                            <td data-label="Assigned" class="border-bottom border-light py-3" style="overflow: visible !important;">
                                <?php 
                                $assignedAgentName = 'Unassigned';
                                foreach($agents as $ag) { if($ag['id'] == $lead['assigned_to']) { $assignedAgentName = $ag['name']; break; } }
                                $initial = strtoupper(substr($assignedAgentName, 0, 1));
                                $colors = ['#f59e0b', '#10b981', '#6366f1', '#ec4899', '#8b5cf6', '#06b6d4', '#eab308', '#ef4444', '#14b8a6', '#f97316', '#3b82f6', '#84cc16'];
                                $agentId = (int)($lead['assigned_to'] ?? 0);
                                $color = $colors[($agentId * 7) % count($colors)];
                                if ($assignedAgentName === 'Unassigned') $color = '#94a3b8';
                                ?>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-light bg-transparent border-0 text-start d-flex align-items-center gap-2 p-0" type="button" data-bs-toggle="dropdown" style="font-size: 12px; font-weight: 500;">
                                        <span class="text-white d-flex align-items-center justify-content-center shadow-sm" style="width: 20px; height: 20px; border-radius: 6px; font-size: 10px; background-color: <?= $color ?>;">
                                            <?= $initial ?>
                                        </span>
                                        <span style="color: #334155;">
                                            <?= e(strtolower($assignedAgentName) === 'unassigned' ? 'Unassigned' : explode(' ', $assignedAgentName)[0]) ?>
                                        </span>
                                        <i class="bi bi-chevron-down text-muted" style="font-size: 9px;"></i>
                                    </button>
                                    <ul class="dropdown-menu shadow-sm border-0" style="font-size: 12px; border-radius: 12px;">
                                        <li><a class="dropdown-item py-2" href="#" onclick="event.preventDefault(); document.getElementById('assign_<?= $lead['id'] ?>_null').submit();">Unassigned</a></li>
                                        <?php foreach ($agents as $agent): ?>
                                            <li><a class="dropdown-item py-2" href="#" onclick="event.preventDefault(); document.getElementById('assign_<?= $lead['id'] ?>_<?= $agent['id'] ?>').submit();"><?= e($agent['name']) ?></a></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                                <form id="assign_<?= $lead['id'] ?>_null" method="POST" style="display:none;"><input type="hidden" name="single_assign" value="1"><input type="hidden" name="lead_id" value="<?= $lead['id'] ?>"><input type="hidden" name="agent_id" value=""></form>
                                <?php foreach ($agents as $agent): ?>
                                <form id="assign_<?= $lead['id'] ?>_<?= $agent['id'] ?>" method="POST" style="display:none;"><input type="hidden" name="single_assign" value="1"><input type="hidden" name="lead_id" value="<?= $lead['id'] ?>"><input type="hidden" name="agent_id" value="<?= $agent['id'] ?>"></form>
                                <?php endforeach; ?>
                            </td>
                            <?php endif; ?>

                            <!-- ACTIONS -->
                            <td class="text-end pe-4 border-bottom border-light py-3" data-label="Actions">
                                <a href="<?= BASE_URL ?>modules/leads/view.php?id=<?= $lead['id'] ?>" class="btn btn-light btn-sm rounded-circle d-inline-flex align-items-center justify-content-center border" style="width: 32px; height: 32px;" title="View Detail">
                                    <i class="bi bi-arrow-right text-primary" style="font-size: 14px;"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <?php if (empty($leads)): ?>
                        <tr>
                            <td colspan="9" class="text-center py-5">
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
        </div>

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

function applyBulkAction(event) {
    event.preventDefault();
    const actionSelect = document.querySelector('select[name="bulk_action"]');
    const agentSelect = document.querySelector('select[name="bulk_agent"]');
    let action = actionSelect ? actionSelect.value : '';
    let agent = agentSelect ? agentSelect.value : '';

    if (!action && agent) action = 'assign';
    if (!action && !agent) {
        alert('Please select an action or an agent.');
        return;
    }

    const checked = Array.from(document.querySelectorAll('.lead-check:checked'));
    if (checked.length === 0) {
        alert("No leads selected.");
        return;
    }

    if (!confirm('Execute bulk action on ' + checked.length + ' selected leads?')) return;

    const form = document.createElement('form');
    form.method = 'POST';
    form.style.display = 'none';

    const inputAction = document.createElement('input');
    inputAction.name = 'bulk_action';
    inputAction.value = action;
    form.appendChild(inputAction);

    if (agent && action === 'assign') {
        const inputAgent = document.createElement('input');
        inputAgent.name = 'bulk_agent';
        inputAgent.value = agent;
        form.appendChild(inputAgent);
    }

    checked.forEach(cb => {
        const inputId = document.createElement('input');
        inputId.name = 'lead_ids[]';
        inputId.value = cb.value;
        form.appendChild(inputId);
    });

    document.body.appendChild(form);
    form.submit();
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

document.addEventListener('DOMContentLoaded', function() {
    // Relocate modal to body to prevent Bootstrap z-index trapping in fixed/relative layout wrappers
    var mod = document.getElementById('importModal');
    if (mod) document.body.appendChild(mod);
});
</script>

<!-- Import Leads Modal -->
<div class="modal fade" id="importModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="<?= BASE_URL ?>modules/leads/import_leads.php" method="POST" enctype="multipart/form-data">
                <div class="modal-header bg-light border-0">
                    <h5 class="modal-title fw-bold"><i class="bi bi-file-earmark-excel text-success me-2"></i>Import Leads</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="alert alert-info border-0 rounded bg-info bg-opacity-10 text-dark" style="font-size:13px;">
                        <i class="bi bi-info-circle-fill text-info me-2"></i><strong>Smart Columns Detection:</strong><br>
                        Upload your CSV or Excel file. Our system will automatically map common column names (like "Customer Name", "Phone", "Mobile", "Email Address").
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Select File (.csv, .xlsx)</label>
                        <input class="form-control" type="file" name="file" accept=".csv, application/vnd.openxmlformats-officedocument.spreadsheetml.sheet, application/vnd.ms-excel" required>
                    </div>
                </div>
                <div class="modal-footer border-0 bg-light">
                    <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4 shadow-sm"><i class="bi bi-upload me-2"></i>Upload File</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>


