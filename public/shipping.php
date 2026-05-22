<?php
require_once '../config.php';

$page_title = 'Shipping Information - ' . SITE_NAME;

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
                    <h1 class="display-5 fw-bold mb-3">Shipping Information</h1>
                    <p class="lead text-muted">Fast and reliable delivery across Ethiopia</p>
                </div>
            </div>
            
            <!-- Shipping Options -->
            <div class="row mb-5">
                <div class="col-md-4 mb-4">
                    <div class="card h-100 border-0 shadow-sm text-center">
                        <div class="card-header bg-primary text-white py-4">
                            <i class="fas fa-truck fa-3x mb-3"></i>
                            <h4 class="card-title mb-0">Standard Shipping</h4>
                        </div>
                        <div class="card-body">
                            <h5 class="text-primary">ETB 150 - 350</h5>
                            <ul class="list-unstyled mt-3">
                                <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Addis Ababa: 1-2 days</li>
                                <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Major Cities: 3-5 days</li>
                                <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Other Regions: 5-10 days</li>
                                <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Order Tracking</li>
                                <li><i class="fas fa-check text-success me-2"></i>SMS Notifications</li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4 mb-4">
                    <div class="card h-100 border-0 shadow-sm text-center">
                        <div class="card-header bg-success text-white py-4">
                            <i class="fas fa-rocket fa-3x mb-3"></i>
                            <h4 class="card-title mb-0">Express Shipping</h4>
                        </div>
                        <div class="card-body">
                            <h5 class="text-success">ETB 300 - 600</h5>
                            <ul class="list-unstyled mt-3">
                                <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Addis Ababa: Same day</li>
                                <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Major Cities: 1-2 days</li>
                                <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Other Regions: 3-5 days</li>
                                <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Priority Processing</li>
                                <li><i class="fas fa-check text-success me-2"></i>24/7 Support</li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4 mb-4">
                    <div class="card h-100 border-0 shadow-sm text-center">
                        <div class="card-header bg-warning text-white py-4">
                            <i class="fas fa-store fa-3x mb-3"></i>
                            <h4 class="card-title mb-0">Store Pickup</h4>
                        </div>
                        <div class="card-body">
                            <h5 class="text-warning">FREE</h5>
                            <ul class="list-unstyled mt-3">
                                <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Ready in 2 hours</li>
                                <li class="mb-2"><i class="fas fa-check text-success me-2"></i>No shipping fees</li>
                                <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Try before you buy</li>
                                <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Expert advice</li>
                                <li><i class="fas fa-check text-success me-2"></i>Immediate availability</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Shipping Rates Table -->
            <div class="row mb-5">
                <div class="col-12">
                    <div class="card shadow-sm">
                        <div class="card-header bg-light">
                            <h4 class="mb-0"><i class="fas fa-map-marked-alt me-2"></i>Shipping Rates by Region</h4>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Region</th>
                                            <th>Major Cities</th>
                                            <th>Standard Shipping</th>
                                            <th>Express Shipping</th>
                                            <th>Delivery Time</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td><strong>Addis Ababa</strong></td>
                                            <td>Addis Ababa</td>
                                            <td>ETB 150</td>
                                            <td>ETB 300</td>
                                            <td>1-2 days (Same day for express)</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Oromia</strong></td>
                                            <td>Adama, Jimma, Bishoftu</td>
                                            <td>ETB 250</td>
                                            <td>ETB 450</td>
                                            <td>3-5 days (1-2 days for express)</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Amhara</strong></td>
                                            <td>Bahir Dar, Gondar, Dessie</td>
                                            <td>ETB 250</td>
                                            <td>ETB 450</td>
                                            <td>3-5 days (1-2 days for express)</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Southern Nations</strong></td>
                                            <td>Hawassa, Arba Minch, Wolaita</td>
                                            <td>ETB 300</td>
                                            <td>ETB 500</td>
                                            <td>5-7 days (3-4 days for express)</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Other Regions</strong></td>
                                            <td>All other cities</td>
                                            <td>ETB 350</td>
                                            <td>ETB 600</td>
                                            <td>7-10 days (5-7 days for express)</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- How It Works -->
            <div class="row mb-5">
                <div class="col-12">
                    <div class="card shadow-sm">
                        <div class="card-header bg-light">
                            <h4 class="mb-0"><i class="fas fa-shipping-fast me-2"></i>How Our Shipping Works</h4>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-lg-3 col-md-6 mb-4">
                                    <div class="text-center p-3">
                                        <div class="mb-3">
                                            <div class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                                                <i class="fas fa-shopping-cart fa-2x"></i>
                                            </div>
                                        </div>
                                        <h5>1. Place Order</h5>
                                        <p class="text-muted">Add items to cart and complete checkout with your address</p>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-md-6 mb-4">
                                    <div class="text-center p-3">
                                        <div class="mb-3">
                                            <div class="rounded-circle bg-success text-white d-inline-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                                                <i class="fas fa-box fa-2x"></i>
                                            </div>
                                        </div>
                                        <h5>2. Processing</h5>
                                        <p class="text-muted">We prepare your order and pack it securely</p>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-md-6 mb-4">
                                    <div class="text-center p-3">
                                        <div class="mb-3">
                                            <div class="rounded-circle bg-info text-white d-inline-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                                                <i class="fas fa-truck fa-2x"></i>
                                            </div>
                                        </div>
                                        <h5>3. Shipping</h5>
                                        <p class="text-muted">Order is dispatched with tracking information</p>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-md-6 mb-4">
                                    <div class="text-center p-3">
                                        <div class="mb-3">
                                            <div class="rounded-circle bg-warning text-white d-inline-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                                                <i class="fas fa-home fa-2x"></i>
                                            </div>
                                        </div>
                                        <h5>4. Delivery</h5>
                                        <p class="text-muted">Receive your order at your doorstep</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- FAQ -->
            <div class="row">
                <div class="col-12">
                    <div class="card shadow-sm">
                        <div class="card-header bg-light">
                            <h4 class="mb-0"><i class="fas fa-question-circle me-2"></i>Frequently Asked Questions</h4>
                        </div>
                        <div class="card-body">
                            <div class="accordion" id="shippingFAQ">
                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="headingOne">
                                        <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne">
                                            How do I track my order?
                                        </button>
                                    </h2>
                                    <div id="collapseOne" class="accordion-collapse collapse show" data-bs-parent="#shippingFAQ">
                                        <div class="accordion-body">
                                            Once your order is shipped, you'll receive a tracking number via SMS and email. You can also track your order from your account dashboard under "My Orders".
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="headingTwo">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo">
                                            Can I change my shipping address after ordering?
                                        </button>
                                    </h2>
                                    <div id="collapseTwo" class="accordion-collapse collapse" data-bs-parent="#shippingFAQ">
                                        <div class="accordion-body">
                                            You can change your shipping address within 1 hour of placing your order. After that, please contact our customer service at +251-911-123-456 or email support@sneakermart.com.
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="headingThree">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree">
                                            What happens if I'm not home during delivery?
                                        </button>
                                    </h2>
                                    <div id="collapseThree" class="accordion-collapse collapse" data-bs-parent="#shippingFAQ">
                                        <div class="accordion-body">
                                            Our delivery agent will attempt delivery twice. If unsuccessful, they will leave a notification with contact information. You can then schedule a redelivery or pick up from the nearest delivery hub.
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="headingFour">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFour">
                                            Do you ship internationally?
                                        </button>
                                    </h2>
                                    <div id="collapseFour" class="accordion-collapse collapse" data-bs-parent="#shippingFAQ">
                                        <div class="accordion-body">
                                            Currently, we only ship within Ethiopia. We're working on expanding to international shipping soon.
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="headingFive">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFive">
                                            What are your store pickup locations?
                                        </button>
                                    </h2>
                                    <div id="collapseFive" class="accordion-collapse collapse" data-bs-parent="#shippingFAQ">
                                        <div class="accordion-body">
                                            We have pickup locations in Addis Ababa (Bole, Mexico, Piazza) and soon in Adama and Bahir Dar. Select "Store Pickup" at checkout and choose your preferred location.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Contact Info -->
            <div class="row mt-5">
                <div class="col-12 text-center">
                    <div class="card border-0 bg-light">
                        <div class="card-body">
                            <h4 class="mb-3">Need Help with Shipping?</h4>
                            <p class="mb-4">Our customer service team is available 7 days a week</p>
                            <div class="row justify-content-center">
                                <div class="col-md-4 mb-3">
                                    <div class="p-3">
                                        <i class="fas fa-phone fa-2x text-primary mb-3"></i>
                                        <h5>Call Us</h5>
                                        <p class="mb-0">+251-911-123-456</p>
                                        <small class="text-muted">Mon-Sun, 8AM-8PM</small>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <div class="p-3">
                                        <i class="fas fa-envelope fa-2x text-primary mb-3"></i>
                                        <h5>Email Us</h5>
                                        <p class="mb-0">support@sneakermart.com</p>
                                        <small class="text-muted">Response within 24 hours</small>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <div class="p-3">
                                        <i class="fas fa-comments fa-2x text-primary mb-3"></i>
                                        <h5>Live Chat</h5>
                                        <p class="mb-0">Available on website</p>
                                        <small class="text-muted">Mon-Fri, 9AM-6PM</small>
                                    </div>
                                </div>
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
</body>
</html>