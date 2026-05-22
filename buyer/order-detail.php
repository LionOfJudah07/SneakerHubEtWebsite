<?php
require_once '../config.php';

// Require buyer login
require_buyer();

// Check if order ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    redirect('orders.php');
}

$order_id = intval($_GET['id']);
$order = new Order();

// Get order details
$order_data = $order->getOrderById($order_id);

// Check if order belongs to buyer
if (!$order_data || $order_data['buyer_id'] != $_SESSION['user_id']) {
    $_SESSION['error'] = 'Order not found or access denied.';
    redirect('orders.php');
}

$page_title = 'Order #' . $order_data['order_number'] . ' - ' . SITE_NAME;

// Get order items
$order_items = $order->getOrderItems($order_id);

// Get user data
$user = new User();
$user_data = $user->getUserById($_SESSION['user_id']);

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
                <?php include 'includes/sidebar.php'; ?>
            </div>
            
            <!-- Main Content -->
            <div class="col-lg-9">
                <!-- Breadcrumb -->
                <nav aria-label="breadcrumb" class="bg-light mb-4 p-3 rounded">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="orders.php">My Orders</a></li>
                        <li class="breadcrumb-item active">Order #<?php echo $order_data['order_number']; ?></li>
                    </ol>
                </nav>
                
                <!-- Order Header -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-0"><i class="fas fa-receipt me-2"></i>Order Details</h5>
                            <small class="text-white-50">Placed on <?php echo format_date($order_data['created_at']); ?></small>
                        </div>
                        <div class="text-end">
                            <h4 class="mb-0"><?php echo format_price($order_data['total_amount']); ?></h4>
                            <small class="text-white-50">Total Amount</small>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Order Status Bar -->
                        <div class="mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="mb-0">Order Status</h6>
                                <div>
                                    <?php echo get_order_status_badge($order_data['status']); ?>
                                    <?php echo get_payment_status_badge($order_data['payment_status']); ?>
                                </div>
                            </div>
                            
                            <!-- Progress Steps -->
                            <div class="progress-steps">
                                <div class="step <?php echo $order_data['status'] == 'pending' ? 'active' : ''; ?>">
                                    <div class="step-icon">1</div>
                                    <div class="step-label">Pending</div>
                                </div>
                                <div class="step <?php echo $order_data['status'] == 'processing' ? 'active' : ''; ?>">
                                    <div class="step-icon">2</div>
                                    <div class="step-label">Processing</div>
                                </div>
                                <div class="step <?php echo $order_data['status'] == 'shipped' ? 'active' : ''; ?>">
                                    <div class="step-icon">3</div>
                                    <div class="step-label">Shipped</div>
                                </div>
                                <div class="step <?php echo $order_data['status'] == 'delivered' ? 'active' : ''; ?>">
                                    <div class="step-icon">4</div>
                                    <div class="step-label">Delivered</div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Order Information -->
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="card h-100">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0"><i class="fas fa-user me-2"></i>Customer Information</h6>
                                    </div>
                                    <div class="card-body">
                                        <p class="mb-2"><strong>Name:</strong> <?php echo htmlspecialchars($user_data['first_name'] . ' ' . $user_data['last_name']); ?></p>
                                        <p class="mb-2"><strong>Email:</strong> <?php echo htmlspecialchars($user_data['email']); ?></p>
                                        <p class="mb-2"><strong>Phone:</strong> <?php echo htmlspecialchars($user_data['phone']); ?></p>
                                        <p class="mb-0"><strong>Order #:</strong> <?php echo $order_data['order_number']; ?></p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <div class="card h-100">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0"><i class="fas fa-truck me-2"></i>Shipping Information</h6>
                                    </div>
                                    <div class="card-body">
                                        <p class="mb-2"><strong>Shipping Address:</strong></p>
                                        <p class="mb-2"><?php echo nl2br(htmlspecialchars($order_data['shipping_address'])); ?></p>
                                        <p class="mb-2"><strong>Phone:</strong> <?php echo htmlspecialchars($order_data['phone']); ?></p>
                                        <p class="mb-0"><strong>Payment Method:</strong> 
                                            <?php 
                                            $payment_methods = [
                                                'telebirr' => 'TeleBirr',
                                                'cbe_birr' => 'CBE Birr',
                                                'cash_on_delivery' => 'Cash on Delivery',
                                                'bank_transfer' => 'Bank Transfer'
                                            ];
                                            echo $payment_methods[$order_data['payment_method']] ?? $order_data['payment_method'];
                                            ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Order Items -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0"><i class="fas fa-boxes me-2"></i>Order Items (<?php echo count($order_items); ?>)</h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($order_items as $item): ?>
                        <div class="row align-items-center mb-3 pb-3 border-bottom">
                            <div class="col-md-2">
                                <img src="<?php echo !empty($item['images']) ? '../' . $item['images'][0] : '../assets/images/products/default.jpg'; ?>" 
                                     alt="<?php echo htmlspecialchars($item['name']); ?>"
                                     class="img-thumbnail" 
                                     style="width: 100px; height: 100px; object-fit: cover;">
                            </div>
                            <div class="col-md-4">
                                <h6 class="mb-1"><?php echo htmlspecialchars($item['name']); ?></h6>
                                <p class="text-muted small mb-1"><?php echo htmlspecialchars($item['brand']); ?></p>
                                <p class="text-muted small mb-0">Seller: <?php echo htmlspecialchars($item['store_name']); ?></p>
                            </div>
                            <div class="col-md-2">
                                <p class="mb-1">Quantity: <?php echo $item['quantity']; ?></p>
                                <p class="text-muted small mb-0">Price: <?php echo format_price($item['unit_price']); ?></p>
                            </div>
                            <div class="col-md-2">
                                <p class="fw-bold"><?php echo format_price($item['subtotal']); ?></p>
                            </div>
                            <div class="col-md-2 text-end">
                                <a href="../public/product-detail.php?id=<?php echo $item['product_id']; ?>" 
                                   class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-eye"></i> View Product
                                </a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        
                        <!-- Order Summary -->
                        <div class="row justify-content-end">
                            <div class="col-md-6">
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <tbody>
                                            <tr>
                                                <td>Subtotal:</td>
                                                <td class="text-end">
                                                    <?php
                                                    $subtotal = 0;
                                                    foreach ($order_items as $item) {
                                                        $subtotal += $item['subtotal'];
                                                    }
                                                    echo format_price($subtotal);
                                                    ?>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Shipping:</td>
                                                <td class="text-end">
                                                    <?php echo format_price($order_data['total_amount'] - $subtotal - calculate_vat($subtotal)); ?>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>VAT (15%):</td>
                                                <td class="text-end"><?php echo format_price(calculate_vat($subtotal)); ?></td>
                                            </tr>
                                            <tr class="table-active">
                                                <th>Total:</th>
                                                <th class="text-end"><?php echo format_price($order_data['total_amount']); ?></th>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Order Actions -->
                <div class="card shadow-sm">
                    <div class="card-header bg-light">
                        <h5 class="mb-0"><i class="fas fa-cogs me-2"></i>Order Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex gap-3">
                            <?php if ($order_data['status'] === 'pending'): ?>
                            <a href="order-cancel.php?id=<?php echo $order_id; ?>" 
                               class="btn btn-outline-danger"
                               onclick="return confirm('Are you sure you want to cancel this order?')">
                                <i class="fas fa-times me-2"></i>Cancel Order
                            </a>
                            <?php endif; ?>
                            
                            <?php if ($order_data['status'] === 'delivered'): ?>
                            <a href="order-review.php?id=<?php echo $order_id; ?>" 
                               class="btn btn-outline-success">
                                <i class="fas fa-star me-2"></i>Write Review
                            </a>
                            <?php endif; ?>
                            
                            <a href="../public/contact.php?order=<?php echo $order_id; ?>" 
                               class="btn btn-outline-primary">
                                <i class="fas fa-headset me-2"></i>Contact Support
                            </a>
                            
                            <button onclick="window.print()" class="btn btn-outline-secondary">
                                <i class="fas fa-print me-2"></i>Print Invoice
                            </button>
                            
                            <a href="orders.php" class="btn btn-outline-dark ms-auto">
                                <i class="fas fa-arrow-left me-2"></i>Back to Orders
                            </a>
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
    
    <style>
        .progress-steps {
            display: flex;
            justify-content: space-between;
            position: relative;
            margin: 20px 0;
        }
        .progress-steps::before {
            content: '';
            position: absolute;
            top: 20px;
            left: 0;
            right: 0;
            height: 3px;
            background: #dee2e6;
            z-index: 1;
        }
        .step {
            position: relative;
            z-index: 2;
            text-align: center;
            flex: 1;
        }
        .step.active .step-icon {
            background: #4e73df;
            color: white;
            border-color: #4e73df;
        }
        .step.completed .step-icon {
            background: #1cc88a;
            color: white;
            border-color: #1cc88a;
        }
        .step-icon {
            width: 40px;
            height: 40px;
            line-height: 40px;
            border-radius: 50%;
            background: white;
            border: 2px solid #dee2e6;
            margin: 0 auto 10px;
            font-weight: bold;
        }
        .step-label {
            font-size: 0.875rem;
            color: #6c757d;
        }
        .step.active .step-label {
            color: #4e73df;
            font-weight: bold;
        }
    </style>
</body>
</html>