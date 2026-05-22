<?php
require_once '../config.php';

// Require vendor login
require_vendor();

$page_title = 'Manage Orders - ' . SITE_NAME;

// Get vendor data
$user = new User();
$vendor_data = $user->getUserById($_SESSION['user_id']);

// Initialize variables
$errors = [];
$success = false;

// Handle order status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $order_id = intval($_POST['order_id']);
    $new_status = sanitize_input($_POST['status']);

    // Validate status
    $valid_statuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
    if (!in_array($new_status, $valid_statuses)) {
        $errors[] = 'Invalid status selected.';
    } else {
        try {
            $db = new Database();

            // Check if order contains vendor's products
            $db->query("SELECT DISTINCT o.id 
                        FROM orders o 
                        JOIN order_items oi ON o.id = oi.order_id 
                        JOIN products p ON oi.product_id = p.id 
                        WHERE o.id = :order_id AND p.vendor_id = :vendor_id");
            $db->bind(':order_id', $order_id);
            $db->bind(':vendor_id', $_SESSION['user_id']);
            $order_exists = $db->single();

            if ($order_exists) {
                // Update order status
                $db->query("UPDATE orders SET status = :status, updated_at = NOW() WHERE id = :id");
                $db->bind(':status', $new_status);
                $db->bind(':id', $order_id);
                $db->execute();

                // Log status change
                $db->query("INSERT INTO order_status_history (order_id, status, changed_by, changed_at) 
                            VALUES (:order_id, :status, 'vendor', NOW())");
                $db->bind(':order_id', $order_id);
                $db->bind(':status', $new_status);
                $db->execute();

                $success = 'Order status updated successfully!';
            } else {
                $errors[] = 'Order not found or access denied.';
            }
        } catch (Exception $e) {
            $errors[] = 'Failed to update order status: ' . $e->getMessage();
        }
    }
}

// Handle filter parameters
$status_filter = $_GET['status'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$search_query = $_GET['search'] ?? '';

// Build base query for vendor's orders
$db = new Database();
$query = "SELECT DISTINCT o.*, 
          u.first_name, u.last_name, u.email,
          COUNT(oi.id) as item_count,
          SUM(oi.quantity) as total_quantity,
          SUM(oi.subtotal) as vendor_total
          FROM orders o 
          JOIN order_items oi ON o.id = oi.order_id 
          JOIN products p ON oi.product_id = p.id 
          JOIN users u ON o.buyer_id = u.id 
          WHERE p.vendor_id = :vendor_id";

$params = [':vendor_id' => $_SESSION['user_id']];

// Apply filters
if (!empty($status_filter)) {
    $query .= " AND o.status = :status";
    $params[':status'] = $status_filter;
}

if (!empty($date_from)) {
    $query .= " AND DATE(o.created_at) >= :date_from";
    $params[':date_from'] = $date_from;
}

if (!empty($date_to)) {
    $query .= " AND DATE(o.created_at) <= :date_to";
    $params[':date_to'] = $date_to;
}

if (!empty($search_query)) {
    $query .= " AND (o.order_number ILIKE :search OR 
                    u.first_name ILIKE :search OR 
                    u.last_name ILIKE :search OR 
                    u.email ILIKE :search)";
    $params[':search'] = '%' . $search_query . '%';
}

$query .= " GROUP BY o.id, u.first_name, u.last_name, u.email";

// Get total count for pagination
$count_query = "SELECT COUNT(DISTINCT o.id) as total 
                FROM orders o 
                JOIN order_items oi ON o.id = oi.order_id 
                JOIN products p ON oi.product_id = p.id 
                WHERE p.vendor_id = :vendor_id";

$count_params = [':vendor_id' => $_SESSION['user_id']];

if (!empty($status_filter)) {
    $count_query .= " AND o.status = :status";
    $count_params[':status'] = $status_filter;
}

if (!empty($date_from)) {
    $count_query .= " AND DATE(o.created_at) >= :date_from";
    $count_params[':date_from'] = $date_from;
}

if (!empty($date_to)) {
    $count_query .= " AND DATE(o.created_at) <= :date_to";
    $count_params[':date_to'] = $date_to;
}

if (!empty($search_query)) {
    $count_query .= " AND EXISTS (
        SELECT 1 FROM users u 
        WHERE u.id = o.buyer_id AND 
        (u.first_name ILIKE :search OR u.last_name ILIKE :search OR u.email ILIKE :search)
    )";
    $count_params[':search'] = '%' . $search_query . '%';
}

