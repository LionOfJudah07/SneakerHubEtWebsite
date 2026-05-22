<?php
require_once '../config.php';
require_once '../functions.php';
require_once '../classes/User.php';

$page_title = 'Login - ' . SITE_NAME;

// Check if already logged in
if (is_logged_in()) {
    $redirect = $_SESSION['redirect_to'] ?? get_user_dashboard($_SESSION['user_type'] ?? 'buyer');
    unset($_SESSION['redirect_to']);
    redirect($redirect);
}

// Handle login form submission
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize_input($_POST['email']);
    $password = $_POST['password'];
    $remember = isset($_POST['remember']);
    
    if (empty($email) || empty($password)) {
        $errors[] = 'Please enter both email and password.';
    } else {
        $user = new User();
        $user_data = $user->login($email, $password);
        
        if ($user_data) {
            login_user($user_data['id'], $user_data['user_type'], $remember);
            
            // Store additional user info in session
            $_SESSION['user_email'] = $user_data['email'];
            $_SESSION['user_name'] = $user_data['first_name'] . ' ' . $user_data['last_name'];
            
            // Set success message
            $_SESSION['welcome_message'] = 'Welcome back, ' . htmlspecialchars($user_data['first_name']) . '!';
            
            // Redirect based on user type
            $redirect = $_SESSION['redirect_to'] ?? get_user_dashboard($user_data['user_type']);
            unset($_SESSION['redirect_to']);
            
            redirect($redirect);
        } else {
            $errors[] = 'Invalid email or password.';
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

    <!-- Login Form -->
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="card shadow-lg border-0">
                    <div class="card-header bg-primary text-white text-center py-4">
                        <h3><i class="fas fa-sign-in-alt me-2"></i>Welcome Back</h3>
                        <p class="mb-0">Sign in to your account</p>
                    </div>
                    
                    <div class="card-body p-4">
                        <!-- Error Messages -->
                        <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <?php foreach ($errors as $error): ?>
                            <p class="mb-1"><?php echo $error; ?></p>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Success Message (for redirected registration) -->
                        <?php if (isset($_SESSION['registration_success'])): ?>
                        <div class="alert alert-success">
                            <?php echo $_SESSION['registration_success']; ?>
                            <?php unset($_SESSION['registration_success']); ?>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Login Form -->
                        <form method="POST" id="login-form">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email Address</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" 
                                           placeholder="your@email.com" required>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                    <input type="password" class="form-control" id="password" name="password" 
                                           placeholder="Enter your password" required>
                                    <button class="btn btn-outline-secondary" type="button" id="toggle-password">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <div class="form-text">
                                    <a href="forgot-password.php" class="text-decoration-none">Forgot password?</a>
                                </div>
                            </div>
                            
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="remember" name="remember">
                                <label class="form-check-label" for="remember">Remember me</label>
                            </div>
                            
                            <div class="d-grid mb-3">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-sign-in-alt me-2"></i>Sign In
                                </button>
                            </div>
                            
                            <div class="text-center mb-3">
                                <p class="text-muted">Or sign in with</p>
                                <div class="d-flex justify-content-center gap-3">
                                    <button type="button" class="btn btn-outline-primary">
                                        <i class="fab fa-google"></i> Google
                                    </button>
                                    <button type="button" class="btn btn-outline-dark">
                                        <i class="fab fa-facebook-f"></i> Facebook
                                    </button>
                                </div>
                            </div>
                        </form>
                        
                        <div class="text-center mt-4">
                            <p class="mb-0">Don't have an account? 
                                <a href="register.php" class="text-decoration-none fw-bold">Sign up now</a>
                            </p>
                            <p class="mt-2">
                                <a href="index.php" class="text-decoration-none">
                                    <i class="fas fa-arrow-left me-1"></i>Back to home
                                </a>
                            </p>
                        </div>
                    </div>
                    
                    <div class="card-footer bg-light text-center py-3">
                        <small class="text-muted">
                            By signing in, you agree to our 
                            <a href="terms.php" class="text-decoration-none">Terms</a> and 
                            <a href="privacy.php" class="text-decoration-none">Privacy Policy</a>
                        </small>
                    </div>
                </div>
                
                <!-- User Type Info -->
                <div class="row mt-4 text-center">
                    <div class="col-md-4 mb-3">
                        <div class="card h-100 border-0 shadow-sm">
                            <div class="card-body">
                                <i class="fas fa-user fa-2x text-primary mb-3"></i>
                                <h6>Buyer</h6>
                                <p class="small text-muted">Shop for sneakers</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card h-100 border-0 shadow-sm">
                            <div class="card-body">
                                <i class="fas fa-store fa-2x text-success mb-3"></i>
                                <h6>Vendor</h6>
                                <p class="small text-muted">Sell your products</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card h-100 border-0 shadow-sm">
                            <div class="card-body">
                                <i class="fas fa-cog fa-2x text-danger mb-3"></i>
                                <h6>Admin</h6>
                                <p class="small text-muted">Manage the platform</p>
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
        // Toggle password visibility
        document.getElementById('toggle-password').addEventListener('click', function() {
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
        
        // Form validation
        document.getElementById('login-form').addEventListener('submit', function(e) {
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            
            if (!email || !password) {
                e.preventDefault();
                alert('Please fill in all required fields.');
                return;
            }
            
            // Show loading
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Signing in...';
            submitBtn.disabled = true;
        });
    </script>
</body>
</html>