<?php
require_once '../config.php';

$page_title = 'Privacy Policy - ' . SITE_NAME;

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
                <li class="breadcrumb-item active">Privacy Policy</li>
            </ol>
        </div>
    </nav>

    <!-- Privacy Content -->
    <div class="container py-5">
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h2 class="mb-0"><i class="fas fa-shield-alt me-2"></i>Privacy Policy</h2>
                        <p class="mb-0 mt-2">Last Updated: <?php echo date('F j, Y'); ?></p>
                    </div>
                    <div class="card-body">
                        <!-- Introduction -->
                        <div class="mb-5">
                            <h3 class="mb-3">1. Introduction</h3>
                            <p>Welcome to SneakerHub Ethiopia ("we," "our," or "us"). We are committed to protecting your privacy and personal information. This Privacy Policy explains how we collect, use, disclose, and safeguard your information when you use our platform.</p>
                            <p>By using SneakerHub Ethiopia, you consent to the data practices described in this policy. If you do not agree with our policies and practices, please do not use our services.</p>
                        </div>
                        
                        <!-- Information We Collect -->
                        <div class="mb-5">
                            <h3 class="mb-3">2. Information We Collect</h3>
                            
                            <h5>2.1 Personal Information</h5>
                            <p>When you create an account or use our services, we may collect:</p>
                            <ul>
                                <li><strong>Contact Information:</strong> Name, email address, phone number</li>
                                <li><strong>Account Information:</strong> Username, password, user type (buyer/vendor)</li>
                                <li><strong>Profile Information:</strong> Profile picture, biography (optional)</li>
                                <li><strong>Payment Information:</strong> Payment method details (processed securely)</li>
                                <li><strong>Address Information:</strong> Shipping and billing addresses</li>
                            </ul>
                            
                            <h5>2.2 Transaction Information</h5>
                            <p>When you make purchases or sales, we collect:</p>
                            <ul>
                                <li>Order details and history</li>
                                <li>Payment transaction information</li>
                                <li>Shipping and delivery information</li>
                                <li>Communication with other users</li>
                            </ul>
                            
                            <h5>2.3 Technical Information</h5>
                            <p>We automatically collect:</p>
                            <ul>
                                <li>IP address and device information</li>
                                <li>Browser type and version</li>
                                <li>Operating system</li>
                                <li>Pages visited and time spent</li>
                                <li>Referring website addresses</li>
                                <li>Cookies and tracking technologies</li>
                            </ul>
                        </div>
                        
                        <!-- How We Use Your Information -->
                        <div class="mb-5">
                            <h3 class="mb-3">3. How We Use Your Information</h3>
                            <p>We use your information for the following purposes:</p>
                            <ul>
                                <li><strong>To provide our services:</strong> Process transactions, facilitate communication between buyers and sellers</li>
                                <li><strong>To improve our platform:</strong> Analyze usage patterns, enhance user experience</li>
                                <li><strong>To communicate with you:</strong> Send order updates, respond to inquiries, provide customer support</li>
                                <li><strong>For security purposes:</strong> Detect and prevent fraud, unauthorized access, and illegal activities</li>
                                <li><strong>For legal compliance:</strong> Comply with applicable laws and regulations</li>
                                <li><strong>For marketing:</strong> Send promotional communications (with your consent)</li>
                            </ul>
                        </div>
                        
                        <!-- Information Sharing -->
                        <div class="mb-5">
                            <h3 class="mb-3">4. Information Sharing</h3>
                            <p>We may share your information in the following circumstances:</p>
                            
                            <h5>4.1 With Other Users</h5>
                            <ul>
                                <li>Buyers and sellers see necessary information to complete transactions</li>
                                <li>Vendor names and store information are visible to buyers</li>
                                <li>Buyer names and shipping information are visible to vendors</li>
                            </ul>
                            
                            <h5>4.2 With Service Providers</h5>
                            <p>We share information with trusted third-party service providers:</p>
                            <ul>
                                <li>Payment processors</li>
                                <li>Shipping and delivery partners</li>
                                <li>Cloud hosting providers</li>
                                <li>Customer support services</li>
                            </ul>
                            
                            <h5>4.3 For Legal Reasons</h5>
                            <p>We may disclose information when required by law:</p>
                            <ul>
                                <li>To comply with legal obligations</li>
                                <li>To protect our rights and property</li>
                                <li>To prevent fraud or security issues</li>
                                <li>To protect the safety of our users</li>
                            </ul>
                            
                            <h5>4.4 Business Transfers</h5>
                            <p>In the event of a merger, acquisition, or sale of assets, user information may be transferred as part of the transaction.</p>
                        </div>
                        
                        <!-- Data Security -->
                        <div class="mb-5">
                            <h3 class="mb-3">5. Data Security</h3>
                            <p>We implement appropriate security measures to protect your information:</p>
                            <ul>
                                <li>Encryption of sensitive data</li>
                                <li>Secure socket layer (SSL) technology</li>
                                <li>Regular security assessments</li>
                                <li>Access controls and authentication</li>
                                <li>Secure data storage practices</li>
                            </ul>
                            <p>While we strive to protect your information, no security system is completely impenetrable. We cannot guarantee absolute security.</p>
                        </div>
                        
                        <!-- Data Retention -->
                        <div class="mb-5">
                            <h3 class="mb-3">6. Data Retention</h3>
                            <p>We retain your information for as long as necessary:</p>
                            <ul>
                                <li>To provide services to you</li>
                                <li>To comply with legal obligations</li>
                                <li>To resolve disputes</li>
                                <li>To enforce our agreements</li>
                            </ul>
                            <p>When information is no longer needed, we securely delete or anonymize it.</p>
                        </div>
                        
                        <!-- Your Rights -->
                        <div class="mb-5">
                            <h3 class="mb-3">7. Your Rights</h3>
                            <p>Depending on your location, you may have the following rights:</p>
                            <ul>
                                <li><strong>Access:</strong> Request access to your personal information</li>
                                <li><strong>Correction:</strong> Request correction of inaccurate information</li>
                                <li><strong>Deletion:</strong> Request deletion of your information</li>
                                <li><strong>Restriction:</strong> Request restriction of processing</li>
                                <li><strong>Portability:</strong> Request transfer of your data</li>
                                <li><strong>Objection:</strong> Object to certain processing activities</li>
                            </ul>
                            <p>To exercise these rights, contact us at privacy@sneakerhub.et</p>
                        </div>
                        
                        <!-- Cookies and Tracking -->
                        <div class="mb-5">
                            <h3 class="mb-3">8. Cookies and Tracking</h3>
                            <p>We use cookies and similar tracking technologies:</p>
                            
                            <h5>8.1 Types of Cookies</h5>
                            <ul>
                                <li><strong>Essential Cookies:</strong> Necessary for platform functionality</li>
                                <li><strong>Performance Cookies:</strong> Help us understand how users interact with our platform</li>
                                <li><strong>Functional Cookies:</strong> Remember your preferences</li>
                                <li><strong>Marketing Cookies:</strong> Used for advertising purposes</li>
                            </ul>
                            
                            <h5>8.2 Managing Cookies</h5>
                            <p>You can control cookies through your browser settings. However, disabling certain cookies may affect platform functionality.</p>
                        </div>
                        
                        <!-- Children's Privacy -->
                        <div class="mb-5">
                            <h3 class="mb-3">9. Children's Privacy</h3>
                            <p>Our services are not intended for children under 13 years of age. We do not knowingly collect information from children under 13. If you believe we have collected information from a child under 13, please contact us immediately.</p>
                        </div>
                        
                        <!-- Third-Party Links -->
                        <div class="mb-5">
                            <h3 class="mb-3">10. Third-Party Links</h3>
                            <p>Our platform may contain links to third-party websites. This Privacy Policy applies only to SneakerHub Ethiopia. We are not responsible for the privacy practices of third-party websites. We encourage you to review their privacy policies.</p>
                        </div>
                        
                        <!-- Changes to Privacy Policy -->
                        <div class="mb-5">
                            <h3 class="mb-3">11. Changes to Privacy Policy</h3>
                            <p>We may update this Privacy Policy periodically. We will notify you of significant changes by posting the new policy on our platform and updating the "Last Updated" date. We encourage you to review this policy regularly.</p>
                        </div>
                        
                        <!-- Contact Information -->
                        <div class="mb-5">
                            <h3 class="mb-3">12. Contact Information</h3>
                            <p>If you have questions about this Privacy Policy, please contact us:</p>
                            <ul>
                                <li>Email: privacy@sneakerhub.et</li>
                                <li>Phone: +251 911 123 456</li>
                                <li>Address: Addis Ababa, Ethiopia</li>
                            </ul>
                        </div>
                        
                        <!-- Consent -->
                        <div class="alert alert-info">
                            <h5><i class="fas fa-check-circle me-2"></i>Your Consent</h5>
                            <p class="mb-0">By using SneakerHub Ethiopia, you consent to our collection, use, and sharing of your information as described in this Privacy Policy.</p>
                        </div>
                    </div>
                    
                    <div class="card-footer bg-light">
                        <div class="d-flex justify-content-between align-items-center">
                            <small class="text-muted">Document ID: PP-2024-001</small>
                            <a href="#top" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-arrow-up me-2"></i>Back to Top
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Cookie Settings -->
                <div class="card shadow-sm mt-4">
                    <div class="card-header bg-warning text-white">
                        <h4 class="mb-0"><i class="fas fa-cookie-bite me-2"></i>Cookie Preferences</h4>
                    </div>
                    <div class="card-body">
                        <p>Manage your cookie preferences for this website:</p>
                        
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="essential-cookies" checked disabled>
                            <label class="form-check-label" for="essential-cookies">
                                <strong>Essential Cookies</strong> (Required)
                            </label>
                            <p class="small text-muted mb-0">These cookies are necessary for the website to function properly.</p>
                        </div>
                        
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="performance-cookies" checked>
                            <label class="form-check-label" for="performance-cookies">
                                <strong>Performance Cookies</strong>
                            </label>
                            <p class="small text-muted mb-0">These cookies help us understand how visitors interact with our website.</p>
                        </div>
                        
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="functional-cookies" checked>
                            <label class="form-check-label" for="functional-cookies">
                                <strong>Functional Cookies</strong>
                            </label>
                            <p class="small text-muted mb-0">These cookies remember your preferences and settings.</p>
                        </div>
                        
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="marketing-cookies">
                            <label class="form-check-label" for="marketing-cookies">
                                <strong>Marketing Cookies</strong>
                            </label>
                            <p class="small text-muted mb-0">These cookies are used to deliver relevant advertisements.</p>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <button class="btn btn-outline-primary" onclick="saveCookiePreferences()">
                                <i class="fas fa-save me-2"></i>Save Preferences
                            </button>
                            <button class="btn btn-outline-secondary" onclick="acceptAllCookies()">
                                <i class="fas fa-check me-2"></i>Accept All
                            </button>
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
    
    <script>
        // Cookie preferences
        function saveCookiePreferences() {
            const performance = document.getElementById('performance-cookies').checked;
            const functional = document.getElementById('functional-cookies').checked;
            const marketing = document.getElementById('marketing-cookies').checked;
            
            // In a real implementation, you would save these preferences
            // For now, just show a success message
            alert('Cookie preferences saved successfully!');
        }
        
        function acceptAllCookies() {
            document.getElementById('performance-cookies').checked = true;
            document.getElementById('functional-cookies').checked = true;
            document.getElementById('marketing-cookies').checked = true;
            saveCookiePreferences();
        }
        
        // Load saved preferences (in a real implementation)
        document.addEventListener('DOMContentLoaded', function() {
            // Load saved cookie preferences here
            // For now, all are checked by default in the HTML
        });
    </script>
</body>
</html>