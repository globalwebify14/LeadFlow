<?php
require_once '../../config/auth.php';
requireLogin();
requireRole(['super_admin', 'org_owner', 'org_admin']);
require_once '../../config/db.php';
require_once '../../models/Role.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $roleId = $_POST['id'] ?? null;
    $orgId = getOrgId();
    
    if ($roleId) {
        $roleModel = new Role($pdo);
        if ($roleModel->deleteRole($roleId, $orgId)) {
            redirect(BASE_URL . 'modules/settings/roles.php', 'Role deleted successfully.', 'success');
        } else {
            redirect(BASE_URL . 'modules/settings/roles.php', 'Failed to delete role. It may be a system role or in use.', 'danger');
        }
    }
}

redirect(BASE_URL . 'modules/settings/roles.php');
