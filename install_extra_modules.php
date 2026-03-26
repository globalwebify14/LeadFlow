<?php
require_once 'config/db.php';
try {
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
