<?php
$_SERVER['HTTP_HOST'] = 'localhost';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../models/Lead.php';

$leadModel = new Lead($pdo);

echo "Starting lead-pipeline synchronization...\n";

// Get all leads
$stmt = $pdo->query("SELECT id, status, organization_id FROM leads");
$leads = $stmt->fetchAll();

$count = 0;
foreach ($leads as $lead) {
    $status = $lead['status'];
    $stageName = $status;
    
    // Mapping for statuses that don't match stage names exactly
    $mapping = [
        'Working'   => 'Contacted',
        'Follow Up' => 'Contacted',
        'Done'      => 'Closed Won',
        'Rejected'  => 'Closed Lost'
    ];

    if (isset($mapping[$status])) {
        $stageName = $mapping[$status];
    }

    // Find a pipeline stage that matches the stage name
    $stageStmt = $pdo->prepare("SELECT id FROM pipeline_stages WHERE organization_id = :org AND name = :name LIMIT 1");
    $stageStmt->execute(['org' => $lead['organization_id'], 'name' => $stageName]);
    $stageId = $stageStmt->fetchColumn();

    if ($stageId) {
        $update = $pdo->prepare("UPDATE leads SET pipeline_stage_id = :stage WHERE id = :id");
        $update->execute(['stage' => $stageId, 'id' => $lead['id']]);
        $count++;
    }
}

echo "Finished syncing $count leads.\n";
