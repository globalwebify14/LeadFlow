<?php
require_once '../../config/auth.php';
requireLogin();
require_once '../../config/db.php';
require_once '../../models/Deal.php';


if (isset($_GET['id'])) {
    $dealModel = new Deal($pdo);
    $dealModel->deleteDeal((int)$_GET['id']);
    redirect(BASE_URL . 'modules/deals/'', 'Deal deleted.', 'success');
}
redirect(BASE_URL . 'modules/deals/'');


