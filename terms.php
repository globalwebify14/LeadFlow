<?php
require_once __DIR__ . '/config/auth.php';
?>
<?php 
$pageTitle = 'Terms of Service';
include_once __DIR__ . '/includes/landing_header.php'; 
?>

    <section class="lp-legal-header">
        <div class="lp-container">
            <h1 class="display-4">Terms of Service</h1>
            <p class="text-muted">Last Updated: <?= date('F j, Y') ?></p>
        </div>
    </section>

    <section class="lp-container">
        <div class="lp-legal-content">
            <div class="mb-5">
                <h2>1. Acceptance of Terms</h2>
                <p>By accessing or using the LeadFlow Software-as-a-Service (the "Service"), you agree to be bound by these Terms of Service. If you disagree with any part of these terms, you may not access the Service.</p>
            </div>

            <div class="mb-5">
                <h2>2. Enterprise Subscriptions</h2>
                <p>LeadFlow operates on a subscription structure. A Super Admin provisions organizations and issues licenses for user and lead tier limits. The Organization Owner is responsible for maintaining adequate subscription plans for their respective teams.</p>
                <ul>
                    <li>The maximum number of users and leads sync limits are dictated by the Plan purchased.</li>
                    <li>If your subscription is set to <strong>Suspended</strong> or <strong>Inactive</strong>, your Facebook Webhooks and login access will be temporarily halted.</li>
                </ul>
            </div>

            <div class="mb-5">
                <h2>3. Use of Facebook Integrations</h2>
                <p>Our platform provides direct integration with Facebook Lead Ads utilizing the Meta Graph API. By authorizing LeadFlow to access your Facebook Page, you represent and warrant that:</p>
                <ul>
                    <li>You are an authorized administrator of the Facebook Page you select.</li>
                    <li>You will comply with Facebook's <a href="https://developers.facebook.com/terms/" target="_blank">Platform Terms</a> and Data Use Policies regarding lead generation.</li>
                    <li>You are solely responsible for obtaining all necessary consents and adding appropriate privacy policy disclosures to your Lead Gen Forms on Facebook.</li>
                </ul>
                <p>LeadFlow strictly acts as a data processor. We merely transport the leads you generate automatically over webhooks securely into your private CRM workspace.</p>
            </div>

            <div class="mb-5">
                <h2>4. User Content &amp; Rules of Conduct</h2>
                <p>You agree not to use the Service to:</p>
                <ul>
                    <li>Upload, transmit, or distribute any data that contains viruses, malware, or malicious code.</li>
                    <li>Attempt to breach the multi-tenant architecture to access data belonging to another Organization.</li>
                    <li>Interfere with or disrupt the global webhook endpoint operations.</li>
                </ul>
            </div>

            <div class="mb-5">
                <h2>5. Termination</h2>
                <p>We may terminate or suspend your account and bar access to the Service immediately, without prior notice or liability, under our sole discretion, for any reason whatsoever and without limitation, including but not limited to a breach of the Terms.</p>
            </div>

            <div class="mb-5">
                <h2>6. Governing Law</h2>
                <p>These terms and conditions are governed by and construed in accordance with standard global software jurisdiction laws. Questions about the Terms of Service should be sent to us at <strong>legal@leadflow.com</strong>.</p>
            </div>
        </div>
    </section>

    <?php include_once __DIR__ . '/includes/landing_footer.php'; ?>
