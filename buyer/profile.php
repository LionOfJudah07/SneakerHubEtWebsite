<?php
require_once '../config.php';
require_once '../functions.php';
require_once '../classes/User.php';
require_once '../classes/Order.php';
require_once '../classes/Database.php';

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Require buyer login
if (!is_logged_in()) {
    $_SESSION['redirect_to'] = $_SERVER['REQUEST_URI'];
    header('Location: ../public/login.php');
    exit();
}

if (!is_buyer()) {
    $_SESSION['error'] = 'Access denied. Please login as a buyer.';
    header('Location: ../public/index.php');
    exit();
}

$page_title = 'My Profile - ' . SITE_NAME;

// Get user data
$user = new User();
$user_data = $user->getUserById($_SESSION['user_id']);

// Handle profile update
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Determine which form was submitted
    if (isset($_POST['update_profile'])) {
        // Update profile information
        $update_data = [
            'first_name' => sanitize_input($_POST['first_name']),
            'last_name' => sanitize_input($_POST['last_name']),
            'phone' => sanitize_input($_POST['phone'])
        ];

        // Handle profile image upload
        if (!empty($_FILES['profile_image']['name']) && $_FILES['profile_image']['error'] == 0) {
            $upload_dir = '../assets/images/users/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            // Check if upload_image function exists, otherwise use fallback
            if (function_exists('upload_image')) {
                $upload_result = upload_image($_FILES['profile_image'], $upload_dir);
            } else {
                // Fallback upload function
                $upload_result = upload_image_fallback($_FILES['profile_image'], $upload_dir);
            }

            if ($upload_result['success']) {
                $update_data['profile_image'] = str_replace('../', '', $upload_result['file_path']);

                // Delete old profile image if exists (and it's not the default)
                if (
                    !empty($user_data['profile_image']) &&
                    file_exists('../' . $user_data['profile_image']) &&
                    strpos($user_data['profile_image'], 'default.png') === false &&
                    strpos($user_data['profile_image'], 'default-avatar.png') === false
                ) {
                    @unlink('../' . $user_data['profile_image']);
                }
            } else {
                $errors[] = 'Profile image upload failed: ' . implode(', ', $upload_result['errors']);
            }
        }

        if (empty($errors)) {
            try {
                if ($user->updateProfile($_SESSION['user_id'], $update_data)) {
                    $success = 'Profile updated successfully!';
                    $user_data = $user->getUserById($_SESSION['user_id']); // Refresh data
                } else {
                    $errors[] = 'Failed to update profile.';
                }
            } catch (Exception $e) {
                $errors[] = $e->getMessage();
            }
        }
    } elseif (isset($_POST['change_password'])) {
        // Change password
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $errors[] = 'All password fields are required.';
        } elseif ($new_password !== $confirm_password) {
            $errors[] = 'New passwords do not match.';
        } elseif (strlen($new_password) < 6) {
            $errors[] = 'New password must be at least 6 characters long.';
        } else {
            try {
                if ($user->changePassword($_SESSION['user_id'], $current_password, $new_password)) {
                    $success = 'Password changed successfully!';
                } else {
                    $errors[] = 'Failed to change password.';
                }
            } catch (Exception $e) {
                $errors[] = $e->getMessage();
            }
        }
    }
}

