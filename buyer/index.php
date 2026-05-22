<?php
require_once '../config.php';
require_once '../functions.php';
require_once '../classes/User.php';
require_once '../classes/Order.php';
require_once '../classes/Product.php';

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Require buyer login
if (!is_logged_in()) {
    $_SESSION['redirect_to'] = $_SERVER['REQUEST_URI'];
    header('Location: ../public/login.php');
    exit();
}

if (!is_buyer()) {
    $_SESSION['error'] = 'Access denied. Please login as a buyer.';
    header('Location: ../public/index.php');
    exit();
}

$page_title = 'Buyer Dashboard - ' . SITE_NAME;

// Get user data
$user = new User();
$user_data = $user->getUserById($_SESSION['user_id']);

// Get recent orders
$recent_orders = [];
if (class_exists('Order')) {
    $order = new Order();
    $recent_orders = $order->getOrdersByBuyer($_SESSION['user_id'], 5);
} else {
    // Fallback: Use direct database query
    $db = new Database();
    $db->query("SELECT * FROM orders WHERE buyer_id = :buyer_id ORDER BY created_at DESC LIMIT 5");
    $db->bind(':buyer_id', $_SESSION['user_id']);
    $recent_orders = $db->resultSet();
}

// Get wishlist items
$wishlist_items = [];
if (!empty($_SESSION['wishlist'])) {
    $product = new Product();
    foreach ($_SESSION['wishlist'] as $product_id) {
        $product_data = $product->getProductById($product_id);
        if ($product_data) {
            // Handle images
            $images = [];
            if (!empty($product_data['images'])) {
                if (is_string($product_data['images'])) {
                    $images = json_decode($product_data['images'], true);
                } elseif (is_array($product_data['images'])) {
                    $images = $product_data['images'];
                }
            }
            $product_data['images'] = $images;
            $wishlist_items[] = $product_data;
        }
    }
    $wishlist_items = array_slice($wishlist_items, 0, 4);
}

// Get cart count
$cart_count = get_cart_count();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Custom CSS -->
    <link href="../assets/css/style.css" rel="stylesheet">
    <link href="../assets/css/bootstrap-custom.css" rel="stylesheet">
</head>

