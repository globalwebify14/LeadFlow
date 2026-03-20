<?php
// org_owner.php view
$stats         = $dashboard->getStatistics($orgId, null, 'org_owner');
$recentLeads   = $dashboard->getRecentLeads($orgId, 8, null, 'org_owner');
$recentActivities = $dashboard->getRecentActivities($orgId, 10, null, 'org_owner');
$todayFollowups= $dashboard->getTodayFollowups($orgId, null);
$monthlyGrowth = $dashboard->getMonthlyLeadGrowth($orgId, null, 'org_owner');
$pipelineOverview = $dashboard->getPipelineOverview($orgId, null, 'org_owner');
$agentPerf     = $dashboard->getAgentPerformance($orgId);
$overdueCount  = $followupModel->getOverdueCount($orgId, null);
?>

<style>
/* ============================================================
   DASHBOARD REDESIGN — Premium Dark/Glass Aesthetic
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
    grid-template-columns: repeat(6, 1fr);
    gap: 14px;
    margin-bottom: 28px;
}
@media (max-width: 1400px) { .kpi-grid { grid-template-columns: repeat(3, 1fr); } }
@media (max-width: 768px)  { .kpi-grid { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 480px)  { .kpi-grid { grid-template-columns: 1fr; } }

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
.kpi-card .kpi-value { font-size: 1.7rem; font-weight: 800; color: #0f172a; line-height: 1; margin-bottom: 8px; }
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
}
.dash-card-header {
    padding: 18px 22px 0;
    display: flex; align-items: center; justify-content: space-between;
}
.dash-card-body { padding: 16px 22px 20px; }

/* Pipeline bars */
.pipe-row + .pipe-row { margin-top: 14px; }
.pipe-row { }
.pipe-row .pipe-meta {
    display: flex; justify-content: space-between;
    align-items: center; margin-bottom: 5px;
}
.pipe-row .pipe-name { font-size: 12px; font-weight: 600; color: #334155; display: flex; align-items: center; gap: 6px; }
.pipe-row .pipe-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
.pipe-row .pipe-count { font-size: 11px; font-weight: 700; padding: 2px 8px; border-radius: 20px; }
.pipe-bar-track { height: 6px; border-radius: 99px; background: #f1f5f9; overflow: hidden; }
.pipe-bar-fill  { height: 100%; border-radius: 99px; transition: width .6s ease; }

/* Agent performance */
.agent-row { display: flex; align-items: center; gap: 12px; padding: 10px 0; }
.agent-row + .agent-row { border-top: 1px solid #f8fafc; }
.ag-avatar {
    width: 38px; height: 38px; border-radius: 10px;
    font-size: 13px; font-weight: 700; color: #fff;
    display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}
.ag-name  { font-size: 13px; font-weight: 600; color: #0f172a; }
.ag-meta  { font-size: 11px; color: #94a3b8; margin-top: 1px; }
.ag-prog-wrap { flex: 1; }
.ag-prog-track { height: 5px; border-radius: 99px; background: #f1f5f9; overflow: hidden; margin-top: 4px; }
.ag-prog-fill  { height: 100%; border-radius: 99px; }
.ag-badge { font-size: 12px; font-weight: 700; padding: 4px 10px; border-radius: 8px; flex-shrink: 0; }

/* Activity feed */
.feed-item { display: flex; gap: 12px; padding: 10px 0; }
.feed-item + .feed-item { border-top: 1px solid #f8fafc; }
.feed-dot-wrap { display: flex; flex-direction: column; align-items: center; }
.feed-dot { width: 32px; height: 32px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 13px; flex-shrink: 0; }
.feed-line { flex: 1; width: 1px; background: #f1f5f9; margin-top: 4px; }
.feed-content { flex: 1; }
.feed-who  { font-size: 13px; font-weight: 600; color: #0f172a; }
.feed-desc { font-size: 12px; color: #64748b; margin-top: 1px; line-height: 1.4; }
.feed-time { font-size: 11px; color: #94a3b8; margin-top: 3px; }

/* Recent leads table */
.leads-table { width: 100%; border-collapse: separate; border-spacing: 0; }
.leads-table th {
    font-size: 10px; font-weight: 700; color: #94a3b8;
    text-transform: uppercase; letter-spacing: 0.5px;
    padding: 0 10px 10px; border-bottom: 1px solid #f1f5f9;
}
.leads-table td { padding: 10px; vertical-align: middle; font-size: 13px; border-bottom: 1px solid #f8fafc; }
.leads-table tr:last-child td { border-bottom: none; }
.leads-table tr:hover td { background: #f8fafc; }
.lead-name-cell { font-weight: 600; color: #0f172a; }
.lead-phone { font-size: 12px; color: #64748b; }
.status-pill { display: inline-flex; align-items: center; gap: 5px; font-size: 11px; font-weight: 600; padding: 3px 10px; border-radius: 20px; }
.priority-dot { width: 6px; height: 6px; border-radius: 50%; display: inline-block; }

/* Scrollbar */
.scroll-y { overflow-y: auto; }
.scroll-y::-webkit-scrollbar { width: 4px; }
.scroll-y::-webkit-scrollbar-track { background: transparent; }
.scroll-y::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 99px; }
</style>

<?php
/* ----------------------------------------------------------------
   Helper: get avatar bg gradient by index
-----------------------------------------------------------------*/
$avatarColors = [
    'linear-gradient(135deg,#6366f1,#4f46e5)',
    'linear-gradient(135deg,#10b981,#059669)',
    'linear-gradient(135deg,#f59e0b,#d97706)',
    'linear-gradient(135deg,#ef4444,#dc2626)',
    'linear-gradient(135deg,#8b5cf6,#7c3aed)',
    'linear-gradient(135deg,#06b6d4,#0891b2)',
    'linear-gradient(135deg,#ec4899,#be185d)',
];
function dashColor($i) {
    global $avatarColors;
    return $avatarColors[$i % count($avatarColors)];
}
?>

<!-- ============================================================
     HERO BANNER
     ============================================================ -->
<div class="dash-hero mb-4">
    <div class="hero-grid-lines"></div>
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3" style="position:relative;z-index:1;">
        <div>
            <div class="hero-role-badge"><i class="bi bi-building-fill-gear"></i> Org Owner · LIVE</div>
            <h3>Welcome back, <?= e(getUserName()) ?> 👋</h3>
            <p>Here's your organization's performance snapshot for today.</p>

            <!-- Mini stat pills inside hero -->
            <div class="hero-stats-row">
                <div class="hero-stat-pill">
                    <span class="hval"><?= $stats['total_leads'] ?></span>
                    <span class="hlbl">TOTAL LEADS</span>
                    <span class="hdlt up"><i class="bi bi-arrow-up-short"></i><?= $stats['new_leads'] ?> new</span>
                </div>
                <div class="hero-stat-pill">
                    <span class="hval"><?= formatCurrency($stats['deal_value']) ?></span>
                    <span class="hlbl">PIPELINE VALUE</span>
                    <span class="hdlt up"><i class="bi bi-trophy-fill"></i><?= $stats['won_deals'] ?> won</span>
                </div>
                <div class="hero-stat-pill">
                    <span class="hval"><?= $stats['conversion_rate'] ?>%</span>
                    <span class="hlbl">CONVERSION</span>
                    <span class="hdlt info"><i class="bi bi-graph-up-arrow"></i> Rate</span>
                </div>
                <?php if ($overdueCount > 0): ?>
                <div class="hero-stat-pill" style="border-color:rgba(239,68,68,0.3);background:rgba(239,68,68,0.08);">
                    <span class="hval" style="color:#f87171;"><?= $overdueCount ?></span>
                    <span class="hlbl">OVERDUE</span>
                    <span class="hdlt warn"><i class="bi bi-exclamation-circle"></i> Follow-ups</span>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <div class="hero-actions align-self-start">
            <a href="<?= BASE_URL ?>modules/leads/add.php" class="btn-hero-primary">
                <i class="bi bi-plus-circle-fill"></i> Add Lead
            </a>
            <a href="<?= BASE_URL ?>modules/reports/" class="btn-hero-secondary">
                <i class="bi bi-bar-chart-line-fill"></i> Reports
            </a>
            <a href="<?= BASE_URL ?>modules/users/" class="btn-hero-secondary">
                <i class="bi bi-people-fill"></i> Team
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
        <p><strong><?= $overdueCount ?> overdue follow-up<?= $overdueCount > 1 ? 's' : '' ?></strong> need urgent attention across your organization.</p>
    </div>
    <a href="<?= BASE_URL ?>modules/followups/?filter=overdue" class="ms-auto text-nowrap" style="color:#fbbf24;font-weight:700;text-decoration:none;font-size:13px;">
        View Now <i class="bi bi-arrow-right"></i>
    </a>
</div>
<?php endif; ?>

<!-- ============================================================
     KPI CARDS — 6 Across
     ============================================================ -->
<div class="kpi-grid mb-4">

    <!-- Total Leads -->
    <div class="kpi-card">
        <div class="kpi-glow" style="background:#6366f1;"></div>
        <div class="kpi-icon" style="background:linear-gradient(135deg,#6366f1,#4f46e5);">
            <i class="bi bi-people-fill"></i>
        </div>
        <div class="kpi-label">Total Leads</div>
        <div class="kpi-value"><?= $stats['total_leads'] ?></div>
        <div class="kpi-sub" style="color:#6366f1;"><i class="bi bi-person-plus-fill"></i> <?= $stats['new_leads'] ?> New Today</div>
    </div>

    <!-- Deal Value -->
    <div class="kpi-card">
        <div class="kpi-glow" style="background:#10b981;"></div>
        <div class="kpi-icon" style="background:linear-gradient(135deg,#10b981,#059669);">
            <i class="bi bi-currency-rupee"></i>
        </div>
        <div class="kpi-label">Deal Value</div>
        <div class="kpi-value"><?= formatCurrency($stats['deal_value']) ?></div>
        <div class="kpi-sub" style="color:#10b981;"><i class="bi bi-trophy-fill"></i> <?= $stats['won_deals'] ?> Won</div>
    </div>

    <!-- Conversion Rate -->
    <div class="kpi-card">
        <div class="kpi-glow" style="background:#f59e0b;"></div>
        <div class="kpi-icon" style="background:linear-gradient(135deg,#f59e0b,#d97706);">
            <i class="bi bi-bullseye"></i>
        </div>
        <div class="kpi-label">Conversion Rate</div>
        <div class="kpi-value"><?= $stats['conversion_rate'] ?>%</div>
        <div class="kpi-sub" style="color:#f59e0b;"><i class="bi bi-graph-up-arrow"></i> Win Ratio</div>
    </div>

    <!-- Due Follow-ups -->
    <div class="kpi-card">
        <div class="kpi-glow" style="background:#ef4444;"></div>
        <div class="kpi-icon" style="background:linear-gradient(135deg,#ef4444,#dc2626);">
            <i class="bi bi-clock-history"></i>
        </div>
        <div class="kpi-label">Due Follow-ups</div>
        <div class="kpi-value"><?= $stats['pending_followups'] ?></div>
        <a href="<?= BASE_URL ?>modules/followups/" class="kpi-sub text-decoration-none" style="color:#ef4444;">
            <i class="bi bi-arrow-right-circle-fill"></i> <?= $stats['missed_followups'] ?> Missed
        </a>
    </div>

    <!-- Assigned Today -->
    <div class="kpi-card">
        <div class="kpi-glow" style="background:#8b5cf6;"></div>
        <div class="kpi-icon" style="background:linear-gradient(135deg,#8b5cf6,#7c3aed);">
            <i class="bi bi-person-lines-fill"></i>
        </div>
        <div class="kpi-label">Assigned Today</div>
        <div class="kpi-value"><?= $stats['assigned_today'] ?></div>
        <div class="kpi-sub" style="color:#8b5cf6;"><i class="bi bi-arrow-down-circle-fill"></i> Distributed</div>
    </div>

    <!-- Contacted Leads -->
    <div class="kpi-card">
        <div class="kpi-glow" style="background:#06b6d4;"></div>
        <div class="kpi-icon" style="background:linear-gradient(135deg,#06b6d4,#0891b2);">
            <i class="bi bi-chat-dots-fill"></i>
        </div>
        <div class="kpi-label">Contacted Leads</div>
        <div class="kpi-value"><?= $stats['contacted_leads'] ?></div>
        <div class="kpi-sub" style="color:#06b6d4;"><i class="bi bi-check-circle-fill"></i> Engaged</div>
    </div>

</div>

<!-- ============================================================
     ROW 2 — Charts (Lead Growth + Leads by Status)
     ============================================================ -->
<div class="row g-4 mb-4">
    <!-- Lead Growth Chart -->
    <div class="col-xl-8">
        <div class="dash-card h-100">
            <div class="dash-card-header">
                <div class="section-title">
                    <div class="st-icon" style="background:#eef2ff;color:#6366f1;"><i class="bi bi-graph-up"></i></div>
                    Lead Growth
                </div>
                <span style="font-size:11px;font-weight:600;color:#94a3b8;background:#f8fafc;padding:4px 12px;border-radius:20px;border:1px solid #e2e8f0;">Last 6 Months</span>
            </div>
            <div class="dash-card-body" style="height:300px;">
                <canvas id="leadGrowthChart"></canvas>
            </div>
        </div>
    </div>
    <!-- Leads by Status Doughnut -->
    <div class="col-xl-4">
        <div class="dash-card h-100">
            <div class="dash-card-header">
                <div class="section-title">
                    <div class="st-icon" style="background:#f0fdf4;color:#10b981;"><i class="bi bi-pie-chart-fill"></i></div>
                    Leads by Status
                </div>
            </div>
            <div class="dash-card-body d-flex flex-column justify-content-center" style="height:300px;">
                <canvas id="leadsByStatusChart"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================
     ROW 3 — Pipeline + Agent Performance + Activity Feed
     ============================================================ -->
<div class="row g-4 mb-4">

    <!-- Team Follow-ups (Added as requested) -->
    <div class="col-xl-5 col-lg-6">
        <div class="dash-card h-100" style="min-height:350px;">
            <div class="dash-card-header">
                <div class="section-title">
                    <div class="st-icon" style="background:#fff1f2;color:#e11d48;"><i class="bi bi-calendar-check-fill"></i></div>
                    Team Schedule (Today)
                </div>
                <div class="d-flex gap-2">
                    <a href="<?= BASE_URL ?>modules/followups/" class="btn btn-sm btn-light border" style="font-size:11px;font-weight:700;">View All</a>
                    <a href="<?= BASE_URL ?>modules/followups/" class="btn btn-sm btn-primary" style="font-size:11px;font-weight:700;border:none;">+ New</a>
                </div>
            </div>
            <div class="dash-card-body scroll-y pt-0" style="max-height:400px;">
                <?php if (!empty($todayFollowups)):
                    foreach ($todayFollowups as $f):
                        $isUrgent = (strtotime($f['followup_time']) < time()) && $f['followup_date'] == date('Y-m-d');
                        $pColor = $f['priority'] === 'high' ? '#ef4444' : ($f['priority'] === 'medium' ? '#f59e0b' : '#3b82f6');
                ?>
                <div class="feed-item px-3 py-3" style="border-bottom: 1px solid #f8fafc;">
                    <div class="feed-dot-wrap">
                        <div class="feed-dot" style="background:<?= $pColor ?>15;color:<?= $pColor ?>;"><i class="bi bi-telephone"></i></div>
                    </div>
                    <div class="feed-content">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="feed-who"><?= e($f['title']) ?></div>
                            <span style="font-size:10px;font-weight:700;padding:2px 8px;border-radius:10px;background:<?= $pColor ?>15;color:<?= $pColor ?>;text-transform:uppercase;">
                                <?= $f['priority'] ?>
                            </span>
                        </div>
                        <div class="feed-desc mt-1">
                            <i class="bi bi-person me-1"></i><?= e($f['lead_name'] ?? 'General') ?>
                            <span class="mx-1">•</span>
                            <span style="color:#6366f1;font-weight:600;"><i class="bi bi-person-badge me-1"></i><?= e($f['agent_name'] ?? 'Admin') ?></span>
                        </div>
                        <div class="feed-time mt-2 d-flex justify-content-between">
                            <span><i class="bi bi-clock me-1"></i><?= date('h:i A', strtotime($f['followup_time'])) ?></span>
                            <a href="<?= BASE_URL ?>modules/leads/view.php?id=<?= $f['lead_id'] ?>" class="text-primary text-decoration-none small fw-bold">View Lead <i class="bi bi-arrow-right"></i></a>
                        </div>
                    </div>
                </div>
                <?php endforeach; else: ?>
                <div class="text-center py-5">
                    <i class="bi bi-calendar-x fs-1 d-block mb-2" style="color:#e2e8f0;"></i>
                    <span class="text-muted" style="font-size:13px;">No follow-ups scheduled for today.</span>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Pipeline Overview -->
    <div class="col-xl-3 col-lg-4">
        <div class="dash-card h-100" style="min-height:280px;">
            <div class="dash-card-header">
                <div class="section-title">
                    <div class="st-icon" style="background:#fff7ed;color:#f97316;"><i class="bi bi-funnel-fill"></i></div>
                    Pipeline
                </div>
                <a href="<?= BASE_URL ?>modules/pipeline/" class="text-decoration-none" style="font-size:12px;font-weight:700;color:#6366f1;">
                    Board <i class="bi bi-arrow-right"></i>
                </a>
            </div>
            <div class="dash-card-body pt-3">
                <?php if (count($pipelineOverview) > 0):
                    $totalPipeline = array_sum(array_column($pipelineOverview, 'count'));
                    foreach ($pipelineOverview as $stage):
                        $pct = $totalPipeline > 0 ? round(($stage['count'] / $totalPipeline) * 100) : 0;
                ?>
                <div class="pipe-row mb-3">
                    <div class="pipe-meta">
                        <span class="pipe-name">
                            <span class="pipe-dot" style="background:<?= e($stage['color']) ?>;"></span>
                            <?= e($stage['name']) ?>
                        </span>
                        <span class="pipe-count" style="background:<?= e($stage['color']) ?>18;color:<?= e($stage['color']) ?>;"><?= $stage['count'] ?></span>
                    </div>
                    <div class="pipe-bar-track">
                        <div class="pipe-bar-fill" style="width:<?= $pct ?>%;background:<?= e($stage['color']) ?>;"></div>
                    </div>
                </div>
                <?php endforeach; else: ?>
                <div class="text-center py-5">
                    <i class="bi bi-inbox fs-1 d-block mb-2" style="color:#e2e8f0;"></i>
                    <span class="text-muted" style="font-size:13px;">No pipeline stages yet</span><br>
                    <a href="<?= BASE_URL ?>modules/pipeline/" class="text-decoration-none mt-2 d-inline-block" style="font-size:12px;font-weight:600;color:#6366f1;">Set up Pipeline →</a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Agent Performance -->
    <div class="col-xl-4 col-lg-4">
        <div class="dash-card h-100" style="min-height:280px;">
            <div class="dash-card-header">
                <div class="section-title">
                    <div class="st-icon" style="background:#f0fdf4;color:#10b981;"><i class="bi bi-people-fill"></i></div>
                    Agent Performance
                </div>
                <a href="<?= BASE_URL ?>modules/users/" style="font-size:12px;font-weight:700;color:#6366f1;text-decoration:none;">All <i class="bi bi-arrow-right"></i></a>
            </div>
            <div class="dash-card-body pt-2">
                <?php if (count($agentPerf) > 0):
                    foreach (array_slice($agentPerf, 0, 5) as $i => $ap):
                        $cr = $ap['total_leads'] > 0 ? round(($ap['converted'] / $ap['total_leads']) * 100) : 0;
                        $crColor = $cr >= 50 ? '#10b981' : ($cr >= 20 ? '#f59e0b' : '#ef4444');
                ?>
                <div class="agent-row">
                    <div class="ag-avatar" style="background:<?= dashColor($i) ?>;">
                        <?= getInitials($ap['name']) ?>
                    </div>
                    <div class="ag-prog-wrap">
                        <div class="ag-name"><?= e($ap['name']) ?></div>
                        <div class="ag-meta"><?= $ap['total_leads'] ?> leads &bull; <?= $ap['converted'] ?> won</div>
                        <div class="ag-prog-track">
                            <div class="ag-prog-fill" style="width:<?= min(100, $cr) ?>%;background:<?= $crColor ?>;"></div>
                        </div>
                    </div>
                    <span class="ag-badge" style="background:<?= $crColor ?>15;color:<?= $crColor ?>;"><?= $cr ?>%</span>
                </div>
                <?php endforeach; else: ?>
                <div class="text-center py-5">
                    <i class="bi bi-person-x fs-1 d-block mb-2" style="color:#e2e8f0;"></i>
                    <span class="text-muted" style="font-size:13px;">No agents found</span>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Recent Activity Feed -->
    <div class="col-xl-5 col-lg-4">
        <div class="dash-card h-100" style="min-height:280px;">
            <div class="dash-card-header">
                <div class="section-title">
                    <div class="st-icon" style="background:#eff6ff;color:#3b82f6;"><i class="bi bi-activity"></i></div>
                    Org Activity
                </div>
                <a href="<?= BASE_URL ?>modules/activities/" style="font-size:12px;font-weight:700;color:#6366f1;text-decoration:none;">All <i class="bi bi-arrow-right"></i></a>
            </div>
            <div class="dash-card-body scroll-y pt-2" style="max-height:320px;">
                <?php if (!empty($recentActivities)):
                    $actColors = ['#6366f1','#10b981','#f59e0b','#06b6d4','#8b5cf6','#ec4899','#ef4444'];
                    foreach (array_slice($recentActivities, 0, 8) as $ai => $a):
                        $ac = $actColors[$ai % count($actColors)];
                ?>
                <div class="feed-item">
                    <div class="feed-dot-wrap">
                        <div class="feed-dot" style="background:<?= $ac ?>18;color:<?= $ac ?>;"><i class="bi bi-journal-text"></i></div>
                    </div>
                    <div class="feed-content">
                        <div class="feed-who"><?= e($a['lead_name']) ?> <span style="font-weight:400;color:#64748b;">by <?= e($a['user_name']) ?></span></div>
                        <div class="feed-desc"><?= e(truncate($a['description'] ?? '', 60)) ?></div>
                        <div class="feed-time"><i class="bi bi-clock me-1"></i><?= timeAgo($a['created_at']) ?></div>
                    </div>
                </div>
                <?php endforeach; else: ?>
                <div class="text-center py-5">
                    <i class="bi bi-bell-slash fs-1 d-block mb-2" style="color:#e2e8f0;"></i>
                    <span class="text-muted" style="font-size:13px;">No recent activity</span>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================
     ROW 4 — Recent Leads Table
     ============================================================ -->
<?php if (!empty($recentLeads)): ?>
<div class="row mb-4">
    <div class="col-12">
        <div class="dash-card" id="dashboardLeadsCard">
            <div class="dash-card-header">
                <div class="section-title">
                    <div class="st-icon" style="background:#fdf4ff;color:#a855f7;"><i class="bi bi-person-vcard-fill"></i></div>
                    Recent Leads
                </div>
                <a href="<?= BASE_URL ?>modules/leads/" style="font-size:12px;font-weight:700;color:#6366f1;text-decoration:none;">View All <i class="bi bi-arrow-right"></i></a>
            </div>
            <div class="dash-card-body pt-2" style="overflow-x:auto;">
                <table class="leads-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Phone</th>
                            <th>Status</th>
                            <th>Pipeline</th>
                            <th>Priority</th>
                            <th>Source</th>
                            <th>Assigned</th>
                            <th>Added</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($recentLeads as $li => $lead):
                        $statusColors = [
                            'New Lead'   => ['#6366f1','#eef2ff'],
                            'Working'    => ['#3b82f6','#eff6ff'],
                            'Interested' => ['#10b981','#f0fdf4'],
                            'Follow Up'  => ['#f59e0b','#fffbeb'],
                            'Converted'  => ['#059669','#ecfdf5'],
                            'Lost'       => ['#ef4444','#fef2f2'],
                        ];
                        $sc = $statusColors[$lead['status']] ?? ['#64748b','#f8fafc'];
                        $pColors = ['Hot'=>'#ef4444','Warm'=>'#f59e0b','Cold'=>'#3b82f6'];
                        $pc = $pColors[$lead['priority'] ?? 'Warm'] ?? '#64748b';
                    ?>
                    <tr>
                        <td>
                            <div class="lead-name-cell"><?= e($lead['name']) ?></div>
                            <div class="lead-phone"><?= e($lead['email'] ?? '—') ?></div>
                        </td>
                        <td><span style="font-size:12px;color:#475569;"><?= e($lead['phone']) ?></span></td>
                        <td>
                            <span class="status-pill" style="color:<?= $sc[0] ?>;background:<?= $sc[1] ?>;">
                                <span style="width:6px;height:6px;border-radius:50%;background:<?= $sc[0] ?>;display:inline-block;"></span>
                                <?= e($lead['status']) ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($lead['stage_name']): ?>
                                <span class="badge rounded-pill text-white" style="background: <?= e($lead['stage_color'] ?: '#64748b') ?>; font-size: 10px; padding: 4px 10px; font-weight: 600;">
                                    <?= e($lead['stage_name']) ?>
                                </span>
                            <?php else: ?>
                                <span class="text-muted" style="font-size:12px;">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span style="display:inline-flex;align-items:center;gap:4px;font-size:11px;font-weight:600;color:<?= $pc ?>;">
                                <span class="priority-dot" style="background:<?= $pc ?>;"></span>
                                <?= e($lead['priority'] ?? 'Warm') ?>
                            </span>
                        </td>
                        <td><span style="font-size:12px;color:#64748b;"><?= e($lead['source'] ?? '—') ?></span></td>
                        <td>
                            <?php if (!empty($lead['agent_name'])): ?>
                            <span style="display:inline-flex;align-items:center;gap:6px;font-size:12px;font-weight:500;color:#475569;">
                                <span style="width:22px;height:22px;border-radius:6px;background:<?= dashColor($li) ?>;color:#fff;font-size:9px;font-weight:700;display:inline-flex;align-items:center;justify-content:center;">
                                    <?= getInitials($lead['agent_name']) ?>
                                </span>
                                <?= e($lead['agent_name']) ?>
                            </span>
                            <?php else: ?>
                            <span style="font-size:12px;color:#94a3b8;">Unassigned</span>
                            <?php endif; ?>
                        </td>
                        <td><span style="font-size:11px;color:#94a3b8;"><?= timeAgo($lead['created_at']) ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- ============================================================
     CHARTS JS
     ============================================================ -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
// ── Lead Growth Chart ────────────────────────────────────────
const monthlyData = <?= json_encode($monthlyGrowth) ?>;
const growthCtx   = document.getElementById('leadGrowthChart');
if (growthCtx && monthlyData.length > 0) {
    new Chart(growthCtx, {
        type: 'line',
        data: {
            labels: monthlyData.map(d => d.label),
            datasets: [{
                label: 'Leads',
                data: monthlyData.map(d => d.count),
                borderColor: '#6366f1',
                backgroundColor: (ctx) => {
                    const { chart } = ctx;
                    const { ctx: c, chartArea } = chart;
                    if (!chartArea) return 'rgba(99,102,241,0.1)';
                    const g = c.createLinearGradient(0, chartArea.top, 0, chartArea.bottom);
                    g.addColorStop(0, 'rgba(99,102,241,0.22)');
                    g.addColorStop(1, 'rgba(99,102,241,0.00)');
                    return g;
                },
                fill: true, tension: 0.45,
                pointBackgroundColor: '#6366f1',
                pointBorderColor: '#fff', pointBorderWidth: 3,
                pointRadius: 5, pointHoverRadius: 8, borderWidth: 2.5,
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#1e293b', titleColor: '#e2e8f0',
                    bodyColor: '#94a3b8', padding: 12, cornerRadius: 10,
                    displayColors: false,
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(0,0,0,0.04)', drawBorder: false },
                    ticks: { font: { size: 11 }, color: '#94a3b8', padding: 8 }
                },
                x: {
                    grid: { display: false },
                    ticks: { font: { size: 11 }, color: '#94a3b8', padding: 4 }
                }
            },
            interaction: { intersect: false, mode: 'index' }
        }
    });
}

// ── Leads by Status Chart ─────────────────────────────────────
const statusData = <?= json_encode($stats['leads_by_status'] ?? []) ?>;
const statusCtx  = document.getElementById('leadsByStatusChart');
if (statusCtx && statusData.length > 0) {
    const palette = ['#6366f1','#10b981','#f59e0b','#ef4444','#8b5cf6','#06b6d4','#ec4899'];
    new Chart(statusCtx, {
        type: 'doughnut',
        data: {
            labels: statusData.map(d => d.status),
            datasets: [{
                data: statusData.map(d => d.count),
                backgroundColor: palette.slice(0, statusData.length),
                borderWidth: 3, borderColor: '#fff', hoverOffset: 6,
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false, cutout: '72%',
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { boxWidth: 10, usePointStyle: true, padding: 14, font: { size: 11 }, color: '#475569' }
                },
                tooltip: {
                    backgroundColor: '#1e293b', titleColor: '#e2e8f0',
                    bodyColor: '#94a3b8', padding: 12, cornerRadius: 10,
                }
            }
        }
    });
}

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
</script>
