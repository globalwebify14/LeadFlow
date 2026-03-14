<?php
require_once __DIR__ . '/../../config/db.php';

$queries = [
    "CREATE TABLE IF NOT EXISTS `tasks` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `organization_id` int(11) NOT NULL,
        `lead_id` int(11) DEFAULT NULL,
        `assigned_to` int(11) DEFAULT NULL,
        `task_title` varchar(255) NOT NULL,
        `description` text DEFAULT NULL,
        `due_date` datetime DEFAULT NULL,
        `status` enum('pending','completed') DEFAULT 'pending',
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        PRIMARY KEY (`id`),
        KEY `organization_id` (`organization_id`),
        KEY `lead_id` (`lead_id`),
        KEY `assigned_to` (`assigned_to`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

    "CREATE TABLE IF NOT EXISTS `automation_sequences` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `organization_id` int(11) NOT NULL,
        `name` varchar(255) NOT NULL,
        `is_active` tinyint(1) DEFAULT 1,
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        PRIMARY KEY (`id`),
        KEY `organization_id` (`organization_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

    "CREATE TABLE IF NOT EXISTS `automation_steps` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `sequence_id` int(11) NOT NULL,
        `day_offset` int(11) DEFAULT 0,
        `action_type` enum('whatsapp','task','email') NOT NULL,
        `action_data` text DEFAULT NULL,
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        PRIMARY KEY (`id`),
        KEY `sequence_id` (`sequence_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
];

foreach ($queries as $query) {
    try {
        $pdo->exec($query);
        echo "Success: Query executed.\n";
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
}
?>
