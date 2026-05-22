<?php
echo "<h1>PHP Extensions Check</h1>";
echo "Checking if PostgreSQL extensions are loaded...<br><hr>";

// List all loaded extensions
$all_extensions = get_loaded_extensions();
echo "<h3>All Loaded Extensions:</h3>";
echo "<div style='column-count: 3;'>";
foreach ($all_extensions as $ext) {
    echo "• $ext<br>";
}
echo "</div>";

echo "<hr><h3>Checking PostgreSQL extensions:</h3>";

// Specific PostgreSQL checks
$pgsql_exts = ['pdo_pgsql', 'pgsql', 'pdo'];

foreach ($pgsql_exts as $ext) {
    if (extension_loaded($ext)) {
        echo "✅ <strong>$ext</strong> is LOADED<br>";
    } else {
        echo "❌ <strong>$ext</strong> is NOT LOADED<br>";
    }
}

echo "<hr><h3>How to Enable PostgreSQL in XAMPP:</h3>";
echo "1. Open XAMPP Control Panel<br>";
echo "2. Click Apache 'Config' button → Select 'PHP (php.ini)'<br>";
echo "3. Find these lines (around line 900-950):<br>";
echo "   <code>;extension=pdo_pgsql</code><br>";
echo "   <code>;extension=pgsql</code><br>";
echo "4. Remove the semicolons:<br>";
echo "   <code>extension=pdo_pgsql</code><br>";
echo "   <code>extension=pgsql</code><br>";
echo "5. Save the file<br>";
echo "6. Restart Apache in XAMPP Control Panel<br>";

echo "<hr>";
echo "<a href='test-simple.php'>← Back to Connection Test</a>";
?>