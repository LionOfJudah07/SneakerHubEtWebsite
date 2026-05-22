<?php
require_once '../config.php';

$page_title = 'Terms & Conditions - ' . SITE_NAME;

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
                <li class="breadcrumb-item active">Terms & Conditions</li>
            </ol>
        </div>
    </nav>

    <!-- Terms Content -->
    <div class="container py-5">
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h2 class="mb-0"><i class="fas fa-file-contract me-2"></i>Terms & Conditions</h2>
                        <p class="mb-0 mt-2">Last Updated: <?php echo date('F j, Y'); ?></p>
                    </div>
                    <div class="card-body">
                        <!-- Introduction -->
                        <div class="mb-5">
                            <h3 class="mb-3">1. Introduction</h3>
                            <p>Welcome to SneakerHub Ethiopia ("we," "our," or "us"). These Terms and Conditions govern your use of our website and services. By accessing or using SneakerHub Ethiopia, you agree to be bound by these terms.</p>
                            <p>SneakerHub Ethiopia is an online marketplace connecting buyers and sellers of authentic sneakers in Ethiopia. Our platform facilitates transactions between users while ensuring authenticity and secure payments.</p>
                        </div>
                        
                        <!-- User Accounts -->
                        <div class="mb-5">
                            <h3 class="mb-3">2. User Accounts</h3>
                            <h5>2.1 Account Creation</h5>
                            <p>To use certain features of our platform, you must create an account. You agree to:</p>
                            <ul>
                                <li>Provide accurate and complete information</li>
                                <li>Maintain the security of your password</li>
                                <li>Accept responsibility for all activities under your account</li>
                                <li>Notify us immediately of any unauthorized use</li>
                            </ul>
                            
                            <h5>2.2 Account Types</h5>
                            <p>We offer three types of accounts:</p>
                            <ul>
                                <li><strong>Buyer Accounts:</strong> For purchasing products</li>
                                <li><strong>Vendor Accounts:</strong> For selling products (requires approval)</li>
                                <li><strong>Admin Accounts:</strong> For platform management</li>
                            </ul>
                        </div>
                        
                        <!-- Buying and Selling -->
                        <div class="mb-5">
                            <h3 class="mb-3">3. Buying and Selling</h3>
                            <h5>3.1 Product Listings</h5>
                            <p>Vendors are responsible for:</p>
                            <ul>
                                <li>Providing accurate product descriptions</li>
                                <li>Uploading clear, authentic product images</li>
                                <li>Setting fair and competitive prices</li>
                                <li>Maintaining adequate stock levels</li>
                                <li>Ensuring product authenticity</li>
                            </ul>
                            
                            <h5>3.2 Purchases</h5>
                            <p>Buyers agree to:</p>
                            <ul>
                                <li>Provide accurate shipping information</li>
                                <li>Make payments through approved methods</li>
                                <li>Inspect products upon delivery</li>
                                <li>Report issues within 24 hours of delivery</li>
                            </ul>
                            
                            <h5>3.3 Pricing and Payments</h5>
                            <p>All prices are in Ethiopian Birr (ETB) and include VAT where applicable. We accept the following payment methods:</p>
                            <ul>
                                <li>TeleBirr</li>
                                <li>CBE Birr</li>
                                <li>Cash on Delivery</li>
                                <li>Bank Transfer</li>
                            </ul>
                        </div>
                        
                        <!-- Shipping and Delivery -->
                        <div class="mb-5">
                            <h3 class="mb-3">4. Shipping and Delivery</h3>
                            <h5>4.1 Delivery Times</h5>
                            <p>Estimated delivery times vary by region:</p>
                            <ul>
                                <li>Addis Ababa: 1-2 business days</li>
                                <li>Major Cities: 3-5 business days</li>
                                <li>Other Regions: 5-10 business days</li>
                            </ul>
                            
                            <h5>4.2 Shipping Costs</h5>
                            <p>Shipping costs are calculated based on:</p>
                            <ul>
                                <li>Delivery location</li>
                                <li>Package weight and size</li>
                                <li>Shipping method selected</li>
                            </ul>
                            
                            <h5>4.3 Delivery Issues</h5>
                            <p>Report any delivery issues within 24 hours. We will investigate and work with our delivery partners to resolve problems.</p>
                        </div>
                        
                        <!-- Returns and Refunds -->
                        <div class="mb-5">
                            <h3 class="mb-3">5. Returns and Refunds</h3>
                            <h5>5.1 Return Policy</h5>
                            <p>We accept returns within 14 days of delivery for:</p>
                            <ul>
                                <li>Incorrect items received</li>
                                <li>Defective or damaged products</li>
                                <li>Significant difference from description</li>
                                <li>Non-authentic products</li>
                            </ul>
                            <p>Items must be:</p>
                            <ul>
                                <li>In original condition</li>
                                <li>Unworn and unused</li>
                                <li>In original packaging with all accessories</li>
                            </ul>
                            
                            <h5>5.2 Refund Process</h5>
                            <p>Refunds are processed within 7-10 business days after receiving returned items. Refunds are issued to the original payment method.</p>
                        </div>
                        
                        <!-- Authenticity Guarantee -->
                        <div class="mb-5">
                            <h3 class="mb-3">6. Authenticity Guarantee</h3>
                            <p>We take product authenticity seriously. All sneakers sold on our platform undergo verification. If you receive a non-authentic product:</p>
                            <ul>
                                <li>Report within 24 hours of delivery</li>
                                <li>Provide supporting evidence</li>
                                <li>We will investigate immediately</li>
                                <li>Full refund if authenticity cannot be verified</li>
                            </ul>
                        </div>
                        
                        <!-- Prohibited Activities -->
                        <div class="mb-5">
                            <h3 class="mb-3">7. Prohibited Activities</h3>
                            <p>Users are prohibited from:</p>
                            <ul>
                                <li>Selling counterfeit products</li>
                                <li>Providing false information</li>
                                <li>Manipulating prices or reviews</li>
                                <li>Harassing other users</li>
                                <li>Violating intellectual property rights</li>
                                <li>Using automated systems to access our platform</li>
                                <li>Attempting to interfere with platform security</li>
                            </ul>
                        </div>
                        
                        <!-- Intellectual Property -->
                        <div class="mb-5">
                            <h3 class="mb-3">8. Intellectual Property</h3>
                            <p>All content on SneakerHub Ethiopia, including logos, text, graphics, and software, is our property or licensed to us. You may not:</p>
                            <ul>
                                <li>Copy, modify, or distribute our content</li>
                                <li>Use our trademarks without permission</li>
                                <li>Reverse engineer our platform</li>
                            </ul>
                        </div>
                        
                        <!-- Limitation of Liability -->
                        <div class="mb-5">
                            <h3 class="mb-3">9. Limitation of Liability</h3>
                            <p>SneakerHub Ethiopia is not liable for:</p>
                            <ul>
                                <li>Indirect, incidental, or consequential damages</li>
                                <li>Loss of data or profits</li>
                                <li>Issues beyond our reasonable control</li>
                                <li>User disputes with other users</li>
                            </ul>
                            <p>Our maximum liability is limited to the amount you paid for the product in question.</p>
                        </div>
                        
                        <!-- Changes to Terms -->
                        <div class="mb-5">
                            <h3 class="mb-3">10. Changes to Terms</h3>
                            <p>We may update these Terms & Conditions periodically. We will notify users of significant changes via email or platform notifications. Continued use after changes constitutes acceptance.</p>
                        </div>
                        
                        <!-- Governing Law -->
                        <div class="mb-5">
                            <h3 class="mb-3">11. Governing Law</h3>
                            <p>These Terms & Conditions are governed by Ethiopian law. Any disputes shall be resolved in the courts of Addis Ababa, Ethiopia.</p>
                        </div>
                        
                        <!-- Contact Information -->
                        <div class="mb-5">
                            <h3 class="mb-3">12. Contact Information</h3>
                            <p>For questions about these Terms & Conditions, contact us:</p>
                            <ul>
                                <li>Email: legal@sneakerhub.et</li>
                                <li>Phone: +251 911 123 456</li>
                                <li>Address: Addis Ababa, Ethiopia</li>
                            </ul>
                        </div>
                        
                        <!-- Acceptance -->
                        <div class="alert alert-info">
                            <h5><i class="fas fa-check-circle me-2"></i>Acceptance of Terms</h5>
                            <p class="mb-0">By using SneakerHub Ethiopia, you acknowledge that you have read, understood, and agree to be bound by these Terms & Conditions.</p>
                        </div>
                    </div>
                    
                    <div class="card-footer bg-light">
                        <div class="d-flex justify-content-between align-items-center">
                            <small class="text-muted">Document ID: TOS-2024-001</small>
                            <a href="#top" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-arrow-up me-2"></i>Back to Top
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Related Documents -->
                <div class="row mt-4">
                    <div class="col-md-4 mb-3">
                        <div class="card h-100 border-primary">
                            <div class="card-body text-center">
                                <i class="fas fa-shield-alt fa-3x text-primary mb-3"></i>
                                <h5>Privacy Policy</h5>
                                <p class="small text-muted">Learn how we protect your data</p>
                                <a href="privacy.php" class="btn btn-outline-primary btn-sm">View Policy</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card h-100 border-success">
                            <div class="card-body text-center">
                                <i class="fas fa-undo-alt fa-3x text-success mb-3"></i>
                                <h5>Return Policy</h5>
                                <p class="small text-muted">Our product return guidelines</p>
                                <a href="returns.php" class="btn btn-outline-success btn-sm">View Policy</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card h-100 border-info">
                            <div class="card-body text-center">
                                <i class="fas fa-shipping-fast fa-3x text-info mb-3"></i>
                                <h5>Shipping Policy</h5>
                                <p class="small text-muted">Delivery information and timelines</p>
                                <a href="shipping.php" class="btn btn-outline-info btn-sm">View Policy</a>
                            </div>
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
</body>
</html>