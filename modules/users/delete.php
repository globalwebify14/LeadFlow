<?php
require_once '../../config/auth.php';
requireLogin();
requireRole(['super_admin', 'org_owner', 'org_admin']);
require_once '../../config/db.php';
require_once '../../models/User.php';


if (isset($_GET['id']) && $_GET['id'] != getUserId()) {
    $userModel = new User($pdo);
    $userModel->deleteUser((int)$_GET['id']);
    redirect(BASE_URL . 'modules/users/'', 'User deleted.', 'success');
}
redirect(BASE_URL . 'modules/users/'');


