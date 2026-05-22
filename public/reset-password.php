<?php
require_once '../config.php';

$page_title = 'Reset Password - ' . SITE_NAME;

// Check for token
$token = $_GET['token'] ?? '';
$email = verify_password_reset_token($token);

if (!$email) {
    $_SESSION['error'] = 'Invalid or expired password reset link.';
    redirect('forgot-password.php');
}

// Handle form submission
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validate passwords
    if (empty($password) || empty($confirm_password)) {
        $errors[] = 'Please enter and confirm your new password.';
    } elseif (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters long.';
    } elseif ($password !== $confirm_password) {
        $errors[] = 'Passwords do not match.';
    } else {
        try {
            // Get user by email
            $user = new User();
            $user_data = $user->getUserByEmail($email);
            
            if ($user_data) {
                // Update password
                $new_hash = hash_password($password);
                
                $db = new Database();
                $db->query("UPDATE users SET password_hash = :hash, updated_at = NOW() WHERE email = :email");
                $db->bind(':hash', $new_hash);
                $db->bind(':email', $email);
                $db->execute();
                
                // Delete used token
                delete_password_reset_token($token);
                
                $success = true;
                
                // Clear session token
                unset($_SESSION['reset_token'], $_SESSION['reset_email']);
            } else {
                $errors[] = 'User not found.';
            }
        } catch (Exception $e) {
            $errors[] = 'An error occurred. Please try again.';
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

    <!-- Reset Password Form -->
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="card shadow-lg border-0">
                    <div class="card-header bg-primary text-white text-center py-4">
                        <h3><i class="fas fa-lock me-2"></i>Set New Password</h3>
                        <p class="mb-0">Create a new password for your account</p>
                    </div>
                    
                    <div class="card-body p-4">
                        <?php if ($success): ?>
                        <!-- Success Message -->
                        <div class="text-center py-4">
                            <i class="fas fa-check-circle fa-4x text-success mb-3"></i>
                            <h4 class="text-success">Password Reset Successful!</h4>
                            <p class="mb-4">Your password has been successfully reset. You can now login with your new password.</p>
                            <div class="d-grid">
                                <a href="login.php" class="btn btn-primary btn-lg">
                                    <i class="fas fa-sign-in-alt me-2"></i>Login Now
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
                        
                        <!-- Password Reset Form -->
                        <form method="POST" id="reset-password-form">
                            <div class="alert alert-info mb-4">
                                <i class="fas fa-user me-2"></i>
                                Resetting password for: <strong><?php echo htmlspecialchars($email); ?></strong>
                            </div>
                            
                            <div class="mb-3">
                                <label for="password" class="form-label">New Password</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                    <input type="password" class="form-control" id="password" name="password" 
                                           placeholder="Enter new password" required>
                                    <button class="btn btn-outline-secondary" type="button" id="toggle-password">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <div class="form-text">Minimum 6 characters</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Confirm New Password</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                    <input type="password" class="form-control" id="confirm_password" 
                                           name="confirm_password" placeholder="Confirm new password" required>
                                    <button class="btn btn-outline-secondary" type="button" id="toggle-confirm-password">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Password Strength Meter -->
                            <div class="mb-4">
                                <div class="progress mb-2" style="height: 5px;">
                                    <div id="password-strength-bar" class="progress-bar" role="progressbar" style="width: 0%"></div>
                                </div>
                                <small id="password-strength-text" class="text-muted"></small>
                            </div>
                            
                            <div class="d-grid mb-3">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-save me-2"></i>Reset Password
                                </button>
                            </div>
                            
                            <div class="text-center">
                                <p class="mb-0">
                                    <a href="login.php" class="text-decoration-none">
                                        <i class="fas fa-arrow-left me-1"></i>Back to Login
                                    </a>
                                </p>
                            </div>
                        </form>
                        <?php endif; ?>
                    </div>
                    
                    <div class="card-footer bg-light text-center py-3">
                        <small class="text-muted">
                            Ensure your new password is strong and unique.
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
        
        document.getElementById('toggle-confirm-password').addEventListener('click', function() {
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
        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
            const strengthBar = document.getElementById('password-strength-bar');
            const strengthText = document.getElementById('password-strength-text');
            
            let strength = 0;
            let text = '';
            let color = '';
            
            // Length check
            if (password.length >= 8) strength += 25;
            if (password.length >= 12) strength += 10;
            
            // Character type checks
            if (/[A-Z]/.test(password)) strength += 25;
            if (/[0-9]/.test(password)) strength += 25;
            if (/[^A-Za-z0-9]/.test(password)) strength += 25;
            
            // Cap at 100
            strength = Math.min(strength, 100);
            
            strengthBar.style.width = strength + '%';
            
            if (strength < 40) {
                color = 'danger';
                text = 'Weak password';
            } else if (strength < 70) {
                color = 'warning';
                text = 'Medium password';
            } else if (strength < 90) {
                color = 'info';
                text = 'Good password';
            } else {
                color = 'success';
                text = 'Strong password';
            }
            
            strengthBar.className = 'progress-bar bg-' + color;
            strengthText.textContent = text;
            strengthText.className = 'text-' + color;
        });
        
        // Form validation
        document.getElementById('reset-password-form').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            // Check password length
            if (password.length < 6) {
                e.preventDefault();
                alert('Password must be at least 6 characters long.');
                return;
            }
            
            // Check password match
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match.');
                return;
            }
            
            // Show loading
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Resetting...';
            submitBtn.disabled = true;
        });
    </script>
</body>
</html>