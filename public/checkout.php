<?php
require_once '../config.php';

// Require login for checkout
if (!is_logged_in()) {
    $_SESSION['redirect_to'] = 'checkout.php';
    redirect('login.php');
}

// Require buyer account
if ($_SESSION['user_type'] !== 'buyer') {
    set_error('Only buyer accounts can place orders.');
    redirect('index.php');
}

$page_title = 'Checkout - ' . SITE_NAME;

// Get cart items
$cart_items = [];
$cart_subtotal = 0;
$shipping_cost = SHIPPING_COST;

if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
    $product = new Product();
    
    foreach ($_SESSION['cart'] as $key => $cart_item) {
        $product_data = $product->getProductById($cart_item['product_id']);
        
        if ($product_data) {
            $price = $product_data['discount_price'] ?? $product_data['price'];
            $subtotal = $price * $cart_item['quantity'];
            
            $cart_items[] = [
                'key' => $key,
                'product_id' => $cart_item['product_id'],
                'name' => $product_data['name'],
                'price' => $price,
                'quantity' => $cart_item['quantity'],
                'size' => $cart_item['size'],
                'color' => $cart_item['color'],
                'stock' => $product_data['stock_quantity'],
                'subtotal' => $subtotal,
                'vendor_id' => $product_data['vendor_id']
            ];
            
            $cart_subtotal += $subtotal;
        }
    }
}

// Check if cart is empty
if (empty($cart_items)) {
    set_error('Your cart is empty. Add items before checkout.');
    redirect('cart.php');
}

// Calculate totals
$vat = calculate_vat($cart_subtotal);
$cart_total = $cart_subtotal + $vat + $shipping_cost;

// Get user info
$user = new User();
$user_data = $user->getUserById($_SESSION['user_id']);

// Handle checkout form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate required fields
    $required_fields = ['shipping_address', 'phone', 'payment_method'];
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            $errors[] = ucfirst(str_replace('_', ' ', $field)) . ' is required.';
        }
    }
    
    // Check stock availability
    foreach ($cart_items as $item) {
        if ($item['quantity'] > $item['stock']) {
            $errors[] = "Insufficient stock for {$item['name']}. Only {$item['stock']} available.";
        }
    }
    
    // Process if no errors
    if (empty($errors)) {
        try {
            // Create order
            $order_data = [
                'buyer_id' => $_SESSION['user_id'],
                'total_amount' => $cart_total,
                'shipping_address' => $_POST['shipping_address'],
                'billing_address' => $_POST['billing_address'] ?? $_POST['shipping_address'],
                'phone' => $_POST['phone'],
                'payment_method' => $_POST['payment_method'],
                'notes' => $_POST['notes'] ?? ''
            ];
            
            $order = new Order();
            $order_result = $order->create($order_data);
            $order_id = $order_result['order_id'];
            
            // Prepare order items
            $order_items = [];
            foreach ($cart_items as $item) {
                $order_items[] = [
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['price']
                ];
            }
            
            // Add order items
            $order->addOrderItems($order_id, $order_items);
            
            // Process payment based on method
            $payment = new Payment();
            $payment_result = [];
            
            switch ($_POST['payment_method']) {
                case 'telebirr':
                    $payment_result = $payment->initiateTeleBirrPayment($_POST['phone'], $cart_total, $order_id);
                    break;
                    
                case 'cbe_birr':
                    $payment_result = $payment->initiateCBEbirrPayment($_POST['phone'], $cart_total, $order_id);
                    break;
                    
                case 'cash_on_delivery':
                    $payment_result = $payment->processCashOnDelivery($order_id);
                    break;
                    
                case 'bank_transfer':
                    $payment_result = $payment->processBankTransfer($order_id, [
                        'bank_name' => $_POST['bank_name'] ?? '',
                        'account_number' => $_POST['account_number'] ?? '',
                        'transaction_reference' => $_POST['transaction_reference'] ?? ''
                    ]);
                    break;
                    
                default:
                    throw new Exception('Invalid payment method.');
            }
            
            // Clear cart
            clear_cart_session();
            
            // Store order info in session for confirmation page
            $_SESSION['last_order'] = [
                'order_number' => $order_result['order_number'],
                'order_id' => $order_id,
                'total' => $cart_total,
                'payment_method' => $_POST['payment_method'],
                'payment_result' => $payment_result
            ];
            
            // Redirect to confirmation page
            redirect('order-confirmation.php');
            
        } catch (Exception $e) {
            $errors[] = $e->getMessage();
        }
    }
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
    <!-- Custom CSS -->
    <link href="../assets/css/style.css" rel="stylesheet">
    <link href="../assets/css/bootstrap-custom.css" rel="stylesheet">
