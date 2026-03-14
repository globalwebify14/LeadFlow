<?php 
$currentPage = basename($_SERVER['PHP_SELF']); 
$requestUri = $_SERVER['REQUEST_URI'];
$userRole = getUserRole(); // super_admin, org_owner, org_admin, team_lead, agent
$base = BASE_URL;
?>
<div class="d-flex" id="wrapper">
    <!-- Sidebar -->
    <div id="sidebar-wrapper" class="text-white border-end shadow-sm d-flex flex-column" style="background-color: var(--sidebar-bg); border-color: rgba(255,255,255,0.05) !important; height: 100vh; position: fixed; width: var(--sidebar-width); z-index: 1060; transition: all 0.3s;">
        <div class="sidebar-heading px-4 py-4 fs-5 fw-bold d-flex align-items-center flex-shrink-0">
            <?php if (!empty($orgLogoHeader) && in_array(getUserRole(), ['org_owner', 'org_admin', 'team_lead', 'agent'])): ?>
                <img src="<?= e($orgLogoHeader) ?>" class="me-2 rounded border border-secondary" style="height:32px;width:auto;max-width:140px;object-fit:contain;background:white;" alt="Org Logo">
            <?php else: ?>
                <i class="bi bi-rocket-takeoff text-primary me-2 fs-4"></i>
                <span style="letter-spacing:0.5px;">LEAD CRM [S]</span>
            <?php endif; ?>
        </div>
        
        <div class="sidebar-scrollable" style="flex: 1; overflow-y: auto; overflow-x: hidden; padding-bottom: 20px;">
            <div class="sidebar-category mt-1">Main</div>
            <div class="list-group list-group-flush">
                <a href="<?= $base ?>modules/dashboard/" class="list-group-item list-group-item-action bg-transparent <?= strpos($requestUri, '/modules/dashboard/') !== false ? 'active-link' : '' ?>">
                    <i class="bi bi-grid-1x2 me-2"></i> Dashboard
                </a>
            </div>
            
            <?php if ($userRole === 'super_admin'): ?>
                <div class="sidebar-category mt-3">Platform Admin</div>
                <div class="list-group list-group-flush">
                    <a href="<?= $base ?>modules/organizations/" class="list-group-item list-group-item-action bg-transparent <?= strpos($requestUri, '/modules/organizations/') !== false ? 'active-link' : '' ?>">
                        <i class="bi bi-building me-2"></i> Organizations
                    </a>
                    <a href="<?= $base ?>modules/users/" class="list-group-item list-group-item-action bg-transparent <?= strpos($requestUri, '/modules/users/') !== false ? 'active-link' : '' ?>">
                        <i class="bi bi-people me-2"></i> Team
                    </a>
                    <a href="<?= $base ?>modules/subscriptions/" class="list-group-item list-group-item-action bg-transparent <?= strpos($requestUri, '/modules/subscriptions/') !== false ? 'active-link' : '' ?>">
                        <i class="bi bi-wallet2 me-2"></i> Plans
                    </a>
                    <a href="<?= $base ?>modules/billing/" class="list-group-item list-group-item-action bg-transparent <?= strpos($requestUri, '/modules/billing/') !== false ? 'active-link' : '' ?>">
                        <i class="bi bi-receipt me-2"></i> Billing
                    </a>
                    <a href="<?= $base ?>modules/superadmin/leads.php" class="list-group-item list-group-item-action bg-transparent <?= strpos($requestUri, '/modules/superadmin/leads') !== false ? 'active-link' : '' ?>">
                        <i class="bi bi-person-lines-fill me-2"></i> Lead Monitoring
                    </a>
                    <a href="<?= $base ?>modules/superadmin/activity_logs.php" class="list-group-item list-group-item-action bg-transparent <?= strpos($requestUri, '/modules/superadmin/activity_logs') !== false ? 'active-link' : '' ?>">
                        <i class="bi bi-journal-text me-2"></i> Activity Logs
                    </a>
                    <a href="<?= $base ?>modules/superadmin/settings_integrations.php" class="list-group-item list-group-item-action bg-transparent <?= strpos($requestUri, '/modules/superadmin/settings_integrations.php') !== false ? 'active-link' : '' ?>">
                        <i class="bi bi-facebook me-2"></i> System Integrations
                    </a>
                    <a href="<?= $base ?>modules/reports/" class="list-group-item list-group-item-action bg-transparent <?= strpos($requestUri, '/modules/reports/') !== false ? 'active-link' : '' ?>">
                        <i class="bi bi-bar-chart-fill me-2"></i> Reports
                    </a>
                    <a href="<?= $base ?>modules/settings/" class="list-group-item list-group-item-action bg-transparent <?= strpos($requestUri, '/modules/settings/') !== false ? 'active-link' : '' ?>">
                        <i class="bi bi-gear me-2"></i> Settings
                    </a>
                </div>
                
            <?php elseif ($userRole === 'org_owner' || $userRole === 'org_admin'): ?>
                <div class="sidebar-category mt-3">Sales</div>
                <div class="list-group list-group-flush">
                    <a href="<?= $base ?>modules/leads/" class="list-group-item list-group-item-action bg-transparent <?= strpos($requestUri, '/modules/leads/') !== false && strpos($requestUri, 'add.php') === false ? 'active-link' : '' ?>">
                        <i class="bi bi-people me-2"></i> Leads
                    </a>
                    <a href="<?= $base ?>modules/pipeline/" class="list-group-item list-group-item-action bg-transparent <?= strpos($requestUri, '/modules/pipeline/') !== false ? 'active-link' : '' ?>">
                        <i class="bi bi-kanban me-2"></i> Pipeline
                    </a>
                    <a href="<?= $base ?>modules/deals/" class="list-group-item list-group-item-action bg-transparent <?= strpos($requestUri, '/modules/deals/') !== false ? 'active-link' : '' ?>">
                        <i class="bi bi-trophy me-2"></i> Deals
                    </a>
                    <a href="<?= $base ?>modules/tasks/" class="list-group-item list-group-item-action bg-transparent <?= strpos($requestUri, '/modules/tasks/') !== false ? 'active-link' : '' ?>">
                        <i class="bi bi-check2-square me-2"></i> Tasks
                    </a>
                    <a href="<?= $base ?>modules/followups/" class="list-group-item list-group-item-action bg-transparent <?= strpos($requestUri, '/modules/followups/') !== false ? 'active-link' : '' ?>">
                        <i class="bi bi-clock-history me-2"></i> Follow-ups
                    </a>
                </div>

                <div class="sidebar-category mt-3">Management</div>
                <div class="list-group list-group-flush">
                    <a href="<?= $base ?>modules/users/" class="list-group-item list-group-item-action bg-transparent <?= strpos($requestUri, '/modules/users/') !== false ? 'active-link' : '' ?>">
                        <i class="bi bi-people-fill me-2"></i> Team
                    </a>
                    <a href="<?= $base ?>modules/reports/" class="list-group-item list-group-item-action bg-transparent <?= strpos($requestUri, '/modules/reports/') !== false ? 'active-link' : '' ?>">
                        <i class="bi bi-bar-chart-fill me-2"></i> Reports
                    </a>
                </div>

                <div class="sidebar-category mt-3">Integrations</div>
                <div class="list-group list-group-flush">
                    <a href="<?= $base ?>modules/facebook_integration/facebook_integration_settings.php" class="list-group-item list-group-item-action bg-transparent <?= strpos($requestUri, '/modules/facebook_integration/') !== false ? 'active-link' : '' ?>">
                        <i class="bi bi-facebook me-2"></i> Facebook Leads
                    </a>
                    <a href="<?= $base ?>modules/automation/" class="list-group-item list-group-item-action bg-transparent <?= strpos($requestUri, '/modules/automation/') !== false ? 'active-link' : '' ?>">
                        <i class="bi bi-robot me-2"></i> Automation
                    </a>
                </div>

                <div class="sidebar-category mt-3">Settings</div>
                <div class="list-group list-group-flush">
                    <a href="<?= $base ?>modules/settings/organization.php" class="list-group-item list-group-item-action bg-transparent <?= strpos($requestUri, '/settings/organization') !== false ? 'active-link' : '' ?>">
                        <i class="bi bi-building-gear me-2"></i> Org Settings
                    </a>
                    <a href="<?= $base ?>modules/settings/" class="list-group-item list-group-item-action bg-transparent <?= (strpos($requestUri, '/modules/settings/') !== false && strpos($requestUri, 'organization.php') === false) ? 'active-link' : '' ?>">
                        <i class="bi bi-person-gear me-2"></i> Profile & API
                    </a>
                </div>

            <?php else: // agent ?>
                <div class="sidebar-category mt-3">My Sales</div>
                <div class="list-group list-group-flush">
                    <a href="<?= $base ?>modules/leads/" class="list-group-item list-group-item-action bg-transparent <?= strpos($requestUri, '/modules/leads/') !== false ? 'active-link' : '' ?>">
                        <i class="bi bi-person-lines-fill me-2"></i> My Leads
                    </a>
                    <a href="<?= $base ?>modules/pipeline/" class="list-group-item list-group-item-action bg-transparent <?= strpos($requestUri, '/modules/pipeline/') !== false ? 'active-link' : '' ?>">
                        <i class="bi bi-kanban me-2"></i> Pipeline
                    </a>
                    <a href="<?= $base ?>modules/deals/" class="list-group-item list-group-item-action bg-transparent <?= strpos($requestUri, '/modules/deals/') !== false ? 'active-link' : '' ?>">
                        <i class="bi bi-trophy me-2"></i> Deals
                    </a>
                    <a href="<?= $base ?>modules/tasks/" class="list-group-item list-group-item-action bg-transparent <?= strpos($requestUri, '/modules/tasks/') !== false ? 'active-link' : '' ?>">
                        <i class="bi bi-check2-square me-2"></i> Tasks
                    </a>
                    <a href="<?= $base ?>modules/followups/" class="list-group-item list-group-item-action bg-transparent <?= strpos($requestUri, '/modules/followups/') !== false ? 'active-link' : '' ?>">
                        <i class="bi bi-clock-history me-2"></i> Follow-ups
                    </a>
                    <a href="<?= $base ?>modules/activities/" class="list-group-item list-group-item-action bg-transparent <?= strpos($requestUri, '/modules/activities/') !== false ? 'active-link' : '' ?>">
                        <i class="bi bi-activity me-2"></i> Activities
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <div class="px-3 pb-3 pt-2 flex-shrink-0" style="background-color: var(--sidebar-bg); border-top: 1px solid rgba(255,255,255,0.05);">
            <div class="d-flex align-items-center p-2 rounded-3" style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.05);">
                <div class="rounded-circle d-flex align-items-center justify-content-center me-2 text-white fw-bold shadow-sm" style="width: 36px; height: 36px; background: linear-gradient(135deg,#6366f1,#4f46e5); font-size: 13px;">
                    <?= getInitials(getUserName()) ?>
                </div>
                <div style="flex:1; min-width:0;">
                    <div class="text-white fw-semibold small text-truncate" style="line-height: 1.2;"><?= e(getUserName()) ?></div>
                    <div class="text-white-50 text-truncate" style="font-size: 11px;"><?= getUserRoleName() ?></div>
                </div>
                <a href="<?= BASE_URL ?>logout.php" class="btn btn-sm btn-link text-white-50 p-0 text-decoration-none" title="Logout"><i class="bi bi-box-arrow-right fs-5"></i></a>
            </div>
        </div>
    </div>
</div>
