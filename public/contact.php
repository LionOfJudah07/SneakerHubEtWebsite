<?php
require_once '../config.php';

$page_title = 'Contact Us - ' . SITE_NAME;

// Handle contact form submission
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize_input($_POST['name']);
    $email = sanitize_input($_POST['email']);
    $phone = sanitize_input($_POST['phone']);
    $subject = sanitize_input($_POST['subject']);
    $message = sanitize_input($_POST['message']);
    
    // Validate required fields
    $required = ['name', 'email', 'subject', 'message'];
    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            $errors[] = ucfirst($field) . ' is required.';
        }
    }
    
    // Validate email
    if (!empty($email) && !validate_email($email)) {
        $errors[] = 'Please enter a valid email address.';
    }
    
    // Validate phone if provided
    if (!empty($phone) && !validate_phone($phone)) {
        $errors[] = 'Please enter a valid Ethiopian phone number (format: +251911234567).';
    }
    
    if (empty($errors)) {
        // In production, you would:
        // 1. Save to database
        // 2. Send email notification
        // 3. Send confirmation email to user
        
        // For now, just show success message
        $success = true;
        
        // Save contact message to database (optional)
        try {
            $db = new Database();
            $db->query("INSERT INTO contact_messages (name, email, phone, subject, message, created_at) 
                       VALUES (:name, :email, :phone, :subject, :message, NOW())");
            $db->bind(':name', $name);
            $db->bind(':email', $email);
            $db->bind(':phone', $phone);
            $db->bind(':subject', $subject);
            $db->bind(':message', $message);
            $db->execute();
        } catch (Exception $e) {
            // Log error but don't show to user
            error_log('Contact form error: ' . $e->getMessage());
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
                <li class="breadcrumb-item active">Contact Us</li>
            </ol>
        </div>
    </nav>

    <!-- Contact Content -->
    <div class="container py-5">
        <div class="row">
            <div class="col-lg-8">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0"><i class="fas fa-envelope me-2"></i>Send Us a Message</h4>
                    </div>
                    <div class="card-body">
                        <?php if ($success): ?>
                        <!-- Success Message -->
                        <div class="text-center py-4">
                            <i class="fas fa-check-circle fa-4x text-success mb-3"></i>
                            <h4 class="text-success">Message Sent Successfully!</h4>
                            <p class="mb-4">Thank you for contacting us. We'll get back to you within 24 hours.</p>
                            <a href="contact.php" class="btn btn-primary">
                                <i class="fas fa-envelope me-2"></i>Send Another Message
                            </a>
                        </div>
                        <?php else: ?>
                        <!-- Error Messages -->
                        <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <h5><i class="fas fa-exclamation-triangle me-2"></i>Please fix the following errors:</h5>
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                <li><?php echo $error; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Contact Form -->
                        <form method="POST" id="contact-form">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="name" class="form-label">Full Name *</label>
                                    <input type="text" class="form-control" id="name" name="name" 
                                           value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" required>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label">Email Address *</label>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="phone" class="form-label">Phone Number</label>
                                    <input type="tel" class="form-control" id="phone" name="phone" 
                                           value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>"
                                           placeholder="+251911234567">
                                    <small class="text-muted">Optional</small>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="subject" class="form-label">Subject *</label>
                                    <select class="form-select" id="subject" name="subject" required>
                                        <option value="">Select Subject</option>
                                        <option value="General Inquiry" <?php echo ($_POST['subject'] ?? '') == 'General Inquiry' ? 'selected' : ''; ?>>General Inquiry</option>
                                        <option value="Order Support" <?php echo ($_POST['subject'] ?? '') == 'Order Support' ? 'selected' : ''; ?>>Order Support</option>
                                        <option value="Product Inquiry" <?php echo ($_POST['subject'] ?? '') == 'Product Inquiry' ? 'selected' : ''; ?>>Product Inquiry</option>
                                        <option value="Become a Vendor" <?php echo ($_POST['subject'] ?? '') == 'Become a Vendor' ? 'selected' : ''; ?>>Become a Vendor</option>
                                        <option value="Authenticity Verification" <?php echo ($_POST['subject'] ?? '') == 'Authenticity Verification' ? 'selected' : ''; ?>>Authenticity Verification</option>
                                        <option value="Return & Refund" <?php echo ($_POST['subject'] ?? '') == 'Return & Refund' ? 'selected' : ''; ?>>Return & Refund</option>
                                        <option value="Technical Support" <?php echo ($_POST['subject'] ?? '') == 'Technical Support' ? 'selected' : ''; ?>>Technical Support</option>
                                        <option value="Other" <?php echo ($_POST['subject'] ?? '') == 'Other' ? 'selected' : ''; ?>>Other</option>
                                    </select>
                                </div>
                                
                                <div class="col-12 mb-3">
                                    <label for="message" class="form-label">Message *</label>
                                    <textarea class="form-control" id="message" name="message" rows="6" required><?php echo htmlspecialchars($_POST['message'] ?? ''); ?></textarea>
                                </div>
                                
                                <div class="col-12">
                                    <div class="d-grid">
                                        <button type="submit" class="btn btn-primary btn-lg">
                                            <i class="fas fa-paper-plane me-2"></i>Send Message
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Contact Information -->
            <div class="col-lg-4">
                <!-- Contact Details -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-success text-white">
                        <h4 class="mb-0"><i class="fas fa-info-circle me-2"></i>Contact Information</h4>
                    </div>
                    <div class="card-body">
                        <div class="contact-info">
                            <div class="d-flex mb-4">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-map-marker-alt fa-2x text-primary"></i>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h6>Our Location</h6>
                                    <p class="mb-0">Addis Ababa, Ethiopia</p>
                                </div>
                            </div>
                            
                            <div class="d-flex mb-4">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-phone fa-2x text-success"></i>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h6>Phone Numbers</h6>
                                    <p class="mb-1">+251 911 123 456</p>
                                    <p class="mb-0">+251 911 987 654</p>
                                </div>
                            </div>
                            
                            <div class="d-flex mb-4">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-envelope fa-2x text-warning"></i>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h6>Email Address</h6>
                                    <p class="mb-1">info@sneakerhub.et</p>
                                    <p class="mb-0">support@sneakerhub.et</p>
                                </div>
                            </div>
                            
                            <div class="d-flex">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-clock fa-2x text-info"></i>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h6>Business Hours</h6>
                                    <p class="mb-1">Monday - Sunday: 9:00 AM - 8:00 PM</p>
                                    <p class="mb-0">24/7 Online Support</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Quick Links -->
                <div class="card shadow-sm">
                    <div class="card-header bg-info text-white">
                        <h4 class="mb-0"><i class="fas fa-link me-2"></i>Quick Links</h4>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled mb-0">
                            <li class="mb-2">
                                <a href="faq.php" class="text-decoration-none">
                                    <i class="fas fa-question-circle me-2"></i>Frequently Asked Questions
                                </a>
                            </li>
                            <li class="mb-2">
                                <a href="shipping.php" class="text-decoration-none">
                                    <i class="fas fa-truck me-2"></i>Shipping Information
                                </a>
                            </li>
                            <li class="mb-2">
                                <a href="returns.php" class="text-decoration-none">
                                    <i class="fas fa-undo-alt me-2"></i>Return Policy
                                </a>
                            </li>
                            <li class="mb-2">
                                <a href="terms.php" class="text-decoration-none">
                                    <i class="fas fa-file-contract me-2"></i>Terms & Conditions
                                </a>
                            </li>
                            <li>
                                <a href="privacy.php" class="text-decoration-none">
                                    <i class="fas fa-shield-alt me-2"></i>Privacy Policy
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Map Section -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-warning text-white">
                        <h4 class="mb-0"><i class="fas fa-map me-2"></i>Find Us</h4>
                    </div>
                    <div class="card-body p-0">
                        <!-- Google Maps Embed -->
                        <div class="map-container" style="height: 400px; background: #f8f9fa;">
                            <div class="d-flex align-items-center justify-content-center h-100">
                                <div class="text-center">
                                    <i class="fas fa-map-marked-alt fa-4x text-muted mb-3"></i>
                                    <h5>Addis Ababa, Ethiopia</h5>
                                    <p class="text-muted">We're located in the heart of Addis Ababa</p>
                                </div>
                            </div>
                            <!-- In production, replace with actual Google Maps embed code -->
                            <!-- 
                            <iframe 
                                src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3940.22244537593!2d38.75768231430241!3d9.022511793517933!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x164b85cef5ab402d%3A0x8467b6b037a24d49!2sAddis%20Ababa%2C%20Ethiopia!5e0!3m2!1sen!2sus!4v1641234567890!5m2!1sen!2sus" 
                                width="100%" 
                                height="400" 
                                style="border:0;" 
                                allowfullscreen="" 
                                loading="lazy">
                            </iframe>
                            -->
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
        // Phone number formatting
        document.getElementById('phone').addEventListener('input', function() {
            let value = this.value.replace(/\D/g, '');
            
            if (value.startsWith('0')) {
                value = '+251' + value.substring(1);
            } else if (value.startsWith('251')) {
                value = '+' + value;
            } else if (value.startsWith('9') && value.length >= 9) {
                value = '+251' + value;
            }
            
            if (value.length > 13) {
                value = value.substring(0, 13);
            }
            
            this.value = value;
        });
        
        // Form validation
        document.getElementById('contact-form').addEventListener('submit', function(e) {
            const name = document.getElementById('name').value;
            const email = document.getElementById('email').value;
            const subject = document.getElementById('subject').value;
            const message = document.getElementById('message').value;
            
            // Check required fields
            if (!name || !email || !subject || !message) {
                e.preventDefault();
                alert('Please fill in all required fields.');
                return;
            }
            
            // Validate email
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                e.preventDefault();
                alert('Please enter a valid email address.');
                return;
            }
            
            // Show loading
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Sending...';
            submitBtn.disabled = true;
        });
    </script>
    
    <style>
        .contact-info .fa-2x {
            width: 40px;
            text-align: center;
        }
        .map-container {
            border-radius: 0 0 0.375rem 0.375rem;
            overflow: hidden;
        }
    </style>
</body>
</html>