<?php
// automation_worker.php - Run this via cron or manually for testing
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../models/Lead.php';
require_once __DIR__ . '/../models/Task.php';
require_once __DIR__ . '/../models/Automation.php';

$leadModel = new Lead($pdo);
$taskModel = new Task($pdo);

// 1. Find all active progresses
$stmt = $pdo->query("SELECT p.*, l.organization_id, l.created_at as lead_created_at, l.assigned_to 
                     FROM lead_automation_progress p 
                     JOIN leads l ON p.lead_id = l.id");
$progresses = $stmt->fetchAll();

foreach ($progresses as $p) {
    $leadId = $p['lead_id'];
    $sequenceId = $p['sequence_id'];
    $orgId = $p['organization_id'];
    $assignedTo = $p['assigned_to'];
    
    // Calculate days since start
    $daysSince = floor((time() - strtotime($p['started_at'])) / 86400);
    
    // Find steps for this sequence that are due and NOT yet executed
    // We check day_offset and id to ensure we only move forward
    $lastDayOffset = 0;
    if ($p['last_step_id']) {
        $lastStmt = $pdo->prepare("SELECT day_offset FROM automation_steps WHERE id = ?");
        $lastStmt->execute([$p['last_step_id']]);
        $lastDayOffset = $lastStmt->fetchColumn();
    }

    $stepSql = "SELECT * FROM automation_steps 
                WHERE sequence_id = :sid 
                AND day_offset <= :days 
                AND (day_offset > :last_day OR (day_offset = :last_day AND id > :last_id) OR :last_id_is_null = 1)
                ORDER BY day_offset ASC, id ASC";
    
    $stepStmt = $pdo->prepare($stepSql);
    $stepStmt->execute([
        'sid' => $sequenceId,
        'days' => $daysSince,
        'last_id' => $p['last_step_id'],
        'last_day' => $lastDayOffset,
        'last_id_is_null' => $p['last_step_id'] === null ? 1 : 0
    ]);
    
    $dueSteps = $stepStmt->fetchAll();
    
    foreach ($dueSteps as $step) {
        // Execute step
        executeStep($step, $leadId, $orgId, $assignedTo, $leadModel, $taskModel, $pdo);
        
        // Update progress
        $upd = $pdo->prepare("UPDATE lead_automation_progress SET last_step_id = :step_id, last_executed_at = CURRENT_TIMESTAMP WHERE id = :id");
        $upd->execute(['step_id' => $step['id'], 'id' => $p['id']]);
    }
}

function executeStep($step, $leadId, $orgId, $assignedTo, $leadModel, $taskModel, $pdo) {
    switch ($step['action_type']) {
        case 'task':
            $taskModel->createTask([
                'organization_id' => $orgId,
                'lead_id' => $leadId,
                'assigned_to' => $assignedTo,
                'task_title' => $step['action_data'],
                'description' => 'Automated task from sequence',
                'due_date' => date('Y-m-d H:i:s', strtotime('+1 day')),
                'status' => 'pending'
            ]);
            $leadModel->logActivity($leadId, 'task', 'Automation: Task created - ' . $step['action_data'], null, null, null);
            break;
            
        case 'whatsapp':
            // Logic to notify agent to send WhatsApp
            $leadModel->logActivity($leadId, 'note', 'Automation: WhatsApp Reminder - ' . $step['action_data'], null, null, null);
            break;
            
        case 'email':
            // Logic to send email
            $leadModel->logActivity($leadId, 'email', 'Automation: Email sent - ' . $step['action_data'], null, null, null);
            break;
    }
}

echo "Automation worker finished.\n";
?>
