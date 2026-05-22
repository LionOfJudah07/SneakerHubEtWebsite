<?php
// public/includes/navbar.php
// Enhanced Navigation Bar for Public Pages

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include functions from root directory
if (file_exists('../functions.php')) {
    require_once '../functions.php';
}

// Initialize variables
$current_user = null;
$categories = [];
$wishlist_items = [];
$cart_count = 0;
$wishlist_count = 0;

// Get cart count
$cart_count = get_cart_count();

// Get wishlist count
if (isset($_SESSION['wishlist']) && is_array($_SESSION['wishlist'])) {
    $wishlist_count = count($_SESSION['wishlist']);
}

// Get current user data if logged in
if (is_logged_in() && isset($_SESSION['user_id'])) {
    if (file_exists('../classes/User.php')) {
        require_once '../classes/User.php';
        $user_obj = new User();
        $current_user = $user_obj->getUserById($_SESSION['user_id']);
    }
}

// Get categories for dropdown
if (file_exists('../classes/Product.php')) {
    require_once '../classes/Product.php';
    $product = new Product();
    $categories = $product->getCategories() ?? [];
}

// Get wishlist items for dropdown
if ($wishlist_count > 0 && file_exists('../classes/Product.php')) {
    if (!isset($product)) {
        require_once '../classes/Product.php';
        $product = new Product();
    }
    foreach ($_SESSION['wishlist'] as $product_id) {
        $item = $product->getProductById($product_id);
        if ($item) $wishlist_items[] = $item;
    }
    $wishlist_items = array_slice($wishlist_items, 0, 3); // Show only first 3
}

// Get current page for active state
$current_page = basename($_SERVER['PHP_SELF']);

