<?php
$_SERVER['HTTP_HOST'] = 'localhost';
require_once __DIR__ . '/../config/db.php';
$stmt = $pdo->prepare("SELECT COUNT(*) FROM pipeline_stages WHERE organization_id = 4");
$stmt->execute();
echo "Stages for Org 4: " . $stmt->fetchColumn() . "\n";
$stmt = $pdo->prepare("SELECT COUNT(*) FROM leads WHERE organization_id = 4");
$stmt->execute();
echo "Total leads in Org 4: " . $stmt->fetchColumn() . "\n";
$stmt = $pdo->prepare("SELECT COUNT(*) FROM leads WHERE organization_id = 4 AND pipeline_stage_id IS NOT NULL");
$stmt->execute();
echo "Leads in Org 4 with stage: " . $stmt->fetchColumn() . "\n";
$stmt = $pdo->prepare("SELECT id, name, organization_id FROM users WHERE id = 16");
$stmt->execute();
$u = $stmt->fetch();
echo "User Shakti (16) Org: " . ($u['organization_id'] ?? 'NULL') . "\n";
