<?php
require_once __DIR__ . '/config/auth.php';
if (isLoggedIn()) {
    header('Location: ' . BASE_URL . 'modules/dashboard/');
    exit;
}

$pageTitle = 'LeadFlow — The Ultimate Lead Management CRM';
$isLanding = true;
include_once __DIR__ . '/includes/landing_header.php'; 
?>

<!-- Hero Section -->
<section class="lp-hero lp-section">
    <div class="lp-container">
        <div class="lp-hero-grid">
            <div class="lp-hero-content" data-aos="fade-right">
                <span class="lp-hero-badge"><i class="bi bi-meta me-2"></i> Official Meta Partner Integration</span>
                <h1>Capture, Engage & <span class="text-primary fw-extrabold">Convert Leads</span> Instantly</h1>
                <p>The high-performance CRM for sales teams. Automate your Facebook & Instagram Lead Ads sync, auto-assign agents, and close deals via WhatsApp — all in one unified platform.</p>
                <div class="d-flex flex-wrap gap-3 justify-content-center justify-content-lg-start">
                    <a href="<?= BASE_URL ?>login.php" class="lp-btn lp-btn-primary">Start Free Trial <i class="bi bi-arrow-right"></i></a>
                    <a href="#features" class="lp-btn lp-btn-outline">Watch Demo</a>
                </div>
                <div class="mt-5 d-flex flex-wrap align-items-center justify-content-center justify-content-lg-start gap-4 text-muted small fw-medium">
                    <span><i class="bi bi-check2-circle text-primary me-2"></i> Real-time Syncing</span>
                    <span><i class="bi bi-check2-circle text-primary me-2"></i> No Credit Card Required</span>
                </div>
            </div>
            <div class="lp-hero-visual" data-aos="fade-left" data-aos-delay="200">
                <img src="hero_dashboard_mockup_1774073943759.png" alt="LeadFlow Dashboard" class="lp-hero-mockup">
                <div class="lp-floating-card" style="top: 15%; right: -5%; border-left: 4px solid #10b981;">
                    <div class="icon bg-success bg-opacity-10 text-success p-2 rounded-circle">
                         <i class="bi bi-whatsapp"></i>
                    </div>
                    <div>
                        <div class="fw-bold small">New Lead via WhatsApp</div>
                        <div class="text-muted" style="font-size: 11px;">Message sent automatically</div>
                    </div>
                </div>
                <div class="lp-floating-card" style="bottom: 10%; left: -10%; border-left: 4px solid var(--lp-primary);">
                    <div class="icon bg-primary bg-opacity-10 text-primary p-2 rounded-circle">
                         <i class="bi bi-facebook"></i>
                    </div>
                    <div>
                        <div class="fw-bold small">+24 New Leads</div>
                        <div class="text-muted" style="font-size: 11px;">Synced from Meta Ads</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Partners -->
<div class="py-4 bg-white border-bottom">
    <div class="lp-container">
        <div class="d-flex justify-content-center justify-content-lg-between align-items-center flex-wrap gap-4 gap-md-5">
            <div class="lp-partner-logo"><i class="bi bi-meta"></i> Meta</div>
            <div class="lp-partner-logo"><i class="bi bi-facebook"></i> Facebook Ads</div>
            <div class="lp-partner-logo"><i class="bi bi-instagram"></i> Instagram</div>
            <div class="lp-partner-logo"><i class="bi bi-google"></i> Google Ads</div>
            <div class="lp-partner-logo"><i class="bi bi-linkedin"></i> LinkedIn</div>
        </div>
    </div>
</div>

