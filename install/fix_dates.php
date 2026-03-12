<?php
require_once __DIR__ . '/config/db.php';

echo "<h1>Syncing Meta Timestamps to Database...</h1>";
echo "<pre>";

try {
    // 1. Fetch all leads that have the "Submitted:" timestamp in their notes
    $stmt = $pdo->query("SELECT id, name, note, created_at FROM leads WHERE note LIKE '%Submitted:%'");
    $leads = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Found " . count($leads) . " leads with 'Submitted:' timestamp in notes.\n\n";
    
    $success = 0;
    $failed = 0;
    $skipped = 0;

    foreach ($leads as $r) {
        echo "Processing Lead ID: {$r['id']} ({$r['name']})...\n";
        
        // Use a very reliable regex for Meta's ISO format
        if (preg_match('/Submitted:\s*([^\n\r]+)/', $r['note'], $m)) {
            $rawTime = trim($m[1]);
            $ts = strtotime($rawTime);
            
            if ($ts) {
                $mysqlDate = date('Y-m-d H:i:s', $ts);
                
                // Only update if it's actually different (to save resources)
                if ($r['created_at'] !== $mysqlDate) {
                    $upd = $pdo->prepare("UPDATE leads SET created_at = ? WHERE id = ?");
                    if ($upd->execute([$mysqlDate, $r['id']])) {
                        echo "  [SUCCESS] Updated to: {$mysqlDate}\n";
                        $success++;
                    } else {
                        echo "  [ERROR] Database Update Failed for ID {$r['id']}\n";
                        $failed++;
                    }
                } else {
                    echo "  [SKIPPED] Already has the correct timestamp: {$mysqlDate}\n";
                    $skipped++;
                }
            } else {
                echo "  [ERROR] Could not parse date string: '{$rawTime}'\n";
                $failed++;
            }
        } else {
            echo "  [ERROR] 'Submitted:' line found but regex failed to extract time.\n";
            $failed++;
        }
        echo "--------------------------------------------------\n";
    }

    echo "\n\nSummary:\n";
    echo "- Successfully Updated: {$success}\n";
    echo "- Already Correct: {$skipped}\n";
    echo "- Failed/Errors: {$failed}\n";
    echo "</pre>";
    echo "<h3>If 'Successfully Updated' is more than 0, your sorting is now FIXED. Please check the Manage Leads page!</h3>";

} catch (Exception $e) {
    echo "</pre>";
    echo "<h2 style='color:red;'>FATAL ERROR: " . $e->getMessage() . "</h2>";
}
