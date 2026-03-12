<?php
require 'c:/xampp/htdocs/lead/config/db.php';
$stmt = $pdo->query("SELECT id, name, email, role, is_active FROM users ORDER BY id DESC LIMIT 5");
file_put_contents('c:/xampp/htdocs/lead/dump.json', json_encode($stmt->fetchAll(PDO::FETCH_ASSOC), JSON_PRETTY_PRINT));
echo "DONE";
?>
