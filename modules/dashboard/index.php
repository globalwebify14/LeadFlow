<?php
$pageTitle = 'Dashboard';
require_once '../../config/auth.php';
requireLogin();
require_once '../../config/db.php';
require_once '../../models/Dashboard.php';
require_once '../../models/Followup.php';

$orgId = getOrgId();
$userId = getUserId();
$userRole = getUserRole();

$dashboard = new Dashboard($pdo);
$followupModel = new Followup($pdo);

// Handle Quick Task Creation from Dashboard
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_task'])) {
    require_once '../../models/Task.php';
    $taskModel = new Task($pdo);
    $assignedTo = !empty($_POST['assigned_to']) ? (int)$_POST['assigned_to'] : $userId;
    
    $taskModel->createTask([
        'organization_id' => $orgId,
        'lead_id'         => null,
        'assigned_to'     => $assignedTo,
        'task_title'      => trim($_POST['task_title']),
        'description'     => trim($_POST['description']),
        'due_date'        => $_POST['due_date'],
        'status'          => 'pending'
    ]);
    $msg = urlencode("Internal task '{$_POST['task_title']}' created successfully!");
    header("Location: " . BASE_URL . "modules/dashboard/?msg=" . $msg);
    exit;
}

// Fetch agents for task assignment
$orgAgents = [];
$agentsStmt = $pdo->prepare("SELECT id, name FROM users WHERE organization_id = :org AND is_active = 1 ORDER BY name");
$agentsStmt->execute(['org' => $orgId]);
$orgAgents = $agentsStmt->fetchAll();

include '../../includes/header.php';

// Route to correct dashboard view based on role
if ($userRole === 'super_admin') {
    include 'views/super_admin.php';
} elseif ($userRole === 'org_owner') {
    include 'views/org_owner.php';
} elseif ($userRole === 'org_admin') {
    include 'views/org_admin.php';
} elseif ($userRole === 'team_lead') {
    include 'views/team_lead.php';
} else {
    include 'views/agent.php';
}

include '../../includes/footer.php';
?>
