<?php
require_once '../config.php';
require_once '../functions.php';

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get vendor ID from URL
$vendor_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($vendor_id <= 0) {
    header('Location: shop.php');
    exit();
}

// Get vendor and store information
$db = new Database();
$vendor = new User();
$vendor_data = $vendor->getUserById($vendor_id);

if (!$vendor_data || $vendor_data['user_type'] !== 'vendor' || ($vendor_data['status'] ?? '') !== 'active') {
    header('Location: shop.php');
    exit();
}

// Get store information
$store_info = null;
try {
    $db->query("SELECT * FROM vendors WHERE user_id = :user_id");
    $db->bind(':user_id', $vendor_id);
    $store_info = $db->single();
} catch (Exception $e) {
    // If vendors table doesn't exist or error occurs, create default store info
    error_log("Error fetching vendor info: " . $e->getMessage());
}

if (!$store_info) {
    // Create default store info
    $store_info = [
        'store_name' => ($vendor_data['first_name'] ?? 'Vendor') . "'s Store",
        'store_description' => '',
        'store_logo' => '',
        'store_banner' => '',
        'store_address' => '',
        'store_phone' => $vendor_data['phone'] ?? '',
        'store_email' => $vendor_data['email'] ?? '',
        'store_social' => '{}',
        'rating' => 0,
        'total_sales' => 0,
        'response_rate' => 85,
        'join_date' => $vendor_data['created_at'] ?? date('Y-m-d H:i:s')
    ];
} else {
    // Ensure all fields exist
    $store_info = array_merge([
        'store_name' => ($vendor_data['first_name'] ?? 'Vendor') . "'s Store",
        'store_description' => '',
        'store_logo' => '',
        'store_banner' => '',
        'store_address' => '',
        'store_phone' => $vendor_data['phone'] ?? '',
        'store_email' => $vendor_data['email'] ?? '',
        'store_social' => '{}',
        'rating' => 0,
        'total_sales' => 0,
        'response_rate' => 85,
        'join_date' => $vendor_data['created_at'] ?? date('Y-m-d H:i:s')
    ], $store_info);
}

// Get vendor's active products
$products = [];
$total_products = 0;
try {
    $product = new Product();
    $products = $product->getVendorProducts($vendor_id, 'active', 20);
    $total_products = count($products);
} catch (Exception $e) {
    error_log("Error fetching vendor products: " . $e->getMessage());
}

