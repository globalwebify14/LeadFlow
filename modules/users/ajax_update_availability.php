<?php
require_once '../../config/auth.php';
requireLogin();
requireRole(['super_admin', 'org_owner', 'org_admin']);
require_once '../../config/db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = (int)($_POST['user_id'] ?? 0);
    $status = $_POST['status'] ?? 'active';

    if (!$userId) {
        echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
        exit;
    }

    if (!in_array($status, ['active', 'absent', 'inactive'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid status']);
        exit;
    }

    try {
        // Security check: Only allow updating users within the same org, unless super_admin
        if (function_exists('getOrgId') && function_exists('getUserRole')) {
            $orgId = getOrgId();
            if (getUserRole() !== 'super_admin') {
                $stmt = $pdo->prepare("SELECT id FROM users WHERE id = :user AND organization_id = :org");
                $stmt->execute(['user' => $userId, 'org' => $orgId]);
                if (!$stmt->fetch()) {
                    echo json_encode(['success' => false, 'message' => 'Permission denied: Cannot edit users outside your organization']);
                    exit;
                }
            }
        }

        $stmt = $pdo->prepare("UPDATE users SET availability_status = :status WHERE id = :id");
        if ($stmt->execute(['status' => $status, 'id' => $userId])) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database update failed']);
        }
    } catch (PDOException $e) {
        // If the live server is missing the column, this will catch it correctly instead of breaking the JSON!
        echo json_encode(['success' => false, 'message' => 'DB Error: ' . $e->getMessage()]);
    }
    exit;
}
echo json_encode(['success' => false, 'message' => 'Invalid request method']);
