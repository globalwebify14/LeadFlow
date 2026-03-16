<?php
$_SERVER['HTTP_HOST'] = 'localhost';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../models/Lead.php';

echo "--- Correcting Shakti Singh's Account ---\n";
$stmt = $pdo->prepare("UPDATE users SET organization_id = 4 WHERE id = 16");
$stmt->execute();
echo "Updated Shakti Singh (ID 16) to Org 4.\n";

echo "\n--- Seeding Pipeline Stages for Org 4 ---\n";
$stages = [
    ['New Lead', '#6366f1', 0, 0, 0],
    ['Contacted', '#3b82f6', 1, 0, 0],
    ['Qualified', '#8b5cf6', 2, 0, 0],
    ['Proposal Sent', '#f59e0b', 3, 0, 0],
    ['Negotiation', '#f97316', 4, 0, 0],
    ['Closed Won', '#10b981', 5, 1, 0],
    ['Closed Lost', '#ef4444', 6, 0, 1],
];
$stageStmt = $pdo->prepare("INSERT INTO pipeline_stages (organization_id, name, color, position, is_won, is_lost) VALUES (4, ?, ?, ?, ?, ?)");
foreach ($stages as $s) {
    // Check if stage already exists to avoid duplicates
    $check = $pdo->prepare("SELECT id FROM pipeline_stages WHERE organization_id = 4 AND name = ?");
    $check->execute([$s[0]]);
    if (!$check->fetch()) {
        $stageStmt->execute([$s[0], $s[1], $s[2], $s[3], $s[4]]);
        echo "Created stage: {$s[0]}\n";
    } else {
        echo "Stage already exists: {$s[0]}\n";
    }
}

echo "\n--- Retroactively Syncing leads for Org 4 ---\n";
$leadModel = new Lead($pdo);
$stmt = $pdo->prepare("SELECT id, status FROM leads WHERE organization_id = 4");
$stmt->execute();
$leads = $stmt->fetchAll();
foreach ($leads as $lead) {
    // This will use the public updateStatus method which correctly triggers the sync
    $leadModel->updateStatus($lead['id'], $lead['status'], 'Retroactive pipeline sync for Org 4', null);
    echo "Synced Lead ID: {$lead['id']} (Status: {$lead['status']})\n";
}

echo "\nCorrection complete.\n";