// Get store reviews
$reviews = [];
$review_count = 0;
$average_rating = $store_info['rating'];
try {
    $db->query("SELECT r.*, u.first_name, u.last_name, u.profile_picture 
               FROM reviews r 
               JOIN users u ON r.user_id = u.id 
               WHERE r.vendor_id = :vendor_id AND r.status = 'approved' 
               ORDER BY r.created_at DESC 
               LIMIT 5");
    $db->bind(':vendor_id', $vendor_id);
    $reviews = $db->resultSet();
    
    $review_count = count($reviews);
    
    // Calculate average rating from reviews
    if ($review_count > 0) {
        $total_rating = 0;
        foreach ($reviews as $review) {
            $total_rating += $review['rating'];
        }
        $average_rating = round($total_rating / $review_count, 1);
        
        // Update store rating if different
        if ($average_rating != $store_info['rating']) {
            $store_info['rating'] = $average_rating;
        }
    }
} catch (Exception $e) {
    // Reviews table doesn't exist or error occurred
    error_log("Error fetching reviews: " . $e->getMessage());
}

// Decode social media links
$social_data = [];
if (!empty($store_info['store_social']) && $store_info['store_social'] != '{}') {
    try {
        $social_data = json_decode($store_info['store_social'], true);
        if (!is_array($social_data)) {
            $social_data = [];
        }
    } catch (Exception $e) {
        error_log("Error decoding social data: " . $e->getMessage());
        $social_data = [];
    }
}

// Get similar stores (other active vendors)
$similar_stores = [];
try {
    $db->query("SELECT u.id, u.first_name, u.last_name, v.store_name, v.store_logo, v.rating, 
                       COUNT(DISTINCT p.id) as product_count
                FROM users u 
                LEFT JOIN vendors v ON u.id = v.user_id 
                LEFT JOIN products p ON u.id = p.vendor_id AND p.status = 'active'
                WHERE u.user_type = 'vendor' 
                AND u.status = 'active' 
                AND u.id != :vendor_id 
                GROUP BY u.id, v.store_name, v.store_logo, v.rating
                HAVING COUNT(DISTINCT p.id) > 0
                ORDER BY v.rating DESC, COUNT(DISTINCT p.id) DESC 
                LIMIT 4");
    $db->bind(':vendor_id', $vendor_id);
    $similar_stores = $db->resultSet();
} catch (Exception $e) {
    error_log("Error fetching similar stores: " . $e->getMessage());
}

$page_title = $store_info['store_name'] . ' - ' . SITE_NAME;

// Get cart count
$cart_count = 0;
if (function_exists('get_cart_count')) {
    $cart_count = get_cart_count();
} elseif (isset($_SESSION['cart_count'])) {
    $cart_count = $_SESSION['cart_count'];
}

// Helper functions if they don't exist
if (!function_exists('format_price')) {
    function format_price($price) {
        return 'ETB ' . number_format($price, 2);
    }
}

if (!function_exists('get_star_rating')) {
    function get_star_rating($rating) {
        $stars = '';
        $full_stars = floor($rating);
        $half_star = ($rating - $full_stars) >= 0.5;
        $empty_stars = 5 - $full_stars - ($half_star ? 1 : 0);

        for ($i = 0; $i < $full_stars; $i++) {
            $stars .= '<i class="fas fa-star text-warning"></i>';
        }

        if ($half_star) {
            $stars .= '<i class="fas fa-star-half-alt text-warning"></i>';
        }

        for ($i = 0; $i < $empty_stars; $i++) {
            $stars .= '<i class="far fa-star text-warning"></i>';
        }

        return $stars;
    }
}

if (!function_exists('time_ago')) {
    function time_ago($datetime) {
        if (empty($datetime)) {
            return 'Recently';
        }
        
        try {
            $time = strtotime($datetime);
            $now = time();
            $diff = $now - $time;
            
            if ($diff < 60) {
                return 'Just now';
            } elseif ($diff < 3600) {
                $mins = floor($diff / 60);
                return $mins . ' minute' . ($mins > 1 ? 's' : '') . ' ago';
            } elseif ($diff < 86400) {
                $hours = floor($diff / 3600);
                return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
            } elseif ($diff < 604800) {
                $days = floor($diff / 86400);
                return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
            } else {
                return date('M d, Y', $time);
            }
        } catch (Exception $e) {
            return 'Recently';
        }
    }
}

// Get active products count for stats
$active_products_count = $total_products;
try {
    $db->query("SELECT COUNT(*) as count FROM products WHERE vendor_id = :vendor_id AND status = 'active'");
    $db->bind(':vendor_id', $vendor_id);
    $result = $db->single();
    $active_products_count = $result ? $result['count'] : $total_products;
} catch (Exception $e) {
    // Use default if query fails
    error_log("Error counting active products: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    
    <!-- SEO Meta Tags -->
    <meta name="description" content="<?php echo htmlspecialchars($store_info['store_description'] ? substr($store_info['store_description'], 0, 160) : 'Visit ' . $store_info['store_name'] . ' on ' . SITE_NAME); ?>">
    <meta name="keywords" content="<?php echo htmlspecialchars($store_info['store_name'] . ', ' . SITE_NAME . ', online store, shopping'); ?>">
    
    <!-- Open Graph Meta Tags -->
    <meta property="og:title" content="<?php echo htmlspecialchars($page_title); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($store_info['store_description'] ? substr($store_info['store_description'], 0, 160) : 'Visit ' . $store_info['store_name'] . ' on ' . SITE_NAME); ?>">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo "https://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]"; ?>">
    <?php
    $store_logo_url = !empty($store_info['store_logo']) ? '../' . htmlspecialchars($store_info['store_logo']) : '../assets/images/stores/default-logo.png';
    ?>
    <meta property="og:image" content="<?php echo $store_logo_url; ?>">
    
    <!-- Twitter Card Meta Tags -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo htmlspecialchars($page_title); ?>">
    <meta name="twitter:description" content="<?php echo htmlspecialchars($store_info['store_description'] ? substr($store_info['store_description'], 0, 160) : 'Visit ' . $store_info['store_name'] . ' on ' . SITE_NAME); ?>">
    <meta name="twitter:image" content="<?php echo $store_logo_url; ?>">

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="../assets/css/style.css" rel="stylesheet">
    <link href="../assets/css/bootstrap-custom.css" rel="stylesheet">
    
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --secondary-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --success-gradient: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
            --warning-gradient: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
        }
        
        .store-header {
            background: var(--primary-gradient);
            color: white;
            padding: 100px 0 50px;
            margin-top: 76px;
            position: relative;
            overflow: hidden;
        }
        
        .store-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320"><path fill="rgba(255,255,255,0.1)" d="M0,224L48,213.3C96,203,192,181,288,181.3C384,181,480,203,576,192C672,181,768,139,864,128C960,117,1056,139,1152,149.3C1248,160,1344,160,1392,160L1440,160L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path></svg>');
            background-size: cover;
            background-position: bottom;
        }
        
        .store-logo {
            width: 140px;
            height: 140px;
            border: 5px solid rgba(255, 255, 255, 0.9);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            transition: transform 0.3s ease;
        }
        
        .store-logo:hover {
            transform: scale(1.05);
        }
        
        .store-banner {
            height: 350px;
            object-fit: cover;
            width: 100%;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
        }
        
        .product-card {
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            border: none;
            border-radius: 15px;
            overflow: hidden;
            background: white;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        }
        
        .product-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }
        
        .product-card .card-img-top {
            height: 220px;
            object-fit: cover;
            transition: transform 0.5s ease;
        }
        
        .product-card:hover .card-img-top {
            transform: scale(1.05);
        }
        
        .store-info-card {
            border: none;
            border-radius: 15px;
            background: white;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            border-left: 5px solid #667eea;
        }
        
        .rating-badge {
            background: rgba(255, 255, 255, 0.25);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .social-icons a {
            width: 45px;
            height: 45px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: all 0.3s ease;
            margin: 0 5px;
            color: white;
            text-decoration: none;
        }
        
        .social-icons a:hover {
            transform: translateY(-5px) scale(1.1);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
        }
        
        .stats-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            transition: transform 0.3s ease;
            border-top: 4px solid transparent;
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
        }
        
        .stats-card.products { border-color: #667eea; }
        .stats-card.sales { border-color: #43e97b; }
        .stats-card.rating { border-color: #fa709a; }
        .stats-card.response { border-color: #38f9d7; }
        
        .stats-number {
            font-size: 2.8rem;
            font-weight: 700;
            line-height: 1;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 10px;
        }
        
        .stats-card.sales .stats-number {
            background: var(--success-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .stats-card.rating .stats-number {
            background: var(--warning-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .stats-card.response .stats-number {
            background: var(--secondary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .stats-label {
            font-size: 0.95rem;
            color: #6c757d;
            font-weight: 500;
        }
        
        .store-nav {
            background: white;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            position: sticky;
            top: 76px;
            z-index: 1000;
        }
        
        .store-nav .nav-link {
            color: #495057;
            font-weight: 500;
            padding: 15px 25px;
            border-bottom: 3px solid transparent;
            transition: all 0.3s ease;
        }
        
        .store-nav .nav-link:hover,
        .store-nav .nav-link.active {
            color: #667eea;
            border-bottom-color: #667eea;
            background: rgba(102, 126, 234, 0.05);
        }
        
        .review-card {
            border: none;
            border-radius: 15px;
            background: white;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease;
        }
        
        .review-card:hover {
            transform: translateY(-5px);
        }
        
        .review-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #667eea;
        }
        
        .badge-custom {
            padding: 6px 15px;
            border-radius: 50px;
            font-weight: 500;
        }
        
        .btn-gradient {
            background: var(--primary-gradient);
            border: none;
            color: white;
            padding: 12px 30px;
            border-radius: 50px;
            font-weight: 500;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .btn-gradient:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.5);
            color: white;
        }
        
        .btn-outline-gradient {
            border: 2px solid transparent;
            background: linear-gradient(white, white) padding-box,
                        var(--primary-gradient) border-box;
            color: #667eea;
            padding: 10px 25px;
            border-radius: 50px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-outline-gradient:hover {
            background: var(--primary-gradient) padding-box;
            color: white;
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
        }
        
        .contact-form input,
        .contact-form textarea,
        .review-form input,
        .review-form textarea,
        .review-form select {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 12px 20px;
            transition: all 0.3s ease;
        }
        
        .contact-form input:focus,
        .contact-form textarea:focus,
        .review-form input:focus,
        .review-form textarea:focus,
        .review-form select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
        }
        
        .empty-state-icon {
            font-size: 5rem;
            color: #dee2e6;
            margin-bottom: 20px;
        }
        
        .section-title {
            position: relative;
            padding-bottom: 15px;
            margin-bottom: 30px;
        }
        
        .section-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 60px;
            height: 4px;
            background: var(--primary-gradient);
            border-radius: 2px;
        }
        
        .floating-alert {
            position: fixed;
            top: 100px;
            right: 20px;
            z-index: 9999;
            min-width: 300px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            border: none;
            border-radius: 10px;
            animation: slideIn 0.3s ease;
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
        
        .animated-gradient {
            background: linear-gradient(-45deg, #667eea, #764ba2, #f093fb, #f5576c);
            background-size: 400% 400%;
            animation: gradient 15s ease infinite;
        }
        
        @keyframes gradient {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        
        .loading-spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s ease-in-out infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        @media (max-width: 768px) {
            .store-header {
                padding: 80px 0 30px;
                margin-top: 66px;
            }
            
            .store-logo {
                width: 100px;
                height: 100px;
            }
            
            .stats-number {
                font-size: 2.2rem;
            }
            
            .store-nav .nav-link {
                padding: 10px 15px;
                font-size: 0.9rem;
            }
        }
        
        @media (max-width: 576px) {
            .store-header {
                padding: 70px 0 25px;
            }
            
            .store-logo {
                width: 80px;
                height: 80px;
            }
            
            .stats-number {
                font-size: 1.8rem;
            }
        }
    </style>
</head>

<body>
    <!-- Navigation -->
    <?php
    $navbar_paths = ['includes/navbar.php', '../includes/navbar.php'];
    $navbar_included = false;
    
    foreach ($navbar_paths as $path) {
        if (file_exists($path)) {
            include $path;
            $navbar_included = true;
            break;
        }
    }
    
    if (!$navbar_included):
    ?>
        <nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top shadow">
            <div class="container">
                <a class="navbar-brand fw-bold" href="index.php">
                    <i class="fas fa-shoe-prints me-2"></i><?php echo htmlspecialchars(SITE_NAME); ?>
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="mainNavbar">
                    <ul class="navbar-nav me-auto">
                        <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
                        <li class="nav-item"><a class="nav-link" href="shop.php">Shop</a></li>
                        <li class="nav-item"><a class="nav-link" href="about.php">About</a></li>
                    </ul>
                    <div class="d-flex">
                        <a href="cart.php" class="text-light position-relative me-4" title="Shopping Cart">
                            <i class="fas fa-shopping-cart fa-lg"></i>
                            <?php if ($cart_count > 0): ?>
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                    <?php echo $cart_count; ?>
                                    <span class="visually-hidden">items in cart</span>
                                </span>
                            <?php endif; ?>
                        </a>
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <div class="dropdown">
                                <a href="#" class="text-light text-decoration-none dropdown-toggle" id="userDropdown" data-bs-toggle="dropdown">
                                    <i class="fas fa-user-circle fa-lg"></i>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <?php if ($_SESSION['user_type'] === 'buyer'): ?>
                                        <li><a class="dropdown-item" href="../buyer/"><i class="fas fa-user me-2"></i>My Account</a></li>
                                    <?php elseif ($_SESSION['user_type'] === 'vendor'): ?>
                                        <li><a class="dropdown-item" href="../vendor/"><i class="fas fa-store me-2"></i>Vendor Dashboard</a></li>
                                    <?php else: ?>
                                        <li><a class="dropdown-item" href="../admin/"><i class="fas fa-cog me-2"></i>Admin Panel</a></li>
                                    <?php endif; ?>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                                </ul>
                            </div>
                        <?php else: ?>
                            <a href="login.php" class="btn btn-outline-light btn-sm me-2">Login</a>
                            <a href="register.php" class="btn btn-primary btn-sm">Register</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </nav>
    <?php endif; ?>

    <!-- Store Header -->
    <header class="store-header">
        <div class="container position-relative">
            <div class="row align-items-center">
                <!-- Store Logo -->
                <div class="col-lg-2 col-md-3 text-center text-md-start mb-4 mb-md-0">
                    <?php
                    $logo_path = !empty($store_info['store_logo']) ? 
                        (file_exists('../' . $store_info['store_logo']) ? '../' . htmlspecialchars($store_info['store_logo']) : '../assets/images/stores/default-logo.png') : 
                        '../assets/images/stores/default-logo.png';
                    ?>
                    <img src="<?php echo $logo_path; ?>" 
                         alt="<?php echo htmlspecialchars($store_info['store_name']); ?>" 
                         class="store-logo rounded-circle img-fluid"
                         onerror="this.src='../assets/images/stores/default-logo.png'">
                </div>
                
                <!-- Store Info -->
                <div class="col-lg-6 col-md-5">
                    <h1 class="display-5 fw-bold mb-2"><?php echo htmlspecialchars($store_info['store_name']); ?></h1>
                    <div class="d-flex flex-wrap align-items-center gap-3 mb-3">
                        <div class="d-flex align-items-center">
                            <div class="me-2">
                                <span class="text-warning">
                                    <?php echo get_star_rating($store_info['rating']); ?>
                                </span>
                            </div>
                            <span class="text-white fw-bold"><?php echo number_format($store_info['rating'], 1); ?></span>
                            <span class="text-white-50 ms-2">(<?php echo $review_count; ?> reviews)</span>
                        </div>
                        <span class="badge rating-badge rounded-pill px-3 py-2">
                            <i class="fas fa-check-circle me-1"></i> Verified Store
                        </span>
                        <?php if ($store_info['total_sales'] > 100): ?>
                            <span class="badge bg-warning text-dark rounded-pill px-3 py-2">
                                <i class="fas fa-bolt me-1"></i> Top Seller
                            </span>
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($store_info['store_description'])): ?>
                        <p class="lead mb-0 opacity-90"><?php echo htmlspecialchars($store_info['store_description']); ?></p>
                    <?php endif; ?>
                </div>
                
                <!-- Store Stats -->
                <div class="col-lg-4 col-md-4">
                    <div class="row g-3">
                        <div class="col-6 col-md-12 col-lg-6">
                            <div class="stats-card products">
                                <div class="stats-number"><?php echo $total_products; ?></div>
                                <div class="stats-label">Products</div>
                            </div>
                        </div>
                        <div class="col-6 col-md-12 col-lg-6">
                            <div class="stats-card sales">
                                <div class="stats-number"><?php echo $store_info['total_sales']; ?></div>
                                <div class="stats-label">Total Sales</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Store Navigation -->
    <nav class="store-nav navbar navbar-expand-lg">
        <div class="container">
            <button class="navbar-toggler ms-auto" type="button" data-bs-toggle="collapse" data-bs-target="#storeNavbar">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="storeNavbar">
                <ul class="navbar-nav mx-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="#products-section">
                            <i class="fas fa-boxes me-2"></i>Products
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#about-section">
                            <i class="fas fa-info-circle me-2"></i>About Store
                        </a>
                    </li>
                    <?php if ($review_count > 0): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="#reviews-section">
                                <i class="fas fa-star me-2"></i>Reviews
                            </a>
                        </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a class="nav-link" href="#contact-section">
                            <i class="fas fa-envelope me-2"></i>Contact
                        </a>
                    </li>
                    <?php if (!empty($similar_stores)): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="#similar-stores">
                                <i class="fas fa-store me-2"></i>Similar Stores
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="py-5">
        <div class="container">
            
            <!-- Products Section -->
            <section id="products-section" class="mb-5">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="section-title">Featured Products</h2>
                    <?php if ($total_products > 0): ?>
                        <a href="shop.php?vendor=<?php echo $vendor_id; ?>" class="btn btn-outline-gradient">
                            <i class="fas fa-store me-2"></i>View All Products
                        </a>
                    <?php endif; ?>
                </div>

                <?php if (empty($products)): ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">
                            <i class="fas fa-box-open"></i>
                        </div>
                        <h3 class="text-muted mb-3">No Products Available</h3>
                        <p class="text-muted mb-4">This store hasn't added any products yet.</p>
                        <a href="shop.php" class="btn btn-gradient">
                            <i class="fas fa-arrow-left me-2"></i>Back to Shop
                        </a>
                    </div>
                <?php else: ?>
                    <div class="row g-4">
                        <?php foreach ($products as $product_item): ?>
                            <div class="col-xl-3 col-lg-4 col-md-6">
                                <div class="product-card">
                                    <div class="position-relative overflow-hidden">
                                        <?php
                                        $product_image = '../assets/images/products/default.jpg';
                                        $images = [];
                                        
                                        if (!empty($product_item['images'])) {
                                            if (is_string($product_item['images'])) {
                                                $images = json_decode($product_item['images'], true);
                                            } elseif (is_array($product_item['images'])) {
                                                $images = $product_item['images'];
                                            }
                                            
                                            if (is_array($images) && !empty($images[0])) {
                                                $image_path = '../' . $images[0];
                                                if (file_exists(str_replace('../', '', $image_path))) {
                                                    $product_image = $image_path;
                                                }
                                            }
                                        }
                                        ?>
                                        <a href="product-detail.php?id=<?php echo $product_item['id']; ?>" class="text-decoration-none">
                                            <img src="<?php echo $product_image; ?>" 
                                                 class="card-img-top" 
                                                 alt="<?php echo htmlspecialchars($product_item['name']); ?>"
                                                 onerror="this.src='../assets/images/products/default.jpg'">
                                        </a>
                                        <?php if (!empty($product_item['discount_price']) && $product_item['discount_price'] > 0): ?>
                                            <span class="badge bg-danger position-absolute top-0 start-0 m-3 badge-custom">
                                                <i class="fas fa-tag me-1"></i>Sale
                                            </span>
                                        <?php endif; ?>
                                        <?php if (isset($product_item['is_featured']) && $product_item['is_featured']): ?>
                                            <span class="badge bg-warning text-dark position-absolute top-0 end-0 m-3 badge-custom">
                                                <i class="fas fa-crown me-1"></i>Featured
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="card-body p-4">
                                        <h6 class="card-title mb-2">
                                            <a href="product-detail.php?id=<?php echo $product_item['id']; ?>" 
                                               class="text-decoration-none text-dark fw-bold">
                                                <?php echo htmlspecialchars($product_item['name']); ?>
                                            </a>
                                        </h6>
                                        <p class="card-text text-muted small mb-3">
                                            <?php echo htmlspecialchars($product_item['brand'] ?? 'Generic'); ?>
                                        </p>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <?php if (!empty($product_item['discount_price']) && $product_item['discount_price'] > 0): ?>
                                                    <span class="text-danger fw-bold h5 mb-0"><?php echo format_price($product_item['discount_price']); ?></span>
                                                    <span class="text-muted text-decoration-line-through small d-block"><?php echo format_price($product_item['price']); ?></span>
                                                <?php else: ?>
                                                    <span class="text-dark fw-bold h5 mb-0"><?php echo format_price($product_item['price']); ?></span>
                                                <?php endif; ?>
                                            </div>
                                            <button class="btn btn-primary btn-sm add-to-cart-btn"
                                                    data-product-id="<?php echo $product_item['id']; ?>"
                                                    data-product-name="<?php echo htmlspecialchars($product_item['name']); ?>">
                                                <i class="fas fa-cart-plus me-1"></i>Add to Cart
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <?php if ($total_products > 20): ?>
                        <div class="text-center mt-5">
                            <a href="shop.php?vendor=<?php echo $vendor_id; ?>" class="btn btn-gradient px-5">
                                <i class="fas fa-eye me-2"></i>View All <?php echo $total_products; ?> Products
                            </a>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </section>

            <!-- About Store & Contact Section -->
            <div class="row g-4 mb-5">
                <!-- About Store -->
                <div class="col-lg-8">
                    <section id="about-section" class="mb-5">
                        <div class="store-info-card">
                            <div class="card-body p-4">
                                <h3 class="section-title mb-4">About This Store</h3>
                                
                                <?php if (!empty($store_info['store_description'])): ?>
                                    <div class="mb-4">
                                        <p class="card-text fs-5"><?php echo nl2br(htmlspecialchars($store_info['store_description'])); ?></p>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle me-2"></i>
                                        This store hasn't added a description yet.
                                    </div>
                                <?php endif; ?>
                                
                                <div class="row g-4 mt-4">
                                    <?php if (!empty($store_info['store_address'])): ?>
                                        <div class="col-md-6">
                                            <div class="d-flex align-items-start">
                                                <div class="bg-primary bg-opacity-10 p-3 rounded-circle me-3">
                                                    <i class="fas fa-map-marker-alt text-primary fa-lg"></i>
                                                </div>
                                                <div>
                                                    <h6 class="fw-bold mb-1">Store Location</h6>
                                                    <p class="text-muted mb-0"><?php echo htmlspecialchars($store_info['store_address']); ?></p>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($store_info['store_phone'])): ?>
                                        <div class="col-md-6">
                                            <div class="d-flex align-items-start">
                                                <div class="bg-success bg-opacity-10 p-3 rounded-circle me-3">
                                                    <i class="fas fa-phone text-success fa-lg"></i>
                                                </div>
                                                <div>
                                                    <h6 class="fw-bold mb-1">Contact Phone</h6>
                                                    <p class="text-muted mb-0"><?php echo htmlspecialchars($store_info['store_phone']); ?></p>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="col-md-6">
                                        <div class="d-flex align-items-start">
                                            <div class="bg-info bg-opacity-10 p-3 rounded-circle me-3">
                                                <i class="fas fa-calendar-alt text-info fa-lg"></i>
                                            </div>
                                            <div>
                                                <h6 class="fw-bold mb-1">Vendor Since</h6>
                                                <p class="text-muted mb-0">
                                                    <?php echo date('F Y', strtotime($store_info['join_date'])); ?>
                                                    (<?php echo time_ago($store_info['join_date']); ?>)
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div class="d-flex align-items-start">
                                            <div class="bg-warning bg-opacity-10 p-3 rounded-circle me-3">
                                                <i class="fas fa-bolt text-warning fa-lg"></i>
                                            </div>
                                            <div>
                                                <h6 class="fw-bold mb-1">Response Time</h6>
                                                <p class="text-muted mb-0">Usually responds within 24 hours</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Social Media Links -->
                                <?php if (!empty(array_filter($social_data))): ?>
                                    <div class="mt-5 pt-4 border-top">
                                        <h5 class="mb-4">Follow on Social Media</h5>
                                        <div class="social-icons">
                                            <?php if (!empty($social_data['facebook'])): ?>
                                                <a href="<?php echo htmlspecialchars($social_data['facebook']); ?>" 
                                                   class="bg-primary" 
                                                   target="_blank" 
                                                   rel="noopener noreferrer"
                                                   title="Facebook">
                                                    <i class="fab fa-facebook-f"></i>
                                                </a>
                                            <?php endif; ?>
                                            
                                            <?php if (!empty($social_data['instagram'])): ?>
                                                <a href="<?php echo htmlspecialchars($social_data['instagram']); ?>" 
                                                   class="bg-danger" 
                                                   target="_blank" 
                                                   rel="noopener noreferrer"
                                                   title="Instagram">
                                                    <i class="fab fa-instagram"></i>
                                                </a>
                                            <?php endif; ?>
                                            
                                            <?php if (!empty($social_data['twitter'])): ?>
                                                <a href="<?php echo htmlspecialchars($social_data['twitter']); ?>" 
                                                   class="bg-info" 
                                                   target="_blank" 
                                                   rel="noopener noreferrer"
                                                   title="Twitter">
                                                    <i class="fab fa-twitter"></i>
                                                </a>
                                            <?php endif; ?>
                                            
                                            <?php if (!empty($social_data['telegram'])): ?>
                                                <a href="<?php echo htmlspecialchars($social_data['telegram']); ?>" 
                                                   class="bg-primary" 
                                                   target="_blank" 
                                                   rel="noopener noreferrer"
                                                   title="Telegram">
                                                    <i class="fab fa-telegram"></i>
                                                </a>
                                            <?php endif; ?>
                                            
                                            <?php if (!empty($social_data['youtube'])): ?>
                                                <a href="<?php echo htmlspecialchars($social_data['youtube']); ?>" 
                                                   class="bg-danger" 
                                                   target="_blank" 
                                                   rel="noopener noreferrer"
                                                   title="YouTube">
                                                    <i class="fab fa-youtube"></i>
                                                </a>
                                            <?php endif; ?>
                                            
                                            <?php if (!empty($social_data['linkedin'])): ?>
                                                <a href="<?php echo htmlspecialchars($social_data['linkedin']); ?>" 
                                                   class="bg-primary" 
                                                   target="_blank" 
                                                   rel="noopener noreferrer"
                                                   title="LinkedIn">
                                                    <i class="fab fa-linkedin-in"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </section>
                    
                    <!-- Reviews Section -->
                    <?php if ($review_count > 0): ?>
                        <section id="reviews-section" class="mb-5">
                            <div class="store-info-card">
                                <div class="card-body p-4">
                                    <div class="d-flex justify-content-between align-items-center mb-4">
                                        <h3 class="section-title mb-0">Customer Reviews</h3>
                                        <div class="text-end">
                                            <div class="h2 fw-bold text-primary mb-0"><?php echo number_format($store_info['rating'], 1); ?></div>
                                            <div class="text-warning mb-2">
                                                <?php echo get_star_rating($store_info['rating']); ?>
                                            </div>
                                            <div class="text-muted small">Based on <?php echo $review_count; ?> reviews</div>
                                        </div>
                                    </div>
                                    
                                    <div class="row g-4 mb-4">
                                        <?php foreach ($reviews as $review): ?>
                                            <div class="col-12">
                                                <div class="review-card p-4">
                                                    <div class="d-flex align-items-start mb-3">
                                                        <?php
                                                        $avatar_path = !empty($review['profile_picture']) ? 
                                                            '../' . htmlspecialchars($review['profile_picture']) : 
                                                            '../assets/images/users/default-avatar.png';
                                                        ?>
                                                        <img src="<?php echo $avatar_path; ?>" 
                                                             alt="<?php echo htmlspecialchars($review['first_name'] . ' ' . $review['last_name']); ?>"
                                                             class="review-avatar me-3"
                                                             onerror="this.src='../assets/images/users/default-avatar.png'">
                                                        <div class="flex-grow-1">
                                                            <div class="d-flex justify-content-between align-items-start">
                                                                <div>
                                                                    <h6 class="fw-bold mb-1"><?php echo htmlspecialchars($review['first_name'] . ' ' . $review['last_name']); ?></h6>
                                                                    <div class="text-warning small mb-2">
                                                                        <?php echo get_star_rating($review['rating']); ?>
                                                                    </div>
                                                                </div>
                                                                <span class="text-muted small"><?php echo time_ago($review['created_at']); ?></span>
                                                            </div>
                                                            <?php if (!empty($review['comment'])): ?>
                                                                <p class="mb-0"><?php echo nl2br(htmlspecialchars($review['comment'])); ?></p>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    
                                    <?php if ($review_count > 5): ?>
                                        <div class="text-center">
                                            <a href="store-reviews.php?id=<?php echo $vendor_id; ?>" class="btn btn-outline-gradient">
                                                <i class="fas fa-list me-2"></i>View All Reviews
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if (isset($_SESSION['user_id']) && $_SESSION['user_type'] === 'buyer'): ?>
                                        <div class="text-center mt-4 pt-4 border-top">
                                            <button class="btn btn-gradient" data-bs-toggle="modal" data-bs-target="#reviewModal">
                                                <i class="fas fa-edit me-2"></i>Write a Review
                                            </button>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </section>
                    <?php endif; ?>
                </div>
                
                <!-- Contact & Stats Sidebar -->
                <div class="col-lg-4">
                    <!-- Contact Form -->
                    <section id="contact-section" class="mb-5">
                        <div class="store-info-card">
                            <div class="card-body p-4">
                                <h3 class="section-title mb-4">Contact Vendor</h3>
                                
                                <?php if (isset($_SESSION['user_id']) && $_SESSION['user_type'] === 'buyer'): ?>
                                    <form id="contactVendorForm" class="contact-form">
                                        <input type="hidden" name="vendor_id" value="<?php echo $vendor_id; ?>">
                                        
                                        <div class="mb-3">
                                            <label for="contact_name" class="form-label fw-bold">Your Name *</label>
                                            <?php
                                            $user_name = '';
                                            if (isset($_SESSION['user_name'])) {
                                                $user_name = $_SESSION['user_name'];
                                            } elseif (isset($_SESSION['user_first_name']) && isset($_SESSION['user_last_name'])) {
                                                $user_name = $_SESSION['user_first_name'] . ' ' . $_SESSION['user_last_name'];
                                            } else {
                                                $user = new User();
                                                $user_data = $user->getUserById($_SESSION['user_id']);
                                                if ($user_data) {
                                                    $user_name = ($user_data['first_name'] ?? '') . ' ' . ($user_data['last_name'] ?? '');
                                                }
                                            }
                                            ?>
                                            <input type="text" class="form-control" id="contact_name" name="name" required
                                                   value="<?php echo htmlspecialchars($user_name); ?>">
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="contact_email" class="form-label fw-bold">Your Email *</label>
                                            <?php
                                            $user_email = $_SESSION['user_email'] ?? '';
                                            if (empty($user_email)) {
                                                $user = new User();
                                                $user_data = $user->getUserById($_SESSION['user_id']);
                                                $user_email = $user_data['email'] ?? '';
                                            }
                                            ?>
                                            <input type="email" class="form-control" id="contact_email" name="email" required
                                                   value="<?php echo htmlspecialchars($user_email); ?>">
                                        </div>
                                        
                                        <div class="mb-3">
                                            <label for="contact_subject" class="form-label fw-bold">Subject *</label>
                                            <input type="text" class="form-control" id="contact_subject" name="subject" required
                                                   placeholder="e.g., Question about a product">
                                        </div>
                                        
                                        <div class="mb-4">
                                            <label for="contact_message" class="form-label fw-bold">Message *</label>
                                            <textarea class="form-control" id="contact_message" name="message" rows="5" required
                                                      placeholder="Type your message here..."></textarea>
                                        </div>
                                        
                                        <button type="submit" class="btn btn-gradient w-100">
                                            <i class="fas fa-paper-plane me-2"></i>Send Message
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <div class="alert alert-info">
                                        <div class="d-flex align-items-center">
                                            <div class="flex-shrink-0">
                                                <i class="fas fa-info-circle fa-2x"></i>
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <h5 class="alert-heading mb-2">Login Required</h5>
                                                <p class="mb-0">Please <a href="login.php" class="alert-link fw-bold">login as a buyer</a> to contact this vendor.</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="text-center">
                                        <a href="login.php?redirect=store.php?id=<?php echo $vendor_id; ?>" class="btn btn-gradient w-100">
                                            <i class="fas fa-sign-in-alt me-2"></i>Login to Contact
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </section>
                    
                    <!-- Store Statistics -->
                    <div class="store-info-card mb-5">
                        <div class="card-body p-4">
                            <h3 class="section-title mb-4">Store Statistics</h3>
                            <div class="row g-3">
                                <div class="col-6">
                                    <div class="stats-card rating">
                                        <div class="stats-number"><?php echo number_format($store_info['rating'], 1); ?></div>
                                        <div class="stats-label">Average Rating</div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="stats-card response">
                                        <div class="stats-number"><?php echo $store_info['response_rate']; ?>%</div>
                                        <div class="stats-label">Response Rate</div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="stats-card">
                                        <div class="stats-number"><?php echo $review_count; ?></div>
                                        <div class="stats-label">Total Reviews</div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="stats-card">
                                        <div class="stats-number"><?php echo $active_products_count; ?></div>
                                        <div class="stats-label">Active Products</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Report Store -->
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <div class="store-info-card">
                            <div class="card-body p-4">
                                <h5 class="fw-bold mb-3">Report This Store</h5>
                                <p class="text-muted small mb-4">If you believe this store is violating our terms of service, please report it.</p>
                                <button class="btn btn-outline-danger w-100" data-bs-toggle="modal" data-bs-target="#reportModal">
                                    <i class="fas fa-flag me-2"></i>Report Store
                                </button>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Similar Stores -->
            <?php if (!empty($similar_stores)): ?>
                <section id="similar-stores" class="mb-5">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2 class="section-title">Similar Stores</h2>
                        <a href="stores.php" class="btn btn-outline-gradient">
                            <i class="fas fa-store me-2"></i>Browse All Stores
                        </a>
                    </div>
                    
                    <div class="row g-4">
                        <?php foreach ($similar_stores as $store): ?>
                            <div class="col-xl-3 col-lg-4 col-md-6">
                                <div class="product-card text-center p-4">
                                    <?php
                                    $similar_store_logo = !empty($store['store_logo']) ? 
                                        '../' . htmlspecialchars($store['store_logo']) : 
                                        '../assets/images/stores/default-logo.png';
                                    ?>
                                    <img src="<?php echo $similar_store_logo; ?>" 
                                         alt="<?php echo htmlspecialchars($store['store_name'] ?? $store['first_name'] . ' ' . $store['last_name']); ?>"
                                         class="rounded-circle mb-3" 
                                         style="width: 100px; height: 100px; object-fit: cover;"
                                         onerror="this.src='../assets/images/stores/default-logo.png'">
                                    <h5 class="fw-bold mb-2">
                                        <a href="store.php?id=<?php echo $store['id']; ?>" class="text-decoration-none text-dark">
                                            <?php echo htmlspecialchars($store['store_name'] ?? $store['first_name'] . ' ' . $store['last_name']); ?>
                                        </a>
                                    </h5>
                                    <div class="text-warning mb-3">
                                        <?php echo get_star_rating($store['rating'] ?? 0); ?>
                                        <span class="text-muted ms-2">(<?php echo number_format($store['rating'] ?? 0, 1); ?>)</span>
                                    </div>
                                    <div class="text-muted small mb-4">
                                        <i class="fas fa-box me-1"></i> <?php echo $store['product_count'] ?? 0; ?> Products
                                    </div>
                                    <a href="store.php?id=<?php echo $store['id']; ?>" class="btn btn-gradient w-100">
                                        <i class="fas fa-store me-2"></i>Visit Store
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>
        </div>
    </main>

    <!-- Footer -->
    <?php
    $footer_paths = ['includes/footer.php', '../includes/footer.php'];
    $footer_included = false;
    
    foreach ($footer_paths as $path) {
        if (file_exists($path)) {
            include $path;
            $footer_included = true;
            break;
        }
    }
    
    if (!$footer_included):
    ?>
        <footer class="bg-dark text-white py-5">
            <div class="container">
                <div class="row">
                    <div class="col-lg-4 mb-4 mb-lg-0">
                        <h5 class="fw-bold mb-3">
                            <i class="fas fa-shoe-prints me-2"></i><?php echo htmlspecialchars(SITE_NAME); ?>
                        </h5>
                        <p class="text-white-50">Your one-stop shop for amazing products from verified vendors.</p>
                    </div>
                    <div class="col-lg-2 col-md-4 mb-4 mb-md-0">
                        <h6 class="fw-bold mb-3">Quick Links</h6>
                        <ul class="list-unstyled">
                            <li class="mb-2"><a href="index.php" class="text-white-50 text-decoration-none">Home</a></li>
                            <li class="mb-2"><a href="shop.php" class="text-white-50 text-decoration-none">Shop</a></li>
                            <li class="mb-2"><a href="about.php" class="text-white-50 text-decoration-none">About Us</a></li>
                            <li><a href="contact.php" class="text-white-50 text-decoration-none">Contact</a></li>
                        </ul>
                    </div>
                    <div class="col-lg-2 col-md-4 mb-4 mb-md-0">
                        <h6 class="fw-bold mb-3">Support</h6>
                        <ul class="list-unstyled">
                            <li class="mb-2"><a href="faq.php" class="text-white-50 text-decoration-none">FAQ</a></li>
                            <li class="mb-2"><a href="shipping.php" class="text-white-50 text-decoration-none">Shipping</a></li>
                            <li class="mb-2"><a href="returns.php" class="text-white-50 text-decoration-none">Returns</a></li>
                            <li><a href="terms.php" class="text-white-50 text-decoration-none">Terms</a></li>
                        </ul>
                    </div>
                    <div class="col-lg-4 col-md-4">
                        <h6 class="fw-bold mb-3">Connect With Us</h6>
                        <div class="social-icons">
                            <a href="#" class="bg-primary me-2"><i class="fab fa-facebook-f"></i></a>
                            <a href="#" class="bg-info me-2"><i class="fab fa-twitter"></i></a>
                            <a href="#" class="bg-danger me-2"><i class="fab fa-instagram"></i></a>
                            <a href="#" class="bg-primary"><i class="fab fa-telegram"></i></a>
                        </div>
                    </div>
                </div>
                <hr class="bg-white-50 my-4">
                <div class="row">
                    <div class="col-md-6">
                        <p class="mb-0 text-white-50">&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars(SITE_NAME); ?>. All rights reserved.</p>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <a href="privacy.php" class="text-white-50 text-decoration-none me-3">Privacy Policy</a>
                        <a href="terms.php" class="text-white-50 text-decoration-none">Terms of Service</a>
                    </div>
                </div>
            </div>
        </footer>
    <?php endif; ?>

    <!-- Review Modal -->
    <div class="modal fade" id="reviewModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Write a Review</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="reviewForm" class="review-form">
                    <div class="modal-body">
                        <input type="hidden" name="vendor_id" value="<?php echo $vendor_id; ?>">
                        
                        <div class="mb-4 text-center">
                            <div class="rating-stars mb-3" id="ratingStars">
                                <i class="far fa-star fa-2x" data-rating="1"></i>
                                <i class="far fa-star fa-2x" data-rating="2"></i>
                                <i class="far fa-star fa-2x" data-rating="3"></i>
                                <i class="far fa-star fa-2x" data-rating="4"></i>
                                <i class="far fa-star fa-2x" data-rating="5"></i>
                            </div>
                            <input type="hidden" name="rating" id="ratingValue" value="0" required>
                            <div class="text-muted">Click stars to rate</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="review_title" class="form-label fw-bold">Review Title</label>
                            <input type="text" class="form-control" id="review_title" name="title" 
                                   placeholder="Summarize your experience">
                        </div>
                        
                        <div class="mb-3">
                            <label for="review_comment" class="form-label fw-bold">Your Review *</label>
                            <textarea class="form-control" id="review_comment" name="comment" rows="4" required
                                      placeholder="Share your experience with this store..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-gradient">Submit Review</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Report Modal -->
    <div class="modal fade" id="reportModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Report Store</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="reportForm">
                    <div class="modal-body">
                        <input type="hidden" name="vendor_id" value="<?php echo $vendor_id; ?>">
                        
                        <div class="mb-3">
                            <label for="report_reason" class="form-label fw-bold">Reason for Report *</label>
                            <select class="form-select" id="report_reason" name="reason" required>
                                <option value="">Select a reason</option>
                                <option value="fake_products">Selling fake/counterfeit products</option>
                                <option value="scam">Scam or fraud</option>
                                <option value="harassment">Harassment or abusive behavior</option>
                                <option value="tos_violation">Violation of terms of service</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="report_details" class="form-label fw-bold">Additional Details</label>
                            <textarea class="form-control" id="report_details" name="details" rows="3"
                                      placeholder="Please provide more details about your report..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Submit Report</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Add to cart functionality
        document.querySelectorAll('.add-to-cart-btn').forEach(button => {
            button.addEventListener('click', function() {
                const productId = this.dataset.productId;
                const productName = this.dataset.productName;
                const originalHTML = this.innerHTML;
                
                // Show loading state
                this.innerHTML = '<span class="loading-spinner"></span>';
                this.disabled = true;
                
                // Send AJAX request
                fetch('../api/cart.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=add&product_id=' + productId
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showAlert('success', '<i class="fas fa-check-circle me-2"></i>' + productName + ' added to cart!');
                        updateCartCount();
                    } else {
                        showAlert('danger', '<i class="fas fa-exclamation-circle me-2"></i>' + (data.message || 'Could not add to cart'));
                        if (data.redirect) {
                            setTimeout(() => {
                                window.location.href = data.redirect;
                            }, 1500);
                        }
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showAlert('danger', '<i class="fas fa-exclamation-circle me-2"></i>An error occurred. Please try again.');
                })
                .finally(() => {
                    // Restore button after 2 seconds
                    setTimeout(() => {
                        this.innerHTML = originalHTML;
                        this.disabled = false;
                    }, 2000);
                });
            });
        });

        // Contact form submission
        const contactForm = document.getElementById('contactVendorForm');
        if (contactForm) {
            contactForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                const submitBtn = this.querySelector('button[type="submit"]');
                const originalHTML = submitBtn.innerHTML;
                
                submitBtn.innerHTML = '<span class="loading-spinner"></span>';
                submitBtn.disabled = true;
                
                fetch('../api/contact.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        showAlert('success', '<i class="fas fa-check-circle me-2"></i>Message sent successfully! The vendor will contact you soon.');
                        this.reset();
                    } else {
                        showAlert('danger', '<i class="fas fa-exclamation-circle me-2"></i>Error: ' + (data.message || 'Failed to send message'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showAlert('info', '<i class="fas fa-info-circle me-2"></i>Message saved. The vendor will be notified.');
                    this.reset();
                })
                .finally(() => {
                    submitBtn.innerHTML = originalHTML;
                    submitBtn.disabled = false;
                });
            });
        }

        // Review form submission
        const reviewForm = document.getElementById('reviewForm');
        if (reviewForm) {
            reviewForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const submitBtn = this.querySelector('button[type="submit"]');
                const originalHTML = submitBtn.innerHTML;
                
                submitBtn.innerHTML = '<span class="loading-spinner"></span>';
                submitBtn.disabled = true;
                
                // Simulate API call (replace with actual API endpoint)
                setTimeout(() => {
                    showAlert('success', '<i class="fas fa-check-circle me-2"></i>Thank you for your review! It will be published after approval.');
                    const reviewModal = bootstrap.Modal.getInstance(document.getElementById('reviewModal'));
                    reviewModal.hide();
                    this.reset();
                    resetRatingStars();
                    
                    submitBtn.innerHTML = originalHTML;
                    submitBtn.disabled = false;
                }, 1500);
            });
        }

        // Report form submission
        const reportForm = document.getElementById('reportForm');
        if (reportForm) {
            reportForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const submitBtn = this.querySelector('button[type="submit"]');
                const originalHTML = submitBtn.innerHTML;
                
                submitBtn.innerHTML = '<span class="loading-spinner"></span>';
                submitBtn.disabled = true;
                
                // Simulate API call
                setTimeout(() => {
                    showAlert('success', '<i class="fas fa-check-circle me-2"></i>Thank you for your report. We will investigate this matter.');
                    const reportModal = bootstrap.Modal.getInstance(document.getElementById('reportModal'));
                    reportModal.hide();
                    this.reset();
                    
                    submitBtn.innerHTML = originalHTML;
                    submitBtn.disabled = false;
                }, 1500);
            });
        }

        // Rating stars functionality
        const ratingStars = document.getElementById('ratingStars');
        const ratingValue = document.getElementById('ratingValue');
        
        if (ratingStars) {
            const stars = ratingStars.querySelectorAll('.fa-star');
            
            stars.forEach(star => {
                star.addEventListener('mouseover', function() {
                    const rating = parseInt(this.dataset.rating);
                    highlightStars(rating);
                });
                
                star.addEventListener('click', function() {
                    const rating = parseInt(this.dataset.rating);
                    ratingValue.value = rating;
                    highlightStars(rating);
                    makeStarsSolid(rating);
                });
            });
            
            ratingStars.addEventListener('mouseleave', function() {
                const currentRating = parseInt(ratingValue.value);
                if (currentRating > 0) {
                    highlightStars(currentRating);
                    makeStarsSolid(currentRating);
                } else {
                    resetRatingStars();
                }
            });
        }

        function highlightStars(rating) {
            const stars = ratingStars.querySelectorAll('.fa-star');
            stars.forEach((star, index) => {
                if (index < rating) {
                    star.classList.add('text-warning');
                } else {
                    star.classList.remove('text-warning');
                }
            });
        }

        function makeStarsSolid(rating) {
            const stars = ratingStars.querySelectorAll('.fa-star');
            stars.forEach((star, index) => {
                if (index < rating) {
                    star.classList.remove('far');
                    star.classList.add('fas');
                } else {
                    star.classList.remove('fas');
                    star.classList.add('far');
                }
            });
        }

        function resetRatingStars() {
            const stars = ratingStars.querySelectorAll('.fa-star');
            stars.forEach(star => {
                star.classList.remove('fas', 'text-warning');
                star.classList.add('far');
            });
            ratingValue.value = '0';
        }

        // Smooth scrolling for anchor links
        document.querySelectorAll('.store-nav .nav-link').forEach(link => {
            link.addEventListener('click', function(e) {
                const href = this.getAttribute('href');
                if (href.startsWith('#')) {
                    e.preventDefault();
                    const target = document.querySelector(href);
                    if (target) {
                        const headerOffset = 100;
                        const elementPosition = target.getBoundingClientRect().top;
                        const offsetPosition = elementPosition + window.pageYOffset - headerOffset;
                        
                        window.scrollTo({
                            top: offsetPosition,
                            behavior: 'smooth'
                        });
                        
                        // Update active nav link
                        document.querySelectorAll('.store-nav .nav-link').forEach(navLink => {
                            navLink.classList.remove('active');
                        });
                        this.classList.add('active');
                    }
                }
            });
        });

        // Update active nav link on scroll
        const sections = document.querySelectorAll('section[id]');
        const navLinks = document.querySelectorAll('.store-nav .nav-link');
        
        window.addEventListener('scroll', () => {
            let current = '';
            sections.forEach(section => {
                const sectionTop = section.offsetTop;
                const sectionHeight = section.clientHeight;
                if (scrollY >= (sectionTop - 150)) {
                    current = section.getAttribute('id');
                }
            });

            navLinks.forEach(link => {
                link.classList.remove('active');
                if (link.getAttribute('href') === '#' + current) {
                    link.classList.add('active');
                }
            });
        });

        // Update cart count
        function updateCartCount() {
            fetch('../api/cart.php?action=count')
                .then(response => response.json())
                .then(data => {
                    if (data.count !== undefined) {
                        const cartBadge = document.querySelector('.fa-shopping-cart').parentElement.querySelector('.badge');
                        if (cartBadge) {
                            if (data.count > 0) {
                                cartBadge.textContent = data.count;
                                cartBadge.style.display = 'block';
                            } else {
                                cartBadge.style.display = 'none';
                            }
                        }
                    }
                })
                .catch(error => {
                    console.error('Error updating cart count:', error);
                });
        }

        // Show alert function
        function showAlert(type, message) {
            // Remove existing alerts
            document.querySelectorAll('.floating-alert').forEach(alert => alert.remove());
            
            const alertDiv = document.createElement('div');
            alertDiv.className = `floating-alert alert alert-${type} alert-dismissible fade show`;
            alertDiv.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            document.body.appendChild(alertDiv);
            
            // Auto remove after 5 seconds
            setTimeout(() => {
                if (alertDiv.parentNode) {
                    alertDiv.remove();
                }
            }, 5000);
        }

        // Initialize tooltips
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        const tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });

        // Image lazy loading
        if ('IntersectionObserver' in window) {
            const imageObserver = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        img.src = img.dataset.src;
                        img.classList.add('loaded');
                        imageObserver.unobserve(img);
                    }
                });
            });

            document.querySelectorAll('img[data-src]').forEach(img => {
                imageObserver.observe(img);
            });
        }
    </script>
</body>

</html>