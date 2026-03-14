<?php
require_once __DIR__ . '/../../config/db.php';

echo "Running Database Updates...\n";

try {
    $pdo->exec("ALTER TABLE organizations ADD COLUMN assignment_mode ENUM('manual', 'auto') DEFAULT 'manual'");
    echo "Added assignment_mode to organizations.\n";
} catch (PDOException $e) {
    if ($e->getCode() != '42S21') {
        echo "Error: " . $e->getMessage() . "\n";
    } else {
        echo "assignment_mode already exists in organizations.\n";
    }
}

try {
    $pdo->exec("ALTER TABLE users ADD COLUMN availability_status ENUM('active', 'inactive', 'absent') DEFAULT 'active'");
    echo "Added availability_status to users.\n";
} catch (PDOException $e) {
    if ($e->getCode() != '42S21') {
        echo "Error: " . $e->getMessage() . "\n";
    } else {
        echo "availability_status already exists in users.\n";
    }
}

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS tasks (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `organization_id` int(11) NOT NULL,
        `user_id` int(11) NOT NULL,
        `title` varchar(255) NOT NULL,
        `description` text DEFAULT NULL,
        `due_date` date DEFAULT NULL,
        `due_time` time DEFAULT NULL,
        `status` enum('pending', 'completed', 'overdue') DEFAULT 'pending',
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `completed_at` timestamp NULL DEFAULT NULL,
        PRIMARY KEY (`id`),
        KEY `organization_id` (`organization_id`),
        KEY `user_id` (`user_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    echo "Created tasks table (or it already exists).\n";
} catch (PDOException $e) {
    echo "Error creating tasks table: " . $e->getMessage() . "\n";
}

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS deals (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `organization_id` int(11) NOT NULL,
        `lead_id` int(11) DEFAULT NULL,
        `name` varchar(255) NOT NULL,
        `value` decimal(15,2) DEFAULT '0.00',
        `stage_id` int(11) DEFAULT NULL,
        `assigned_to` int(11) DEFAULT NULL,
        `expected_close_date` date DEFAULT NULL,
        `status` enum('open', 'won', 'lost') DEFAULT 'open',
        `description` text,
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `organization_id` (`organization_id`),
        KEY `lead_id` (`lead_id`),
        KEY `stage_id` (`stage_id`),
        KEY `assigned_to` (`assigned_to`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    echo "Created deals table (or it already exists).\n";
} catch (PDOException $e) {
    echo "Error creating deals table: " . $e->getMessage() . "\n";
}

echo "Database updates complete.\n";
