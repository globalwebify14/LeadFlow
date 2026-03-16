<?php
$_SERVER['HTTP_HOST'] = 'localhost';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../models/Lead.php';

$leadModel = new Lead($pdo);

echo "Starting retroactive lead-pipeline synchronization...\n";

// Get all leads
$stmt = $pdo->query("SELECT id, status, organization_id FROM leads");
$leads = $stmt->fetchAll();

$count = 0;
foreach ($leads as $lead) {
    // This will use the improved sync logic in the Model
    $leadModel->updateStatus($lead['id'], $lead['status'], 'Retroactive pipeline sync', null);
    $count++;
}

echo "Finished syncing $count leads.\n";