$db->query($count_query);
foreach ($count_params as $key => $value) {
    $db->bind($key, $value);
}

$total_count = 0;
try {
    $result = $db->single();
    $total_count = $result ? $result['total'] : 0;
} catch (Exception $e) {
    error_log("Error counting orders: " . $e->getMessage());
}

// Setup pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 15;
$total_pages = ceil($total_count / $per_page);
$offset = ($page - 1) * $per_page;

$query .= " ORDER BY o.created_at DESC LIMIT :limit OFFSET :offset";
$params[':limit'] = $per_page;
$params[':offset'] = $offset;

// Get orders
try {
    $db->query($query);
    foreach ($params as $key => $value) {
        $db->bind($key, $value);
    }
    $orders = $db->resultSet();
} catch (Exception $e) {
    $orders = [];
    error_log("Error loading orders: " . $e->getMessage());
}

// Get order details if viewing single order
$view_order = null;
$order_items = [];
if (isset($_GET['view']) && is_numeric($_GET['view'])) {
    $order_id = intval($_GET['view']);

    try {
        // Get order details
        $db->query("SELECT o.*, u.first_name, u.last_name, u.email, u.phone 
                    FROM orders o 
                    JOIN users u ON o.buyer_id = u.id 
                    WHERE o.id = :id");
        $db->bind(':id', $order_id);
        $view_order = $db->single();

        if ($view_order) {
            // Get order items for this vendor
            $db->query("SELECT oi.*, p.name, p.sku, p.images, p.vendor_id 
                        FROM order_items oi 
                        JOIN products p ON oi.product_id = p.id 
                        WHERE oi.order_id = :order_id AND p.vendor_id = :vendor_id");
            $db->bind(':order_id', $order_id);
            $db->bind(':vendor_id', $_SESSION['user_id']);
            $order_items = $db->resultSet();

            // If no items belong to this vendor, deny access
            if (empty($order_items)) {
                $view_order = null;
                $errors[] = 'Order not found or access denied.';
            }
        }
    } catch (Exception $e) {
        error_log("Error loading order details: " . $e->getMessage());
        $view_order = null;
    }
}

// Get cart count if function exists
$cart_count = function_exists('get_cart_count') ? get_cart_count() : 0;

// Get order statistics
$stats = [];
try {
    $db->query("SELECT 
                COUNT(DISTINCT o.id) as total_orders,
                SUM(CASE WHEN o.status = 'pending' THEN 1 ELSE 0 END) as pending_orders,
                SUM(CASE WHEN o.status = 'processing' THEN 1 ELSE 0 END) as processing_orders,
                SUM(CASE WHEN o.status = 'shipped' THEN 1 ELSE 0 END) as shipped_orders,
                SUM(CASE WHEN o.status = 'delivered' THEN 1 ELSE 0 END) as delivered_orders,
                COALESCE(SUM(oi.subtotal), 0) as total_sales
                FROM orders o 
                JOIN order_items oi ON o.id = oi.order_id 
                JOIN products p ON oi.product_id = p.id 
                WHERE p.vendor_id = :vendor_id");
    $db->bind(':vendor_id', $_SESSION['user_id']);
    $stats = $db->single();
} catch (Exception $e) {
    error_log("Error loading order stats: " . $e->getMessage());
    $stats = [];
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
    <style>
        .sidebar {
            min-height: calc(100vh - 56px);
            background: #1a1d20;
            padding-top: 20px;
        }

        .sidebar .nav-link {
            color: #adb5bd;
            padding: 0.75rem 1rem;
            border-radius: 0.375rem;
            margin: 0.25rem 0.5rem;
            transition: all 0.2s;
        }

        .sidebar .nav-link:hover {
            color: #fff;
            background-color: rgba(255, 255, 255, 0.1);
        }

        .sidebar .nav-link.active {
            color: #fff;
            background-color: #0d6efd;
        }

        .badge {
            font-weight: 500;
            padding: 0.35em 0.65em;
        }

        .stat-card {
            border-radius: 10px;
            border: none;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s;
        }

        .stat-card:hover {
            transform: translateY(-2px);
        }

        .order-status-badge {
            padding: 0.5em 1em;
            border-radius: 20px;
            font-weight: 600;
        }

        .status-pending {
            background-color: #ffc107;
            color: #000;
        }

        .status-processing {
            background-color: #0dcaf0;
            color: #000;
        }

        .status-shipped {
            background-color: #6f42c1;
            color: #fff;
        }

        .status-delivered {
            background-color: #198754;
            color: #fff;
        }

        .status-cancelled {
            background-color: #dc3545;
            color: #fff;
        }

        .table-hover tbody tr:hover {
            background-color: rgba(13, 110, 253, 0.05);
            cursor: pointer;
        }

        .order-details-card {
            border-left: 4px solid #0d6efd;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #86b7fe;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
        }

        .alert {
            border: none;
            border-left: 4px solid;
        }

        .alert-success {
            border-left-color: #198754;
            background-color: #d1e7dd;
        }

        .alert-danger {
            border-left-color: #dc3545;
            background-color: #f8d7da;
        }

        @media (max-width: 768px) {
            .sidebar {
                min-height: auto;
                padding: 10px;
            }

            .table-responsive {
                font-size: 0.9rem;
            }

            .btn-group-sm .btn {
                padding: 0.2rem 0.4rem;
                font-size: 0.8rem;
            }
        }

        .product-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 4px;
        }

        .modal-header {
            background: linear-gradient(135deg, #0d6efd 0%, #0b5ed7 100%);
            color: white;
        }

        .pagination .page-item.active .page-link {
            background-color: #0d6efd;
            border-color: #0d6efd;
        }
    </style>
</head>

<body>
    <!-- Vendor Navigation -->
    <?php if (file_exists('includes/navbar.php')): ?>
        <?php include 'includes/navbar.php'; ?>
    <?php else: ?>
        <!-- Fallback Navigation -->
        <nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
            <div class="container-fluid">
                <a class="navbar-brand" href="index.php">
                    <i class="fas fa-store me-2"></i>Vendor Panel
                </a>
                <div class="ms-auto">
                    <a href="index.php" class="btn btn-outline-light btn-sm me-2">Dashboard</a>
                    <a href="../public/logout.php" class="btn btn-outline-light btn-sm">Logout</a>
                </div>
            </div>
        </nav>
    <?php endif; ?>

    <!-- Dashboard Layout -->
    <div class="container-fluid mt-5 pt-3">
        <div class="row">
            <!-- Vendor Sidebar -->
            <div class="col-md-3 col-lg-2 d-md-block sidebar collapse">
                <?php if (file_exists('includes/sidebar.php')): ?>
                    <?php include 'includes/sidebar.php'; ?>
                <?php else: ?>
                    <!-- Fallback Sidebar -->
                    <div class="position-sticky pt-3">
                        <ul class="nav flex-column">
                            <li class="nav-item">
                                <a class="nav-link text-white" href="index.php">
                                    <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link text-white" href="products.php">
                                    <i class="fas fa-box me-2"></i>Products
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link text-white active" href="orders.php">
                                    <i class="fas fa-shopping-cart me-2"></i>Orders
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link text-white" href="earnings.php">
                                    <i class="fas fa-money-bill me-2"></i>Earnings
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link text-white" href="profile.php">
                                    <i class="fas fa-user me-2"></i>Profile
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link text-white" href="../public/logout.php">
                                    <i class="fas fa-sign-out-alt me-2"></i>Logout
                                </a>
                            </li>
                        </ul>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <!-- Page Header -->
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <div>
                        <h1 class="h2 mb-0">Manage Orders</h1>
                        <p class="text-muted mb-0">View and manage customer orders for your products</p>
                    </div>
                    <?php if (isset($_GET['view'])): ?>
                        <a href="orders.php" class="btn btn-outline-primary">
                            <i class="fas fa-arrow-left me-2"></i>Back to Orders
                        </a>
                    <?php endif; ?>
                </div>

                <!-- Success/Error Messages -->
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show mb-4">
                        <i class="fas fa-check-circle me-2"></i>
                        <?php echo htmlspecialchars($_SESSION['success']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        <?php unset($_SESSION['success']); ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show mb-4">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <?php echo htmlspecialchars($_SESSION['error']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        <?php unset($_SESSION['error']); ?>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show mb-4">
                        <i class="fas fa-check-circle me-2"></i>
                        <?php echo htmlspecialchars($success); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger alert-dismissible fade show mb-4">
                        <h5><i class="fas fa-exclamation-triangle me-2"></i>Please fix the following errors:</h5>
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- View Single Order -->
                <?php if ($view_order): ?>
                    <div class="row">
                        <div class="col-lg-8">
                            <!-- Order Details Card -->
                            <div class="card order-details-card mb-4">
                                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">Order Details</h5>
                                    <span class="badge order-status-badge status-<?php echo $view_order['status']; ?>">
                                        <?php echo ucfirst($view_order['status']); ?>
                                    </span>
                                </div>
                                <div class="card-body">
                                    <div class="row mb-4">
                                        <div class="col-md-6">
                                            <h6>Order Information</h6>
                                            <table class="table table-sm">
                                                <tr>
                                                    <td width="40%"><strong>Order Number:</strong></td>
                                                    <td><?php echo htmlspecialchars($view_order['order_number']); ?></td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Order Date:</strong></td>
                                                    <td><?php echo date('F j, Y, g:i a', strtotime($view_order['created_at'])); ?></td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Payment Method:</strong></td>
                                                    <td><?php echo ucfirst($view_order['payment_method'] ?? 'N/A'); ?></td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Payment Status:</strong></td>
                                                    <td>
                                                        <span class="badge bg-<?php echo $view_order['payment_status'] === 'paid' ? 'success' : 'warning'; ?>">
                                                            <?php echo ucfirst($view_order['payment_status']); ?>
                                                        </span>
                                                    </td>
                                                </tr>
                                            </table>
                                        </div>
                                        <div class="col-md-6">
                                            <h6>Customer Information</h6>
                                            <table class="table table-sm">
                                                <tr>
                                                    <td width="40%"><strong>Name:</strong></td>
                                                    <td><?php echo htmlspecialchars($view_order['first_name'] . ' ' . $view_order['last_name']); ?></td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Email:</strong></td>
                                                    <td><?php echo htmlspecialchars($view_order['email']); ?></td>
                                                </tr>
                                                <tr>
                                                    <td><strong>Phone:</strong></td>
                                                    <td><?php echo htmlspecialchars($view_order['phone'] ?? 'N/A'); ?></td>
                                                </tr>
                                            </table>
                                        </div>
                                    </div>

                                    <!-- Shipping Address -->
                                    <div class="mb-4">
                                        <h6>Shipping Address</h6>
                                        <div class="border rounded p-3">
                                            <?php
                                            $shipping_address = json_decode($view_order['shipping_address'] ?? '{}', true);
                                            if (!empty($shipping_address)): ?>
                                                <p class="mb-1"><?php echo htmlspecialchars($shipping_address['street'] ?? ''); ?></p>
                                                <p class="mb-1"><?php echo htmlspecialchars($shipping_address['city'] ?? ''); ?>, <?php echo htmlspecialchars($shipping_address['state'] ?? ''); ?> <?php echo htmlspecialchars($shipping_address['postal_code'] ?? ''); ?></p>
                                                <p class="mb-0"><?php echo htmlspecialchars($shipping_address['country'] ?? ''); ?></p>
                                            <?php else: ?>
                                                <p class="text-muted mb-0">No shipping address provided</p>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <!-- Order Items -->
                                    <div class="mb-4">
                                        <h6>Order Items</h6>
                                        <div class="table-responsive">
                                            <table class="table table-bordered">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th width="80">Image</th>
                                                        <th>Product</th>
                                                        <th class="text-center">SKU</th>
                                                        <th class="text-center">Price</th>
                                                        <th class="text-center">Quantity</th>
                                                        <th class="text-end">Subtotal</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php
                                                    $vendor_subtotal = 0;
                                                    foreach ($order_items as $item):
                                                        $vendor_subtotal += $item['subtotal'];
                                                        $images = !empty($item['images']) ? json_decode($item['images'], true) : [];
                                                        $first_image = !empty($images) && !empty($images[0]) ? '../' . $images[0] : '../assets/images/products/default.jpg';
                                                    ?>
                                                        <tr>
                                                            <td>
                                                                <img src="<?php echo htmlspecialchars($first_image); ?>"
                                                                    alt="<?php echo htmlspecialchars($item['name']); ?>"
                                                                    class="product-image">
                                                            </td>
                                                            <td>
                                                                <h6 class="mb-1"><?php echo htmlspecialchars($item['name']); ?></h6>
                                                                <p class="text-muted small mb-0">Size: <?php echo htmlspecialchars($item['size'] ?? 'N/A'); ?></p>
                                                                <p class="text-muted small mb-0">Color: <?php echo htmlspecialchars($item['color'] ?? 'N/A'); ?></p>
                                                            </td>
                                                            <td class="text-center">
                                                                <small class="text-muted"><?php echo htmlspecialchars($item['sku']); ?></small>
                                                            </td>
                                                            <td class="text-center">
                                                                <?php echo format_price($item['price']); ?>
                                                            </td>
                                                            <td class="text-center">
                                                                <?php echo $item['quantity']; ?>
                                                            </td>
                                                            <td class="text-end">
                                                                <strong><?php echo format_price($item['subtotal']); ?></strong>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                                <tfoot>
                                                    <tr>
                                                        <td colspan="5" class="text-end"><strong>Vendor Subtotal:</strong></td>
                                                        <td class="text-end"><strong><?php echo format_price($vendor_subtotal); ?></strong></td>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>
                                    </div>

                                    <!-- Update Status Form -->
                                    <div class="card">
                                        <div class="card-header bg-light">
                                            <h6 class="mb-0">Update Order Status</h6>
                                        </div>
                                        <div class="card-body">
                                            <form method="POST" class="row g-3">
                                                <input type="hidden" name="order_id" value="<?php echo $view_order['id']; ?>">
                                                <div class="col-md-6">
                                                    <label class="form-label">Current Status</label>
                                                    <input type="text" class="form-control" value="<?php echo ucfirst($view_order['status']); ?>" readonly>
                                                </div>
                                                <div class="col-md-6">
                                                    <label for="status" class="form-label">New Status</label>
                                                    <select class="form-select" id="status" name="status" required>
                                                        <option value="">Select Status</option>
                                                        <option value="pending" <?php echo $view_order['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                        <option value="processing" <?php echo $view_order['status'] === 'processing' ? 'selected' : ''; ?>>Processing</option>
                                                        <option value="shipped" <?php echo $view_order['status'] === 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                                                        <option value="delivered" <?php echo $view_order['status'] === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                                                        <option value="cancelled" <?php echo $view_order['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                                    </select>
                                                </div>
                                                <div class="col-12">
                                                    <button type="submit" name="update_status" class="btn btn-primary">
                                                        <i class="fas fa-sync-alt me-2"></i>Update Status
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Order Actions Sidebar -->
                        <div class="col-lg-4">
                            <!-- Order Summary -->
                            <div class="card mb-4">
                                <div class="card-header bg-white">
                                    <h6 class="mb-0">Order Summary</h6>
                                </div>
                                <div class="card-body">
                                    <table class="table table-sm">
                                        <tr>
                                            <td>Items:</td>
                                            <td class="text-end"><?php echo count($order_items); ?></td>
                                        </tr>
                                        <tr>
                                            <td>Total Quantity:</td>
                                            <td class="text-end">
                                                <?php echo array_sum(array_column($order_items, 'quantity')); ?>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>Vendor Total:</td>
                                            <td class="text-end">
                                                <strong><?php echo format_price($vendor_subtotal); ?></strong>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            </div>

                            <!-- Order Actions -->
                            <div class="card">
                                <div class="card-header bg-white">
                                    <h6 class="mb-0">Quick Actions</h6>
                                </div>
                                <div class="card-body">
                                    <div class="d-grid gap-2">
                                        <a href="mailto:<?php echo htmlspecialchars($view_order['email']); ?>"
                                            class="btn btn-outline-primary">
                                            <i class="fas fa-envelope me-2"></i>Contact Customer
                                        </a>
                                        <button type="button" class="btn btn-outline-secondary" onclick="window.print()">
                                            <i class="fas fa-print me-2"></i>Print Order
                                        </button>
                                        <a href="orders.php" class="btn btn-outline-danger">
                                            <i class="fas fa-times me-2"></i>Close Order View
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                <?php else: ?>
                    <!-- Orders List View -->
                    <!-- Quick Stats -->
                    <div class="row mb-4">
                        <div class="col-xl-2 col-md-4 col-6 mb-3">
                            <div class="card stat-card border-left-primary">
                                <div class="card-body">
                                    <div class="text-center">
                                        <h3 class="mb-0"><?php echo $stats['total_orders'] ?? 0; ?></h3>
                                        <small class="text-muted">Total Orders</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-2 col-md-4 col-6 mb-3">
                            <div class="card stat-card border-left-warning">
                                <div class="card-body">
                                    <div class="text-center">
                                        <h3 class="mb-0"><?php echo $stats['pending_orders'] ?? 0; ?></h3>
                                        <small class="text-muted">Pending</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-2 col-md-4 col-6 mb-3">
                            <div class="card stat-card border-left-info">
                                <div class="card-body">
                                    <div class="text-center">
                                        <h3 class="mb-0"><?php echo $stats['processing_orders'] ?? 0; ?></h3>
                                        <small class="text-muted">Processing</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-2 col-md-4 col-6 mb-3">
                            <div class="card stat-card border-left-success">
                                <div class="card-body">
                                    <div class="text-center">
                                        <h3 class="mb-0"><?php echo $stats['delivered_orders'] ?? 0; ?></h3>
                                        <small class="text-muted">Delivered</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-4 col-md-8 col-12 mb-3">
                            <div class="card stat-card border-left-danger">
                                <div class="card-body">
                                    <div class="text-center">
                                        <h3 class="mb-0"><?php echo format_price($stats['total_sales'] ?? 0); ?></h3>
                                        <small class="text-muted">Total Sales from Orders</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Filter Section -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white">
                            <h6 class="mb-0"><i class="fas fa-filter me-2"></i>Filter Orders</h6>
                        </div>
                        <div class="card-body">
                            <form method="GET" class="row g-3">
                                <div class="col-md-3">
                                    <label class="form-label">Status</label>
                                    <select name="status" class="form-select">
                                        <option value="">All Status</option>
                                        <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                        <option value="processing" <?php echo $status_filter === 'processing' ? 'selected' : ''; ?>>Processing</option>
                                        <option value="shipped" <?php echo $status_filter === 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                                        <option value="delivered" <?php echo $status_filter === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                                        <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Date From</label>
                                    <input type="date" name="date_from" class="form-control"
                                        value="<?php echo htmlspecialchars($date_from); ?>">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Date To</label>
                                    <input type="date" name="date_to" class="form-control"
                                        value="<?php echo htmlspecialchars($date_to); ?>">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Search</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                                        <input type="text" name="search" class="form-control"
                                            placeholder="Order # or Customer"
                                            value="<?php echo htmlspecialchars($search_query); ?>">
                                        <button class="btn btn-primary" type="submit">Search</button>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="d-flex justify-content-between">
                                        <a href="orders.php" class="btn btn-outline-secondary">
                                            <i class="fas fa-times me-1"></i>Clear Filters
                                        </a>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-filter me-1"></i>Apply Filters
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Orders Table -->
                    <div class="card shadow-sm">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center">
                            <h6 class="mb-0"><i class="fas fa-list me-2"></i>Recent Orders</h6>
                            <span class="badge bg-primary"><?php echo $total_count; ?> orders</span>
                        </div>
                        <div class="card-body">
                            <?php if (empty($orders)): ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-shopping-cart fa-4x text-muted mb-4"></i>
                                    <h3>No orders found</h3>
                                    <p class="text-muted">Orders will appear here when customers purchase your products.</p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Order #</th>
                                                <th>Customer</th>
                                                <th>Date</th>
                                                <th>Items</th>
                                                <th>Total</th>
                                                <th>Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($orders as $order): ?>
                                                <tr>
                                                    <td>
                                                        <strong><?php echo htmlspecialchars($order['order_number']); ?></strong>
                                                    </td>
                                                    <td>
                                                        <div><?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?></div>
                                                        <small class="text-muted"><?php echo htmlspecialchars($order['email']); ?></small>
                                                    </td>
                                                    <td>
                                                        <?php echo date('M j, Y', strtotime($order['created_at'])); ?>
                                                        <br>
                                                        <small class="text-muted"><?php echo date('g:i a', strtotime($order['created_at'])); ?></small>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-dark"><?php echo $order['item_count']; ?> items</span>
                                                        <br>
                                                        <small class="text-muted"><?php echo $order['total_quantity']; ?> units</small>
                                                    </td>
                                                    <td>
                                                        <strong><?php echo format_price($order['vendor_total']); ?></strong>
                                                    </td>
                                                    <td>
                                                        <span class="badge order-status-badge status-<?php echo $order['status']; ?>">
                                                            <?php echo ucfirst($order['status']); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <div class="btn-group btn-group-sm">
                                                            <a href="orders.php?view=<?php echo $order['id']; ?>"
                                                                class="btn btn-outline-primary" title="View Details">
                                                                <i class="fas fa-eye"></i>
                                                            </a>
                                                            <button type="button" class="btn btn-outline-info"
                                                                data-bs-toggle="modal"
                                                                data-bs-target="#updateStatusModal<?php echo $order['id']; ?>"
                                                                title="Update Status">
                                                                <i class="fas fa-sync-alt"></i>
                                                            </button>
                                                        </div>

                                                        <!-- Update Status Modal -->
                                                        <div class="modal fade" id="updateStatusModal<?php echo $order['id']; ?>" tabindex="-1">
                                                            <div class="modal-dialog">
                                                                <div class="modal-content">
                                                                    <div class="modal-header">
                                                                        <h5 class="modal-title">Update Order Status</h5>
                                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                                    </div>
                                                                    <form method="POST">
                                                                        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                                                        <div class="modal-body">
                                                                            <div class="mb-3">
                                                                                <label class="form-label">Current Status</label>
                                                                                <input type="text" class="form-control"
                                                                                    value="<?php echo ucfirst($order['status']); ?>" readonly>
                                                                            </div>
                                                                            <div class="mb-3">
                                                                                <label for="status<?php echo $order['id']; ?>" class="form-label">New Status</label>
                                                                                <select class="form-select" id="status<?php echo $order['id']; ?>" name="status" required>
                                                                                    <option value="">Select Status</option>
                                                                                    <option value="pending" <?php echo $order['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                                                    <option value="processing" <?php echo $order['status'] === 'processing' ? 'selected' : ''; ?>>Processing</option>
                                                                                    <option value="shipped" <?php echo $order['status'] === 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                                                                                    <option value="delivered" <?php echo $order['status'] === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                                                                                    <option value="cancelled" <?php echo $order['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                                                                </select>
                                                                            </div>
                                                                        </div>
                                                                        <div class="modal-footer">
                                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                            <button type="submit" name="update_status" class="btn btn-primary">
                                                                                Update Status
                                                                            </button>
                                                                        </div>
                                                                    </form>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>

                                <!-- Pagination -->
                                <?php if ($total_pages > 1): ?>
                                    <nav aria-label="Page navigation" class="mt-4">
                                        <ul class="pagination justify-content-center">
                                            <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">
                                                    <i class="fas fa-chevron-left me-1"></i>Previous
                                                </a>
                                            </li>

                                            <?php
                                            // Show limited pagination
                                            $start_page = max(1, $page - 2);
                                            $end_page = min($total_pages, $page + 2);

                                            for ($i = $start_page; $i <= $end_page; $i++): ?>
                                                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                                    <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>">
                                                        <?php echo $i; ?>
                                                    </a>
                                                </li>
                                            <?php endfor; ?>

                                            <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">
                                                    Next<i class="fas fa-chevron-right ms-1"></i>
                                                </a>
                                            </li>
                                        </ul>
                                    </nav>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <!-- Vendor Footer -->
    <?php if (file_exists('includes/footer.php')): ?>
        <?php include 'includes/footer.php'; ?>
    <?php else: ?>
        <!-- Fallback Footer -->
        <footer class="bg-dark text-white py-4 mt-5">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-md-6">
                        <p class="mb-0">&copy; <?php echo date('Y'); ?> Snaker-Mart. All rights reserved.</p>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <a href="../public/contact.php" class="text-white text-decoration-none me-3">Contact</a>
                        <a href="../public/terms.php" class="text-white text-decoration-none me-3">Terms</a>
                        <a href="../public/privacy.php" class="text-white text-decoration-none">Privacy</a>
                    </div>
                </div>
            </div>
        </footer>
    <?php endif; ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Initialize tooltips
        document.addEventListener('DOMContentLoaded', function() {
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[title]'));
            tooltipTriggerList.map(function(tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });

            // Auto-submit filter form on status change (optional)
            const statusFilter = document.querySelector('select[name="status"]');
            if (statusFilter) {
                statusFilter.addEventListener('change', function() {
                    this.form.submit();
                });
            }

            // Date validation
            const dateFrom = document.querySelector('input[name="date_from"]');
            const dateTo = document.querySelector('input[name="date_to"]');

            if (dateFrom && dateTo) {
                dateFrom.addEventListener('change', function() {
                    if (dateTo.value && this.value > dateTo.value) {
                        alert('Date From cannot be after Date To');
                        this.value = '';
                    }
                });

                dateTo.addEventListener('change', function() {
                    if (dateFrom.value && this.value < dateFrom.value) {
                        alert('Date To cannot be before Date From');
                        this.value = '';
                    }
                });
            }
        });

        // Print order function
        function printOrder() {
            window.print();
        }
    </script>
</body>

</html>