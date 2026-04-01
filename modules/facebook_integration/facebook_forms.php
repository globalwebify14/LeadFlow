<?php
require_once '../../config/auth.php';
requireLogin();
requireRole('org_owner');
require_once '../../config/db.php';

$orgId = getOrgId();

// Verify Pages Exist
$stmt = $pdo->prepare("SELECT * FROM facebook_pages WHERE organization_id = ?");
$stmt->execute([$orgId]);
$pages = $stmt->fetchAll();

if (empty($pages)) {
    redirect(BASE_URL . 'modules/facebook_integration/facebook_integration_settings.php', 'No pages found to sync forms from.', 'danger');
}

$totalForms = 0;
$totalLeads = 0;
$skippedLeads = 0;
$fallbackUsed = false;
$apiErrors = [];

$pdo->beginTransaction();
try {
    $stmtFormInsert = $pdo->prepare("INSERT IGNORE INTO facebook_forms (organization_id, page_id, form_id, form_name, created_at) VALUES (:org, :page, :form, :name, NOW())");

    foreach ($pages as $page) {
        $pageId = $page['page_id'];
        $pageToken = $page['page_access_token'];

        $activeFormsList = []; 

        // STEP 1: Proactively test the API call itself. No flawed /permissions lookup.
        $url = "https://graph.facebook.com/v19.0/{$pageId}/leadgen_forms?access_token=" . urlencode($pageToken);
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200) {
            $formsData = json_decode($response, true);
            if (!empty($formsData['data'])) {
                foreach ($formsData['data'] as $f) {
                    $stmtFormInsert->execute([
                        'org' => $orgId,
                        'page' => $pageId,
                        'form' => $f['id'],
                        'name' => $f['name']
                    ]);
                    $activeFormsList[] = ['form_id' => $f['id'], 'form_name' => $f['name']];
                }
            }
        } else {
            // Permission rejected or something else went wrong, fallback engaged!
            $fallbackUsed = true;
            $errData = json_decode($response, true);
            if (!empty($errData['error']['message'])) {
                $apiErrors[] = "Forms API: " . $errData['error']['message'];
            }
        }

        // STEP 2: Fallback mechanism - load forms from our Auto-Detection DB!
        if (empty($activeFormsList)) {
            $stmtGetKnown = $pdo->prepare("SELECT form_id, form_name FROM facebook_forms WHERE page_id = ?");
            $stmtGetKnown->execute([$pageId]);
            $activeFormsList = $stmtGetKnown->fetchAll(PDO::FETCH_ASSOC);

            // FORCE UI VISIBILITY: Map these found forms perfectly to your logged-in organization
            if (!empty($activeFormsList)) {
                $pdo->prepare("UPDATE facebook_forms SET organization_id = ? WHERE page_id = ? AND (organization_id IS NULL OR organization_id = 0)")->execute([$orgId, $pageId]);
            }
        }

        // Lead pulling from forms has been removed to preserve API Rate limits for Webhooks.
    } // end foreach (pages)
    
    $pdo->commit();
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    redirect(BASE_URL . 'modules/facebook_integration/facebook_integration_settings.php', 'Database error: ' . $e->getMessage(), 'danger');
}

if ($fallbackUsed) {
    $msg = "Forms scanned! Fallback Mode Used.";
    if (!empty($apiErrors)) $msg .= " | Errors: " . substr(implode(', ', $apiErrors), 0, 150);
    redirect(BASE_URL . 'modules/facebook_integration/facebook_integration_settings.php', $msg, (!empty($apiErrors) ? 'danger' : 'warning'));
} else {
    $msg = "Forms Synced Successfully! Auto-detected forms from Facebook.";
    if (!empty($apiErrors)) $msg .= " | Errors: " . substr(implode(', ', $apiErrors), 0, 150);
    redirect(BASE_URL . 'modules/facebook_integration/facebook_integration_settings.php', $msg, 'success');
}
exit;
?>
