<?php
// agent.php view
/** @var Dashboard $dashboard */
/** @var Followup $followupModel */
$stats         = $dashboard->getStatistics($orgId, $userId, 'agent');
$recentLeads   = $dashboard->getRecentLeads($orgId, 6, $userId, 'agent');
$todayFollowups= $dashboard->getTodayFollowups($orgId, $userId);
$overdueCount  = $followupModel->getOverdueCount($orgId, $userId);
?>

<style>
/* ============================================================
   AGENT DASHBOARD — Admin UI Replica (Premium Dark/Glass)
   ============================================================ */

/* Welcome Hero */
.dash-hero {
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 60%, #1a1040 100%);
    border-radius: 20px;
    padding: 32px 36px;
    margin-bottom: 28px;
    position: relative;
    overflow: hidden;
    border: 1px solid rgba(99,102,241,0.18);
    box-shadow: 0 8px 40px rgba(0,0,0,0.18);
}
.dash-hero::before {
    content: '';
    position: absolute;
    inset: 0;
    background:
        radial-gradient(ellipse 500px 300px at 80% 50%, rgba(99,102,241,0.18), transparent),
        radial-gradient(ellipse 300px 200px at 10% 80%, rgba(14,165,233,0.12), transparent);
    pointer-events: none;
}
.dash-hero .hero-grid-lines {
    position: absolute;
    inset: 0;
    background-image:
        linear-gradient(rgba(255,255,255,0.025) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255,255,255,0.025) 1px, transparent 1px);
    background-size: 40px 40px;
    pointer-events: none;
}
.dash-hero h3 { font-size: 1.9rem; font-weight: 800; color: #fff; margin: 0 0 4px; letter-spacing: -0.5px; }
.dash-hero p  { color: rgba(255,255,255,0.5); margin: 0; font-size: 0.88rem; }
.hero-role-badge {
    display: inline-flex; align-items: center; gap: 6px;
    background: rgba(99,102,241,0.15);
    border: 1px solid rgba(99,102,241,0.3);
    color: #a5b4fc;
    font-size: 11px; font-weight: 600; padding: 4px 12px;
    border-radius: 100px; margin-bottom: 10px; letter-spacing: 0.5px;
}
.hero-actions { display: flex; gap: 10px; flex-wrap: wrap; }
.btn-hero-primary {
    display: inline-flex; align-items: center; gap: 7px;
    background: linear-gradient(135deg,#6366f1,#4f46e5);
    color: #fff; border: none; border-radius: 10px;
    padding: 10px 20px; font-size: 13px; font-weight: 600;
    cursor: pointer; transition: all .25s; text-decoration: none;
    box-shadow: 0 4px 20px rgba(99,102,241,0.35);
}
.btn-hero-primary:hover { transform: translateY(-2px); box-shadow: 0 8px 28px rgba(99,102,241,0.45); color: #fff; }
.btn-hero-secondary {
    display: inline-flex; align-items: center; gap: 7px;
    background: rgba(255,255,255,0.07); color: rgba(255,255,255,0.8);
    border: 1px solid rgba(255,255,255,0.14); border-radius: 10px;
    padding: 10px 20px; font-size: 13px; font-weight: 600;
    cursor: pointer; transition: all .25s; text-decoration: none; backdrop-filter: blur(6px);
}
.btn-hero-secondary:hover { background: rgba(255,255,255,0.12); color: #fff; transform: translateY(-2px); }

/* Hero mini-stats row */
.hero-stats-row {
    display: flex; gap: 28px; margin-top: 24px; flex-wrap: wrap;
}
.hero-stat-pill {
    display: flex; flex-direction: column;
    padding: 12px 20px; border-radius: 12px;
    background: rgba(255,255,255,0.05);
    border: 1px solid rgba(255,255,255,0.08);
    min-width: 110px;
}
.hero-stat-pill .hval { font-size: 1.5rem; font-weight: 800; color: #fff; line-height: 1; }
.hero-stat-pill .hlbl { font-size: 11px; color: rgba(255,255,255,0.45); margin-top: 4px; font-weight: 500; letter-spacing: 0.4px; }
.hero-stat-pill .hdlt { font-size: 11px; font-weight: 600; margin-top: 2px; }
.hdlt.up   { color: #34d399; }
.hdlt.warn { color: #fbbf24; }
.hdlt.info { color: #60a5fa; }

/* Overdue Alert */
.overdue-alert {
    background: linear-gradient(135deg,#451a03,#7c2d12);
    border: 1px solid rgba(234,88,12,0.4);
    border-radius: 14px; padding: 14px 20px;
    display: flex; align-items: center; gap: 14px;
    margin-bottom: 24px; box-shadow: 0 4px 20px rgba(239,68,68,0.1);
}
.overdue-alert .oa-icon {
    width: 40px; height: 40px; border-radius: 10px;
    background: rgba(239,68,68,0.25); display: flex;
    align-items: center; justify-content: center; flex-shrink: 0;
}
.overdue-alert p { margin: 0; color: #fca5a5; font-size: 13px; }
.overdue-alert strong { color: #fff; }
.overdue-alert a { color: #fbbf24; font-weight: 700; text-decoration: none; }
.overdue-alert a:hover { text-decoration: underline; }

/* KPI Cards Grid */
.kpi-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 14px;
    margin-bottom: 28px;
}
@media (max-width: 1400px) { .kpi-grid { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 768px)  { .kpi-grid { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 480px)  { .kpi-grid { grid-template-columns: 1fr; } }

/* Mobile Dashboard Compression */
@media (max-width: 768px) {
    .dash-hero { padding: 20px 18px; }
    .hero-stats-row { gap: 10px; margin-top: 18px; }
    .hero-stat-pill { min-width: 45%; flex: 1; padding: 12px 14px; }
    .hero-stat-pill .hval { font-size: 0.85rem; letter-spacing: -0.5px; white-space: nowrap; overflow: hidden; line-height: 1.1; }
    .hero-actions { width: 100%; justify-content: stretch; }
    .hero-actions .btn-hero-primary, .hero-actions .btn-hero-secondary { flex: 1; justify-content: center; }
    .dash-card-header { padding: 14px 16px 0; flex-direction: column; align-items: flex-start; gap: 8px; }
    .dash-card-header .ms-auto, .dash-card-header a { align-self: flex-start; }
    .dash-card-body { padding: 12px 16px 16px; }
    .kpi-card { padding: 16px 14px 14px; }
    .kpi-card .kpi-value { font-size: 0.85rem; letter-spacing: -0.5px; white-space: nowrap; overflow: hidden; line-height: 1.1; }
    .kpi-card .kpi-icon { width: 34px; height: 34px; font-size: 15px; margin-bottom: 10px; }
    .kpi-card .kpi-label { font-size: 10px; }
    .kpi-card .kpi-sub { font-size: 10px; }
}

.kpi-card {
    background: #fff;
    border-radius: 16px;
    padding: 20px 18px 16px;
    border: 1px solid #f1f5f9;
    box-shadow: 0 1px 12px rgba(0,0,0,0.04);
    position: relative; overflow: hidden;
    transition: transform .2s, box-shadow .2s;
}
.kpi-card:hover { transform: translateY(-3px); box-shadow: 0 8px 28px rgba(0,0,0,0.09); }
.kpi-card .kpi-icon {
    width: 42px; height: 42px; border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    font-size: 18px; color: #fff; margin-bottom: 14px;
}
.kpi-card .kpi-label { font-size: 11px; font-weight: 600; color: #94a3b8; letter-spacing: 0.5px; text-transform: uppercase; margin-bottom: 4px; }
.kpi-card .kpi-value { font-size: 1.2rem; font-weight: 800; color: #0f172a; line-height: 1; margin-bottom: 8px; }
.kpi-card .kpi-sub { font-size: 12px; font-weight: 500; display: flex; align-items: center; gap: 4px; }
.kpi-card .kpi-glow {
    position: absolute; top: -20px; right: -20px;
    width: 80px; height: 80px; border-radius: 50%;
    opacity: 0.08; pointer-events: none;
}

/* Section header */
.section-title {
    font-size: 14px; font-weight: 700; color: #0f172a;
    display: flex; align-items: center; gap: 8px;
}
.section-title .st-icon {
    width: 28px; height: 28px; border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
    font-size: 13px;
}

/* Dashboard card */
.dash-card {
    background: #fff; border-radius: 18px;
    border: 1px solid #f1f5f9;
    box-shadow: 0 1px 12px rgba(0,0,0,0.04);
    overflow: hidden;
    height: 100%;
}
.dash-card-header {
    padding: 18px 22px 14px;
    display: flex; align-items: center; justify-content: space-between;
    border-bottom: 1px solid #f8fafc;
}
.dash-card-body { padding: 0; }

/* Activity / Follow-up feed */
.feed-item { display: flex; gap: 12px; padding: 14px 22px; border-bottom: 1px solid #f8fafc; transition: background 0.2s; }
.feed-item:last-child { border-bottom: none; }
.feed-item:hover { background: #f8fafc; }
.feed-dot-wrap { display: flex; flex-direction: column; align-items: center; }
.feed-dot { width: 32px; height: 32px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 13px; flex-shrink: 0; }
.feed-content { flex: 1; }
.feed-who  { font-size: 13px; font-weight: 600; color: #0f172a; }
.feed-desc { font-size: 12px; color: #64748b; margin-top: 1px; line-height: 1.4; display: flex; align-items: center; gap: 6px; }
.feed-time { font-size: 11px; color: #94a3b8; margin-top: 3px; }

/* Recent leads table */
.leads-table { width: 100%; border-collapse: separate; border-spacing: 0; margin-bottom: 0; }
.leads-table th {
    font-size: 10px; font-weight: 700; color: #94a3b8;
    text-transform: uppercase; letter-spacing: 0.5px;
    padding: 14px 22px; border-bottom: 1px solid #f1f5f9; background: #fafbfc;
}
.leads-table td { padding: 14px 22px; vertical-align: middle; font-size: 13px; border-bottom: 1px solid #f8fafc; }
.leads-table tr:last-child td { border-bottom: none; }
.leads-table tr:hover td { background: #f8fafc; }
.lead-name-cell { font-weight: 600; color: #0f172a; }
.lead-phone { font-size: 12px; color: #64748b; margin-top: 2px;}
.status-pill { display: inline-flex; align-items: center; gap: 5px; font-size: 11px; font-weight: 600; padding: 3px 10px; border-radius: 20px; }

.action-btn-mini {
    width: 28px; height: 28px; border-radius: 8px; display: inline-flex; align-items: center; justify-content: center;
    background: #f1f5f9; color: #64748b; transition: all 0.2s; text-decoration: none;
}
.action-btn-mini:hover { background: #e2e8f0; color: #0f172a; }

.priority-dot { width: 6px; height: 6px; border-radius: 50%; display: inline-block; }
</style>

<!-- ============================================================
     HERO BANNER
     ============================================================ -->
<div class="dash-hero mb-4">
    <div class="hero-grid-lines"></div>
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3" style="position:relative;z-index:1;">
        <div>
            <div class="hero-role-badge"><i class="bi bi-person-fill-gear"></i> Sales Agent</div>
            <h3>Welcome back, <?= e(explode(' ', getUserName())[0]) ?> 👋</h3>
            <p>Here's your sales pipeline and tasks assigned to you for today.</p>

            <!-- Mini stat pills inside hero -->
            <div class="hero-stats-row">
                <div class="hero-stat-pill">
                    <span class="hval"><?= $stats['total_leads'] ?></span>
                    <span class="hlbl">YOUR LEADS</span>
                    <span class="hdlt info"><i class="bi bi-person-plus-fill"></i> Pipeline</span>
                </div>
                <div class="hero-stat-pill">
                    <span class="hval"><?= $stats['deals_in_progress'] ?? 0 ?></span>
                    <span class="hlbl">IN PROGRESS</span>
                    <span class="hdlt warn"><i class="bi bi-hourglass-split"></i> Working</span>
                </div>
                <div class="hero-stat-pill">
                    <span class="hval"><?= $stats['won_deals'] ?></span>
                    <span class="hlbl">DEALS WON</span>
                    <span class="hdlt up"><i class="bi bi-trophy-fill"></i> Closed</span>
                </div>
                <?php if ($overdueCount > 0): ?>
                <div class="hero-stat-pill" style="border-color:rgba(239,68,68,0.3);background:rgba(239,68,68,0.08);">
                    <span class="hval" style="color:#f87171;"><?= $overdueCount ?></span>
                    <span class="hlbl">OVERDUE</span>
                    <span class="hdlt warn"><i class="bi bi-exclamation-circle text-danger"></i> Missed</span>
                </div>
                <?php endif; ?>

                <?php if (($stats['upcoming_followups'] ?? 0) > 0): ?>
                <div class="hero-stat-pill" style="border-color:rgba(99,102,241,0.3);background:rgba(99,102,241,0.08);">
                    <span class="hval" style="color:#818cf8;"><?= $stats['upcoming_followups'] ?></span>
                    <span class="hlbl">UPCOMING</span>
                    <span class="hdlt info"><i class="bi bi-calendar-check text-primary"></i> Future</span>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <div class="hero-actions align-self-start">
            <a href="<?= BASE_URL ?>modules/leads/add.php" class="btn-hero-primary">
                <i class="bi bi-plus-circle-fill"></i> Add Lead
            </a>
            <a href="<?= BASE_URL ?>modules/followups/" class="btn-hero-secondary">
                <i class="bi bi-calendar2-check-fill"></i> My Schedule
            </a>
        </div>
    </div>
</div>

<!-- ============================================================
     OVERDUE ALERT
     ============================================================ -->
<?php if ($overdueCount > 0): ?>
<div class="overdue-alert">
    <div class="oa-icon"><i class="bi bi-exclamation-triangle-fill text-danger fs-5"></i></div>
    <div>
        <p><strong><?= $overdueCount ?> overdue follow-up<?= $overdueCount > 1 ? 's' : '' ?></strong> need your urgent attention to close pending deals.</p>
    </div>
    <a href="<?= BASE_URL ?>modules/followups/?filter=overdue" class="ms-auto text-nowrap" style="color:#fbbf24;font-weight:700;text-decoration:none;font-size:13px;">
        View Now <i class="bi bi-arrow-right"></i>
    </a>
</div>
<?php endif; ?>

<!-- ============================================================
     KPI CARDS — 4 Across
     ============================================================ -->
<div class="kpi-grid mb-4">

    <!-- Total Leads -->
    <div class="kpi-card">
        <div class="kpi-glow" style="background:#6366f1;"></div>
        <div class="kpi-icon" style="background:linear-gradient(135deg,#6366f1,#4f46e5);">
            <i class="bi bi-people-fill"></i>
        </div>
        <div class="kpi-label">Total Leads</div>
        <div class="kpi-value"><?= number_format($stats['total_leads']) ?></div>
        <div class="kpi-sub" style="color:#6366f1;"><i class="bi bi-arrow-up-right me-1"></i> Assigned to you</div>
    </div>

    <!-- Contacted -->
    <div class="kpi-card">
        <div class="kpi-glow" style="background:#06b6d4;"></div>
        <div class="kpi-icon" style="background:linear-gradient(135deg,#06b6d4,#0891b2);">
            <i class="bi bi-telephone-outbound-fill"></i>
        </div>
        <div class="kpi-label">Contacted Leads</div>
        <div class="kpi-value"><?= number_format($stats['contacted_leads']) ?></div>
        <div class="kpi-sub" style="color:#06b6d4;"><i class="bi bi-chat-dots me-1"></i> Engagement</div>
    </div>

    <!-- Due Today -->
    <div class="kpi-card">
        <div class="kpi-glow" style="background:#f59e0b;"></div>
        <div class="kpi-icon" style="background:linear-gradient(135deg,#f59e0b,#d97706);">
            <i class="bi bi-calendar-event-fill"></i>
        </div>
        <div class="kpi-label">Tasks Today</div>
        <div class="kpi-value"><?= count($todayFollowups) ?></div>
        <div class="kpi-sub" style="color:#f59e0b;"><i class="bi bi-clock-history me-1"></i> Pending action</div>
    </div>

    <!-- Won Deals -->
    <div class="kpi-card">
        <div class="kpi-glow" style="background:#10b981;"></div>
        <div class="kpi-icon" style="background:linear-gradient(135deg,#10b981,#059669);">
            <i class="bi bi-trophy-fill"></i>
        </div>
        <div class="kpi-label">Deals Won</div>
        <div class="kpi-value"><?= number_format($stats['won_deals']) ?></div>
        <div class="kpi-sub" style="color:#10b981;"><i class="bi bi-graph-up-arrow me-1"></i> Successfully closed</div>
    </div>

</div>

<!-- ============================================================
     ACTION PANELS
     ============================================================ -->
<div class="row g-4 mb-4">
    
    <!-- Today's Schedule -->
    <div class="col-xl-5">
        <div class="dash-card">
            <div class="dash-card-header">
                <div class="section-title">
                    <div class="st-icon" style="background:#eff6ff;color:#3b82f6;"><i class="bi bi-calendar2-check-fill"></i></div>
                    Today's Schedule
                </div>
                <a href="<?= BASE_URL ?>modules/followups/" style="font-size:12px;font-weight:700;color:#6366f1;text-decoration:none;">View All <i class="bi bi-arrow-right"></i></a>
            </div>
            <div class="dash-card-body">
                <?php if (empty($todayFollowups)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-emoji-sunglasses-fill fs-1 d-block mb-2" style="color:#e2e8f0;"></i>
                        <span class="text-muted" style="font-size:13px;font-weight:500;">No tasks left today! You're all caught up.</span>
                    </div>
                <?php else: ?>
                    <?php foreach (array_slice($todayFollowups, 0, 6) as $f): 
                        $isUrgent = (strtotime($f['followup_time']) < time());
                        $iconColor = $isUrgent ? '#ef4444' : '#6366f1';
                    ?>
                        <div class="feed-item">
                            <div class="feed-dot-wrap pt-1">
                                <div class="feed-dot" style="background:<?= $iconColor ?>18;color:<?= $iconColor ?>;"><i class="bi <?= $isUrgent ? 'bi-exclamation-circle-fill' : 'bi-check2-circle' ?>"></i></div>
                            </div>
                            <div class="feed-content">
                                <div class="feed-who"><?= e($f['title']) ?></div>
                                <div class="feed-desc">
                                    <i class="bi bi-person"></i> <?= e($f['lead_name'] ?? 'Lead') ?>
                                </div>
                                <div class="feed-time">
                                    <span style="color:<?= $isUrgent ? '#ef4444' : '#64748b' ?>; font-weight: <?= $isUrgent ? '600' : 'normal' ?>;">
                                        <i class="bi bi-clock me-1"></i><?= date('h:i A', strtotime($f['followup_time'])) ?>
                                    </span>
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-2 ps-2">
                                <button type="button" class="action-btn-mini btn-complete-followup" data-id="<?= $f['id'] ?>" style="background:#dcfce7;color:#16a34a;border:none;cursor:pointer;" title="Mark Contacted / Completed">
                                    <i class="bi bi-check-lg" style="stroke-width:2px;"></i>
                                </button>
                                <?php if (isset($f['lead_phone']) && $f['lead_phone']): ?>
                                    <a href="tel:<?= e($f['lead_phone']) ?>" class="action-btn-mini" style="background:#e0e7ff;color:#4f46e5;" title="Call Lead"><i class="bi bi-telephone-fill"></i></a>
                                <?php endif; ?>
                                <a href="<?= BASE_URL ?>modules/leads/view.php?id=<?= $f['lead_id'] ?>" class="action-btn-mini" title="View Lead details"><i class="bi bi-arrow-right"></i></a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Recent Inbox -->
    <div class="col-xl-7">
        <div class="dash-card" id="dashboardLeadsCard">
            <div class="dash-card-header">
                <div class="section-title">
                    <div class="st-icon" style="background:#fdf4ff;color:#a855f7;"><i class="bi bi-inbox-fill"></i></div>
                    Recent Assigned Leads
                </div>
                <a href="<?= BASE_URL ?>modules/leads/" style="font-size:12px;font-weight:700;color:#6366f1;text-decoration:none;">View Pipeline <i class="bi bi-arrow-right"></i></a>
            </div>
            <div class="dash-card-body" style="overflow-x:auto;">
                <?php if (empty($recentLeads)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-inbox fs-1 d-block mb-2" style="color:#e2e8f0;"></i>
                        <span class="text-muted" style="font-size:13px;font-weight:500;">No recent leads assigned to you.</span>
                    </div>
                <?php else: ?>
                    <table class="leads-table">
                        <thead>
                            <tr>
                                <th>Lead Identity</th>
                                <th>Contact Status</th>
                                <th>Tracking</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($recentLeads as $l):
                            $statusColors = [
                                'New Lead'   => ['#6366f1','#eef2ff'],
                                'Working'    => ['#3b82f6','#eff6ff'],
                                'Interested' => ['#10b981','#f0fdf4'],
                                'Follow Up'  => ['#f59e0b','#fffbeb'],
                                'Converted'  => ['#059669','#ecfdf5'],
                                'Lost'       => ['#ef4444','#fef2f2'],
                            ];
                            $sc = $statusColors[$l['status']] ?? ['#64748b','#f8fafc'];
                        ?>
                        <tr>
                            <td>
                                <div class="lead-name-cell"><?= e($l['name']) ?></div>
                                <div class="lead-phone"><i class="bi bi-telephone me-1"></i><?= e($l['phone'] ?? '—') ?></div>
                            </td>
                            <td>
                                <span class="status-pill" style="color:<?= $sc[0] ?>;background:<?= $sc[1] ?>;">
                                    <span style="width:6px;height:6px;border-radius:50%;background:<?= $sc[0] ?>;display:inline-block;"></span>
                                    <?= e($l['status']) ?>
                                </span>
                            </td>
                            <td><span style="font-size:12px;color:#94a3b8;"><i class="bi bi-clock me-1"></i><?= timeAgo($l['created_at']) ?></span></td>
                            <td>
                                <a href="<?= BASE_URL ?>modules/leads/view.php?id=<?= $l['id'] ?>" class="action-btn-mini"><i class="bi bi-arrow-right"></i></a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>

</div>

<script>
function refreshLeadData() {
    const card = document.getElementById('dashboardLeadsCard');
    if (!card) return;
    
    card.style.opacity = '0.6';
    card.style.transition = 'opacity 0.3s ease';
    
    fetch(window.location.href)
        .then(res => res.text())
        .then(html => {
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const newContent = doc.getElementById('dashboardLeadsCard');
            if (newContent) {
                card.innerHTML = newContent.innerHTML;
            }
            card.style.opacity = '1';
        })
        .catch(err => {
            console.error("Dashboard refresh failed:", err);
            card.style.opacity = '1';
        });
}

document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.btn-complete-followup').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            if (!confirm('Confirm you have contacted this lead and completed the task?')) return;
            
            const originalHtml = this.innerHTML;
            this.innerHTML = '<span class="spinner-border spinner-border-sm border-2" role="status" aria-hidden="true" style="width:14px;height:14px;"></span>';
            this.disabled = true;

            fetch('<?= BASE_URL ?>ajax/complete_followup.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({id: id})
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.error || 'Failed to complete task.');
                    this.innerHTML = originalHtml;
                    this.disabled = false;
                }
            })
            .catch(err => {
                console.error(err);
                alert('Connection error.');
                this.innerHTML = originalHtml;
                this.disabled = false;
            });
        });
    });
});
</script>
