<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include required files
require_once '../config.php';
require_once '../functions.php';

// Check if product ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: shop.php');
    exit();
}

$product_id = intval($_GET['id']);

// Initialize Product class
$product = null;
$product_data = null;
try {
    require_once '../classes/Product.php';
    $product = new Product();
    $product_data = $product->getProductById($product_id);
} catch (Exception $e) {
    error_log("Error loading product: " . $e->getMessage());
    header('Location: shop.php');
    exit();
}

// Check if product exists
if (!$product_data) {
    header('Location: shop.php');
    exit();
}

$page_title = $product_data['name'] . ' - ' . SITE_NAME;

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

if (!function_exists('is_logged_in')) {
    function is_logged_in() {
        return isset($_SESSION['user_id']);
    }
}

if (!function_exists('get_product_condition_label')) {
    function get_product_condition_label($condition) {
        $labels = [
            'new' => 'New',
            'like_new' => 'Like New',
            'good' => 'Good',
            'fair' => 'Fair',
            'poor' => 'Poor'
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

// Handle add to cart from this page
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    $product_id = intval($_POST['product_id']);
    $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;
    $size = $_POST['size'] ?? '';
    $color = $_POST['color'] ?? '';
    
    // Check stock
    if ($product_data && ($product_data['stock_quantity'] ?? 0) >= $quantity) {
        // Initialize cart if not exists
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }
        
        // Check if product already exists in cart with same size and color
        $found = false;
        foreach ($_SESSION['cart'] as $key => $item) {
            if ($item['product_id'] == $product_id && 
                $item['size'] == $size && 
                $item['color'] == $color) {
                $_SESSION['cart'][$key]['quantity'] += $quantity;
                $found = true;
                break;
            }
        }
        
        // If not found, add new item
        if (!$found) {
            $_SESSION['cart'][] = [
                'product_id' => $product_id,
                'quantity' => $quantity,
                'size' => $size,
                'color' => $color
            ];
        }
        
        $cart_message = ['type' => 'success', 'text' => 'Product added to cart successfully!'];
    } else {
        $cart_message = ['type' => 'error', 'text' => 'Product is out of stock or quantity not available.'];
    }
}

// Get related products (same category)
$related_products = [];
try {
    if ($product && method_exists($product, 'getProductsByCategory')) {
        $related_products = $product->getProductsByCategory($product_data['category'], 4);
    } elseif ($product && method_exists($product, 'getRelatedProducts')) {
        $related_products = $product->getRelatedProducts($product_id, 4);
    }
} catch (Exception $e) {
    error_log("Error getting related products: " . $e->getMessage());
}

// Get product reviews
$reviews = [];
$review_count = 0;
$average_rating = 0;

try {
    if ($product && method_exists($product, 'getProductReviews')) {
        $reviews = $product->getProductReviews($product_id, 5);
        $review_count = count($reviews);
        // Calculate average rating from reviews
        if ($review_count > 0) {
            $total_rating = 0;
            foreach ($reviews as $review) {
                $total_rating += $review['rating'] ?? 0;
            }
            $average_rating = $total_rating / $review_count;
        }
    } else {
        // Fallback: Use product data ratings if available
        $average_rating = $product_data['rating'] ?? 0;
        $review_count = $product_data['review_count'] ?? 0;
    }
} catch (Exception $e) {
    error_log("Error getting reviews: " . $e->getMessage());
    $average_rating = $product_data['rating'] ?? 0;
    $review_count = $product_data['review_count'] ?? 0;
}

// Process product images
$product_images = [];
if (!empty($product_data['images'])) {
    if (is_string($product_data['images'])) {
        $images = json_decode($product_data['images'], true);
        if ($images && is_array($images)) {
            $product_images = $images;
        }
    } elseif (is_array($product_data['images'])) {
        $product_images = $product_data['images'];
    }
}

// If no images, use default
if (empty($product_images)) {
    $product_images = ['assets/images/products/default.jpg'];
}

// Get cart count
$cart_count = get_cart_count();

// Get wishlist count
$wishlist_count = 0;
if (isset($_SESSION['wishlist']) && is_array($_SESSION['wishlist'])) {
    $wishlist_count = count($_SESSION['wishlist']);
}

// Check if product is in wishlist
$is_in_wishlist = is_in_wishlist_session($product_id);

// Generate CSRF token
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Custom CSS -->
    <link href="../assets/css/style.css" rel="stylesheet">
    <link href="../assets/css/bootstrap-custom.css" rel="stylesheet">
    <!-- Lightbox CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.3/css/lightbox.min.css">
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
        
        .product-card {
            transition: transform 0.3s, box-shadow 0.3s;
            border: 1px solid #e0e0e0;
            height: 100%;
            background: white;
        }
        
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        
        .star-rating {
            color: #ffc107;
        }
        
        .thumbnail-image {
            cursor: pointer;
            transition: all 0.3s;
            border: 2px solid transparent;
        }
        
        .thumbnail-image.active {
            border-color: var(--primary-color) !important;
            box-shadow: 0 0 0 0.2rem rgba(22, 163, 74, 0.25);
        }
        
        .thumbnail-image:hover {
            opacity: 0.8;
            border-color: #dee2e6;
        }
        
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
        
        .breadcrumb {
            background-color: transparent;
            padding: 0.75rem 0;
            margin-bottom: 0;
        }
        
        .product-price-large {
            font-size: 2rem;
            font-weight: 800;
        }
        
        .btn-quantity {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .quantity-input {
            width: 60px;
            text-align: center;
            height: 40px;
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
        
        .text-primary {
            color: var(--primary-color) !important;
        }
        
        .text-danger {
            color: var(--danger-color) !important;
        }
        
        .bg-primary {
            background-color: var(--primary-color) !important;
        }
        
        .badge.bg-primary {
            background-color: var(--primary-color) !important;
        }
        
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
        
        @media (max-width: 768px) {
            body {
                padding-top: 70px;
            }
            
            .product-price-large {
                font-size: 1.5rem;
            }
            
            .custom-alert {
                top: 80px;
                left: 20px;
                right: 20px;
                min-width: auto;
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
                        <a class="nav-link" href="shop.php">Shop</a>
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
                               aria-label="Search" style="min-width: 200px;">
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
                    <?php if (is_logged_in()): ?>
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
                <li class="breadcrumb-item"><a href="shop.php">Shop</a></li>
                <?php if (!empty($product_data['category'])): ?>
                <li class="breadcrumb-item"><a href="shop.php?category=<?php echo urlencode($product_data['category']); ?>">
                    <?php echo htmlspecialchars($product_data['category']); ?>
                </a></li>
                <?php endif; ?>
                <li class="breadcrumb-item active"><?php echo htmlspecialchars($product_data['name']); ?></li>
            </ol>
        </div>
    </nav>

    <!-- Cart Message -->
    <?php if (isset($cart_message)): ?>
    <div class="container mt-3">
        <div class="alert alert-<?php echo $cart_message['type']; ?> alert-dismissible fade show">
            <i class="fas fa-<?php echo $cart_message['type'] === 'success' ? 'check-circle' : 'exclamation-circle'; ?> me-2"></i>
            <?php echo htmlspecialchars($cart_message['text']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    </div>
    <?php endif; ?>

    <!-- Product Detail -->
    <div class="container py-5">
        <form method="POST" action="" id="product-form">
            <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
            <div class="row">
                <!-- Product Images -->
                <div class="col-lg-6 mb-4">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <!-- Main Image -->
                            <div class="text-center mb-4">
                                <?php if (!empty($product_images[0])): ?>
                                <a href="../<?php echo $product_images[0]; ?>" data-lightbox="product-images" data-title="<?php echo htmlspecialchars($product_data['name']); ?>">
                                    <img src="../<?php echo $product_images[0]; ?>" 
                                         class="img-fluid rounded" 
                                         alt="<?php echo htmlspecialchars($product_data['name']); ?>"
                                         id="main-image"
                                         style="max-height: 400px; object-fit: contain;">
                                </a>
                                <?php else: ?>
                                <img src="../assets/images/products/default.jpg" 
                                     class="img-fluid rounded" 
                                     alt="No image available"
                                     style="max-height: 400px; object-fit: contain;">
                                <?php endif; ?>
                            </div>
                            
                            <!-- Thumbnail Images -->
                            <?php if (count($product_images) > 1): ?>
                            <div class="row g-2">
                                <?php foreach ($product_images as $index => $image): ?>
                                <div class="col-3">
                                    <img src="../<?php echo $image; ?>" 
                                         class="img-thumbnail thumbnail-image <?php echo $index == 0 ? 'active' : ''; ?>"
                                         alt="Thumbnail <?php echo $index + 1; ?>"
                                         style="cursor: pointer; height: 80px; object-fit: cover;"
                                         data-image="../<?php echo $image; ?>">
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Product Info -->
                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <!-- Product Title -->
                            <h1 class="h2 mb-2"><?php echo htmlspecialchars($product_data['name']); ?></h1>
                            
                            <!-- Brand and Category -->
                            <div class="d-flex align-items-center mb-3">
                                <?php if (!empty($product_data['brand'])): ?>
                                <span class="badge bg-light text-dark me-2"><?php echo htmlspecialchars($product_data['brand']); ?></span>
                                <?php endif; ?>
                                <?php if (!empty($product_data['category'])): ?>
                                <span class="badge bg-secondary"><?php echo htmlspecialchars($product_data['category']); ?></span>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Rating -->
                            <div class="d-flex align-items-center mb-3">
                                <div class="star-rating me-2">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="fas fa-star <?php echo $i <= floor($average_rating) ? 'text-warning' : 'text-muted'; ?>"></i>
                                    <?php endfor; ?>
                                </div>
                                <span class="text-muted">(<?php echo $review_count; ?> reviews)</span>
                            </div>
                            
                            <!-- Price -->
                            <div class="mb-4">
                                <?php if (!empty($product_data['discount_price']) && $product_data['discount_price'] > 0): 
                                    $discount_percentage = calculate_discount_percentage($product_data['price'], $product_data['discount_price']);
                                ?>
                                <div class="d-flex align-items-center flex-wrap">
                                    <h2 class="text-danger product-price-large mb-0"><?php echo format_price($product_data['discount_price']); ?></h2>
                                    <span class="text-muted text-decoration-line-through ms-3"><?php echo format_price($product_data['price']); ?></span>
                                    <?php if ($discount_percentage > 0): ?>
                                    <span class="badge bg-danger ms-3 fs-6">
                                        Save <?php echo $discount_percentage; ?>%
                                    </span>
                                    <?php endif; ?>
                                </div>
                                <?php else: ?>
                                <h2 class="text-primary product-price-large mb-0"><?php echo format_price($product_data['price']); ?></h2>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Stock Status -->
                            <div class="mb-4">
                                <?php $stock = $product_data['stock_quantity'] ?? 0; ?>
                                <span class="badge <?php echo $stock > 0 ? 'bg-success' : 'bg-danger'; ?> fs-6">
                                    <?php echo $stock > 0 ? $stock . ' in stock' : 'Out of stock'; ?>
                                </span>
                            </div>
                            
                            <!-- Size Selection -->
                            <?php if (!empty($product_data['size_range'])): ?>
                            <div class="mb-4">
                                <label class="form-label fw-bold">Select Size</label>
                                <div class="d-flex flex-wrap gap-2" id="size-selection">
                                    <?php
                                    $sizes = explode(',', $product_data['size_range']);
                                    foreach ($sizes as $index => $size):
                                        $size = trim($size);
                                        if (!empty($size)):
                                    ?>
                                    <div class="form-check m-0">
                                        <input class="form-check-input d-none" type="radio" name="size" 
                                               id="size-<?php echo htmlspecialchars($size); ?>" 
                                               value="<?php echo htmlspecialchars($size); ?>" 
                                               <?php echo $index === 0 ? 'checked' : ''; ?>
                                               required>
                                        <label class="form-check-label border rounded px-3 py-2 size-option" 
                                               for="size-<?php echo htmlspecialchars($size); ?>"
                                               style="cursor: pointer; min-width: 50px; text-align: center;">
                                            <?php echo htmlspecialchars($size); ?>
                                        </label>
                                    </div>
                                    <?php
                                        endif;
                                    endforeach;
                                    ?>
                                </div>
                                <div class="text-danger small mt-1" id="size-error"></div>
                            </div>
                            <?php endif; ?>
                            
                            <!-- Color Selection -->
                            <?php if (!empty($product_data['colors'])): ?>
                            <div class="mb-4">
                                <label class="form-label fw-bold">Select Color</label>
                                <div class="d-flex flex-wrap gap-2" id="color-selection">
                                    <?php
                                    $colors = explode(',', $product_data['colors']);
                                    foreach ($colors as $index => $color):
                                        $color = trim($color);
                                        if (!empty($color)):
                                    ?>
                                    <div class="form-check m-0">
                                        <input class="form-check-input d-none" type="radio" name="color" 
                                               id="color-<?php echo htmlspecialchars($color); ?>" 
                                               value="<?php echo htmlspecialchars($color); ?>"
                                               <?php echo $index === 0 ? 'checked' : ''; ?>
                                               required>
                                        <label class="form-check-label border rounded px-3 py-2 color-option" 
                                               for="color-<?php echo htmlspecialchars($color); ?>"
                                               style="cursor: pointer; min-width: 80px; text-align: center;">
                                            <?php echo htmlspecialchars($color); ?>
                                        </label>
                                    </div>
                                    <?php
                                        endif;
                                    endforeach;
                                    ?>
                                </div>
                                <div class="text-danger small mt-1" id="color-error"></div>
                            </div>
                            <?php endif; ?>
                            
                            <!-- Quantity -->
                            <div class="mb-4">
                                <label class="form-label fw-bold">Quantity</label>
                                <div class="d-flex align-items-center" style="width: 150px;">
                                    <button type="button" class="btn btn-outline-secondary btn-quantity" id="decrease-qty">-</button>
                                    <input type="number" class="form-control quantity-input border-left-0 border-right-0 rounded-0" 
                                           id="quantity" name="quantity" value="1" min="1" 
                                           max="<?php echo $product_data['stock_quantity'] ?? 10; ?>">
                                    <button type="button" class="btn btn-outline-secondary btn-quantity" id="increase-qty">+</button>
                                </div>
                            </div>
                            
                            <!-- Action Buttons -->
                            <div class="d-flex flex-wrap gap-3 mb-4">
                                <button type="submit" name="add_to_cart" class="btn btn-primary btn-lg flex-grow-1" 
                                        id="add-to-cart-btn"
                                        <?php echo ($product_data['stock_quantity'] ?? 0) <= 0 ? 'disabled' : ''; ?>>
                                    <i class="fas fa-cart-plus me-2"></i>
                                    <?php echo ($product_data['stock_quantity'] ?? 0) <= 0 ? 'Out of Stock' : 'Add to Cart'; ?>
                                </button>
                                
                                <button type="button" class="btn btn-outline-danger btn-lg px-4" id="add-to-wishlist-btn">
                                    <i class="<?php echo $is_in_wishlist ? 'fas' : 'far'; ?> fa-heart"></i>
                                </button>
                                
                                <button type="button" class="btn btn-outline-secondary btn-lg px-4" onclick="window.location.href='cart.php'">
                                    <i class="fas fa-shopping-cart"></i>
                                </button>
                            </div>
                            
                            <!-- Product Details -->
                            <div class="mb-4">
                                <h5 class="mb-3">Product Details</h5>
                                <div class="row">
                                    <?php if (!empty($product_data['sku'])): ?>
                                    <div class="col-md-6 mb-2">
                                        <strong>SKU:</strong> <?php echo htmlspecialchars($product_data['sku']); ?>
                                    </div>
                                    <?php endif; ?>
                                    <?php if (!empty($product_data['condition'])): ?>
                                    <div class="col-md-6 mb-2">
                                        <strong>Condition:</strong> <?php echo get_product_condition_label($product_data['condition']); ?>
                                    </div>
                                    <?php endif; ?>
                                    <?php if (!empty($product_data['brand'])): ?>
                                    <div class="col-md-6 mb-2">
                                        <strong>Brand:</strong> <?php echo htmlspecialchars($product_data['brand']); ?>
                                    </div>
                                    <?php endif; ?>
                                    <?php if (!empty($product_data['category'])): ?>
                                    <div class="col-md-6 mb-2">
                                        <strong>Category:</strong> <?php echo htmlspecialchars($product_data['category']); ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <!-- Product Description -->
                            <div class="mb-4">
                                <h5 class="mb-3">Description</h5>
                                <div class="product-description">
                                    <?php echo nl2br(htmlspecialchars($product_data['description'] ?? 'No description available.')); ?>
                                </div>
                            </div>
                            
                            <!-- Share Product -->
                            <div class="mb-4">
                                <h5 class="mb-3">Share Product</h5>
                                <div class="d-flex gap-2">
                                    <a href="#" class="btn btn-outline-primary btn-sm">
                                        <i class="fab fa-facebook-f"></i>
                                    </a>
                                    <a href="#" class="btn btn-outline-info btn-sm">
                                        <i class="fab fa-twitter"></i>
                                    </a>
                                    <a href="#" class="btn btn-outline-danger btn-sm">
                                        <i class="fab fa-instagram"></i>
                                    </a>
                                    <a href="#" class="btn btn-outline-secondary btn-sm">
                                        <i class="fas fa-link"></i> Copy Link
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>

        <!-- Related Products -->
        <?php if (!empty($related_products)): ?>
        <div class="row mt-5">
            <div class="col-12">
                <h3 class="mb-4">Related Products</h3>
                <div class="row">
                    <?php foreach ($related_products as $related): ?>
                        <?php if ($related['id'] != $product_id): 
                            $related_images = [];
                            if (!empty($related['images'])) {
                                if (is_string($related['images'])) {
                                    $images = json_decode($related['images'], true);
                                    if ($images && is_array($images)) {
                                        $related_images = $images;
                                    }
                                } elseif (is_array($related['images'])) {
                                    $related_images = $related['images'];
                                }
                            }
                            $related_image = !empty($related_images[0]) ? $related_images[0] : 'assets/images/products/default.jpg';
                        ?>
                        <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                            <div class="card product-card h-100">
                                <div class="position-relative">
                                    <a href="product-detail.php?id=<?php echo $related['id']; ?>">
                                        <img src="../<?php echo $related_image; ?>" 
                                             class="card-img-top" alt="<?php echo htmlspecialchars($related['name']); ?>"
                                             style="height: 200px; object-fit: cover;">
                                    </a>
                                    <?php if (!empty($related['discount_price']) && $related['discount_price'] > 0): 
                                        $discount_percentage = calculate_discount_percentage($related['price'], $related['discount_price']);
                                    ?>
                                    <span class="badge bg-danger position-absolute top-0 start-0 m-2">
                                        -<?php echo $discount_percentage; ?>%
                                    </span>
                                    <?php endif; ?>
                                </div>
                                <div class="card-body">
                                    <h6 class="card-title">
                                        <a href="product-detail.php?id=<?php echo $related['id']; ?>" class="text-decoration-none text-dark">
                                            <?php echo htmlspecialchars($related['name']); ?>
                                        </a>
                                    </h6>
                                    <p class="card-text text-muted small mb-2"><?php echo htmlspecialchars($related['brand'] ?? ''); ?></p>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <?php if (!empty($related['discount_price']) && $related['discount_price'] > 0): ?>
                                        <div>
                                            <span class="fw-bold text-danger"><?php echo format_price($related['discount_price']); ?></span>
                                            <small class="text-muted text-decoration-line-through d-block"><?php echo format_price($related['price']); ?></small>
                                        </div>
                                        <?php else: ?>
                                        <span class="fw-bold"><?php echo format_price($related['price']); ?></span>
                                        <?php endif; ?>
                                        <a href="product-detail.php?id=<?php echo $related['id']; ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
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

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Lightbox JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.3/js/lightbox.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
    
    <script>
        // Store CSRF token and product info
        const csrfToken = '<?php echo $csrf_token; ?>';
        const productId = <?php echo $product_id; ?>;
        const productName = '<?php echo addslashes($product_data['name']); ?>';
        const productStock = <?php echo $product_data['stock_quantity'] ?? 0; ?>;
        const productPrice = <?php echo !empty($product_data['discount_price']) ? $product_data['discount_price'] : $product_data['price']; ?>;
        
        // Thumbnail image click
        document.querySelectorAll('.thumbnail-image').forEach(thumb => {
            thumb.addEventListener('click', function() {
                const mainImage = document.getElementById('main-image');
                const lightboxLink = mainImage.parentElement;
                
                // Update main image
                mainImage.src = this.dataset.image;
                if (lightboxLink) {
                    lightboxLink.href = this.dataset.image;
                }
                
                // Update active thumbnail
                document.querySelectorAll('.thumbnail-image').forEach(img => {
                    img.classList.remove('active');
                });
                this.classList.add('active');
            });
        });
        
        // Quantity controls
        document.getElementById('increase-qty').addEventListener('click', function() {
            const quantityInput = document.getElementById('quantity');
            const max = parseInt(quantityInput.max);
            let current = parseInt(quantityInput.value);
            if (current < max) {
                quantityInput.value = current + 1;
            }
        });
        
        document.getElementById('decrease-qty').addEventListener('click', function() {
            const quantityInput = document.getElementById('quantity');
            const min = parseInt(quantityInput.min);
            let current = parseInt(quantityInput.value);
            if (current > min) {
                quantityInput.value = current - 1;
            }
        });
        
        // Size and color selection styling
        document.querySelectorAll('.size-option').forEach(option => {
            option.addEventListener('click', function() {
                const input = this.previousElementSibling;
                input.checked = true;
                
                // Update styling
                document.querySelectorAll('.size-option').forEach(opt => {
                    opt.style.borderColor = '#dee2e6';
                    opt.style.backgroundColor = 'white';
                });
                this.style.borderColor = 'var(--primary-color)';
                this.style.backgroundColor = 'rgba(22, 163, 74, 0.1)';
                
                // Clear error
                document.getElementById('size-error').textContent = '';
            });
        });
        
        document.querySelectorAll('.color-option').forEach(option => {
            option.addEventListener('click', function() {
                const input = this.previousElementSibling;
                input.checked = true;
                
                // Update styling
                document.querySelectorAll('.color-option').forEach(opt => {
                    opt.style.borderColor = '#dee2e6';
                    opt.style.backgroundColor = 'white';
                });
                this.style.borderColor = 'var(--primary-color)';
                this.style.backgroundColor = 'rgba(22, 163, 74, 0.1)';
                
                // Clear error
                document.getElementById('color-error').textContent = '';
            });
        });
        
        // Initialize selection styling
        document.addEventListener('DOMContentLoaded', function() {
            // Size selection
            const selectedSize = document.querySelector('input[name="size"]:checked');
            if (selectedSize && selectedSize.nextElementSibling) {
                selectedSize.nextElementSibling.style.borderColor = 'var(--primary-color)';
                selectedSize.nextElementSibling.style.backgroundColor = 'rgba(22, 163, 74, 0.1)';
            }
            
            // Color selection
            const selectedColor = document.querySelector('input[name="color"]:checked');
            if (selectedColor && selectedColor.nextElementSibling) {
                selectedColor.nextElementSibling.style.borderColor = 'var(--primary-color)';
                selectedColor.nextElementSibling.style.backgroundColor = 'rgba(22, 163, 74, 0.1)';
            }
        });
        
        // Add to wishlist - AJAX version
        document.getElementById('add-to-wishlist-btn').addEventListener('click', function() {
            const button = this;
            const icon = button.querySelector('i');
            const isInWishlist = icon.classList.contains('fas');
            
            // Toggle icon immediately for better UX
            if (isInWishlist) {
                icon.classList.remove('fas');
                icon.classList.add('far');
            } else {
                icon.classList.remove('far');
                icon.classList.add('fas');
            }
            
            // Send AJAX request
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
                            updateWishlistBadge(data.count || 0);
                        } else {
                            // Revert on error
                            if (isInWishlist) {
                                icon.classList.remove('far');
                                icon.classList.add('fas');
                            } else {
                                icon.classList.remove('fas');
                                icon.classList.add('far');
                            }
                            showAlert('error', data.message || 'Failed to update wishlist');
                        }
                    } catch (e) {
                        console.error('JSON parse error:', e);
                        // Revert on error
                        if (isInWishlist) {
                            icon.classList.remove('far');
                            icon.classList.add('fas');
                        } else {
                            icon.classList.remove('fas');
                            icon.classList.add('far');
                        }
                        showAlert('error', 'Server response error');
                    }
                },
                error: function() {
                    // Revert on error
                    if (isInWishlist) {
                        icon.classList.remove('far');
                        icon.classList.add('fas');
                    } else {
                        icon.classList.remove('fas');
                        icon.classList.add('far');
                    }
                    showAlert('error', 'Failed to update wishlist. Please try again.');
                }
            });
        });
        
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
        
        // AJAX add to cart - prevent page reload
        document.getElementById('product-form').addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Validate size selection
            <?php if (!empty($product_data['size_range'])): ?>
            const sizeSelected = document.querySelector('input[name="size"]:checked');
            if (!sizeSelected) {
                document.getElementById('size-error').textContent = 'Please select a size';
                return false;
            }
            <?php endif; ?>
            
            // Validate color selection
            <?php if (!empty($product_data['colors'])): ?>
            const colorSelected = document.querySelector('input[name="color"]:checked');
            if (!colorSelected) {
                document.getElementById('color-error').textContent = 'Please select a color';
                return false;
            }
            <?php endif; ?>
            
            const quantity = document.getElementById('quantity').value;
            const addToCartBtn = document.getElementById('add-to-cart-btn');
            
            if (productStock <= 0) {
                showAlert('error', `${productName} is out of stock`);
                return false;
            }
            
            // Show loading state
            const originalText = addToCartBtn.innerHTML;
            addToCartBtn.classList.add('btn-loading');
            addToCartBtn.disabled = true;
            
            // Get form data
            const formData = new FormData(this);
            
            // Send AJAX request
            $.ajax({
                url: '../includes/ajax-cart.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    try {
                        const data = typeof response === 'string' ? JSON.parse(response) : response;
                        if (data.success) {
                            showAlert('success', `${productName} added to cart!`);
                            updateCartBadge(data.count || 0);
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
                        addToCartBtn.classList.remove('btn-loading');
                        addToCartBtn.innerHTML = originalText;
                        addToCartBtn.disabled = false;
                    }, 1500);
                }
            });
            
            return false;
        });
        
        // Show alert message
        function showAlert(type, message) {
            // Remove any existing alerts
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
        
        // Share functionality
        document.querySelector('.btn-outline-secondary[href="#"]').addEventListener('click', function(e) {
            e.preventDefault();
            const url = window.location.href;
            navigator.clipboard.writeText(url).then(() => {
                showAlert('success', 'Link copied to clipboard!');
            }).catch(err => {
                showAlert('error', 'Failed to copy link');
            });
        });
    </script>
</body>
</html>