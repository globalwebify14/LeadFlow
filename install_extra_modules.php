<?php
try {
    $pdo = new PDO("mysql:host=127.0.0.1;dbname=lead;charset=utf8mb4", "root", "");
    $coreModules = [
        ['name' => 'users', 'label' => 'Team'],
        ['name' => 'automation', 'label' => 'Automation'],
        ['name' => 'org_settings', 'label' => 'Organization Settings'],
        ['name' => 'profile_settings', 'label' => 'Profile & API']
    ];
    $stmt = $pdo->prepare("INSERT IGNORE INTO modules (name, label) VALUES (:name, :label)");
    foreach ($coreModules as $mod) {
        $stmt->execute($mod);
    }
    echo "SUCCESS: Added extra modules!\n";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>
