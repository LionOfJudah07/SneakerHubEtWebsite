<?php
require_once '../config.php';

$page_title = 'Returns & Refunds - ' . SITE_NAME;

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
                    <h1 class="display-5 fw-bold mb-3">Returns & Refunds Policy</h1>
                    <p class="lead text-muted">Hassle-free returns within 14 days</p>
                </div>
            </div>
            
            <!-- Return Process -->
            <div class="row mb-5">
                <div class="col-12">
                    <div class="card shadow-sm">
                        <div class="card-header bg-light">
                            <h4 class="mb-0"><i class="fas fa-exchange-alt me-2"></i>Easy Return Process</h4>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-lg-3 col-md-6 mb-4">
                                    <div class="text-center p-3">
                                        <div class="mb-3">
                                            <div class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                                                <span class="h4 mb-0">1</span>
                                            </div>
                                        </div>
                                        <h5>Initiate Return</h5>
                                        <p class="text-muted">Go to "My Orders" and select "Return Item" within 14 days of delivery</p>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-md-6 mb-4">
                                    <div class="text-center p-3">
                                        <div class="mb-3">
                                            <div class="rounded-circle bg-success text-white d-inline-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                                                <span class="h4 mb-0">2</span>
                                            </div>
                                        </div>
                                        <h5>Package Item</h5>
                                        <p class="text-muted">Pack the item in original packaging with all accessories</p>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-md-6 mb-4">
                                    <div class="text-center p-3">
                                        <div class="mb-3">
                                            <div class="rounded-circle bg-info text-white d-inline-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                                                <span class="h4 mb-0">3</span>
                                            </div>
                                        </div>
                                        <h5>Schedule Pickup</h5>
                                        <p class="text-muted">We'll schedule a pickup at your location (free for defects)</p>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-md-6 mb-4">
                                    <div class="text-center p-3">
                                        <div class="mb-3">
                                            <div class="rounded-circle bg-warning text-white d-inline-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                                                <span class="h4 mb-0">4</span>
                                            </div>
                                        </div>
                                        <h5>Get Refund</h5>
                                        <p class="text-muted">Receive refund within 5-7 business days after inspection</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Return Policy Details -->
            <div class="row mb-5">
                <div class="col-md-6 mb-4">
                    <div class="card h-100 shadow-sm">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0"><i class="fas fa-check-circle me-2"></i>What Can Be Returned</h5>
                        </div>
                        <div class="card-body">
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item border-0 px-0">
                                    <i class="fas fa-check text-success me-2"></i>
                                    <strong>Defective products</strong> - Within 30 days
                                </li>
                                <li class="list-group-item border-0 px-0">
                                    <i class="fas fa-check text-success me-2"></i>
                                    <strong>Wrong size/fit</strong> - Within 14 days
                                </li>
                                <li class="list-group-item border-0 px-0">
                                    <i class="fas fa-check text-success me-2"></i>
                                    <strong>Wrong item received</strong> - Within 7 days
                                </li>
                                <li class="list-group-item border-0 px-0">
                                    <i class="fas fa-check text-success me-2"></i>
                                    <strong>Unopened items</strong> - Within 14 days
                                </li>
                                <li class="list-group-item border-0 px-0">
                                    <i class="fas fa-check text-success me-2"></i>
                                    <strong>Damaged during shipping</strong> - Report within 48 hours
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6 mb-4">
                    <div class="card h-100 shadow-sm">
                        <div class="card-header bg-danger text-white">
                            <h5 class="mb-0"><i class="fas fa-times-circle me-2"></i>What Cannot Be Returned</h5>
                        </div>
                        <div class="card-body">
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item border-0 px-0">
                                    <i class="fas fa-times text-danger me-2"></i>
                                    <strong>Used or worn items</strong> (except for defects)
                                </li>
                                <li class="list-group-item border-0 px-0">
                                    <i class="fas fa-times text-danger me-2"></i>
                                    <strong>Customized/personalized items</strong>
                                </li>
                                <li class="list-group-item border-0 px-0">
                                    <i class="fas fa-times text-danger me-2"></i>
                                    <strong>Items without original packaging</strong>
                                </li>
                                <li class="list-group-item border-0 px-0">
                                    <i class="fas fa-times text-danger me-2"></i>
                                    <strong>Discounted/clearance items</strong> (marked "Final Sale")
                                </li>
                                <li class="list-group-item border-0 px-0">
                                    <i class="fas fa-times text-danger me-2"></i>
                                    <strong>Gift cards</strong>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Refund Policy -->
            <div class="row mb-5">
                <div class="col-12">
                    <div class="card shadow-sm">
                        <div class="card-header bg-primary text-white">
                            <h4 class="mb-0"><i class="fas fa-money-bill-wave me-2"></i>Refund Policy</h4>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-4">
                                    <div class="p-4 border rounded h-100">
                                        <h5 class="text-primary"><i class="fas fa-sync-alt me-2"></i>Refund Methods</h5>
                                        <ul class="mt-3">
                                            <li class="mb-2">
                                                <strong>Original Payment Method</strong> - Refunded to the card or mobile money used for purchase
                                            </li>
                                            <li class="mb-2">
                                                <strong>Store Credit</strong> - Instant credit to your Sneaker Mart account
                                            </li>
                                            <li class="mb-2">
                                                <strong>Bank Transfer</strong> - For cash on delivery orders
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                                
                                <div class="col-md-6 mb-4">
                                    <div class="p-4 border rounded h-100">
                                        <h5 class="text-primary"><i class="fas fa-clock me-2"></i>Refund Timelines</h5>
                                        <ul class="mt-3">
                                            <li class="mb-2">
                                                <strong>Credit/Debit Cards</strong> - 5-10 business days
                                            </li>
                                            <li class="mb-2">
                                                <strong>Mobile Money (TeleBirr/CBE)</strong> - 24-48 hours
                                            </li>
                                            <li class="mb-2">
                                                <strong>Bank Transfer</strong> - 3-5 business days
                                            </li>
                                            <li class="mb-2">
                                                <strong>Store Credit</strong> - Instant
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="alert alert-info mt-3">
                                <h6><i class="fas fa-info-circle me-2"></i>Important Note</h6>
                                <p class="mb-0">Shipping fees are non-refundable unless the return is due to our error (wrong item, defective product, etc.). Return shipping fees may apply for size exchanges or change of mind returns.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Size Exchange -->
            <div class="row mb-5">
                <div class="col-12">
                    <div class="card shadow-sm">
                        <div class="card-header bg-warning text-white">
                            <h4 class="mb-0"><i class="fas fa-shoe-prints me-2"></i>Size Exchange</h4>
                        </div>
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-md-8">
                                    <h5>Need a different size?</h5>
                                    <p>We offer free size exchange within 14 days of delivery if the same product is available in your preferred size.</p>
                                    <ul>
                                        <li>Free pickup for exchange items</li>
                                        <li>No additional shipping charges</li>
                                        <li>Quick processing (2-3 business days)</li>
                                        <li>Available for all footwear categories</li>
                                    </ul>
                                </div>
                                <div class="col-md-4 text-center">
                                    <div class="bg-light p-4 rounded">
                                        <i class="fas fa-exchange-alt fa-4x text-warning mb-3"></i>
                                        <h5>Easy Exchange</h5>
                                        <p class="text-muted">From your account dashboard</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- FAQ -->
            <div class="row mb-5">
                <div class="col-12">
                    <div class="card shadow-sm">
                        <div class="card-header bg-light">
                            <h4 class="mb-0"><i class="fas fa-question-circle me-2"></i>Returns FAQ</h4>
                        </div>
                        <div class="card-body">
                            <div class="accordion" id="returnsFAQ">
                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="faqOne">
                                        <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFAQ1">
                                            How long do I have to return an item?
                                        </button>
                                    </h2>
                                    <div id="collapseFAQ1" class="accordion-collapse collapse show" data-bs-parent="#returnsFAQ">
                                        <div class="accordion-body">
                                            You have 14 days from the date of delivery to initiate a return for most items. For defective products, you have 30 days. The item must be in its original condition with all tags and packaging.
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="faqTwo">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFAQ2">
                                            Do I need to pay for return shipping?
                                        </button>
                                    </h2>
                                    <div id="collapseFAQ2" class="accordion-collapse collapse" data-bs-parent="#returnsFAQ">
                                        <div class="accordion-body">
                                            Return shipping is free for defective items, wrong items received, or our errors. For size exchanges or change of mind returns, a return shipping fee of ETB 150 applies for Addis Ababa and ETB 250 for other regions.
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="faqThree">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFAQ3">
                                            How long does the refund process take?
                                        </button>
                                    </h2>
                                    <div id="collapseFAQ3" class="accordion-collapse collapse" data-bs-parent="#returnsFAQ">
                                        <div class="accordion-body">
                                            Once we receive your return, it takes 2-3 business days for inspection. After approval, refunds are processed within 5-7 business days for cards, 24-48 hours for mobile money, and instantly for store credit.
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="faqFour">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFAQ4">
                                            Can I exchange an item instead of returning?
                                        </button>
                                    </h2>
                                    <div id="collapseFAQ4" class="accordion-collapse collapse" data-bs-parent="#returnsFAQ">
                                        <div class="accordion-body">
                                            Yes! We offer free size exchanges within 14 days. Go to "My Orders" and select "Exchange" instead of "Return". If the desired size is available, we'll process the exchange immediately.
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="faqFive">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFAQ5">
                                            What if my item arrives damaged?
                                        </button>
                                    </h2>
                                    <div id="collapseFAQ5" class="accordion-collapse collapse" data-bs-parent="#returnsFAQ">
                                        <div class="accordion-body">
                                            Please contact us within 48 hours of delivery with photos of the damaged item and packaging. We'll arrange a free pickup and send a replacement immediately or issue a full refund.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Contact & Support -->
            <div class="row">
                <div class="col-12">
                    <div class="card border-0 bg-light">
                        <div class="card-body text-center py-5">
                            <h2 class="mb-4">Need Help with a Return?</h2>
                            <p class="lead mb-4">Our returns team is here to help you 7 days a week</p>
                            <div class="row justify-content-center">
                                <div class="col-lg-4 col-md-6 mb-4">
                                    <div class="p-4 bg-white rounded shadow-sm">
                                        <i class="fas fa-headset fa-3x text-primary mb-3"></i>
                                        <h4>Customer Support</h4>
                                        <p class="text-muted">+251-911-123-456</p>
                                        <small>Mon-Sun, 8AM-8PM</small>
                                    </div>
                                </div>
                                <div class="col-lg-4 col-md-6 mb-4">
                                    <div class="p-4 bg-white rounded shadow-sm">
                                        <i class="fas fa-envelope-open-text fa-3x text-primary mb-3"></i>
                                        <h4>Email Support</h4>
                                        <p class="text-muted">returns@sneakermart.com</p>
                                        <small>Response within 12 hours</small>
                                    </div>
                                </div>
                                <div class="col-lg-4 col-md-6 mb-4">
                                    <div class="p-4 bg-white rounded shadow-sm">
                                        <i class="fas fa-user-circle fa-3x text-primary mb-3"></i>
                                        <h4>Account Dashboard</h4>
                                        <p class="text-muted">Manage returns online</p>
                                        <small>24/7 self-service</small>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-4">
                                <a href="../buyer/orders.php" class="btn btn-primary btn-lg me-3">
                                    <i class="fas fa-clipboard-list me-2"></i>View My Orders
                                </a>
                                <a href="contact.php" class="btn btn-outline-primary btn-lg">
                                    <i class="fas fa-envelope me-2"></i>Contact Us
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
</body>
</html>