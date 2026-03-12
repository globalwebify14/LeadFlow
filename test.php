<?php
require 'config/db.php';
$stmt = $pdo->query("SELECT id, email, role, is_active FROM users ORDER BY id DESC LIMIT 5");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
?>
