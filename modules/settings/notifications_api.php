<?php
require_once '../../config/auth.php';
requireLogin();
require_once '../../config/db.php';


$notifModel = new Notification($pdo);
$userId = getUserId();
$action = $_GET['action'] ?? $_POST['action'] ?? '';

if ($action === 'mark_all_read') {
    $notifModel->markAllRead($userId);
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
        jsonResponse(['success' => true]);
    }
    redirect('notifications.php');
}

if ($action === 'unread_count') {
    jsonResponse(['count' => $notifModel->getUnreadCount($userId)]);
}

if ($action === 'recent') {
    $notifications = $notifModel->getForUser($userId, 5);
    jsonResponse(['notifications' => $notifications, 'unread' => $notifModel->getUnreadCount($userId)]);
}

jsonResponse(['error' => 'Invalid action'], 400);


