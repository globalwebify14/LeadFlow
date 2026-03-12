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
