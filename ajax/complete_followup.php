<?php
require_once '../config/auth.php';
requireLogin();
require_once '../config/db.php';
require_once '../models/Followup.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid method']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$id = (int)($data['id'] ?? 0);

if (!$id) {
    echo json_encode(['success' => false, 'error' => 'Invalid ID']);
    exit;
}

$followupModel = new Followup($pdo);
$f = $followupModel->getById($id);

if (!$f) {
    echo json_encode(['success' => false, 'error' => 'Not found']);
    exit;
}

if (!isAdmin() && $f['user_id'] != getUserId()) {
    echo json_encode(['success' => false, 'error' => 'Access denied']);
    exit;
}

$success = $followupModel->complete($id);
echo json_encode(['success' => $success]);
