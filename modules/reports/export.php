<?php
require_once '../../config/auth.php';
requireLogin();
require_once '../../config/db.php';
require_once '../../models/Report.php';

if (getUserRole() === 'agent') {
    die("Access denied.");
}

$orgId = getOrgId();
$reportModel = new Report($pdo);

// Date filters setup using identical logic to reports/index
$dateFilter = $_GET['date_filter'] ?? 'this_month';
$dateFrom = $_GET['date_from'] ?? null;
$dateTo = $_GET['date_to'] ?? null;

if ($dateFilter !== 'custom') {
    $dateTo = date('Y-m-d');
    switch ($dateFilter) {
        case 'today': $dateFrom = date('Y-m-d'); break;
        case 'yesterday': $dateFrom = date('Y-m-d', strtotime('-1 day')); $dateTo = $dateFrom; break;
        case 'last_7_days': $dateFrom = date('Y-m-d', strtotime('-7 days')); break;
        case 'last_30_days': $dateFrom = date('Y-m-d', strtotime('-30 days')); break;
        case 'this_month': $dateFrom = date('Y-m-01'); break;
        case 'last_month': $dateFrom = date('Y-m-01', strtotime('first day of last month')); $dateTo = date('Y-m-t', strtotime('last day of last month')); break;
        case 'all_time': $dateFrom = null; $dateTo = null; break;
        default: $dateFrom = date('Y-m-01');
    }
}

$agentIdFilter = $_GET['agent_id'] ?? null;

// Read Data
$summary = $reportModel->getLeadSummary($orgId, $agentIdFilter, $dateFrom, $dateTo);
$dealsRev = $reportModel->getDealsRevenueReport($orgId, $agentIdFilter, $dateFrom, $dateTo);
$agentPerf = $reportModel->getAgentAdvancedPerformance($orgId, $dateFrom, $dateTo);
$campaigns = $reportModel->getFacebookCampaignReport($orgId, $dateFrom, $dateTo);

// Prep Output
$filename = "analytics_export_" . date('Y-m-d') . ".csv";
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
$output = fopen('php://output', 'w');

function printSection($output, $title, $headers, $rows) {
    fputcsv($output, [$title]);
    fputcsv($output, $headers);
    foreach ($rows as $row) {
        fputcsv($output, $row);
    }
    fputcsv($output, []); // blank line
}

// Write Overview
printSection($output, "OVERVIEW SUMMARY [Date Filter: " . strtoupper($dateFilter) . " / From $dateFrom To $dateTo]", 
    ["Metric", "Value"],
    [
        ["Total Leads", $summary['total_leads'] ?? 0],
        ["Leads Today", $summary['leads_today'] ?? 0],
        ["Contacted Leads", $summary['contacted_leads'] ?? 0],
        ["Converted Leads", $summary['converted_leads'] ?? 0],
        ["Total Deals Won", $dealsRev['total_closed_deals'] ?? 0],
        ["Total Revenue", $dealsRev['total_revenue'] ?? 0],
    ]
);

// Write Agent Leaderboard
$agentRows = [];
foreach ($agentPerf as $ap) {
    $agentRows[] = [
        $ap['name'],
        $ap['total_leads'],
        $ap['contacted_leads'],
        $ap['converted_deals'],
        $ap['conv_rate'] . "%"
    ];
}
printSection($output, "AGENT LEADERBOARD", 
    ["Agent Name", "Leads Assigned", "Contacted Leads", "Deals Closed", "Conversion Rate"], 
    $agentRows
);

// Write Campaign ROI
$campRows = [];
foreach ($campaigns as $camp) {
    $campRows[] = [
        $camp['page_name'] ?? 'Disconnected',
        $camp['campaign_name'],
        $camp['lead_count']
    ];
}
printSection($output, "FACEBOOK CAMPAIGN ROI", 
    ["Facebook Page Name", "Campaign Name", "Leads Generated"], 
    $campRows
);

fclose($output);
exit;
?>
