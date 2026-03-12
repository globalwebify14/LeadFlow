<?php
require_once '../../config/auth.php';
requireLogin();
require_once '../../config/db.php';
require_once '../../models/Lead.php';



header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'error' => 'Invalid method'], 405);
}

$input = json_decode(file_get_contents('php://input'), true);
$leadId = $input['lead_id'] ?? null;
$stageId = $input['stage_id'] ?? null;

if (!$leadId || !$stageId) {
    jsonResponse(['success' => false, 'error' => 'Missing parameters'], 400);
}

$leadModel = new Lead($pdo);
$result = $leadModel->updatePipelineStage($leadId, $stageId, getUserId());
jsonResponse(['success' => $result]);


