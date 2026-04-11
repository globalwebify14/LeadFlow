<?php
header('Content-Type: application/json');
require_once '../config/db.php';
require_once '../core/helpers.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Session expired.']);
    exit;
}

// Allow agents, team leads, and organization owners to receive alerts
$role = getUserRole();
if (!in_array($role, ['agent', 'team_lead', 'org_owner'])) {
    echo json_encode(['success' => false, 'message' => 'Not applicable for this role.']);
    exit;
}

$orgId = getOrgId();
$userId = getUserId();

try {
    // Determine the query based on role
    // Agents/Team Leads only see leads assigned to them.
    // Owners see leads assigned to them OR unassigned leads.
    if ($role === 'org_owner') {
        $stmt = $pdo->prepare("SELECT id, name, phone, source, created_at FROM leads WHERE organization_id = :org_id AND (assigned_to = :user_id OR assigned_to IS NULL) AND is_seen = 0 ORDER BY created_at ASC LIMIT 1");
    } else {
        $stmt = $pdo->prepare("SELECT id, name, phone, source, created_at FROM leads WHERE organization_id = :org_id AND assigned_to = :user_id AND is_seen = 0 ORDER BY created_at ASC LIMIT 1");
    }
    
    $stmt->execute(['org_id' => $orgId, 'user_id' => $userId]);
    $lead = $stmt->fetch();

    if ($lead) {
        // Log it for antigravity debug
        file_put_contents('../tmp_ajax_debug.txt', date('H:i:s') . " - Found lead for User $userId: " . $lead['id'] . "\n", FILE_APPEND);
        
        // Mark as notified immediately to prevent duplicate popups
        $updateStmt = $pdo->prepare("UPDATE leads SET is_seen = 1 WHERE id = :id");
        $updateStmt->execute(['id' => $lead['id']]);

        echo json_encode([
            'success' => true,
            'has_new' => true,
            'lead' => [
                'id'     => $lead['id'],
                'name'   => strip_tags((string)$lead['name']),
                'phone'  => strip_tags((string)$lead['phone']),
                'source' => strip_tags((string)$lead['source']),
                'time'   => date('h:i A', strtotime($lead['created_at']))
            ]
        ], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode(['success' => true, 'has_new' => false]);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>
