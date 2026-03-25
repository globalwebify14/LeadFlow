<?php
try {
    $pdo = new PDO("mysql:host=127.0.0.1;dbname=lead;charset=utf8mb4", "root", "");
    $pdo->exec("UPDATE modules SET label='API' WHERE name='profile_settings'");
    echo "SUCCESS: Updated label to API\n";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>
