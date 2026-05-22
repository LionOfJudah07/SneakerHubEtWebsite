<?php
// admin/index.php - FIXED VERSION

// Start session FIRST - before any output
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Debug: Add this line to see what's in session (remove after fixing)
echo "<!-- SESSION DEBUG: ";
print_r($_SESSION);
echo " -->";

// Check admin access IMMEDIATELY
if (!isset($_SESSION['user_id'])) {
    // Not logged in
    header('Location: ../public/login.php');
    exit();
}

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    // Not admin
    header('Location: ../public/index.php');
    exit();
}

// Now include other files
require_once '../config.php';

$page_title = 'Admin Dashboard - ' . SITE_NAME;

// Initialize Database
$db = new Database();

// Get dashboard statistics
$stats = [];

try {
    // Total users
    $db->query("SELECT COUNT(*) as count FROM users WHERE status = 'active'");
    $stats['total_users'] = $db->single()['count'];

    // Total vendors
    $db->query("SELECT COUNT(*) as count FROM users WHERE user_type = 'vendor' AND status = 'active'");
    $stats['total_vendors'] = $db->single()['count'];

    // Total products
    $db->query("SELECT COUNT(*) as count FROM products WHERE status = 'active'");
    $stats['total_products'] = $db->single()['count'];

    // Total orders
    $db->query("SELECT COUNT(*) as count FROM orders");
    $stats['total_orders'] = $db->single()['count'];

    // Total revenue
    $db->query("SELECT COALESCE(SUM(total_amount), 0) as total FROM orders WHERE status = 'delivered' OR status = 'completed'");
    $stats['total_revenue'] = $db->single()['total'];

    // Pending orders
    $db->query("SELECT COUNT(*) as count FROM orders WHERE status = 'pending'");
    $stats['pending_orders'] = $db->single()['count'];

    // Pending vendors
    $db->query("SELECT COUNT(*) as count FROM users WHERE user_type = 'vendor' AND status = 'pending'");
    $stats['pending_vendors'] = $db->single()['count'];

    // Low stock products (less than or equal to 10)
    $db->query("SELECT COUNT(*) as count FROM products WHERE stock_quantity <= 10 AND stock_quantity > 0 AND status = 'active'");
    $stats['low_stock'] = $db->single()['count'];

    // Out of stock products
    $db->query("SELECT COUNT(*) as count FROM products WHERE stock_quantity = 0 AND status = 'active'");
    $stats['out_of_stock'] = $db->single()['count'];

    // Recent orders (limit 10)
    $db->query("SELECT o.*, u.first_name, u.last_name FROM orders o 
                JOIN users u ON o.buyer_id = u.id 
                ORDER BY o.created_at DESC LIMIT 10");
    $recent_orders = $db->resultSet();

    // Recent users (limit 10)
    $db->query("SELECT * FROM users ORDER BY created_at DESC LIMIT 10");
    $recent_users = $db->resultSet();

    // Recent products (limit 10)
    $db->query("SELECT p.*, u.first_name as vendor_name FROM products p 
                LEFT JOIN users u ON p.vendor_id = u.id 
                ORDER BY p.created_at DESC LIMIT 10");
    $recent_products = $db->resultSet();

    // Get monthly revenue for chart
    try {
        $db->query("SELECT 
                        EXTRACT(YEAR FROM created_at) as year,
                        EXTRACT(MONTH FROM created_at) as month,
                        SUM(total_amount) as revenue 
                    FROM orders 
                    WHERE (status = 'delivered' OR status = 'completed')
                    AND created_at >= NOW() - INTERVAL '12 months'
                    GROUP BY EXTRACT(YEAR FROM created_at), EXTRACT(MONTH FROM created_at)
                    ORDER BY year, month");
        $monthly_revenue = $db->resultSet();
    } catch (Exception $e) {
        error_log("Monthly revenue error: " . $e->getMessage());
        $monthly_revenue = [];
    }
} catch (Exception $e) {
    error_log("Dashboard error: " . $e->getMessage());
    // Handle error gracefully - set default values
    $stats = [
        'total_users' => 0,
        'total_vendors' => 0,
        'total_products' => 0,
        'total_orders' => 0,
        'total_revenue' => 0,
        'pending_orders' => 0,
        'pending_vendors' => 0,
        'low_stock' => 0,
        'out_of_stock' => 0
    ];
    $recent_orders = [];
    $recent_users = [];
    $recent_products = [];
    $monthly_revenue = [];
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
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            padding-top: 0;
            background-color: #f8f9fa;
        }

        .sidebar {
            min-height: 100vh;
            background-color: #343a40;
            padding-top: 20px;
        }

        .sidebar .nav-link {
            color: #adb5bd;
            padding: 10px 20px;
            margin-bottom: 5px;
            border-radius: 5px;
            transition: all 0.3s;
        }

        .sidebar .nav-link:hover {
            color: #fff;
            background-color: #495057;
        }

        .sidebar .nav-link.active {
            color: #fff;
            background-color: #007bff;
        }

        .sidebar .dropdown-toggle {
            color: #fff;
        }

        .main-content {
            padding-top: 20px;
        }

        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
        }

        .card-header {
            background-color: #fff;
            border-bottom: 1px solid #eee;
            font-weight: 600;
        }

        .stat-card {
            border-left: 4px solid #007bff;
        }

        .stat-card .card-body {
            padding: 15px;
        }

        .stat-card i {
            font-size: 2rem;
            opacity: 0.8;
        }

        .table th {
            font-weight: 600;
            border-top: none;
        }

        .badge {
            font-weight: 500;
            padding: 5px 10px;
        }

        .navbar {
            display: none;
        }

        @media (max-width: 768px) {
            .sidebar {
                min-height: auto;
            }
        }
    </style>
</head>

<body>
    <!-- Admin Dashboard Layout -->
    <div class="container-fluid">
        <div class="row">
            <!-- Admin Sidebar -->
            <div class="col-md-3 col-lg-2 px-0 bg-dark sidebar d-print-none">
                <div class="position-sticky pt-3">
                    <div class="text-center mb-4">
                        <h4 class="text-white">
                            <i class="fas fa-shoe-prints"></i> Admin Panel
                        </h4>
                    </div>

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
                            <a class="nav-link text-white" href="users.php">
                                <i class="fas fa-users me-2"></i>
                                Users
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="orders.php">
                                <i class="fas fa-shopping-cart me-2"></i>
                                Orders
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="categories.php">
                                <i class="fas fa-tags me-2"></i>
                                Categories
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="settings.php">
                                <i class="fas fa-cog me-2"></i>
                                Settings
                            </a>
                        </li>
                    </ul>

                    <hr class="bg-light my-4">

                    <div class="dropdown">
                        <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle px-3"
                            data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle fa-2x me-2"></i>
                            <div>
                                <strong><?php echo htmlspecialchars($_SESSION['first_name'] ?? 'Admin'); ?></strong>
                                <div class="small text-muted">Administrator</div>
                            </div>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-dark text-small shadow">
                            <li><a class="dropdown-item" href="../public/index.php">
                                    <i class="fas fa-home me-2"></i>View Site
                                </a></li>
                            <li><a class="dropdown-item" href="../buyer/profile.php">
                                    <i class="fas fa-user me-2"></i>My Profile
                                </a></li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li><a class="dropdown-item text-danger" href="../public/logout.php">
                                    <i class="fas fa-sign-out-alt me-2"></i>Logout
                                </a></li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
                <!-- Page Header -->
                <div class="d-flex justify-content-between align-items-center pt-3 pb-3 mb-4 border-bottom">
                    <h1 class="h2 mb-0">Dashboard</h1>
                    <div class="btn-toolbar">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-calendar me-1"></i>
                                <?php echo date('F Y'); ?>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Debug Info (Remove this div after testing) -->
                <div class="alert alert-info mb-3">
                    <strong>Debug Info:</strong> You are logged in as
                    <strong><?php echo $_SESSION['first_name'] ?? 'Admin'; ?></strong>
                    (User Type: <strong><?php echo $_SESSION['user_type'] ?? 'Not set'; ?></strong>)
                </div>

                <!-- Stats Cards -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stat-card border-left-primary">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-8">
                                        <div class="text-muted small">Total Revenue</div>
                                        <div class="h4 mb-0 font-weight-bold">
                                            ETB <?php echo number_format($stats['total_revenue'], 2); ?>
                                        </div>
                                    </div>
                                    <div class="col-4 text-end">
                                        <i class="fas fa-dollar-sign text-primary"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stat-card border-left-success">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-8">
                                        <div class="text-muted small">Total Orders</div>
                                        <div class="h4 mb-0 font-weight-bold">
                                            <?php echo number_format($stats['total_orders']); ?>
                                        </div>
                                    </div>
                                    <div class="col-4 text-end">
                                        <i class="fas fa-shopping-cart text-success"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stat-card border-left-info">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-8">
                                        <div class="text-muted small">Total Users</div>
                                        <div class="h4 mb-0 font-weight-bold">
                                            <?php echo number_format($stats['total_users']); ?>
                                        </div>
                                    </div>
                                    <div class="col-4 text-end">
                                        <i class="fas fa-users text-info"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stat-card border-left-warning">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-8">
                                        <div class="text-muted small">Pending Orders</div>
                                        <div class="h4 mb-0 font-weight-bold">
                                            <?php echo number_format($stats['pending_orders']); ?>
                                        </div>
                                    </div>
                                    <div class="col-4 text-end">
                                        <i class="fas fa-clock text-warning"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Orders & Users -->
                <div class="row">
                    <!-- Recent Orders -->
                    <div class="col-lg-6 mb-4">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h6 class="mb-0">Recent Orders</h6>
                                <a href="orders.php" class="btn btn-sm btn-primary">View All</a>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Order #</th>
                                                <th>Customer</th>
                                                <th>Amount</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (!empty($recent_orders)): ?>
                                                <?php foreach ($recent_orders as $order): ?>
                                                    <tr>
                                                        <td>
                                                            <a href="order-detail.php?id=<?php echo $order['id']; ?>"
                                                                class="text-decoration-none">
                                                                #<?php echo htmlspecialchars($order['order_number'] ?? $order['id']); ?>
                                                            </a>
                                                        </td>
                                                        <td><?php echo htmlspecialchars(($order['first_name'] ?? '') . ' ' . ($order['last_name'] ?? '')); ?></td>
                                                        <td>ETB <?php echo number_format($order['total_amount'] ?? 0, 2); ?></td>
                                                        <td>
                                                            <?php
                                                            $status = $order['status'] ?? 'pending';
                                                            $badge_class = [
                                                                'pending' => 'badge bg-warning',
                                                                'processing' => 'badge bg-info',
                                                                'shipped' => 'badge bg-primary',
                                                                'delivered' => 'badge bg-success',
                                                                'cancelled' => 'badge bg-danger'
                                                            ][$status] ?? 'badge bg-secondary';
                                                            ?>
                                                            <span class="<?php echo $badge_class; ?>">
                                                                <?php echo ucfirst($status); ?>
                                                            </span>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="4" class="text-center py-3 text-muted">No recent orders</td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Users -->
                    <div class="col-lg-6 mb-4">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h6 class="mb-0">Recent Users</h6>
                                <a href="users.php" class="btn btn-sm btn-primary">View All</a>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Name</th>
                                                <th>Email</th>
                                                <th>Type</th>
                                                <th>Joined</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (!empty($recent_users)): ?>
                                                <?php foreach ($recent_users as $user): ?>
                                                    <tr>
                                                        <td>
                                                            <?php echo htmlspecialchars(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')); ?>
                                                        </td>
                                                        <td><?php echo htmlspecialchars($user['email'] ?? ''); ?></td>
                                                        <td>
                                                            <?php
                                                            $type = $user['user_type'] ?? 'buyer';
                                                            $badge_class = [
                                                                'buyer' => 'badge bg-primary',
                                                                'vendor' => 'badge bg-success',
                                                                'admin' => 'badge bg-danger'
                                                            ][$type] ?? 'badge bg-secondary';
                                                            ?>
                                                            <span class="<?php echo $badge_class; ?>">
                                                                <?php echo ucfirst($type); ?>
                                                            </span>
                                                        </td>
                                                        <td><?php echo date('M d', strtotime($user['created_at'] ?? date('Y-m-d'))); ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="4" class="text-center py-3 text-muted">No recent users</td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>