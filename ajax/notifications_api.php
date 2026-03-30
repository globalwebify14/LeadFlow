<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../models/Notification.php';

if (!isLoggedIn()) {
    die("Unauthorized");
}

$notifModel = new Notification($pdo);
$action = $_GET['action'] ?? '';

if ($action === 'mark_all_read') {
    $notifModel->markAllRead($_SESSION['user_id']);
    
    // Redirect the user seamlessly back to the page they clicked the button on
    $referer = $_SERVER['HTTP_REFERER'] ?? BASE_URL . 'modules/dashboard/';
    header("Location: " . $referer);
    exit;
}

if ($action === 'mark_read' && isset($_GET['id'])) {
    header('Content-Type: application/json');
    $notifModel->markAsRead($_GET['id']);
    echo json_encode(['success' => true]);
    exit;
}

echo "Invalid action specified.";
?>
