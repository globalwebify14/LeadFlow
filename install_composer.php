<?php
/**
 * 1-Click Excel Packages Installer
 * Just visit this page in your browser!
 */

echo "<div style='font-family: sans-serif; max-width: 800px; margin: 40px auto; padding: 20px; border: 1px solid #ddd; border-radius: 10px;'>";
echo "<h2 style='color: #4f46e5;'>🚀 Installing Excel Modules...</h2>";

// 1. Download portable composer securely if it doesn't exist
if (!file_exists('composer.phar')) {
    echo "<p>Downloading portable Composer...</p>";
    file_put_contents('composer.phar', file_get_contents('https://getcomposer.org/composer.phar'));
}

// 2. Set memory limit super high for installation
ini_set('memory_limit', '2G');
set_time_limit(300);

echo "<p>Installing PhpSpreadsheet (this usually takes 10 to 30 seconds)...</p>";

// 3. Setup temporary home environment for Composer to bypass Hostinger restrictions
$composerHome = __DIR__ . '/.composer';
if (!is_dir($composerHome)) mkdir($composerHome, 0777, true);
putenv('COMPOSER_HOME=' . $composerHome);

// 4. Execute the composer installation command securely
$output = shell_exec('php composer.phar require phpoffice/phpspreadsheet 2>&1');

echo "<div style='background: #1e1e1e; color: #0f0; padding: 15px; border-radius: 5px; font-family: monospace; overflow-x: auto;'>";
echo nl2br(htmlspecialchars($output));
echo "</div>";

// 4. Verify Success
if (file_exists('vendor/autoload.php')) {
    echo "<h3 style='color: #10b981;'>✅ Installation 100% Successful!</h3>";
    echo "<p>The <b>vendor</b> folder was successfully created on the server! You can now freely use the incredible Excel Import tool!</p>";
    echo "<p style='color: red; font-size: 12px;'>IMPORTANT: For security, please delete this 'install_composer.php' file when you are totally done!</p>";
} else {
    echo "<h3 style='color: #ef4444;'>❌ Installation failed!</h3>";
    echo "<p>Please ensure your server allows `shell_exec()`.</p>";
}

echo "</div>";
?>
