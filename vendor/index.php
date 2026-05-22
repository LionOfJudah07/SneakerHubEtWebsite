<?php
require_once '../config.php';

// Require vendor login
require_vendor();

$page_title = 'Vendor Dashboard - ' . SITE_NAME;

// Get vendor data
$user = new User();
$vendor_data = $user->getUserById($_SESSION['user_id']);

// Get vendor statistics
$stats = [];
try {
    $db = new Database();

    // Total products
    $db->query("SELECT COUNT(*) as count FROM products WHERE vendor_id = :vendor_id");
    $db->bind(':vendor_id', $_SESSION['user_id']);
    $stats['total_products'] = $db->single()['count'];

    // Active products
    $db->query("SELECT COUNT(*) as count FROM products WHERE vendor_id = :vendor_id AND status = 'active'");
    $db->bind(':vendor_id', $_SESSION['user_id']);
    $stats['active_products'] = $db->single()['count'];

    // Pending products
    $db->query("SELECT COUNT(*) as count FROM products WHERE vendor_id = :vendor_id AND status = 'pending'");
    $db->bind(':vendor_id', $_SESSION['user_id']);
    $stats['pending_products'] = $db->single()['count'];

    // Out of stock
    $db->query("SELECT COUNT(*) as count FROM products WHERE vendor_id = :vendor_id AND stock_quantity = 0");
    $db->bind(':vendor_id', $_SESSION['user_id']);
    $stats['out_of_stock'] = $db->single()['count'];

    // Low stock (< 10)
    $db->query("SELECT COUNT(*) as count FROM products WHERE vendor_id = :vendor_id AND stock_quantity <= 10 AND stock_quantity > 0");
    $db->bind(':vendor_id', $_SESSION['user_id']);
    $stats['low_stock'] = $db->single()['count'];

    // Total orders
    $db->query("SELECT COUNT(DISTINCT o.id) as count 
                FROM orders o 
                JOIN order_items oi ON o.id = oi.order_id 
                JOIN products p ON oi.product_id = p.id 
                WHERE p.vendor_id = :vendor_id");
    $db->bind(':vendor_id', $_SESSION['user_id']);
    $stats['total_orders'] = $db->single()['count'];

    // Pending orders
    $db->query("SELECT COUNT(DISTINCT o.id) as count 
                FROM orders o 
                JOIN order_items oi ON o.id = oi.order_id 
                JOIN products p ON oi.product_id = p.id 
                WHERE p.vendor_id = :vendor_id AND o.status = 'pending'");
    $db->bind(':vendor_id', $_SESSION['user_id']);
    $stats['pending_orders'] = $db->single()['count'];

    // Processing orders
    $db->query("SELECT COUNT(DISTINCT o.id) as count 
                FROM orders o 
                JOIN order_items oi ON o.id = oi.order_id 
                JOIN products p ON oi.product_id = p.id 
                WHERE p.vendor_id = :vendor_id AND o.status = 'processing'");
    $db->bind(':vendor_id', $_SESSION['user_id']);
    $stats['processing_orders'] = $db->single()['count'];

    // Total sales
    $db->query("SELECT COALESCE(SUM(oi.subtotal), 0) as total 
                FROM order_items oi 
                JOIN products p ON oi.product_id = p.id 
                JOIN orders o ON oi.order_id = o.id 
                WHERE p.vendor_id = :vendor_id AND o.status = 'delivered'");
    $db->bind(':vendor_id', $_SESSION['user_id']);
    $stats['total_sales'] = $db->single()['total'];

    // Pending withdrawal
    $db->query("SELECT COALESCE(SUM(amount), 0) as total FROM vendor_withdrawals 
                WHERE vendor_id = :vendor_id AND status = 'pending'");
    $db->bind(':vendor_id', $_SESSION['user_id']);
    $stats['pending_withdrawal'] = $db->single()['total'];

    // Available balance (sales - withdrawn)
    $db->query("SELECT COALESCE(SUM(amount), 0) as total FROM vendor_withdrawals 
                WHERE vendor_id = :vendor_id AND status = 'completed'");
    $db->bind(':vendor_id', $_SESSION['user_id']);
    $withdrawn = $db->single()['total'];
    $stats['available_balance'] = $stats['total_sales'] - $withdrawn;

    // Get recent orders
    $db->query("SELECT DISTINCT o.*, u.first_name, u.last_name 
                FROM orders o 
                JOIN order_items oi ON o.id = oi.order_id 
                JOIN products p ON oi.product_id = p.id 
                JOIN users u ON o.buyer_id = u.id 
                WHERE p.vendor_id = :vendor_id 
                ORDER BY o.created_at DESC 
                LIMIT 10");
    $db->bind(':vendor_id', $_SESSION['user_id']);
    $recent_orders = $db->resultSet();

    // Get recent products
    $db->query("SELECT * FROM products 
                WHERE vendor_id = :vendor_id 
                ORDER BY created_at DESC 
                LIMIT 10");
    $db->bind(':vendor_id', $_SESSION['user_id']);
    $recent_products = $db->resultSet();
} catch (Exception $e) {
    error_log("Vendor dashboard error: " . $e->getMessage());
    $stats = [];
    $recent_orders = [];
    $recent_products = [];
}

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
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Custom CSS -->
    <link href="../assets/css/style.css" rel="stylesheet">
    <link href="../assets/css/bootstrap-custom.css" rel="stylesheet">
</head>

<body>
    <!-- Navigation -->
    <?php include '../public/includes/navbar.php'; ?>

    <!-- Dashboard Layout -->
    <div class="container-fluid">
        <div class="row">
            <!-- Vendor Sidebar -->
            <div class="col-md-3 col-lg-2 d-md-block bg-dark sidebar collapse">
                <div class="position-sticky pt-3">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link active text-white" href="index.php">
                                <i class="fas fa-tachometer-alt me-2"></i>
                                Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="products.php">
                                <i class="fas fa-box me-2"></i>
                                Products
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="orders.php">
                                <i class="fas fa-shopping-cart me-2"></i>
                                Orders
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="earnings.php">
                                <i class="fas fa-money-bill-wave me-2"></i>
                                Earnings
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="profile.php">
                                <i class="fas fa-store me-2"></i>
                                Store Profile
                            </a>
                        </li>
                    </ul>

                    <hr class="bg-light">

                    <div class="dropdown mt-4">
                        <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle"
                            data-bs-toggle="dropdown">
                            <img src="<?php echo get_user_avatar($_SESSION['user_id'], $vendor_data['profile_image']); ?>"
                                alt="<?php echo htmlspecialchars($vendor_data['store_name'] ?: $vendor_data['first_name']); ?>"
                                width="32" height="32" class="rounded-circle me-2">
                            <strong><?php echo htmlspecialchars($vendor_data['store_name'] ?: $vendor_data['first_name']); ?></strong>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-dark text-small shadow">
                            <li><a class="dropdown-item" href="../buyer/profile.php">My Profile</a></li>
                            <li><a class="dropdown-item" href="profile.php">Store Settings</a></li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li><a class="dropdown-item" href="../public/logout.php">Sign out</a></li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <!-- Page Header -->
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Vendor Dashboard</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="products.php?action=add" class="btn btn-sm btn-primary me-2">
                            <i class="fas fa-plus me-2"></i>Add New Product
                        </a>
                        <a href="orders.php" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-shopping-cart me-2"></i>View Orders
                        </a>
                    </div>
                </div>

                <!-- Welcome Message -->
                <div class="alert alert-info">
                    <h5><i class="fas fa-store me-2"></i>Welcome, <?php echo htmlspecialchars($vendor_data['store_name'] ?: $vendor_data['first_name']); ?>!</h5>
                    <p class="mb-0">Manage your products, track orders, and view earnings from your vendor dashboard.</p>
                </div>

                <!-- Stats Cards -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-primary shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                            Total Sales
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo format_price($stats['total_sales'] ?? 0); ?>
                                        </div>
                                        <div class="mt-2 mb-0 text-muted text-xs">
                                            <span class="text-success mr-2">
                                                <i class="fas fa-arrow-up"></i> 8%
                                            </span>
                                            <span>Since last month</span>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-dollar-sign fa-2x text-primary"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-success shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                            Total Orders
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo number_format($stats['total_orders'] ?? 0); ?>
                                        </div>
                                        <div class="mt-2 mb-0 text-muted text-xs">
                                            <span class="text-success mr-2">
                                                <i class="fas fa-arrow-up"></i> 12%
                                            </span>
                                            <span>Since last week</span>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-shopping-cart fa-2x text-success"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-info shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                            Active Products
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo number_format($stats['active_products'] ?? 0); ?>
                                        </div>
                                        <div class="mt-2 mb-0 text-muted text-xs">
                                            <span class="text-success mr-2">
                                                <i class="fas fa-arrow-up"></i> 5%
                                            </span>
                                            <span>Since last month</span>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-box fa-2x text-info"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-warning shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                            Pending Orders
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo number_format($stats['pending_orders'] ?? 0); ?>
                                        </div>
                                        <div class="mt-2 mb-0 text-muted text-xs">
                                            <span class="text-danger mr-2">
                                                <i class="fas fa-arrow-down"></i> 2%
                                            </span>
                                            <span>Since last week</span>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-clock fa-2x text-warning"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts & Recent Activity -->
                <div class="row">
                    <!-- Sales Chart -->
                    <div class="col-xl-8 col-lg-7">
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Sales Overview</h6>
                            </div>
                            <div class="card-body">
                                <div class="chart-area">
                                    <canvas id="salesChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Stock Status -->
                    <div class="col-xl-4 col-lg-5">
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Stock Status</h6>
                            </div>
                            <div class="card-body">
                                <div class="chart-pie pt-4 pb-2">
                                    <canvas id="stockChart"></canvas>
                                </div>
                                <div class="mt-4 text-center small">
                                    <span class="mr-2">
                                        <i class="fas fa-circle text-success"></i> In Stock
                                    </span>
                                    <span class="mr-2">
                                        <i class="fas fa-circle text-warning"></i> Low Stock
                                    </span>
                                    <span class="mr-2">
                                        <i class="fas fa-circle text-danger"></i> Out of Stock
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Orders & Products -->
                <div class="row">
                    <!-- Recent Orders -->
                    <div class="col-xl-6 col-lg-6">
                        <div class="card shadow mb-4">
                            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                <h6 class="m-0 font-weight-bold text-primary">Recent Orders</h6>
                                <a href="orders.php" class="btn btn-sm btn-primary">View All</a>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Order #</th>
                                                <th>Customer</th>
                                                <th>Amount</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($recent_orders as $order): ?>
                                                <tr>
                                                    <td>
                                                        <a href="order-detail.php?id=<?php echo $order['id']; ?>"
                                                            class="text-primary">
                                                            <?php echo $order['order_number']; ?>
                                                        </a>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?></td>
                                                    <td><?php echo format_price($order['total_amount']); ?></td>
                                                    <td>
                                                        <?php echo get_order_status_badge($order['status']); ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Products -->
                    <div class="col-xl-6 col-lg-6">
                        <div class="card shadow mb-4">
                            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                                <h6 class="m-0 font-weight-bold text-primary">Recent Products</h6>
                                <a href="products.php" class="btn btn-sm btn-primary">View All</a>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Product</th>
                                                <th>Price</th>
                                                <th>Stock</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($recent_products as $product): ?>
                                                <tr>
                                                    <td>
                                                        <h6 class="mb-1"><?php echo htmlspecialchars($product['name']); ?></h6>
                                                        <p class="text-muted small mb-0">SKU: <?php echo htmlspecialchars($product['sku']); ?></p>
                                                    </td>
                                                    <td>
                                                        <?php echo format_price($product['price']); ?>
                                                        <?php if ($product['discount_price']): ?>
                                                            <br><small class="text-danger"><?php echo format_price($product['discount_price']); ?></small>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php if ($product['stock_quantity'] > 10): ?>
                                                            <span class="badge bg-success"><?php echo $product['stock_quantity']; ?></span>
                                                        <?php elseif ($product['stock_quantity'] > 0): ?>
                                                            <span class="badge bg-warning"><?php echo $product['stock_quantity']; ?></span>
                                                        <?php else: ?>
                                                            <span class="badge bg-danger">Out of Stock</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php
                                                        $status_badges = [
                                                            'active' => 'badge bg-success',
                                                            'inactive' => 'badge bg-secondary',
                                                            'pending' => 'badge bg-warning'
                                                        ];
                                                        $badge_class = $status_badges[$product['status']] ?? 'badge bg-secondary';
                                                        ?>
                                                        <span class="<?php echo $badge_class; ?>">
                                                            <?php echo ucfirst($product['status']); ?>
                                                        </span>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="row">
                    <div class="col-12">
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Quick Actions</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-3 col-sm-6 mb-3">
                                        <a href="products.php?action=add" class="btn btn-primary w-100">
                                            <i class="fas fa-plus me-2"></i>Add Product
                                        </a>
                                    </div>
                                    <div class="col-md-3 col-sm-6 mb-3">
                                        <a href="products.php" class="btn btn-success w-100">
                                            <i class="fas fa-box me-2"></i>Manage Products
                                        </a>
                                    </div>
                                    <div class="col-md-3 col-sm-6 mb-3">
                                        <a href="orders.php" class="btn btn-info w-100">
                                            <i class="fas fa-shopping-cart me-2"></i>View Orders
                                        </a>
                                    </div>
                                    <div class="col-md-3 col-sm-6 mb-3">
                                        <a href="earnings.php" class="btn btn-warning w-100">
                                            <i class="fas fa-money-bill-wave me-2"></i>View Earnings
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Footer -->
    <?php include '../public/includes/footer.php'; ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script src="../assets/js/vendor.js"></script>

    <script>
        // Sales Chart
        const salesCtx = document.getElementById('salesChart');
        if (salesCtx) {
            new Chart(salesCtx, {
                type: 'line',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                    datasets: [{
                        label: 'Sales',
                        data: [50000, 75000, 60000, 90000, 85000, 95000],
                        borderColor: '#4e73df',
                        backgroundColor: 'rgba(78, 115, 223, 0.05)',
                        borderWidth: 2,
                        pointRadius: 3,
                        pointBackgroundColor: '#4e73df',
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return 'ETB ' + value.toLocaleString();
                                }
                            }
                        }
                    }
                }
            });
        }

        // Stock Chart
        const stockCtx = document.getElementById('stockChart');
        if (stockCtx) {
            const active = <?php echo $stats['active_products'] ?? 0; ?>;
            const low = <?php echo $stats['low_stock'] ?? 0; ?>;
            const out = <?php echo $stats['out_of_stock'] ?? 0; ?>;
            const pending = <?php echo $stats['pending_products'] ?? 0; ?>;

            new Chart(stockCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Active', 'Low Stock', 'Out of Stock', 'Pending'],
                    datasets: [{
                        data: [active, low, out, pending],
                        backgroundColor: ['#4e73df', '#f6c23e', '#e74a3b', '#858796'],
                        hoverBackgroundColor: ['#2e59d9', '#f5b300', '#d52a1e', '#6c757d']
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '70%',
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });
        }
    </script>
</body>

</html>