<?php
echo "<h1>Checking Database Structure</h1>";

require_once __DIR__ . '/includes/config.php';

try {
    $pdo = new PDO(DB_DSN, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✅ Connected to database: " . DB_NAME . "<br><hr>";
    
    // Check if products table exists
    $stmt = $pdo->query("SELECT EXISTS (SELECT FROM information_schema.tables WHERE table_schema = 'public' AND table_name = 'products')");
    $table_exists = $stmt->fetchColumn();
    
    if (!$table_exists) {
        die("❌ Products table doesn't exist! <a href='create-products-table.php'>Create it first</a>");
    }
    
    echo "✅ Products table exists<br>";
    
    // Show all columns in products table
    echo "<h3>Columns in products table:</h3>";
    $columns = $pdo->query("
        SELECT column_name, data_type, is_nullable, column_default
        FROM information_schema.columns 
        WHERE table_schema = 'public' 
        AND table_name = 'products'
        ORDER BY ordinal_position
    ")->fetchAll();
    
    echo "<table border='1' cellpadding='8' style='border-collapse: collapse;'>";
    echo "<tr><th>Column</th><th>Type</th><th>Nullable</th><th>Default</th></tr>";
    
    $has_is_featured = false;
    $has_is_active = false;
    
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td><strong>" . $col['column_name'] . "</strong></td>";
        echo "<td>" . $col['data_type'] . "</td>";
        echo "<td>" . $col['is_nullable'] . "</td>";
        echo "<td>" . ($col['column_default'] ?: 'NULL') . "</td>";
        echo "</tr>";
        
        if ($col['column_name'] == 'is_featured') $has_is_featured = true;
        if ($col['column_name'] == 'is_active') $has_is_active = true;
    }
    
    echo "</table>";
    
    echo "<hr>";
    
    if (!$has_is_featured) {
        echo "<div style='background: #FF9800; color: white; padding: 15px;'>";
        echo "⚠️ <strong>Missing column:</strong> is_featured<br>";
        echo "</div>";
    }
    
    if (!$has_is_active) {
        echo "<div style='background: #FF9800; color: white; padding: 15px;'>";
        echo "⚠️ <strong>Missing column:</strong> is_active<br>";
        echo "</div>";
    }
    
    // Show sample data
    echo "<h3>Sample data in products table:</h3>";
    $products = $pdo->query("SELECT * FROM products LIMIT 5")->fetchAll();
    
    if (empty($products)) {
        echo "No products in the table. <a href='add-sample-products.php'>Add sample products</a>";
    } else {
        echo "<table border='1' cellpadding='8'>";
        echo "<tr>";
        foreach (array_keys($products[0]) as $col) {
            echo "<th>" . $col . "</th>";
        }
        echo "</tr>";
        
        foreach ($products as $product) {
            echo "<tr>";
            foreach ($product as $value) {
                echo "<td>" . htmlspecialchars(substr($value, 0, 50)) . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    }
    
} catch (PDOException $e) {
    echo "<div style='background: #f44336; color: white; padding: 20px;'>";
    echo "<h2>❌ Database Error</h2>";
    echo $e->getMessage();
    echo "</div>";
}

echo "<hr>";
echo "<a href='fix-products-table.php'>Fix Products Table</a> | ";
echo "<a href='update-product-class.php'>Update Product Class</a>";
?>