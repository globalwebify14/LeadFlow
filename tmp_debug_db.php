<?php
require_once __DIR__ . '/config/db.php';

echo "Database Object: " . get_class($pdo) . "\n";
$stmt = $pdo->query("SELECT * FROM facebook_forms LIMIT 1");
$form = $stmt->fetch();
print_r($form);
?>
