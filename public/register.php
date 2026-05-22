<?php
require_once '../config.php';

$page_title = 'Register - ' . SITE_NAME;

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: ../buyer/index.php');
    exit();
}

// Handle registration form submission
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect form data
    $data = [
        'email' => isset($_POST['email']) ? sanitize_input($_POST['email']) : '',
        'password' => isset($_POST['password']) ? $_POST['password'] : '',
        'confirm_password' => isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '',
        'first_name' => isset($_POST['first_name']) ? sanitize_input($_POST['first_name']) : '',
        'last_name' => isset($_POST['last_name']) ? sanitize_input($_POST['last_name']) : '',
        'phone' => isset($_POST['phone']) ? sanitize_input($_POST['phone']) : '',
        'user_type' => isset($_POST['user_type']) ? sanitize_input($_POST['user_type']) : 'buyer'
    ];
    
    // Validate required fields
    $required_fields = ['email', 'password', 'confirm_password', 'first_name', 'last_name', 'phone', 'user_type'];
    foreach ($required_fields as $field) {
        if (empty($data[$field])) {
            $errors[] = ucfirst(str_replace('_', ' ', $field)) . ' is required.';
        }
    }
    
    // Validate email
    if (!empty($data['email'])) {
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address.';
        }
    }
    
    // Validate phone (Ethiopian format)
    if (!empty($data['phone']) && !preg_match('/^\+251[0-9]{9}$/', $data['phone'])) {
        $errors[] = 'Please enter a valid Ethiopian phone number (format: +251911234567).';
    }
    
    // Validate password
    if (!empty($data['password']) && strlen($data['password']) < 6) {
        $errors[] = 'Password must be at least 6 characters long.';
    }
    
    // Confirm password
    if ($data['password'] !== $data['confirm_password']) {
        $errors[] = 'Passwords do not match.';
    }
    
    // Vendor-specific validation
    if ($data['user_type'] === 'vendor') {
        if (empty($_POST['store_name'])) {
            $errors[] = 'Store name is required for vendors.';
        } else {
            $data['store_name'] = sanitize_input($_POST['store_name']);
            $data['store_description'] = sanitize_input($_POST['store_description'] ?? '');
        }
    }
    
    // Check terms agreement
    if (!isset($_POST['terms'])) {
        $errors[] = 'You must agree to the Terms and Conditions.';
    }
    
    // If no errors, proceed with registration
    if (empty($errors)) {
        try {
            // Include the User class
            require_once '../classes/User.php';
            
            // Check if User class exists and can be instantiated
            if (!class_exists('User')) {
                throw new Exception('User class not found. Please check the classes directory.');
            }
            
            $user = new User();
            
            // Debug: Check if register method exists
            if (!method_exists($user, 'register')) {
                throw new Exception('Register method not found in User class.');
            }
            
            $user_id = $user->register($data);
            
            if ($user_id) {
                // Set success message
                $_SESSION['registration_success'] = 'Registration successful! You can now login to your account.';
                
                // Redirect to login page
                header('Location: login.php');
                exit();
            } else {
                $errors[] = 'Registration failed. Please try again.';
            }
            
        } catch (Exception $e) {
            // Log the error for debugging
            error_log('Registration error: ' . $e->getMessage());
            $errors[] = 'An error occurred during registration. Please try again later.';
            // For debugging, you can show the actual error during development:
            // $errors[] = $e->getMessage();
        }
    }
}
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
    
    <style>
        .card-radio .card {
            cursor: pointer;
            transition: all 0.3s;
        }
        .card-radio .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .card-radio .form-check-input {
            position: absolute;
            opacity: 0;
        }
        .card-radio .form-check-input:checked + label .card {
            border-color: #0d6efd !important;
            background-color: rgba(13, 110, 253, 0.05);
        }
        .progress {
            height: 5px;
        }
    </style>
