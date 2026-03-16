<?php
$_SERVER['HTTP_HOST'] = 'localhost';
require_once __DIR__ . '/../config/db.php';

echo "--- ORGANIZATION SUMMARY ---\n";
$stmt = $pdo->query("SELECT o.id, o.name, 
                    (SELECT COUNT(*) FROM leads WHERE organization_id = o.id) as lead_count,
                    (SELECT COUNT(*) FROM pipeline_stages WHERE organization_id = o.id) as stage_count
                    FROM organizations o");
while($row = $stmt->fetch()) {
    echo "ID: {$row['id']} | Name: {$row['name']} | Leads: {$row['lead_count']} | Stages: {$row['stage_count']}\n";
}

echo "\n--- RAW LEADS WITHOUT ORG MATCH ---\n";
$stmt = $pdo->query("SELECT id, name, organization_id, status FROM leads WHERE organization_id NOT IN (SELECT id FROM organizations)");
while($row = $stmt->fetch()) {
    echo "Lead ID: {$row['id']} | Name: {$row['name']} | Status: {$row['status']} | Org ID: {$row['organization_id']}\n";
}

echo "\n--- USERS BY ORG ---\n";
$stmt = $pdo->query("SELECT id, name, organization_id, role FROM users");
while($row = $stmt->fetch()) {
    echo "User ID: {$row['id']} | Name: {$row['name']} | Org ID: {$row['organization_id']} | Role: {$row['role']}\n";
}

echo "\n--- VERIFYING ORG 4 DATA ---\n";
$stmt = $pdo->prepare("SELECT id, name, organization_id, role FROM users WHERE organization_id = 4");
$stmt->execute();
while($row = $stmt->fetch()) { echo "User ID: {$row['id']} | Name: {$row['name']} | Role: {$row['role']}\n"; }

$stmt = $pdo->prepare("SELECT id, name FROM pipeline_stages WHERE organization_id = 4");
$stmt->execute();
$stages = $stmt->fetchAll();
echo "Org 4 Stages: " . count($stages) . "\n";

$stmt = $pdo->prepare("SELECT id, name, status, pipeline_stage_id FROM leads WHERE organization_id = 4");
$stmt->execute();
while($row = $stmt->fetch()) { echo "Lead ID: {$row['id']} | Name: {$row['name']} | Status: {$row['status']} | Stage ID: " . ($row['pipeline_stage_id'] ?: 'NULL') . "\n"; }
$stmt = $pdo->query("SELECT id, name, organization_id, role FROM users");
while($row = $stmt->fetch()) {
    echo "User ID: {$row['id']} | Name: {$row['name']} | Org ID: {$row['organization_id']} | Role: {$row['role']}\n";
}
