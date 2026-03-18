<?php
header('Content-Type: application/json');
require_once '../config/auth.php';
requireLogin();
require_once '../config/db.php';

// Only agents should receive these specific popups (or team leads if they take leads)
$role = getUserRole();
if (!in_array($role, ['agent', 'team_lead'])) {
    echo json_encode(['success' => false, 'message' => 'Not applicable for this role.']);
    exit;
}

$orgId = getOrgId();
$userId = getUserId();

try {
    // Check for unnotified leads assigned to this user
    $stmt = $pdo->prepare("SELECT id, name, phone, source, created_at FROM leads WHERE organization_id = :org_id AND assigned_to = :user_id AND is_seen = 0 ORDER BY created_at ASC LIMIT 1");
    $stmt->execute(['org_id' => $orgId, 'user_id' => $userId]);
    $lead = $stmt->fetch();

    if ($lead) {
        // Mark as notified immediately to prevent duplicate popups
        $updateStmt = $pdo->prepare("UPDATE leads SET is_seen = 1 WHERE id = :id");
        $updateStmt->execute(['id' => $lead['id']]);

        echo json_encode([
            'success' => true,
            'has_new' => true,
            'lead' => [
                'id' => $lead['id'],
                'name' => htmlspecialchars((string)$lead['name']),
                'phone' => htmlspecialchars((string)$lead['phone']),
                'source' => htmlspecialchars((string)$lead['source']),
                'time' => date('h:i A', strtotime($lead['created_at']))
            ]
        ]);
    } else {
        echo json_encode(['success' => true, 'has_new' => false]);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>
