<?php
$pageTitle = 'System Monitor';
require_once '../../config/auth.php';
requireLogin();
requireRole(['super_admin']);
require_once '../../config/db.php';
require_once '../../models/ActivityLog.php';

$logModel = new ActivityLog($pdo);

// ── Handle CSV export ────────────────────────────────────────
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $allLogs = $logModel->getAll(2000, 0,
        $_GET['search']   ?? '',
        $_GET['org']      ?? '',
        $_GET['type']     ?? '',
        $_GET['date_from'] ?? '',
        $_GET['date_to']  ?? ''
    );
    $csv = $logModel->exportCsv($allLogs);
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="activity_log_' . date('Y-m-d') . '.csv"');
    echo $csv;
    exit;
}

// ── Filters ──────────────────────────────────────────────────
$search   = trim($_GET['search']   ?? '');
$orgFilter = trim($_GET['org']     ?? '');
$typeFilter = trim($_GET['type']   ?? '');
$dateFrom = trim($_GET['date_from'] ?? '');
$dateTo   = trim($_GET['date_to']   ?? '');
$page     = max(1, (int)($_GET['page'] ?? 1));
$limit    = 30;
$offset   = ($page - 1) * $limit;

// ── Data ─────────────────────────────────────────────────────
$total      = $logModel->count($search, $orgFilter, $typeFilter, $dateFrom, $dateTo);
$totalPages = ceil($total / $limit);
$logs       = $logModel->getAll($limit, $offset, $search, $orgFilter, $typeFilter, $dateFrom, $dateTo);
$dayStats   = $logModel->getDashboardStats();
$alerts     = $logModel->getSecurityAlerts(8);
$actionTypes = $logModel->getActionTypes();

// Organizations for filter
try {
    $orgs = $pdo->query("SELECT id, name FROM organizations ORDER BY name")->fetchAll();
} catch (Exception $e) {
    $orgs = [];
}

include '../../includes/header.php';
?>

<style>
/* ─── Monitor Page ─────────────────────────────────── */
.monitor-hero {
    background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 50%, #0f172a 100%);
    border-radius: 20px;
    padding: 28px 32px;
    margin-bottom: 24px;
    position: relative;
    overflow: hidden;
    border: 1px solid rgba(99,102,241,0.2);
}
.monitor-hero::before {
    content: '';
    position: absolute;
    inset: 0;
    background: radial-gradient(ellipse 600px 300px at 80% 50%, rgba(239,68,68,0.1), transparent),
                radial-gradient(ellipse 400px 200px at 10% 80%, rgba(99,102,241,0.12), transparent);
    pointer-events: none;
}
.monitor-pill {
    display: inline-flex; align-items: center; gap: 6px;
    background: rgba(239,68,68,0.12);
    border: 1px solid rgba(239,68,68,0.3);
    color: #fca5a5; font-size: 11px; font-weight: 700;
    padding: 4px 12px; border-radius: 100px; letter-spacing: 0.5px;
}
.monitor-pulse {
    width: 8px; height: 8px; border-radius: 50%;
    background: #ef4444;
    animation: pulse-ring 1.5s cubic-bezier(0.215,0.61,0.355,1) infinite;
}
@keyframes pulse-ring {
    0%   { box-shadow: 0 0 0 0 rgba(239,68,68,0.5); }
    80%  { box-shadow: 0 0 0 6px rgba(239,68,68,0); }
    100% { box-shadow: 0 0 0 0 rgba(239,68,68,0); }
}

/* KPI row */
.mon-kpi-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 12px;
    margin-bottom: 24px;
}
@media (max-width: 1200px) { .mon-kpi-grid { grid-template-columns: repeat(4, 1fr); } }
@media (max-width: 640px)  { .mon-kpi-grid { grid-template-columns: repeat(2, 1fr); } }

