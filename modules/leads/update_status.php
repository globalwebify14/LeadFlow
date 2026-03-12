<?php
require_once '../../config/auth.php';
requireLogin();
require_once '../../config/db.php';
require_once '../../models/Lead.php';



if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $leadId = (int)$_POST['lead_id'];
    $status = trim($_POST['status']);
    $note = trim($_POST['note']);

    if (!empty($leadId) && !empty($status)) {
        $leadModel = new Lead($pdo);
        // Only log note if it exists, otherwise just state "Status updated to X"
        $logMessage = $note ?: "Status updated to " . $status;
        $leadModel->updateStatus($leadId, $status, $logMessage);
    }
    
    header("Location: view_lead.php?id=$leadId&msg=status_updated");
    exit;
}
?>


