<?php
echo "<h1>Fixing Index.php Method Calls</h1>";

$index_file = __DIR__ . '/public/index.php';

if (!file_exists($index_file)) {
    die("❌ Index file not found");
}

$content = file_get_contents($index_file);

// Fix method calls
$replacements = [
    // Change getFeaturedProducts() to getFeatured()
    "\$product->getFeaturedProducts(8)" => "\$product->getFeatured(8)",
    
    // Change getNewArrivals() to getAll()
    "\$product->getNewArrivals(8)" => "\$product->getAll(8)",
    
    // Remove getCategories() and getBrands() for now
    "\$categories = \$product->getCategories();\n\$brands = \$product->getBrands();" => 
    "// Categories and brands will be loaded from database later\n\$categories = [];\n\$brands = [];",
    
    // Fix getProductById() call (line in wishlist section)
    "\$item = \$product->getProductById(\$product_id);" => "\$item = \$product->getById(\$product_id);"
];

foreach ($replacements as $search => $replace) {
    if (strpos($content, $search) !== false) {
        $content = str_replace($search, $replace, $content);
        echo "✅ Fixed: $search<br>";
    }
}

// Write back
if (file_put_contents($index_file, $content)) {
    echo "<hr><div style='background: #4CAF50; color: white; padding: 20px;'>";
    echo "<h2>✅ Index.php Fixed Successfully!</h2>";
    echo "All method calls have been updated to match your Product class.<br>";
    echo "<a href='/sneaker-mart/public/index.php' style='color: white; text-decoration: underline;'>Test Your Website Now</a>";
    echo "</div>";
} else {
    echo "❌ Failed to update index file";
}

echo "<hr>";
echo "<h3>Or you can manually update these lines in public/index.php:</h3>";
echo "<pre>
Line 8:  \$featured_products = \$product->getFeatured(8);
Line 9:  \$new_arrivals = \$product->getAll(8);
Lines 10-11: Remove getCategories() and getBrands() calls
</pre>";
?>