.mon-kpi {
    background: #fff;
    border-radius: 14px;
    padding: 16px 14px;
    border: 1px solid #f1f5f9;
    box-shadow: 0 1px 10px rgba(0,0,0,0.04);
    text-align: center;
}
.mon-kpi .mk-icon {
    width: 36px; height: 36px; border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 16px; margin: 0 auto 10px;
}
.mon-kpi .mk-val { font-size: 1.4rem; font-weight: 800; color: #0f172a; line-height: 1; }
.mon-kpi .mk-lbl { font-size: 10px; font-weight: 600; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.4px; margin-top: 4px; }

/* Filter bar */
.filter-bar {
    background: #fff;
    border-radius: 16px;
    border: 1px solid #f1f5f9;
    padding: 16px 20px;
    margin-bottom: 20px;
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    align-items: flex-end;
}
.filter-bar .form-control,
.filter-bar .form-select {
    border-radius: 10px;
    border: 1px solid #e2e8f0;
    font-size: 13px;
    height: 38px;
    padding: 6px 12px;
}
.filter-bar label { font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.3px; margin-bottom: 4px; }

/* Alert strip */
.alert-strip {
    background: linear-gradient(135deg, #450a0a, #7f1d1d);
    border: 1px solid rgba(239,68,68,0.3);
    border-radius: 16px;
    padding: 0;
    margin-bottom: 20px;
    overflow: hidden;
}
.alert-strip-header {
    padding: 14px 20px;
    display: flex; align-items: center; gap: 10px;
    border-bottom: 1px solid rgba(255,255,255,0.06);
}
.alert-strip-header span { color: #fca5a5; font-size: 13px; font-weight: 700; }
.alert-item {
    display: flex; align-items: flex-start; gap: 12px;
    padding: 12px 20px;
    border-bottom: 1px solid rgba(255,255,255,0.04);
}
.alert-item:last-child { border-bottom: none; }
.alert-dot {
    width: 8px; height: 8px; border-radius: 50%;
    background: #ef4444; flex-shrink: 0; margin-top: 5px;
}
.alert-item .ai-who { font-size: 13px; font-weight: 600; color: #fecaca; }
.alert-item .ai-desc { font-size: 12px; color: rgba(255,255,255,0.5); margin-top: 2px; }
.alert-item .ai-time { font-size: 11px; color: rgba(255,255,255,0.3); margin-left: auto; white-space: nowrap; }

/* Log table */
.log-card {
    background: #fff;
    border-radius: 18px;
    border: 1px solid #f1f5f9;
    box-shadow: 0 1px 12px rgba(0,0,0,0.04);
    overflow: hidden;
}
.log-table { width: 100%; border-collapse: separate; border-spacing: 0; }
.log-table th {
    font-size: 10px; font-weight: 700; color: #94a3b8;
    text-transform: uppercase; letter-spacing: 0.5px;
    padding: 10px 14px 10px; border-bottom: 1px solid #f1f5f9;
    background: #fafafa;
}
.log-table td {
    padding: 10px 14px; font-size: 13px;
    border-bottom: 1px solid #f8fafc;
    vertical-align: middle;
}
.log-table tr:last-child td { border-bottom: none; }
.log-table tr:hover td { background: #f8fafc; }

/* Action badge */
.action-badge {
    display: inline-flex; align-items: center; gap: 5px;
    font-size: 11px; font-weight: 700; padding: 3px 10px;
    border-radius: 20px; white-space: nowrap;
}
.sev-info     { background: #eef2ff; color: #4f46e5; }
.sev-warning  { background: #fffbeb; color: #d97706; }
.sev-critical { background: #fef2f2; color: #dc2626; }

/* Pagination */
.pag-btn {
    display: inline-flex; align-items: center; justify-content: center;
    width: 32px; height: 32px; border-radius: 8px;
    font-size: 13px; font-weight: 600; text-decoration: none;
    border: 1px solid #e2e8f0; color: #475569; background: #fff;
    transition: all .2s;
}
.pag-btn:hover, .pag-btn.active {
    background: #6366f1; color: #fff; border-color: #6366f1;
}
</style>

<?php
// ── Action → badge config ────────────────────────────────────
function actionBadge(string $action, string $severity): string {
    $map = [
        'login_success'   => ['🔐', 'Login',         'sev-info'],
        'login_failed'    => ['⚠️', 'Login Failed',   'sev-critical'],
        'logout'          => ['👋', 'Logout',          'sev-info'],
        'lead_created'    => ['✅', 'Lead Created',   'sev-info'],
        'lead_imported'   => ['📥', 'Lead Import',    'sev-info'],
        'user_created'    => ['👤', 'User Created',   'sev-info'],
        'org_created'     => ['🏢', 'Org Created',    'sev-info'],
        'org_updated'     => ['✏️', 'Org Updated',    'sev-info'],
        'org_suspended'   => ['🚫', 'Org Suspended',  'sev-critical'],
        'schema_change'   => ['🗄️', 'Schema Change',  'sev-critical'],
        'table_dropped'   => ['💥', 'Table Dropped',  'sev-critical'],
    ];
    $cfg  = $map[$action] ?? ['📋', ucfirst(str_replace('_', ' ', $action)), 'sev-' . $severity];
    $cls  = ($severity === 'critical') ? 'sev-critical' : ($severity === 'warning' ? 'sev-warning' : $cfg[2]);
    return "<span class=\"action-badge $cls\">{$cfg[0]} {$cfg[1]}</span>";
}
?>

<!-- ── HERO ──────────────────────────────────────────────── -->
<div class="monitor-hero mb-4" style="position:relative;z-index:1;">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3" style="position:relative;z-index:2;">
        <div>
            <div class="monitor-pill mb-2"><div class="monitor-pulse"></div> LIVE MONITORING</div>
            <h3 style="font-size:1.6rem;font-weight:800;color:#fff;margin:0 0 4px;">System Activity Monitor</h3>
            <p style="color:rgba(255,255,255,0.4);margin:0;font-size:13px;">
                Full audit trail · <?= number_format($total) ?> total events recorded
            </p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="?<?= http_build_query(array_merge($_GET, ['export' => 'csv'])) ?>" class="btn btn-sm btn-light fw-bold">
                <i class="bi bi-download me-1"></i>Export CSV
            </a>
            <a href="<?= BASE_URL ?>modules/superadmin/activity_logs.php" class="btn btn-sm" style="background:rgba(255,255,255,0.1);color:#fff;border:1px solid rgba(255,255,255,0.2);">
                <i class="bi bi-arrow-counterclockwise me-1"></i>Reset Filters
            </a>
        </div>
    </div>
</div>

<!-- ── KPI STATS ROW ─────────────────────────────────────── -->
<div class="mon-kpi-grid">
    <div class="mon-kpi">
        <div class="mk-icon" style="background:#eef2ff;color:#6366f1;"><i class="bi bi-activity"></i></div>
        <div class="mk-val"><?= (int)($dayStats['total_today'] ?? 0) ?></div>
        <div class="mk-lbl">Events Today</div>
    </div>
    <div class="mon-kpi">
        <div class="mk-icon" style="background:#fef2f2;color:#ef4444;"><i class="bi bi-shield-exclamation"></i></div>
        <div class="mk-val" style="color:#ef4444;"><?= (int)($dayStats['critical_today'] ?? 0) ?></div>
        <div class="mk-lbl">Critical</div>
    </div>
    <div class="mon-kpi">
        <div class="mk-icon" style="background:#fef2f2;color:#dc2626;"><i class="bi bi-x-circle"></i></div>
        <div class="mk-val" style="color:#dc2626;"><?= (int)($dayStats['failed_logins'] ?? 0) ?></div>
        <div class="mk-lbl">Failed Logins</div>
    </div>
    <div class="mon-kpi">
        <div class="mk-icon" style="background:#f0fdf4;color:#10b981;"><i class="bi bi-box-arrow-in-right"></i></div>
        <div class="mk-val"><?= (int)($dayStats['successful_logins'] ?? 0) ?></div>
        <div class="mk-lbl">Logins</div>
    </div>
    <div class="mon-kpi">
        <div class="mk-icon" style="background:#eff6ff;color:#3b82f6;"><i class="bi bi-person-plus"></i></div>
        <div class="mk-val"><?= (int)($dayStats['leads_created'] ?? 0) ?></div>
        <div class="mk-lbl">Leads Added</div>
    </div>
    <div class="mon-kpi">
        <div class="mk-icon" style="background:#fff7ed;color:#f97316;"><i class="bi bi-file-earmark-spreadsheet"></i></div>
        <div class="mk-val"><?= (int)($dayStats['leads_imported'] ?? 0) ?></div>
        <div class="mk-lbl">Imported</div>
    </div>
    <div class="mon-kpi">
        <div class="mk-icon" style="background:#fdf4ff;color:#a855f7;"><i class="bi bi-people"></i></div>
        <div class="mk-val"><?= (int)($dayStats['users_created'] ?? 0) ?></div>
        <div class="mk-lbl">Users Created</div>
    </div>
</div>

<!-- ── SECURITY ALERTS ───────────────────────────────────── -->
<?php if (!empty($alerts)): ?>
<div class="alert-strip mb-4">
    <div class="alert-strip-header">
        <i class="bi bi-shield-fill-exclamation text-danger fs-5"></i>
        <span>Security Alerts &amp; Critical Events</span>
        <span style="font-size:11px;color:rgba(255,255,255,0.3);margin-left:auto;">Last <?= count($alerts) ?> critical events</span>
    </div>
    <?php foreach ($alerts as $a): ?>
    <div class="alert-item">
        <div class="alert-dot"></div>
        <div style="flex:1;">
            <div class="ai-who">
                <?= htmlspecialchars(str_replace('_', ' ', ucfirst($a['action']))) ?>
                <?php if ($a['user_name']): ?>
                    <span style="color:rgba(255,255,255,0.4);font-weight:400;"> — <?= htmlspecialchars($a['user_name']) ?></span>
                <?php endif; ?>
                <?php if ($a['org_name']): ?>
                    <span style="color:rgba(255,255,255,0.3);font-weight:400;font-size:11px;"> [<?= htmlspecialchars($a['org_name']) ?>]</span>
                <?php endif; ?>
            </div>
            <div class="ai-desc"><?= htmlspecialchars($a['description'] ?? '') ?> · IP: <?= htmlspecialchars($a['ip_address'] ?? '—') ?></div>
        </div>
        <div class="ai-time"><?= timeAgo($a['created_at']) ?></div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- ── FILTER BAR ────────────────────────────────────────── -->
<form method="GET" class="filter-bar">
    <div>
        <label>Search</label>
        <input type="text" name="search" class="form-control" placeholder="Action, user, description…" value="<?= e($search) ?>" style="width:200px;">
    </div>
    <div>
        <label>Organization</label>
        <select name="org" class="form-select" style="width:160px;">
            <option value="">All Orgs</option>
            <?php foreach ($orgs as $o): ?>
                <option value="<?= $o['id'] ?>" <?= $orgFilter == $o['id'] ? 'selected' : '' ?>><?= e($o['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div>
        <label>Event Type</label>
        <select name="type" class="form-select" style="width:160px;">
            <option value="">All Types</option>
            <?php foreach ($actionTypes as $t): ?>
                <option value="<?= e($t) ?>" <?= $typeFilter === $t ? 'selected' : '' ?>><?= e(str_replace('_', ' ', ucfirst($t))) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div>
        <label>From</label>
        <input type="date" name="date_from" class="form-control" value="<?= e($dateFrom) ?>" style="width:140px;">
    </div>
    <div>
        <label>To</label>
        <input type="date" name="date_to" class="form-control" value="<?= e($dateTo) ?>" style="width:140px;">
    </div>
    <div style="margin-top:auto;">
        <button type="submit" class="btn btn-primary btn-sm fw-bold px-4" style="height:38px;">Filter</button>
    </div>
    <?php if ($search || $orgFilter || $typeFilter || $dateFrom || $dateTo): ?>
    <div style="margin-top:auto;">
        <a href="<?= BASE_URL ?>modules/superadmin/activity_logs.php" class="btn btn-outline-secondary btn-sm fw-bold px-3" style="height:38px;">Clear</a>
    </div>
    <?php endif; ?>
</form>

<!-- ── LOG TABLE ─────────────────────────────────────────── -->
<div class="log-card">
    <div style="padding:16px 20px 0;display:flex;align-items:center;justify-content:space-between;">
        <span style="font-size:14px;font-weight:700;color:#0f172a;">
            <i class="bi bi-journal-text me-2" style="color:#6366f1;"></i>Event Log
        </span>
        <span style="font-size:12px;color:#94a3b8;">
            Showing <?= min($offset + 1, $total) ?>–<?= min($offset + $limit, $total) ?> of <?= number_format($total) ?> events
        </span>
    </div>
    <div style="padding:12px 0 0;overflow-x:auto;">
        <table class="log-table">
            <thead>
                <tr>
                    <th>When</th>
                    <th>Event</th>
                    <th>Description</th>
                    <th>User</th>
                    <th>Organization</th>
                    <th>IP Address</th>
                    <th>Severity</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)): ?>
                <tr>
                    <td colspan="7" class="text-center py-5" style="color:#94a3b8;">
                        <i class="bi bi-journal-x" style="font-size:2rem;display:block;margin-bottom:8px;"></i>
                        No events found<?= $search ? ' matching "' . e($search) . '"' : '' ?>
                    </td>
                </tr>
                <?php endif; ?>
                <?php foreach ($logs as $log): ?>
                <tr>
                    <td style="white-space:nowrap;color:#64748b;font-size:12px;">
                        <?= date('d M, h:i A', strtotime($log['created_at'])) ?>
                    </td>
                    <td><?= actionBadge($log['action'], $log['severity'] ?? 'info') ?></td>
                    <td style="max-width:280px;color:#475569;"><?= e(truncate($log['description'] ?? '', 70)) ?></td>
                    <td>
                        <?php if ($log['user_name']): ?>
                        <span style="font-size:12px;font-weight:600;color:#1e293b;"><?= e($log['user_name']) ?></span>
                        <?php else: ?>
                        <span style="font-size:12px;color:#94a3b8;">System</span>
                        <?php endif; ?>
                    </td>
                    <td style="font-size:12px;color:#64748b;"><?= e($log['org_name'] ?? '—') ?></td>
                    <td>
                        <code style="font-size:11px;background:#f8fafc;padding:2px 6px;border-radius:5px;color:#475569;">
                            <?= e($log['ip_address'] ?? '—') ?>
                        </code>
                    </td>
                    <td>
                        <?php $sev = $log['severity'] ?? 'info'; ?>
                        <span class="action-badge sev-<?= $sev ?>" style="font-size:10px;">
                            <?= $sev === 'critical' ? '🔴' : ($sev === 'warning' ? '🟡' : '🟢') ?>
                            <?= ucfirst($sev) ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
    <div style="padding:14px 20px;display:flex;align-items:center;justify-content:space-between;border-top:1px solid #f1f5f9;">
        <small style="color:#94a3b8;">Page <?= $page ?> of <?= $totalPages ?></small>
        <div style="display:flex;gap:4px;flex-wrap:wrap;">
            <?php if ($page > 1): ?>
            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>" class="pag-btn"><i class="bi bi-chevron-left"></i></a>
            <?php endif; ?>
            <?php for ($p = max(1, $page - 3); $p <= min($totalPages, $page + 3); $p++): ?>
            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $p])) ?>" class="pag-btn <?= $p === $page ? 'active' : '' ?>"><?= $p ?></a>
            <?php endfor; ?>
            <?php if ($page < $totalPages): ?>
            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>" class="pag-btn"><i class="bi bi-chevron-right"></i></a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Auto-refresh every 30 seconds -->
<script>
setTimeout(() => { if (!document.querySelector('form input:focus, form select:focus')) window.location.reload(); }, 30000);
</script>

<?php include '../../includes/footer.php'; ?>
