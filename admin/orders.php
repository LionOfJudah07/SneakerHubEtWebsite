<?php
require_once '../config.php';

// Require admin login
require_admin();

$page_title = 'Orders Management - ' . SITE_NAME;

// Handle order actions
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_status'])) {
        // Update order status
        $order_id = intval($_POST['order_id']);
        $status = sanitize_input($_POST['status']);

        try {
            $db = new Database();
            $db->query("UPDATE orders SET status = :status, updated_at = NOW() WHERE id = :id");
            $db->bind(':status', $status);
            $db->bind(':id', $order_id);
            $db->execute();

            $success = 'Order status updated successfully!';

            // Send notification to buyer
            $db->query("SELECT buyer_id FROM orders WHERE id = :id");
            $db->bind(':id', $order_id);
            $order = $db->single();

            if ($order) {
                // Create notification
                $notification = [
                    'user_id' => $order['buyer_id'],
                    'title' => 'Order Status Updated',
                    'message' => "Your order status has been updated to: " . ucfirst($status),
                    'type' => 'order_update',
                    'reference_id' => $order_id,
                    'created_at' => date('Y-m-d H:i:s')
                ];
                $db->insert('notifications', $notification);
            }
        } catch (Exception $e) {
            $errors[] = 'Failed to update order status: ' . $e->getMessage();
        }
    }
}

// Get filter parameters
$status_filter = $_GET['status'] ?? '';
$payment_filter = $_GET['payment'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$search_query = $_GET['search'] ?? '';

// Build query
$query = "SELECT o.*, u.first_name, u.last_name, u.email 
          FROM orders o 
          JOIN users u ON o.buyer_id = u.id 
          WHERE 1=1";

$params = [];

if (!empty($status_filter)) {
    $query .= " AND o.status = :status";
    $params[':status'] = $status_filter;
}

if (!empty($payment_filter)) {
    $query .= " AND o.payment_status = :payment";
    $params[':payment'] = $payment_filter;
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
    $query .= " AND (o.order_number ILIKE :search OR u.first_name ILIKE :search OR u.last_name ILIKE :search OR u.email ILIKE :search)";
    $params[':search'] = '%' . $search_query . '%';
}

$query .= " ORDER BY o.created_at DESC";

// Get total count for pagination
$db = new Database();
$db->query(str_replace("SELECT o.*, u.first_name, u.last_name, u.email", "SELECT COUNT(*) as total", $query));

foreach ($params as $key => $value) {
    $db->bind($key, $value);
}

$total_count = $db->single()['total'];

// Setup pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 20;
$total_pages = ceil($total_count / $per_page);
$offset = ($page - 1) * $per_page;

$query .= " LIMIT :limit OFFSET :offset";
$params[':limit'] = $per_page;
$params[':offset'] = $offset;

// Get orders
$db->query($query);
foreach ($params as $key => $value) {
    $db->bind($key, $value);
}

$orders = $db->resultSet();

// Get order statistics
$db->query("SELECT 
    COUNT(*) as total_orders,
    COALESCE(SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END), 0) as pending_orders,
    COALESCE(SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END), 0) as processing_orders,
    COALESCE(SUM(CASE WHEN status = 'shipped' THEN 1 ELSE 0 END), 0) as shipped_orders,
    COALESCE(SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END), 0) as delivered_orders,
    COALESCE(SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END), 0) as cancelled_orders,
    COALESCE(SUM(CASE WHEN payment_status = 'paid' THEN total_amount ELSE 0 END), 0) as total_revenue
    FROM orders");
$order_stats = $db->single();

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
    <!-- Datepicker -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <!-- Custom CSS -->
    <link href="../assets/css/style.css" rel="stylesheet">
    <link href="../assets/css/bootstrap-custom.css" rel="stylesheet">
    <link href="../assets/css/admin.css" rel="stylesheet">
</head>

