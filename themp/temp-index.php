<?php
require_once '../config.php';

// Simple Product class that always works
class TempProduct {
    public function getFeatured($limit = 8) {
        return [
            ['id' => 1, 'name' => 'Sample Nike Shoes', 'price' => 4500, 'brand' => 'Nike'],
            ['id' => 2, 'name' => 'Sample Adidas Shoes', 'price' => 5200, 'brand' => 'Adidas']
        ];
    }
    
    public function getAll($limit = 8) {
        return $this->getFeatured($limit);
    }
    
    public function getCategories() {
        return ['Running', 'Basketball', 'Casual'];
    }
    
    public function getBrands() {
        return ['Nike', 'Adidas', 'Puma'];
    }
}

$product = new TempProduct();
$featured_products = $product->getFeatured(8);
$new_arrivals = $product->getAll(8);
$categories = $product->getCategories();
$brands = $product->getBrands();

// Simple HTML to test
?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Page - <?php echo SITE_NAME; ?></title>
    <style>
        body { font-family: Arial; padding: 20px; }
        .product { border: 1px solid #ccc; padding: 10px; margin: 10px; }
    </style>
</head>
<body>
    <h1>Test Page - Working!</h1>
    <h2>Featured Products:</h2>
    <?php foreach ($featured_products as $p): ?>
        <div class="product">
            <h3><?php echo $p['name']; ?></h3>
            <p>Price: ETB <?php echo $p['price']; ?></p>
            <p>Brand: <?php echo $p['brand']; ?></p>
        </div>
    <?php endforeach; ?>
    
    <hr>
    <p><a href="index.php">Try original page</a></p>
</body>
</html>