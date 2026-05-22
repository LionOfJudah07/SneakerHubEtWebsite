<?php
echo "<h1>Checking Product Class</h1>";

// Include necessary files
require_once __DIR__ . '/includes/config.php';

// Check if Product class file exists
$product_file = __DIR__ . '/classes/Product.php';
if (file_exists($product_file)) {
    echo "✅ Product.php file exists<br>";
    
    // Read the file
    $content = file_get_contents($product_file);
    echo "<h3>Product Class Methods:</h3>";
    
    // Extract method names
    preg_match_all('/public\s+function\s+(\w+)/', $content, $matches);
    
    if (!empty($matches[1])) {
        echo "<ul>";
        foreach ($matches[1] as $method) {
            echo "<li>$method()</li>";
        }
        echo "</ul>";
    } else {
        echo "No public methods found in Product class<br>";
        echo "<pre>" . htmlspecialchars($content) . "</pre>";
    }
} else {
    echo "❌ Product.php file not found<br>";
}

// Check public/index.php
$index_file = __DIR__ . '/public/index.php';
if (file_exists($index_file)) {
    echo "<h3>Checking public/index.php:</h3>";
    $index_content = file_get_contents($index_file);
    
    // Look for Product method calls
    if (preg_match('/\$product->(\w+)\(/', $index_content, $matches)) {
        echo "Found method call: <strong>\$product->" . $matches[1] . "()</strong><br>";
        
        // Show line 8
        $lines = explode("\n", $index_content);
        if (isset($lines[7])) { // Line 8 is index 7 (0-based)
            echo "Line 8: <code>" . htmlspecialchars(trim($lines[7])) . "</code><br>";
        }
    }
    
    echo "<h4>Full public/index.php content:</h4>";
    echo "<pre style='background: #f5f5f5; padding: 10px;'>" . htmlspecialchars($index_content) . "</pre>";
}

echo "<hr>";
echo "<a href='fix-index.php'>Fix Index File</a> | ";
echo "<a href='create-product-class.php'>Create Product Class</a>";
?>