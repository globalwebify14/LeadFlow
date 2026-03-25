<?php
// HARDCODED LOCALHOST CONNECTION FOR CLI EXECUTION
try {
    $pdo = new PDO("mysql:host=127.0.0.1;dbname=lead;charset=utf8mb4", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 1. Create `modules` table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS modules (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL UNIQUE,
            label VARCHAR(150) NOT NULL,
            status ENUM('active', 'inactive') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // 2. Create `organization_modules` table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS organization_modules (
            id INT AUTO_INCREMENT PRIMARY KEY,
            organization_id INT NOT NULL,
            module_name VARCHAR(100) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_org_module (organization_id, module_name)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // 3. Seed default core modules
    $coreModules = [
        ['name' => 'leads', 'label' => 'Leads Management'],
        ['name' => 'manual_leads', 'label' => 'Manual Leads Entry'],
        ['name' => 'import_leads', 'label' => 'Import Leads (Excel/CSV)'],
        ['name' => 'facebook_integration', 'label' => 'Facebook Integration'],
        ['name' => 'pipeline', 'label' => 'Pipeline'],
        ['name' => 'deals', 'label' => 'Deals'],
        ['name' => 'tasks', 'label' => 'Tasks'],
        ['name' => 'followups', 'label' => 'Follow-ups'],
        ['name' => 'reports', 'label' => 'Reports']
    ];

    $stmt = $pdo->prepare("INSERT IGNORE INTO modules (name, label) VALUES (:name, :label)");
    foreach ($coreModules as $mod) {
        $stmt->execute(['name' => $mod['name'], 'label' => $mod['label']]);
    }

    echo "SUCCESS: Module tables created and seeded!\n";
} catch (PDOException $e) {
    echo "SQL ERROR: " . $e->getMessage() . "\n";
}
?>
