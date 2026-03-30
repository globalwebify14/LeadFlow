<?php
require_once __DIR__ . '/config/auth.php';
if (isLoggedIn()) {
    // Logged in users can also view docs
}
$pageTitle = 'User Guide';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Guide — LeadFlow CRM</title>
    <meta name="description" content="Complete step-by-step user guide for LeadFlow CRM. Learn how to manage leads, deals, follow-ups, pipeline, team members, and reports.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>assets/css/docs.css?v=<?= time() ?>" rel="stylesheet">
</head>
<body>

<!-- Mobile Toggle -->
<button class="docs-mobile-toggle" id="mobileToggle" onclick="toggleSidebar()">
    <i class="bi bi-list"></i>
</button>

<!-- Overlay -->
<div class="docs-overlay" id="docsOverlay" onclick="toggleSidebar()"></div>

<div class="docs-wrapper">
    <!-- ============ SIDEBAR ============ -->
    <aside class="docs-sidebar" id="docsSidebar">
        <a href="<?= BASE_URL ?>index.php" class="docs-sidebar-brand">
            <i class="bi bi-rocket-takeoff-fill"></i> Lead<span>Flow</span>
        </a>

        <div class="docs-sidebar-category">Getting Started</div>
        <a href="#login" class="docs-sidebar-link active" data-section="login"><i class="bi bi-box-arrow-in-right"></i> Login & Access</a>
        <a href="#dashboard" class="docs-sidebar-link" data-section="dashboard"><i class="bi bi-grid-1x2"></i> Dashboard Overview</a>
        <a href="#roles" class="docs-sidebar-link" data-section="roles"><i class="bi bi-shield-lock"></i> User Roles</a>

        <div class="docs-sidebar-category">Organization</div>
        <a href="#org-settings" class="docs-sidebar-link" data-section="org-settings"><i class="bi bi-building-gear"></i> Org Settings</a>
        <a href="#team" class="docs-sidebar-link" data-section="team"><i class="bi bi-people-fill"></i> Team Management</a>
        <a href="#assignment" class="docs-sidebar-link" data-section="assignment"><i class="bi bi-shuffle"></i> Lead Distribution</a>

        <div class="docs-sidebar-category">Sales CRM</div>
        <a href="#leads" class="docs-sidebar-link" data-section="leads"><i class="bi bi-person-lines-fill"></i> Leads</a>
        <a href="#add-lead" class="docs-sidebar-link" data-section="add-lead"><i class="bi bi-plus-circle"></i> Add / Import Leads</a>
        <a href="#pipeline" class="docs-sidebar-link" data-section="pipeline"><i class="bi bi-kanban"></i> Sales Pipeline</a>
        <a href="#deals" class="docs-sidebar-link" data-section="deals"><i class="bi bi-trophy"></i> Deals</a>

        <div class="docs-sidebar-category">Engagement</div>
        <a href="#followups" class="docs-sidebar-link" data-section="followups"><i class="bi bi-clock-history"></i> Follow-ups</a>
        <a href="#tasks" class="docs-sidebar-link" data-section="tasks"><i class="bi bi-check2-square"></i> Tasks</a>
        <a href="#notes" class="docs-sidebar-link" data-section="notes"><i class="bi bi-journal-text"></i> Notes & Activity</a>

        <div class="docs-sidebar-category">Analytics</div>
        <a href="#reports" class="docs-sidebar-link" data-section="reports"><i class="bi bi-bar-chart-fill"></i> Reports</a>

        <div style="padding: 20px 24px; margin-top: 20px;">
            <a href="<?= BASE_URL ?>login.php" class="btn btn-primary btn-sm w-100 rounded-pill fw-semibold">
                <i class="bi bi-box-arrow-in-right me-1"></i> Go to CRM
            </a>
        </div>
    </aside>

    <!-- ============ MAIN ============ -->
    <main class="docs-main">
        <!-- Topbar -->
        <div class="docs-topbar">
            <div class="docs-topbar-title">
                <i class="bi bi-book me-2 text-primary"></i> LeadFlow User Guide
            </div>
            <div class="position-relative">
                <i class="bi bi-search position-absolute" style="left:12px;top:50%;transform:translateY(-50%);color:#94a3b8;"></i>
                <input type="text" class="docs-topbar-search" id="docsSearch" placeholder="Search documentation..." oninput="searchDocs(this.value)">
            </div>
        </div>

        <!-- Content -->
        <div class="docs-content">

            <!-- Hero -->
            <div class="docs-hero">
                <h1><i class="bi bi-book me-2"></i> LeadFlow CRM User Guide</h1>
                <p>Your complete step-by-step guide to mastering LeadFlow CRM. Learn how to manage leads, close deals, track follow-ups, and grow your sales — all from one powerful platform.</p>
                <div class="d-flex gap-2 mt-3">
                    <span class="badge px-3 py-2 rounded-pill" style="background:rgba(255,255,255,0.2);color:#fff;font-size:13px;font-weight:500;backdrop-filter:blur(4px);"><i class="bi bi-clock me-1"></i> 15 min read</span>
                    <span class="badge px-3 py-2 rounded-pill" style="background:rgba(255,255,255,0.2);color:#fff;font-size:13px;font-weight:500;backdrop-filter:blur(4px);"><i class="bi bi-journal me-1"></i> 13 Sections</span>
                </div>
            </div>

            <!-- Table of Contents -->
            <div class="docs-toc">
                <div class="docs-toc-title"><i class="bi bi-list-ul text-primary"></i> Quick Navigation</div>
                <ul class="docs-toc-list">
                    <li><a href="#login"><i class="bi bi-chevron-right"></i> Login & Access</a></li>
                    <li><a href="#dashboard"><i class="bi bi-chevron-right"></i> Dashboard Overview</a></li>
                    <li><a href="#roles"><i class="bi bi-chevron-right"></i> User Roles & Permissions</a></li>
                    <li><a href="#org-settings"><i class="bi bi-chevron-right"></i> Organization Settings</a></li>
                    <li><a href="#team"><i class="bi bi-chevron-right"></i> Team Management</a></li>
                    <li><a href="#assignment"><i class="bi bi-chevron-right"></i> Lead Distribution</a></li>
                    <li><a href="#leads"><i class="bi bi-chevron-right"></i> Managing Leads</a></li>
                    <li><a href="#add-lead"><i class="bi bi-chevron-right"></i> Add / Import Leads</a></li>
                    <li><a href="#pipeline"><i class="bi bi-chevron-right"></i> Sales Pipeline (Kanban)</a></li>
                    <li><a href="#deals"><i class="bi bi-chevron-right"></i> Deals & Revenue</a></li>
                    <li><a href="#followups"><i class="bi bi-chevron-right"></i> Follow-ups</a></li>
                    <li><a href="#tasks"><i class="bi bi-chevron-right"></i> Tasks</a></li>
                    <li><a href="#reports"><i class="bi bi-chevron-right"></i> Reports & Analytics</a></li>
                </ul>
            </div>

            <!-- ========== SECTION: LOGIN ========== -->
            <div class="docs-section" id="login">
                <div class="docs-section-title">
                    <div class="section-icon" style="background:linear-gradient(135deg,#6366f1,#4f46e5);"><i class="bi bi-box-arrow-in-right"></i></div>
                    Login & Access
                </div>
                <p class="docs-section-subtitle">How to sign in and access your CRM dashboard.</p>

                <div class="docs-step-card">
                    <div class="d-flex gap-3 align-items-start">
                        <div class="docs-step-number">1</div>
                        <div>
                            <div class="docs-step-title">Open the Login Page</div>
                            <p class="docs-step-desc">Navigate to your LeadFlow CRM URL and click <strong>"Sign In"</strong> from the top navigation bar, or go directly to <span class="docs-kbd">/login.php</span>.</p>
                        </div>
                    </div>
                </div>
                <div class="docs-step-card">
                    <div class="d-flex gap-3 align-items-start">
                        <div class="docs-step-number">2</div>
                        <div>
                            <div class="docs-step-title">Enter Your Credentials</div>
                            <p class="docs-step-desc">Type your <strong>Email Address</strong> and <strong>Password</strong>. Use the eye icon <i class="bi bi-eye text-primary"></i> to reveal your password if needed. Click <strong>"Sign In"</strong>.</p>
                        </div>
                    </div>
                </div>
                <div class="docs-step-card">
                    <div class="d-flex gap-3 align-items-start">
                        <div class="docs-step-number">3</div>
                        <div>
                            <div class="docs-step-title">You're In!</div>
                            <p class="docs-step-desc">After successful login, you will be redirected to your <strong>Dashboard</strong>. The dashboard view varies based on your user role (Owner, Admin, Team Lead, or Agent).</p>
                        </div>
                    </div>
                </div>

                <div class="docs-info-box tip">
                    <i class="bi bi-lightbulb-fill"></i>
                    <div><strong>Tip:</strong> If you've forgotten your password, contact your organization admin to reset it for you.</div>
                </div>
            </div>

            <!-- ========== SECTION: DASHBOARD ========== -->
            <div class="docs-section" id="dashboard">
                <div class="docs-section-title">
                    <div class="section-icon" style="background:linear-gradient(135deg,#10b981,#059669);"><i class="bi bi-grid-1x2"></i></div>
                    Dashboard Overview
                </div>
                <p class="docs-section-subtitle">Your command center — at-a-glance metrics and quick actions.</p>

                <div class="docs-step-card">
                    <div class="d-flex gap-3 align-items-start">
                        <div class="docs-step-number"><i class="bi bi-star-fill" style="font-size:12px;"></i></div>
                        <div>
                            <div class="docs-step-title">Owner / Admin Dashboard</div>
                            <p class="docs-step-desc">See <strong>total leads, conversion rates, revenue stats, agent performance rankings, recent activities</strong>, and quick-action buttons. You get a bird's-eye view of your entire organization's sales pipeline.</p>
                        </div>
                    </div>
                </div>
                <div class="docs-step-card">
                    <div class="d-flex gap-3 align-items-start">
                        <div class="docs-step-number"><i class="bi bi-person-fill" style="font-size:12px;"></i></div>
                        <div>
                            <div class="docs-step-title">Agent Dashboard</div>
                            <p class="docs-step-desc">Agents see <strong>their assigned leads, pending follow-ups, tasks due today, and personal performance</strong> metrics. Everything is scoped to only the leads assigned to you.</p>
                        </div>
                    </div>
                </div>

                <div class="docs-info-box info">
                    <i class="bi bi-info-circle-fill"></i>
                    <div><strong>Navigation:</strong> Use the <strong>left sidebar</strong> to navigate between modules. On mobile, tap the <i class="bi bi-list"></i> hamburger icon at the top to open the menu.</div>
                </div>
            </div>

            <!-- ========== SECTION: ROLES ========== -->
            <div class="docs-section" id="roles">
                <div class="docs-section-title">
                    <div class="section-icon" style="background:linear-gradient(135deg,#8b5cf6,#6d28d9);"><i class="bi bi-shield-lock"></i></div>
                    User Roles & Permissions
                </div>
                <p class="docs-section-subtitle">Understand the different access levels in LeadFlow CRM.</p>

                <div class="docs-step-card">
                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                        <div>
                            <div class="docs-step-title"><span class="docs-role-badge owner me-2">Owner</span> Organization Owner</div>
                            <p class="docs-step-desc">Full control — manage org settings, invite users, configure lead distribution rules, view all leads & reports, manage billing, and access all modules.</p>
                        </div>
                    </div>
                </div>
                <div class="docs-step-card">
                    <div>
                        <div class="docs-step-title"><span class="docs-role-badge admin me-2">Admin</span> Organization Admin</div>
                        <p class="docs-step-desc">Similar to Owner but without billing management. Can manage team members, view all leads, configure assignment rules, and access reports.</p>
                    </div>
                </div>
                <div class="docs-step-card">
                    <div>
                        <div class="docs-step-title"><span class="docs-role-badge" style="background:rgba(59,130,246,0.1);color:#2563eb;">TEAM LEAD</span> Team Lead</div>
                        <p class="docs-step-desc">Can view leads assigned to their team, manage pipeline, create deals and follow-ups, and access limited settings like lead assignment.</p>
                    </div>
                </div>
                <div class="docs-step-card">
                    <div>
                        <div class="docs-step-title"><span class="docs-role-badge agent me-2">Agent</span> Sales Agent</div>
                        <p class="docs-step-desc">Can only view and work on <strong>leads assigned to them</strong>. Can update lead status, add notes, create follow-ups, manage personal tasks, and create deals on their leads.</p>
                    </div>
                </div>
            </div>

            <!-- ========== SECTION: ORG SETTINGS ========== -->
            <div class="docs-section" id="org-settings">
                <div class="docs-section-title">
                    <div class="section-icon" style="background:linear-gradient(135deg,#f59e0b,#d97706);"><i class="bi bi-building-gear"></i></div>
                    Organization Settings
                </div>
                <p class="docs-section-subtitle">Configure your company profile and lead distribution mode.</p>

                <div class="docs-step-card">
                    <div class="d-flex gap-3 align-items-start">
                        <div class="docs-step-number">1</div>
                        <div>
                            <div class="docs-step-title">Navigate to Settings</div>
                            <p class="docs-step-desc">In the sidebar, click <strong>Org Settings</strong> under the Settings section. Only <span class="docs-role-badge owner">Owner</span> and <span class="docs-role-badge admin">Admin</span> roles can access this.</p>
                        </div>
                    </div>
                </div>
                <div class="docs-step-card">
                    <div class="d-flex gap-3 align-items-start">
                        <div class="docs-step-number">2</div>
                        <div>
                            <div class="docs-step-title">Edit Company Profile</div>
                            <p class="docs-step-desc">Update your <strong>Organization Name, Contact Email, Phone, Website, Address</strong> and upload your <strong>Company Logo</strong> (JPG, PNG, WebP — max 2MB).</p>
                        </div>
                    </div>
                </div>
                <div class="docs-step-card">
                    <div class="d-flex gap-3 align-items-start">
                        <div class="docs-step-number">3</div>
                        <div>
                            <div class="docs-step-title">Set Lead Distribution Mode</div>
                            <p class="docs-step-desc">Choose between:<br>
                            • <strong>Manual Assignment</strong> — You or admins manually assign incoming leads to agents.<br>
                            • <strong>Auto (Round Robin)</strong> — New leads are automatically distributed to available agents equally.</p>
                        </div>
                    </div>
                </div>
                <div class="docs-step-card">
                    <div class="d-flex gap-3 align-items-start">
                        <div class="docs-step-number">4</div>
                        <div>
                            <div class="docs-step-title">Save Changes</div>
                            <p class="docs-step-desc">Click <strong>"Save Changes"</strong> to apply. Your Quick Info panel on the right shows your org status, total users, total leads, and creation date.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ========== SECTION: TEAM ========== -->
            <div class="docs-section" id="team">
                <div class="docs-section-title">
                    <div class="section-icon" style="background:linear-gradient(135deg,#3b82f6,#1d4ed8);"><i class="bi bi-people-fill"></i></div>
                    Team Management
                </div>
                <p class="docs-section-subtitle">Add, edit, and manage your sales team members.</p>

                <div class="docs-step-card">
                    <div class="d-flex gap-3 align-items-start">
                        <div class="docs-step-number">1</div>
                        <div>
                            <div class="docs-step-title">Go to Team</div>
                            <p class="docs-step-desc">Click <strong>"Team"</strong> in the sidebar under the Management section. You'll see a list of all team members with their roles, availability status, and lead counts.</p>
                        </div>
                    </div>
                </div>
                <div class="docs-step-card">
                    <div class="d-flex gap-3 align-items-start">
                        <div class="docs-step-number">2</div>
                        <div>
                            <div class="docs-step-title">Add a New Member</div>
                            <p class="docs-step-desc">Click <strong>"+ Add User"</strong>. Fill in <strong>Name, Email, Password</strong>, and select the <strong>Role</strong> (Admin, Team Lead, or Agent). Click <strong>Create User</strong>.</p>
                        </div>
                    </div>
                </div>
                <div class="docs-step-card">
                    <div class="d-flex gap-3 align-items-start">
                        <div class="docs-step-number">3</div>
                        <div>
                            <div class="docs-step-title">Toggle Availability</div>
                            <p class="docs-step-desc">Each user has an <strong>Availability toggle</strong>. When set to <strong>Available</strong>, that agent will receive auto-assigned leads. Toggle to <strong>Unavailable</strong> when they're on leave or busy.</p>
                        </div>
                    </div>
                </div>
                <div class="docs-step-card">
                    <div class="d-flex gap-3 align-items-start">
                        <div class="docs-step-number">4</div>
                        <div>
                            <div class="docs-step-title">Edit or Remove</div>
                            <p class="docs-step-desc">Use the <strong>Edit</strong> button to update a member's details or role. Use <strong>Delete</strong> to deactivate a user. Their leads remain in the system.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ========== SECTION: ASSIGNMENT ========== -->
            <div class="docs-section" id="assignment">
                <div class="docs-section-title">
                    <div class="section-icon" style="background:linear-gradient(135deg,#ec4899,#be185d);"><i class="bi bi-shuffle"></i></div>
                    Lead Distribution & Assignment
                </div>
                <p class="docs-section-subtitle">Configure how leads are distributed to your team.</p>

                <div class="docs-step-card">
                    <div class="d-flex gap-3 align-items-start">
                        <div class="docs-step-number">1</div>
                        <div>
                            <div class="docs-step-title">Create Assignment Rules</div>
                            <p class="docs-step-desc">Go to <strong>Settings → Lead Assignment</strong>. Click <strong>"New Rule"</strong>. Choose a rule type:<br>
                            • <strong>Round Robin</strong> — Automatically rotate leads among selected agents equally.<br>
                            • <strong>Source Based</strong> — Route leads from a specific source (Facebook, Website, etc.) to certain agents.<br>
                            • <strong>Manual</strong> — No automation; you assign leads yourself.</p>
                        </div>
                    </div>
                </div>
                <div class="docs-step-card">
                    <div class="d-flex gap-3 align-items-start">
                        <div class="docs-step-number">2</div>
                        <div>
                            <div class="docs-step-title">Select Agents</div>
                            <p class="docs-step-desc">Tick the agents who should receive leads for this rule. Set the rule to <strong>Active</strong> and click <strong>"Create Rule"</strong>.</p>
                        </div>
                    </div>
                </div>
                <div class="docs-step-card">
                    <div class="d-flex gap-3 align-items-start">
                        <div class="docs-step-number">3</div>
                        <div>
                            <div class="docs-step-title">Auto-Assign Unassigned Leads</div>
                            <p class="docs-step-desc">If you have existing unassigned leads, use the <strong>"Auto-Assign"</strong> button in the stats panel to distribute them based on your active rules.</p>
                        </div>
                    </div>
                </div>
                <div class="docs-step-card">
                    <div class="d-flex gap-3 align-items-start">
                        <div class="docs-step-number">4</div>
                        <div>
                            <div class="docs-step-title">Reassign Leads Between Agents</div>
                            <p class="docs-step-desc">Use the <strong>"Reassign Leads"</strong> section at the bottom. Select <strong>From Agent</strong> and <strong>To Agent</strong>, then click <strong>"Reassign All"</strong> to transfer all leads from one agent to another.</p>
                        </div>
                    </div>
                </div>

                <div class="docs-info-box warning">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <div><strong>Important:</strong> Reassigning moves ALL leads from one agent to another. This action cannot be undone — use it carefully.</div>
                </div>
            </div>

            <!-- ========== SECTION: LEADS ========== -->
            <div class="docs-section" id="leads">
                <div class="docs-section-title">
                    <div class="section-icon" style="background:linear-gradient(135deg,#6366f1,#4f46e5);"><i class="bi bi-person-lines-fill"></i></div>
                    Managing Leads
                </div>
                <p class="docs-section-subtitle">View, filter, assign, and act on your leads.</p>

                <div class="docs-step-card">
                    <div class="d-flex gap-3 align-items-start">
                        <div class="docs-step-number">1</div>
                        <div>
                            <div class="docs-step-title">View All Leads</div>
                            <p class="docs-step-desc">Click <strong>"Leads"</strong> in the sidebar. You'll see a table with columns for <strong>Name, Phone, Status, Pipeline Stage, Priority, Source, Assigned Agent, Notes, and Actions</strong>.</p>
                        </div>
                    </div>
                </div>
                <div class="docs-step-card">
                    <div class="d-flex gap-3 align-items-start">
                        <div class="docs-step-number">2</div>
                        <div>
                            <div class="docs-step-title">Filter & Search</div>
                            <p class="docs-step-desc">Use the filter bar at the top to narrow down leads by:<br>
                            • <strong>Search</strong> — Name, phone, or company<br>
                            • <strong>Status</strong> — Pipeline stage (New Lead, Contacted, Qualified, etc.)<br>
                            • <strong>Priority</strong> — 🔥 Hot, ☀️ Warm, ❄️ Cold<br>
                            • <strong>Source</strong> — Facebook Ads, Manual, Excel Import<br>
                            • <strong>Agent</strong> — Filter by assigned team member<br>
                            • <strong>Facebook Page</strong> — Filter leads from a specific FB page</p>
                        </div>
                    </div>
                </div>
                <div class="docs-step-card">
                    <div class="d-flex gap-3 align-items-start">
                        <div class="docs-step-number">3</div>
                        <div>
                            <div class="docs-step-title">Change Lead Status</div>
                            <p class="docs-step-desc">Click the <strong>status badge</strong> (e.g., "New Lead") on any lead row. A dropdown will appear — select the new pipeline stage. The change is saved instantly.</p>
                        </div>
                    </div>
                </div>
                <div class="docs-step-card">
                    <div class="d-flex gap-3 align-items-start">
                        <div class="docs-step-number">4</div>
                        <div>
                            <div class="docs-step-title">Assign Lead to Agent</div>
                            <p class="docs-step-desc">Click the <strong>agent name/avatar</strong> on any lead row. Select a new agent from the dropdown. Only Owners, Admins, and Team Leads can reassign leads.</p>
                        </div>
                    </div>
                </div>
                <div class="docs-step-card">
                    <div class="d-flex gap-3 align-items-start">
                        <div class="docs-step-number">5</div>
                        <div>
                            <div class="docs-step-title">Quick Actions</div>
                            <p class="docs-step-desc">Each lead row has quick action buttons:<br>
                            • <i class="bi bi-telephone-fill text-primary"></i> <strong>Call</strong> — Opens your phone dialer<br>
                            • <i class="bi bi-whatsapp text-success"></i> <strong>WhatsApp</strong> — Opens WhatsApp chat with the lead<br>
                            • <i class="bi bi-pencil-square text-primary"></i> <strong>Add Note</strong> — Quick inline note<br>
                            • <i class="bi bi-arrow-right text-primary"></i> <strong>View Detail</strong> — Opens the full lead profile</p>
                        </div>
                    </div>
                </div>
                <div class="docs-step-card">
                    <div class="d-flex gap-3 align-items-start">
                        <div class="docs-step-number">6</div>
                        <div>
                            <div class="docs-step-title">Bulk Actions</div>
                            <p class="docs-step-desc">Select multiple leads using checkboxes, then use the <strong>Bulk Actions Bar</strong> to:<br>
                            • 🗑️ <strong>Delete Selected</strong><br>
                            • Change <strong>Status</strong> in bulk<br>
                            • <strong>Assign</strong> all selected leads to an agent</p>
                        </div>
                    </div>
                </div>
                <div class="docs-step-card">
                    <div class="d-flex gap-3 align-items-start">
                        <div class="docs-step-number">7</div>
                        <div>
                            <div class="docs-step-title">Export Leads</div>
                            <p class="docs-step-desc">Click the <strong>"Export"</strong> button in the header to download leads as a CSV file. Your active filters will be applied to the export.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ========== SECTION: ADD LEAD ========== -->
            <div class="docs-section" id="add-lead">
                <div class="docs-section-title">
                    <div class="section-icon" style="background:linear-gradient(135deg,#10b981,#059669);"><i class="bi bi-plus-circle"></i></div>
                    Add & Import Leads
                </div>
                <p class="docs-section-subtitle">Two ways to get leads into LeadFlow — manually or via Excel/CSV.</p>

                <div class="docs-step-card">
                    <div class="d-flex gap-3 align-items-start">
                        <div class="docs-step-number">A</div>
                        <div>
                            <div class="docs-step-title">Manual Entry</div>
                            <p class="docs-step-desc">Click <strong>"+ Add Lead"</strong> from the Leads page header. Fill in:<br>
                            • <strong>Name</strong> (required) & <strong>Phone</strong> (required)<br>
                            • Email, Company, Source, Priority, Notes<br>
                            • Assign to an agent and select a pipeline stage<br>
                            Click <strong>"Save Lead"</strong>.</p>
                        </div>
                    </div>
                </div>
                <div class="docs-step-card">
                    <div class="d-flex gap-3 align-items-start">
                        <div class="docs-step-number">B</div>
                        <div>
                            <div class="docs-step-title">Excel / CSV Import</div>
                            <p class="docs-step-desc">Click <strong>"Import Leads"</strong> from the Leads page header. Your file must follow this format:</p>
                            <table class="table table-bordered table-sm mt-2 mb-2" style="font-size:12px;">
                                <thead class="table-light"><tr><th>Name ✱</th><th>Phone ✱</th><th>Email</th><th>Source</th><th>Note</th></tr></thead>
                                <tbody><tr><td>Rahul Sharma</td><td>9876543210</td><td>rahul@email.com</td><td>Website</td><td>Interested</td></tr></tbody>
                            </table>
                            <p class="docs-step-desc">Supported formats: <span class="docs-kbd">.csv</span> <span class="docs-kbd">.xls</span> <span class="docs-kbd">.xlsx</span>. The first row must be a header row.</p>
                        </div>
                    </div>
                </div>

                <div class="docs-info-box tip">
                    <i class="bi bi-lightbulb-fill"></i>
                    <div><strong>Tip:</strong> If you're having trouble with .xlsx files, save your Excel file as CSV (Comma Delimited) and upload the CSV instead.</div>
                </div>
            </div>

            <!-- ========== SECTION: PIPELINE ========== -->
            <div class="docs-section" id="pipeline">
                <div class="docs-section-title">
                    <div class="section-icon" style="background:linear-gradient(135deg,#0ea5e9,#0284c7);"><i class="bi bi-kanban"></i></div>
                    Sales Pipeline (Kanban Board)
                </div>
                <p class="docs-section-subtitle">Visualize your entire sales process with drag-and-drop pipeline columns.</p>

                <div class="docs-step-card">
                    <div class="d-flex gap-3 align-items-start">
                        <div class="docs-step-number">1</div>
                        <div>
                            <div class="docs-step-title">View the Pipeline</div>
                            <p class="docs-step-desc">Click <strong>"Pipeline"</strong> in the sidebar. You'll see a Kanban-style board with columns for each stage (e.g., New Lead → Contacted → Qualified → Proposal → Negotiation → Won → Lost).</p>
                        </div>
                    </div>
                </div>
                <div class="docs-step-card">
                    <div class="d-flex gap-3 align-items-start">
                        <div class="docs-step-number">2</div>
                        <div>
                            <div class="docs-step-title">Drag & Drop Leads</div>
                            <p class="docs-step-desc"><strong>Click and drag</strong> any lead card from one column to another to update its pipeline stage. The change is saved automatically via AJAX — no page reload needed!</p>
                        </div>
                    </div>
                </div>
                <div class="docs-step-card">
                    <div class="d-flex gap-3 align-items-start">
                        <div class="docs-step-number">3</div>
                        <div>
                            <div class="docs-step-title">Filter by Agent & Date</div>
                            <p class="docs-step-desc">Use the filter bar at the top to see a specific agent's pipeline or leads within a date range. Click <strong>"Clear"</strong> to remove filters.</p>
                        </div>
                    </div>
                </div>
                <div class="docs-step-card">
                    <div class="d-flex gap-3 align-items-start">
                        <div class="docs-step-number">4</div>
                        <div>
                            <div class="docs-step-title">Unassigned Leads</div>
                            <p class="docs-step-desc">Leads not yet assigned to any pipeline stage appear in the <strong>"Unassigned to Pipeline"</strong> section below the board. Drag them into the appropriate column.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ========== SECTION: DEALS ========== -->
            <div class="docs-section" id="deals">
                <div class="docs-section-title">
                    <div class="section-icon" style="background:linear-gradient(135deg,#f59e0b,#d97706);"><i class="bi bi-trophy"></i></div>
                    Deals & Revenue
                </div>
                <p class="docs-section-subtitle">Track deal values, win rates, and revenue from qualified leads.</p>

                <div class="docs-step-card">
                    <div class="d-flex gap-3 align-items-start">
                        <div class="docs-step-number">1</div>
                        <div>
                            <div class="docs-step-title">Revenue Stats</div>
                            <p class="docs-step-desc">At the top of the Deals page, you'll see three summary cards: <strong>Won Revenue</strong> (closed deals), <strong>Pipeline Value</strong> (open deals), and <strong>Win Rate</strong> (conversion percentage).</p>
                        </div>
                    </div>
                </div>
                <div class="docs-step-card">
                    <div class="d-flex gap-3 align-items-start">
                        <div class="docs-step-number">2</div>
                        <div>
                            <div class="docs-step-title">Create a New Deal</div>
                            <p class="docs-step-desc">Click <strong>"+ New Deal"</strong>. Fill in the deal name, link it to a lead, set the <strong>deal value (₹)</strong>, select a pipeline stage, set the expected close date, and assign an agent. Click <strong>Save</strong>.</p>
                        </div>
                    </div>
                </div>
                <div class="docs-step-card">
                    <div class="d-flex gap-3 align-items-start">
                        <div class="docs-step-number">3</div>
                        <div>
                            <div class="docs-step-title">Deal Statuses</div>
                            <p class="docs-step-desc">Each deal has a status: <span class="badge bg-primary bg-opacity-10 text-primary">Open</span>, <span class="badge bg-success bg-opacity-10 text-success">Won</span>, or <span class="badge bg-danger bg-opacity-10 text-danger">Lost</span>. Mark a deal as Won or Lost from the deal view/edit page.</p>
                        </div>
                    </div>
                </div>
                <div class="docs-step-card">
                    <div class="d-flex gap-3 align-items-start">
                        <div class="docs-step-number">4</div>
                        <div>
                            <div class="docs-step-title">Filter & Search Deals</div>
                            <p class="docs-step-desc">Use the search bar and filter dropdowns to find deals by status (Open / Won / Lost) or pipeline stage.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ========== SECTION: FOLLOWUPS ========== -->
            <div class="docs-section" id="followups">
                <div class="docs-section-title">
                    <div class="section-icon" style="background:linear-gradient(135deg,#ef4444,#dc2626);"><i class="bi bi-clock-history"></i></div>
                    Follow-ups
                </div>
                <p class="docs-section-subtitle">Schedule, track, and complete follow-up reminders.</p>

                <div class="docs-step-card">
                    <div class="d-flex gap-3 align-items-start">
                        <div class="docs-step-number">1</div>
                        <div>
                            <div class="docs-step-title">View Follow-ups</div>
                            <p class="docs-step-desc">Click <strong>"Follow-ups"</strong> in the sidebar. Use the tab filters: <strong>Today, Upcoming, Overdue, Completed, All</strong>. The stat cards show today's count and overdue count.</p>
                        </div>
                    </div>
                </div>
                <div class="docs-step-card">
                    <div class="d-flex gap-3 align-items-start">
                        <div class="docs-step-number">2</div>
                        <div>
                            <div class="docs-step-title">Schedule a Follow-up</div>
                            <p class="docs-step-desc">Use the form on the right side. Enter a <strong>Title</strong>, optionally link to a <strong>Lead</strong>, pick a <strong>Date & Time</strong>, set <strong>Priority</strong> (🔴 High, 🟡 Medium, 🔵 Low), and optionally add Notes. Click <strong>"Schedule"</strong>.</p>
                        </div>
                    </div>
                </div>
                <div class="docs-step-card">
                    <div class="d-flex gap-3 align-items-start">
                        <div class="docs-step-number">3</div>
                        <div>
                            <div class="docs-step-title">Mark as Complete</div>
                            <p class="docs-step-desc">Click the green <strong>✓ checkmark button</strong> next to any pending follow-up to mark it as completed. Overdue follow-ups appear with a red <i class="bi bi-exclamation-circle-fill text-danger"></i> icon.</p>
                        </div>
                    </div>
                </div>

                <div class="docs-info-box warning">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <div><strong>Don't miss overdue follow-ups!</strong> They indicate leads that haven't been contacted on time. Check the "Overdue" tab daily.</div>
                </div>
            </div>

            <!-- ========== SECTION: TASKS ========== -->
            <div class="docs-section" id="tasks">
                <div class="docs-section-title">
                    <div class="section-icon" style="background:linear-gradient(135deg,#14b8a6,#0d9488);"><i class="bi bi-check2-square"></i></div>
                    Tasks
                </div>
                <p class="docs-section-subtitle">Create and manage action items for yourself or your team.</p>

                <div class="docs-step-card">
                    <div class="d-flex gap-3 align-items-start">
                        <div class="docs-step-number">1</div>
                        <div>
                            <div class="docs-step-title">View Tasks</div>
                            <p class="docs-step-desc">Click <strong>"Tasks"</strong> in the sidebar. Toggle between <strong>Pending</strong> and <strong>Completed</strong> using the buttons at the top. Tasks show the title, description, linked lead, due date, and assigned agent.</p>
                        </div>
                    </div>
                </div>
                <div class="docs-step-card">
                    <div class="d-flex gap-3 align-items-start">
                        <div class="docs-step-number">2</div>
                        <div>
                            <div class="docs-step-title">Complete a Task</div>
                            <p class="docs-step-desc">Click the green <strong>"✓ Complete"</strong> button on any pending task. The task will move to the Completed view.</p>
                        </div>
                    </div>
                </div>
                <div class="docs-step-card">
                    <div class="d-flex gap-3 align-items-start">
                        <div class="docs-step-number">3</div>
                        <div>
                            <div class="docs-step-title">Calendar View</div>
                            <p class="docs-step-desc">Click <strong>"Calendar"</strong> at the top to see a calendar view of all tasks with their due dates, making it easy to plan your week.</p>
                        </div>
                    </div>
                </div>

                <div class="docs-info-box tip">
                    <i class="bi bi-lightbulb-fill"></i>
                    <div><strong>Tip:</strong> Tasks with overdue due dates are highlighted in <span class="text-danger fw-bold">red</span> so you can prioritize them easily.</div>
                </div>
            </div>

            <!-- ========== SECTION: NOTES ========== -->
            <div class="docs-section" id="notes">
                <div class="docs-section-title">
                    <div class="section-icon" style="background:linear-gradient(135deg,#8b5cf6,#7c3aed);"><i class="bi bi-journal-text"></i></div>
                    Notes & Activity
                </div>
                <p class="docs-section-subtitle">Record interactions and track lead engagement.</p>

                <div class="docs-step-card">
                    <div class="d-flex gap-3 align-items-start">
                        <div class="docs-step-number">1</div>
                        <div>
                            <div class="docs-step-title">Quick Notes from Leads Table</div>
                            <p class="docs-step-desc">In the Leads table, click the <strong><i class="bi bi-pencil-square text-primary"></i> "Add Note"</strong> button on any row. Type your note in the popup and save — no page navigation needed.</p>
                        </div>
                    </div>
                </div>
                <div class="docs-step-card">
                    <div class="d-flex gap-3 align-items-start">
                        <div class="docs-step-number">2</div>
                        <div>
                            <div class="docs-step-title">Detailed Notes on Lead Profile</div>
                            <p class="docs-step-desc">Open a lead's detail page by clicking their name or the <i class="bi bi-arrow-right text-primary"></i> arrow. Scroll down to the <strong>Notes & Timeline</strong> section to add detailed notes with timestamps.</p>
                        </div>
                    </div>
                </div>
                <div class="docs-step-card">
                    <div class="d-flex gap-3 align-items-start">
                        <div class="docs-step-number">3</div>
                        <div>
                            <div class="docs-step-title">Activity Log</div>
                            <p class="docs-step-desc">Every action on a lead — status change, assignment, note added — is recorded in the <strong>Activity Timeline</strong> on the lead's profile page. This helps you trace the full engagement history.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ========== SECTION: REPORTS ========== -->
            <div class="docs-section" id="reports">
                <div class="docs-section-title">
                    <div class="section-icon" style="background:linear-gradient(135deg,#f97316,#ea580c);"><i class="bi bi-bar-chart-fill"></i></div>
                    Reports & Analytics
                </div>
                <p class="docs-section-subtitle">Track performance, revenue, and team productivity.</p>

                <div class="docs-step-card">
                    <div class="d-flex gap-3 align-items-start">
                        <div class="docs-step-number">1</div>
                        <div>
                            <div class="docs-step-title">Access Reports</div>
                            <p class="docs-step-desc">Click <strong>"Reports"</strong> in the sidebar. Use the <strong>Date Range filter</strong> (Today, Last 7 Days, This Month, Custom Range) and optionally filter by <strong>Agent</strong>.</p>
                        </div>
                    </div>
                </div>
                <div class="docs-step-card">
                    <div class="d-flex gap-3 align-items-start">
                        <div class="docs-step-number">2</div>
                        <div>
                            <div class="docs-step-title">Summary Cards</div>
                            <p class="docs-step-desc">The top row shows key metrics: <strong>Total Leads, Converted Leads, Total Revenue, Overdue Follow-ups</strong> — with arrows showing trends.</p>
                        </div>
                    </div>
                </div>
                <div class="docs-step-card">
                    <div class="d-flex gap-3 align-items-start">
                        <div class="docs-step-number">3</div>
                        <div>
                            <div class="docs-step-title">Visual Charts</div>
                            <p class="docs-step-desc">Reports include interactive charts:<br>
                            • <strong>Daily Lead Trend</strong> — Line chart of leads over time<br>
                            • <strong>Leads by Pipeline Stage</strong> — Doughnut chart<br>
                            • <strong>Leads by Source</strong> — Horizontal bar chart<br>
                            • <strong>Pipeline Values</strong> — Grouped bar chart of leads & deals per stage</p>
                        </div>
                    </div>
                </div>
                <div class="docs-step-card">
                    <div class="d-flex gap-3 align-items-start">
                        <div class="docs-step-number">4</div>
                        <div>
                            <div class="docs-step-title">Agent Performance Leaderboard</div>
                            <p class="docs-step-desc">Admins can see the <strong>Agent Performance table</strong> with: Leads Assigned, Leads Contacted, Deals Won, Conversion Ratio, and Average Response Time. Identify top performers and areas for coaching.</p>
                        </div>
                    </div>
                </div>
                <div class="docs-step-card">
                    <div class="d-flex gap-3 align-items-start">
                        <div class="docs-step-number">5</div>
                        <div>
                            <div class="docs-step-title">Export Reports</div>
                            <p class="docs-step-desc">Click the green <strong>"Export"</strong> button next to the Apply filter to download your report data as a file for sharing or offline analysis.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="text-center py-5 border-top mt-5">
                <p class="text-muted small mb-1">Need help? Contact your organization administrator.</p>
                <p class="text-muted small mb-0">&copy; <?= date('Y') ?> LeadFlow CRM by Global Webify. All rights reserved.</p>
            </div>

        </div>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Mobile sidebar toggle