</head>
<body class="bg-light">
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-shoe-prints"></i> <?php echo SITE_NAME; ?>
            </a>
        </div>
    </nav>

    <!-- Registration Form -->
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card shadow-lg border-0">
                    <div class="card-header bg-primary text-white text-center py-4">
                        <h3><i class="fas fa-user-plus me-2"></i>Create Your Account</h3>
                        <p class="mb-0">Join Ethiopia's premier sneaker marketplace</p>
                    </div>
                    
                    <div class="card-body p-4">
                        <!-- Error Messages -->
                        <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <h5><i class="fas fa-exclamation-triangle me-2"></i>Please fix the following errors:</h5>
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Registration Form -->
                        <form method="POST" id="register-form" novalidate>
                            <!-- Account Type -->
                            <div class="mb-4">
                                <h6 class="text-muted mb-3">Account Type *</h6>
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <div class="form-check card-radio">
                                            <input class="form-check-input" type="radio" name="user_type" 
                                                   id="buyer" value="buyer" required 
                                                   <?php echo (isset($_POST['user_type']) && $_POST['user_type'] === 'buyer') || !isset($_POST['user_type']) ? 'checked' : ''; ?>>
                                            <label class="form-check-label w-100 h-100" for="buyer">
                                                <div class="card h-100 text-center border-2 
                                                    <?php echo (isset($_POST['user_type']) && $_POST['user_type'] === 'buyer') || !isset($_POST['user_type']) ? 'border-primary' : 'border-light'; ?>">
                                                    <div class="card-body">
                                                        <i class="fas fa-shopping-bag fa-3x text-primary mb-3"></i>
                                                        <h5>Buyer</h5>
                                                        <p class="small text-muted">I want to buy sneakers</p>
                                                    </div>
                                                </div>
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <div class="form-check card-radio">
                                            <input class="form-check-input" type="radio" name="user_type" 
                                                   id="vendor" value="vendor" 
                                                   <?php echo isset($_POST['user_type']) && $_POST['user_type'] === 'vendor' ? 'checked' : ''; ?>>
                                            <label class="form-check-label w-100 h-100" for="vendor">
                                                <div class="card h-100 text-center border-2 
                                                    <?php echo isset($_POST['user_type']) && $_POST['user_type'] === 'vendor' ? 'border-primary' : 'border-light'; ?>">
                                                    <div class="card-body">
                                                        <i class="fas fa-store fa-3x text-success mb-3"></i>
                                                        <h5>Vendor</h5>
                                                        <p class="small text-muted">I want to sell sneakers</p>
                                                    </div>
                                                </div>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Vendor Information (shown when vendor is selected) -->
                            <div id="vendor-info" class="mb-4" style="display: <?php echo isset($_POST['user_type']) && $_POST['user_type'] === 'vendor' ? 'block' : 'none'; ?>;">
                                <h6 class="text-muted mb-3">Store Information</h6>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="store_name" class="form-label">Store Name *</label>
                                        <input type="text" class="form-control" id="store_name" name="store_name" 
                                               value="<?php echo isset($_POST['store_name']) ? htmlspecialchars($_POST['store_name']) : ''; ?>"
                                               placeholder="Your store name">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="store_description" class="form-label">Store Description</label>
                                        <input type="text" class="form-control" id="store_description" name="store_description" 
                                               value="<?php echo isset($_POST['store_description']) ? htmlspecialchars($_POST['store_description']) : ''; ?>"
                                               placeholder="Brief description of your store">
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Personal Information -->
                            <div class="mb-4">
                                <h6 class="text-muted mb-3">Personal Information</h6>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="first_name" class="form-label">First Name *</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                                            <input type="text" class="form-control" id="first_name" name="first_name" 
                                                   value="<?php echo isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : ''; ?>"
                                                   placeholder="John" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="last_name" class="form-label">Last Name *</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                                            <input type="text" class="form-control" id="last_name" name="last_name" 
                                                   value="<?php echo isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : ''; ?>"
                                                   placeholder="Doe" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="email" class="form-label">Email Address *</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                            <input type="email" class="form-control" id="email" name="email" 
                                                   value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                                                   placeholder="john@example.com" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="phone" class="form-label">Phone Number *</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-phone"></i></span>
                                            <input type="tel" class="form-control" id="phone" name="phone" 
                                                   value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>"
                                                   placeholder="+251911234567" 
                                                   pattern="\+251[0-9]{9}" required>
                                        </div>
                                        <small class="text-muted">Ethiopian format: +251911234567</small>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Password -->
                            <div class="mb-4">
                                <h6 class="text-muted mb-3">Password</h6>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="password" class="form-label">Password *</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                            <input type="password" class="form-control" id="password" name="password" 
                                                   placeholder="At least 6 characters" required>
                                            <button class="btn btn-outline-secondary" type="button" id="toggle-password">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="confirm_password" class="form-label">Confirm Password *</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                            <input type="password" class="form-control" id="confirm_password" 
                                                   name="confirm_password" placeholder="Confirm your password" required>
                                            <button class="btn btn-outline-secondary" type="button" id="toggle-confirm-password">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="progress mt-2">
                                    <div id="password-strength-bar" class="progress-bar" role="progressbar" style="width: 0%"></div>
                                </div>
                                <small id="password-strength-text" class="text-muted"></small>
                            </div>
                            
                            <!-- Terms and Conditions -->
                            <div class="mb-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="terms" name="terms" 
                                           <?php echo isset($_POST['terms']) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="terms">
                                        I agree to the <a href="terms.php" target="_blank" class="text-decoration-none">Terms and Conditions</a> 
                                        and <a href="privacy.php" target="_blank" class="text-decoration-none">Privacy Policy</a> *
                                    </label>
                                </div>
                            </div>
                            
                            <!-- Submit Button -->
                            <div class="d-grid mb-3">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-user-plus me-2"></i>Create Account
                                </button>
                            </div>
                            
                            <div class="text-center">
                                <p class="mb-0">Already have an account? 
                                    <a href="login.php" class="text-decoration-none fw-bold">Sign in here</a>
                                </p>
                            </div>
                        </form>
                    </div>
                    
                    <div class="card-footer bg-light py-3">
                        <div class="row text-center">
                            <div class="col-md-4 mb-2">
                                <i class="fas fa-shield-alt text-success me-2"></i>
                                <small>Secure Registration</small>
                            </div>
                            <div class="col-md-4 mb-2">
                                <i class="fas fa-bolt text-warning me-2"></i>
                                <small>Quick Setup</small>
                            </div>
                            <div class="col-md-4 mb-2">
                                <i class="fas fa-headset text-info me-2"></i>
                                <small>24/7 Support</small>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Benefits -->
                <div class="row mt-4">
                    <div class="col-md-4 mb-3">
                        <div class="card h-100 border-0 shadow-sm">
                            <div class="card-body text-center">
                                <i class="fas fa-truck fa-2x text-primary mb-3"></i>
                                <h6>Fast Delivery</h6>
                                <p class="small text-muted">Delivery across Ethiopia within 3-7 days</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card h-100 border-0 shadow-sm">
                            <div class="card-body text-center">
                                <i class="fas fa-shield-alt fa-2x text-success mb-3"></i>
                                <h6>Authentic Products</h6>
                                <p class="small text-muted">100% genuine sneakers guaranteed</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card h-100 border-0 shadow-sm">
                            <div class="card-body text-center">
                                <i class="fas fa-handshake fa-2x text-warning mb-3"></i>
                                <h6>Secure Payments</h6>
                                <p class="small text-muted">Multiple secure payment options</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-white py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <p class="mb-0">&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. All rights reserved.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <a href="contact.php" class="text-white text-decoration-none me-3">Contact</a>
                    <a href="terms.php" class="text-white text-decoration-none me-3">Terms</a>
                    <a href="privacy.php" class="text-white text-decoration-none">Privacy</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script src="../assets/js/main.js"></script>
    
    <script>
        // Show/hide vendor info based on account type
        document.querySelectorAll('input[name="user_type"]').forEach(radio => {
            radio.addEventListener('change', function() {
                const vendorInfo = document.getElementById('vendor-info');
                const storeNameInput = document.getElementById('store_name');
                
                if (this.value === 'vendor') {
                    vendorInfo.style.display = 'block';
                    // Make store name required
                    if (storeNameInput) {
                        storeNameInput.required = true;
                    }
                } else {
                    vendorInfo.style.display = 'none';
                    // Make store name not required
                    if (storeNameInput) {
                        storeNameInput.required = false;
                    }
                }
            });
        });
        
        // Toggle password visibility
        document.getElementById('toggle-password')?.addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
            const icon = this.querySelector('i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
        
        document.getElementById('toggle-confirm-password')?.addEventListener('click', function() {
            const passwordInput = document.getElementById('confirm_password');
            const icon = this.querySelector('i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
        
        // Password strength checker
        document.getElementById('password')?.addEventListener('input', function() {
            const password = this.value;
            const strengthBar = document.getElementById('password-strength-bar');
            const strengthText = document.getElementById('password-strength-text');
            
            let strength = 0;
            let text = '';
            let color = '';
            
            if (password.length >= 8) strength += 25;
            if (/[A-Z]/.test(password)) strength += 25;
            if (/[0-9]/.test(password)) strength += 25;
            if (/[^A-Za-z0-9]/.test(password)) strength += 25;
            
            if (strengthBar) {
                strengthBar.style.width = strength + '%';
            }
            
            if (strength < 50) {
                color = 'danger';
                text = 'Weak password';
            } else if (strength < 75) {
                color = 'warning';
                text = 'Medium password';
            } else {
                color = 'success';
                text = 'Strong password';
            }
            
            if (strengthBar) {
                strengthBar.className = 'progress-bar bg-' + color;
            }
            if (strengthText) {
                strengthText.textContent = text;
                strengthText.className = 'text-' + color;
            }
        });
        
        // Form validation
        document.getElementById('register-form')?.addEventListener('submit', function(e) {
            const password = document.getElementById('password')?.value || '';
            const confirmPassword = document.getElementById('confirm_password')?.value || '';
            const userType = document.querySelector('input[name="user_type"]:checked')?.value;
            
            // Check password match
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match.');
                return;
            }
            
            // Check password strength
            if (password.length < 6) {
                e.preventDefault();
                alert('Password must be at least 6 characters long.');
                return;
            }
            
            // Check vendor store name
            if (userType === 'vendor') {
                const storeName = document.getElementById('store_name')?.value || '';
                if (!storeName.trim()) {
                    e.preventDefault();
                    alert('Store name is required for vendors.');
                    return;
                }
            }
            
            // Check terms agreement
            if (!document.getElementById('terms')?.checked) {
                e.preventDefault();
                alert('You must agree to the Terms and Conditions.');
                return;
            }
            
            // Show loading
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Creating Account...';
                submitBtn.disabled = true;
            }
        });
        
        // Format phone number
        document.getElementById('phone')?.addEventListener('input', function() {
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
    </script>
</body>
</html>