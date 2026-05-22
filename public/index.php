<?php
// public/index.php

// Start session first
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include required files
require_once '../includes/config.php';
require_once '../functions.php';

// Initialize Product class
require_once '../classes/Product.php';
$product = new Product();

$page_title = 'Home - ' . SITE_NAME;
$cart_count = get_cart_count();

// Get current user
$current_user = null;
if (is_logged_in() && isset($_SESSION['user_id'])) {
    if (file_exists('../classes/User.php')) {
        require_once '../classes/User.php';
        $user = new User();
        $current_user = $user->getUserById($_SESSION['user_id']);
    }
}

// Get featured products
try {
    $featured_products = $product->getFeatured(8);
    $new_arrivals = $product->getAll(12, 0, 'created_at', 'DESC'); // Get 12 latest products
    $categories = $product->getCategories();
    $brands = $product->getBrands();
} catch (Exception $e) {
    // Handle error gracefully
    $featured_products = [];
    $new_arrivals = [];
    $categories = [];
    $brands = [];
    error_log("Error loading products: " . $e->getMessage());
}

// Get wishlist count
$wishlist_count = 0;
if (isset($_SESSION['wishlist']) && is_array($_SESSION['wishlist'])) {
    $wishlist_count = count($_SESSION['wishlist']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link href="../assets/css/style.css" rel="stylesheet">
    <link href="../assets/css/bootstrap-custom.css" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #16a34a;
            --primary-dark: #0f7a35;
            --primary-light: #4ade80;
            --secondary-color: #6b7280;
            --success-color: #22c55e;
            --info-color: #14b8a6;
            --warning-color: #facc15;
            --danger-color: #ef4444;
            --dark-color: #0b0f0e;
            --light-color: #f9fafb;
        }
        
        /* Hero Section */
        .hero-section {
            background: linear-gradient(135deg, rgba(22, 163, 74, 0.1) 0%, rgba(249, 250, 251, 1) 100%);
            padding: 6rem 0 4rem;
            position: relative;
            overflow: hidden;
        }
        
        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 50%;
            height: 100%;
            background: url('https://images.unsplash.com/photo-1606107557195-0e29a4b5b4aa?ixlib=rb-4.0.3&auto=format&fit=crop&w=1600&q=80');
            background-size: cover;
            background-position: center;
            opacity: 0.1;
            z-index: 0;
        }
        
        .hero-content {
            position: relative;
            z-index: 1;
        }
        
        .hero-badge {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            padding: 0.5rem 1.5rem;
            border-radius: 50px;
            font-weight: 600;
            display: inline-block;
            margin-bottom: 1.5rem;
            box-shadow: 0 4px 15px rgba(22, 163, 74, 0.3);
        }
        
        .hero-stats {
            display: flex;
            gap: 2rem;
            margin-top: 2rem;
        }
        
        .stat-item {
            text-align: center;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: 800;
            color: var(--primary-color);
            line-height: 1;
        }
        
        .stat-label {
            font-size: 0.875rem;
            color: var(--secondary-color);
        }
        
        /* Brands Section */
        .brands-section {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            padding: 4rem 0;
        }
        
        .brand-card {
            background: white;
            border-radius: 0.75rem;
            padding: 2rem 1rem;
            text-align: center;
            transition: all 0.3s ease;
            border: 2px solid transparent;
            height: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }
        
        .brand-card:hover {
            transform: translateY(-5px);
            border-color: var(--primary-color);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        
        .brand-logo {
            max-height: 60px;
            max-width: 100%;
            margin-bottom: 1rem;
        }
        
        /* Product Cards */
        .product-card {
            background: white;
            border-radius: 0.75rem;
            overflow: hidden;
            border: 1px solid #e5e7eb;
            transition: all 0.3s ease;
            height: 100%;
            position: relative;
        }
        
        .product-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            border-color: var(--primary-color);
        }
        
        .product-image-container {
            position: relative;
            overflow: hidden;
            height: 250px;
        }
        
        .product-image-container img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }
        
        .product-card:hover .product-image-container img {
            transform: scale(1.05);
        }
        
        .product-badge {
            position: absolute;
            top: 12px;
            left: 12px;
            z-index: 1;
            font-size: 0.75rem;
            font-weight: 700;
            padding: 0.25rem 0.75rem;
            border-radius: 0.25rem;
        }
        
        .product-badge.discount {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
        }
        
        .product-badge.new {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
        }
        
        .product-badge.featured {
            background: linear-gradient(135deg, #facc15, #eab308);
            color: #713f12;
        }
        
        .product-actions {
            position: absolute;
            top: 12px;
            right: 12px;
            z-index: 1;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        
        .product-action-btn {
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, 0.9);
            border-radius: 50%;
            color: var(--dark-color);
            transition: all 0.3s ease;
            border: none;
        }
        
        .product-action-btn:hover {
            background: var(--primary-color);
            color: white;
            transform: scale(1.1);
        }
        
        .product-action-btn.active {
            background: var(--danger-color);
            color: white;
        }
        
        .product-price {
            font-size: 1.25rem;
            font-weight: 800;
            color: var(--primary-color);
        }
        
        .product-old-price {
            font-size: 0.875rem;
            color: var(--secondary-color);
            text-decoration: line-through;
        }
        
        .product-stock {
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            display: inline-block;
        }
        
        .product-stock.in-stock {
            background-color: #dcfce7;
            color: #166534;
        }
        
        .product-stock.low-stock {
            background-color: #fef3c7;
            color: #92400e;
        }
        
        .product-stock.out-stock {
            background-color: #fee2e2;
            color: #991b1b;
        }
        
        /* Sections */
        .section-header {
            position: relative;
            padding-bottom: 1.5rem;
            margin-bottom: 3rem;
        }
        
        .section-header::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 80px;
            height: 4px;
            background: linear-gradient(to right, var(--primary-color), var(--primary-dark));
            border-radius: 2px;
        }
        
        .section-header.center::after {
            left: 50%;
            transform: translateX(-50%);
        }
        
        /* Features Section */
        .features-section {
            padding: 5rem 0;
            background: linear-gradient(135deg, var(--light-color) 0%, #f3f4f6 100%);
        }
        
        .feature-card {
            background: white;
            border-radius: 0.75rem;
            padding: 2rem;
            text-align: center;
            transition: all 0.3s ease;
            height: 100%;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }
        
        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.1);
        }
        
        .feature-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, rgba(22, 163, 74, 0.1), rgba(22, 163, 74, 0.2));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            color: var(--primary-color);
            font-size: 2rem;
        }
        
        /* CTA Section */
        .cta-section {
            background: linear-gradient(135deg, var(--dark-color), #1f2937);
            color: white;
            padding: 5rem 0;
            position: relative;
            overflow: hidden;
        }
        
        .cta-section::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 50%;
            height: 100%;
            background: url('https://images.unsplash.com/photo-1552346154-21d32810aba3?ixlib=rb-4.0.3&auto=format&fit=crop&w=1600&q=80');
            background-size: cover;
            background-position: center;
            opacity: 0.1;
        }
        
        .cta-content {
            position: relative;
            z-index: 1;
        }
        
        .btn-light {
            background: white;
            color: var(--dark-color);
            border: none;
            font-weight: 600;
        }
        
        .btn-light:hover {
            background: #f3f4f6;
            color: var(--dark-color);
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .hero-section {
                padding: 4rem 0 3rem;
            }
            
            .hero-section::before {
                width: 100%;
                opacity: 0.05;
            }
            
            .product-image-container {
                height: 200px;
            }
            
            .features-section, .cta-section {
                padding: 3rem 0;
            }
            
            .hero-stats {
                flex-wrap: wrap;
                gap: 1rem;
            }
            
            .stat-item {
                flex: 1 0 calc(50% - 0.5rem);
            }
        }
        
        @media (max-width: 576px) {
            .product-image-container {
                height: 180px;
            }
            
            .feature-card {
                padding: 1.5rem;
            }
        }
        
        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .animate-fade-in-up {
            animation: fadeInUp 0.6s ease forwards;
        }
        
        /* Custom colors */
        .bg-primary {
            background-color: var(--primary-color) !important;
        }
        
        .text-primary {
            color: var(--primary-color) !important;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-primary:hover {
            background-color: var(--primary-dark);
            border-color: var(--primary-dark);
        }
        
        .btn-outline-primary {
            color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-outline-primary:hover {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            color: white;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <?php include 'includes/navbar.php'; ?>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6 hero-content animate-fade-in-up">
                    <span class="hero-badge">
                        <i class="fas fa-crown me-2"></i>Ethiopia's #1 Sneaker Marketplace
                    </span>
                    <h1 class="display-3 fw-bold mb-4">Step Up Your <span class="text-primary">Style</span> Game</h1>
                    <p class="lead mb-4">
                        Discover authentic sneakers from top brands, curated for the Ethiopian market. 
                        From classic styles to the latest releases, find your perfect pair with guaranteed authenticity.
                    </p>
                    <div class="d-flex flex-wrap gap-3 mb-4">
                        <a href="shop.php" class="btn btn-primary btn-lg px-4 py-3">
                            <i class="fas fa-shopping-bag me-2"></i>Shop Now
                        </a>
                        <a href="#featured" class="btn btn-outline-primary btn-lg px-4 py-3">
                            <i class="fas fa-fire me-2"></i>Trending Now
                        </a>
                    </div>
                    
                    <div class="hero-stats">
                        <div class="stat-item">
                            <div class="stat-number">500+</div>
                            <div class="stat-label">Happy Customers</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number">1K+</div>
                            <div class="stat-label">Products</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number">98%</div>
                            <div class="stat-label">Satisfaction</div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <!-- Hero image will be handled by background -->
                </div>
            </div>
        </div>
    </section>

    <!-- Featured Brands -->
    <?php if (!empty($brands)): ?>
    <section class="brands-section">
        <div class="container">
            <div class="section-header center text-center">
                <h2 class="display-5 fw-bold mb-3">Trusted Brands</h2>
                <p class="lead text-muted">Authentic sneakers from the world's leading brands</p>
            </div>
            
            <div class="row g-4 justify-content-center">
                <?php 
                $brands_to_show = array_slice($brands, 0, 6);
                foreach ($brands_to_show as $brand): 
                    $brand_name = $brand['name'] ?? $brand['brand'] ?? 'Unknown Brand';
                    $brand_logo = !empty($brand['logo']) ? '../' . $brand['logo'] : '';
                    $brand_id = $brand['id'] ?? urlencode($brand_name);
                ?>
                <div class="col-6 col-md-4 col-lg-2">
                    <div class="brand-card">
                        <?php if ($brand_logo): ?>
                        <img src="<?php echo htmlspecialchars($brand_logo); ?>" 
                             alt="<?php echo htmlspecialchars($brand_name); ?>" 
                             class="brand-logo">
                        <?php endif; ?>
                        <h6 class="fw-bold mb-3"><?php echo htmlspecialchars($brand_name); ?></h6>
                        <a href="shop.php?brand=<?php echo urlencode($brand_id); ?>" 
                           class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-arrow-right me-1"></i>Explore
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Featured Products -->
    <section id="featured" class="py-5">
        <div class="container">
            <div class="section-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h2 class="display-5 fw-bold mb-2">Featured Products</h2>
                        <p class="text-muted mb-0">Handpicked collection of premium sneakers</p>
                    </div>
                    <a href="shop.php" class="btn btn-outline-primary">
                        <i class="fas fa-eye me-2"></i>View All
                    </a>
                </div>
            </div>
            
            <?php if (!empty($featured_products)): ?>
            <div class="row g-4">
                <?php foreach ($featured_products as $product_item): 
                    $product_id = $product_item['id'];
                    $product_name = htmlspecialchars($product_item['name']);
                    $category = htmlspecialchars($product_item['category_name'] ?? $product_item['category'] ?? 'Uncategorized');
                    $brand = htmlspecialchars($product_item['brand_name'] ?? $product_item['brand'] ?? '');
                    $price = $product_item['price'];
                    $discount_price = $product_item['discount_price'] ?? 0;
                    $stock = $product_item['stock_quantity'] ?? 0;
                    $is_in_wishlist = isset($_SESSION['wishlist']) && in_array($product_id, $_SESSION['wishlist']);
                    
                    // Get product image
                    $product_image = '../assets/images/products/default.jpg';
                    if (!empty($product_item['images'])) {
                        $images = is_string($product_item['images']) ? json_decode($product_item['images'], true) : $product_item['images'];
                        if (is_array($images) && !empty($images[0])) {
                            $product_image = '../' . $images[0];
                        }
                    }
                    
                    // Stock status
                    $stock_class = 'in-stock';
                    $stock_text = 'In Stock';
                    if ($stock <= 0) {
                        $stock_class = 'out-stock';
                        $stock_text = 'Out of Stock';
                    } elseif ($stock < 5) {
                        $stock_class = 'low-stock';
                        $stock_text = 'Low Stock';
                    }
                    
                    // Discount percentage
                    $discount_percentage = 0;
                    if ($discount_price > 0 && $price > 0) {
                        $discount_percentage = round((($price - $discount_price) / $price) * 100);
                    }
                ?>
                <div class="col-lg-3 col-md-4 col-sm-6">
                    <div class="product-card animate-fade-in-up">
                        <div class="product-image-container">
                            <a href="product-detail.php?id=<?php echo $product_id; ?>">
                                <img src="<?php echo htmlspecialchars($product_image); ?>" 
                                     alt="<?php echo $product_name; ?>"
                                     onerror="this.src='../assets/images/products/default.jpg'">
                            </a>
                            
                            <?php if ($discount_percentage > 0): ?>
                            <span class="product-badge discount">
                                -<?php echo $discount_percentage; ?>%
                            </span>
                            <?php endif; ?>
                            
                            <div class="product-actions">
                                <button class="product-action-btn wishlist-btn" 
                                        data-product-id="<?php echo $product_id; ?>"
                                        data-product-name="<?php echo $product_name; ?>"
                                        title="<?php echo $is_in_wishlist ? 'Remove from Wishlist' : 'Add to Wishlist'; ?>">
                                    <i class="<?php echo $is_in_wishlist ? 'fas' : 'far'; ?> fa-heart"></i>
                                </button>
                                <button class="product-action-btn quick-view-btn" 
                                        data-product-id="<?php echo $product_id; ?>"
                                        title="Quick View">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="p-4">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <span class="badge bg-light text-dark mb-2"><?php echo $category; ?></span>
                                    <?php if ($brand): ?>
                                    <small class="text-muted d-block"><?php echo $brand; ?></small>
                                    <?php endif; ?>
                                </div>
                                <span class="product-stock <?php echo $stock_class; ?>">
                                    <?php echo $stock_text; ?>
                                </span>
                            </div>
                            
                            <h6 class="fw-bold mb-2">
                                <a href="product-detail.php?id=<?php echo $product_id; ?>" 
                                   class="text-decoration-none text-dark">
                                    <?php echo $product_name; ?>
                                </a>
                            </h6>
                            
                            <div class="d-flex justify-content-between align-items-center mt-3">
                                <div>
                                    <?php if ($discount_price > 0): ?>
                                    <div class="product-price"><?php echo format_price($discount_price); ?></div>
                                    <div class="product-old-price"><?php echo format_price($price); ?></div>
                                    <?php else: ?>
                                    <div class="product-price"><?php echo format_price($price); ?></div>
                                    <?php endif; ?>
                                </div>
                                <button class="btn btn-sm btn-primary add-to-cart-btn" 
                                        data-product-id="<?php echo $product_id; ?>"
                                        data-product-name="<?php echo $product_name; ?>"
                                        <?php echo $stock <= 0 ? 'disabled' : ''; ?>>
                                    <i class="fas fa-cart-plus me-1"></i>
                                    <?php echo $stock <= 0 ? 'Out of Stock' : 'Add to Cart'; ?>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="text-center py-5">
                <div class="animate-fade-in-up">
                    <i class="fas fa-box-open fa-4x text-muted mb-3"></i>
                    <h4 class="mb-3">No Featured Products Available</h4>
                    <p class="text-muted mb-4">Check back soon for new arrivals!</p>
                    <a href="shop.php" class="btn btn-primary">
                        <i class="fas fa-shopping-bag me-2"></i>Browse All Products
                    </a>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features-section">
        <div class="container">
            <div class="section-header center text-center">
                <h2 class="display-5 fw-bold mb-3">Why Shop With Us</h2>
                <p class="lead text-muted">Experience the difference with SneakerHub Ethiopia</p>
            </div>
            
            <div class="row g-4">
                <div class="col-lg-3 col-md-6">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <h4 class="fw-bold mb-3">100% Authentic</h4>
                        <p class="text-muted">Every sneaker is verified for authenticity by our expert team.</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-truck-fast"></i>
                        </div>
                        <h4 class="fw-bold mb-3">Fast Delivery</h4>
                        <p class="text-muted">Nationwide delivery across Ethiopia within 3-7 business days.</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-undo-alt"></i>
                        </div>
                        <h4 class="fw-bold mb-3">Easy Returns</h4>
                        <p class="text-muted">14-day return policy for unworn items with original packaging.</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-headset"></i>
                        </div>
                        <h4 class="fw-bold mb-3">24/7 Support</h4>
                        <p class="text-muted">Dedicated support in Amharic, English, and local languages.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- New Arrivals -->
    <section class="py-5 bg-light">
        <div class="container">
            <div class="section-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h2 class="display-5 fw-bold mb-2">New Arrivals</h2>
                        <p class="text-muted mb-0">Latest additions to our collection</p>
                    </div>
                    <a href="shop.php?sort=newest" class="btn btn-outline-primary">
                        <i class="fas fa-eye me-2"></i>View All
                    </a>
                </div>
            </div>
            
            <?php if (!empty($new_arrivals)): ?>
            <div class="row g-4">
                <?php foreach ($new_arrivals as $product_item): 
                    $product_id = $product_item['id'];
                    $product_name = htmlspecialchars($product_item['name']);
                    $price = $product_item['price'];
                    $stock = $product_item['stock_quantity'] ?? 0;
                    
                    // Get product image
                    $product_image = '../assets/images/products/default.jpg';
                    if (!empty($product_item['images'])) {
                        $images = is_string($product_item['images']) ? json_decode($product_item['images'], true) : $product_item['images'];
                        if (is_array($images) && !empty($images[0])) {
                            $product_image = '../' . $images[0];
                        }
                    }
                ?>
                <div class="col-lg-3 col-md-4 col-sm-6">
                    <div class="product-card animate-fade-in-up">
                        <div class="product-image-container">
                            <a href="product-detail.php?id=<?php echo $product_id; ?>">
                                <img src="<?php echo htmlspecialchars($product_image); ?>" 
                                     alt="<?php echo $product_name; ?>"
                                     onerror="this.src='../assets/images/products/default.jpg'">
                            </a>
                            <span class="product-badge new">New</span>
                        </div>
                        
                        <div class="p-4">
                            <h6 class="fw-bold mb-2">
                                <a href="product-detail.php?id=<?php echo $product_id; ?>" 
                                   class="text-decoration-none text-dark">
                                    <?php echo $product_name; ?>
                                </a>
                            </h6>
                            
                            <div class="d-flex justify-content-between align-items-center mt-3">
                                <div class="product-price"><?php echo format_price($price); ?></div>
                                <button class="btn btn-sm btn-outline-primary add-to-cart-btn" 
                                        data-product-id="<?php echo $product_id; ?>"
                                        data-product-name="<?php echo $product_name; ?>"
                                        <?php echo $stock <= 0 ? 'disabled' : ''; ?>>
                                    <i class="fas fa-cart-plus me-1"></i>
                                    <?php echo $stock <= 0 ? 'Out of Stock' : 'Add to Cart'; ?>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="text-center py-5">
                <div class="animate-fade-in-up">
                    <i class="fas fa-clock fa-4x text-muted mb-3"></i>
                    <h4 class="mb-3">No New Arrivals Yet</h4>
                    <p class="text-muted mb-4">Check back soon for the latest sneaker releases!</p>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section">
        <div class="container">
            <div class="row justify-content-center text-center">
                <div class="col-lg-8 cta-content animate-fade-in-up">
                    <h2 class="display-4 fw-bold mb-4">Ready to Find Your Perfect Pair?</h2>
                    <p class="lead mb-4 opacity-75">
                        Join thousands of satisfied customers who've found their dream sneakers with us. 
                        Authentic products, secure payment, and fast delivery guaranteed.
                    </p>
                    <div class="d-flex flex-wrap justify-content-center gap-3">
                        <a href="shop.php" class="btn btn-light btn-lg px-4 py-3">
                            <i class="fas fa-shopping-bag me-2"></i>Start Shopping
                        </a>
                        <a href="register.php" class="btn btn-outline-light btn-lg px-4 py-3">
                            <i class="fas fa-user-plus me-2"></i>Create Account
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <?php include 'includes/footer.php'; ?>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Initialize animations
        document.addEventListener('DOMContentLoaded', function() {
            // Add animation delay to cards
            document.querySelectorAll('.animate-fade-in-up').forEach((element, index) => {
                element.style.animationDelay = (index * 0.1) + 's';
            });
        });
        
        // Add to cart functionality
        document.addEventListener('click', function(e) {
            // Add to cart button
            if (e.target.closest('.add-to-cart-btn')) {
                const button = e.target.closest('.add-to-cart-btn');
                const productId = button.dataset.productId;
                const productName = button.dataset.productName;
                
                if (button.disabled) {
                    showAlert('error', `${productName} is currently out of stock`);
                    return;
                }
                
                // Show loading state
                const originalText = button.innerHTML;
                button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                button.disabled = true;
                
                // Simulate API call
                setTimeout(() => {
                    // Update cart via fetch
                    fetch(`cart.php?action=add&product_id=${productId}&quantity=1`)
                        .then(response => response.text())
                        .then(data => {
                            showAlert('success', `${productName} added to cart!`);
                            updateCartCount();
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            showAlert('error', 'Failed to add to cart. Please try again.');
                        })
                        .finally(() => {
                            // Restore button after 1.5 seconds
                            setTimeout(() => {
                                button.innerHTML = originalText;
                                button.disabled = false;
                            }, 1500);
                        });
                }, 500);
            }
            
            // Wishlist button
            if (e.target.closest('.wishlist-btn')) {
                const button = e.target.closest('.wishlist-btn');
                const productId = button.dataset.productId;
                const productName = button.dataset.productName;
                const icon = button.querySelector('i');
                const isInWishlist = icon.classList.contains('fas');
                
                // Toggle icon immediately for better UX
                if (isInWishlist) {
                    icon.classList.remove('fas');
                    icon.classList.add('far');
                    button.title = 'Add to Wishlist';
                } else {
                    icon.classList.remove('far');
                    icon.classList.add('fas');
                    button.title = 'Remove from Wishlist';
                }
                
                // Update wishlist
                fetch(`wishlist.php?action=${isInWishlist ? 'remove' : 'add'}&product_id=${productId}`)
                    .then(response => response.text())
                    .then(data => {
                        showAlert('success', 
                            isInWishlist ? 
                            `${productName} removed from wishlist` : 
                            `${productName} added to wishlist`
                        );
                        updateWishlistCount();
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        // Revert on error
                        if (isInWishlist) {
                            icon.classList.remove('far');
                            icon.classList.add('fas');
                            button.title = 'Remove from Wishlist';
                        } else {
                            icon.classList.remove('fas');
                            icon.classList.add('far');
                            button.title = 'Add to Wishlist';
                        }
                        showAlert('error', 'Failed to update wishlist. Please try again.');
                    });
            }
            
            // Quick view button
            if (e.target.closest('.quick-view-btn')) {
                const button = e.target.closest('.quick-view-btn');
                const productId = button.dataset.productId;
                
                // Show quick view modal
                showQuickView(productId);
            }
        });
        
        // Update cart count
        function updateCartCount() {
            // Get current cart count from session
            fetch('cart.php?action=get_count')
                .then(response => response.text())
                .then(count => {
                    const cartBadge = document.querySelector('.fa-shopping-cart').parentElement.querySelector('.badge');
                    const numCount = parseInt(count) || 0;
                    
                    if (numCount > 0) {
                        if (!cartBadge) {
                            const cartIcon = document.querySelector('.fa-shopping-cart').parentElement;
                            const badge = document.createElement('span');
                            badge.className = 'position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger';
                            cartIcon.appendChild(badge);
                        }
                        cartBadge.textContent = numCount;
                    } else if (cartBadge) {
                        cartBadge.remove();
                    }
                })
                .catch(error => console.error('Error updating cart count:', error));
        }
        
        // Update wishlist count
        function updateWishlistCount() {
            // Get current wishlist count
            const wishlistCount = <?php echo $wishlist_count; ?>;
            const wishlistBadge = document.querySelector('.fa-heart').parentElement.querySelector('.badge');
            
            if (wishlistCount > 0) {
                if (!wishlistBadge) {
                    const wishlistIcon = document.querySelector('.fa-heart').parentElement;
                    const badge = document.createElement('span');
                    badge.className = 'position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger';
                    wishlistIcon.appendChild(badge);
                }
                wishlistBadge.textContent = wishlistCount;
            } else if (wishlistBadge) {
                wishlistBadge.remove();
            }
        }
        
        // Show quick view modal
        function showQuickView(productId) {
            // In a real implementation, this would fetch product details
            // and display them in a modal
            showAlert('info', 'Quick view feature coming soon!');
            
            // Example implementation:
            /*
            fetch(`api/get-product.php?id=${productId}`)
                .then(response => response.json())
                .then(product => {
                    // Show modal with product details
                    const modal = new bootstrap.Modal(document.getElementById('quickViewModal'));
                    // Populate modal with product data
                    // ...
                    modal.show();
                })
                .catch(error => {
                    console.error('Error:', error);
                    showAlert('error', 'Failed to load product details');
                });
            */
        }
        
        // Alert system
        function showAlert(type, message) {
            // Remove existing alerts
            document.querySelectorAll('.custom-alert').forEach(alert => alert.remove());
            
            const alertDiv = document.createElement('div');
            alertDiv.className = `custom-alert alert alert-${type} alert-dismissible fade show position-fixed`;
            alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px; animation: slideIn 0.3s ease;';
            alertDiv.innerHTML = `
                <div class="d-flex align-items-center">
                    <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'} me-2"></i>
                    <div class="flex-grow-1">${message}</div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
                </div>
            `;
            
            document.body.appendChild(alertDiv);
            
            // Auto remove after 5 seconds
            setTimeout(() => {
                if (alertDiv.parentNode) {
                    alertDiv.remove();
                }
            }, 5000);
        }
        
        // Add CSS for alert animation
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideIn {
                from {
                    transform: translateX(100%);
                    opacity: 0;
                }
                to {
                    transform: translateX(0);
                    opacity: 1;
                }
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>