<?php
require_once '../../config/auth.php';
requireLogin();
requireRole('org_owner');
require_once '../../config/db.php';

$pageTitle = 'Webhook Debugger';
include '../../includes/header.php';
?>

<div class="mb-4">
    <h4 class="fw-bold">Webhook Diagnostic Tool</h4>
    <p class="text-muted">Use this to verify if your server can reach Meta and if webhooks are arriving.</p>
</div>

<div class="row g-4">
    <div class="col-md-6">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white border-0 pt-4 fw-bold">Connectivity Test</div>
            <div class="card-body">
                <?php
                $host = 'graph.facebook.com';
                $ip = gethostbyname($host);
                $canResolve = ($ip !== $host);
                ?>
                <div class="d-flex align-items-center mb-3">
                    <div class="me-3">
                        <i class="bi bi-globe fs-2 <?= $canResolve ? 'text-success' : 'text-danger' ?>"></i>
                    </div>
                    <div>
                        <div class="fw-bold">DNS Resolution</div>
                        <div class="small text-muted"><?= $host ?> &rarr; <?= $ip ?></div>
                    </div>
                </div>
                
                <?php if ($canResolve): ?>
                    <div class="alert alert-success border-0 small py-2">
                        <i class="bi bi-check-circle me-1"></i> Server can resolve Meta's domain.
                    </div>
                <?php else: ?>
                    <div class="alert alert-danger border-0 small py-2">
                        <i class="bi bi-x-circle me-1"></i> <strong>DNS Failed!</strong> Your server cannot find <code>graph.facebook.com</code>. Please contact your hosting provider.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white border-0 pt-4 fw-bold">Recent Webhook Activity</div>
            <div class="card-body">
                <p class="text-muted small">Showing last 10 events captured in <code>webhook_logs</code> table.</p>
                <div class="table-responsive">
                    <table class="table table-sm small">
                        <thead>
                            <tr>
                                <th>Type</th>
                                <th>Time</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $stmt = $pdo->query("SELECT id, event_type, created_at, payload FROM webhook_logs ORDER BY id DESC LIMIT 10");
                            $logs = $stmt->fetchAll();
                            foreach ($logs as $log):
                                $badgeClass = ($log['event_type'] === 'leadgen') ? 'bg-success' : (($log['event_type'] === 'error') ? 'bg-danger' : 'bg-secondary');
                            ?>
                            <tr>
                                <td><span class="badge <?= $badgeClass ?>"><?= e($log['event_type']) ?></span></td>
                                <td class="text-muted"><?= date('H:i:s', strtotime($log['created_at'])) ?></td>
                                <td>
                                    <button class="btn btn-link btn-sm p-0" onclick='viewPayload(<?= json_encode($log['payload']) ?>)'>View</button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($logs)): ?>
                                <tr><td colspan="3" class="text-center text-muted">No logs found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Payload Modal -->
<div class="modal fade" id="payloadModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Webhook Payload</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <pre id="payloadContent" class="bg-light p-3 rounded small" style="white-space: pre-wrap; word-break: break-all;"></pre>
            </div>
        </div>
    </div>
</div>

<script>
function viewPayload(payload) {
    try {
        const obj = JSON.parse(payload);
        document.getElementById('payloadContent').textContent = JSON.stringify(obj, null, 2);
    } catch(e) {
        document.getElementById('payloadContent').textContent = payload;
    }
    new bootstrap.Modal(document.getElementById('payloadModal')).show();
}
</script>

<?php include '../../includes/footer.php'; ?>
