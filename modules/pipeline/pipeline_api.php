<?php
header('Content-Type: application/json');
require_once '../../config/auth.php';
requireLogin();
require_once '../../config/db.php';
if (getUserRole() === 'super_admin') {
    echo json_encode(['success' => false, 'message' => 'No permission']);
    exit;
}
require_once '../../models/Lead.php';

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['lead_id']) || !isset($input['stage_id'])) {
    echo json_encode(['success' => false, 'message' => 'Missing data']);
    exit;
}

$orgId = getOrgId();
$leadId = (int)$input['lead_id'];
$stageId = (int)$input['stage_id'];

// Verify lead belongs to org
$stmt = $pdo->prepare("SELECT id, pipeline_stage_id, assigned_to FROM leads WHERE id = :id AND organization_id = :org");
$stmt->execute(['id' => $leadId, 'org' => $orgId]);
$lead = $stmt->fetch();

if (!$lead) {
    echo json_encode(['success' => false, 'message' => 'Lead not found']);
    exit;
}

if (getUserRole() === 'agent' && $lead['assigned_to'] != getUserId()) {
    echo json_encode(['success' => false, 'message' => 'Access denied. You can only move leads assigned to you.']);
    exit;
}

$leadModel = new Lead($pdo);
$success = $leadModel->updatePipelineStage($leadId, $stageId, getUserId());

if ($success) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Update failed']);
}
?>
