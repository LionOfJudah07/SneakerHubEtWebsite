<?php
echo "<h1>Checking Your Website Structure</h1>";
echo "Current directory: " . __DIR__ . "<br><hr>";

// List all files and folders
echo "<h3>Files and Folders in your website:</h3>";
$files = scandir(__DIR__);
echo "<ul>";
foreach ($files as $file) {
    if ($file != '.' && $file != '..') {
        $type = is_dir($file) ? "📁 [FOLDER]" : "📄 [FILE]";
        echo "<li>$type $file</li>";
    }
}
echo "</ul>";

echo "<hr><h3>Checking includes folder:</h3>";
if (is_dir(__DIR__ . '/includes')) {
    $includes_files = scandir(__DIR__ . '/includes');
    echo "<ul>";
    foreach ($includes_files as $file) {
        if ($file != '.' && $file != '..') {
            $path = __DIR__ . '/includes/' . $file;
            $type = is_dir($path) ? "📁 [FOLDER]" : "📄 [FILE]";
            echo "<li>$type $file</li>";
        }
    }
    echo "</ul>";
} else {
    echo "❌ includes folder not found!";
}

echo "<hr>";
echo "<a href='test-simple.php'>Test Simple Connection</a>";
?>