<?php
require_once __DIR__ . '/config/auth.php';
?>
<?php 
$pageTitle = 'Data Deletion Instructions';
include_once __DIR__ . '/includes/landing_header.php'; 
?>

    <div class="legal-header">
        <div class="container">
            <h1>Data Deletion Instructions</h1>
            <p class="opacity-75">Following Meta Developer App Compliance</p>
        </div>
    </div>

    <div class="container mb-5">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="legal-content">
                    
                    <div class="legal-section">
                        <h2>1. Overview</h2>
                        <p>Lead CRM is a multi-tenant application that interfaces with Facebook Lead Ads utilizing the Meta Graph API. According to Meta Developer privacy protocols, you must have the ability to explicitly revoke authorization and remove your data from our systems.</p>
                        <p>If you no longer wish to use Lead CRM to sync your Facebook Leads, you can remove our application using the steps below.</p>
                    </div>

                    <div class="legal-section">
                        <h2>2. How to Remove the Facebook App Integration</h2>
                        <p>To disconnect your Facebook Page and revoke our access to your Form payload webhooks, follow these steps natively on Facebook:</p>
                        
                        <div class="step-box">
                            <strong>Step 1:</strong> Log into your Facebook account and navigate to <strong>Settings & Privacy</strong> &gt; <strong>Settings</strong>.
                        </div>
                        <div class="step-box">
                            <strong>Step 2:</strong> In the left-hand menu, click on <strong>Security and Login</strong>, then navigate to <strong>Business Integrations</strong>.
                        </div>
                        <div class="step-box">
                            <strong>Step 3:</strong> Locate the <strong>Lead CRM Integration App</strong> from the list of active integrations.
                        </div>
                        <div class="step-box">
                            <strong>Step 4:</strong> Click <strong>Remove</strong>. Check the boxes to delete any previous posts if desired, then click <strong>Remove</strong> again to confirm. 
                        </div>
                        
                        <p class="mt-4"><span class="badge bg-warning text-dark me-2">Note:</span> Once the integration is removed via Facebook, the global webhook endpoint will immediately stop accepting payloads for your <code>page_id</code>.</p>
                    </div>

                    <div class="legal-section">
                        <h2>3. Complete Organization Database Deletion</h2>
                        <p>If you wish to permanently erase all leads, users, deals, and configurations associated with your Organization from Lead CRM's servers:</p>
                        <ul>
                            <li>Log into the Lead CRM Portal as the <strong>Organization Owner</strong>.</li>
                            <li>Navigate to <strong>Settings</strong> > <strong>Organization</strong>.</li>
                            <li>Click the red <strong>Delete Organization</strong> button at the bottom of the page.</li>
                            <li>A prompt will require you to type your organization's name to confirm. Once confirmed, all databases linked to your <code>organization_id</code> are immediately decoupled and erased.</li>
                        </ul>
                    </div>

                    <div class="legal-section">
                        <h2>4. Contact Support</h2>
                        <p>If you require manual assistance with data removal requests, please email our security team directly at <strong>privacy@leadcrm.com</strong>. We will process manual deletion requests within 48 business hours.</p>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <?php include_once __DIR__ . '/includes/landing_footer.php'; ?>
