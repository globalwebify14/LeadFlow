<?php
require_once __DIR__ . '/../config/db.php';

$tables = ['leads', 'lead_activities', 'lead_notes', 'lead_tags', 'lead_tag_map', 'pipeline_stages', 'followups', 'tasks', 'automation_sequences', 'automation_steps', 'activity_logs', 'deals'];

$output = "";
foreach ($tables as $table) {
    $output .= "--- Table: $table ---\n";
    try {
        $stmt = $pdo->query("DESCRIBE `$table`");
        while ($row = $stmt->fetch()) {
            $output .= "{$row['Field']} | {$row['Type']} | {$row['Null']} | {$row['Key']} | {$row['Default']} | {$row['Extra']}\n";
        }
    } catch (Exception $e) {
        $output .= "Error or table does not exist: " . $e->getMessage() . "\n";
    }
    $output .= "\n";
}
file_put_contents(__DIR__ . '/schema.txt', $output);
echo "Schema written to database/schema.txt";
?>
