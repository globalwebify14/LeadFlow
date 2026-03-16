<?php
$_SERVER['HTTP_HOST'] = 'localhost';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/models/Lead.php';

$leadModel = new Lead($pdo);

// 1. Create a dummy lead
$leadId = $leadModel->addLead([
    'organization_id' => 1,
    'name' => 'Test Lead Sync',
    'phone' => '1234567890',
    'status' => 'New Lead'
]);

if (!$leadId) die("Failed to create test lead\n");

function checkStage($leadId, $status, $leadModel) {
    $lead = $leadModel->getLeadById($leadId);
    echo "Status: [$status] -> Pipeline Stage ID: [" . $lead['pipeline_stage_id'] . "]\n";
}

echo "Testing individual update...\n";
$leadModel->updateStatus($leadId, 'Contacted');
checkStage($leadId, 'Contacted', $leadModel);

echo "Testing bulk update...\n";
$leadModel->bulkUpdateStatus([$leadId], 'Working');
checkStage($leadId, 'Working', $leadModel);

echo "Testing case-insensitivity and trim...\n";
// Manually update DB for a stage with spaces for testing if possible, 
// but here we just test if the model handles it.
$leadModel->updateStatus($leadId, 'qualified'); // case difference
checkStage($leadId, 'qualified', $leadModel);

// Clean up
$leadModel->deleteLead($leadId);
echo "Verification complete.\n";
