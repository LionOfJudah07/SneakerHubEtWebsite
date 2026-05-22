<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in and is a vendor
// Remove the redirect from here - let the main page handle authentication
$is_vendor = isset($_SESSION['user_id']) && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'vendor';

// Get vendor data for display if logged in
if ($is_vendor) {
    require_once '../classes/User.php';
    $user = new User();
    $vendor_data = $user->getUserById($_SESSION['user_id']);
    $store_name = $vendor_data['store_name'] ?: $vendor_data['first_name'] . "'s Store";

    // Get cart count
    require_once '../includes/functions.php';
    $cart_count = get_cart_count();

    // Get stats if on dashboard page
    $stats = [];
    if (basename($_SERVER['PHP_SELF']) == 'index.php' || basename($_SERVER['PHP_SELF']) == 'navbar.php') {
        try {
            require_once '../classes/Database.php';
            $db = new Database();

            // Pending orders count
            $db->query("SELECT COUNT(DISTINCT o.id) as count 
                        FROM orders o 
                        JOIN order_items oi ON o.id = oi.order_id 
                        JOIN products p ON oi.product_id = p.id 
                        WHERE p.vendor_id = :vendor_id AND o.status = 'pending'");
            $db->bind(':vendor_id', $_SESSION['user_id']);
            $stats['pending_orders'] = $db->single()['count'];

            // Total products count
            $db->query("SELECT COUNT(*) as count FROM products WHERE vendor_id = :vendor_id");
            $db->bind(':vendor_id', $_SESSION['user_id']);
            $stats['total_products'] = $db->single()['count'];

            // Available balance
            $db->query("SELECT COALESCE(SUM(oi.subtotal), 0) as total 
                        FROM order_items oi 
                        JOIN products p ON oi.product_id = p.id 
                        JOIN orders o ON oi.order_id = o.id 
                        WHERE p.vendor_id = :vendor_id AND o.status = 'delivered'");
            $db->bind(':vendor_id', $_SESSION['user_id']);
            $total_sales = $db->single()['total'];

            $db->query("SELECT COALESCE(SUM(amount), 0) as total FROM vendor_withdrawals 
                        WHERE vendor_id = :vendor_id AND status = 'completed'");
            $db->bind(':vendor_id', $_SESSION['user_id']);
            $withdrawn = $db->single()['total'];
            $stats['available_balance'] = $total_sales - $withdrawn;
        } catch (Exception $e) {
            error_log("Navbar stats error: " . $e->getMessage());
            $stats = [];
        }
    }
}
?>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
    <div class="container-fluid">
        <!-- Sidebar Toggle Button -->
        <button class="navbar-toggler me-2" type="button" data-bs-toggle="collapse" data-bs-target="#vendorSidebar">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Brand/Logo -->
        <a class="navbar-brand" href="index.php">
            <i class="fas fa-store me-2"></i>
            <span class="d-none d-md-inline">
                <?php if ($is_vendor): ?>
                    <?php echo htmlspecialchars($store_name); ?> - Vendor Panel
                <?php else: ?>
                    Vendor Panel
                <?php endif; ?>
            </span>
            <span class="d-inline d-md-none">Vendor Panel</span>
        </a>

        <!-- Mobile Toggle Button -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#vendorNavbar">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Navbar Content -->
        <div class="collapse navbar-collapse" id="vendorNavbar">
            <ul class="navbar-nav ms-auto">
                <?php if ($is_vendor): ?>
                    <!-- Dashboard Link -->
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">
                            <i class="fas fa-tachometer-alt"></i>
                            <span class="d-lg-none ms-2">Dashboard</span>
                        </a>
                    </li>

                    <!-- Products Dropdown -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-box"></i>
                            <span class="d-lg-none ms-2">Products</span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-dark">
                            <li><a class="dropdown-item" href="products.php"><i class="fas fa-list me-2"></i>All Products</a></li>
                            <li><a class="dropdown-item" href="products.php?action=add"><i class="fas fa-plus me-2"></i>Add New Product</a></li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li><a class="dropdown-item" href="products.php?status=active"><i class="fas fa-check-circle me-2"></i>Active Products</a></li>
                            <li><a class="dropdown-item" href="products.php?status=pending"><i class="fas fa-clock me-2"></i>Pending Review</a></li>
                            <li><a class="dropdown-item" href="products.php?stock=low"><i class="fas fa-exclamation-triangle me-2"></i>Low Stock</a></li>
                        </ul>
                    </li>

                    <!-- Orders -->
                    <li class="nav-item">
                        <a class="nav-link" href="orders.php">
                            <i class="fas fa-shopping-cart"></i>
                            <span class="d-lg-none ms-2">Orders</span>
                            <?php if (($stats['pending_orders'] ?? 0) > 0): ?>
                                <span class="badge bg-danger rounded-pill"><?php echo $stats['pending_orders']; ?></span>
                            <?php endif; ?>
                        </a>
                    </li>

                    <!-- Earnings -->
                    <li class="nav-item">
                        <a class="nav-link" href="earnings.php">
                            <i class="fas fa-money-bill-wave"></i>
                            <span class="d-lg-none ms-2">Earnings</span>
                        </a>
                    </li>

                    <!-- Customer View -->
                    <li class="nav-item">
                        <a class="nav-link" href="../public/index.php" target="_blank" title="View Store">
                            <i class="fas fa-eye"></i>
                            <span class="d-lg-none ms-2">View Store</span>
                        </a>
                    </li>

                    <!-- Vendor Dropdown -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <img src="<?php
                                        if ($is_vendor && function_exists('get_user_avatar')) {
                                            echo get_user_avatar($_SESSION['user_id'], $vendor_data['profile_image']);
                                        } else {
                                            echo '../assets/images/default-avatar.png';
                                        }
                                        ?>"
                                alt="Profile"
                                width="30"
                                height="30"
                                class="rounded-circle">
                        </a>
                        <ul class="dropdown-menu dropdown-menu-dark dropdown-menu-end">
                            <?php if ($is_vendor): ?>
                                <li>
                                    <div class="dropdown-header">
                                        <h6 class="mb-0"><?php echo htmlspecialchars($store_name); ?></h6>
                                        <small class="text-muted">Vendor Account</small>
                                    </div>
                                </li>
                                <li>
                                    <hr class="dropdown-divider">
                                </li>
                                <li><a class="dropdown-item" href="profile.php"><i class="fas fa-store me-2"></i>Store Profile</a></li>
                                <li><a class="dropdown-item" href="../buyer/profile.php"><i class="fas fa-user me-2"></i>My Profile</a></li>
                                <li>
                                    <hr class="dropdown-divider">
                                </li>
                            <?php endif; ?>
                            <li><a class="dropdown-item" href="../public/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <!-- Show login option if not authenticated -->
                    <li class="nav-item">
                        <a class="nav-link" href="../public/login.php">
                            <i class="fas fa-sign-in-alt"></i>
                            <span class="d-lg-none ms-2">Login</span>
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<!-- Vendor Sidebar - Only show if logged in as vendor -->
<?php if ($is_vendor): ?>
    <div class="collapse" id="vendorSidebar">
        <div class="bg-dark text-white vh-100 p-3">
            <div class="d-flex flex-column h-100">
                <!-- Store Info -->
                <div class="mb-4">
                    <div class="d-flex align-items-center mb-3">
                        <img src="<?php
                                    if (function_exists('get_user_avatar')) {
                                        echo get_user_avatar($_SESSION['user_id'], $vendor_data['profile_image']);
                                    } else {
                                        echo '../assets/images/default-avatar.png';
                                    }
                                    ?>"
                            alt="Store Logo"
                            width="50"
                            height="50"
                            class="rounded-circle me-3">
                        <div>
                            <h6 class="mb-0"><?php echo htmlspecialchars($store_name); ?></h6>
                            <small class="text-muted">Vendor Store</small>
                        </div>
                    </div>
                    <div class="d-grid">
                        <a href="profile.php" class="btn btn-outline-light btn-sm">
                            <i class="fas fa-cog me-2"></i>Store Settings
                        </a>
                    </div>
                </div>

                <!-- Navigation -->
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link text-white <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>" href="index.php">
                            <i class="fas fa-tachometer-alt me-2"></i>
                            Dashboard
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link text-white <?php echo basename($_SERVER['PHP_SELF']) == 'products.php' ? 'active' : ''; ?>"
                            href="products.php">
                            <i class="fas fa-box me-2"></i>
                            Products
                            <span class="badge bg-info float-end"><?php echo $stats['total_products'] ?? 0; ?></span>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link text-white <?php echo basename($_SERVER['PHP_SELF']) == 'orders.php' ? 'active' : ''; ?>"
                            href="orders.php">
                            <i class="fas fa-shopping-cart me-2"></i>
                            Orders
                            <?php if (($stats['pending_orders'] ?? 0) > 0): ?>
                                <span class="badge bg-danger float-end"><?php echo $stats['pending_orders']; ?></span>
                            <?php endif; ?>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link text-white <?php echo basename($_SERVER['PHP_SELF']) == 'earnings.php' ? 'active' : ''; ?>" href="earnings.php">
                            <i class="fas fa-money-bill-wave me-2"></i>
                            Earnings
                            <span class="badge bg-success float-end"><?php echo isset($stats['available_balance']) ? format_price($stats['available_balance']) : 'ETB 0'; ?></span>
                        </a>
                    </li>
                </ul>

                <!-- Quick Stats -->
                <div class="mt-auto pt-4 border-top border-secondary">
                    <div class="row text-center g-2">
                        <div class="col-6">
                            <div class="bg-primary bg-opacity-25 p-2 rounded">
                                <small class="d-block text-muted">Products</small>
                                <strong class="d-block"><?php echo $stats['total_products'] ?? 0; ?></strong>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="bg-warning bg-opacity-25 p-2 rounded">
                                <small class="d-block text-muted">Pending</small>
                                <strong class="d-block"><?php echo $stats['pending_orders'] ?? 0; ?></strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>