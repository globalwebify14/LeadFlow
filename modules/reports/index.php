<?php
$pageTitle = 'Reports & Analytics';
require_once '../../config/auth.php';
requireLogin();
require_once '../../config/db.php';
require_once '../../models/Report.php';
require_once '../../models/User.php';

$orgId = getOrgId();
$reportModel = new Report($pdo);

$userRole = getUserRole();
$isAgent = ($userRole === 'agent');
$agentIdFilter = $isAgent ? getUserId() : ($_GET['agent_id'] ?? null);

// Date filters
$dateFilter = $_GET['date_filter'] ?? 'today';
$dateFrom = $_GET['date_from'] ?? null;
$dateTo = $_GET['date_to'] ?? null;

if ($dateFilter !== 'custom') {
    $dateTo = date('Y-m-d');
    switch ($dateFilter) {
        case 'today':
            $dateFrom = date('Y-m-d');
            break;
        case 'yesterday':
            $dateFrom = date('Y-m-d', strtotime('-1 day'));
            $dateTo = $dateFrom;
            break;
        case 'last_7_days':
            $dateFrom = date('Y-m-d', strtotime('-7 days'));
            break;
        case 'last_30_days':
            $dateFrom = date('Y-m-d', strtotime('-30 days'));
            break;
        case 'this_month':
            $dateFrom = date('Y-m-01');
            break;
        case 'last_month':
            $dateFrom = date('Y-m-01', strtotime('first day of last month'));
            $dateTo = date('Y-m-t', strtotime('last day of last month'));
            break;
        case 'all_time':
            $dateFrom = null;
            $dateTo = null;
            break;
        default:
            $dateFrom = date('Y-m-01'); // this_month
    }
}

// Fetch all agents for dropdown
$agents = [];
if (!$isAgent) {
    $stmtAgents = $pdo->prepare("SELECT id, name FROM users WHERE organization_id = :org AND role IN ('org_admin','team_lead','agent') AND is_active = 1 ORDER BY name");
    $stmtAgents->execute(['org' => $orgId]);
    $agents = $stmtAgents->fetchAll();
}

// Status filter
$statusFilter = $_GET['status_filter'] ?? '';

