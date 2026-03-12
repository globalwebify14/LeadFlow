<?php
$modulesDir = __DIR__ . '/modules';

$models = [
    'User' => 'User.php',
    'Lead' => 'Lead.php',
    'Deal' => 'Deal.php',
    'Dashboard' => 'Dashboard.php',
    'Followup' => 'Followup.php',
    'Report' => 'Report.php',
    'AssignmentRule' => 'AssignmentRule.php',
    'MetaIntegration' => 'MetaIntegration.php',
];

function processDir($dir, $models) {
    $files = glob($dir . '/*');
    foreach ($files as $file) {
        if (is_dir($file)) {
            processDir($file, $models);
        } elseif (pathinfo($file, PATHINFO_EXTENSION) === 'php') {
            $content = file_get_contents($file);
            $changed = false;
            
            $requiresToAdd = [];
            foreach ($models as $className => $fileName) {
                // If file instantiates the class (e.g., new Lead) or calls static methods, but doesn't require it
                if (preg_match("/new\s+{$className}/", $content) || preg_match("/{$className}::/", $content)) {
                    // Check if it's already required
                    if (!preg_match("/require_once.*['\"]([^'\"]*)models\/{$fileName}['\"]/", $content)) {
                        $requiresToAdd[] = "require_once '../../models/{$fileName}';";
                    }
                }
            }
            
            if (!empty($requiresToAdd)) {
                // Insert after require_once '../../config/db.php';
                $insertStr = "\n" . implode("\n", $requiresToAdd) . "\n";
                // If it has db.php, insert after
                if (strpos($content, "require_once '../../config/db.php';") !== false) {
                    $content = str_replace("require_once '../../config/db.php';", "require_once '../../config/db.php';" . $insertStr, $content);
                    $changed = true;
                } else if (strpos($content, "require_once '../../config/auth.php';") !== false) {
                     $content = str_replace("require_once '../../config/auth.php';", "require_once '../../config/auth.php';" . $insertStr, $content);
                     $changed = true;
                }
            }
            
            if ($changed) {
                file_put_contents($file, $content);
                echo "Fixed requires in: $file\n";
            }
        }
    }
}

processDir($modulesDir, $models);
echo "Done.\n";
