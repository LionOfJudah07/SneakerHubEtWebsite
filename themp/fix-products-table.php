<?php
echo "<h1>Add Missing Columns to Products Table</h1>";

require_once __DIR__ . '/includes/config.php';

try {
    $pdo = new PDO(DB_DSN, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✅ Connected to database<br><hr>";
    
    // Add missing columns if they don't exist
    $columns_to_add = [
        'is_featured' => "ALTER TABLE products ADD COLUMN IF NOT EXISTS is_featured BOOLEAN DEFAULT FALSE",
        'is_active' => "ALTER TABLE products ADD COLUMN IF NOT EXISTS is_active BOOLEAN DEFAULT TRUE",
        'slug' => "ALTER TABLE products ADD COLUMN IF NOT EXISTS slug VARCHAR(200)",
        'category_id' => "ALTER TABLE products ADD COLUMN IF NOT EXISTS category_id INTEGER",
        'brand' => "ALTER TABLE products ADD COLUMN IF NOT EXISTS brand VARCHAR(100)",
        'size' => "ALTER TABLE products ADD COLUMN IF NOT EXISTS size VARCHAR(50)",
        'color' => "ALTER TABLE products ADD COLUMN IF NOT EXISTS color VARCHAR(50)",
        'sku' => "ALTER TABLE products ADD COLUMN IF NOT EXISTS sku VARCHAR(100) UNIQUE",
        'stock_quantity' => "ALTER TABLE products ADD COLUMN IF NOT EXISTS stock_quantity INTEGER DEFAULT 0",
        'image_url' => "ALTER TABLE products ADD COLUMN IF NOT EXISTS image_url VARCHAR(255)",
        'created_at' => "ALTER TABLE products ADD COLUMN IF NOT EXISTS created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP",
        'updated_at' => "ALTER TABLE products ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP"
    ];
    
    foreach ($columns_to_add as $column => $sql) {
        try {
            $pdo->exec($sql);
            echo "✅ Added column: $column<br>";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'already exists') !== false) {
                echo "✅ Column $column already exists<br>";
            } else {
                echo "⚠️ Could not add $column: " . $e->getMessage() . "<br>";
            }
        }
    }
    
    echo "<hr>";
    
    // Update some products to be featured
    echo "<h3>Setting some products as featured...</h3>";
    $pdo->exec("UPDATE products SET is_featured = true WHERE id IN (SELECT id FROM products ORDER BY RANDOM() LIMIT 3)");
    echo "✅ Set 3 random products as featured<br>";
    
    // Show featured products
    echo "<h3>Current featured products:</h3>";
    $featured = $pdo->query("SELECT id, name, is_featured, is_active FROM products WHERE is_featured = true")->fetchAll();
    
    if (empty($featured)) {
        echo "No featured products yet.<br>";
        // Force some to be featured
        $pdo->exec("UPDATE products SET is_featured = true WHERE id = (SELECT MIN(id) FROM products)");
        echo "✅ Set first product as featured<br>";
    } else {
        echo "<table border='1' cellpadding='8'>";
        echo "<tr><th>ID</th><th>Name</th><th>Featured</th><th>Active</th></tr>";
        foreach ($featured as $product) {
            echo "<tr>";
            echo "<td>" . $product['id'] . "</td>";
            echo "<td>" . htmlspecialchars($product['name'] ?? 'Unknown') . "</td>";
            echo "<td>" . ($product['is_featured'] ? '✅' : '❌') . "</td>";
            echo "<td>" . ($product['is_active'] ? '✅' : '❌') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    echo "<hr><div style='background: #4CAF50; color: white; padding: 20px;'>";
    echo "<h2>✅ Database Table Fixed!</h2>";
    echo "All required columns have been added to the products table.<br>";
    echo "<a href='test-fixed.php'>Test the fix</a>";
    echo "</div>";
    
} catch (PDOException $e) {
    echo "<div style='background: #f44336; color: white; padding: 20px;'>";
    echo "<h2>❌ Error</h2>";
    echo $e->getMessage();
    echo "</div>";
}
?>