// Generate CSRF token for AJAX requests
$csrf_token = bin2hex(random_bytes(32));
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = $csrf_token;
} else {
    $csrf_token = $_SESSION['csrf_token'];
}
?>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top shadow-lg" style="background: linear-gradient(135deg, #0b0f0e 0%, #1a1f1e 100%) !important;">
    <div class="container">
        <!-- Brand/Logo -->
        <a class="navbar-brand fw-bold d-flex align-items-center" href="index.php">
            <div class="brand-logo bg-primary rounded-circle d-flex align-items-center justify-content-center me-2" 
                 style="width: 40px; height: 40px; background: linear-gradient(135deg, #16a34a, #0f7a35);">
                <i class="fas fa-shoe-prints text-white"></i>
            </div>
            <div>
                <div class="text-primary fw-bold" style="font-size: 1.2rem; line-height: 1;">Sneaker</div>
                <div class="text-white" style="font-size: 0.9rem; line-height: 1; margin-top: -2px;">Hub Ethiopia</div>
            </div>
        </a>

        <!-- Mobile Toggle -->
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Main Navigation -->
        <div class="collapse navbar-collapse" id="navbarMain">
            <!-- Left Navigation -->
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'index.php' ? 'active' : ''; ?>" href="index.php">
                        <div class="nav-icon"><i class="fas fa-home"></i></div>
                        <span class="nav-text">Home</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'shop.php' ? 'active' : ''; ?>" href="shop.php">
                        <div class="nav-icon"><i class="fas fa-store"></i></div>
                        <span class="nav-text">Shop</span>
                    </a>
                </li>

                <!-- Categories Dropdown -->
                <?php if (!empty($categories)): ?>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="categoriesDropdown" role="button" data-bs-toggle="dropdown">
                        <div class="nav-icon"><i class="fas fa-list"></i></div>
                        <span class="nav-text">Categories</span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-dark" aria-labelledby="categoriesDropdown">
                        <li class="dropdown-header">
                            <i class="fas fa-filter me-2"></i>Browse Categories
                        </li>
                        <?php foreach (array_slice($categories, 0, 10) as $category): ?>
                        <li>
                            <a class="dropdown-item d-flex align-items-center" href="shop.php?category=<?php echo urlencode($category); ?>">
                                <i class="fas fa-chevron-right me-2 text-primary" style="font-size: 0.8rem;"></i>
                                <?php echo htmlspecialchars($category); ?>
                            </a>
                        </li>
                        <?php endforeach; ?>
                        <?php if (count($categories) > 10): ?>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item text-center text-primary" href="shop.php">
                                <i class="fas fa-arrow-right me-1"></i>View All Categories
                            </a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </li>
                <?php endif; ?>

                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'about.php' ? 'active' : ''; ?>" href="about.php">
                        <div class="nav-icon"><i class="fas fa-info-circle"></i></div>
                        <span class="nav-text">About</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'contact.php' ? 'active' : ''; ?>" href="contact.php">
                        <div class="nav-icon"><i class="fas fa-envelope"></i></div>
                        <span class="nav-text">Contact</span>
                    </a>
                </li>
            </ul>

            <!-- Right Navigation -->
            <div class="d-flex align-items-center">
                <!-- Desktop Search -->
                <div class="d-none d-lg-block me-3">
                    <form class="d-flex" action="shop.php" method="GET">
                        <div class="input-group search-group">
                            <input class="form-control border-end-0 rounded-start" type="search" name="search" 
                                   placeholder="Search sneakers..." aria-label="Search" style="min-width: 280px;">
                            <button class="btn btn-primary rounded-end" type="submit">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Mobile Search Toggle -->
                <div class="d-lg-none me-3">
                    <button class="btn btn-outline-light btn-search-toggle" type="button" data-bs-toggle="collapse" data-bs-target="#mobileSearch">
                        <i class="fas fa-search"></i>
                    </button>
                </div>

                <!-- Wishlist Dropdown -->
                <div class="dropdown me-3 position-relative">
                    <a href="#" class="text-light position-relative nav-icon-btn" data-bs-toggle="dropdown" aria-expanded="false">
                        <div class="nav-icon">
                            <i class="fas fa-heart"></i>
                        </div>
                        <?php if ($wishlist_count > 0): ?>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger wishlist-badge">
                            <?php echo $wishlist_count; ?>
                        </span>
                        <?php endif; ?>
                    </a>
                    <div class="dropdown-menu dropdown-menu-end p-3 wishlist-dropdown" style="min-width: 350px;">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="mb-0 fw-bold">
                                <i class="fas fa-heart text-danger me-2"></i>My Wishlist
                            </h6>
                            <a href="wishlist.php" class="btn btn-sm btn-primary">View All</a>
                        </div>
                        
                        <?php if (empty($wishlist_items)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-heart fa-3x text-muted mb-3"></i>
                            <p class="text-muted mb-2">Your wishlist is empty</p>
                            <small class="text-muted">Add items you like to your wishlist</small>
                        </div>
                        <?php else: ?>
                        <div class="wishlist-items">
                            <?php foreach ($wishlist_items as $item): 
                                $image = '../assets/images/products/default.jpg';
                                if (!empty($item['images'])) {
                                    $images = is_string($item['images']) ? json_decode($item['images'], true) : $item['images'];
                                    if (is_array($images) && !empty($images[0])) {
                                        $image = '../' . $images[0];
                                    }
                                }
                            ?>
                            <div class="wishlist-item d-flex align-items-center mb-3 p-2 rounded" style="background: #f8f9fa;">
                                <div class="position-relative me-3">
                                    <img src="<?php echo htmlspecialchars($image); ?>"
                                         alt="<?php echo htmlspecialchars($item['name'] ?? ''); ?>"
                                         class="img-thumbnail" style="width: 70px; height: 70px; object-fit: cover;">
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-1 fw-semibold" style="font-size: 0.9rem;">
                                        <?php echo htmlspecialchars($item['name'] ?? ''); ?>
                                    </h6>
                                    <div class="d-flex align-items-center">
                                        <span class="text-primary fw-bold me-2" style="font-size: 0.9rem;">
                                            <?php echo format_price($item['price'] ?? 0); ?>
                                        </span>
                                        <?php if (!empty($item['discount_price']) && $item['discount_price'] > 0): ?>
                                        <span class="text-muted text-decoration-line-through" style="font-size: 0.8rem;">
                                            <?php echo format_price($item['discount_price']); ?>
                                        </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="ms-2">
                                    <button class="btn btn-sm btn-primary add-to-cart-from-wishlist" 
                                            data-product-id="<?php echo $item['id'] ?? ''; ?>"
                                            data-product-name="<?php echo htmlspecialchars($item['name'] ?? ''); ?>"
                                            title="Add to cart">
                                        <i class="fas fa-cart-plus"></i>
                                    </button>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="text-center mt-3">
                            <a href="wishlist.php" class="btn btn-primary w-100">
                                <i class="fas fa-external-link-alt me-2"></i>Go to Wishlist
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Cart -->
                <div class="dropdown me-3 position-relative">
                    <a href="cart.php" class="text-light position-relative nav-icon-btn">
                        <div class="nav-icon">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                        <?php if ($cart_count > 0): ?>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger cart-badge">
                            <?php echo $cart_count; ?>
                        </span>
                        <?php endif; ?>
                    </a>
                    <!-- Optional cart preview dropdown -->
                    <div class="dropdown-menu dropdown-menu-end p-3 cart-preview" style="min-width: 350px; display: none;">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="mb-0 fw-bold">
                                <i class="fas fa-shopping-cart text-primary me-2"></i>Shopping Cart
                            </h6>
                            <span class="badge bg-primary"><?php echo $cart_count; ?> items</span>
                        </div>
                        <div class="text-center py-4">
                            <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
                            <p class="text-muted mb-2">Your cart is empty</p>
                            <small class="text-muted">Add items to start shopping</small>
                        </div>
                        <div class="text-center mt-3">
                            <a href="cart.php" class="btn btn-primary w-100">
                                <i class="fas fa-external-link-alt me-2"></i>Go to Cart
                            </a>
                        </div>
                    </div>
                </div>

                <!-- User Account -->
                <?php if (is_logged_in() && $current_user): ?>
                <div class="dropdown">
                    <a href="#" class="text-light dropdown-toggle d-flex align-items-center user-dropdown-toggle" 
                       data-bs-toggle="dropdown" aria-expanded="false">
                        <div class="position-relative">
                            <?php if (!empty($current_user['profile_image'])): ?>
                            <img src="../<?php echo htmlspecialchars($current_user['profile_image']); ?>" 
                                 alt="Profile" class="rounded-circle user-avatar">
                            <?php else: ?>
                            <div class="user-avatar-placeholder rounded-circle d-flex align-items-center justify-content-center"
                                 style="background: linear-gradient(135deg, #16a34a, #0f7a35);">
                                <i class="fas fa-user text-white"></i>
                            </div>
                            <?php endif; ?>
                            <?php if (($current_user['status'] ?? '') === 'active'): ?>
                            <span class="position-absolute bottom-0 end-0 translate-middle badge rounded-circle bg-success" 
                                  style="width: 10px; height: 10px; border: 2px solid #0b0f0e; padding: 0;"></span>
                            <?php endif; ?>
                        </div>
                        <div class="ms-2 d-none d-lg-block">
                            <div class="user-name" style="font-size: 0.9rem; line-height: 1.2;">
                                <?php echo htmlspecialchars($current_user['first_name'] ?? 'User'); ?>
                            </div>
                            <div class="user-type text-muted" style="font-size: 0.75rem;">
                                <?php 
                                $user_type = $_SESSION['user_type'] ?? 'buyer';
                                echo ucfirst($user_type);
                                ?>
                            </div>
                        </div>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end user-dropdown">
                        <li class="dropdown-header">
                            <div class="d-flex align-items-center">
                                <?php if (!empty($current_user['profile_image'])): ?>
                                <img src="../<?php echo htmlspecialchars($current_user['profile_image']); ?>" 
                                     alt="Profile" class="rounded-circle me-2" style="width: 40px; height: 40px; object-fit: cover;">
                                <?php else: ?>
                                <div class="rounded-circle bg-primary d-flex align-items-center justify-content-center me-2" 
                                     style="width: 40px; height: 40px; background: linear-gradient(135deg, #16a34a, #0f7a35);">
                                    <i class="fas fa-user text-white"></i>
                                </div>
                                <?php endif; ?>
                                <div>
                                    <div class="fw-bold"><?php echo htmlspecialchars($current_user['first_name'] . ' ' . $current_user['last_name']); ?></div>
                                    <div class="text-muted small"><?php echo htmlspecialchars($current_user['email'] ?? ''); ?></div>
                                </div>
                            </div>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        
                        <?php if (($_SESSION['user_type'] ?? '') === 'buyer'): ?>
                        <li>
                            <a class="dropdown-item d-flex align-items-center" href="../buyer/">
                                <div class="dropdown-icon"><i class="fas fa-tachometer-alt"></i></div>
                                <div>
                                    <div>Dashboard</div>
                                    <div class="text-muted small">Your orders & profile</div>
                                </div>
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item d-flex align-items-center" href="../buyer/orders.php">
                                <div class="dropdown-icon"><i class="fas fa-shopping-bag"></i></div>
                                <div>
                                    <div>My Orders</div>
                                    <div class="text-muted small">Track & manage orders</div>
                                </div>
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item d-flex align-items-center" href="../buyer/profile.php">
                                <div class="dropdown-icon"><i class="fas fa-user-cog"></i></div>
                                <div>
                                    <div>Account Settings</div>
                                    <div class="text-muted small">Update your profile</div>
                                </div>
                            </a>
                        </li>
                        <?php endif; ?>

                        <?php if (($_SESSION['user_type'] ?? '') === 'vendor'): ?>
                        <li>
                            <a class="dropdown-item d-flex align-items-center" href="../vendor/">
                                <div class="dropdown-icon"><i class="fas fa-store"></i></div>
                                <div>
                                    <div>Vendor Dashboard</div>
                                    <div class="text-muted small">Manage your store</div>
                                </div>
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item d-flex align-items-center" href="../vendor/products.php">
                                <div class="dropdown-icon"><i class="fas fa-box"></i></div>
                                <div>
                                    <div>My Products</div>
                                    <div class="text-muted small">Add & edit products</div>
                                </div>
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item d-flex align-items-center" href="../vendor/earnings.php">
                                <div class="dropdown-icon"><i class="fas fa-chart-line"></i></div>
                                <div>
                                    <div>Earnings</div>
                                    <div class="text-muted small">View sales & earnings</div>
                                </div>
                            </a>
                        </li>
                        <?php endif; ?>

                        <?php if (($_SESSION['user_type'] ?? '') === 'admin'): ?>
                        <li>
                            <a class="dropdown-item d-flex align-items-center" href="../admin/">
                                <div class="dropdown-icon"><i class="fas fa-cogs"></i></div>
                                <div>
                                    <div>Admin Dashboard</div>
                                    <div class="text-muted small">Manage entire site</div>
                                </div>
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item d-flex align-items-center" href="../admin/products.php">
                                <div class="dropdown-icon"><i class="fas fa-boxes"></i></div>
                                <div>
                                    <div>All Products</div>
                                    <div class="text-muted small">Manage all products</div>
                                </div>
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item d-flex align-items-center" href="../admin/users.php">
                                <div class="dropdown-icon"><i class="fas fa-users"></i></div>
                                <div>
                                    <div>User Management</div>
                                    <div class="text-muted small">Manage all users</div>
                                </div>
                            </a>
                        </li>
                        <?php endif; ?>

                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item d-flex align-items-center text-danger" href="logout.php">
                                <div class="dropdown-icon"><i class="fas fa-sign-out-alt"></i></div>
                                <div>
                                    <div>Logout</div>
                                    <div class="text-muted small">Sign out of your account</div>
                                </div>
                            </a>
                        </li>
                    </ul>
                </div>
                <?php else: ?>
                <div class="d-flex gap-2">
                    <a href="login.php" class="btn btn-outline-light">
                        <i class="fas fa-sign-in-alt me-1"></i>Login
                    </a>
                    <a href="register.php" class="btn btn-primary">
                        <i class="fas fa-user-plus me-1"></i>Register
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

<!-- Mobile Search Collapse -->
<div class="collapse bg-dark py-3 shadow" id="mobileSearch">
    <div class="container">
        <form action="shop.php" method="GET" class="d-flex">
            <input class="form-control me-2 rounded-start" type="search" name="search" 
                   placeholder="Search for sneakers, brands, categories..." aria-label="Search">
            <button class="btn btn-primary rounded-end" type="submit">
                <i class="fas fa-search"></i>
            </button>
        </form>
    </div>
</div>

<!-- Space for fixed navbar -->
<div style="height: 76px;"></div>

<style>
    /* Custom Navigation Styles */
    .navbar-dark {
        background: linear-gradient(135deg, #0b0f0e 0%, #1a1f1e 100%) !important;
        border-bottom: 1px solid rgba(22, 163, 74, 0.2);
    }

    /* Brand Logo */
    .brand-logo {
        transition: transform 0.3s ease;
    }
    
    .brand-logo:hover {
        transform: rotate(15deg);
    }

    /* Navigation Items */
    .nav-link {
        padding: 0.5rem 1rem;
        border-radius: 0.5rem;
        margin: 0 0.125rem;
        transition: all 0.3s ease;
    }
    
    .nav-link:hover, .nav-link.active {
        background: rgba(22, 163, 74, 0.1);
        transform: translateY(-2px);
    }
    
    .nav-link.active {
        color: #16a34a !important;
        font-weight: 600;
    }

    .nav-icon {
        display: inline-block;
        width: 24px;
        text-align: center;
        margin-right: 0.5rem;
    }
    
    .nav-text {
        display: inline-block;
    }

    /* Search */
    .search-group input {
        background: rgba(255, 255, 255, 0.1);
        border: 1px solid rgba(255, 255, 255, 0.2);
        color: white;
    }
    
    .search-group input::placeholder {
        color: rgba(255, 255, 255, 0.6);
    }
    
    .search-group input:focus {
        background: rgba(255, 255, 255, 0.15);
        border-color: #16a34a;
        color: white;
        box-shadow: 0 0 0 0.25rem rgba(22, 163, 74, 0.25);
    }

    /* Wishlist & Cart Icons */
    .nav-icon-btn {
        padding: 0.5rem;
        border-radius: 0.5rem;
        transition: all 0.3s ease;
    }
    
    .nav-icon-btn:hover {
        background: rgba(22, 163, 74, 0.1);
        transform: translateY(-2px);
    }
    
    .wishlist-badge, .cart-badge {
        font-size: 0.65rem;
        padding: 0.35em 0.6em;
        animation: pulse 2s infinite;
    }
    
    @keyframes pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.1); }
        100% { transform: scale(1); }
    }

    /* Dropdowns */
    .dropdown-menu {
        border: 1px solid rgba(22, 163, 74, 0.2);
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        animation: fadeIn 0.3s ease;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .dropdown-menu-dark {
        background: linear-gradient(135deg, #1a1f1e, #0b0f0e);
        color: white;
    }
    
    .dropdown-item {
        padding: 0.75rem 1rem;
        border-radius: 0.5rem;
        margin: 0.125rem;
        transition: all 0.2s ease;
    }
    
    .dropdown-item:hover {
        background: rgba(22, 163, 74, 0.1);
        transform: translateX(5px);
    }
    
    .dropdown-icon {
        width: 24px;
        text-align: center;
        margin-right: 0.75rem;
        color: #16a34a;
    }

    /* Wishlist Dropdown */
    .wishlist-dropdown {
        background: linear-gradient(135deg, #ffffff, #f8f9fa);
    }
    
    .wishlist-item:hover {
        background: #e9ecef !important;
        transform: translateX(-5px);
        transition: all 0.3s ease;
    }

    /* User Avatar */
    .user-avatar, .user-avatar-placeholder {
        width: 40px;
        height: 40px;
        object-fit: cover;
        border: 2px solid rgba(22, 163, 74, 0.3);
        transition: all 0.3s ease;
    }
    
    .user-dropdown-toggle:hover .user-avatar,
    .user-dropdown-toggle:hover .user-avatar-placeholder {
        border-color: #16a34a;
        transform: scale(1.1);
    }
    
    .user-avatar-placeholder {
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1rem;
    }

    /* Buttons */
    .btn-primary {
        background: linear-gradient(135deg, #16a34a, #0f7a35);
        border: none;
        transition: all 0.3s ease;
    }
    
    .btn-primary:hover {
        background: linear-gradient(135deg, #0f7a35, #16a34a);
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(22, 163, 74, 0.3);
    }
    
    .btn-outline-light {
        border-color: rgba(255, 255, 255, 0.3);
        transition: all 0.3s ease;
    }
    
    .btn-outline-light:hover {
        background: rgba(255, 255, 255, 0.1);
        border-color: #16a34a;
        transform: translateY(-2px);
    }

    /* Mobile Search */
    #mobileSearch {
        border-bottom: 1px solid rgba(22, 163, 74, 0.2);
    }
    
    #mobileSearch input {
        background: rgba(255, 255, 255, 0.1);
        border: 1px solid rgba(255, 255, 255, 0.2);
        color: white;
    }
    
    #mobileSearch input::placeholder {
        color: rgba(255, 255, 255, 0.6);
    }

    /* Active States */
    .dropdown-item.active, .dropdown-item:active {
        background: linear-gradient(135deg, #16a34a, #0f7a35);
        color: white;
    }

    /* Text Colors */
    .text-primary {
        color: #16a34a !important;
    }
    
    .bg-primary {
        background-color: #16a34a !important;
    }
    
    .border-primary {
        border-color: #16a34a !important;
    }

    /* Responsive */
    @media (max-width: 991px) {
        .navbar-nav {
            padding: 1rem 0;
        }
        
        .nav-link {
            margin: 0.25rem 0;
        }
        
        .search-group input {
            min-width: auto;
        }
        
        .dropdown-menu {
            border: none;
            box-shadow: none;
        }
    }
    
    @media (max-width: 768px) {
        .navbar-brand {
            font-size: 1.1rem;
        }
        
        .nav-text {
            font-size: 0.9rem;
        }
        
        .btn {
            padding: 0.375rem 0.75rem;
            font-size: 0.875rem;
        }
    }
</style>

<script>
    // CSRF Token for AJAX
    const csrfToken = '<?php echo $csrf_token; ?>';
    
    // Initialize on page load
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize tooltips
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[title]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
        
        // Update cart and wishlist badges
        updateBadgeCounts();
    });
    
    // Add to cart from wishlist dropdown
    document.addEventListener('click', function(e) {
        if (e.target.closest('.add-to-cart-from-wishlist')) {
            const button = e.target.closest('.add-to-cart-from-wishlist');
            const productId = button.dataset.productId;
            const productName = button.dataset.productName;
            
            // Show loading state
            const originalHTML = button.innerHTML;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            button.disabled = true;
            
            // Send AJAX request
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
                            // Show success message
                            showAlert('success', `${productName} added to cart!`);
                            
                            // Update cart badge
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
                        button.innerHTML = originalHTML;
                        button.disabled = false;
                    }, 1500);
                }
            });
            
            // Prevent dropdown from closing
            e.preventDefault();
            e.stopPropagation();
        }
        
        // Wishlist dropdown - keep open on internal clicks
        if (e.target.closest('.wishlist-dropdown')) {
            e.stopPropagation();
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
        const cartBadge = document.querySelector('.cart-badge');
        const cartIcon = document.querySelector('.fa-shopping-cart')?.parentElement?.parentElement;
        
        if (count > 0) {
            if (cartBadge) {
                cartBadge.textContent = count;
                cartBadge.style.display = 'block';
            } else if (cartIcon) {
                const badge = document.createElement('span');
                badge.className = 'position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger cart-badge';
                badge.textContent = count;
                cartIcon.appendChild(badge);
            }
        } else if (cartBadge) {
            cartBadge.remove();
        }
    }
    
    // Update wishlist badge
    function updateWishlistBadge(count) {
        const wishlistBadge = document.querySelector('.wishlist-badge');
        const wishlistIcon = document.querySelector('.fa-heart')?.parentElement?.parentElement;
        
        if (count > 0) {
            if (wishlistBadge) {
                wishlistBadge.textContent = count;
                wishlistBadge.style.display = 'block';
            } else if (wishlistIcon) {
                const badge = document.createElement('span');
                badge.className = 'position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger wishlist-badge';
                badge.textContent = count;
                wishlistIcon.appendChild(badge);
            }
        } else if (wishlistBadge) {
            wishlistBadge.remove();
        }
    }
    
    // Show alert message
    function showAlert(type, message) {
        // Remove existing alerts
        document.querySelectorAll('.custom-alert').forEach(alert => alert.remove());
        
        const alertDiv = document.createElement('div');
        alertDiv.className = `custom-alert alert alert-${type} alert-dismissible fade show position-fixed`;
        alertDiv.style.cssText = 'top: 90px; right: 20px; z-index: 9999; min-width: 300px; animation: slideIn 0.3s ease;';
        
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
    
    // Add animation styles
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