<!-- Problem Section -->
<section class="lp-section lp-bg-alt">
    <div class="lp-container">
        <div class="row align-items-center g-5">
            <div class="col-lg-6" data-aos="fade-right">
                <h2 class="display-5 mb-4">Stop Losing Leads to <span class="text-primary">Manual Work</span></h2>
                <p class="fs-5 mb-5">Most businesses lose 60% of their leads because of slow response times. LeadFlow eliminates the gap between "Interested" and "Contacted".</p>
                
                <div class="d-flex flex-column gap-4">
                    <div class="d-flex gap-3">
                        <div class="fs-3 text-primary"><i class="bi bi-x-circle-fill"></i></div>
                        <div>
                            <h5 class="fw-bold">No More Messy Spreadsheets</h5>
                            <p class="text-muted">Stop downloading CSVs manually. Get every lead delivered to your CRM the second they click.</p>
                        </div>
                    </div>
                    <div class="d-flex gap-3">
                        <div class="fs-3 text-secondary"><i class="bi bi-clock-history"></i></div>
                        <div>
                            <h5 class="fw-bold">Zero Response Delay</h5>
                            <p class="text-muted">Automated WhatsApp messages ensure your leads get a reply while they are still on your page.</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6" data-aos="fade-left">
                <div class="bg-white p-2 rounded-4 shadow-lg border">
                    <img src="crm_workflow_illustration_1774074017794.png" alt="Workflow" class="w-100 rounded-3">
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Features Section -->
<section class="lp-section" id="features">
    <div class="lp-container">
        <div class="text-center mb-5" data-aos="fade-up">
            <h2 class="display-5 mb-3">Everything You Need to Scale</h2>
            <p class="fs-5 text-muted mx-auto" style="max-width: 700px;">Powerful tools built specifically for high-volume lead generation and professional sales teams.</p>
        </div>
        
        <div class="lp-feature-grid">
            <div class="lp-feature-card" data-aos="fade-up" data-aos-delay="100">
                <div class="lp-feature-icon"><i class="bi bi-lightning-charge-fill"></i></div>
                <h4>Real-Time Sync</h4>
                <p>Instant synchronization from Facebook, Instagram, and LinkedIn Lead Ads via Meta Graph API.</p>
            </div>
            <div class="lp-feature-card" data-aos="fade-up" data-aos-delay="200">
                <div class="lp-feature-icon"><i class="bi bi-people-fill"></i></div>
                <h4>Auto-Assignment</h4>
                <p>Distribute leads to your sales agents automatically based on round-robin or custom rules.</p>
            </div>
            <div class="lp-feature-card" data-aos="fade-up" data-aos-delay="300">
                <div class="lp-feature-icon"><i class="bi bi-whatsapp"></i></div>
                <h4>WhatsApp Automation</h4>
                <p>Trigger instant personalized WhatsApp messages the moment a new lead arrives.</p>
            </div>
            <div class="lp-feature-card" data-aos="fade-up" data-aos-delay="400">
                <div class="lp-feature-icon"><i class="bi bi-pie-chart-fill"></i></div>
                <h4>Advanced Analytics</h4>
                <p>Track conversion rates, agent performance, and ROI across all your advertising channels.</p>
            </div>
            <div class="lp-feature-card" data-aos="fade-up" data-aos-delay="500">
                <div class="lp-feature-icon"><i class="bi bi-shield-lock-fill"></i></div>
                <h4>Role Management</h4>
                <p>Multi-tenant architecture with granular permissions for Admins, Managers, and Agents.</p>
            </div>
            <div class="lp-feature-card" data-aos="fade-up" data-aos-delay="600">
                <div class="lp-feature-icon"><i class="bi bi-calendar-check-fill"></i></div>
                <h4>Smart Follow-ups</h4>
                <p>System-generated reminders and task lists to ensure no prospect is ever forgotten.</p>
            </div>
        </div>
    </div>
</section>