// Fetch ALL possible statuses in pipeline stage order (same as lead edit form)
try {
    // Primary: pipeline stages ordered by position (exactly as they appear in lead status dropdown)
    $stmtStatuses = $pdo->prepare("
        SELECT name as status FROM pipeline_stages 
        WHERE organization_id = :org AND name IS NOT NULL AND name != ''
        ORDER BY position ASC
    ");
    $stmtStatuses->execute(['org' => $orgId]);
    $allStatuses = $stmtStatuses->fetchAll(PDO::FETCH_COLUMN);

    // We ONLY show the active pipeline stages to keep the reports professional
    // and consistent with your current funnel. Legacy statuses are excluded.
    $allStatuses = $allStatuses;
} catch (Exception $e) {
    $allStatuses = [];
}

// Fetch pipeline stages for dropdown
try {
    $stmtStages = $pdo->prepare("SELECT id, name, color FROM pipeline_stages WHERE organization_id = :org ORDER BY position");
    $stmtStages->execute(['org' => $orgId]);
    $pipelineStages = $stmtStages->fetchAll();
} catch (Exception $e) {
    $pipelineStages = [];
}

// Get metrics
$summary     = $reportModel->getLeadSummary($orgId, $agentIdFilter, $dateFrom, $dateTo, $statusFilter);
$conversion  = $reportModel->getConversionRate($orgId, $agentIdFilter, $dateFrom, $dateTo, $statusFilter);
$leadsBySource = $reportModel->getLeadsBySource($orgId, $agentIdFilter, $dateFrom, $dateTo, $statusFilter);
$leadsByStatus = $reportModel->getLeadsByStatus($orgId, $agentIdFilter, $dateFrom, $dateTo, $statusFilter);
$dealsRev    = $reportModel->getDealsRevenueReport($orgId, $agentIdFilter, $dateFrom, $dateTo, $statusFilter);
$followUps   = $reportModel->getFollowUpStatusReport($orgId, $agentIdFilter, $dateFrom, $dateTo, $statusFilter);

// Status-wise lead breakdown (for the new full status table)
try {
    $sbQuerySql = "SELECT l.status, COUNT(*) as count,
                      SUM(CASE WHEN l.assigned_to IS NOT NULL THEN 1 ELSE 0 END) as assigned,
                      SUM(CASE WHEN l.priority='Hot' THEN 1 ELSE 0 END) as hot,
                      SUM(CASE WHEN l.priority='Warm' THEN 1 ELSE 0 END) as warm,
                      SUM(CASE WHEN l.priority='Cold' THEN 1 ELSE 0 END) as cold
               FROM leads l
               WHERE l.organization_id = :org";
    $sbQueryParams = ['org' => $orgId];
    if ($agentIdFilter) { $sbQuerySql .= " AND l.assigned_to = :uid"; $sbQueryParams['uid'] = $agentIdFilter; }
    if ($dateFrom)      { $sbQuerySql .= " AND DATE(l.created_at) >= :df"; $sbQueryParams['df'] = $dateFrom; }
    if ($dateTo)        { $sbQuerySql .= " AND DATE(l.created_at) <= :dt"; $sbQueryParams['dt'] = $dateTo; }
    $sbQuerySql .= " GROUP BY l.status";
    $stmtSb = $pdo->prepare($sbQuerySql);
    $stmtSb->execute($sbQueryParams);
    $rawStatusBreakdown = $stmtSb->fetchAll(PDO::FETCH_UNIQUE | PDO::FETCH_ASSOC);

    // Merge with $allStatuses to ensure every status shows up, even with 0 counts
    $statusBreakdown = [];
    foreach ($allStatuses as $stName) {
        $statusBreakdown[] = [
            'status'   => $stName,
            'count'    => $rawStatusBreakdown[$stName]['count'] ?? 0,
            'assigned' => $rawStatusBreakdown[$stName]['assigned'] ?? 0,
            'hot'      => $rawStatusBreakdown[$stName]['hot'] ?? 0,
            'warm'     => $rawStatusBreakdown[$stName]['warm'] ?? 0,
            'cold'     => $rawStatusBreakdown[$stName]['cold'] ?? 0,
        ];
    }
} catch (Exception $e) {
    $statusBreakdown = [];
}

// Source-wise breakdown
try {
    $srcSql = "SELECT COALESCE(source,'unknown') as source, COUNT(*) as count FROM leads WHERE organization_id = :org";
    $srcParams = ['org' => $orgId];
    if ($agentIdFilter) { $srcSql .= " AND assigned_to = :uid"; $srcParams['uid'] = $agentIdFilter; }
    if ($dateFrom)      { $srcSql .= " AND DATE(created_at) >= :df"; $srcParams['df'] = $dateFrom; }
    if ($dateTo)        { $srcSql .= " AND DATE(created_at) <= :dt"; $srcParams['dt'] = $dateTo; }
    $srcSql .= " GROUP BY source ORDER BY count DESC";
    $stmtSrc = $pdo->prepare($srcSql);
    $stmtSrc->execute($srcParams);
    $sourceBreakdown = $stmtSrc->fetchAll();
} catch (Exception $e) {
    $sourceBreakdown = [];
}

// Only for Admins
$agentPerf = [];
$leadDist  = [];
$agentResp = [];
$campaigns = [];
if (!$isAgent) {
    $agentPerf = $reportModel->getAgentAdvancedPerformance($orgId, $agentIdFilter, $dateFrom, $dateTo, $statusFilter);
    $leadDist  = $reportModel->getLeadDistribution($orgId, $agentIdFilter, $dateFrom, $dateTo, $statusFilter);
    $agentResp = $reportModel->getAgentResponseTime($orgId, $agentIdFilter, $dateFrom, $dateTo, $statusFilter);
    $campaigns = $reportModel->getFacebookCampaignReport($orgId, $agentIdFilter, $dateFrom, $dateTo, $statusFilter);
}

$monthlyGrowth = $reportModel->getMonthlyGrowth($orgId, $agentIdFilter, $dateFrom, $dateTo, $statusFilter);
$pipelinePerf  = $reportModel->getPipelinePerformance($orgId, $agentIdFilter, $dateFrom, $dateTo, $statusFilter);

// Detailed leads feed — apply status filter if set
$detailedLeads = $reportModel->getDetailedLeadsReport($orgId, $agentIdFilter, $dateFrom, $dateTo, 50, $statusFilter);
$followupsList = $reportModel->getFollowUpsListReport($orgId, $agentIdFilter, $dateFrom, $dateTo, 100, $statusFilter);

include '../../includes/header.php';
?>
<!-- Global Filter Bar -->
<div class="card shadow-sm border-0 mb-4 bg-white">
    <div class="card-body p-3">
        <form method="GET" class="row g-3 align-items-end" id="filterForm">
            <?php if (!$isAgent): ?>
            <div class="col-6 col-lg-2">
                <label class="form-label small fw-semibold text-muted mb-1">Agent</label>
                <select name="agent_id" class="form-select form-select-sm">
                    <option value="">All Agents</option>
                    <?php foreach ($agents as $a): ?>
                        <option value="<?= $a['id'] ?>" <?= $agentIdFilter == $a['id'] ? 'selected' : '' ?>><?= e($a['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            <div class="col-6 col-lg-2">
                <label class="form-label small fw-semibold text-muted mb-1">Date Range</label>
                <select name="date_filter" id="dateFilter" class="form-select form-select-sm" onchange="toggleCustomDates()">
                    <option value="today"      <?= $dateFilter==='today'      ?'selected':'' ?>>Today</option>
                    <option value="yesterday"  <?= $dateFilter==='yesterday'  ?'selected':'' ?>>Yesterday</option>
                    <option value="last_7_days"<?= $dateFilter==='last_7_days'?'selected':'' ?>>Last 7 Days</option>
                    <option value="last_30_days"<?=$dateFilter==='last_30_days'?'selected':'' ?>>Last 30 Days</option>
                    <option value="this_month" <?= $dateFilter==='this_month' ?'selected':'' ?>>This Month</option>
                    <option value="last_month" <?= $dateFilter==='last_month' ?'selected':'' ?>>Last Month</option>
                    <option value="all_time"   <?= $dateFilter==='all_time'   ?'selected':'' ?>>All Time</option>
                    <option value="custom"     <?= $dateFilter==='custom'     ?'selected':'' ?>>Custom...</option>
                </select>
            </div>
            <div class="col-6 col-lg-1 custom-dates" style="<?= $dateFilter!=='custom'?'display:none;':'' ?>">
                <label class="form-label small fw-semibold text-muted mb-1">From</label>
                <input type="date" name="date_from" class="form-control form-control-sm" value="<?= e($dateFrom) ?>">
            </div>
            <div class="col-6 col-lg-1 custom-dates" style="<?= $dateFilter!=='custom'?'display:none;':'' ?>">
                <label class="form-label small fw-semibold text-muted mb-1">To</label>
                <input type="date" name="date_to" class="form-control form-control-sm" value="<?= e($dateTo) ?>">
            </div>
            <div class="col-6 col-lg-2">
                <label class="form-label small fw-semibold text-muted mb-1">Status</label>
                <select name="status_filter" class="form-select form-select-sm">
                    <option value="">All Statuses</option>
                    <?php foreach ($allStatuses as $st): ?>
                        <option value="<?= e($st) ?>" <?= $statusFilter===$st?'selected':'' ?>><?= e($st) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 col-lg-2 d-flex gap-2 align-items-end">
                <button type="submit" class="btn btn-primary btn-sm flex-grow-1"><i class="bi bi-funnel me-1"></i>Apply</button>
                <?php if (!$isAgent): ?>
                <a href="<?= BASE_URL ?>modules/reports/export.php?<?= http_build_query($_GET) ?>" class="btn btn-success btn-sm"><i class="bi bi-download"></i></a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>
>



<?php
// Build filter query string to pass current filters to clicked pages
$filterParams = [];
if ($agentIdFilter && !$isAgent)       $filterParams['assigned_to'] = $agentIdFilter;
if ($dateFrom)                          $filterParams['date_from']    = $dateFrom;
if ($dateTo)                            $filterParams['date_to']      = $dateTo;
if ($statusFilter)                      $filterParams['status']       = $statusFilter;
$filterQs = $filterParams ? '?' . http_build_query($filterParams) : '';

// Converted statuses for the converted leads link
$convertedStatuses = implode(',', ['Converted','Done','Closed Won']);
$convertedQs = http_build_query(array_merge($filterParams, ['status' => 'Converted']));
?>

<!-- Summary Cards -->
<div class="row g-3 mb-4">
    <!-- Card 1: Total Leads -->
    <div class="col-xl-3 col-md-6">
        <a href="<?= BASE_URL ?>modules/leads/<?= $filterQs ?>" class="text-decoration-none" title="View all leads with current filters">
        <div class="card shadow-sm border-0 h-100 bg-white report-kpi-card" style="cursor:pointer;transition:box-shadow 0.2s,transform 0.15s;">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-muted fw-semibold small text-uppercase tracking-wide">Total Leads</span>
                    <div class="rounded-circle d-flex align-items-center justify-content-center" style="width:32px;height:32px;background:rgba(99,102,241,0.1);color:#4f46e5;">
                        <i class="bi bi-people-fill"></i>
                    </div>
                </div>
                <h3 class="fw-bold mb-1 text-dark"><?= number_format($summary['total_leads'] ?? 0) ?></h3>
                <div class="small <?= ($summary['leads_today'] ?? 0) > 0 ? 'text-success' : 'text-muted' ?>">
                    <i class="bi <?= ($summary['leads_today'] ?? 0) > 0 ? 'bi-arrow-up-right' : 'bi-dash' ?>"></i> <?= number_format($summary['leads_today'] ?? 0) ?> arrived today
                </div>
                <div class="mt-2 small text-primary fw-semibold"><i class="bi bi-arrow-right-circle me-1"></i>View Leads</div>
            </div>
        </div>
        </a>
    </div>

    <!-- Card 2: Converted Leads -->
    <div class="col-xl-3 col-md-6">
        <a href="<?= BASE_URL ?>modules/leads/?<?= $convertedQs ?>" class="text-decoration-none" title="View converted leads">
        <div class="card shadow-sm border-0 h-100 bg-white report-kpi-card" style="cursor:pointer;transition:box-shadow 0.2s,transform 0.15s;">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-muted fw-semibold small text-uppercase tracking-wide">Converted Leads</span>
                    <div class="rounded-circle d-flex align-items-center justify-content-center" style="width:32px;height:32px;background:rgba(16,185,129,0.1);color:#059669;">
                        <i class="bi bi-check-circle-fill"></i>
                    </div>
                </div>
                <h3 class="fw-bold mb-1 text-dark"><?= number_format($conversion['converted']) ?></h3>
                <div class="small fw-semibold <?= $conversion['rate'] >= 10 ? 'text-success' : 'text-warning' ?>">
                    <i class="bi bi-bullseye"></i> <?= $conversion['rate'] ?>% Conversion Rate
                </div>
                <div class="mt-2 small text-success fw-semibold"><i class="bi bi-arrow-right-circle me-1"></i>View Converted</div>
            </div>
        </div>
        </a>
    </div>

    <!-- Card 3: Total Revenue -->
    <div class="col-xl-3 col-md-6">
        <?php
        $dealsParams = $filterParams;
        unset($dealsParams['status']); // deals don't use lead status
        $dealsParams['status'] = 'won';
        $dealsQs = $dealsParams ? '?' . http_build_query($dealsParams) : '';
        ?>
        <a href="<?= BASE_URL ?>modules/deals/<?= $dealsQs ?>" class="text-decoration-none" title="View won deals">
        <div class="card shadow-sm border-0 h-100 bg-white report-kpi-card" style="cursor:pointer;transition:box-shadow 0.2s,transform 0.15s;">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-muted fw-semibold small text-uppercase tracking-wide">Total Revenue</span>
                    <div class="rounded-circle d-flex align-items-center justify-content-center" style="width:32px;height:32px;background:rgba(245,158,11,0.1);color:#d97706;">
                        <i class="bi bi-currency-dollar"></i>
                    </div>
                </div>
                <h3 class="fw-bold mb-1 text-dark"><?= formatCurrency($dealsRev['total_revenue'] ?? 0) ?></h3>
                <div class="small text-muted">
                    <i class="bi bi-trophy-fill text-warning"></i> <?= $dealsRev['total_closed_deals'] ?? 0 ?> Deals Won
                </div>
                <div class="mt-2 small text-warning fw-semibold"><i class="bi bi-arrow-right-circle me-1"></i>View Deals</div>
            </div>
        </div>
        </a>
    </div>

    <!-- Card 4: Follow-up Action -->
    <div class="col-xl-3 col-md-6">
        <?php
        $fuParams = [];
        if ($agentIdFilter && !$isAgent) $fuParams['user_id'] = $agentIdFilter;
        $fuParams['date'] = 'overdue';
        $fuQs = '?' . http_build_query($fuParams);
        ?>
        <a href="<?= BASE_URL ?>modules/followups/<?= $fuQs ?>" class="text-decoration-none" title="View overdue follow-ups">
        <div class="card shadow-sm border-0 h-100 bg-white report-kpi-card" style="cursor:pointer;transition:box-shadow 0.2s,transform 0.15s;">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-muted fw-semibold small text-uppercase tracking-wide">Follow-Up Action</span>
                    <div class="rounded-circle d-flex align-items-center justify-content-center" style="width:32px;height:32px;background:rgba(239,68,68,0.1);color:#dc2626;">
                        <i class="bi bi-clock-history"></i>
                    </div>
                </div>
                <h3 class="fw-bold mb-1 text-danger"><?= number_format($followUps['overdue_tasks'] ?? 0) ?> <span class="fs-6 fw-normal text-muted">Overdue</span></h3>
                <div class="small text-muted">
                    <?= number_format($followUps['pending_tasks'] ?? 0) ?> Pending | <?= number_format($followUps['completed_tasks'] ?? 0) ?> Completed
                </div>
                <div class="mt-2 small text-danger fw-semibold"><i class="bi bi-arrow-right-circle me-1"></i>View Follow-ups</div>
            </div>
        </div>
        </a>
    </div>
</div>

<style>
.report-kpi-card:hover { box-shadow: 0 6px 20px rgba(0,0,0,0.10) !important; transform: translateY(-3px); }
</style>



<div class="row g-4 mb-4">
    <!-- Daily Detailed Leads Feed -->

    <div class="col-12">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white border-0 pt-4 pb-3 d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0"><i class="bi bi-list-ul me-2 text-primary"></i>Daily Leads Feed & Recent Notes</h6>
                <span class="badge bg-primary bg-opacity-10 text-primary">Showing latest 10</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr class="small text-uppercase text-muted fw-semibold">
                                <th class="ps-4">Lead</th>
                                <th>Source</th>
                                <th>Agent</th>
                                <th>Stage / Status</th>
                                <th>Scheduled Follow-up</th>
                                <th style="min-width: 250px;">Latest Note</th>
                                <th class="pe-4 text-end">Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($detailedLeads as $dl): ?>
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-semibold text-dark"><a href="<?= BASE_URL ?>modules/leads/view.php?id=<?= $dl['id'] ?>" class="text-decoration-none text-dark"><?= e($dl['name']) ?></a></div>
                                    <div class="small text-muted"><i class="bi bi-telephone me-1"></i><?= e($dl['phone']) ?></div>
                                </td>
                                <td>
                                    <?php if($dl['source'] === 'facebook_ads'): ?>
                                        <span class="badge bg-primary bg-opacity-10 text-primary"><i class="bi bi-facebook me-1"></i>Ads</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary bg-opacity-10 text-secondary"><?= e(ucfirst($dl['source'] ?: 'Manual')) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><?= e($dl['agent_name'] ?: 'Unassigned') ?></td>
                                <td>
                                    <?php if ($dl['stage_name']): ?>
                                        <span class="badge rounded-pill px-2 py-1" style="background:<?= e($dl['stage_color'] ?? '#6366f1') ?>20;color:<?= e($dl['stage_color'] ?? '#6366f1') ?>; border:1px solid <?= e($dl['stage_color'] ?? '#6366f1') ?>30;">
                                            <?= e($dl['stage_name']) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-light text-dark border"><?= e($dl['status']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    $fuTs = !empty($dl['latest_followup_date']) ? @strtotime($dl['latest_followup_date']) : false;
                                    ?>
                                    <?php if ($fuTs): ?>
                                        <div class="small fw-semibold text-dark"><?= date('M d, Y', $fuTs) ?></div>
                                        <div class="small text-primary fw-bold"><?= date('h:i A', $fuTs) ?></div>
                                        <div class="mt-1">
                                            <?php if ($dl['latest_followup_status'] === 'completed'): ?>
                                                <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2 py-0" style="font-size: 0.65rem;">Completed</span>
                                            <?php elseif ($fuTs < time()): ?>
                                                <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 px-2 py-0" style="font-size: 0.65rem;">Overdue</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 px-2 py-0" style="font-size: 0.65rem;">Pending</span>
                                            <?php endif; ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="small text-muted fst-italic">None scheduled</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($dl['latest_note']): ?>
                                        <div class="small text-dark" style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; text-overflow: ellipsis;" title="<?= e($dl['latest_note']) ?>">
                                            <i class="bi bi-chat-left-text text-muted me-1"></i> <?= e($dl['latest_note']) ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="small text-muted fst-italic">No notes yet</span>
                                    <?php endif; ?>
                                </td>
                                <td class="pe-4 text-end">
                                    <div class="small text-muted fw-semibold"><?= date('M d, Y', strtotime($dl['created_at'])) ?></div>
                                    <div class="small text-muted"><?= date('h:i A', strtotime($dl['created_at'])) ?></div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($detailedLeads)): ?>
                            <tr><td colspan="6" class="text-center text-muted py-5">No leads found in this period</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <!-- Follow-up Tracking Master List -->
    <div class="col-12">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white border-0 pt-4 pb-3 d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0"><i class="bi bi-calendar-check me-2 text-danger"></i>Scheduled Follow-ups & Outcomes</h6>
                <span class="badge bg-danger bg-opacity-10 text-danger">For Selected Date Range</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr class="small text-uppercase text-muted fw-semibold">
                                <th class="ps-4">Sales Agent</th>
                                <th>Lead</th>
                                <th>Scheduled For</th>
                                <th>Outcome Note / Task</th>
                                <th class="pe-4 text-end">Current Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($followupsList as $fl): ?>
                            <tr>
                                <td class="ps-4 fw-semibold text-dark"><?= e($fl['agent_name'] ?: 'Unassigned') ?></td>
                                <td>
                                    <div class="fw-semibold text-primary"><a href="<?= BASE_URL ?>modules/leads/view.php?id=<?= $fl['lead_id'] ?>" class="text-decoration-none"><?= e($fl['lead_name']) ?></a></div>
                                </td>
                                <td>
                                    <?php
                                    $flDate = !empty($fl['followup_date']) ? $fl['followup_date'] : null;
                                    $flTime = !empty($fl['followup_time']) ? $fl['followup_time'] : '00:00:00';
                                    $flTs   = $flDate ? @strtotime($flDate . ' ' . $flTime) : false;
                                    ?>
                                    <?php if ($flTs): ?>
                                    <div class="small fw-bold text-dark"><?= date('M d, Y', $flTs) ?></div>
                                    <div class="small text-primary fw-bold"><?= date('h:i A', $flTs) ?></div>
                                    <?php else: ?>
                                    <span class="small text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($fl['description'])): ?>
                                        <div class="small text-dark" style="max-width:300px; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden;" title="<?= e($fl['description']) ?>"><i class="bi bi-sticky text-muted me-1"></i><?= e($fl['description']) ?></div>
                                    <?php else: ?>
                                        <span class="small text-muted fst-italic">No additional notes</span>
                                    <?php endif; ?>
                                </td>
                                <td class="pe-4 text-end">
                                    <?php if ($fl['status'] === 'completed'): ?>
                                        <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-3 py-2"><i class="bi bi-check2-all me-1"></i>Completed</span>
                                    <?php else: ?>
                                        <?php if (strtotime($fl['followup_date'] . ' ' . $fl['followup_time']) < time()): ?>
                                            <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 px-3 py-2"><i class="bi bi-exclamation-triangle me-1"></i>Overdue</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 px-3 py-2"><i class="bi bi-hourglass-split me-1"></i>Pending</span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($followupsList)): ?>
                            <tr><td colspan="5" class="text-center text-muted py-5">No follow-ups scheduled for this period</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>


<div class="row g-4 mb-4">
    <!-- Monthly Lead Trend -->
    <div class="col-xl-7">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white border-0 pt-4 pb-0"><h6 class="fw-bold"><i class="bi bi-graph-up me-2 text-primary"></i>Daily Lead Trend</h6></div>
            <div class="card-body"><canvas id="monthlyChart" height="280"></canvas></div>
        </div>
    </div>
    <!-- Status Breakdown Sidebar -->
    <div class="col-xl-5">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white border-0 pt-4 pb-2 d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0"><i class="bi bi-layers me-2 text-primary"></i>Pipeline Funnel Breakdown</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive" style="max-height: 280px; overflow-y: auto;">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light sticky-top">
                            <tr class="small text-uppercase text-muted fw-semibold">
                                <th class="ps-3" style="font-size: 11px;">Status</th>
                                <th class="text-center" style="font-size: 11px;">Leads</th>
                                <th class="pe-3 text-end" style="font-size: 11px;">Share</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
                            $totalLeads = array_sum(array_column($statusBreakdown, 'count')) ?: 1;
                            $statusColors = [
                                'New Lead'=>'#6366f1','Contacted'=>'#3b82f6','Working'=>'#8b5cf6',
                                'Follow Up'=>'#f59e0b','Site Visit'=>'#14b8a6','Office Visit'=>'#06b6d4',
                                'Visited Site'=>'#14b8a6','Visited Office'=>'#06b6d4',
                                'Done'=>'#10b981','Converted'=>'#10b981',
                                'Rejected'=>'#ef4444','Not Interested'=>'#ef4444','Closed Lost'=>'#ef4444',
                            ];
                        ?>
                        <?php if(!empty($statusBreakdown)): foreach ($statusBreakdown as $sb):
                            $color = $statusColors[$sb['status']] ?? '#94a3b8';
                            $pct   = round(($sb['count'] / $totalLeads) * 100, 1);
                        ?>
                        <tr class="<?= $sb['count'] > 0 ? '' : 'text-muted opacity-50' ?>">
                            <td class="ps-3">
                                <div class="d-flex align-items-center gap-2">
                                    <span style="width:8px;height:8px;border-radius:50%;background:<?= $color ?>;display:inline-block;flex-shrink:0;"></span>
                                    <span class="fw-semibold text-dark" style="font-size:12px;"><?= e($sb['status']) ?></span>
                                </div>
                            </td>
                            <td class="text-center fw-bold" style="color:<?= $sb['count'] > 0 ? $color : '#cbd5e1' ?>; font-size:12px;"><?= number_format($sb['count']) ?></td>
                            <td class="pe-3 text-end">
                                <span class="small text-muted" style="font-size:11px;"><?= $pct ?>%</span>
                            </td>
                        </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>



<?php if (!$isAgent): ?>
<div class="row g-4 mb-4">
    <!-- Agent Performance Table -->
    <div class="col-12">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white border-0 pt-4 d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0"><i class="bi bi-people me-2 text-primary"></i>Agent Performance Leaderboard</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead><tr class="small text-uppercase text-muted fw-semibold"><th>Agent</th><th>Assigned</th><th>Deals</th><th>Conv Ratio</th><th>Avg Resp Time</th></tr></thead>
                        <tbody>
                            <?php foreach ($agentPerf as $ap): ?>
                            <?php 
                                // Find response time for this agent
                                $respStr = "N/A";
                                foreach ($agentResp as $ar) {
                                    if ($ar['agent_name'] === $ap['name'] && $ar['avg_response_minutes'] !== null) {
                                        $rmins = round($ar['avg_response_minutes']);
                                        if ($rmins < 60) $respStr = $rmins . "m";
                                        else $respStr = round($rmins/60, 1) . "h";
                                        break;
                                    }
                                }
                            ?>
                            <tr>
                                <td class="fw-semibold text-dark"><?= e($ap['name']) ?></td>
                                <td><?= number_format($ap['total_leads']) ?></td>
                                <td class="fw-bold text-success"><?= number_format($ap['converted_deals']) ?></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <span class="me-2 fw-semibold" style="width: 32px;"><?= $ap['conv_rate'] ?>%</span>
                                        <div class="progress" style="height:6px;width:60px;">
                                            <div class="progress-bar bg-<?= $ap['conv_rate'] > 20 ? 'success' : ($ap['conv_rate'] > 5 ? 'warning' : 'danger') ?>" style="width:<?= min(100, $ap['conv_rate']) ?>%"></div>
                                        </div>
                                    </div>
                                </td>
                                <td><span class="badge bg-light text-dark border"><?= $respStr ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($agentPerf)): ?><tr><td colspan="5" class="text-center text-muted py-4">No agents active in this timeframe</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
