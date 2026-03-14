<?php
$_SERVER['HTTP_HOST'] = 'localhost';
require_once __DIR__ . '/../../config/db.php';

try {
    $sql = "ALTER TABLE `leads` ADD COLUMN IF NOT EXISTS `is_notified` tinyint(1) DEFAULT 0 AFTER `status`";
    $pdo->exec($sql);
    echo "Successfully added 'is_notified' column to leads table.\n";
} catch (PDOException $e) {
    if ($e->getCode() == '42S21') {
        echo "Column 'is_notified' already exists.\n";
    } else {
        echo "Error: " . $e->getMessage() . "\n";
    }
}
