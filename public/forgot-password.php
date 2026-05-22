<?php
require_once '../config.php';

$page_title = 'Forgot Password - ' . SITE_NAME;

// Handle form submission
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize_input($_POST['email']);
    
    if (empty($email)) {
        $errors[] = 'Please enter your email address.';
    } elseif (!validate_email($email)) {
        $errors[] = 'Please enter a valid email address.';
    } else {
        // Check if email exists
        $user = new User();
        $user_data = $user->getUserByEmail($email);
        
        if ($user_data) {
            // Generate password reset token
            $token = generate_password_reset_token($email);
            
            if ($token) {
                // In production, you would send an email here
                // For now, we'll just show the reset link
                $reset_link = SITE_URL . '/public/reset-password.php?token=' . $token;
                $success = true;
                
                // Store token in session for demo purposes
                $_SESSION['reset_token'] = $token;
                $_SESSION['reset_email'] = $email;
            } else {
                $errors[] = 'Failed to generate reset token. Please try again.';
            }
        } else {
            // Don't reveal if email exists for security
            $success = true; // Show success message anyway
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

    <!-- Forgot Password Form -->
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="card shadow-lg border-0">
                    <div class="card-header bg-primary text-white text-center py-4">
                        <h3><i class="fas fa-key me-2"></i>Forgot Password</h3>
                        <p class="mb-0">Reset your account password</p>
                    </div>
                    
                    <div class="card-body p-4">
                        <?php if ($success): ?>
                        <!-- Success Message -->
                        <div class="text-center py-4">
                            <i class="fas fa-check-circle fa-4x text-success mb-3"></i>
                            <h4 class="text-success">Check Your Email</h4>
                            <p class="mb-3">If an account exists with that email, you will receive password reset instructions.</p>
                            
                            <?php if (isset($reset_link)): ?>
                            <div class="alert alert-info">
                                <h6><i class="fas fa-link me-2"></i>Password Reset Link (for demo):</h6>
                                <input type="text" class="form-control mt-2" value="<?php echo $reset_link; ?>" readonly>
                                <small class="text-muted">Click this link to reset your password</small>
                            </div>
                            <?php endif; ?>
                            
                            <div class="mt-4">
                                <a href="login.php" class="btn btn-primary">
                                    <i class="fas fa-sign-in-alt me-2"></i>Back to Login
                                </a>
                            </div>
                        </div>
                        <?php else: ?>
                        <!-- Error Messages -->
                        <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <?php foreach ($errors as $error): ?>
                            <p class="mb-1"><?php echo $error; ?></p>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Instructions -->
                        <div class="alert alert-info mb-4">
                            <i class="fas fa-info-circle me-2"></i>
                            Enter your email address and we'll send you a link to reset your password.
                        </div>
                        
                        <!-- Forgot Password Form -->
                        <form method="POST" id="forgot-password-form">
                            <div class="mb-4">
                                <label for="email" class="form-label">Email Address</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" 
                                           placeholder="your@email.com" required>
                                </div>
                            </div>
                            
                            <div class="d-grid mb-3">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-paper-plane me-2"></i>Send Reset Link
                                </button>
                            </div>
                            
                            <div class="text-center">
                                <p class="mb-0">
                                    Remember your password? 
                                    <a href="login.php" class="text-decoration-none fw-bold">Sign in here</a>
                                </p>
                                <p class="mt-2">
                                    <a href="index.php" class="text-decoration-none">
                                        <i class="fas fa-arrow-left me-1"></i>Back to home
                                    </a>
                                </p>
                            </div>
                        </form>
                        <?php endif; ?>
                    </div>
                    
                    <div class="card-footer bg-light text-center py-3">
                        <small class="text-muted">
                            For security reasons, password reset links expire in 1 hour.
                        </small>
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
                    <a href="terms.php" class="text-white text-decoration-none">Terms</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Form validation
        document.getElementById('forgot-password-form').addEventListener('submit', function(e) {
            const email = document.getElementById('email').value;
            
            if (!email) {
                e.preventDefault();
                alert('Please enter your email address.');
                return;
            }
            
            // Basic email validation
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
</body>
</html>