// Get cart count
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
    <?php
    // Set $current_user for navbar
    $current_user = $user_data;
    $nav_path = '../public/includes/navbar.php';
    if (file_exists($nav_path)) {
        include $nav_path;
    } else {
        // Fallback navbar
        echo '<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
            <div class="container">
                <a class="navbar-brand" href="../public/index.php">
                    <i class="fas fa-shoe-prints"></i> ' . SITE_NAME . '
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item">
                            <a class="nav-link" href="../public/shop.php">Shop</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link position-relative" href="../public/cart.php">
                                Cart
                                ' . ($cart_count > 0 ? '<span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">' . $cart_count . '</span>' : '') . '
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="index.php">Dashboard</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-danger" href="../public/logout.php">Logout</a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>';
    }
    ?>

    <!-- Dashboard Layout -->
    <div class="container-fluid py-4">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-lg-3">
                <?php
                $sidebar_path = 'includes/sidebar.php';
                if (file_exists($sidebar_path)) {
                    include $sidebar_path;
                } else {
                    // Fallback sidebar
                    echo '<div class="card shadow-sm">
                        <div class="card-header bg-primary text-white">
                            <h6 class="mb-0"><i class="fas fa-bars me-2"></i>Dashboard Menu</h6>
                        </div>
                        <div class="list-group list-group-flush">
                            <a href="index.php" class="list-group-item list-group-item-action">
                                <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                            </a>
                            <a href="orders.php" class="list-group-item list-group-item-action">
                                <i class="fas fa-shopping-bag me-2"></i>My Orders
                            </a>
                            <a href="wishlist.php" class="list-group-item list-group-item-action">
                                <i class="fas fa-heart me-2"></i>Wishlist
                            </a>
                            <a href="profile.php" class="list-group-item list-group-item-action active">
                                <i class="fas fa-user me-2"></i>My Profile
                            </a>
                            <a href="addresses.php" class="list-group-item list-group-item-action">
                                <i class="fas fa-address-book me-2"></i>Address Book
                            </a>
                            <a href="payment-methods.php" class="list-group-item list-group-item-action">
                                <i class="fas fa-credit-card me-2"></i>Payment Methods
                            </a>
                            <a href="../public/logout.php" class="list-group-item list-group-item-action text-danger">
                                <i class="fas fa-sign-out-alt me-2"></i>Logout
                            </a>
                        </div>
                    </div>';
                }
                ?>
            </div>

            <!-- Main Content -->
            <div class="col-lg-9">
                <!-- Page Header -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2 class="mb-1">My Profile</h2>
                        <p class="text-muted mb-0">Manage your account information and settings</p>
                    </div>
                </div>

                <!-- Success/Error Messages -->
                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show mb-4">
                        <i class="fas fa-check-circle me-2"></i>
                        <?php echo $success; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger alert-dismissible fade show mb-4">
                        <h5><i class="fas fa-exclamation-triangle me-2"></i>Please fix the following errors:</h5>
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo $error; ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="row">
                    <!-- Profile Information -->
                    <div class="col-lg-8">
                        <!-- Personal Information -->
                        <div class="card shadow-sm mb-4">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0"><i class="fas fa-user me-2"></i>Personal Information</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" enctype="multipart/form-data" id="profile-form">
                                    <div class="row">
                                        <!-- Profile Image -->
                                        <div class="col-md-3 text-center mb-4">
                                            <div class="profile-image-container mb-3">
                                                <?php
                                                $avatar = get_user_avatar($_SESSION['user_id'], $user_data['profile_image'] ?? '');
                                                if (!empty($user_data['profile_image']) && file_exists('../' . $user_data['profile_image'])) {
                                                    $avatar = '../' . $user_data['profile_image'];
                                                } elseif (file_exists('../assets/images/users/default.png')) {
                                                    $avatar = '../assets/images/users/default.png';
                                                } elseif (file_exists('../assets/images/users/default-avatar.png')) {
                                                    $avatar = '../assets/images/users/default-avatar.png';
                                                } else {
                                                    $avatar = 'https://ui-avatars.com/api/?name=' . urlencode(($user_data['first_name'] ?? '') . '+' . ($user_data['last_name'] ?? '')) . '&background=random';
                                                }
                                                ?>
                                                <img id="profile-image-preview"
                                                    src="<?php echo $avatar; ?>"
                                                    alt="Profile"
                                                    class="rounded-circle img-thumbnail"
                                                    style="width: 150px; height: 150px; object-fit: cover;">
                                            </div>
                                            <div class="mb-3">
                                                <label for="profile_image" class="btn btn-outline-primary btn-sm">
                                                    <i class="fas fa-camera me-2"></i>Change Photo
                                                    <input type="file" id="profile_image" name="profile_image"
                                                        accept="image/*" class="d-none" onchange="previewImage(this)">
                                                </label>
                                            </div>
                                            <small class="text-muted">JPEG, PNG or GIF. Max 5MB.</small>
                                        </div>

                                        <!-- Form Fields -->
                                        <div class="col-md-9">
                                            <div class="row">
                                                <div class="col-md-6 mb-3">
                                                    <label for="first_name" class="form-label">First Name *</label>
                                                    <input type="text" class="form-control" id="first_name" name="first_name"
                                                        value="<?php echo htmlspecialchars($user_data['first_name'] ?? ''); ?>" required>
                                                </div>

                                                <div class="col-md-6 mb-3">
                                                    <label for="last_name" class="form-label">Last Name *</label>
                                                    <input type="text" class="form-control" id="last_name" name="last_name"
                                                        value="<?php echo htmlspecialchars($user_data['last_name'] ?? ''); ?>" required>
                                                </div>

                                                <div class="col-md-6 mb-3">
                                                    <label for="email" class="form-label">Email Address *</label>
                                                    <input type="email" class="form-control" id="email"
                                                        value="<?php echo htmlspecialchars($user_data['email'] ?? ''); ?>" disabled>
                                                    <small class="text-muted">Email cannot be changed</small>
                                                </div>

                                                <div class="col-md-6 mb-3">
                                                    <label for="phone" class="form-label">Phone Number *</label>
                                                    <input type="tel" class="form-control" id="phone" name="phone"
                                                        value="<?php echo htmlspecialchars($user_data['phone'] ?? ''); ?>"
                                                        pattern="\+251[0-9]{9}" placeholder="+251911234567" required>
                                                    <small class="text-muted">Ethiopian format: +251911234567</small>
                                                </div>

                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label">Account Type</label>
                                                    <input type="text" class="form-control" value="Buyer" disabled>
                                                </div>

                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label">Member Since</label>
                                                    <input type="text" class="form-control"
                                                        value="<?php echo format_date($user_data['created_at'] ?? '', 'F j, Y'); ?>" disabled>
                                                </div>
                                            </div>

                                            <!-- Submit Button -->
                                            <div class="mt-4">
                                                <button type="submit" name="update_profile" class="btn btn-primary">
                                                    <i class="fas fa-save me-2"></i>Update Profile
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- Change Password -->
                        <div class="card shadow-sm">
                            <div class="card-header bg-warning text-white">
                                <h5 class="mb-0"><i class="fas fa-lock me-2"></i>Change Password</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" id="password-form">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="current_password" class="form-label">Current Password *</label>
                                            <div class="input-group">
                                                <input type="password" class="form-control" id="current_password" name="current_password" required>
                                                <button class="btn btn-outline-secondary" type="button" id="toggle-current-password">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            </div>
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label for="new_password" class="form-label">New Password *</label>
                                            <div class="input-group">
                                                <input type="password" class="form-control" id="new_password" name="new_password" required>
                                                <button class="btn btn-outline-secondary" type="button" id="toggle-new-password">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            </div>
                                            <small class="text-muted">Minimum 6 characters</small>
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label for="confirm_password" class="form-label">Confirm New Password *</label>
                                            <div class="input-group">
                                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                                <button class="btn btn-outline-secondary" type="button" id="toggle-confirm-password">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <!-- Password Strength Meter -->
                                            <div class="mb-3">
                                                <label class="form-label">Password Strength</label>
                                                <div class="progress mb-2" style="height: 5px;">
                                                    <div id="password-strength-bar" class="progress-bar" role="progressbar" style="width: 0%"></div>
                                                </div>
                                                <small id="password-strength-text" class="text-muted"></small>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Password Requirements -->
                                    <div class="alert alert-info">
                                        <h6><i class="fas fa-info-circle me-2"></i>Password Requirements:</h6>
                                        <ul class="mb-0 small">
                                            <li>At least 6 characters long</li>
                                            <li>Include uppercase and lowercase letters</li>
                                            <li>Include numbers for better security</li>
                                            <li>Include special characters for maximum security</li>
                                        </ul>
                                    </div>

                                    <!-- Submit Button -->
                                    <div class="mt-4">
                                        <button type="submit" name="change_password" class="btn btn-warning">
                                            <i class="fas fa-key me-2"></i>Change Password
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Account Status & Stats -->
                    <div class="col-lg-4">
                        <!-- Account Status -->
                        <div class="card shadow-sm mb-4">
                            <div class="card-header bg-success text-white">
                                <h5 class="mb-0"><i class="fas fa-user-check me-2"></i>Account Status</h5>
                            </div>
                            <div class="card-body">
                                <div class="text-center mb-4">
                                    <div class="mb-3">
                                        <i class="fas fa-user-circle fa-4x text-success"></i>
                                    </div>
                                    <h5><?php echo htmlspecialchars(($user_data['first_name'] ?? '') . ' ' . ($user_data['last_name'] ?? '')); ?></h5>
                                    <p class="text-muted mb-2">Buyer Account</p>
                                    <span class="badge bg-success">Active</span>
                                </div>

                                <div class="list-group list-group-flush">
                                    <div class="list-group-item d-flex justify-content-between">
                                        <span>Email Verified</span>
                                        <span class="text-success">
                                            <i class="fas fa-check-circle"></i> Yes
                                        </span>
                                    </div>
                                    <div class="list-group-item d-flex justify-content-between">
                                        <span>Phone Verified</span>
                                        <span class="text-success">
                                            <i class="fas fa-check-circle"></i> Yes
                                        </span>
                                    </div>
                                    <div class="list-group-item d-flex justify-content-between">
                                        <span>Last Login</span>
                                        <span><?php echo !empty($user_data['last_login']) ? format_date($user_data['last_login']) : 'Never'; ?></span>
                                    </div>
                                    <div class="list-group-item d-flex justify-content-between">
                                        <span>Account Created</span>
                                        <span><?php echo format_date($user_data['created_at'] ?? '', 'M d, Y'); ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Quick Stats -->
                        <div class="card shadow-sm mb-4">
                            <div class="card-header bg-info text-white">
                                <h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Quick Stats</h5>
                            </div>
                            <div class="card-body">
                                <?php
                                $recent_orders = [];
                                $wishlist_count = 0;
                                $pending_count = 0;
                                $delivered_count = 0;

                                if (class_exists('Order')) {
                                    $order = new Order();
                                    $recent_orders = $order->getOrdersByBuyer($_SESSION['user_id']);

                                    foreach ($recent_orders as $order_item) {
                                        if (($order_item['status'] ?? '') === 'pending') $pending_count++;
                                        if (($order_item['status'] ?? '') === 'delivered') $delivered_count++;
                                    }
                                }

                                $wishlist_count = !empty($_SESSION['wishlist']) ? count($_SESSION['wishlist']) : 0;
                                ?>
                                <div class="row text-center">
                                    <div class="col-6 mb-3">
                                        <div class="display-6 fw-bold text-primary">
                                            <?php echo count($recent_orders); ?>
                                        </div>
                                        <small class="text-muted">Total Orders</small>
                                    </div>
                                    <div class="col-6 mb-3">
                                        <div class="display-6 fw-bold text-success">
                                            <?php echo $wishlist_count; ?>
                                        </div>
                                        <small class="text-muted">Wishlist Items</small>
                                    </div>
                                    <div class="col-6">
                                        <div class="display-6 fw-bold text-warning">
                                            <?php echo $pending_count; ?>
                                        </div>
                                        <small class="text-muted">Pending Orders</small>
                                    </div>
                                    <div class="col-6">
                                        <div class="display-6 fw-bold text-info">
                                            <?php echo $delivered_count; ?>
                                        </div>
                                        <small class="text-muted">Delivered</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Account Actions -->
                        <div class="card shadow-sm">
                            <div class="card-header bg-light">
                                <h5 class="mb-0"><i class="fas fa-cog me-2"></i>Account Actions</h5>
                            </div>
                            <div class="card-body">
                                <div class="d-grid gap-2">
                                    <a href="../public/logout.php" class="btn btn-outline-danger">
                                        <i class="fas fa-sign-out-alt me-2"></i>Logout
                                    </a>
                                    <button type="button" class="btn btn-outline-warning" data-bs-toggle="modal" data-bs-target="#deleteAccountModal">
                                        <i class="fas fa-trash-alt me-2"></i>Delete Account
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Account Modal -->
    <div class="modal fade" id="deleteAccountModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title"><i class="fas fa-exclamation-triangle me-2"></i>Delete Account</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-danger">
                        <h6><i class="fas fa-exclamation-circle me-2"></i>Warning: This action cannot be undone!</h6>
                        <p class="mb-0">Deleting your account will:</p>
                        <ul class="mb-0">
                            <li>Permanently delete your profile</li>
                            <li>Cancel any pending orders</li>
                            <li>Remove all your data from our system</li>
                            <li>Delete your wishlist and saved items</li>
                        </ul>
                    </div>
                    <p>Are you sure you want to delete your account? This action is permanent.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirm-delete-account">
                        <i class="fas fa-trash-alt me-2"></i>Yes, Delete My Account
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <?php
    $footer_path = '../public/includes/footer.php';
    if (file_exists($footer_path)) {
        include $footer_path;
    } else {
        echo '<footer class="bg-dark text-white py-4 mt-5">
            <div class="container">
                <div class="row">
                    <div class="col-md-6">
                        <p class="mb-0">&copy; ' . date('Y') . ' ' . SITE_NAME . '. All rights reserved.</p>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <a href="../public/contact.php" class="text-white text-decoration-none me-3">Contact</a>
                        <a href="../public/terms.php" class="text-white text-decoration-none me-3">Terms</a>
                        <a href="../public/privacy.php" class="text-white text-decoration-none">Privacy</a>
                    </div>
                </div>
            </div>
        </footer>';
    }
    ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Toggle password visibility
        document.getElementById('toggle-current-password').addEventListener('click', function() {
            const input = document.getElementById('current_password');
            const icon = this.querySelector('i');
            togglePasswordVisibility(input, icon);
        });

        document.getElementById('toggle-new-password').addEventListener('click', function() {
            const input = document.getElementById('new_password');
            const icon = this.querySelector('i');
            togglePasswordVisibility(input, icon);
        });

        document.getElementById('toggle-confirm-password').addEventListener('click', function() {
            const input = document.getElementById('confirm_password');
            const icon = this.querySelector('i');
            togglePasswordVisibility(input, icon);
        });

        function togglePasswordVisibility(input, icon) {
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        // Password strength checker
        document.getElementById('new_password').addEventListener('input', function() {
            const password = this.value;
            const strengthBar = document.getElementById('password-strength-bar');
            const strengthText = document.getElementById('password-strength-text');

            let strength = 0;
            let text = '';
            let color = '';

            // Length check
            if (password.length >= 6) strength += 25;
            if (password.length >= 8) strength += 10;
            if (password.length >= 12) strength += 15;

            // Character type checks
            if (/[A-Z]/.test(password)) strength += 20;
            if (/[a-z]/.test(password)) strength += 20;
            if (/[0-9]/.test(password)) strength += 20;
            if (/[^A-Za-z0-9]/.test(password)) strength += 20;

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

        // Profile image preview
        function previewImage(input) {
            const preview = document.getElementById('profile-image-preview');
            const file = input.files[0];

            if (file) {
                // Check file size (max 5MB)
                if (file.size > 5 * 1024 * 1024) {
                    alert('File size must be less than 5MB');
                    input.value = '';
                    return;
                }

                // Check file type
                const validTypes = ['image/jpeg', 'image/png', 'image/gif'];
                if (!validTypes.includes(file.type)) {
                    alert('Only JPEG, PNG, and GIF files are allowed');
                    input.value = '';
                    return;
                }

                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                }
                reader.readAsDataURL(file);
            }
        }

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
        document.getElementById('profile-form').addEventListener('submit', function(e) {
            const phone = document.getElementById('phone').value;
            const phoneRegex = /^\+251[0-9]{9}$/;

            if (!phoneRegex.test(phone)) {
                e.preventDefault();
                alert('Please enter a valid Ethiopian phone number (format: +251911234567).');
                return;
            }
        });

        document.getElementById('password-form').addEventListener('submit', function(e) {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const currentPassword = document.getElementById('current_password').value;

            if (currentPassword.length === 0) {
                e.preventDefault();
                alert('Current password is required.');
                return;
            }

            if (newPassword.length < 6) {
                e.preventDefault();
                alert('New password must be at least 6 characters long.');
                return;
            }

            if (newPassword !== confirmPassword) {
                e.preventDefault();
                alert('New passwords do not match.');
                return;
            }
        });

        // Delete account confirmation
        document.getElementById('confirm-delete-account').addEventListener('click', function() {
            if (confirm('Are you absolutely sure? This action cannot be undone!')) {
                // In production, you would make an AJAX call or redirect to delete account page
                alert('Account deletion would be processed here. In production, this would delete your account.');
                const modal = bootstrap.Modal.getInstance(document.getElementById('deleteAccountModal'));
                modal.hide();
            }
        });
    </script>
</body>

</html>