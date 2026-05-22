<?php
require_once '../config.php';

$page_title = 'Frequently Asked Questions - ' . SITE_NAME;

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
    
    <!-- Main Content -->
    <main class="py-5">
        <div class="container">
            <!-- Page Header -->
            <div class="row mb-5">
                <div class="col-12 text-center">
                    <h1 class="display-5 fw-bold mb-3">Frequently Asked Questions</h1>
                    <p class="lead text-muted">Find quick answers to common questions</p>
                </div>
            </div>
            
            <!-- Search FAQ -->
            <div class="row mb-5">
                <div class="col-lg-8 mx-auto">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-4">
                            <div class="input-group input-group-lg">
                                <span class="input-group-text bg-light border-0">
                                    <i class="fas fa-search text-muted"></i>
                                </span>
                                <input type="text" class="form-control border-0" id="faqSearch" 
                                       placeholder="Search for questions about orders, shipping, returns, etc...">
                                <button class="btn btn-primary" type="button" id="searchButton">Search</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- FAQ Categories -->
            <div class="row mb-5">
                <div class="col-12">
                    <div class="d-flex flex-wrap justify-content-center gap-3">
                        <a href="#orders" class="btn btn-outline-primary">
                            <i class="fas fa-shopping-cart me-2"></i>Orders
                        </a>
                        <a href="#shipping" class="btn btn-outline-primary">
                            <i class="fas fa-truck me-2"></i>Shipping
                        </a>
                        <a href="#payments" class="btn btn-outline-primary">
                            <i class="fas fa-credit-card me-2"></i>Payments
                        </a>
                        <a href="#returns" class="btn btn-outline-primary">
                            <i class="fas fa-exchange-alt me-2"></i>Returns
                        </a>
                        <a href="#account" class="btn btn-outline-primary">
                            <i class="fas fa-user me-2"></i>Account
                        </a>
                        <a href="#products" class="btn btn-outline-primary">
                            <i class="fas fa-shoe-prints me-2"></i>Products
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Orders FAQ -->
            <div class="row mb-5" id="orders">
                <div class="col-12">
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-primary text-white">
                            <h3 class="mb-0"><i class="fas fa-shopping-cart me-2"></i>Orders & Purchases</h3>
                        </div>
                        <div class="card-body">
                            <div class="accordion" id="ordersAccordion">
                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#order1">
                                            How do I place an order?
                                        </button>
                                    </h2>
                                    <div id="order1" class="accordion-collapse collapse show" data-bs-parent="#ordersAccordion">
                                        <div class="accordion-body">
                                            <p>To place an order:</p>
                                            <ol>
                                                <li>Browse products and add items to your cart</li>
                                                <li>Click the cart icon and review your items</li>
                                                <li>Click "Proceed to Checkout"</li>
                                                <li>Enter your shipping address and contact information</li>
                                                <li>Choose a payment method (TeleBirr, CBE Birr, Cash on Delivery, or Bank Transfer)</li>
                                                <li>Review your order and click "Place Order"</li>
                                            </ol>
                                            <p>You'll receive an order confirmation via email and SMS.</p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#order2">
                                            How can I check my order status?
                                        </button>
                                    </h2>
                                    <div id="order2" class="accordion-collapse collapse" data-bs-parent="#ordersAccordion">
                                        <div class="accordion-body">
                                            <p>You can check your order status in several ways:</p>
                                            <ul>
                                                <li><strong>Account Dashboard:</strong> Log in and go to "My Orders"</li>
                                                <li><strong>Email:</strong> Check your order confirmation email for status updates</li>
                                                <li><strong>SMS:</strong> We send SMS updates for major status changes</li>
                                                <li><strong>Tracking Number:</strong> Once shipped, use the tracking number in your email</li>
                                                <li><strong>Customer Support:</strong> Call +251-911-123-456 with your order number</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#order3">
                                            Can I modify or cancel my order?
                                        </button>
                                    </h2>
                                    <div id="order3" class="accordion-collapse collapse" data-bs-parent="#ordersAccordion">
                                        <div class="accordion-body">
                                            <p><strong>Order Modification:</strong> You can modify your order (change address, phone, or items) within 1 hour of placing it through your account dashboard.</p>
                                            <p><strong>Order Cancellation:</strong> You can cancel your order within 24 hours if it hasn't been shipped yet. Go to "My Orders" and click "Cancel Order".</p>
                                            <p><strong>After 24 hours:</strong> If your order has been shipped, you'll need to wait for delivery and then initiate a return.</p>
                                            <p><strong>Cancellation Fees:</strong> No fees for cancellations before shipping. For cash on delivery orders cancelled after dispatch, a ETB 150 cancellation fee may apply.</p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#order4">
                                            How do I use a discount code?
                                        </button>
                                    </h2>
                                    <div id="order4" class="accordion-collapse collapse" data-bs-parent="#ordersAccordion">
                                        <div class="accordion-body">
                                            <p>To use a discount code:</p>
                                            <ol>
                                                <li>Add items to your cart</li>
                                                <li>Click the cart icon to view your cart</li>
                                                <li>Find the "Discount Code" field</li>
                                                <li>Enter your code and click "Apply"</li>
                                                <li>The discount will be applied to your order total</li>
                                            </ol>
                                            <p><strong>Important Notes:</strong></p>
                                            <ul>
                                                <li>Discount codes are case-sensitive</li>
                                                <li>Some codes have minimum purchase requirements</li>
                                                <li>Only one code can be used per order</li>
                                                <li>Codes cannot be combined with other promotions</li>
                                                <li>Check expiry dates - expired codes won't work</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Shipping FAQ -->
            <div class="row mb-5" id="shipping">
                <div class="col-12">
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-success text-white">
                            <h3 class="mb-0"><i class="fas fa-truck me-2"></i>Shipping & Delivery</h3>
                        </div>
                        <div class="card-body">
                            <div class="accordion" id="shippingAccordion">
                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#shipping1">
                                            What are your shipping options and costs?
                                        </button>
                                    </h2>
                                    <div id="shipping1" class="accordion-collapse collapse show" data-bs-parent="#shippingAccordion">
                                        <div class="accordion-body">
                                            <p>We offer three shipping options:</p>
                                            <table class="table table-bordered">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>Service</th>
                                                        <th>Cost</th>
                                                        <th>Delivery Time</th>
                                                        <th>Features</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr>
                                                        <td><strong>Standard</strong></td>
                                                        <td>ETB 150-350</td>
                                                        <td>1-10 days</td>
                                                        <td>Tracking, SMS updates</td>
                                                    </tr>
                                                    <tr>
                                                        <td><strong>Express</strong></td>
                                                        <td>ETB 300-600</td>
                                                        <td>Same day - 5 days</td>
                                                        <td>Priority, 24/7 support</td>
                                                    </tr>
                                                    <tr>
                                                        <td><strong>Store Pickup</strong></td>
                                                        <td>FREE</td>
                                                        <td>2 hours</td>
                                                        <td>Try before buy, no shipping</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                            <p>Exact cost depends on your location. Calculate shipping at checkout.</p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#shipping2">
                                            How long does delivery take?
                                        </button>
                                    </h2>
                                    <div id="shipping2" class="accordion-collapse collapse" data-bs-parent="#shippingAccordion">
                                        <div class="accordion-body">
                                            <p>Delivery times vary by location and shipping method:</p>
                                            <ul>
                                                <li><strong>Addis Ababa:</strong> 1-2 days (Standard), Same day (Express)</li>
                                                <li><strong>Major Cities (Adama, Bahir Dar, Hawassa, etc.):</strong> 3-5 days (Standard), 1-2 days (Express)</li>
                                                <li><strong>Other Regions:</strong> 5-10 days (Standard), 3-5 days (Express)</li>
                                                <li><strong>Store Pickup:</strong> Ready in 2 hours</li>
                                            </ul>
                                            <p><strong>Processing Time:</strong> Orders are processed within 24 hours on business days. Orders placed after 2 PM or on weekends may be processed the next business day.</p>
                                            <p><strong>Note:</strong> Delivery times are estimates and may be affected by weather, holidays, or other factors beyond our control.</p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#shipping3">
                                            Do you offer international shipping?
                                        </button>
                                    </h2>
                                    <div id="shipping3" class="accordion-collapse collapse" data-bs-parent="#shippingAccordion">
                                        <div class="accordion-body">
                                            <p>Currently, we only ship within Ethiopia. We're working on expanding to international shipping to the following countries soon:</p>
                                            <ul>
                                                <li>Kenya</li>
                                                <li>Uganda</li>
                                                <li>Tanzania</li>
                                                <li>Rwanda</li>
                                                <li>Djibouti</li>
                                                <li>Sudan</li>
                                            </ul>
                                            <p>Sign up for our newsletter to be notified when international shipping becomes available.</p>
                                            <p><strong>For International Customers:</strong> If you have someone in Ethiopia who can receive the package for you, you can still order and have it delivered to their address.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Payments FAQ -->
            <div class="row mb-5" id="payments">
                <div class="col-12">
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-warning text-white">
                            <h3 class="mb-0"><i class="fas fa-credit-card me-2"></i>Payments & Pricing</h3>
                        </div>
                        <div class="card-body">
                            <div class="accordion" id="paymentsAccordion">
                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#payment1">
                                            What payment methods do you accept?
                                        </button>
                                    </h2>
                                    <div id="payment1" class="accordion-collapse collapse show" data-bs-parent="#paymentsAccordion">
                                        <div class="accordion-body">
                                            <p>We accept the following payment methods:</p>
                                            <div class="row">
                                                <div class="col-md-6 mb-3">
                                                    <div class="border rounded p-3">
                                                        <h6><i class="fas fa-mobile-alt text-primary me-2"></i>Mobile Money</h6>
                                                        <ul class="mb-0">
                                                            <li>TeleBirr</li>
                                                            <li>CBE Birr</li>
                                                            <li>Amole (coming soon)</li>
                                                        </ul>
                                                    </div>
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <div class="border rounded p-3">
                                                        <h6><i class="fas fa-money-bill-wave text-success me-2"></i>Other Methods</h6>
                                                        <ul class="mb-0">
                                                            <li>Cash on Delivery (Addis Ababa & major cities)</li>
                                                            <li>Bank Transfer (CBE, Awash, Dashen)</li>
                                                            <li>Credit/Debit Cards (Visa, Mastercard) - coming soon</li>
                                                        </ul>
                                                    </div>
                                                </div>
                                            </div>
                                            <p><strong>Security:</strong> All payments are processed securely. We never store your full card or mobile money details.</p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#payment2">
                                            Is Cash on Delivery available in my area?
                                        </button>
                                    </h2>
                                    <div id="payment2" class="accordion-collapse collapse" data-bs-parent="#paymentsAccordion">
                                        <div class="accordion-body">
                                            <p>Cash on Delivery (COD) is available in the following areas:</p>
                                            <ul>
                                                <li><strong>All areas in Addis Ababa</strong></li>
                                                <li><strong>Major cities:</strong> Adama, Bahir Dar, Gondar, Dessie, Hawassa, Jimma, Dire Dawa, Mekelle</li>
                                                <li><strong>COD Limits:</strong> Maximum ETB 50,000 per order for COD</li>
                                                <li><strong>COD Fee:</strong> No additional fee for COD orders</li>
                                            </ul>
                                            <p><strong>Important COD Notes:</strong></p>
                                            <ul>
                                                <li>You must have exact change or small bills ready</li>
                                                <li>Our delivery agents carry limited change</li>
                                                <li>You can inspect the package before paying</li>
                                                <li>COD is not available for international orders</li>
                                                <li>Some high-value items may require prepayment</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#payment3">
                                            Are prices inclusive of VAT?
                                        </button>
                                    </h2>
                                    <div id="payment3" class="accordion-collapse collapse" data-bs-parent="#paymentsAccordion">
                                        <div class="accordion-body">
                                            <p>Yes, all prices displayed on our website are <strong>inclusive of 15% VAT</strong> as required by Ethiopian law.</p>
                                            <p><strong>Breakdown of charges at checkout:</strong></p>
                                            <ul>
                                                <li><strong>Subtotal:</strong> Sum of all item prices</li>
                                                <li><strong>Shipping:</strong> Calculated based on your location</li>
                                                <li><strong>VAT (15%):</strong> Already included in item prices, shown for transparency</li>
                                                <li><strong>Total:</strong> Final amount to pay</li>
                                            </ul>
                                            <p><strong>Tax Invoice:</strong> You will receive a tax invoice with your order that shows the VAT breakdown. This can be used for business expense claims if applicable.</p>
                                            <p><strong>For Businesses:</strong> If you need a VAT registration number on your invoice for tax purposes, please contact our business sales team at business@sneakermart.com.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Returns FAQ -->
            <div class="row mb-5" id="returns">
                <div class="col-12">
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-danger text-white">
                            <h3 class="mb-0"><i class="fas fa-exchange-alt me-2"></i>Returns & Refunds</h3>
                        </div>
                        <div class="card-body">
                            <div class="accordion" id="returnsAccordion">
                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#return1">
                                            What is your return policy?
                                        </button>
                                    </h2>
                                    <div id="return1" class="accordion-collapse collapse show" data-bs-parent="#returnsAccordion">
                                        <div class="accordion-body">
                                            <p>We offer a 14-day return policy for most items. Here are the key points:</p>
                                            <ul>
                                                <li><strong>Return Window:</strong> 14 days from delivery date</li>
                                                <li><strong>Condition:</strong> Items must be unworn, in original packaging with all tags</li>
                                                <li><strong>Return Process:</strong> Initiate return from "My Orders" in your account</li>
                                                <li><strong>Refund Method:</strong> Refund to original payment method or store credit</li>
                                                <li><strong>Return Shipping:</strong> Free for defective items, ETB 150-250 for other returns</li>
                                            </ul>
                                            <p><strong>Exceptions:</strong> Customized items, clearance items, and gift cards cannot be returned.</p>
                                            <p><strong>Defective Items:</strong> For defective products, you have 30 days to return with free pickup.</p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#return2">
                                            How long do refunds take?
                                        </button>
                                    </h2>
                                    <div id="return2" class="accordion-collapse collapse" data-bs-parent="#returnsAccordion">
                                        <div class="accordion-body">
                                            <p>Refund processing times vary by payment method:</p>
                                            <table class="table table-bordered">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>Payment Method</th>
                                                        <th>Processing Time</th>
                                                        <th>Notes</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr>
                                                        <td>Mobile Money (TeleBirr/CBE)</td>
                                                        <td>24-48 hours</td>
                                                        <td>Fastest method</td>
                                                    </tr>
                                                    <tr>
                                                        <td>Credit/Debit Cards</td>
                                                        <td>5-10 business days</td>
                                                        <td>Bank processing time</td>
                                                    </tr>
                                                    <tr>
                                                        <td>Bank Transfer</td>
                                                        <td>3-5 business days</td>
                                                        <td>For COD returns</td>
                                                    </tr>
                                                    <tr>
                                                        <td>Store Credit</td>
                                                        <td>Instant</td>
                                                        <td>Added to your account immediately</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                            <p><strong>Important:</strong> The timeline starts after we receive and inspect your return (2-3 business days). You'll receive email notifications at each step.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Account FAQ -->
            <div class="row mb-5" id="account">
                <div class="col-12">
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-info text-white">
                            <h3 class="mb-0"><i class="fas fa-user me-2"></i>Account & Security</h3>
                        </div>
                        <div class="card-body">
                            <div class="accordion" id="accountAccordion">
                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#account1">
                                            How do I create an account?
                                        </button>
                                    </h2>
                                    <div id="account1" class="accordion-collapse collapse show" data-bs-parent="#accountAccordion">
                                        <div class="accordion-body">
                                            <p>Creating an account is easy and free:</p>
                                            <ol>
                                                <li>Click "Sign Up" at the top right of any page</li>
                                                <li>Choose account type (Buyer or Vendor)</li>
                                                <li>Fill in your details (name, email, phone, password)</li>
                                                <li>Verify your email (click link in confirmation email)</li>
                                                <li>Complete your profile (optional)</li>
                                            </ol>
                                            <p><strong>Benefits of having an account:</strong></p>
                                            <ul>
                                                <li>Faster checkout (save addresses & payment methods)</li>
                                                <li>Track order history and status</li>
                                                <li>Save items to wishlist</li>
                                                <li>Receive exclusive offers and discounts</li>
                                                <li>Easier returns and exchanges</li>
                                                <li>Manage multiple addresses</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#account2">
                                            I forgot my password. What should I do?
                                        </button>
                                    </h2>
                                    <div id="account2" class="accordion-collapse collapse" data-bs-parent="#accountAccordion">
                                        <div class="accordion-body">
                                            <p>If you forgot your password:</p>
                                            <ol>
                                                <li>Go to the login page</li>
                                                <li>Click "Forgot Password?"</li>
                                                <li>Enter the email address associated with your account</li>
                                                <li>Check your email for a password reset link</li>
                                                <li>Click the link and create a new password</li>
                                                <li>Log in with your new password</li>
                                            </ol>
                                            <p><strong>Important Notes:</strong></p>
                                            <ul>
                                                <li>The reset link expires after 1 hour for security</li>
                                                <li>Check your spam folder if you don't see the email</li>
                                                <li>If you don't receive the email, contact support</li>
                                                <li>Never share your password with anyone</li>
                                                <li>Use a strong password with letters, numbers, and symbols</li>
                                            </ul>
                                            <p><strong>Still having trouble?</strong> Contact our support team at support@sneakermart.com or call +251-911-123-456.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Products FAQ -->
            <div class="row mb-5" id="products">
                <div class="col-12">
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-secondary text-white">
                            <h3 class="mb-0"><i class="fas fa-shoe-prints me-2"></i>Products & Sizing</h3>
                        </div>
                        <div class="card-body">
                            <div class="accordion" id="productsAccordion">
                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#product1">
                                            How do I find the right size?
                                        </button>
                                    </h2>
                                    <div id="product1" class="accordion-collapse collapse show" data-bs-parent="#productsAccordion">
                                        <div class="accordion-body">
                                            <p>Finding the right size is important for comfort. Here's how:</p>
                                            <ol>
                                                <li><strong>Check the Size Guide:</strong> Each product page has a size guide specific to that brand</li>
                                                <li><strong>Measure Your Foot:</strong> Use our printable size chart to measure your foot length</li>
                                                <li><strong>Consider Width:</strong> Some brands run narrow or wide - check product descriptions</li>
                                                <li><strong>Read Reviews:</strong> Other customers often mention if items run large or small</li>
                                                <li><strong>Use Our Fit Finder:</strong> Answer a few questions about your usual size and get recommendations</li>
                                            </ol>
                                            <p><strong>Still unsure?</strong> Consider ordering two sizes and returning the one that doesn't fit. We offer free returns for size exchanges within 14 days.</p>
                                            <p><strong>Conversion Chart:</strong></p>
                                            <div class="table-responsive">
                                                <table class="table table-bordered">
                                                    <thead>
                                                        <tr>
                                                            <th>US Size</th>
                                                            <th>UK Size</th>
                                                            <th>EU Size</th>
                                                            <th>CM Length</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr><td>7</td><td>6</td><td>40</td><td>25.0 cm</td></tr>
                                                        <tr><td>8</td><td>7</td><td>41</td><td>25.7 cm</td></tr>
                                                        <tr><td>9</td><td>8</td><td>42</td><td>26.4 cm</td></tr>
                                                        <tr><td>10</td><td>9</td><td>43</td><td>27.1 cm</td></tr>
                                                        <tr><td>11</td><td>10</td><td>44</td><td>27.8 cm</td></tr>
                                                        <tr><td>12</td><td>11</td><td>45</td><td>28.5 cm</td></tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#product2">
                                            Are your products authentic?
                                        </button>
                                    </h2>
                                    <div id="product2" class="accordion-collapse collapse" data-bs-parent="#productsAccordion">
                                        <div class="accordion-body">
                                            <p><strong>Yes, 100% authentic!</strong> We only sell genuine, brand-new products from authorized distributors and brands.</p>
                                            <p><strong>How we ensure authenticity:</strong></p>
                                            <ul>
                                                <li>Direct partnerships with brands and authorized distributors</li>
                                                <li>Official import documentation for all products</li>
                                                <li>Quality checks upon arrival at our warehouse</li>
                                                <li>Serial number verification for high-value items</li>
                                                <li>Anti-counterfeiting measures in packaging and labeling</li>
                                            </ul>
                                            <p><strong>Authenticity Guarantee:</strong> If you receive any product that you believe is not authentic, contact us immediately. We will investigate and, if confirmed, provide a full refund plus compensation.</p>
                                            <p><strong>How to spot fakes:</strong> Genuine products have consistent branding, high-quality materials, proper packaging with barcodes, and serial numbers that can be verified with the brand.</p>
                                            <p><strong>Report Suspicious Products:</strong> If you see potentially counterfeit products elsewhere, report them to brands@report.sneakermart.com.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Still Have Questions -->
            <div class="row">
                <div class="col-12">
                    <div class="card border-0 bg-light">
                        <div class="card-body text-center py-5">
                            <h2 class="mb-4">Still Have Questions?</h2>
                            <p class="lead mb-4">Can't find what you're looking for? Our support team is ready to help!</p>
                            <div class="row justify-content-center">
                                <div class="col-lg-3 col-md-6 mb-4">
                                    <div class="p-4 bg-white rounded shadow-sm">
                                        <i class="fas fa-headset fa-3x text-primary mb-3"></i>
                                        <h5>Call Us</h5>
                                        <p class="mb-2">+251-911-123-456</p>
                                        <small class="text-muted">7 days a week, 8AM-8PM</small>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-md-6 mb-4">
                                    <div class="p-4 bg-white rounded shadow-sm">
                                        <i class="fas fa-envelope fa-3x text-primary mb-3"></i>
                                        <h5>Email Us</h5>
                                        <p class="mb-2">support@sneakermart.com</p>
                                        <small class="text-muted">Response within 24 hours</small>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-md-6 mb-4">
                                    <div class="p-4 bg-white rounded shadow-sm">
                                        <i class="fas fa-comments fa-3x text-primary mb-3"></i>
                                        <h5>Live Chat</h5>
                                        <p class="mb-2">Click the chat icon</p>
                                        <small class="text-muted">Mon-Fri, 9AM-6PM</small>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-md-6 mb-4">
                                    <div class="p-4 bg-white rounded shadow-sm">
                                        <i class="fas fa-store fa-3x text-primary mb-3"></i>
                                        <h5>Visit Store</h5>
                                        <p class="mb-2">Bole, Mexico, Piazza</p>
                                        <small class="text-muted">Expert advice in person</small>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-4">
                                <a href="contact.php" class="btn btn-primary btn-lg me-3">
                                    <i class="fas fa-envelope me-2"></i>Contact Form
                                </a>
                                <a href="../buyer/orders.php" class="btn btn-outline-primary btn-lg">
                                    <i class="fas fa-shopping-cart me-2"></i>My Orders
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
    
    <!-- Footer -->
    <?php include 'includes/footer.php'; ?>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- FAQ Search Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('faqSearch');
            const searchButton = document.getElementById('searchButton');
            const accordionButtons = document.querySelectorAll('.accordion-button');
            
            // Search functionality
            function searchFAQ() {
                const searchTerm = searchInput.value.toLowerCase().trim();
                
                if (searchTerm === '') {
                    // Show all questions if search is empty
                    accordionButtons.forEach(button => {
                        button.closest('.accordion-item').style.display = '';
                    });
                    return;
                }
                
                let foundCount = 0;
                
                accordionButtons.forEach(button => {
                    const questionText = button.textContent.toLowerCase();
                    const answerText = button.nextElementSibling.textContent.toLowerCase();
                    const accordionItem = button.closest('.accordion-item');
                    
                    if (questionText.includes(searchTerm) || answerText.includes(searchTerm)) {
                        accordionItem.style.display = '';
                        foundCount++;
                        
                        // Auto-expand found items
                        if (!button.classList.contains('collapsed')) {
                            const bsCollapse = new bootstrap.Collapse(button.nextElementSibling);
                            bsCollapse.show();
                        }
                    } else {
                        accordionItem.style.display = 'none';
                    }
                });
                
                // Show message if no results
                if (foundCount === 0) {
                    const noResults = document.createElement('div');
                    noResults.className = 'alert alert-info mt-3';
                    noResults.innerHTML = `<i class="fas fa-info-circle me-2"></i>No results found for "${searchTerm}". Try different keywords or contact our support team.`;
                    
                    // Remove previous no-results message
                    const existingAlert = document.querySelector('.alert-info');
                    if (existingAlert) {
                        existingAlert.remove();
                    }
                    
                    searchInput.parentElement.parentElement.after(noResults);
                } else {
                    // Remove no-results message if it exists
                    const existingAlert = document.querySelector('.alert-info');
                    if (existingAlert) {
                        existingAlert.remove();
                    }
                }
            }
            
            // Search on button click
            searchButton.addEventListener('click', searchFAQ);
            
            // Search on Enter key
            searchInput.addEventListener('keyup', function(event) {
                if (event.key === 'Enter') {
                    searchFAQ();
                }
            });
            
            // Category navigation
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    const targetId = this.getAttribute('href');
                    const targetElement = document.querySelector(targetId);
                    
                    if (targetElement) {
                        // Smooth scroll to section
                        window.scrollTo({
                            top: targetElement.offsetTop - 100,
                            behavior: 'smooth'
                        });
                        
                        // Highlight the clicked category button
                        document.querySelectorAll('a[href^="#"]').forEach(btn => {
                            btn.classList.remove('btn-primary');
                            btn.classList.add('btn-outline-primary');
                        });
                        
                        this.classList.remove('btn-outline-primary');
                        this.classList.add('btn-primary');
                    }
                });
            });
            
            // Auto-expand first question in each category
            const firstQuestions = document.querySelectorAll('.accordion:not(.show) .accordion-button');
            if (firstQuestions.length > 0) {
                firstQuestions.forEach((button, index) => {
                    if (index % 3 === 0) { // Expand every 3rd (first in each accordion)
                        const bsCollapse = new bootstrap.Collapse(button.nextElementSibling);
                        bsCollapse.show();
                    }
                });
            }
        });
    </script>
</body>
</html>