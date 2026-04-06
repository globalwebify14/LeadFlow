<?php
session_start();
if (isset($_SESSION['user_id'])) {
    require_once __DIR__ . '/config/db.php';
    require_once __DIR__ . '/models/ActivityLog.php';
    ActivityLog::write($pdo, 'logout', 'User logged out', $_SESSION['user_id'], $_SESSION['organization_id'] ?? null);
}
session_destroy();
header('Location: login.php');
exit;
