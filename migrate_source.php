<?php
require_once 'config/db.php';
try {
    $pdo->exec("ALTER TABLE leads MODIFY COLUMN source ENUM('facebook', 'manual', 'import', 'Website', 'Organic / No Ad', 'Facebook Ads', 'Organic') DEFAULT 'manual'");
    echo "SUCCESS";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
?>
