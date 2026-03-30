<?php
require_once __DIR__ . '/config/db.php';

echo "<h1>Applying Live Database Updates</h1>";

// 1. Fix Deals Status
try {
    $sql1 = "UPDATE deals d JOIN pipeline_stages ps ON d.stage_id = ps.id SET d.status = 'won' WHERE ps.is_won = 1 AND d.status != 'won'";
    $stmt1 = $pdo->prepare($sql1);
    $stmt1->execute();
    echo "<p>Updated " . $stmt1->rowCount() . " Won Deals successfully.</p>";

    $sql2 = "UPDATE deals d JOIN pipeline_stages ps ON d.stage_id = ps.id SET d.status = 'lost' WHERE ps.is_lost = 1 AND d.status != 'lost'";
    $stmt2 = $pdo->prepare($sql2);
    $stmt2->execute();
    echo "<p>Updated " . $stmt2->rowCount() . " Lost Deals successfully.</p>";
} catch (Exception $e) {
    echo "<p>Error updating deals: " . $e->getMessage() . "</p>";
}

// 2. Add Database Indexes (Performance Boost)
$indexes = [
    // Leads table
    "ALTER TABLE leads ADD INDEX idx_org_id (organization_id)",
    "ALTER TABLE leads ADD INDEX idx_assigned_to (assigned_to)",
    "ALTER TABLE leads ADD INDEX idx_status (status)",
    "ALTER TABLE leads ADD INDEX idx_pipeline (pipeline_stage_id)",
    "ALTER TABLE leads ADD INDEX idx_created_at (created_at)",
    // Composite indexes for fast filtering
    "ALTER TABLE leads ADD INDEX idx_org_assigned (organization_id, assigned_to)",
    "ALTER TABLE leads ADD INDEX idx_org_status (organization_id, status)",
    
    // Deals table
    "ALTER TABLE deals ADD INDEX idx_deal_org (organization_id)",
    "ALTER TABLE deals ADD INDEX idx_deal_assigned (assigned_to)",
    "ALTER TABLE deals ADD INDEX idx_deal_stage (stage_id)",
    "ALTER TABLE deals ADD INDEX idx_deal_status (status)",
    
    // Followups table
    "ALTER TABLE followups ADD INDEX idx_fup_org (organization_id)",
    "ALTER TABLE followups ADD INDEX idx_fup_user (user_id)",
    "ALTER TABLE followups ADD INDEX idx_fup_date_status (followup_date, status)",
    
    // Lead Activities
    "ALTER TABLE lead_activities ADD INDEX idx_act_lead (lead_id)",
    "ALTER TABLE lead_activities ADD INDEX idx_act_user (user_id)"
];

echo "<h3>Applying Speed Optimization Indexes</h3><ul>";
foreach ($indexes as $sql) {
    try {
        $pdo->exec($sql);
        echo "<li>Successfully applied: $sql</li>";
    } catch (PDOException $e) {
        // Ignore "Duplicate key name" errors, meaning it was already optimized
        if ($e->getCode() == '42000' && strpos($e->getMessage(), 'Duplicate key name') !== false) {
            echo "<li style='color:gray;'>Index already exists: $sql</li>";
        } else {
            echo "<li style='color:red;'>Error executing $sql: " . $e->getMessage() . "</li>";
        }
    }
}
echo "</ul>";
echo "<h2>All live database updates complete! You can safely delete this file.</h2>";
?>
