<?php
// public/shop.php

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include required files
require_once '../config.php';
require_once '../functions.php';

// Define helper functions if not already defined
if (!function_exists('get_cart_count')) {
    function get_cart_count() {
        $cart_count = 0;
        if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
            foreach ($_SESSION['cart'] as $item) {
                $cart_count += $item['quantity'] ?? 1;
            }
        }
        return $cart_count;
    }
}

if (!function_exists('format_price')) {
    function format_price($price) {
        if (empty($price)) {
            return 'ETB 0.00';
        }
        return 'ETB ' . number_format($price, 2);
    }
}

if (!function_exists('is_in_wishlist_session')) {
    function is_in_wishlist_session($product_id) {
        return isset($_SESSION['wishlist']) && is_array($_SESSION['wishlist']) && in_array($product_id, $_SESSION['wishlist']);
    }
}

if (!function_exists('get_product_condition_label')) {
    function get_product_condition_label($condition) {
        $labels = [
            'new' => 'New',
            'used' => 'Used',
            'refurbished' => 'Refurbished'
        ];
        return $labels[$condition] ?? ucfirst($condition);
    }
}

if (!function_exists('calculate_discount_percentage')) {
    function calculate_discount_percentage($original_price, $discount_price) {
        if ($original_price <= 0 || $discount_price <= 0) return 0;
        return round((($original_price - $discount_price) / $original_price) * 100);
    }
}

// Initialize Product class
$product = null;
$products = [];
$total_products = 0;
$categories = [];
$brands = [];

try {
    require_once '../classes/Product.php';
    $product = new Product();
} catch (Exception $e) {
    error_log("Error loading Product class: " . $e->getMessage());
}

// Get filters from URL
$category = $_GET['category'] ?? '';
$brand = $_GET['brand'] ?? '';
$min_price = $_GET['min_price'] ?? '';
$max_price = $_GET['max_price'] ?? '';
$condition = $_GET['condition'] ?? '';
$search = $_GET['search'] ?? '';
$sort = $_GET['sort'] ?? 'created_at';
$order = $_GET['order'] ?? 'DESC';
$page = max(1, intval($_GET['page'] ?? 1));

// Products per page
$per_page = 12;

// Prepare filters
$filters = [];
if (!empty($category)) $filters['category'] = $category;
if (!empty($brand)) $filters['brand'] = $brand;
if (!empty($min_price)) $filters['min_price'] = floatval($min_price);
if (!empty($max_price)) $filters['max_price'] = floatval($max_price);
if (!empty($condition)) $filters['condition'] = $condition;
if (!empty($search)) $filters['search'] = $search;
$filters['sort'] = $sort;
$filters['order'] = $order;

// Get products data
if ($product) {
    try {
        $total_products = $product->countProducts($filters);
        $products = $product->getProducts($filters, $per_page, ($page - 1) * $per_page);
        $categories = $product->getCategories();
        $brands = $product->getBrands();
    } catch (Exception $e) {
        error_log("Error loading products: " . $e->getMessage());
    }
}

$page_title = 'Shop - ' . SITE_NAME;
$cart_count = get_cart_count();

// Get wishlist count
$wishlist_count = 0;
if (isset($_SESSION['wishlist']) && is_array($_SESSION['wishlist'])) {
    $wishlist_count = count($_SESSION['wishlist']);
}

// Helper function for modify_url
function modify_url($new_params) {
    $params = $_GET;
    foreach ($new_params as $key => $value) {
        $params[$key] = $value;
    }
    return 'shop.php?' . http_build_query($params);
}

