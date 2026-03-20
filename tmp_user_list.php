<?php
require 'config/db.php';
$stmt = $pdo->query('SELECT name, role, status FROM users');
while($r = $stmt->fetch()){
    echo $r['name'] . ' | ' . $r['role'] . ' | ' . $r['status'] . "\n";
}
unlink(__FILE__);