function toggleCustomDates() {
    const val = document.getElementById('dateFilter').value;
    const customDivs = document.querySelectorAll('.custom-dates');
    if (val === 'custom') {
        customDivs.forEach(el => el.style.display = 'block');
    } else {
        customDivs.forEach(el => el.style.display = 'none');
    }
}
</script>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
const colors = ['#6366f1','#10b981','#f59e0b','#ef4444','#3b82f6','#8b5cf6','#ec4899','#14b8a6','#f97316','#64748b'];

// Monthly / Daily Trend Chart
const monthlyData = <?= json_encode($monthlyGrowth) ?>;
if (document.getElementById('monthlyChart') && monthlyData.length > 0) {
    new Chart(document.getElementById('monthlyChart'), {
        type: 'line',
        data: { 
            labels: monthlyData.map(d => d.label), 
            datasets: [{ 
                label: 'Leads Received', 
                data: monthlyData.map(d => d.count), 
                borderColor: '#6366f1', 
                backgroundColor: 'rgba(99,102,241,0.1)', 
                borderWidth: 2, 
                fill: true, 
                tension: 0.3,
                pointRadius: 4,
                pointBackgroundColor: '#fff',
                pointBorderWidth: 2
            }] 
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.04)' } }, x: { grid: { display: false } } } }
    });
}

// Status Pipeline Pie
const statusData = <?= json_encode($leadsByStatus) ?>;
if (document.getElementById('statusChart') && statusData.length > 0) {
    new Chart(document.getElementById('statusChart'), {
        type: 'doughnut',
        data: { 
            labels: statusData.map(d => d.status), 
            datasets: [{ data: statusData.map(d => d.count), backgroundColor: colors, borderWidth: 0 }] 
        },
        options: { responsive: true, maintainAspectRatio: false, cutout: '65%', plugins: { legend: { position: 'right', labels: { usePointStyle: true, padding: 12 } } } }
    });
}


</script>

<?php include '../../includes/footer.php'; ?>
