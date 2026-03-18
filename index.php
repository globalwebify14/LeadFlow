<?php
require_once __DIR__ . '/config/auth.php';
if (isLoggedIn()) {
    header('Location: ' . BASE_URL . 'modules/dashboard/');
    exit;
}
?>
<?php 
$pageTitle = 'Advanced Lead Management SaaS';
$isLanding = true;
include_once __DIR__ . '/includes/landing_header.php'; 
?>

    <!-- Hero -->
    <section class="hero">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6 hero-content" data-aos="fade-right">
                    <div class="hero-badge">
                        <i class="bi bi-megaphone-fill me-2 text-primary"></i> <span>Official Meta Partner SaaS Integration</span>
                    </div>
                    <h1 class="hero-title">Automate wewgartyju your sales with <span>Advanced CRM</span></h1>
                    <p class="hero-subtitle">The ultimate multi-tenant CRM designed for organizations to seamlessly connect Meta Ads, manage role-based pipelines, and close more deals autonomously.</p>
                    <div class="d-flex flex-wrap gap-3">
                        <a href="<?= BASE_URL ?>login.php" class="btn-primary-custom">Get Started Now <i class="bi bi-arrow-right ms-2"></i></a>
                    </div>
                    <div class="mt-4 pt-3 d-flex align-items-center gap-4 text-muted small fw-semibold">
                        <span><i class="bi bi-check-circle-fill text-success me-1"></i> Global Webhooks</span>
                        <span><i class="bi bi-check-circle-fill text-success me-1"></i> Multi-Tenant Architecture</span>
                    </div>
                </div>
                <div class="col-lg-6" data-aos="fade-left" data-aos-delay="200">
                    <div class="hero-image-wrapper bg-white p-3">
                        <!-- Dashboard UI Mockup using Bootstrap Utilities to represent the complex CRM view without loading an actual image -->
                        <div class="rounded overflow-hidden border border-light shadow-sm" style="background:#f8fafc; height:400px; display:flex;">
                            <div style="width:25%; background:#1e293b; padding:20px;">
                                <div class="text-white fw-bold mb-4 opacity-75"><i class="bi bi-rocket me-2"></i> CRM</div>
                                <div class="bg-white bg-opacity-10 rounded p-2 mb-2 text-white small opacity-75"><i class="bi bi-grid-1x2 me-2"></i> Dashboard</div>
                                <div class="bg-white bg-opacity-25 rounded p-2 mb-2 text-white small"><i class="bi bi-facebook me-2"></i> Meta Leads</div>
                                <div class="bg-white bg-opacity-10 rounded p-2 mb-2 text-white small opacity-75"><i class="bi bi-people me-2"></i> My Team</div>
                            </div>
                            <div style="width:75%; padding:20px;">
                                <div class="d-flex justify-content-between mb-4">
                                    <h5 class="fw-bold m-0 text-dark">Meta Graph Dashboard</h5>
                                    <div><span class="badge bg-success">Status: Syncing</span></div>
                                </div>
                                <div class="row g-3">
                                    <div class="col-6"><div class="bg-white rounded border shadow-sm p-3"><h6 class="text-muted small">New Facebook Leads</h6><h3 class="fw-bold m-0 text-primary">2,481</h3></div></div>
                                    <div class="col-6"><div class="bg-white rounded border shadow-sm p-3"><h6 class="text-muted small">Closed Revenue</h6><h3 class="fw-bold m-0 text-success">$94K</h3></div></div>
                                </div>
                                <div class="mt-4 bg-white border rounded shadow-sm p-3">
                                    <h6 class="fw-bold small mb-3">Live Webhook Feed</h6>
                                    <div class="d-flex gap-3 mb-2 border-bottom pb-2">
                                        <div class="text-primary"><i class="bi bi-facebook"></i></div><div class="small fw-semibold flex-grow-1">John Doe</div><div class="small text-muted">2 mins ago</div>
                                    </div>
                                    <div class="d-flex gap-3 mb-2 border-bottom pb-2">
                                        <div class="text-primary"><i class="bi bi-facebook"></i></div><div class="small fw-semibold flex-grow-1">Sarah Smith</div><div class="small text-muted">12 mins ago</div>
                                    </div>
                                    <div class="d-flex gap-3 mb-2">
                                        <div class="text-primary"><i class="bi bi-facebook"></i></div><div class="small fw-semibold flex-grow-1">Mike Johnson</div><div class="small text-muted">1 hour ago</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Meta Logo Section -->
    <div class="integration-logos text-center">
        <div class="container">
            <p class="mb-4">Officially integrated with platform leaders</p>
            <div class="d-flex justify-content-center align-items-center gap-5 flex-wrap">
                <div class="meta-logo"><i class="bi bi-meta" style="font-size:32px;"></i> Meta</div>
                <div class="text-secondary fw-bold fs-5"><i class="bi bi-facebook me-2"></i> Lead Ads</div>
                <div class="text-danger fw-bold fs-5"><i class="bi bi-instagram me-2"></i> Instagram Ads</div>
            </div>
        </div>
    </div>

    <!-- Features -->
    <section class="features-section" id="features">
        <div class="container">
            <div class="section-header" data-aos="fade-up">
                <h2>A complete Lead Management Engine</h2>
                <p>Built for scale. Everything from securely connecting complex Graph APIs to tracking daily sales calls, in one place.</p>
            </div>
            
            <div class="row g-4">
                <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="100">
                    <div class="feature-box">
                        <div class="feature-icon icon-meta"><i class="bi bi-facebook"></i></div>
                        <h4>Meta OAuth & Webhooks</h4>
                        <p>Fully compliant Facebook App integration. Org Owners securely authorize their pages via OAuth, and a single centralized webhook distributes leadgen payloads in real-time.</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="200">
                    <div class="feature-box">
                        <div class="feature-icon icon-hierarchy"><i class="bi bi-building"></i></div>
                        <h4>Multi-Tenant Separation</h4>
                        <p>A true SaaS architecture. Dozens of organizations can operate under the same platform with strict database isolation, independent workflows, and team constraints.</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6" data-aos="fade-up" data-aos-delay="300">
                    <div class="feature-box">
                        <div class="feature-icon icon-pipeline"><i class="bi bi-kanban"></i></div>
                        <h4>Dynamic Pipelines</h4>
                        <p>Move leads natively from imported prospect to Closed Won deal utilizing visual Kanban boards, follow-up scheduling grids, and autonomous status trackers.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Hierarchy Section -->
    <section class="hierarchy-section" id="hierarchy">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-5 mb-5 mb-lg-0" data-aos="fade-right">
                    <h2 class="fw-bold mb-4" style="font-size: 3rem; letter-spacing: -1px;">Role-Based Security</h2>
                    <p class="text-white opacity-75 fs-5 mb-5" style="line-height: 1.6;">Our robust permissions system ensures that everyone from the Platform Administrators to the daily Sales Agents only see the data they are authorized to manage.</p>
                    <a href="<?= BASE_URL ?>login.php" class="btn btn-outline-light rounded-pill px-4 py-3 fw-bold">Explore Dashboard <i class="bi bi-arrow-right ms-2"></i></a>
                </div>
                <div class="col-lg-6 offset-lg-1" data-aos="fade-left">
                    <div class="role-card">
                        <div class="role-title text-danger"><i class="bi bi-shield-lock-fill"></i> Platform Super Admin</div>
                        <p class="role-desc">Manages global subscriptions, creates organizations, and configures the single Meta App credential and Webhook endpoints for the entire platform.</p>
                    </div>
                    <div class="role-card">
                        <div class="role-title text-primary"><i class="bi bi-building-fill-gear"></i> Organization Owner</div>
                        <p class="role-desc">Connects their Facebook Page via OAuth, manages the company profile, and oversees all internal sales teams and total closed revenue.</p>
                    </div>
                    <div class="role-card">
                        <div class="role-title text-info"><i class="bi bi-person-hearts"></i> Team Leads & Admins</div>
                        <p class="role-desc">Manages internal users, tracks daily team performance across all campaigns, and reallocates cold leads.</p>
                    </div>
                    <div class="role-card">
                        <div class="role-title text-success"><i class="bi bi-headset"></i> Sales Agents</div>
                        <p class="role-desc">Logged into a focused pipeline where they receive instant notifications for new Meta leads and execute sales strategies.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA -->
    <section class="cta-section">
        <div class="container" data-aos="zoom-in">
            <h2>Ready to scale your leads?</h2>
            <p>Join the lead CRM platform that seamlessly handles Facebook Leads integration and multi-tenant organization workflows autonomously.</p>
            <a href="<?= BASE_URL ?>login.php" class="btn-white">Go To Login Portal</a>
        </div>
    </section>

    <?php include_once __DIR__ . '/includes/landing_footer.php'; ?>