</head>
<body>
    <!-- Navigation -->
    <?php include 'includes/navbar.php'; ?>

    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="bg-light py-3">
        <div class="container">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                <li class="breadcrumb-item"><a href="cart.php">Cart</a></li>
                <li class="breadcrumb-item active">Checkout</li>
            </ol>
        </div>
    </nav>

    <!-- Checkout Content -->
    <div class="container py-5">
        <!-- Errors -->
        <?php if (!empty($errors)): ?>
        <div class="alert alert-danger mb-4">
            <h5><i class="fas fa-exclamation-triangle me-2"></i>Please fix the following errors:</h5>
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                <li><?php echo $error; ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
        
        <div class="row">
            <!-- Checkout Form -->
            <div class="col-lg-8">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-shipping-fast me-2"></i>Shipping & Payment</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" id="checkout-form">
                            <!-- Contact Information -->
                            <div class="mb-4">
                                <h6 class="border-bottom pb-2 mb-3">Contact Information</h6>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="first_name" class="form-label">First Name *</label>
                                        <input type="text" class="form-control" id="first_name" 
                                               name="first_name" value="<?php echo htmlspecialchars($user_data['first_name'] ?? ''); ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="last_name" class="form-label">Last Name *</label>
                                        <input type="text" class="form-control" id="last_name" 
                                               name="last_name" value="<?php echo htmlspecialchars($user_data['last_name'] ?? ''); ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="email" class="form-label">Email *</label>
                                        <input type="email" class="form-control" id="email" 
                                               name="email" value="<?php echo htmlspecialchars($user_data['email'] ?? ''); ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="phone" class="form-label">Phone Number *</label>
                                        <input type="tel" class="form-control" id="phone" 
                                               name="phone" value="<?php echo htmlspecialchars($user_data['phone'] ?? ''); ?>" 
                                               pattern="\+251[0-9]{9}" placeholder="+251911234567" required>
                                        <small class="text-muted">Format: +251911234567</small>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Shipping Address -->
                            <div class="mb-4">
                                <h6 class="border-bottom pb-2 mb-3">Shipping Address</h6>
                                <div class="row">
                                    <div class="col-12 mb-3">
                                        <label for="shipping_address" class="form-label">Full Address *</label>
                                        <textarea class="form-control" id="shipping_address" name="shipping_address" rows="3" required
                                                  placeholder="Street address, City, Region"><?php echo htmlspecialchars($_POST['shipping_address'] ?? ''); ?></textarea>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="region" class="form-label">Region *</label>
                                        <select class="form-select" id="region" name="region" required>
                                            <option value="">Select Region</option>
                                            <?php foreach (get_ethiopian_regions() as $region): ?>
                                            <option value="<?php echo $region; ?>" <?php echo ($_POST['region'] ?? '') == $region ? 'selected' : ''; ?>>
                                                <?php echo $region; ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="city" class="form-label">City *</label>
                                        <input type="text" class="form-control" id="city" name="city" 
                                               value="<?php echo htmlspecialchars($_POST['city'] ?? ''); ?>" required>
                                    </div>
                                </div>
                                
                                <!-- Billing Address (Optional) -->
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="same_as_shipping">
                                    <label class="form-check-label" for="same_as_shipping">
                                        Billing address same as shipping
                                    </label>
                                </div>
                                
                                <div id="billing-address" style="display: none;">
                                    <div class="row">
                                        <div class="col-12 mb-3">
                                            <label for="billing_address" class="form-label">Billing Address</label>
                                            <textarea class="form-control" id="billing_address" name="billing_address" rows="2"
                                                      placeholder="Billing address (if different)"><?php echo htmlspecialchars($_POST['billing_address'] ?? ''); ?></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Payment Method -->
                            <div class="mb-4">
                                <h6 class="border-bottom pb-2 mb-3">Payment Method</h6>
                                <div class="row">
                                    <div class="col-12">
                                        <div class="form-check mb-3">
                                            <input class="form-check-input" type="radio" name="payment_method" 
                                                   id="telebirr" value="telebirr" required <?php echo ($_POST['payment_method'] ?? '') == 'telebirr' ? 'checked' : ''; ?>>
                                            <label class="form-check-label w-100" for="telebirr">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div>
                                                        <i class="fas fa-mobile-alt fa-2x text-success me-3"></i>
                                                        <strong>TeleBirr</strong>
                                                        <p class="small text-muted mb-0">Pay securely with TeleBirr mobile money</p>
                                                    </div>
                                                    <img src="../assets/images/payment/telebirr.png" alt="TeleBirr" style="height: 40px;">
                                                </div>
                                            </label>
                                        </div>
                                        
                                        <div class="form-check mb-3">
                                            <input class="form-check-input" type="radio" name="payment_method" 
                                                   id="cbe_birr" value="cbe_birr" <?php echo ($_POST['payment_method'] ?? '') == 'cbe_birr' ? 'checked' : ''; ?>>
                                            <label class="form-check-label w-100" for="cbe_birr">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div>
                                                        <i class="fas fa-university fa-2x text-primary me-3"></i>
                                                        <strong>CBE Birr</strong>
                                                        <p class="small text-muted mb-0">Pay with CBE Birr mobile banking</p>
                                                    </div>
                                                    <img src="../assets/images/payment/cbe-birr.png" alt="CBE Birr" style="height: 40px;">
                                                </div>
                                            </label>
                                        </div>
                                        
                                        <div class="form-check mb-3">
                                            <input class="form-check-input" type="radio" name="payment_method" 
                                                   id="cash_on_delivery" value="cash_on_delivery" <?php echo ($_POST['payment_method'] ?? '') == 'cash_on_delivery' ? 'checked' : ''; ?>>
                                            <label class="form-check-label w-100" for="cash_on_delivery">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div>
                                                        <i class="fas fa-money-bill-wave fa-2x text-warning me-3"></i>
                                                        <strong>Cash on Delivery</strong>
                                                        <p class="small text-muted mb-0">Pay when you receive your order</p>
                                                    </div>
                                                    <small class="text-muted">+ETB 50 service fee</small>
                                                </div>
                                            </label>
                                        </div>
                                        
                                        <div class="form-check mb-3">
                                            <input class="form-check-input" type="radio" name="payment_method" 
                                                   id="bank_transfer" value="bank_transfer" <?php echo ($_POST['payment_method'] ?? '') == 'bank_transfer' ? 'checked' : ''; ?>>
                                            <label class="form-check-label w-100" for="bank_transfer">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div>
                                                        <i class="fas fa-exchange-alt fa-2x text-info me-3"></i>
                                                        <strong>Bank Transfer</strong>
                                                        <p class="small text-muted mb-0">Transfer to our bank account</p>
                                                    </div>
                                                    <img src="../assets/images/payment/bank-transfer.png" alt="Bank Transfer" style="height: 40px;">
                                                </div>
                                            </label>
                                        </div>
                                        
                                        <!-- Bank Transfer Details (shown when selected) -->
                                        <div id="bank-transfer-details" class="border rounded p-3 mt-3" style="display: none;">
                                            <h6 class="mb-3">Bank Transfer Information</h6>
                                            <div class="row">
                                                <div class="col-md-6 mb-2">
                                                    <strong>Bank Name:</strong> Commercial Bank of Ethiopia
                                                </div>
                                                <div class="col-md-6 mb-2">
                                                    <strong>Account Name:</strong> SneakerHub Ethiopia
                                                </div>
                                                <div class="col-md-6 mb-2">
                                                    <strong>Account Number:</strong> 1000001234567
                                                </div>
                                                <div class="col-md-6 mb-2">
                                                    <strong>Branch:</strong> Addis Ababa Main Branch
                                                </div>
                                            </div>
                                            <div class="mt-3">
                                                <label for="transaction_reference" class="form-label">Transaction Reference *</label>
                                                <input type="text" class="form-control" id="transaction_reference" 
                                                       name="transaction_reference" placeholder="Your bank transaction reference">
                                                <small class="text-muted">Required for payment verification</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Order Notes -->
                            <div class="mb-4">
                                <h6 class="border-bottom pb-2 mb-3">Order Notes (Optional)</h6>
                                <textarea class="form-control" id="notes" name="notes" rows="3" 
                                          placeholder="Special instructions for delivery, notes about your order, etc."><?php echo htmlspecialchars($_POST['notes'] ?? ''); ?></textarea>
                            </div>
                            
                            <!-- Terms and Conditions -->
                            <div class="form-check mb-4">
                                <input class="form-check-input" type="checkbox" id="terms" name="terms" required>
                                <label class="form-check-label" for="terms">
                                    I agree to the <a href="terms.php" target="_blank">Terms and Conditions</a> and <a href="privacy.php" target="_blank">Privacy Policy</a> *
                                </label>
                            </div>
                            
                            <!-- Submit Button -->
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-lock me-2"></i>Place Order
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Security Badges -->
                <div class="card shadow-sm">
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-md-3 col-6 mb-3">
                                <i class="fas fa-shield-alt fa-2x text-success mb-2"></i>
                                <p class="small mb-0">Secure Payment</p>
                            </div>
                            <div class="col-md-3 col-6 mb-3">
                                <i class="fas fa-truck fa-2x text-primary mb-2"></i>
                                <p class="small mb-0">Fast Delivery</p>
                            </div>
                            <div class="col-md-3 col-6">
                                <i class="fas fa-undo-alt fa-2x text-warning mb-2"></i>
                                <p class="small mb-0">Easy Returns</p>
                            </div>
                            <div class="col-md-3 col-6">
                                <i class="fas fa-headset fa-2x text-info mb-2"></i>
                                <p class="small mb-0">24/7 Support</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Order Summary -->
            <div class="col-lg-4">
                <div class="card shadow-sm sticky-top" style="top: 20px;">
                    <div class="card-header bg-light">
                        <h5 class="mb-0"><i class="fas fa-shopping-bag me-2"></i>Order Summary</h5>
                    </div>
                    <div class="card-body">
                        <!-- Order Items -->
                        <div class="mb-3">
                            <h6 class="text-muted mb-3">Items (<?php echo count($cart_items); ?>)</h6>
                            <?php foreach ($cart_items as $item): ?>
                            <div class="d-flex mb-3">
                                <img src="../assets/images/products/default.jpg" 
                                     alt="<?php echo htmlspecialchars($item['name']); ?>"
                                     class="img-thumbnail me-3" 
                                     style="width: 60px; height: 60px; object-fit: cover;">
                                <div class="flex-grow-1">
                                    <h6 class="mb-1"><?php echo htmlspecialchars($item['name']); ?></h6>
                                    <p class="text-muted small mb-1">
                                        Qty: <?php echo $item['quantity']; ?>
                                        <?php if (!empty($item['size'])): ?>
                                        | Size: <?php echo htmlspecialchars($item['size']); ?>
                                        <?php endif; ?>
                                        <?php if (!empty($item['color'])): ?>
                                        | Color: <?php echo htmlspecialchars($item['color']); ?>
                                        <?php endif; ?>
                                    </p>
                                    <p class="mb-0 fw-bold"><?php echo format_price($item['subtotal']); ?></p>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <!-- Order Totals -->
                        <div class="border-top pt-3">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Subtotal:</span>
                                <span class="fw-bold"><?php echo format_price($cart_subtotal); ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Shipping:</span>
                                <span class="fw-bold"><?php echo format_price($shipping_cost); ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">VAT (15%):</span>
                                <span class="fw-bold"><?php echo format_price($vat); ?></span>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between mb-3">
                                <span class="h5 mb-0">Total:</span>
                                <span class="h5 mb-0 text-primary"><?php echo format_price($cart_total); ?></span>
                            </div>
                            <small class="text-muted d-block mb-3">
                                <i class="fas fa-info-circle me-1"></i>
                                Delivery in <?php echo get_delivery_estimate($_POST['region'] ?? 'Addis Ababa'); ?> days
                            </small>
                            
                            <!-- Back to Cart -->
                            <div class="d-grid">
                                <a href="cart.php" class="btn btn-outline-primary">
                                    <i class="fas fa-arrow-left me-2"></i>Back to Cart
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Need Help? -->
                <div class="card shadow-sm mt-4">
                    <div class="card-header bg-light">
                        <h6 class="mb-0"><i class="fas fa-question-circle me-2"></i>Need Help?</h6>
                    </div>
                    <div class="card-body">
                        <div class="small">
                            <p class="mb-2"><i class="fas fa-phone me-2"></i> Call: +251 911 123 456</p>
                            <p class="mb-2"><i class="fas fa-envelope me-2"></i> Email: support@sneakerhub.et</p>
                            <p class="mb-0"><i class="fas fa-clock me-2"></i> Hours: 24/7</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <?php include 'includes/footer.php'; ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script src="../assets/js/main.js"></script>
    
    <script>
        // Same as shipping checkbox
        document.getElementById('same_as_shipping').addEventListener('change', function() {
            const billingAddressDiv = document.getElementById('billing-address');
            if (this.checked) {
                billingAddressDiv.style.display = 'none';
                document.getElementById('billing_address').value = '';
            } else {
                billingAddressDiv.style.display = 'block';
            }
        });
        
        // Show bank transfer details when selected
        document.querySelectorAll('input[name="payment_method"]').forEach(radio => {
            radio.addEventListener('change', function() {
                const bankDetails = document.getElementById('bank-transfer-details');
                if (this.value === 'bank_transfer') {
                    bankDetails.style.display = 'block';
                } else {
                    bankDetails.style.display = 'none';
                }
            });
        });
        
        // Form validation
        document.getElementById('checkout-form').addEventListener('submit', function(e) {
            // Check if terms are accepted
            if (!document.getElementById('terms').checked) {
                e.preventDefault();
                alert('Please accept the Terms and Conditions.');
                return;
            }
            
            // Check if payment method is selected
            const paymentMethod = document.querySelector('input[name="payment_method"]:checked');
            if (!paymentMethod) {
                e.preventDefault();
                alert('Please select a payment method.');
                return;
            }
            
            // Additional validation for bank transfer
            if (paymentMethod.value === 'bank_transfer') {
                const reference = document.getElementById('transaction_reference').value;
                if (!reference.trim()) {
                    e.preventDefault();
                    alert('Please enter your bank transaction reference.');
                    return;
                }
            }
            
            // Show loading
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Processing...';
            submitBtn.disabled = true;
        });
        
        // Auto-fill city based on region
        document.getElementById('region').addEventListener('change', function() {
            const region = this.value;
            const cityInput = document.getElementById('city');
            
            const regionCities = {
                'Addis Ababa': 'Addis Ababa',
                'Oromia': 'Adama',
                'Amhara': 'Bahir Dar',
                'Tigray': 'Mekelle',
                'Southern Nations': 'Hawassa',
                'Somali': 'Jijiga',
                'Afar': 'Semera',
                'Benishangul-Gumuz': 'Assosa',
                'Gambela': 'Gambela',
                'Harari': 'Harar',
                'Sidama': 'Hawassa',
                'Dire Dawa': 'Dire Dawa'
            };
            
            if (regionCities[region]) {
                cityInput.value = regionCities[region];
            } else {
                cityInput.value = '';
            }
        });
    </script>
</body>
</html>