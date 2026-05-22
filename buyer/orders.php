<?php
require_once '../config.php';

// Require buyer login
require_buyer();

$page_title = 'My Orders - ' . SITE_NAME;

// Get user data
$user = new User();
$user_data = $user->getUserById($_SESSION['user_id']);

// Get filter parameters
$status = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 10;

// Prepare filters
$filters = ['buyer_id' => $_SESSION['user_id']];
if (!empty($status)) $filters['status'] = $status;
if (!empty($search)) $filters['search'] = $search;

// Get orders
$order = new Order();
$total_orders = $order->countOrders($filters);
$orders = $order->getAllOrders($filters, $per_page, ($page - 1) * $per_page);

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
    <?php include '../public/includes/navbar.php'; ?>

    <!-- Dashboard Layout -->
    <div class="container-fluid py-4">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-lg-3">
                <div class="card shadow-sm mb-4">
                    <div class="card-body text-center">
                        <div class="mb-3">
                            <img src="<?php echo get_user_avatar($_SESSION['user_id'], $user_data['profile_image']); ?>" 
                                 alt="Profile" class="rounded-circle" style="width: 80px; height: 80px; object-fit: cover;">
                        </div>
                        <h6><?php echo htmlspecialchars($user_data['first_name'] . ' ' . $user_data['last_name']); ?></h6>
                        <p class="text-muted small mb-0">Buyer Account</p>
                    </div>
                </div>
                
                <!-- Dashboard Menu -->
                <?php include 'includes/sidebar.php'; ?>
            </div>
            
            <!-- Main Content -->
            <div class="col-lg-9">
                <!-- Page Header -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2 class="mb-1">My Orders</h2>
                        <p class="text-muted mb-0">View and manage your orders</p>
                    </div>
                    <a href="../public/shop.php" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Continue Shopping
                    </a>
                </div>
                
                <!-- Filters -->
                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select">
                                    <option value="">All Statuses</option>
                                    <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="processing" <?php echo $status === 'processing' ? 'selected' : ''; ?>>Processing</option>
                                    <option value="shipped" <?php echo $status === 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                                    <option value="delivered" <?php echo $status === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                                    <option value="cancelled" <?php echo $status === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Search Orders</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" name="search" 
                                           placeholder="Search by order number..." value="<?php echo htmlspecialchars($search); ?>">
                                    <button class="btn btn-primary" type="submit">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <a href="orders.php" class="btn btn-outline-secondary w-100">Reset</a>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Orders Table -->
                <div class="card shadow-sm">
                    <div class="card-header bg-light d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Order History</h5>
                        <span class="badge bg-primary"><?php echo $total_orders; ?> orders</span>
                    </div>
                    
                    <div class="card-body">
                        <?php if (empty($orders)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-shopping-bag fa-4x text-muted mb-3"></i>
                            <h4>No Orders Found</h4>
                            <p class="text-muted mb-4">You haven't placed any orders yet.</p>
                            <a href="../public/shop.php" class="btn btn-primary btn-lg">
                                <i class="fas fa-shopping-bag me-2"></i>Start Shopping
                            </a>
                        </div>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Order #</th>
                                        <th>Date</th>
                                        <th>Items</th>
                                        <th>Total</th>
                                        <th>Status</th>
                                        <th>Payment</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($orders as $order_item): 
                                        $order_items = $order->getOrderItems($order_item['id']);
                                    ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo $order_item['order_number']; ?></strong>
                                        </td>
                                        <td><?php echo format_date($order_item['created_at'], 'M d, Y'); ?></td>
                                        <td><?php echo count($order_items); ?> item(s)</td>
                                        <td><?php echo format_price($order_item['total_amount']); ?></td>
                                        <td><?php echo get_order_status_badge($order_item['status']); ?></td>
                                        <td><?php echo get_payment_status_badge($order_item['payment_status']); ?></td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="order-detail.php?id=<?php echo $order_item['id']; ?>" 
                                                   class="btn btn-outline-primary" title="View Details">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <?php if ($order_item['status'] === 'pending'): ?>
                                                <a href="order-cancel.php?id=<?php echo $order_item['id']; ?>" 
                                                   class="btn btn-outline-danger" 
                                                   onclick="return confirm('Cancel this order?')" title="Cancel Order">
                                                    <i class="fas fa-times"></i>
                                                </a>
                                                <?php endif; ?>
                                                <?php if ($order_item['status'] === 'delivered'): ?>
                                                <a href="order-review.php?id=<?php echo $order_item['id']; ?>" 
                                                   class="btn btn-outline-success" title="Write Review">
                                                    <i class="fas fa-star"></i>
                                                </a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Pagination -->
                        <?php if ($total_orders > $per_page): ?>
                        <nav aria-label="Page navigation" class="mt-4">
                            <ul class="pagination justify-content-center">
                                <?php
                                $total_pages = ceil($total_orders / $per_page);
                                $current_url = $_SERVER['PHP_SELF'] . '?' . http_build_query($_GET);
                                $current_url = preg_replace('/&page=\d+/', '', $current_url);
                                ?>
                                
                                <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="<?php echo $current_url . '&page=' . ($page - 1); ?>">Previous</a>
                                </li>
                                
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <?php if ($i == 1 || $i == $total_pages || ($i >= $page - 2 && $i <= $page + 2)): ?>
                                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                        <a class="page-link" href="<?php echo $current_url . '&page=' . $i; ?>"><?php echo $i; ?></a>
                                    </li>
                                    <?php elseif ($i == $page - 3 || $i == $page + 3): ?>
                                    <li class="page-item disabled">
                                        <span class="page-link">...</span>
                                    </li>
                                    <?php endif; ?>
                                <?php endfor; ?>
                                
                                <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="<?php echo $current_url . '&page=' . ($page + 1); ?>">Next</a>
                                </li>
                            </ul>
                        </nav>
                        <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Order Status Guide -->
                <div class="card shadow-sm mt-4">
                    <div class="card-header bg-light">
                        <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>Order Status Guide</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3 text-center mb-3">
                                <div class="p-3 border rounded">
                                    <span class="badge bg-warning mb-2">Pending</span>
                                    <p class="small mb-0">Order received, awaiting processing</p>
                                </div>
                            </div>
                            <div class="col-md-3 text-center mb-3">
                                <div class="p-3 border rounded">
                                    <span class="badge bg-info mb-2">Processing</span>
                                    <p class="small mb-0">Order is being prepared for shipping</p>
                                </div>
                            </div>
                            <div class="col-md-3 text-center mb-3">
                                <div class="p-3 border rounded">
                                    <span class="badge bg-primary mb-2">Shipped</span>
                                    <p class="small mb-0">Order has been shipped</p>
                                </div>
                            </div>
                            <div class="col-md-3 text-center mb-3">
                                <div class="p-3 border rounded">
                                    <span class="badge bg-success mb-2">Delivered</span>
                                    <p class="small mb-0">Order delivered successfully</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <?php include '../public/includes/footer.php'; ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script src="../assets/js/buyer.js"></script>
</body>
</html>