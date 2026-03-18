<?php
require_once 'config/db.php';
$stmt = $pdo->query('SELECT * FROM system_settings');
header('Content-Type: text/plain');
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo $row['setting_key'] . ": " . $row['setting_value'] . "\n";
}
?>