<body>
    <!-- Navigation -->
    <?php include '../public/includes/navbar.php'; ?>

    <!-- Dashboard Layout -->
    <div class="container-fluid">
        <div class="row">
            <!-- Admin Sidebar -->
            <div class="col-md-3 col-lg-2 d-md-block bg-dark sidebar collapse">
                <?php include 'includes/sidebar.php'; ?>
            </div>

            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <!-- Page Header -->
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Orders Management</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <button type="button" class="btn btn-sm btn-outline-secondary me-2" onclick="window.print()">
                            <i class="fas fa-print me-2"></i>Print
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="exportOrders()">
                            <i class="fas fa-download me-2"></i>Export
                        </button>
                    </div>
                </div>

                <!-- Statistics -->
                <div class="row mb-4">
                    <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
                        <div class="card border-left-primary shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                            Total Orders
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo number_format($order_stats['total_orders']); ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-shopping-cart fa-2x text-primary"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
                        <div class="card border-left-warning shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                            Pending
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo number_format($order_stats['pending_orders']); ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-clock fa-2x text-warning"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
                        <div class="card border-left-info shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                            Processing
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo number_format($order_stats['processing_orders']); ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-cog fa-2x text-info"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
                        <div class="card border-left-success shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                            Delivered
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo number_format($order_stats['delivered_orders']); ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-check-circle fa-2x text-success"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
                        <div class="card border-left-danger shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                            Cancelled
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo number_format($order_stats['cancelled_orders']); ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-times-circle fa-2x text-danger"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-2 col-md-4 col-sm-6 mb-3">
                        <div class="card border-left-primary shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                            Revenue
                                        </div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo format_price($order_stats['total_revenue']); ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-dollar-sign fa-2x text-primary"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Success/Error Messages -->
                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show mb-4">
                        <i class="fas fa-check-circle me-2"></i>
                        <?php echo $success; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger alert-dismissible fade show mb-4">
                        <h5><i class="fas fa-exclamation-triangle me-2"></i>Please fix the following errors:</h5>
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo $error; ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Filter Section -->
                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Order Status</label>
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
                                <label class="form-label">Payment Status</label>
                                <select name="payment" class="form-select">
                                    <option value="">All Payments</option>
                                    <option value="pending" <?php echo $payment_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="paid" <?php echo $payment_filter === 'paid' ? 'selected' : ''; ?>>Paid</option>
                                    <option value="failed" <?php echo $payment_filter === 'failed' ? 'selected' : ''; ?>>Failed</option>
                                    <option value="refunded" <?php echo $payment_filter === 'refunded' ? 'selected' : ''; ?>>Refunded</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Date From</label>
                                <input type="text" name="date_from" class="form-control datepicker"
                                    value="<?php echo htmlspecialchars($date_from); ?>"
                                    placeholder="YYYY-MM-DD">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Date To</label>
                                <input type="text" name="date_to" class="form-control datepicker"
                                    value="<?php echo htmlspecialchars($date_to); ?>"
                                    placeholder="YYYY-MM-DD">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Search</label>
                                <div class="input-group">
                                    <input type="text" name="search" class="form-control"
                                        placeholder="Search by order #, customer name, or email"
                                        value="<?php echo htmlspecialchars($search_query); ?>">
                                    <button class="btn btn-outline-primary" type="submit">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-filter me-2"></i>Apply Filters
                                </button>
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <a href="orders.php" class="btn btn-outline-secondary w-100">
                                    <i class="fas fa-times me-2"></i>Clear Filters
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Orders Table -->
                <div class="card shadow-sm">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Order #</th>
                                        <th>Customer</th>
                                        <th>Date</th>
                                        <th>Items</th>
                                        <th>Amount</th>
                                        <th>Payment</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($orders)): ?>
                                        <tr>
                                            <td colspan="8" class="text-center py-4">
                                                <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
                                                <h4>No orders found</h4>
                                                <p class="text-muted">Try adjusting your filters.</p>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($orders as $order): ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo $order['order_number']; ?></strong>
                                                    <br>
                                                    <small class="text-muted">
                                                        <?php echo format_date($order['created_at'], 'M d, Y H:i'); ?>
                                                    </small>
                                                </td>
                                                <td>
                                                    <h6 class="mb-1"><?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?></h6>
                                                    <p class="text-muted small mb-0"><?php echo htmlspecialchars($order['email']); ?></p>
                                                    <p class="text-muted small mb-0"><?php echo htmlspecialchars($order['phone'] ?? 'N/A'); ?></p>
                                                </td>
                                                <td>
                                                    <?php echo format_date($order['created_at'], 'M d, Y'); ?>
                                                </td>
                                                <td>
                                                    <?php
                                                    $db->query("SELECT COUNT(*) as count FROM order_items WHERE order_id = :order_id");
                                                    $db->bind(':order_id', $order['id']);
                                                    $item_count = $db->single()['count'];
                                                    ?>
                                                    <span class="badge bg-primary"><?php echo $item_count; ?> items</span>
                                                </td>
                                                <td>
                                                    <strong><?php echo format_price($order['total_amount']); ?></strong>
                                                    <br>
                                                    <small class="text-muted">
                                                        <?php
                                                        $payment_methods = [
                                                            'telebirr' => 'TeleBirr',
                                                            'cbe_birr' => 'CBE Birr',
                                                            'cash_on_delivery' => 'Cash',
                                                            'bank_transfer' => 'Transfer'
                                                        ];
                                                        echo $payment_methods[$order['payment_method']] ?? $order['payment_method'];
                                                        ?>
                                                    </small>
                                                </td>
                                                <td>
                                                    <?php echo get_payment_status_badge($order['payment_status']); ?>
                                                </td>
                                                <td>
                                                    <?php echo get_order_status_badge($order['status']); ?>
                                                </td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <a href="order-detail.php?id=<?php echo $order['id']; ?>"
                                                            class="btn btn-outline-primary" title="View">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        <button type="button" class="btn btn-outline-warning"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#statusModal"
                                                            data-order-id="<?php echo $order['id']; ?>"
                                                            data-current-status="<?php echo $order['status']; ?>"
                                                            title="Update Status">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <a href="../public/includes/invoice.php?order_id=<?php echo $order['id']; ?>"
                                                            class="btn btn-outline-info" title="Invoice" target="_blank">
                                                            <i class="fas fa-file-invoice"></i>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                            <nav aria-label="Page navigation" class="mt-4">
                                <ul class="pagination justify-content-center">
                                    <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">
                                            Previous
                                        </a>
                                    </li>

                                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                        <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>

                                    <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">
                                            Next
                                        </a>
                                    </li>
                                </ul>
                            </nav>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Status Change Modal -->
    <div class="modal fade" id="statusModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header bg-warning text-white">
                        <h5 class="modal-title">Update Order Status</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="status_order_id" name="order_id">

                        <div class="mb-3">
                            <label for="status" class="form-label">Select New Status</label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="pending">Pending</option>
                                <option value="processing">Processing</option>
                                <option value="shipped">Shipped</option>
                                <option value="delivered">Delivered</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>

                        <div class="alert alert-info">
                            <p class="mb-0"><strong>Status Flow:</strong></p>
                            <ul class="mb-0 small">
                                <li><strong>Pending:</strong> Order placed, awaiting confirmation</li>
                                <li><strong>Processing:</strong> Order confirmed, preparing for shipment</li>
                                <li><strong>Shipped:</strong> Order shipped to customer</li>
                                <li><strong>Delivered:</strong> Order delivered to customer</li>
                                <li><strong>Cancelled:</strong> Order cancelled (refund if paid)</li>
                            </ul>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_status" class="btn btn-warning">Update Status</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <?php include '../public/includes/footer.php'; ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Datepicker -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

    <script>
        // Initialize datepicker
        flatpickr('.datepicker', {
            dateFormat: 'Y-m-d',
            allowInput: true
        });

        // Status modal handling
        const statusModal = document.getElementById('statusModal');
        if (statusModal) {
            statusModal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const orderId = button.getAttribute('data-order-id');
                const currentStatus = button.getAttribute('data-current-status');

                document.getElementById('status_order_id').value = orderId;
                document.getElementById('status').value = currentStatus;
            });
        }

        // Export orders function
        function exportOrders() {
            const params = new URLSearchParams(window.location.search);
            window.location.href = 'export-orders.php?' + params.toString();
        }
    </script>
</body>

</html>