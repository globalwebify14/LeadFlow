<?php
require_once __DIR__ . '/../../config/db.php';

try {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 1. Create `roles` table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS roles (
            id INT AUTO_INCREMENT PRIMARY KEY,
            organization_id INT NULL,
            name VARCHAR(100) NOT NULL,
            is_system TINYINT(1) DEFAULT 0,
            created_by INT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (organization_id) REFERENCES organizations(id) ON DELETE CASCADE,
            FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "Created roles table\n";

    // 2. Create `role_permissions` table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS role_permissions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            role_id INT NOT NULL,
            module_key VARCHAR(50) NOT NULL,
            can_view TINYINT(1) DEFAULT 0,
            can_create TINYINT(1) DEFAULT 0,
            can_edit TINYINT(1) DEFAULT 0,
            can_delete TINYINT(1) DEFAULT 0,
            FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
            UNIQUE KEY unique_role_module (role_id, module_key)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "Created role_permissions table\n";

    // 3. Add `role_id` to `users` if not exists
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'role_id'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE users ADD COLUMN role_id INT NULL AFTER role");
        $pdo->exec("ALTER TABLE users ADD FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE SET NULL");
        echo "Added role_id to users\n";
    }

    echo "Migration successful!\n";

} catch (PDOException $e) {
    echo "PDO Error: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
