<?php
$pageTitle = 'Task Calendar';
require_once '../../config/auth.php';
requireLogin();
require_once '../../config/db.php';
if (getUserRole() === 'super_admin') {
    redirect(BASE_URL . 'modules/dashboard/', 'No permission.', 'danger');
}
require_once '../../models/Task.php';

include '../../includes/header.php';

$orgId = getOrgId();
$userId = (getUserRole() === 'agent') ? getUserId() : null;
$taskModel = new Task($pdo);

// Get tasks for calendar (all pending and completed for the last 2 months and next 2 months)
$filters = [];
if ($userId) $filters['assigned_to'] = $userId;
$tasks = $taskModel->getAllTasks($orgId, $filters, 500);

$events = [];
foreach ($tasks as $t) {
    $events[] = [
        'title' => $t['task_title'],
        'start' => $t['due_date'],
        'url' => BASE_URL . 'modules/leads/view.php?id=' . $t['lead_id'],
        'backgroundColor' => $t['status'] === 'completed' ? '#10b981' : (strtotime($t['due_date']) < time() ? '#ef4444' : '#6366f1'),
        'borderColor' => 'transparent'
    ];
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold mb-0">Calendar View</h4>
    <div class="d-flex gap-2">
        <a href="index.php" class="btn btn-outline-primary btn-sm"><i class="bi bi-list-ul me-1"></i>List View</a>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-4">
        <div id="calendar"></div>
    </div>
</div>

<!-- FullCalendar -->
<link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css' rel='stylesheet' />
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js'></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var calendarEl = document.getElementById('calendar');
    var calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,listWeek'
        },
        events: <?= json_encode($events) ?>,
        eventClick: function(info) {
            if (info.event.url) {
                window.location.href = info.event.url;
                info.jsEvent.preventDefault();
            }
        },
        eventTimeFormat: {
            hour: '2-digit',
            minute: '2-digit',
            meridiem: 'short'
        }
    });
    calendar.render();
});
</script>

<style>
#calendar { min-height: 600px; }
.fc-toolbar-title { font-weight: 700; font-size: 1.25rem !important; }
.fc-button-primary { background-color: #6366f1 !important; border-color: #6366f1 !important; }
.fc-event { cursor: pointer; padding: 2px 5px; border-radius: 4px; }
</style>

<?php include '../../includes/footer.php'; ?>
