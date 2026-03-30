<?php
require_once __DIR__ . '/config/db.php';

echo "<h2>Database Migration Tool</h2>";

try {
    // Check if availability_status already exists
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'availability_status'");
    if ($stmt->rowCount() > 0) {
        echo "<p style='color:green;'>Column `availability_status` already exists in `users` table.</p>";
    } else {
        // Add the column
        $pdo->exec("ALTER TABLE users ADD COLUMN availability_status VARCHAR(50) DEFAULT 'active'");
        echo "<p style='color:blue;'>Successfully added `availability_status` column to `users` table.</p>";
    }

} catch (PDOException $e) {
    echo "<p style='color:red;'>Error updating database: " . $e->getMessage() . "</p>";
}

echo "<p>Migration finished. Please delete this file from your live server.</p>";
