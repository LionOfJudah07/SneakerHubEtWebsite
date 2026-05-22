<?php
require_once '../config.php';

$page_title = 'Order Confirmation - ' . SITE_NAME;

// Check if order was placed
if (!isset($_SESSION['last_order'])) {
    redirect('index.php');
}

$order_data = $_SESSION['last_order'];
unset($_SESSION['last_order']);

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
    <?php include 'includes/navbar.php'; ?>

    <!-- Order Confirmation -->
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card shadow-lg border-0">
                    <div class="card-header bg-success text-white text-center py-4">
                        <i class="fas fa-check-circle fa-4x mb-3"></i>
                        <h2 class="mb-2">Order Confirmed!</h2>
                        <p class="mb-0">Thank you for your purchase</p>
                    </div>
                    
                    <div class="card-body p-4">
                        <!-- Order Summary -->
                        <div class="text-center mb-5">
                            <h4 class="text-success mb-3">
                                <i class="fas fa-receipt me-2"></i>
                                Order #<?php echo $order_data['order_number']; ?>
                            </h4>
                            <p class="lead">Your order has been successfully placed.</p>
                        </div>
                        
                        <div class="row mb-4">
                            <div class="col-md-6 mb-3">
                                <div class="card h-100 border-success">
                                    <div class="card-body text-center">
                                        <i class="fas fa-clock fa-2x text-warning mb-3"></i>
                                        <h5>Order Status</h5>
                                        <p class="text-muted mb-0">Pending Processing</p>
                                        <div class="mt-2">
                                            <span class="badge bg-warning">Processing</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="card h-100 border-success">
                                    <div class="card-body text-center">
                                        <i class="fas fa-money-bill-wave fa-2x text-primary mb-3"></i>
                                        <h5>Total Amount</h5>
                                        <p class="display-6 text-success mb-0"><?php echo format_price($order_data['total']); ?></p>
                                        <small class="text-muted">Including shipping and VAT</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Payment Information -->
                        <div class="card mb-4 border-primary">
                            <div class="card-header bg-light">
                                <h5 class="mb-0"><i class="fas fa-credit-card me-2"></i>Payment Information</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <p class="mb-2"><strong>Payment Method:</strong></p>
                                        <p>
                                            <?php
                                            $payment_methods = [
                                                'telebirr' => '<i class="fas fa-mobile-alt text-success me-2"></i> TeleBirr',
                                                'cbe_birr' => '<i class="fas fa-university text-primary me-2"></i> CBE Birr',
                                                'cash_on_delivery' => '<i class="fas fa-money-bill-wave text-warning me-2"></i> Cash on Delivery',
                                                'bank_transfer' => '<i class="fas fa-exchange-alt text-info me-2"></i> Bank Transfer'
                                            ];
                                            echo $payment_methods[$order_data['payment_method']] ?? $order_data['payment_method'];
                                            ?>
                                        </p>
                                    </div>
                                    <div class="col-md-6">
                                        <p class="mb-2"><strong>Payment Status:</strong></p>
                                        <p>
                                            <?php
                                            if ($order_data['payment_method'] === 'cash_on_delivery' || $order_data['payment_method'] === 'bank_transfer') {
                                                echo '<span class="badge bg-warning">Pending</span>';
                                            } else {
                                                echo '<span class="badge bg-success">Paid</span>';
                                            }
                                            ?>
                                        </p>
                                    </div>
                                </div>
                                
                                <?php if (isset($order_data['payment_result']['message'])): ?>
                                <div class="alert alert-info mt-3">
                                    <i class="fas fa-info-circle me-2"></i>
                                    <?php echo $order_data['payment_result']['message']; ?>
                                </div>
                                <?php endif; ?>
                                
                                <?php if ($order_data['payment_method'] === 'bank_transfer'): ?>
                                <div class="alert alert-warning mt-3">
                                    <h6><i class="fas fa-university me-2"></i>Bank Transfer Details:</h6>
                                    <p class="mb-1"><strong>Bank:</strong> Commercial Bank of Ethiopia</p>
                                    <p class="mb-1"><strong>Account Name:</strong> SneakerHub Ethiopia</p>
                                    <p class="mb-1"><strong>Account Number:</strong> 1000001234567</p>
                                    <p class="mb-0"><strong>Reference:</strong> <?php echo $order_data['order_number']; ?></p>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Next Steps -->
                        <div class="card mb-4 border-info">
                            <div class="card-header bg-light">
                                <h5 class="mb-0"><i class="fas fa-list-check me-2"></i>What Happens Next?</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-3 text-center mb-3">
                                        <div class="step-number mb-2">1</div>
                                        <h6>Order Confirmed</h6>
                                        <p class="small text-muted">We've received your order</p>
                                    </div>
                                    <div class="col-md-3 text-center mb-3">
                                        <div class="step-number mb-2">2</div>
                                        <h6>Processing</h6>
                                        <p class="small text-muted">Preparing your items</p>
                                    </div>
                                    <div class="col-md-3 text-center mb-3">
                                        <div class="step-number mb-2">3</div>
                                        <h6>Shipped</h6>
                                        <p class="small text-muted">Items dispatched for delivery</p>
                                    </div>
                                    <div class="col-md-3 text-center mb-3">
                                        <div class="step-number mb-2">4</div>
                                        <h6>Delivered</h6>
                                        <p class="small text-muted">Items delivered to you</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Action Buttons -->
                        <div class="text-center">
                            <div class="d-grid d-md-flex justify-content-center gap-3">
                                <a href="../buyer/orders.php" class="btn btn-primary btn-lg">
                                    <i class="fas fa-shopping-bag me-2"></i>View My Orders
                                </a>
                                <a href="shop.php" class="btn btn-outline-primary btn-lg">
                                    <i class="fas fa-shopping-cart me-2"></i>Continue Shopping
                                </a>
                                <a href="index.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-home me-2"></i>Back to Home
                                </a>
                            </div>
                        </div>
                        
                        <!-- Contact Support -->
                        <div class="text-center mt-5">
                            <p class="text-muted">Need help with your order?</p>
                            <a href="contact.php" class="btn btn-outline-success">
                                <i class="fas fa-headset me-2"></i>Contact Support
                            </a>
                        </div>
                    </div>
                    
                    <div class="card-footer bg-light text-center py-3">
                        <small class="text-muted">
                            A confirmation email has been sent to your registered email address. 
                            Check your spam folder if you don't see it.
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <?php include 'includes/footer.php'; ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <style>
        .step-number {
            width: 40px;
            height: 40px;
            line-height: 40px;
            background: #4e73df;
            color: white;
            border-radius: 50%;
            margin: 0 auto;
            font-weight: bold;
        }
    </style>
</body>
</html>