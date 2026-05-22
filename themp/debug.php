<?php
echo "<h1>Debug Info</h1>";

// Check current directory
echo "<p>Current dir: " . __DIR__ . "</p>";

// Check if includes/config.php exists
$config_path = __DIR__ . '/includes/config.php';
echo "<p>Config path: $config_path</p>";
echo "<p>Config exists: " . (file_exists($config_path) ? 'YES' : 'NO') . "</p>";

// Check classes directory
$classes_dir = __DIR__ . '/classes';
echo "<p>Classes dir: $classes_dir</p>";
echo "<p>Classes exists: " . (is_dir($classes_dir) ? 'YES' : 'NO') . "</p>";

// List files in classes
if (is_dir($classes_dir)) {
    $files = scandir($classes_dir);
    echo "<p>Files in classes:</p><ul>";
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..') {
            echo "<li>$file</li>";
        }
    }
    echo "</ul>";
}

// Try to include config
echo "<h2>Testing config inclusion...</h2>";
if (file_exists($config_path)) {
    require_once $config_path;
    echo "<p style='color: green;'>✓ Config loaded successfully</p>";
    
    // Test Database
    if (class_exists('Database')) {
        echo "<p style='color: green;'>✓ Database class exists</p>";
        try {
            $db = new Database();
            echo "<p style='color: green;'>✓ Database connected</p>";
        } catch (Exception $e) {
            echo "<p style='color: red;'>✗ Database error: " . $e->getMessage() . "</p>";
        }
    } else {
        echo "<p style='color: red;'>✗ Database class not found</p>";
    }
} else {
    echo "<p style='color: red;'>✗ Config file not found</p>";
}