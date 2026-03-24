<?php
require_once '../../config/auth.php';
requireLogin();
requireRole('org_owner');

// With the new webhook auto-discovery system, we no longer need to manually fetch forms
// via API (which requires the restricted pages_manage_ads permission).
// Instead, forms are auto-populated gracefully upon their first lead arriving.

redirect(BASE_URL . 'modules/facebook_integration/facebook_integration_settings.php', 'Facebook successfully connected! Custom Forms will automatically sync the moment a new lead arrives via Webhook.', 'success');
exit;
?>
