<?php
$pageTitle = 'Notifications';
require_once '../../config/auth.php';
requireLogin();
require_once '../../config/db.php';


$notifModel = new Notification($pdo);
$userId = getUserId();

// Mark all read
if (isset($_GET['mark_all_read'])) {
    $notifModel->markAllRead($userId);
    redirect('notifications.php', 'All notifications marked as read.', 'success');
}

// Mark single as read
if (isset($_GET['mark_read'])) {
    $notifModel->markAsRead((int)$_GET['mark_read']);
    if (isset($_GET['redirect'])) {
        header('Location: ' . $_GET['redirect']);
        exit;
    }
    redirect('notifications.php');
}

$unreadOnly = ($_GET['filter'] ?? '') === 'unread';
$notifications = $notifModel->getForUser($userId, 50, $unreadOnly);
$unreadCount = $notifModel->getUnreadCount($userId);

include '../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <ul class="nav nav-pills">
            <li class="nav-item"><a class="nav-link <?= !$unreadOnly ? 'active' : '' ?>" href="notifications.php">All</a></li>
            <li class="nav-item"><a class="nav-link <?= $unreadOnly ? 'active' : '' ?>" href="?filter=unread">Unread (<?= $unreadCount ?>)</a></li>
        </ul>
    </div>
    <?php if ($unreadCount > 0): ?>
    <a href="?mark_all_read=1" class="btn btn-sm btn-outline-primary">Mark All Read</a>
    <?php endif; ?>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <?php if (count($notifications) > 0): ?>
            <?php foreach ($notifications as $n): ?>
            <div class="d-flex align-items-start p-3 border-bottom <?= $n['is_read'] ? '' : 'bg-light' ?>" style="transition:background 0.2s;">
                <div class="me-3 mt-1">
                    <?php
                    $typeIcons = ['lead_assigned'=>'bi-person-check text-primary','followup_reminder'=>'bi-clock text-warning','deal_stage'=>'bi-trophy text-success','system'=>'bi-gear text-muted'];
                    $icon = $typeIcons[$n['type']] ?? 'bi-bell text-info';
                    ?>
                    <i class="bi <?= $icon ?> fs-5"></i>
                </div>
                <div class="flex-grow-1">
                    <div class="fw-semibold"><?= e($n['title']) ?></div>
                    <div class="text-muted small"><?= e($n['message']) ?></div>
                    <div class="text-muted" style="font-size:11px;"><i class="bi bi-clock me-1"></i><?= timeAgo($n['created_at']) ?></div>
                </div>
                <div class="d-flex gap-1">
                    <?php if ($n['link']): ?><a href="<?= e($n['link']) ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a><?php endif; ?>
                    <?php if (!$n['is_read']): ?><a href="?mark_read=<?= $n['id'] ?>" class="btn btn-sm btn-outline-secondary" title="Mark Read"><i class="bi bi-check"></i></a><?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="text-center text-muted py-5"><i class="bi bi-bell-slash fs-1 d-block mb-3"></i>No notifications</div>
        <?php endif; ?>
    </div>
</div>
<?php include '../../includes/footer.php'; ?>