// Generate CSRF token for AJAX requests
$csrf_token = bin2hex(random_bytes(32));
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = $csrf_token;
} else {
    $csrf_token = $_SESSION['csrf_token'];
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
            --danger-color: #ef4444;
            --dark-color: #0b0f0e;
            --light-color: #f9fafb;
        }
        
        body {
            padding-top: 76px;
            background-color: #f8f9fa;
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
            height: 200px;
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
        
        .product-badge.condition {
            position: absolute;
            top: 12px;
            right: 12px;
            background: rgba(0, 0, 0, 0.7);
            color: white;
            font-size: 0.7rem;
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
            opacity: 0;
            transform: translateX(20px);
        }
        
        .product-card:hover .product-action-btn {
            opacity: 1;
            transform: translateX(0);
        }
        
        .product-action-btn:hover {
            background: var(--primary-color);
            color: white;
            transform: scale(1.1) !important;
        }
        
        .product-action-btn.active {
            background: var(--danger-color);
            color: white;
        }
        
        .product-price {
            font-size: 1.1rem;
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
        
        /* Filter Sidebar */
        .filter-card {
            background: white;
            border-radius: 0.75rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            overflow: hidden;
        }
        
        .filter-header {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            padding: 1.25rem;
        }
        
        .filter-body {
            padding: 1.5rem;
        }
        
        .filter-group {
            margin-bottom: 1.5rem;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 1.5rem;
        }
        
        .filter-group:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        
        .filter-title {
            font-weight: 600;
            margin-bottom: 1rem;
            color: var(--dark-color);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .filter-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .filter-list li {
            margin-bottom: 0.5rem;
        }
        
        .filter-list li:last-child {
            margin-bottom: 0;
        }
        
        .filter-link {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.5rem 0.75rem;
            border-radius: 0.5rem;
            text-decoration: none;
            color: var(--secondary-color);
            transition: all 0.2s ease;
        }
        
        .filter-link:hover {
            background-color: #f3f4f6;
            color: var(--primary-color);
        }
        
        .filter-link.active {
            background-color: rgba(22, 163, 74, 0.1);
            color: var(--primary-color);
            font-weight: 600;
        }
        
        .filter-count {
            background-color: #e5e7eb;
            color: #374151;
            font-size: 0.75rem;
            padding: 0.125rem 0.5rem;
            border-radius: 1rem;
        }
        
        .filter-link.active .filter-count {
            background-color: var(--primary-color);
            color: white;
        }
        
        /* Price Range Slider */
        .price-range-container {
            padding: 0.5rem 0;
        }
        
        .price-inputs {
            display: flex;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .price-input-group {
            flex: 1;
        }
        
        .price-input-group label {
            font-size: 0.875rem;
            color: var(--secondary-color);
            margin-bottom: 0.25rem;
            display: block;
        }
        
        .price-input {
            width: 100%;
            padding: 0.5rem 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 0.375rem;
            font-size: 0.875rem;
        }
        
        .price-input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(22, 163, 74, 0.1);
        }
        
        .price-slider {
            width: 100%;
            height: 4px;
            background: #d1d5db;
            border-radius: 2px;
            margin: 1rem 0;
            position: relative;
        }
        
        .price-slider-track {
            position: absolute;
            height: 100%;
            background: var(--primary-color);
            border-radius: 2px;
        }
        
        /* Pagination */
        .pagination-custom .page-link {
            border: 1px solid #e5e7eb;
            color: var(--secondary-color);
            padding: 0.5rem 0.75rem;
            margin: 0 0.125rem;
            border-radius: 0.375rem;
            transition: all 0.2s ease;
        }
        
        .pagination-custom .page-link:hover {
            background-color: #f3f4f6;
            border-color: #d1d5db;
            color: var(--primary-color);
        }
        
        .pagination-custom .page-item.active .page-link {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            color: white;
        }
        
        .pagination-custom .page-item.disabled .page-link {
            background-color: #f9fafb;
            color: #9ca3af;
            cursor: not-allowed;
        }
        
        /* Sort Dropdown */
        .sort-dropdown .dropdown-toggle {
            border: 1px solid #d1d5db;
            background: white;
            color: var(--secondary-color);
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            transition: all 0.2s ease;
        }
        
        .sort-dropdown .dropdown-toggle:hover {
            border-color: var(--primary-color);
            color: var(--primary-color);
        }
        
        .sort-dropdown .dropdown-menu {
            border: 1px solid #e5e7eb;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            border-radius: 0.5rem;
            padding: 0.5rem;
        }
        
        .sort-dropdown .dropdown-item {
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            transition: all 0.2s ease;
        }
        
        .sort-dropdown .dropdown-item:hover {
            background-color: rgba(22, 163, 74, 0.1);
            color: var(--primary-color);
        }
        
        /* Alert System */
        .custom-alert {
            position: fixed;
            top: 90px;
            right: 20px;
            z-index: 9999;
            min-width: 300px;
            animation: slideIn 0.3s ease;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
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
        
        /* Loading State */
        .btn-loading {
            position: relative;
            color: transparent !important;
        }
        
        .btn-loading::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 20px;
            height: 20px;
            margin: -10px 0 0 -10px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 0.6s linear infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        /* Active Filters */
        .active-filters {
            background: white;
            border-radius: 0.75rem;
            padding: 1rem;
            margin-bottom: 1.5rem;
            border: 1px solid #e5e7eb;
        }
        
        .active-filter-tag {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background-color: rgba(22, 163, 74, 0.1);
            color: var(--primary-color);
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            font-size: 0.875rem;
            margin-right: 0.5rem;
            margin-bottom: 0.5rem;
        }
        
        .active-filter-tag .remove-filter {
            cursor: pointer;
            font-size: 0.75rem;
            opacity: 0.7;
            transition: opacity 0.2s ease;
        }
        
        .active-filter-tag .remove-filter:hover {
            opacity: 1;
        }
        
        /* Grid/List View Toggle */
        .view-toggle {
            display: flex;
            border: 1px solid #d1d5db;
            border-radius: 0.375rem;
            overflow: hidden;
        }
        
        .view-toggle-btn {
            padding: 0.5rem 1rem;
            background: white;
            border: none;
            color: var(--secondary-color);
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .view-toggle-btn:hover {
            background-color: #f3f4f6;
        }
        
        .view-toggle-btn.active {
            background-color: var(--primary-color);
            color: white;
        }
        
        .view-toggle-btn:not(:last-child) {
            border-right: 1px solid #d1d5db;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            body {
                padding-top: 70px;
            }
            
            .product-image-container {
                height: 180px;
            }
            
            .custom-alert {
                top: 80px;
                left: 20px;
                right: 20px;
                min-width: auto;
            }
        }
        
        @media (max-width: 576px) {
            .product-image-container {
                height: 160px;
            }
            
            .price-inputs {
                flex-direction: column;
                gap: 0.5rem;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-shoe-prints"></i> <?php echo htmlspecialchars(SITE_NAME); ?>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="shop.php">Shop</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="about.php">About Us</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="contact.php">Contact</a>
                    </li>
                </ul>
                
                <div class="d-flex align-items-center">
                    <!-- Search Form -->
                    <form class="d-flex me-3" action="shop.php" method="GET">
                        <input class="form-control me-2" type="search" name="search" placeholder="Search sneakers..." 
                               aria-label="Search" style="min-width: 200px;" value="<?php echo htmlspecialchars($search); ?>">
                        <button class="btn btn-outline-light" type="submit">
                            <i class="fas fa-search"></i>
                        </button>
                    </form>
                    
                    <!-- Wishlist -->
                    <div class="me-3">
                        <a href="wishlist.php" class="text-light position-relative" style="text-decoration: none;">
                            <i class="fas fa-heart fa-lg"></i>
                            <?php if ($wishlist_count > 0): ?>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" id="wishlist-badge">
                                <?php echo $wishlist_count; ?>
                            </span>
                            <?php endif; ?>
                        </a>
                    </div>
                    
                    <!-- Cart -->
                    <div class="me-3">
                        <a href="cart.php" class="text-light position-relative" style="text-decoration: none;">
                            <i class="fas fa-shopping-cart fa-lg"></i>
                            <?php if ($cart_count > 0): ?>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" id="cart-badge">
                                <?php echo $cart_count; ?>
                            </span>
                            <?php endif; ?>
                        </a>
                    </div>
                    
                    <!-- User Dropdown -->
                    <?php if (isset($_SESSION['user_id'])): ?>
                    <div class="dropdown">
                        <a href="#" class="text-light dropdown-toggle d-flex align-items-center" 
                           data-bs-toggle="dropdown" aria-expanded="false" style="text-decoration: none;">
                            <i class="fas fa-user fa-lg me-1"></i>
                            <span class="d-none d-lg-inline">My Account</span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <?php if (isset($_SESSION['user_type'])): ?>
                                <?php if ($_SESSION['user_type'] === 'buyer'): ?>
                                <li><a class="dropdown-item" href="../buyer/">Dashboard</a></li>
                                <li><a class="dropdown-item" href="../buyer/orders.php">My Orders</a></li>
                                <li><a class="dropdown-item" href="../buyer/profile.php">My Profile</a></li>
                                <?php elseif ($_SESSION['user_type'] === 'vendor'): ?>
                                <li><a class="dropdown-item" href="../vendor/">Dashboard</a></li>
                                <li><a class="dropdown-item" href="../vendor/products.php">My Products</a></li>
                                <li><a class="dropdown-item" href="../vendor/earnings.php">Earnings</a></li>
                                <?php elseif ($_SESSION['user_type'] === 'admin'): ?>
                                <li><a class="dropdown-item" href="../admin/">Dashboard</a></li>
                                <li><a class="dropdown-item" href="../admin/products.php">Products</a></li>
                                <li><a class="dropdown-item" href="../admin/users.php">Users</a></li>
                                <?php endif; ?>
                            <?php endif; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="logout.php">Logout</a></li>
                        </ul>
                    </div>
                    <?php else: ?>
                    <div>
                        <a href="login.php" class="btn btn-outline-light me-2">Login</a>
                        <a href="register.php" class="btn btn-primary">Register</a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="bg-light py-3">
        <div class="container">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                <li class="breadcrumb-item active">Shop</li>
            </ol>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container py-5">
        <div class="row">
            <!-- Sidebar Filters -->
            <div class="col-lg-3 mb-4">
                <div class="filter-card">
                    <div class="filter-header">
                        <h5 class="mb-0"><i class="fas fa-filter me-2"></i>Filters</h5>
                    </div>
                    <div class="filter-body">
                        <!-- Search -->
                        <div class="filter-group">
                            <div class="filter-title">
                                <i class="fas fa-search"></i>
                                <span>Search</span>
                            </div>
                            <form id="search-form" action="shop.php" method="GET">
                                <div class="input-group">
                                    <input type="text" class="form-control" name="search" placeholder="Search products..." 
                                           value="<?php echo htmlspecialchars($search); ?>">
                                    <button class="btn btn-primary" type="submit">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- Categories -->
                        <div class="filter-group">
                            <div class="filter-title">
                                <i class="fas fa-list"></i>
                                <span>Categories</span>
                            </div>
                            <ul class="filter-list">
                                <li>
                                    <a href="<?php echo modify_url(['category' => '', 'page' => 1]); ?>" 
                                       class="filter-link <?php echo empty($category) ? 'active' : ''; ?>">
                                        <span>All Categories</span>
                                        <span class="filter-count"><?php echo $total_products; ?></span>
                                    </a>
                                </li>
                                <?php foreach ($categories as $cat): 
                                    // Get count for each category (you would need to implement this in your Product class)
                                    // For now, using placeholder
                                    $cat_count = 0;
                                ?>
                                <li>
                                    <a href="<?php echo modify_url(['category' => $cat, 'page' => 1]); ?>" 
                                       class="filter-link <?php echo $category == $cat ? 'active' : ''; ?>">
                                        <span><?php echo htmlspecialchars($cat); ?></span>
                                        <span class="filter-count"><?php echo $cat_count; ?></span>
                                    </a>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>

                        <!-- Brands -->
                        <div class="filter-group">
                            <div class="filter-title">
                                <i class="fas fa-tag"></i>
                                <span>Brands</span>
                            </div>
                            <ul class="filter-list">
                                <li>
                                    <a href="<?php echo modify_url(['brand' => '', 'page' => 1]); ?>" 
                                       class="filter-link <?php echo empty($brand) ? 'active' : ''; ?>">
                                        <span>All Brands</span>
                                        <span class="filter-count"><?php echo $total_products; ?></span>
                                    </a>
                                </li>
                                <?php foreach ($brands as $brnd): 
                                    // Get count for each brand (you would need to implement this in your Product class)
                                    // For now, using placeholder
                                    $brand_count = 0;
                                ?>
                                <li>
                                    <a href="<?php echo modify_url(['brand' => $brnd, 'page' => 1]); ?>" 
                                       class="filter-link <?php echo $brand == $brnd ? 'active' : ''; ?>">
                                        <span><?php echo htmlspecialchars($brnd); ?></span>
                                        <span class="filter-count"><?php echo $brand_count; ?></span>
                                    </a>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>

                        <!-- Price Range -->
                        <div class="filter-group">
                            <div class="filter-title">
                                <i class="fas fa-money-bill-wave"></i>
                                <span>Price Range (ETB)</span>
                            </div>
                            <form id="price-form" action="shop.php" method="GET">
                                <input type="hidden" name="category" value="<?php echo htmlspecialchars($category); ?>">
                                <input type="hidden" name="brand" value="<?php echo htmlspecialchars($brand); ?>">
                                <input type="hidden" name="condition" value="<?php echo htmlspecialchars($condition); ?>">
                                <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                                <input type="hidden" name="sort" value="<?php echo htmlspecialchars($sort); ?>">
                                <input type="hidden" name="order" value="<?php echo htmlspecialchars($order); ?>">
                                
                                <div class="price-range-container">
                                    <div class="price-inputs">
                                        <div class="price-input-group">
                                            <label for="min_price">Min</label>
                                            <input type="number" class="form-control price-input" id="min_price" 
                                                   name="min_price" placeholder="0" min="0" 
                                                   value="<?php echo htmlspecialchars($min_price); ?>">
                                        </div>
                                        <div class="price-input-group">
                                            <label for="max_price">Max</label>
                                            <input type="number" class="form-control price-input" id="max_price" 
                                                   name="max_price" placeholder="50000" min="0" 
                                                   value="<?php echo htmlspecialchars($max_price); ?>">
                                        </div>
                                    </div>
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="fas fa-check me-1"></i>Apply Price
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- Condition -->
                        <div class="filter-group">
                            <div class="filter-title">
                                <i class="fas fa-certificate"></i>
                                <span>Condition</span>
                            </div>
                            <ul class="filter-list">
                                <li>
                                    <a href="<?php echo modify_url(['condition' => '', 'page' => 1]); ?>" 
                                       class="filter-link <?php echo empty($condition) ? 'active' : ''; ?>">
                                        <span>All Conditions</span>
                                    </a>
                                </li>
                                <li>
                                    <a href="<?php echo modify_url(['condition' => 'new', 'page' => 1]); ?>" 
                                       class="filter-link <?php echo $condition == 'new' ? 'active' : ''; ?>">
                                        <span class="badge bg-success me-2" style="padding: 0.125rem 0.375rem;">New</span>
                                        <span>New</span>
                                    </a>
                                </li>
                                <li>
                                    <a href="<?php echo modify_url(['condition' => 'used', 'page' => 1]); ?>" 
                                       class="filter-link <?php echo $condition == 'used' ? 'active' : ''; ?>">
                                        <span class="badge bg-warning me-2" style="padding: 0.125rem 0.375rem;">Used</span>
                                        <span>Used</span>
                                    </a>
                                </li>
                                <li>
                                    <a href="<?php echo modify_url(['condition' => 'refurbished', 'page' => 1]); ?>" 
                                       class="filter-link <?php echo $condition == 'refurbished' ? 'active' : ''; ?>">
                                        <span class="badge bg-info me-2" style="padding: 0.125rem 0.375rem;">Refurb</span>
                                        <span>Refurbished</span>
                                    </a>
                                </li>
                            </ul>
                        </div>

                        <!-- Clear Filters -->
                        <div class="d-grid">
                            <a href="shop.php" class="btn btn-outline-danger">
                                <i class="fas fa-times-circle me-2"></i>Clear All Filters
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Products Section -->
            <div class="col-lg-9">
                <!-- Active Filters -->
                <?php if ($category || $brand || $min_price || $max_price || $condition || $search): ?>
                <div class="active-filters mb-4">
                    <h6 class="mb-3">Active Filters:</h6>
                    <div>
                        <?php if ($category): ?>
                        <span class="active-filter-tag">
                            Category: <?php echo htmlspecialchars($category); ?>
                            <a href="<?php echo modify_url(['category' => '', 'page' => 1]); ?>" class="remove-filter">
                                <i class="fas fa-times"></i>
                            </a>
                        </span>
                        <?php endif; ?>
                        
                        <?php if ($brand): ?>
                        <span class="active-filter-tag">
                            Brand: <?php echo htmlspecialchars($brand); ?>
                            <a href="<?php echo modify_url(['brand' => '', 'page' => 1]); ?>" class="remove-filter">
                                <i class="fas fa-times"></i>
                            </a>
                        </span>
                        <?php endif; ?>
                        
                        <?php if ($min_price || $max_price): ?>
                        <span class="active-filter-tag">
                            Price: 
                            <?php if ($min_price) echo 'ETB ' . htmlspecialchars($min_price) . ' - '; ?>
                            <?php if ($max_price) echo 'ETB ' . htmlspecialchars($max_price); ?>
                            <?php if (!$max_price && $min_price) echo '+'; ?>
                            <a href="<?php echo modify_url(['min_price' => '', 'max_price' => '', 'page' => 1]); ?>" class="remove-filter">
                                <i class="fas fa-times"></i>
                            </a>
                        </span>
                        <?php endif; ?>
                        
                        <?php if ($condition): ?>
                        <span class="active-filter-tag">
                            Condition: <?php echo get_product_condition_label($condition); ?>
                            <a href="<?php echo modify_url(['condition' => '', 'page' => 1]); ?>" class="remove-filter">
                                <i class="fas fa-times"></i>
                            </a>
                        </span>
                        <?php endif; ?>
                        
                        <?php if ($search): ?>
                        <span class="active-filter-tag">
                            Search: "<?php echo htmlspecialchars($search); ?>"
                            <a href="<?php echo modify_url(['search' => '', 'page' => 1]); ?>" class="remove-filter">
                                <i class="fas fa-times"></i>
                            </a>
                        </span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Header -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2 class="mb-1">Sneakers Collection</h2>
                        <p class="text-muted mb-0">
                            Showing 
                            <span class="fw-semibold"><?php echo ($page - 1) * $per_page + 1; ?>-<?php echo min($page * $per_page, $total_products); ?></span> 
                            of <span class="fw-semibold"><?php echo $total_products; ?></span> products
                        </p>
                    </div>
                    
                    <div class="d-flex align-items-center gap-3">
                        <!-- View Toggle -->
                        <div class="view-toggle">
                            <button class="view-toggle-btn active" data-view="grid" title="Grid View">
                                <i class="fas fa-th-large"></i>
                            </button>
                            <button class="view-toggle-btn" data-view="list" title="List View">
                                <i class="fas fa-list"></i>
                            </button>
                        </div>
                        
                        <!-- Sort Dropdown -->
                        <div class="sort-dropdown">
                            <div class="dropdown">
                                <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                    <i class="fas fa-sort me-2"></i>
                                    <?php 
                                    $sort_options = [
                                        'created_at_desc' => 'Newest',
                                        'price_asc' => 'Price: Low to High',
                                        'price_desc' => 'Price: High to Low',
                                        'name_asc' => 'Name: A to Z',
                                        'name_desc' => 'Name: Z to A',
                                        'rating_desc' => 'Highest Rated'
                                    ];
                                    $current_sort_key = $sort . '_' . strtolower($order);
                                    echo $sort_options[$current_sort_key] ?? 'Newest';
                                    ?>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><a class="dropdown-item" href="<?php echo modify_url(['sort' => 'created_at', 'order' => 'DESC', 'page' => 1]); ?>">Newest</a></li>
                                    <li><a class="dropdown-item" href="<?php echo modify_url(['sort' => 'price', 'order' => 'ASC', 'page' => 1]); ?>">Price: Low to High</a></li>
                                    <li><a class="dropdown-item" href="<?php echo modify_url(['sort' => 'price', 'order' => 'DESC', 'page' => 1]); ?>">Price: High to Low</a></li>
                                    <li><a class="dropdown-item" href="<?php echo modify_url(['sort' => 'name', 'order' => 'ASC', 'page' => 1]); ?>">Name: A to Z</a></li>
                                    <li><a class="dropdown-item" href="<?php echo modify_url(['sort' => 'name', 'order' => 'DESC', 'page' => 1]); ?>">Name: Z to A</a></li>
                                    <li><a class="dropdown-item" href="<?php echo modify_url(['sort' => 'rating', 'order' => 'DESC', 'page' => 1]); ?>">Highest Rated</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Products Grid -->
                <?php if (empty($products)): ?>
                <div class="text-center py-5">
                    <div class="animate-fade-in-up">
                        <i class="fas fa-search fa-4x text-muted mb-3"></i>
                        <h3>No Products Found</h3>
                        <p class="text-muted mb-4">Try adjusting your search or filter criteria</p>
                        <a href="shop.php" class="btn btn-primary">
                            <i class="fas fa-times-circle me-2"></i>Clear Filters
                        </a>
                    </div>
                </div>
                <?php else: ?>
                <div class="row" id="products-grid">
                    <?php foreach ($products as $product_item): 
                        $product_id = $product_item['id'];
                        $product_name = htmlspecialchars($product_item['name']);
                        $category = htmlspecialchars($product_item['category_name'] ?? $product_item['category'] ?? 'Uncategorized');
                        $brand = htmlspecialchars($product_item['brand_name'] ?? $product_item['brand'] ?? '');
                        $price = $product_item['price'];
                        $discount_price = $product_item['discount_price'] ?? 0;
                        $stock = $product_item['stock_quantity'] ?? 0;
                        $rating = $product_item['rating'] ?? 0;
                        $review_count = $product_item['review_count'] ?? 0;
                        $product_condition = $product_item['condition'] ?? 'new';
                        $is_in_wishlist = is_in_wishlist_session($product_id);
                        
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
                            $discount_percentage = calculate_discount_percentage($price, $discount_price);
                        }
                    ?>
                    <div class="col-lg-4 col-md-6 mb-4 product-grid-item">
                        <div class="product-card animate-fade-in-up" data-product-id="<?php echo $product_id; ?>">
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
                                
                                <span class="product-badge condition">
                                    <?php echo get_product_condition_label($product_condition); ?>
                                </span>
                                
                                <div class="product-actions">
                                    <button class="product-action-btn wishlist-btn <?php echo $is_in_wishlist ? 'active' : ''; ?>" 
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
                                
                                <!-- Rating -->
                                <?php if ($rating > 0): ?>
                                <div class="d-flex align-items-center mb-2">
                                    <div class="star-rating me-2">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="fas fa-star <?php echo $i <= floor($rating) ? 'text-warning' : 'text-muted'; ?>"></i>
                                        <?php endfor; ?>
                                    </div>
                                    <small class="text-muted">(<?php echo $review_count; ?>)</small>
                                </div>
                                <?php endif; ?>
                                
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
                                            data-product-price="<?php echo $discount_price > 0 ? $discount_price : $price; ?>"
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

                <!-- Pagination -->
                <?php if ($total_products > $per_page): 
                    $total_pages = ceil($total_products / $per_page);
                    $current_url = 'shop.php?' . http_build_query(array_merge($_GET, ['page' => '']));
                    $current_url = rtrim($current_url, '=');
                ?>
                <nav aria-label="Page navigation" class="mt-5">
                    <ul class="pagination pagination-custom justify-content-center">
                        <!-- Previous Button -->
                        <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                            <a class="page-link" href="<?php echo $current_url . ($page - 1); ?>" aria-label="Previous">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        </li>
                        
                        <!-- First Page -->
                        <?php if ($page > 3): ?>
                        <li class="page-item">
                            <a class="page-link" href="<?php echo $current_url . '1'; ?>">1</a>
                        </li>
                        <li class="page-item disabled">
                            <span class="page-link">...</span>
                        </li>
                        <?php endif; ?>
                        
                        <!-- Page Numbers -->
                        <?php 
                        $start = max(1, $page - 2);
                        $end = min($total_pages, $page + 2);
                        
                        for ($i = $start; $i <= $end; $i++): 
                        ?>
                        <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                            <a class="page-link" href="<?php echo $current_url . $i; ?>"><?php echo $i; ?></a>
                        </li>
                        <?php endfor; ?>
                        
                        <!-- Last Page -->
                        <?php if ($page < $total_pages - 2): ?>
                        <li class="page-item disabled">
                            <span class="page-link">...</span>
                        </li>
                        <li class="page-item">
                            <a class="page-link" href="<?php echo $current_url . $total_pages; ?>"><?php echo $total_pages; ?></a>
                        </li>
                        <?php endif; ?>
                        
                        <!-- Next Button -->
                        <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                            <a class="page-link" href="<?php echo $current_url . ($page + 1); ?>" aria-label="Next">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        </li>
                    </ul>
                </nav>
                <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-white py-5">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 mb-4">
                    <h5><i class="fas fa-shoe-prints"></i> <?php echo htmlspecialchars(SITE_NAME); ?></h5>
                    <p class="mt-3">Ethiopia's premier destination for authentic sneakers.</p>
                    <div class="social-links mt-3">
                        <a href="#" class="text-white me-3"><i class="fab fa-facebook-f fa-lg"></i></a>
                        <a href="#" class="text-white me-3"><i class="fab fa-twitter fa-lg"></i></a>
                        <a href="#" class="text-white me-3"><i class="fab fa-instagram fa-lg"></i></a>
                        <a href="#" class="text-white"><i class="fab fa-telegram fa-lg"></i></a>
                    </div>
                </div>
                <div class="col-lg-2 col-md-6 mb-4">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="index.php" class="text-white-50 text-decoration-none">Home</a></li>
                        <li class="mb-2"><a href="shop.php" class="text-white-50 text-decoration-none">Shop</a></li>
                        <li class="mb-2"><a href="about.php" class="text-white-50 text-decoration-none">About Us</a></li>
                        <li class="mb-2"><a href="contact.php" class="text-white-50 text-decoration-none">Contact</a></li>
                    </ul>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <h5>Customer Service</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="terms.php" class="text-white-50 text-decoration-none">Terms & Conditions</a></li>
                        <li class="mb-2"><a href="privacy.php" class="text-white-50 text-decoration-none">Privacy Policy</a></li>
                        <li class="mb-2"><a href="shipping.php" class="text-white-50 text-decoration-none">Shipping Information</a></li>
                        <li class="mb-2"><a href="returns.php" class="text-white-50 text-decoration-none">Return Policy</a></li>
                    </ul>
                </div>
                <div class="col-lg-3 mb-4">
                    <h5>Contact Us</h5>
                    <p class="mb-2"><i class="fas fa-map-marker-alt me-2"></i> Addis Ababa, Ethiopia</p>
                    <p class="mb-2"><i class="fas fa-phone me-2"></i> +251 911 123 456</p>
                    <p class="mb-2"><i class="fas fa-envelope me-2"></i> info@<?php echo strtolower(preg_replace('/[^a-zA-Z0-9]/', '', SITE_NAME)); ?>.et</p>
                </div>
            </div>
            <hr class="bg-light">
            <div class="row">
                <div class="col-md-6">
                    <p class="mb-0">&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars(SITE_NAME); ?>. All rights reserved.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="mb-2">Accepted Payment Methods:</p>
                    <i class="fab fa-cc-visa fa-2x me-2"></i>
                    <i class="fab fa-cc-mastercard fa-2x me-2"></i>
                    <i class="fas fa-university fa-2x me-2"></i>
                    <i class="fas fa-mobile-alt fa-2x"></i>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
    
    <script>
        // Store CSRF token for AJAX requests
        const csrfToken = '<?php echo $csrf_token; ?>';
        
        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            // Update badge counts initially
            updateBadgeCounts();
            
            // Initialize view toggle
            initViewToggle();
        });
        
        // Add to cart functionality - AJAX
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
                button.classList.add('btn-loading');
                button.disabled = true;
                
                // Send AJAX request to add to cart
                $.ajax({
                    url: '../includes/ajax-cart.php',
                    type: 'POST',
                    data: {
                        product_id: productId,
                        quantity: 1,
                        csrf_token: csrfToken,
                        action: 'add_to_cart'
                    },
                    success: function(response) {
                        try {
                            const data = typeof response === 'string' ? JSON.parse(response) : response;
                            if (data.success) {
                                showAlert('success', `${productName} added to cart!`);
                                updateBadgeCounts();
                            } else {
                                showAlert('error', data.message || 'Failed to add to cart');
                            }
                        } catch (e) {
                            console.error('JSON parse error:', e);
                            showAlert('error', 'Server response error');
                        }
                    },
                    error: function() {
                        showAlert('error', 'Failed to add to cart. Please try again.');
                    },
                    complete: function() {
                        // Restore button after 1.5 seconds
                        setTimeout(() => {
                            button.classList.remove('btn-loading');
                            button.innerHTML = originalText;
                            button.disabled = false;
                        }, 1500);
                    }
                });
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
                    button.classList.remove('active');
                    button.title = 'Add to Wishlist';
                } else {
                    icon.classList.remove('far');
                    icon.classList.add('fas');
                    button.classList.add('active');
                    button.title = 'Remove from Wishlist';
                }
                
                // Send AJAX request to update wishlist
                $.ajax({
                    url: '../includes/ajax-wishlist.php',
                    type: 'POST',
                    data: {
                        product_id: productId,
                        action: isInWishlist ? 'remove_from_wishlist' : 'add_to_wishlist',
                        csrf_token: csrfToken
                    },
                    success: function(response) {
                        try {
                            const data = typeof response === 'string' ? JSON.parse(response) : response;
                            if (data.success) {
                                showAlert('success', 
                                    isInWishlist ? 
                                    `${productName} removed from wishlist` : 
                                    `${productName} added to wishlist`
                                );
                                updateBadgeCounts();
                            } else {
                                // Revert on error
                                if (isInWishlist) {
                                    icon.classList.remove('far');
                                    icon.classList.add('fas');
                                    button.classList.add('active');
                                    button.title = 'Remove from Wishlist';
                                } else {
                                    icon.classList.remove('fas');
                                    icon.classList.add('far');
                                    button.classList.remove('active');
                                    button.title = 'Add to Wishlist';
                                }
                                showAlert('error', data.message || 'Failed to update wishlist');
                            }
                        } catch (e) {
                            console.error('JSON parse error:', e);
                            // Revert on error
                            if (isInWishlist) {
                                icon.classList.remove('far');
                                icon.classList.add('fas');
                                button.classList.add('active');
                                button.title = 'Remove from Wishlist';
                            } else {
                                icon.classList.remove('fas');
                                icon.classList.add('far');
                                button.classList.remove('active');
                                button.title = 'Add to Wishlist';
                            }
                            showAlert('error', 'Server response error');
                        }
                    },
                    error: function() {
                        // Revert on error
                        if (isInWishlist) {
                            icon.classList.remove('far');
                            icon.classList.add('fas');
                            button.classList.add('active');
                            button.title = 'Remove from Wishlist';
                        } else {
                            icon.classList.remove('fas');
                            icon.classList.add('far');
                            button.classList.remove('active');
                            button.title = 'Add to Wishlist';
                        }
                        showAlert('error', 'Failed to update wishlist. Please try again.');
                    }
                });
            }
            
            // Quick view button
            if (e.target.closest('.quick-view-btn')) {
                const button = e.target.closest('.quick-view-btn');
                const productId = button.dataset.productId;
                showQuickView(productId);
            }
        });
        
        // Update badge counts
        function updateBadgeCounts() {
            // Update cart count
            $.ajax({
                url: '../includes/ajax-cart.php',
                type: 'GET',
                data: { action: 'get_count' },
                success: function(response) {
                    try {
                        const data = typeof response === 'string' ? JSON.parse(response) : response;
                        if (data.success) {
                            updateCartBadge(data.count);
                        }
                    } catch (e) {
                        console.error('JSON parse error:', e);
                    }
                },
                error: function() {
                    console.error('Error updating cart count');
                }
            });
            
            // Update wishlist count
            $.ajax({
                url: '../includes/ajax-wishlist.php',
                type: 'GET',
                data: { action: 'get_count' },
                success: function(response) {
                    try {
                        const data = typeof response === 'string' ? JSON.parse(response) : response;
                        if (data.success) {
                            updateWishlistBadge(data.count);
                        }
                    } catch (e) {
                        console.error('JSON parse error:', e);
                    }
                },
                error: function() {
                    console.error('Error updating wishlist count');
                }
            });
        }
        
        // Update cart badge
        function updateCartBadge(count) {
            const cartBadge = document.getElementById('cart-badge');
            const cartIcon = document.querySelector('.fa-shopping-cart')?.parentElement;
            
            if (count > 0) {
                if (cartBadge) {
                    cartBadge.textContent = count;
                    cartBadge.style.display = 'block';
                } else if (cartIcon) {
                    const badge = document.createElement('span');
                    badge.id = 'cart-badge';
                    badge.className = 'position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger';
                    badge.textContent = count;
                    cartIcon.appendChild(badge);
                }
            } else if (cartBadge) {
                cartBadge.remove();
            }
        }
        
        // Update wishlist badge
        function updateWishlistBadge(count) {
            const wishlistBadge = document.getElementById('wishlist-badge');
            const wishlistIcon = document.querySelector('.fa-heart')?.parentElement;
            
            if (count > 0) {
                if (wishlistBadge) {
                    wishlistBadge.textContent = count;
                    wishlistBadge.style.display = 'block';
                } else if (wishlistIcon) {
                    const badge = document.createElement('span');
                    badge.id = 'wishlist-badge';
                    badge.className = 'position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger';
                    badge.textContent = count;
                    wishlistIcon.appendChild(badge);
                }
            } else if (wishlistBadge) {
                wishlistBadge.remove();
            }
        }
        
        // Show quick view modal
        function showQuickView(productId) {
            $.ajax({
                url: '../includes/ajax-product.php',
                type: 'GET',
                data: { action: 'quick_view', product_id: productId },
                success: function(response) {
                    try {
                        const data = typeof response === 'string' ? JSON.parse(response) : response;
                        if (data.success && data.product) {
                            const product = data.product;
                            
                            // Create modal HTML
                            const modalHtml = `
                                <div class="modal fade" id="quickViewModal" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-lg modal-dialog-centered">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">${product.name}</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <img src="${product.image}" class="img-fluid rounded" alt="${product.name}" style="max-height: 300px; object-fit: contain;">
                                                    </div>
                                                    <div class="col-md-6">
                                                        <h4 class="mb-2">${product.name}</h4>
                                                        <p class="text-muted mb-3">${product.brand}</p>
                                                        ${product.discount_price ? 
                                                            `<h3 class="text-danger">${product.discount_price_formatted}</h3>
                                                             <p class="text-muted"><del>${product.price_formatted}</del></p>` : 
                                                            `<h3 class="text-primary">${product.price_formatted}</h3>`
                                                        }
                                                        <p class="mb-3">${product.description ? product.description.substring(0, 150) + '...' : 'No description available.'}</p>
                                                        <div class="d-grid gap-2">
                                                            <a href="product-detail.php?id=${productId}" class="btn btn-primary">
                                                                <i class="fas fa-eye me-2"></i>View Full Details
                                                            </a>
                                                            <button class="btn btn-outline-primary add-to-cart-btn" 
                                                                    data-product-id="${productId}"
                                                                    data-product-name="${product.name}"
                                                                    data-product-price="${product.discount_price || product.price}">
                                                                <i class="fas fa-cart-plus me-2"></i>Add to Cart
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            `;
                            
                            // Remove existing modal
                            const existingModal = document.getElementById('quickViewModal');
                            if (existingModal) {
                                existingModal.remove();
                            }
                            
                            // Add modal to body
                            document.body.insertAdjacentHTML('beforeend', modalHtml);
                            
                            // Show modal
                            const modal = new bootstrap.Modal(document.getElementById('quickViewModal'));
                            modal.show();
                            
                            // Add event listener for add to cart button in modal
                            document.getElementById('quickViewModal').addEventListener('click', function(e) {
                                if (e.target.closest('.add-to-cart-btn')) {
                                    const button = e.target.closest('.add-to-cart-btn');
                                    const productId = button.dataset.productId;
                                    const productName = button.dataset.productName;
                                    
                                    // Close modal
                                    modal.hide();
                                    
                                    // Trigger add to cart
                                    const addToCartBtn = document.querySelector(`.add-to-cart-btn[data-product-id="${productId}"]`);
                                    if (addToCartBtn && !addToCartBtn.disabled) {
                                        addToCartBtn.click();
                                    }
                                }
                            });
                        } else {
                            showAlert('info', 'Quick view not available for this product');
                        }
                    } catch (e) {
                        console.error('JSON parse error:', e);
                        showAlert('info', 'Quick view feature coming soon!');
                    }
                },
                error: function() {
                    showAlert('info', 'Quick view feature coming soon!');
                }
            });
        }
        
        // Initialize view toggle
        function initViewToggle() {
            const viewToggleBtns = document.querySelectorAll('.view-toggle-btn');
            const productsGrid = document.getElementById('products-grid');
            
            viewToggleBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    const view = this.dataset.view;
                    
                    // Update active button
                    viewToggleBtns.forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                    
                    // Toggle view
                    if (view === 'list') {
                        productsGrid.classList.add('list-view');
                        productsGrid.classList.remove('row');
                        productsGrid.querySelectorAll('.product-grid-item').forEach(item => {
                            item.classList.remove('col-lg-4', 'col-md-6', 'mb-4');
                            item.classList.add('list-view-item');
                        });
                    } else {
                        productsGrid.classList.remove('list-view');
                        productsGrid.classList.add('row');
                        productsGrid.querySelectorAll('.product-grid-item').forEach(item => {
                            item.classList.add('col-lg-4', 'col-md-6', 'mb-4');
                            item.classList.remove('list-view-item');
                        });
                    }
                });
            });
        }
        
        // Alert system
        function showAlert(type, message) {
            // Remove existing alerts
            document.querySelectorAll('.custom-alert').forEach(alert => alert.remove());
            
            const alertDiv = document.createElement('div');
            alertDiv.className = `custom-alert alert alert-${type} alert-dismissible fade show`;
            
            const iconClass = {
                'success': 'fa-check-circle',
                'error': 'fa-exclamation-circle',
                'info': 'fa-info-circle',
                'warning': 'fa-exclamation-triangle'
            }[type] || 'fa-info-circle';
            
            alertDiv.innerHTML = `
                <div class="d-flex align-items-center">
                    <i class="fas ${iconClass} me-2"></i>
                    <div class="flex-grow-1">${message}</div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
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
        
        // Price form validation
        document.getElementById('price-form')?.addEventListener('submit', function(e) {
            const minPrice = document.getElementById('min_price').value;
            const maxPrice = document.getElementById('max_price').value;
            
            if (minPrice && maxPrice && parseFloat(minPrice) > parseFloat(maxPrice)) {
                e.preventDefault();
                showAlert('warning', 'Minimum price cannot be greater than maximum price');
                return false;
            }
        });
        
        // Search form submit with loading
        document.getElementById('search-form')?.addEventListener('submit', function(e) {
            const searchInput = this.querySelector('input[name="search"]');
            if (!searchInput.value.trim()) {
                e.preventDefault();
                return false;
            }
        });
    </script>
</body>
</html>