<!-- Dashboard Preview Section -->
<section class="lp-section lp-bg-alt overflow-hidden">
    <div class="lp-container">
        <div class="row align-items-center g-5">
            <div class="col-lg-5" data-aos="fade-right">
                <h2 class="display-5 mb-4">Command Center for Your Sales</h2>
                <p class="fs-5 mb-4 text-muted">A clean, intuitive interface designed for maximum productivity. Monitor your entire sales pipeline in real-time.</p>
                <div class="d-flex flex-column gap-3 mb-5">
                    <div class="d-flex align-items-center gap-2"><i class="bi bi-check-circle-fill text-primary"></i> <span>Live Webhook Feed</span></div>
                    <div class="d-flex align-items-center gap-2"><i class="bi bi-check-circle-fill text-primary"></i> <span>Visual Kanban Pipeline</span></div>
                    <div class="d-flex align-items-center gap-2"><i class="bi bi-check-circle-fill text-primary"></i> <span>Agent Performance Ranking</span></div>
                </div>
                <a href="<?= BASE_URL ?>login.php" class="lp-btn lp-btn-primary">Explore Dashboard</a>
            </div>
            <div class="col-lg-7" data-aos="fade-left">
                <div class="position-relative">
                    <img src="hero_dashboard_mockup_1774073943759.png" alt="Admin Dashboard" class="w-100 rounded-4 shadow-2xl border" style="transform: perspective(1000px) rotateY(-10deg);">
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Pricing Section -->
<section class="lp-section" id="pricing">
    <div class="lp-container">
        <div class="text-center mb-5" data-aos="fade-up">
            <h2 class="display-5 mb-3">Simple, Transparent Pricing</h2>
            <p class="fs-5 text-muted">Scale your business without worrying about per-lead costs.</p>
        </div>
        
        <div class="row g-4 align-items-center">
            <div class="col-lg-4" data-aos="fade-up" data-aos-delay="100">
                <div class="bg-white p-5 rounded-4 border">
                    <h4>Starter</h4>
                    <div class="display-4 fw-bold my-4">₹999<span class="fs-6 text-muted fw-normal">/mo</span></div>
                    <ul class="list-unstyled d-flex flex-column gap-3 mb-5 text-muted">
                        <li><i class="bi bi-check-lg text-primary me-2"></i> Up to 500 Leads/mo</li>
                        <li><i class="bi bi-check-lg text-primary me-2"></i> 2 Team Members</li>
                        <li><i class="bi bi-check-lg text-primary me-2"></i> Basic Meta Integration</li>
                    </ul>
                    <a href="<?= BASE_URL ?>login.php" class="lp-btn lp-btn-outline w-100 justify-content-center">Get Started</a>
                </div>
            </div>
            <div class="col-lg-4" data-aos="fade-up" data-aos-delay="200">
                <div class="bg-white p-5 rounded-4 border border-primary border-2 shadow-lg lp-pricing-featured position-relative">
                    <div class="position-absolute top-0 start-50 translate-middle badge rounded-pill bg-primary px-3 py-2">MOST POPULAR</div>
                    <h4>Professional</h4>
                    <div class="display-4 fw-bold my-4">₹2,499<span class="fs-6 text-muted fw-normal">/mo</span></div>
                    <ul class="list-unstyled d-flex flex-column gap-3 mb-5">
                        <li><i class="bi bi-check-lg text-primary me-2"></i> Unlimited Leads</li>
                        <li><i class="bi bi-check-lg text-primary me-2"></i> 10 Team Members</li>
                        <li><i class="bi bi-check-lg text-primary me-2"></i> WhatsApp Automation</li>
                        <li><i class="bi bi-check-lg text-primary me-2"></i> Auto-Assignment</li>
                    </ul>
                    <a href="<?= BASE_URL ?>login.php" class="lp-btn lp-btn-premium w-100 justify-content-center">Get Started Now</a>
                </div>
            </div>
            <div class="col-lg-4" data-aos="fade-up" data-aos-delay="300">
                <div class="bg-white p-5 rounded-4 border">
                    <h4>Enterprise</h4>
                    <div class="display-4 fw-bold my-4">Custom</div>
                    <ul class="list-unstyled d-flex flex-column gap-3 mb-5 text-muted">
                        <li><i class="bi bi-check-lg text-primary me-2"></i> Unlimited Everything</li>
                        <li><i class="bi bi-check-lg text-primary me-2"></i> Dedicated Support</li>
                        <li><i class="bi bi-check-lg text-primary me-2"></i> Custom Integrations</li>
                    </ul>
                    <a href="<?= BASE_URL ?>login.php" class="lp-btn lp-btn-outline w-100 justify-content-center">Contact Sales</a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Final CTA Section -->
<section class="lp-section">
    <div class="lp-container">
        <div class="bg-primary p-4 p-md-5 rounded-4 text-center text-white position-relative overflow-hidden shadow-2xl" data-aos="zoom-in" style="background: linear-gradient(135deg, var(--lp-primary) 0%, var(--lp-secondary) 100%) !important;">
            <div class="position-relative py-3" style="z-index: 2;">
                <h2 class="display-5 text-white fw-bold mb-3">Ready to transform your sales?</h2>
                <p class="fs-5 opacity-75 mb-5 mx-auto" style="max-width: 550px;">Join 500+ growing teams who use LeadFlow to automate their advertising success.</p>
                <div class="d-flex justify-content-center gap-3">
                    <a href="<?= BASE_URL ?>login.php" class="lp-btn bg-white text-primary fw-bold rounded-pill shadow-lg px-5 py-3">Start 14-Day Free Trial</a>
                </div>
            </div>
            
            <!-- Desktop decorative circles (Large Cut Edges) -->
            <div class="position-absolute bg-white rounded-circle pe-none d-none d-md-block" style="width: 300px; height: 300px; top: -150px; right: -150px;"></div>
            <div class="position-absolute bg-white rounded-circle pe-none d-none d-md-block" style="width: 200px; height: 200px; bottom: -100px; left: -100px;"></div>
            
            <!-- Mobile decorative circles (Smaller cut edges) -->
            <div class="position-absolute bg-white rounded-circle pe-none d-md-none" style="width: 120px; height: 120px; top: -60px; right: -60px;"></div>
            <div class="position-absolute bg-white rounded-circle pe-none d-md-none" style="width: 80px; height: 80px; bottom: -40px; left: -40px;"></div>
        </div>
    </div>
</section>

<?php include_once __DIR__ . '/includes/landing_footer.php'; ?>

</body>
</html>
