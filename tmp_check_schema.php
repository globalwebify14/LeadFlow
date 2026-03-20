<?php
require 'config/db.php';
$s = $pdo->query('DESCRIBE followups');
while($r=$s->fetch(PDO::FETCH_ASSOC)) {
    print_r($r);
}
echo "\n--- Recent Rows ---\n";
$s = $pdo->query('SELECT * FROM followups ORDER BY id DESC LIMIT 5');
while($r=$s->fetch(PDO::FETCH_ASSOC)) {
    print_r($r);
}
?>
