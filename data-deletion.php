<?php
require_once __DIR__ . '/config/auth.php';
?>
<?php 
$pageTitle = 'Data Deletion Instructions';
include_once __DIR__ . '/includes/landing_header.php'; 
?>

    <section class="lp-legal-header">
        <div class="lp-container">
            <h1 class="display-4">Data Deletion Instructions</h1>
            <p class="text-muted">Following Meta Developer App Compliance</p>
        </div>
    </section>

    <section class="lp-container">
        <div class="lp-legal-content">
            <div class="mb-5">
                <h2>1. Overview</h2>
                <p>LeadFlow is a multi-tenant application that interfaces with Facebook Lead Ads utilizing the Meta Graph API. According to Meta Developer privacy protocols, you must have the ability to explicitly revoke authorization and remove your data from our systems.</p>
                <p>If you no longer wish to use LeadFlow to sync your Facebook Leads, you can remove our application using the steps below.</p>
            </div>

            <div class="mb-5">
                <h2>2. How to Remove the Facebook App Integration</h2>
                <p>To disconnect your Facebook Page and revoke our access to your Form payload webhooks, follow these steps natively on Facebook:</p>
                
                <div class="lp-step-box">
                    <strong>Step 1:</strong> Log into your Facebook account and navigate to <strong>Settings &amp; Privacy</strong> &gt; <strong>Settings</strong>.
                </div>
                <div class="lp-step-box">
                    <strong>Step 2:</strong> In the left-hand menu, click on <strong>Security and Login</strong>, then navigate to <strong>Business Integrations</strong>.
                </div>
                <div class="lp-step-box">
                    <strong>Step 3:</strong> Locate the <strong>LeadFlow Integration App</strong> from the list of active integrations.
                </div>
                <div class="lp-step-box">
                    <strong>Step 4:</strong> Click <strong>Remove</strong>. Check the boxes to delete any previous posts if desired, then click <strong>Remove</strong> again to confirm. 
                </div>
                
                <div class="alert alert-info border-0 rounded-4 mt-4">
                    <i class="bi bi-info-circle-fill me-2"></i> Once the integration is removed via Facebook, the global webhook endpoint will immediately stop accepting payloads for your <code>page_id</code>.
                </div>
            </div>

            <div class="mb-5">
                <h2>3. Complete Organization Database Deletion</h2>
                <p>If you wish to permanently erase all leads, users, deals, and configurations associated with your Organization from LeadFlow's servers:</p>
                <ul>
                    <li>Log into the LeadFlow Portal as the <strong>Organization Owner</strong>.</li>
                    <li>Navigate to <strong>Settings</strong> &gt; <strong>Organization</strong>.</li>
                    <li>Click the red <strong>Delete Organization</strong> button at the bottom of the page.</li>
                    <li>A prompt will require you to type your organization's name to confirm. Once confirmed, all databases linked to your <code>organization_id</code> are immediately decoupled and erased.</li>
                </ul>
            </div>

            <div class="mb-5">
                <h2>4. Contact Support</h2>
                <p>If you require manual assistance with data removal requests, please email our security team directly at <strong>privacy@leadflow.com</strong>. We will process manual deletion requests within 48 business hours.</p>
            </div>
        </div>
    </section>

    <?php include_once __DIR__ . '/includes/landing_footer.php'; ?>
