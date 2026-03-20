<?php
require 'config/db.php';
$s = $pdo->query('DESCRIBE followups');
while($r=$s->fetch(PDO::FETCH_ASSOC)) {
    echo $r['Field'] . " - " . $r['Type'] . " - Null:" . $r['Null'] . " - Default:" . $r['Default'] . "\n";
}
?>
