<?php
require __DIR__ . '/config/db.php';
$stmt = $pdo->query("SELECT id, note FROM leads WHERE source='facebook_ads' AND note LIKE '%Submitted:%'");
$c = 0;
while($r = $stmt->fetch()){
    if(preg_match('/Submitted:\s*([^\n]+)/', $r['note'], $m)){
        $ts = strtotime(trim($m[1]));
        if($ts){
            $dt = date('Y-m-d H:i:s', $ts);
            $pdo->query("UPDATE leads SET created_at='{$dt}' WHERE id={$r['id']}");
            $c++;
        }
    }
}
echo "Updated {$c} leads\n";
