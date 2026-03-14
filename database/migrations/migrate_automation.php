<?php
require_once __DIR__ . '/../../config/db.php';

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS lead_automation_progress (
        id INT AUTO_INCREMENT PRIMARY KEY,
        lead_id INT,
        sequence_id INT,
        last_step_id INT DEFAULT NULL,
        started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        last_executed_at TIMESTAMP NULL,
        UNIQUE KEY (lead_id, sequence_id)
    )");
    echo "Table lead_automation_progress created successfully.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
