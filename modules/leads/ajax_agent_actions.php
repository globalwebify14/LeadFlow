<?php
require_once '../../config/auth.php';
require_once '../../config/db.php';
require_once '../../models/Lead.php';

requireLogin();

// Only allow agents or above
if (!in_array(getUserRole(), ['org_admin', 'team_lead', 'agent', 'org_owner'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$orgId = getOrgId();
$userId = getUserId();
$leadModel = new Lead($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $leadId = $_POST['lead_id'] ?? 0;

    // Security check: ensure the lead belongs to the user's organization 
    // and if auth user is 'agent', ensure the lead is assigned to them.
    $lead = $leadModel->getLeadById($leadId, $orgId);
    if (!$lead) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Lead not found']);
        exit;
    }

    if (getUserRole() === 'agent' && $lead['assigned_to'] != $userId) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Unauthorized lead access']);
        exit;
    }

    if ($action === 'update_status') {
        $status = $_POST['status'] ?? '';
        $validStatuses = ['New Lead', 'Contacted', 'Working', 'Qualified', 'Processing', 'Proposal Sent', 'Follow Up', 'Negotiation', 'Not Picked', 'Done', 'Closed Won', 'Closed Lost', 'Rejected'];
        
        if (in_array($status, $validStatuses)) {
            $success = $leadModel->updateStatus($leadId, $status, 'Status updated via Agent Quick Action', $userId);
            if ($success) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update status']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid status']);
        }
        exit;
    }

    if ($action === 'add_note') {
        $note = trim($_POST['note'] ?? '');
        if (!empty($note)) {
            $success = $leadModel->addNote($leadId, $note, $userId);
            if ($success) {
                // Update the lead's main context note as well for quick view
                $stmt = $pdo->prepare("UPDATE leads SET note = :note WHERE id = :id");
                $stmt->execute(['note' => $note, 'id' => $leadId]);
                
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to add note']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Note cannot be empty']);
        }
        exit;
    }
}

http_response_code(400);
echo json_encode(['success' => false, 'message' => 'Invalid request']);