function toggleSidebar() {
    document.getElementById('docsSidebar').classList.toggle('show');
    document.getElementById('docsOverlay').classList.toggle('show');
}

// Active sidebar link on scroll
const sections = document.querySelectorAll('.docs-section');
const sidebarLinks = document.querySelectorAll('.docs-sidebar-link');

const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            sidebarLinks.forEach(link => link.classList.remove('active'));
            const id = entry.target.id;
            const activeLink = document.querySelector(`.docs-sidebar-link[data-section="${id}"]`);
            if (activeLink) activeLink.classList.add('active');
        }
    });
}, { rootMargin: '-20% 0px -70% 0px' });

sections.forEach(section => observer.observe(section));

// Sidebar link click closes mobile sidebar
sidebarLinks.forEach(link => {
    link.addEventListener('click', () => {
        if (window.innerWidth < 993) {
            toggleSidebar();
        }
    });
});

// Search functionality
function searchDocs(query) {
    query = query.toLowerCase().trim();
    const cards = document.querySelectorAll('.docs-step-card');
    const sects = document.querySelectorAll('.docs-section');

    if (!query) {
        cards.forEach(c => c.style.display = '');
        sects.forEach(s => s.style.display = '');
        return;
    }

    sects.forEach(section => {
        const sectionCards = section.querySelectorAll('.docs-step-card');
        let hasMatch = false;
        sectionCards.forEach(card => {
            const text = card.textContent.toLowerCase();
            if (text.includes(query)) {
                card.style.display = '';
                hasMatch = true;
            } else {
                card.style.display = 'none';
            }
        });
        // Show section title if any card matches
        const title = section.querySelector('.docs-section-title');
        const subtitle = section.querySelector('.docs-section-subtitle');
        if (title) title.style.display = hasMatch ? '' : 'none';
        if (subtitle) subtitle.style.display = hasMatch ? '' : 'none';
        // Also check section title text
        if (title && title.textContent.toLowerCase().includes(query)) {
            section.style.display = '';
            sectionCards.forEach(c => c.style.display = '');
            if (title) title.style.display = '';
            if (subtitle) subtitle.style.display = '';
        } else if (!hasMatch) {
            section.style.display = 'none';
        } else {
            section.style.display = '';
        }
    });
}
</script>
</body>
</html>
