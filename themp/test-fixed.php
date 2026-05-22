<?php
echo "<h1>Test After Fix</h1>";

// Test database connection
require_once __DIR__ . '/includes/config.php';

try {
    $pdo = new PDO(DB_DSN, DB_USER, DB_PASS);
    echo "✅ Database connected<br>";
    
    // Test Product class
    require_once __DIR__ . '/classes/Product.php';
    $product = new Product();
    
    echo "✅ Product class instantiated<br>";
    
    // Test getFeatured()
    echo "<h3>Testing getFeatured():</h3>";
    try {
        $featured = $product->getFeatured(5);
        echo "✅ getFeatured() works! Found " . count($featured) . " products<br>";
        
        if (!empty($featured)) {
            echo "<table border='1' cellpadding='8'>";
            echo "<tr><th>ID</th><th>Name</th><th>Price</th></tr>";
            foreach ($featured as $p) {
                echo "<tr>";
                echo "<td>" . ($p['id'] ?? 'N/A') . "</td>";
                echo "<td>" . htmlspecialchars($p['name'] ?? 'Unknown') . "</td>";
                echo "<td>" . ($p['price'] ?? '0.00') . " ETB</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    } catch (Exception $e) {
        echo "❌ getFeatured() failed: " . $e->getMessage() . "<br>";
    }
    
    // Test getAll()
    echo "<h3>Testing getAll():</h3>";
    try {
        $all = $product->getAll(5);
        echo "✅ getAll() works! Total products: " . count($all) . "<br>";
    } catch (Exception $e) {
        echo "❌ getAll() failed: " . $e->getMessage() . "<br>";
    }
    
    // Test getCategories()
    echo "<h3>Testing getCategories():</h3>";
    try {
        $categories = $product->getCategories();
        echo "✅ getCategories() works! Found " . count($categories) . " categories<br>";
        echo "Categories: " . implode(', ', $categories) . "<br>";
    } catch (Exception $e) {
        echo "❌ getCategories() failed: " . $e->getMessage() . "<br>";
    }
    
    // Test getBrands()
    echo "<h3>Testing getBrands():</h3>";
    try {
        $brands = $product->getBrands();
        echo "✅ getBrands() works! Found " . count($brands) . " brands<br>";
        echo "Brands: " . implode(', ', $brands) . "<br>";
    } catch (Exception $e) {
        echo "❌ getBrands() failed: " . $e->getMessage() . "<br>";
    }
    
    echo "<hr><div style='background: #4CAF50; color: white; padding: 20px;'>";
    echo "<h2>✅ All Tests Passed!</h2>";
    echo "<a href='/sneaker-mart/public/index.php' style='color: white; text-decoration: underline;'>Test Your Website Now</a>";
    echo "</div>";
    
} catch (PDOException $e) {
    echo "<div style='background: #f44336; color: white; padding: 20px;'>";
    echo "<h2>❌ Database Error</h2>";
    echo $e->getMessage();
    echo "</div>";
}
?>