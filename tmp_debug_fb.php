<?php
require_once 'config/db.php';

echo "=== FORM AND PAGE STATUS ===\n";
$stmt = $pdo->query("SELECT f.organization_id, f.form_id, f.form_name, p.page_name, p.page_access_token FROM facebook_forms f JOIN facebook_pages p ON f.page_id = p.page_id LIMIT 5");
$forms = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$forms) {
    echo "NO FORMS FOUND!\n";
    exit;
}

echo "Found " . count($forms) . " forms in DB. Analyzing the first one...\n";
$form = $forms[0];
echo "Form Name: {$form['form_name']} ({$form['form_id']})\n";

$leadsUrl = "https://graph.facebook.com/v19.0/{$form['form_id']}/leads?access_token=" . urlencode($form['page_access_token']) . "&limit=5";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $leadsUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "\n=== API RESPONSE FROM META FOR LEADS ===\n";
echo "HTTP Status: {$httpCode}\n";
echo "Response: " . substr($response, 0, 1000) . "\n\n";

echo "=== CRM LEADS TABLE ===\n";
$stmt2 = $pdo->query("SELECT COUNT(*) FROM leads");
echo "Total leads in CRM: " . $stmt2->fetchColumn() . "\n";

$stmt3 = $pdo->query("SELECT COUNT(*) FROM facebook_leads");
echo "Total raw leads in facebook_leads table: " . $stmt3->fetchColumn() . "\n";
?>
