<?php
require_once '../../config/auth.php';
requireLogin();
require_once '../../config/db.php';
require_once '../../models/Lead.php';



if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $leadId = (int)$_POST['lead_id'];
    $note = trim($_POST['note']);

    if (!empty($leadId) && !empty($note)) {
        $leadModel = new Lead($pdo);
        $leadModel->addNote($leadId, $note);
    }
    
    header("Location: view_lead.php?id=$leadId&msg=note_added");
    exit;
}
?>


