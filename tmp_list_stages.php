<?php
$_SERVER['HTTP_HOST'] = 'localhost';
require_once __DIR__ . '/config/db.php';
try {
    $stmt = $pdo->query("SELECT id, name FROM pipeline_stages ORDER BY position");
    $stages = $stmt->fetchAll();
    foreach ($stages as $stage) {
        echo "ID: " . $stage['id'] . " | Name: [" . $stage['name'] . "]\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

