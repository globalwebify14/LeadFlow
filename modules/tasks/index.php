<?php
$pageTitle = 'Task Management';
require_once '../../config/auth.php';
requireLogin();
require_once '../../config/db.php';
require_once '../../models/Task.php';

$orgId = getOrgId();
$userId = getUserId();
$taskModel = new Task($pdo);

// Handle status update
if (isset($_GET['complete'])) {
    $id = (int)$_GET['complete'];
    $task = $taskModel->getTaskById($id, $orgId);
    
    // Security Check
    if ($task && getUserRole() === 'agent' && $task['assigned_to'] != getUserId()) {
        redirect(BASE_URL . 'modules/tasks/', 'Access denied.', 'danger');
    }

    if ($task) {
        $taskModel->updateTask($id, ['status' => 'completed', 'task_title' => $task['task_title'], 'due_date' => $task['due_date'], 'assigned_to' => $task['assigned_to']]);
        redirect(BASE_URL . 'modules/tasks/', 'Task completed!', 'success');
    }
}

// Filters
$filters = [];
if (getUserRole() === 'agent') {
    $filters['assigned_to'] = $userId;
}
$filters['status'] = $_GET['status'] ?? 'pending';

$tasks = $taskModel->getAllTasks($orgId, $filters);

include '../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0">Tasks</h4>
    <div class="d-flex gap-2">
        <a href="calendar.php" class="btn btn-outline-primary btn-sm px-3"><i class="bi bi-calendar3 me-1"></i>Calendar</a>
        <div class="btn-group shadow-sm">
            <a href="?status=pending" class="btn btn-<?= $filters['status'] === 'pending' ? 'primary' : 'light border' ?> btn-sm px-3">Pending</a>
            <a href="?status=completed" class="btn btn-<?= $filters['status'] === 'completed' ? 'primary' : 'light border' ?> btn-sm px-3">Completed</a>
        </div>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4">Task</th>
                        <th>Lead</th>
                        <th>Due Date</th>
                        <th>Assigned To</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tasks as $t): ?>
                    <tr>
                        <td class="ps-4">
                            <div class="fw-bold text-dark"><?= e($t['task_title']) ?></div>
                            <small class="text-muted d-block text-truncate" style="max-width: 250px;"><?= e($t['description']) ?></small>
                        </td>
                        <td>
                            <?php if ($t['lead_id']): ?>
                            <a href="<?= BASE_URL ?>modules/leads/view.php?id=<?= $t['lead_id'] ?>" class="text-decoration-none small"><i class="bi bi-person me-1"></i><?= e($t['lead_name']) ?></a>
                            <?php else: ?>
                            <span class="text-muted small">-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="small <?= (strtotime($t['due_date']) < time() && $t['status'] === 'pending') ? 'text-danger fw-bold' : 'text-muted' ?>">
                                <?= date('d M Y', strtotime($t['due_date'])) ?>
                            </span>
                        </td>
                        <td><span class="small"><?= e($t['agent_name'] ?: 'Unassigned') ?></span></td>
                        <td class="text-end pe-4">
                            <?php if ($t['status'] === 'pending'): ?>
                            <a href="?complete=<?= $t['id'] ?>" class="btn btn-sm btn-outline-success border-1 shadow-sm"><i class="bi bi-check2 me-1"></i>Complete</a>
                            <?php else: ?>
                            <span class="badge bg-success-subtle text-success">Completed</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($tasks)): ?>
                    <tr>
                        <td colspan="5" class="text-center py-5 text-muted">
                            <i class="bi bi-check2-circle fs-1 d-block mb-3 opacity-25"></i>
                            No tasks found for this view.
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
