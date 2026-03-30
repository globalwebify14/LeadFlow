<?php
session_start();

// Calculate the exact BASE_URL automatically to prevent broken CSS/links on live servers
$docRoot = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']);
$dirRoot = str_replace('\\', '/', dirname(__DIR__));
$basePath = str_replace($docRoot, '', $dirRoot);
$basePath = ($basePath === '') ? '/' : $basePath . '/';
define('BASE_URL', $basePath);

require_once __DIR__ . '/../core/helpers.php';

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . 'login.php');
        exit;
    }

    // Check if organization is still active (skip for super_admin)
    $role = $_SESSION['user_role'] ?? '';
    $orgId = $_SESSION['organization_id'] ?? null;
    if ($role !== 'super_admin' && $orgId) {
        require_once __DIR__ . '/db.php';
        $orgCheck = $pdo->prepare("SELECT status FROM organizations WHERE id = :id LIMIT 1");
        $orgCheck->execute(['id' => $orgId]);
        $orgData = $orgCheck->fetch();
        if ($orgData && $orgData['status'] !== 'active') {
            // Destroy session and redirect to login
            session_destroy();
            session_start();
            $_SESSION['login_error'] = 'Your organization account has been suspended. Please contact support.';
            header('Location: ' . BASE_URL . 'login.php?suspended=1');
            exit;
        }
    }
}

function getUserName() {
    return $_SESSION['user_name'] ?? 'Admin';
}

function getUserEmail() {
    return $_SESSION['user_email'] ?? '';
}

function getUserRoleName() {
    $role = $_SESSION['user_role'] ?? 'agent';
    $names = [
        'super_admin' => 'Super Admin',
        'org_owner'   => 'Org Owner',
        'org_admin'   => 'Org Admin',
        'team_lead'   => 'Team Lead',
        'agent'       => 'Sales Agent',
    ];
    return $names[$role] ?? 'Agent';
}
?>
