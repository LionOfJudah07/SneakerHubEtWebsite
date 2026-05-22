<?php
require_once '../config.php';

// Require admin login
require_admin();

// Get user ID from URL
$user_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($user_id <= 0) {
    header('Location: users.php');
    exit();
}

$db = new Database();

// Get user details
$db->query("SELECT * FROM users WHERE id = :id");
$db->bind(':id', $user_id);
$user = $db->single();

if (!$user) {
    $_SESSION['error'] = 'User not found!';
    header('Location: users.php');
    exit();
}

$page_title = 'Edit User - ' . SITE_NAME;

$errors = [];
$success = false;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $first_name = sanitize_input($_POST['first_name'] ?? '');
    $last_name = sanitize_input($_POST['last_name'] ?? '');
    $email = sanitize_input($_POST['email'] ?? '');
    $phone = sanitize_input($_POST['phone'] ?? '');
    $date_of_birth = sanitize_input($_POST['date_of_birth'] ?? '');
    $user_type = sanitize_input($_POST['user_type'] ?? 'buyer');
    $status = sanitize_input($_POST['status'] ?? 'active');
    $email_verified = isset($_POST['email_verified']) ? 1 : 0;

    // Validate required fields
    if (empty($first_name)) {
        $errors[] = 'First name is required';
    }

    if (empty($last_name)) {
        $errors[] = 'Last name is required';
    }

    if (empty($email)) {
        $errors[] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email format';
    } else {
        // Check if email already exists (excluding current user)
        $db->query("SELECT id FROM users WHERE email = :email AND id != :id");
        $db->bind(':email', $email);
        $db->bind(':id', $user_id);
        if ($db->single()) {
            $errors[] = 'Email already exists';
        }
    }

    if (!empty($phone) && !preg_match('/^[0-9+\-\s()]{10,20}$/', $phone)) {
        $errors[] = 'Invalid phone number format';
    }

    // If no errors, update user
    if (empty($errors)) {
        try {
            $db->beginTransaction();

            // Update user
            $db->query("UPDATE users SET 
                        first_name = :first_name,
                        last_name = :last_name,
                        email = :email,
                        phone = :phone,
                        date_of_birth = :date_of_birth,
                        user_type = :user_type,
                        status = :status,
                        email_verified = :email_verified,
                        updated_at = NOW()
                        WHERE id = :id");

            $db->bind(':first_name', $first_name);
            $db->bind(':last_name', $last_name);
            $db->bind(':email', $email);
            $db->bind(':phone', $phone);
            $db->bind(':date_of_birth', empty($date_of_birth) ? null : $date_of_birth);
            $db->bind(':user_type', $user_type);
            $db->bind(':status', $status);
            $db->bind(':email_verified', $email_verified, PDO::PARAM_INT);
            $db->bind(':id', $user_id);

            $db->execute();

            // If changing to/from vendor, handle vendor record
            if ($user_type === 'vendor' && $user['user_type'] !== 'vendor') {
                // Create vendor record if doesn't exist
                $db->query("SELECT id FROM vendors WHERE user_id = :user_id");
                $db->bind(':user_id', $user_id);
                if (!$db->single()) {
                    $db->query("INSERT INTO vendors (user_id, store_name, store_email, store_phone, created_at) 
                                VALUES (:user_id, :store_name, :store_email, :store_phone, NOW())");
                    $db->bind(':user_id', $user_id);
                    $db->bind(':store_name', $first_name . "'s Store");
                    $db->bind(':store_email', $email);
                    $db->bind(':store_phone', $phone);
                    $db->execute();
                }
            } elseif ($user_type !== 'vendor' && $user['user_type'] === 'vendor') {
                // Remove vendor record if user is no longer vendor
                // Note: We might want to keep vendor data for historical purposes
                // So we'll just update the user type but keep vendor record
            }

            $db->commit();

            $success = 'User updated successfully!';

            // Update user data for display
            $user['first_name'] = $first_name;
            $user['last_name'] = $last_name;
            $user['email'] = $email;
            $user['phone'] = $phone;
            $user['date_of_birth'] = $date_of_birth;
            $user['user_type'] = $user_type;
            $user['status'] = $status;
            $user['email_verified'] = $email_verified;
        } catch (Exception $e) {
            $db->rollBack();
            $errors[] = 'Failed to update user: ' . $e->getMessage();
        }
    }
}

// Get vendor info if user is vendor
$vendor_info = null;
if ($user['user_type'] === 'vendor') {
    $db->query("SELECT * FROM vendors WHERE user_id = :user_id");
    $db->bind(':user_id', $user_id);
    $vendor_info = $db->single();
}

// Helper function for avatar
if (!function_exists('get_user_avatar')) {
    function get_user_avatar($user_id, $profile_image)
    {
        if (!empty($profile_image) && file_exists('../' . $profile_image)) {
            return '../' . $profile_image;
        }
        return '../assets/images/users/default-avatar.png';
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Datepicker -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            padding-top: 0;
        }

        .sidebar {
            min-height: 100vh;
            background-color: #343a40;
            padding-top: 20px;
            position: fixed;
            top: 0;
            left: 0;
            width: 240px;
            overflow-y: auto;
            z-index: 1000;
        }

        .main-content {
            margin-left: 240px;
            padding: 20px;
        }

        @media (max-width: 768px) {
            .sidebar {
                position: static;
                width: 100%;
                min-height: auto;
            }

            .main-content {
                margin-left: 0;
            }
        }

        .user-avatar {
            width: 120px;
            height: 120px;
            border: 5px solid #dee2e6;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
        }

        .card-header {
            background-color: #fff;
            border-bottom: 1px solid #eee;
            font-weight: 600;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.25rem rgba(102, 126, 234, 0.25);
        }

        .sidebar .nav-link {
            color: #adb5bd;
            padding: 10px 20px;
            margin-bottom: 5px;
            border-radius: 5px;
            transition: all 0.3s;
        }

        .sidebar .nav-link:hover {
            color: #fff;
            background-color: #495057;
        }

        .sidebar .nav-link.active {
            color: #fff;
            background-color: #007bff;
        }

        .tab-content {
            padding: 20px;
            background: white;
            border-radius: 0 0 10px 10px;
        }

        .nav-tabs .nav-link {
            border: none;
            color: #495057;
            font-weight: 500;
            padding: 10px 20px;
        }

        .nav-tabs .nav-link.active {
            color: #667eea;
            border-bottom: 3px solid #667eea;
            background: transparent;
        }
    </style>
</head>

<body>
    <!-- Admin Sidebar -->
    <div class="sidebar d-print-none">
        <div class="position-sticky pt-3">
            <div class="text-center mb-4">
                <h4 class="text-white">
                    <i class="fas fa-shoe-prints"></i> <?php echo htmlspecialchars(SITE_NAME); ?>
                </h4>
                <p class="text-white-50 small">Admin Panel</p>
            </div>

            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link" href="index.php">
                        <i class="fas fa-tachometer-alt me-2"></i>
                        Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="products.php">
                        <i class="fas fa-box me-2"></i>
                        Products
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" href="users.php">
                        <i class="fas fa-users me-2"></i>
                        Users
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="orders.php">
                        <i class="fas fa-shopping-cart me-2"></i>
                        Orders
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="categories.php">
                        <i class="fas fa-tags me-2"></i>
                        Categories
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="settings.php">
                        <i class="fas fa-cog me-2"></i>
                        Settings
                    </a>
                </li>
            </ul>

            <hr class="bg-light my-4">

            <div class="px-3">
                <div class="d-flex align-items-center text-white mb-3">
                    <i class="fas fa-user-circle fa-2x me-2"></i>
                    <div>
                        <strong><?php echo htmlspecialchars($_SESSION['first_name'] ?? 'Admin'); ?></strong>
                        <div class="small text-muted">Administrator</div>
                    </div>
                </div>

                <div class="d-grid gap-2">
                    <a href="../public/index.php" class="btn btn-outline-light btn-sm">
                        <i class="fas fa-home me-2"></i>View Site
                    </a>
                    <a href="../public/logout.php" class="btn btn-outline-danger btn-sm">
                        <i class="fas fa-sign-out-alt me-2"></i>Logout
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Page Header -->
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
            <h1 class="h2">Edit User</h1>
            <div class="btn-toolbar mb-2 mb-md-0">
                <a href="user-view.php?id=<?php echo $user_id; ?>" class="btn btn-sm btn-outline-secondary me-2">
                    <i class="fas fa-eye me-2"></i>View User
                </a>
                <a href="users.php" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Back to Users
                </a>
            </div>
        </div>

        <!-- Success/Error Messages -->
        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show mb-4">
                <i class="fas fa-check-circle me-2"></i>
                <?php echo htmlspecialchars($success); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger alert-dismissible fade show mb-4">
                <h5><i class="fas fa-exclamation-triangle me-2"></i>Please fix the following errors:</h5>
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-lg-4 mb-4">
                <!-- User Profile Card -->
                <div class="card">
                    <div class="card-body text-center">
                        <img src="<?php echo get_user_avatar($user['id'], $user['profile_image'] ?? ''); ?>"
                            alt="<?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>"
                            class="user-avatar rounded-circle img-fluid mb-3"
                            onerror="this.src='../assets/images/users/default-avatar.png'">

                        <h4><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h4>
                        <p class="text-muted mb-3"><?php echo htmlspecialchars($user['email']); ?></p>

                        <div class="mb-3">
                            <?php
                            $type_badges = [
                                'buyer' => 'badge bg-primary',
                                'vendor' => 'badge bg-success',
                                'admin' => 'badge bg-danger'
                            ];
                            $type_class = $type_badges[$user['user_type']] ?? 'badge bg-secondary';
                            ?>
                            <span class="<?php echo $type_class; ?> me-2">
                                <?php echo ucfirst($user['user_type']); ?>
                            </span>

                            <?php
                            $status_badges = [
                                'active' => 'badge bg-success',
                                'inactive' => 'badge bg-secondary',
                                'pending' => 'badge bg-warning'
                            ];
                            $status_class = $status_badges[$user['status']] ?? 'badge bg-secondary';
                            ?>
                            <span class="<?php echo $status_class; ?>">
                                <?php echo ucfirst($user['status']); ?>
                            </span>
                        </div>

                        <div class="text-muted small mb-3">
                            <p class="mb-1">
                                <i class="fas fa-calendar-plus me-2"></i>
                                Joined: <?php echo date('F d, Y', strtotime($user['created_at'])); ?>
                            </p>
                            <p class="mb-0">
                                <i class="fas fa-calendar-check me-2"></i>
                                Last Login: <?php echo !empty($user['last_login']) ? date('M d, Y H:i', strtotime($user['last_login'])) : 'Never'; ?>
                            </p>
                        </div>

                        <div class="d-grid gap-2 mt-4">
                            <a href="user-view.php?id=<?php echo $user_id; ?>" class="btn btn-outline-primary">
                                <i class="fas fa-eye me-2"></i>View Profile
                            </a>
                            <?php if ($user_id != $_SESSION['user_id']): ?>
                                <a href="users.php?delete=<?php echo $user_id; ?>"
                                    class="btn btn-outline-danger"
                                    onclick="return confirm('Are you sure you want to deactivate this user?')">
                                    <i class="fas fa-user-slash me-2"></i>Deactivate User
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Quick Stats -->
                <div class="card mt-4">
                    <div class="card-header">
                        <i class="fas fa-chart-bar me-2"></i>Quick Stats
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            <?php
                            // Get user stats
                            if ($user['user_type'] === 'buyer') {
                                $db->query("SELECT COUNT(*) as count FROM orders WHERE buyer_id = :user_id");
                                $db->bind(':user_id', $user_id);
                                $order_count = $db->single()['count'];

                                $db->query("SELECT COUNT(*) as count FROM addresses WHERE user_id = :user_id");
                                $db->bind(':user_id', $user_id);
                                $address_count = $db->single()['count'];

                                echo '<li class="list-group-item d-flex justify-content-between align-items-center">
                                        Orders <span class="badge bg-primary">' . $order_count . '</span>
                                      </li>';
                                echo '<li class="list-group-item d-flex justify-content-between align-items-center">
                                        Addresses <span class="badge bg-primary">' . $address_count . '</span>
                                      </li>';
                            } elseif ($user['user_type'] === 'vendor') {
                                $db->query("SELECT COUNT(*) as count FROM products WHERE vendor_id = :user_id AND status = 'active'");
                                $db->bind(':user_id', $user_id);
                                $product_count = $db->single()['count'];

                                $db->query("SELECT COUNT(*) as count FROM products WHERE vendor_id = :user_id");
                                $db->bind(':user_id', $user_id);
                                $total_products = $db->single()['count'];

                                echo '<li class="list-group-item d-flex justify-content-between align-items-center">
                                        Active Products <span class="badge bg-success">' . $product_count . '</span>
                                      </li>';
                                echo '<li class="list-group-item d-flex justify-content-between align-items-center">
                                        Total Products <span class="badge bg-secondary">' . $total_products . '</span>
                                      </li>';
                            }
                            ?>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <!-- Edit Form -->
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-edit me-2"></i>Edit User Information
                    </div>
                    <div class="card-body">
                        <form method="POST" id="editUserForm">
                            <!-- Basic Information -->
                            <h5 class="mb-4 pb-2 border-bottom">Basic Information</h5>
                            <div class="row mb-4">
                                <div class="col-md-6 mb-3">
                                    <label for="first_name" class="form-label">First Name *</label>
                                    <input type="text" class="form-control" id="first_name" name="first_name"
                                        value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="last_name" class="form-label">Last Name *</label>
                                    <input type="text" class="form-control" id="last_name" name="last_name"
                                        value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                                </div>
                                <div class="col-md-12 mb-3">
                                    <label for="email" class="form-label">Email Address *</label>
                                    <input type="email" class="form-control" id="email" name="email"
                                        value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="phone" class="form-label">Phone Number</label>
                                    <input type="tel" class="form-control" id="phone" name="phone"
                                        value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="date_of_birth" class="form-label">Date of Birth</label>
                                    <input type="text" class="form-control datepicker" id="date_of_birth" name="date_of_birth"
                                        value="<?php echo !empty($user['date_of_birth']) ? date('Y-m-d', strtotime($user['date_of_birth'])) : ''; ?>">
                                </div>
                            </div>

                            <!-- Account Settings -->
                            <h5 class="mb-4 pb-2 border-bottom">Account Settings</h5>
                            <div class="row mb-4">
                                <div class="col-md-6 mb-3">
                                    <label for="user_type" class="form-label">User Type *</label>
                                    <select class="form-select" id="user_type" name="user_type" required>
                                        <option value="buyer" <?php echo $user['user_type'] === 'buyer' ? 'selected' : ''; ?>>Buyer</option>
                                        <option value="vendor" <?php echo $user['user_type'] === 'vendor' ? 'selected' : ''; ?>>Vendor</option>
                                        <option value="admin" <?php echo $user['user_type'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="status" class="form-label">Account Status *</label>
                                    <select class="form-select" id="status" name="status" required>
                                        <option value="active" <?php echo $user['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                        <option value="inactive" <?php echo $user['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                        <option value="pending" <?php echo $user['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    </select>
                                </div>
                                <div class="col-md-12 mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="email_verified" name="email_verified"
                                            value="1" <?php echo $user['email_verified'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="email_verified">
                                            Email Verified
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- Vendor Information (only shown for vendors) -->
                            <?php if ($user['user_type'] === 'vendor' && $vendor_info): ?>
                                <div id="vendorInfoSection">
                                    <h5 class="mb-4 pb-2 border-bottom">Vendor Information</h5>
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle me-2"></i>
                                        Vendor information can be managed separately in the vendor management section.
                                    </div>
                                    <div class="row mb-4">
                                        <div class="col-md-12 mb-3">
                                            <label class="form-label">Store Name</label>
                                            <input type="text" class="form-control"
                                                value="<?php echo htmlspecialchars($vendor_info['store_name'] ?? ''); ?>"
                                                readonly>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Store Rating</label>
                                            <input type="text" class="form-control"
                                                value="<?php echo number_format($vendor_info['rating'] ?? 0, 1); ?>"
                                                readonly>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Total Sales</label>
                                            <input type="text" class="form-control"
                                                value="<?php echo $vendor_info['total_sales'] ?? 0; ?>"
                                                readonly>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <!-- Form Actions -->
                            <div class="d-flex justify-content-between mt-4 pt-3 border-top">
                                <a href="user-view.php?id=<?php echo $user_id; ?>" class="btn btn-outline-secondary">
                                    <i class="fas fa-times me-2"></i>Cancel
                                </a>
                                <div>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>Save Changes
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Danger Zone -->
                <?php if ($user_id != $_SESSION['user_id']): ?>
                    <div class="card mt-4 border-danger">
                        <div class="card-header bg-danger text-white">
                            <i class="fas fa-exclamation-triangle me-2"></i>Danger Zone
                        </div>
                        <div class="card-body">
                            <h5 class="text-danger">Deactivate User</h5>
                            <p class="text-muted">
                                Deactivating a user will prevent them from logging in and accessing their account.
                                Their data will be preserved but hidden from the system.
                            </p>
                            <a href="users.php?delete=<?php echo $user_id; ?>"
                                class="btn btn-danger"
                                onclick="return confirm('Are you absolutely sure you want to deactivate this user? This action cannot be undone.')">
                                <i class="fas fa-user-slash me-2"></i>Deactivate User
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Flatpickr for datepicker -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

    <script>
        // Initialize datepicker
        flatpickr('.datepicker', {
            dateFormat: 'Y-m-d',
            maxDate: 'today'
        });

        // Show/hide vendor info based on user type
        const userTypeSelect = document.getElementById('user_type');
        const vendorInfoSection = document.getElementById('vendorInfoSection');

        if (userTypeSelect && vendorInfoSection) {
            function toggleVendorInfo() {
                if (userTypeSelect.value === 'vendor') {
                    vendorInfoSection.style.display = 'block';
                } else {
                    vendorInfoSection.style.display = 'none';
                }
            }

            userTypeSelect.addEventListener('change', toggleVendorInfo);
            toggleVendorInfo(); // Initial call
        }

        // Form validation
        document.getElementById('editUserForm').addEventListener('submit', function(e) {
            const email = document.getElementById('email').value;
            const phone = document.getElementById('phone').value;

            // Email validation
            if (!isValidEmail(email)) {
                e.preventDefault();
                alert('Please enter a valid email address.');
                return;
            }

            // Phone validation (if provided)
            if (phone && !isValidPhone(phone)) {
                e.preventDefault();
                alert('Please enter a valid phone number (10-20 digits, can include +, -, spaces, parentheses).');
                return;
            }
        });

        function isValidEmail(email) {
            const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return re.test(email);
        }

        function isValidPhone(phone) {
            const re = /^[0-9+\-\s()]{10,20}$/;
            return re.test(phone);
        }
    </script>
</body>

</html>