<body>
    <!-- Navigation -->
    <?php
    $nav_path = '../public/includes/navbar.php';
    if (file_exists($nav_path)) {
        include $nav_path;
    } else {
        // Fallback navbar
        echo '<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
            <div class="container">
                <a class="navbar-brand" href="../public/index.php">
                    <i class="fas fa-shoe-prints"></i> ' . SITE_NAME . '
                </a>
                <div class="navbar-nav ms-auto">
                    <a class="nav-link" href="../public/shop.php">Shop</a>
                    <a class="nav-link" href="../public/cart.php">Cart</a>
                    <a class="nav-link" href="profile.php">Profile</a>
                    <a class="nav-link text-danger" href="../public/logout.php">Logout</a>
                </div>
            </div>
        </nav>';
    }
    ?>

    <!-- Dashboard Layout -->
    <div class="container-fluid py-4">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-lg-3">
                <div class="card shadow-sm mb-4">
                    <div class="card-body text-center">
                        <div class="mb-3">
                            <?php
                            $avatar = get_user_avatar($_SESSION['user_id'], $user_data['profile_image'] ?? '');
                            if (strpos($avatar, 'http') === false && !file_exists(str_replace(SITE_URL . '/', '', $avatar))) {
                                $avatar = '../assets/images/users/default.png';
                            }
                            ?>
                            <img src="<?php echo $avatar; ?>"
                                alt="Profile" class="rounded-circle" style="width: 100px; height: 100px; object-fit: cover;">
                        </div>
                        <h5><?php echo htmlspecialchars(($user_data['first_name'] ?? '') . ' ' . ($user_data['last_name'] ?? '')); ?></h5>
                        <p class="text-muted mb-3">
                            <i class="fas fa-user-circle me-1"></i> Buyer Account
                        </p>
                        <div class="d-grid gap-2">
                            <a href="profile.php" class="btn btn-outline-primary">
                                <i class="fas fa-user-edit me-2"></i>Edit Profile
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Dashboard Menu -->
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h6 class="mb-0"><i class="fas fa-bars me-2"></i>Dashboard Menu</h6>
                    </div>
                    <div class="list-group list-group-flush">
                        <a href="index.php" class="list-group-item list-group-item-action active">
                            <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                        </a>
                        <a href="orders.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-shopping-bag me-2"></i>My Orders
                        </a>
                        <a href="wishlist.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-heart me-2"></i>Wishlist
                            <?php if (!empty($_SESSION['wishlist'])): ?>
                                <span class="badge bg-danger float-end"><?php echo count($_SESSION['wishlist']); ?></span>
                            <?php endif; ?>
                        </a>
                        <a href="profile.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-user me-2"></i>My Profile
                        </a>
                        <a href="addresses.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-address-book me-2"></i>Address Book
                        </a>
                        <a href="payment-methods.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-credit-card me-2"></i>Payment Methods
                        </a>
                        <a href="../public/logout.php" class="list-group-item list-group-item-action text-danger">
                            <i class="fas fa-sign-out-alt me-2"></i>Logout
                        </a>
                    </div>
                </div>

                <!-- Quick Stats -->
                <div class="card shadow-sm mt-4">
                    <div class="card-header bg-light">
                        <h6 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Quick Stats</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-6 text-center mb-3">
                                <div class="display-6 fw-bold text-primary">
                                    <?php echo count($recent_orders); ?>
                                </div>
                                <small class="text-muted">Orders</small>
                            </div>
                            <div class="col-6 text-center mb-3">
                                <div class="display-6 fw-bold text-success">
                                    <?php echo count($wishlist_items); ?>
                                </div>
                                <small class="text-muted">Wishlist</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-lg-9">
                <!-- Welcome Message -->
                <?php if (isset($_SESSION['welcome_message'])): ?>
                    <div class="alert alert-success alert-dismissible fade show mb-4">
                        <i class="fas fa-check-circle me-2"></i>
                        <?php echo $_SESSION['welcome_message']; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        <?php unset($_SESSION['welcome_message']); ?>
                    </div>
                <?php endif; ?>

                <!-- Dashboard Header -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2 class="mb-1">Welcome back, <?php echo htmlspecialchars($user_data['first_name'] ?? 'User'); ?>!</h2>
                        <p class="text-muted mb-0">Here's what's happening with your account today.</p>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="../public/shop.php" class="btn btn-primary">
                            <i class="fas fa-shopping-bag me-2"></i>Continue Shopping
                        </a>
                        <a href="../public/cart.php" class="btn btn-outline-primary position-relative">
                            <i class="fas fa-shopping-cart"></i>
                            <?php if ($cart_count > 0): ?>
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                    <?php echo $cart_count; ?>
                                </span>
                            <?php endif; ?>
                        </a>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div class="row mb-4">
                    <div class="col-md-3 mb-3">
                        <div class="card bg-primary text-white shadow-sm">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-white-50">Total Orders</h6>
                                        <h3 class="mb-0"><?php echo count($recent_orders); ?></h3>
                                    </div>
                                    <i class="fas fa-shopping-bag fa-2x opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card bg-success text-white shadow-sm">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-white-50">Wishlist</h6>
                                        <h3 class="mb-0"><?php echo count($wishlist_items); ?></h3>
                                    </div>
                                    <i class="fas fa-heart fa-2x opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card bg-warning text-white shadow-sm">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-white-50">Pending</h6>
                                        <h3 class="mb-0">
                                            <?php
                                            $pending_count = 0;
                                            foreach ($recent_orders as $order) {
                                                if (($order['status'] ?? '') === 'pending') $pending_count++;
                                            }
                                            echo $pending_count;
                                            ?>
                                        </h3>
                                    </div>
                                    <i class="fas fa-clock fa-2x opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card bg-info text-white shadow-sm">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-white-50">Delivered</h6>
                                        <h3 class="mb-0">
                                            <?php
                                            $delivered_count = 0;
                                            foreach ($recent_orders as $order) {
                                                if (($order['status'] ?? '') === 'delivered') $delivered_count++;
                                            }
                                            echo $delivered_count;
                                            ?>
                                        </h3>
                                    </div>
                                    <i class="fas fa-check-circle fa-2x opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Orders -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-light d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-history me-2"></i>Recent Orders</h5>
                        <a href="orders.php" class="btn btn-sm btn-outline-primary">View All</a>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recent_orders)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-shopping-bag fa-3x text-muted mb-3"></i>
                                <h5>No Orders Yet</h5>
                                <p class="text-muted">You haven't placed any orders yet.</p>
                                <a href="../public/shop.php" class="btn btn-primary">Start Shopping</a>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Order #</th>
                                            <th>Date</th>
                                            <th>Total</th>
                                            <th>Status</th>
                                            <th>Payment</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_orders as $order_item): ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo $order_item['order_number'] ?? 'N/A'; ?></strong>
                                                </td>
                                                <td><?php echo format_date($order_item['created_at'] ?? '', 'M d, Y'); ?></td>
                                                <td><?php echo format_price($order_item['total_amount'] ?? 0); ?></td>
                                                <td><?php echo get_order_status_badge($order_item['status'] ?? 'pending'); ?></td>
                                                <td><?php echo get_payment_status_badge($order_item['payment_status'] ?? 'pending'); ?></td>
                                                <td>
                                                    <a href="order-detail.php?id=<?php echo $order_item['id'] ?? ''; ?>"
                                                        class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Wishlist Items -->
                <?php if (!empty($wishlist_items)): ?>
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-light d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="fas fa-heart me-2"></i>Wishlist Items</h5>
                            <a href="wishlist.php" class="btn btn-sm btn-outline-primary">View All</a>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <?php foreach ($wishlist_items as $item): ?>
                                    <div class="col-md-3 col-sm-6 mb-3">
                                        <div class="card h-100">
                                            <?php
                                            $image = '../assets/images/products/default.jpg';
                                            if (!empty($item['images']) && is_array($item['images']) && !empty($item['images'][0])) {
                                                $image = '../' . $item['images'][0];
                                            }
                                            ?>
                                            <img src="<?php echo $image; ?>"
                                                class="card-img-top" alt="<?php echo htmlspecialchars($item['name'] ?? ''); ?>"
                                                style="height: 150px; object-fit: cover;">
                                            <div class="card-body">
                                                <h6 class="card-title"><?php echo htmlspecialchars($item['name'] ?? ''); ?></h6>
                                                <p class="card-text text-muted small mb-2"><?php echo htmlspecialchars($item['brand'] ?? ''); ?></p>
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <span class="fw-bold"><?php echo format_price($item['price'] ?? 0); ?></span>
                                                    <div>
                                                        <a href="../public/cart.php?action=add&product_id=<?php echo $item['id'] ?? ''; ?>"
                                                            class="btn btn-sm btn-outline-primary me-1">
                                                            <i class="fas fa-cart-plus"></i>
                                                        </a>
                                                        <a href="wishlist.php?remove=<?php echo $item['id'] ?? ''; ?>"
                                                            class="btn btn-sm btn-outline-danger">
                                                            <i class="fas fa-times"></i>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Recommended Products -->
                <div class="card shadow-sm">
                    <div class="card-header bg-light">
                        <h5 class="mb-0"><i class="fas fa-fire me-2"></i>Recommended For You</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        $recommended = [];
                        if (class_exists('Product')) {
                            $product = new Product();
                            $recommended = $product->getFeaturedProducts(4);
                        } else {
                            // Fallback: Get random products
                            $db = new Database();
                            $db->query("SELECT * FROM products WHERE status = 'active' ORDER BY RANDOM() LIMIT 4");
                            $recommended = $db->resultSet();
                        }
                        ?>
                        <div class="row">
                            <?php foreach ($recommended as $item): ?>
                                <div class="col-md-3 col-sm-6 mb-3">
                                    <div class="card h-100">
                                        <?php
                                        $image = '../assets/images/products/default.jpg';
                                        if (!empty($item['images'])) {
                                            $images = is_string($item['images']) ? json_decode($item['images'], true) : $item['images'];
                                            if (is_array($images) && !empty($images[0])) {
                                                $image = '../' . $images[0];
                                            }
                                        }
                                        ?>
                                        <img src="<?php echo $image; ?>"
                                            class="card-img-top" alt="<?php echo htmlspecialchars($item['name'] ?? ''); ?>"
                                            style="height: 150px; object-fit: cover;">
                                        <div class="card-body">
                                            <h6 class="card-title"><?php echo htmlspecialchars($item['name'] ?? ''); ?></h6>
                                            <p class="card-text text-muted small mb-2"><?php echo htmlspecialchars($item['brand'] ?? ''); ?></p>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span class="fw-bold"><?php echo format_price($item['price'] ?? 0); ?></span>
                                                <a href="../public/product-detail.php?id=<?php echo $item['id'] ?? ''; ?>"
                                                    class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <?php
    $footer_path = '../public/includes/footer.php';
    if (file_exists($footer_path)) {
        include $footer_path;
    } else {
        echo '<footer class="bg-dark text-white py-4 mt-5">
            <div class="container">
                <div class="row">
                    <div class="col-md-6">
                        <p class="mb-0">&copy; ' . date('Y') . ' ' . SITE_NAME . '. All rights reserved.</p>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <a href="../public/contact.php" class="text-white text-decoration-none me-3">Contact</a>
                        <a href="../public/terms.php" class="text-white text-decoration-none me-3">Terms</a>
                        <a href="../public/privacy.php" class="text-white text-decoration-none">Privacy</a>
                    </div>
                </div>
            </div>
        </footer>';
    }
    ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script src="../assets/js/buyer.js"></script>
</body>

</html>