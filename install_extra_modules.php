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
    
    // Add availability status column to users if it doesn't exist
    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN availability_status ENUM('active', 'inactive', 'absent') DEFAULT 'active'");
        echo "SUCCESS: Added availability_status to users!\n<br>";
    } catch (PDOException $e) {
        // 1060 is "Duplicate column name". Safe to ignore.
        if ($e->getCode() != '42S21' && !str_contains($e->getMessage(), 'Duplicate column')) {
            echo "Notice: Column already exists or skipped: " . $e->getMessage() . "\n<br>";
        }
    }

    echo "SUCCESS: Added extra modules!\n";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>
