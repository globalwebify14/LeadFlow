<?php
// config/db.php — auto environment detection
// No need to manually swap credentials before pushing!

$isLocal = in_array($_SERVER['HTTP_HOST'] ?? '', ['localhost', '127.0.0.1', 'localhost:80', 'localhost:8080']) || (php_sapi_name() === 'cli');

if ($isLocal) {
    // ── LOCAL (XAMPP) ──────────────────────────
    $host = 'localhost';
    $username = 'root';
    $password = '';
    $database = 'lead';
} else {
    // ── LIVE SERVER ────────────────────────────
    $host = 'localhost';
    $username = 'u823573651_lead';
    $password = '=jg9G7;p';
    $database = 'u823573651_lead';
}

try {
    $pdo = new PDO("mysql:host=$host;dbname=$database;charset=utf8mb4", $username, $password);
    // Set PDO error mode to exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database Connection Failed: " . $e->getMessage());
}
?>