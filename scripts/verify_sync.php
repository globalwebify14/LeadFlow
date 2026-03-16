<?php
$_SERVER['HTTP_HOST'] = 'localhost';
require_once __DIR__ . '/../config/db.php';

$stmt = $pdo->query("SELECT l.status, ps.name as stage_name, COUNT(*) as count 
                     FROM leads l 
                     JOIN pipeline_stages ps ON l.pipeline_stage_id = ps.id 
                     GROUP BY l.status, ps.name");
$results = $stmt->fetchAll();

echo "Lead Status vs Pipeline Stage Alignment:\n";
echo str_pad("Status", 20) . " | " . str_pad("Pipeline Stage", 20) . " | Count\n";
echo str_repeat("-", 55) . "\n";
foreach ($results as $r) {
    echo str_pad($r['status'], 20) . " | " . str_pad($r['stage_name'], 20) . " | " . $r['count'] . "\n";
}
