<?php
require_once '../../config/auth.php';
requireLogin();
require_once '../../config/db.php';
require_once '../../models/Lead.php';

$orgId = getOrgId();
$leadModel = new Lead($pdo);

// Capture filters from GET
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

// Fetch matching leads for export
$leads = $leadModel->getFilteredLeadsForExport($orgId, $filters);

// Set headers for CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=leads_export_' . date('Y-m-d_H-i-s') . '.csv');

// Create a file pointer connected to the output stream
$output = fopen('php://output', 'w');

// Output CSV headers
fputcsv($output, ['ID', 'Name', 'Email', 'Phone', 'Company', 'Source', 'Status', 'Priority', 'Assigned To', 'Meta Campaign', 'Date Created']);

// Output data rows
foreach ($leads as $lead) {
    fputcsv($output, [
        $lead['id'],
        $lead['name'],
        $lead['email'],
        "\t" . $lead['phone'],
        $lead['company'],
        $lead['source'],
        $lead['status'],
        $lead['priority'],
        $lead['agent_name'] ?? 'Unassigned',
        $lead['meta_campaign'],
        $lead['created_at']
    ]);
}

fclose($output);
exit;
