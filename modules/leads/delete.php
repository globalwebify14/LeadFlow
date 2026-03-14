<?php
require_once '../../config/auth.php';
requireLogin();
require_once '../../config/db.php';
require_once '../../models/Lead.php';



if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = (int)$_GET['id'];
    $leadModel = new Lead($pdo);
    
    // Security check for agents
    $lead = $leadModel->getLeadById($id, getOrgId());
    if ($lead && getUserRole() === 'agent' && $lead['assigned_to'] != getUserId()) {
        header("Location: leads.php?msg=access_denied");
        exit;
    }

    if ($lead) {
        $leadModel->deleteLead($id);
    }
}

header("Location: leads.php?msg=deleted");
exit;
?>
