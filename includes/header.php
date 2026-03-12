<?php
require_once __DIR__ . '/../models/Notification.php';
$notifModel = new Notification($pdo);
$unreadCount = $notifModel->getUnreadCount($_SESSION['user_id'] ?? 0);
$recentNotifs = $notifModel->getForUser($_SESSION['user_id'] ?? 0, 5);
$flash = getFlashMessage();

$orgLogoHeader = null;
if (!empty($_SESSION['organization_id'])) {
    $stmtLogo = $pdo->prepare("SELECT logo FROM organizations WHERE id = ?");
    $stmtLogo->execute([$_SESSION['organization_id']]);
    $orgLogoHeader = $stmtLogo->fetchColumn();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? $pageTitle . ' - Lead CRM' : 'Lead CRM' ?></title>
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="<?= BASE_URL ?>assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="d-flex" id="wrapper">
        <?php include __DIR__ . '/sidebar.php'; ?>

        <div id="page-content-wrapper" class="w-100">
            <nav class="navbar navbar-expand-lg px-4 py-3" style="position:relative;z-index:1050;">
                <div class="d-flex align-items-center">
                    <button class="btn btn-outline-primary shadow-sm rounded-pill px-3 me-3 border-1 bg-white text-primary" id="menu-toggle" style="border-color: #e2e8f0 !important;"><i class="bi bi-list"></i></button>
                    <h5 class="m-0 text-dark fw-bold"><?= $pageTitle ?? 'Dashboard' ?></h5>
                </div>
                
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent"><span class="navbar-toggler-icon"></span></button>

                <div class="collapse navbar-collapse" id="navbarContent">
                    <form class="ms-auto d-flex my-2 my-lg-0" action="leads.php" method="GET">
                        <div class="input-group" style="width: 300px;">
                            <input type="text" class="form-control" placeholder="Search leads..." name="search">
                            <button class="btn btn-search" type="submit"><i class="bi bi-search"></i></button>
                        </div>
                    </form>
                    <ul class="navbar-nav ms-3 align-items-center">
                        <!-- Notifications -->
                        <li class="nav-item dropdown me-2">
                            <a class="nav-link position-relative p-2" href="#" id="notifDropdown" role="button" data-bs-toggle="dropdown" data-bs-auto-close="outside">
                                <i class="bi bi-bell fs-5 text-muted"></i>
                                <?php if ($unreadCount > 0): ?>
                                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size:10px;"><?= $unreadCount > 9 ? '9+' : $unreadCount ?></span>
                                <?php endif; ?>
                            </a>
                            <div class="dropdown-menu dropdown-menu-end shadow-lg border-0 p-0" style="width:340px;border-radius:16px;" aria-labelledby="notifDropdown">
                                <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0 fw-bold">Notifications</h6>
                                    <?php if ($unreadCount > 0): ?>
                                        <a href="notifications_api.php?action=mark_all_read" class="text-primary small text-decoration-none">Mark all read</a>
                                    <?php endif; ?>
                                </div>
                                <div style="max-height:300px;overflow-y:auto;">
                                    <?php if (count($recentNotifs) > 0): ?>
                                        <?php foreach ($recentNotifs as $n): ?>
                                            <a href="<?= e($n['link'] ?: '#') ?>" class="dropdown-item px-3 py-2 border-bottom <?= $n['is_read'] ? '' : 'bg-light' ?>" style="white-space:normal;">
                                                <div class="fw-semibold small"><?= e($n['title']) ?></div>
                                                <div class="text-muted" style="font-size:12px;"><?= e($n['message']) ?></div>
                                                <div class="text-muted" style="font-size:11px;"><i class="bi bi-clock me-1"></i><?= timeAgo($n['created_at']) ?></div>
                                            </a>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="text-center text-muted p-4">No notifications</div>
                                    <?php endif; ?>
                                </div>
                                <div class="p-2 text-center border-top">
                                    <a href="<?= BASE_URL ?>modules/settings/notifications.php" class="text-primary text-decoration-none small">View All</a>
                                </div>
                            </div>
                        </li>
                        <!-- User Menu -->
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle fw-semibold text-dark d-flex align-items-center" href="#" data-bs-toggle="dropdown">
                                <?php if (!empty($orgLogoHeader) && in_array(getUserRole(), ['org_owner', 'org_admin'])): ?>
                                    <img src="<?= e($orgLogoHeader) ?>" class="rounded-circle me-2 border" style="width:34px;height:34px;object-fit:cover;" alt="Logo">
                                <?php else: ?>
                                    <div class="rounded-circle d-flex align-items-center justify-content-center me-2 text-white" style="width:34px;height:34px;font-size:13px;background:linear-gradient(135deg,#6366f1,#4f46e5);">
                                        <?= strtoupper(substr(getUserName(), 0, 1)) ?>
                                    </div>
                                <?php endif; ?>
                                <?= e(getUserName()) ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                                <li><span class="dropdown-item-text small text-muted"><?= e(getUserRoleName()) ?></span></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="<?= BASE_URL ?>modules/dashboard/"><i class="bi bi-speedometer2 me-2"></i>Dashboard</a></li>
                                <li><a class="dropdown-item" href="<?= BASE_URL ?>modules/settings/"><i class="bi bi-gear me-2"></i>Settings</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger" href="<?= BASE_URL ?>logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                            </ul>
                        </li>
                    </ul>
                </div>
            </nav>
            <div class="container-fluid px-4 pt-3 pb-5">
                <?php if ($flash): ?>
                    <div class="alert alert-<?= $flash['type'] === 'danger' ? 'danger' : ($flash['type'] === 'warning' ? 'warning' : 'success') ?> alert-dismissible fade show border-0 shadow-sm" role="alert" style="border-radius:12px;">
                        <i class="bi <?= $flash['type'] === 'danger' ? 'bi-exclamation-circle' : 'bi-check-circle' ?> me-1"></i>
                        <?= e($flash['message']) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
