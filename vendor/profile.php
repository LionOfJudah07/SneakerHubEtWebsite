<?php
require_once '../includes/config.php';
require_once '../classes/User.php';
require_once '../classes/Product.php';

// Check if vendor is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'vendor') {
    header('Location: ../public/login.php');
    exit();
}

$page_title = 'Vendor Profile - ' . SITE_NAME;

// Get vendor data
$vendor = new User();
$vendor_data = $vendor->getUserById($_SESSION['user_id']);

// Get vendor store info
$db = new Database();
$db->query("SELECT * FROM vendors WHERE user_id = :vendor_id");
$db->bind(':vendor_id', $_SESSION['user_id']);
$store_info = $db->single();

if (!$store_info) {
    // Create default store info
    $store_info = [
        'store_name' => $vendor_data['first_name'] . "'s Store",
        'store_description' => '',
        'store_logo' => '',
        'store_banner' => '',
        'store_address' => '',
        'store_phone' => $vendor_data['phone'],
        'store_email' => $vendor_data['email'],
        'store_social' => '{}',
        'rating' => 0,
        'total_sales' => 0
    ];
}

// Handle profile update
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        // Update vendor profile
        $update_data = [
            'first_name' => sanitize_input($_POST['first_name']),
            'last_name' => sanitize_input($_POST['last_name']),
            'phone' => sanitize_input($_POST['phone'])
        ];

        // Handle profile image upload
        if (!empty($_FILES['profile_image']['name'])) {
            $upload_result = upload_image($_FILES['profile_image'], '../assets/images/users/');
            if ($upload_result['success']) {
                $update_data['profile_image'] = $upload_result['file_path'];

                // Delete old profile image if exists
                if (!empty($vendor_data['profile_image']) && file_exists('../' . $vendor_data['profile_image'])) {
                    @unlink('../' . $vendor_data['profile_image']);
                }
            } else {
                $errors[] = 'Profile image upload failed: ' . implode(', ', $upload_result['errors']);
            }
        }

        if (empty($errors)) {
            try {
                $vendor->updateProfile($_SESSION['user_id'], $update_data);
                $vendor_data = $vendor->getUserById($_SESSION['user_id']); // Refresh data
                $success = 'Profile updated successfully!';
            } catch (Exception $e) {
                $errors[] = $e->getMessage();
            }
        }
    } elseif (isset($_POST['update_store'])) {
        // Update store information
        $store_data = [
            'store_name' => sanitize_input($_POST['store_name']),
            'store_description' => sanitize_input($_POST['store_description']),
            'store_address' => sanitize_input($_POST['store_address']),
            'store_phone' => sanitize_input($_POST['store_phone']),
            'store_email' => sanitize_input($_POST['store_email']),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        // Handle store logo upload
        if (!empty($_FILES['store_logo']['name'])) {
            $upload_result = upload_image($_FILES['store_logo'], '../assets/images/stores/');
            if ($upload_result['success']) {
                $store_data['store_logo'] = $upload_result['file_path'];

                // Delete old logo if exists
                if (!empty($store_info['store_logo']) && file_exists('../' . $store_info['store_logo'])) {
                    @unlink('../' . $store_info['store_logo']);
                }
            } else {
                $errors[] = 'Store logo upload failed: ' . implode(', ', $upload_result['errors']);
            }
        }

        // Handle store banner upload
        if (!empty($_FILES['store_banner']['name'])) {
            $upload_result = upload_image($_FILES['store_banner'], '../assets/images/stores/banners/');
            if ($upload_result['success']) {
                $store_data['store_banner'] = $upload_result['file_path'];

                // Delete old banner if exists
                if (!empty($store_info['store_banner']) && file_exists('../' . $store_info['store_banner'])) {
                    @unlink('../' . $store_info['store_banner']);
                }
            } else {
                $errors[] = 'Store banner upload failed: ' . implode(', ', $upload_result['errors']);
            }
        }

        // Social media links
        $social_data = [
            'facebook' => sanitize_input($_POST['facebook'] ?? ''),
            'instagram' => sanitize_input($_POST['instagram'] ?? ''),
            'telegram' => sanitize_input($_POST['telegram'] ?? ''),
            'twitter' => sanitize_input($_POST['twitter'] ?? '')
        ];
        $store_data['store_social'] = json_encode($social_data);

        if (empty($errors)) {
            try {
                // Check if store info exists
                $db = new Database();
                $db->query("SELECT id FROM vendors WHERE user_id = :vendor_id");
                $db->bind(':vendor_id', $_SESSION['user_id']);
                $existing_store = $db->single();

                if ($existing_store) {
                    // Update existing store
                    $update_condition = "user_id = :user_id";
                    $db->query("UPDATE vendors SET 
                                store_name = :store_name,
                                store_description = :store_description,
                                store_logo = :store_logo,
                                store_banner = :store_banner,
                                store_address = :store_address,
                                store_phone = :store_phone,
                                store_email = :store_email,
                                store_social = :store_social,
                                updated_at = :updated_at
                                WHERE user_id = :user_id");

                    $db->bind(':store_name', $store_data['store_name']);
                    $db->bind(':store_description', $store_data['store_description']);
                    $db->bind(':store_logo', $store_data['store_logo'] ?? $store_info['store_logo']);
                    $db->bind(':store_banner', $store_data['store_banner'] ?? $store_info['store_banner']);
                    $db->bind(':store_address', $store_data['store_address']);
                    $db->bind(':store_phone', $store_data['store_phone']);
                    $db->bind(':store_email', $store_data['store_email']);
                    $db->bind(':store_social', $store_data['store_social']);
                    $db->bind(':updated_at', $store_data['updated_at']);
                    $db->bind(':user_id', $_SESSION['user_id']);

                    $db->execute();
                } else {
                    // Create new store
                    $store_data['user_id'] = $_SESSION['user_id'];
                    $store_data['created_at'] = date('Y-m-d H:i:s');

                    $db->query("INSERT INTO vendors 
                                (user_id, store_name, store_description, store_logo, store_banner, store_address, store_phone, store_email, store_social, created_at, updated_at)
                                VALUES 
                                (:user_id, :store_name, :store_description, :store_logo, :store_banner, :store_address, :store_phone, :store_email, :store_social, :created_at, :updated_at)");

                    $db->bind(':user_id', $store_data['user_id']);
                    $db->bind(':store_name', $store_data['store_name']);
                    $db->bind(':store_description', $store_data['store_description']);
                    $db->bind(':store_logo', $store_data['store_logo'] ?? '');
                    $db->bind(':store_banner', $store_data['store_banner'] ?? '');
                    $db->bind(':store_address', $store_data['store_address']);
                    $db->bind(':store_phone', $store_data['store_phone']);
                    $db->bind(':store_email', $store_data['store_email']);
                    $db->bind(':store_social', $store_data['store_social']);
                    $db->bind(':created_at', $store_data['created_at']);
                    $db->bind(':updated_at', $store_data['updated_at']);

                    $db->execute();
                }

                $success = 'Store information updated successfully!';

                // Update local store info
                $db->query("SELECT * FROM vendors WHERE user_id = :vendor_id");
                $db->bind(':vendor_id', $_SESSION['user_id']);
                $store_info = $db->single();
            } catch (Exception $e) {
                $errors[] = 'Failed to update store information: ' . $e->getMessage();
            }
        }
    } elseif (isset($_POST['change_password'])) {
        // Change password
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $errors[] = 'All password fields are required.';
        } elseif ($new_password !== $confirm_password) {
            $errors[] = 'New passwords do not match.';
        } elseif (strlen($new_password) < 6) {
            $errors[] = 'New password must be at least 6 characters long.';
        } else {
            try {
                $vendor->changePassword($_SESSION['user_id'], $current_password, $new_password);
                $success = 'Password changed successfully!';
            } catch (Exception $e) {
                $errors[] = $e->getMessage();
            }
        }
    }
}

// Get cart count
$cart_count = get_cart_count();

// Decode social data safely
$social_data = [];
if (!empty($store_info['store_social'])) {
    $social_data = json_decode($store_info['store_social'], true);
    if (!is_array($social_data)) {
        $social_data = [];
    }
}

// Get store statistics
$total_products = 0;
$total_orders = 0;
$total_revenue = 0;

try {
    // Get total products
    $db->query("SELECT COUNT(*) as count FROM products WHERE vendor_id = :vendor_id AND status = 'active'");
    $db->bind(':vendor_id', $_SESSION['user_id']);
    $result = $db->single();
    $total_products = $result['count'] ?? 0;

    // Get total orders
    $db->query("SELECT COUNT(DISTINCT o.id) as count 
                FROM orders o 
                JOIN order_items oi ON o.id = oi.order_id 
                JOIN products p ON oi.product_id = p.id 
                WHERE p.vendor_id = :vendor_id 
                AND o.status IN ('completed', 'delivered')");
    $db->bind(':vendor_id', $_SESSION['user_id']);
    $result = $db->single();
    $total_orders = $result['count'] ?? 0;

    // Get total revenue
    $db->query("SELECT SUM(oi.price * oi.quantity) as total 
                FROM order_items oi 
                JOIN products p ON oi.product_id = p.id 
                JOIN orders o ON oi.order_id = o.id 
                WHERE p.vendor_id = :vendor_id 
                AND o.status IN ('completed', 'delivered')");
    $db->bind(':vendor_id', $_SESSION['user_id']);
    $result = $db->single();
    $total_revenue = $result['total'] ?? 0;
} catch (Exception $e) {
    error_log("Error getting vendor statistics: " . $e->getMessage());
    $total_products = 0;
    $total_orders = 0;
    $total_revenue = 0;
}

// Helper function for star rating (local only)
function get_star_rating_local($rating)
{
    $stars = '';
    $full_stars = floor($rating);
    $half_star = ($rating - $full_stars) >= 0.5;
    $empty_stars = 5 - $full_stars - ($half_star ? 1 : 0);

    for ($i = 0; $i < $full_stars; $i++) {
        $stars .= '<i class="fas fa-star"></i>';
    }

    if ($half_star) {
        $stars .= '<i class="fas fa-star-half-alt"></i>';
    }

    for ($i = 0; $i < $empty_stars; $i++) {
        $stars .= '<i class="far fa-star"></i>';
    }

    return $stars;
}

// Function to get user avatar
function get_user_avatar_local($user_id, $profile_image)
{
    if (!empty($profile_image) && file_exists('../' . $profile_image)) {
        return '../' . $profile_image;
    }
    return '../assets/images/users/default.jpg';
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

    <style>
        :root {
            --primary-green: #16a34a;
            --primary-dark: #0f7a35;
            --primary-light: #4ade80;
            --secondary: #6b7280;
            --success: #22c55e;
            --info: #14b8a6;
            --warning: #facc15;
            --danger: #ef4444;
            --light: #ffffff;
            --dark: #0b0f0e;
            --ethiopia-green: #078930;
            --ethiopia-yellow: #fcd116;
            --ethiopia-red: #da121a;
        }

        body {
            background-color: #f8f9fa;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
        }

        .sidebar {
            min-height: calc(100vh - 56px);
            background: var(--dark);
            padding-top: 20px;
        }

        .sidebar .nav-link {
            color: #adb5bd;
            padding: 0.75rem 1rem;
            border-radius: 0.375rem;
            margin: 0.25rem 0.5rem;
            transition: all 0.2s;
        }

        .sidebar .nav-link:hover {
            color: #fff;
            background-color: rgba(255, 255, 255, 0.1);
        }

        .sidebar .nav-link.active {
            color: #fff;
            background-color: var(--primary-green);
        }

        .profile-image-container {
            position: relative;
            display: inline-block;
            transition: transform 0.3s;
        }

        .profile-image-container:hover {
            transform: scale(1.05);
        }

        .store-logo {
            position: relative;
            z-index: 1;
            transition: transform 0.3s;
        }

        .store-logo:hover {
            transform: scale(1.1);
        }

        .store-banner {
            position: relative;
            overflow: hidden;
            border-radius: 12px;
            transition: transform 0.3s;
        }

        .store-banner:hover {
            transform: translateY(-5px);
        }

        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s, box-shadow 0.3s;
            overflow: hidden;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.15);
        }

        .card-header {
            background: linear-gradient(135deg, var(--primary-green), var(--primary-dark));
            color: white;
            border-bottom: none;
            padding: 1rem 1.5rem;
            font-weight: 600;
            font-size: 1.1rem;
        }

        .card-header.bg-success {
            background: linear-gradient(135deg, var(--success), #15803d) !important;
        }

        .card-header.bg-warning {
            background: linear-gradient(135deg, var(--warning), #ca8a04) !important;
        }

        .card-header.bg-info {
            background: linear-gradient(135deg, var(--info), #0d9488) !important;
        }

        .card-header.bg-primary {
            background: linear-gradient(135deg, #3b82f6, #1d4ed8) !important;
        }

        .list-group-item {
            border: none;
            padding: 0.75rem 0;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }

        .list-group-item:last-child {
            border-bottom: none;
        }

        .display-6 {
            font-size: 2.5rem;
            font-weight: 700;
            background: linear-gradient(45deg, var(--primary-green), var(--primary-dark));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .btn-outline-primary {
            color: var(--primary-green);
            border-color: var(--primary-green);
        }

        .btn-outline-primary:hover {
            background-color: var(--primary-green);
            border-color: var(--primary-green);
            color: white;
        }

        .btn-outline-success {
            color: var(--success);
            border-color: var(--success);
        }

        .btn-outline-success:hover {
            background-color: var(--success);
            border-color: var(--success);
            color: white;
        }

        .btn-outline-warning {
            color: var(--warning);
            border-color: var(--warning);
        }

        .btn-outline-warning:hover {
            background-color: var(--warning);
            border-color: var(--warning);
            color: #000;
        }

        .btn-outline-danger {
            color: var(--danger);
            border-color: var(--danger);
        }

        .btn-outline-danger:hover {
            background-color: var(--danger);
            border-color: var(--danger);
            color: white;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: var(--primary-light);
            box-shadow: 0 0 0 0.25rem rgba(22, 163, 74, 0.25);
        }

        .alert {
            border-radius: 10px;
            border: none;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .alert-success {
            background: linear-gradient(135deg, #d1fae5, #a7f3d0);
            color: #065f46;
            border-left: 4px solid var(--success);
        }

        .alert-danger {
            background: linear-gradient(135deg, #fee2e2, #fecaca);
            color: #991b1b;
            border-left: 4px solid var(--danger);
        }

        .stat-card {
            border-radius: 10px;
            border: none;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            transition: transform 0.2s, box-shadow 0.2s;
            overflow: hidden;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 0.35rem 1.75rem 0 rgba(58, 59, 69, 0.2);
        }

        .stat-icon {
            font-size: 2.5rem;
            opacity: 0.7;
        }

        .border-left-primary {
            border-left: 4px solid var(--primary-green) !important;
        }

        .border-left-success {
            border-left: 4px solid var(--success) !important;
        }

        .border-left-warning {
            border-left: 4px solid var(--warning) !important;
        }

        .border-left-info {
            border-left: 4px solid var(--info) !important;
        }

        .modal-header {
            background: linear-gradient(135deg, var(--danger), #dc2626);
            color: white;
        }

        .store-preview-card {
            border: 2px solid rgba(22, 163, 74, 0.2);
            transition: all 0.3s;
        }

        .store-preview-card:hover {
            border-color: var(--primary-green);
            box-shadow: 0 5px 15px rgba(22, 163, 74, 0.2);
        }

        @media (max-width: 768px) {
            .display-6 {
                font-size: 2rem;
            }

            .sidebar {
                min-height: auto;
                padding: 10px;
            }

            .card {
                margin-bottom: 1rem;
            }
        }

        .password-strength-meter {
            height: 5px;
            border-radius: 3px;
            overflow: hidden;
            margin-top: 5px;
        }

        .password-strength-meter .progress-bar {
            transition: width 0.3s ease;
        }

        .upload-btn {
            position: relative;
            overflow: hidden;
            transition: all 0.3s;
        }

        .upload-btn input[type="file"] {
            position: absolute;
            top: 0;
            right: 0;
            min-width: 100%;
            min-height: 100%;
            font-size: 100px;
            text-align: right;
            filter: alpha(opacity=0);
            opacity: 0;
            outline: none;
            background: white;
            cursor: inherit;
            display: block;
        }

        .tooltip-inner {
            background-color: var(--primary-green);
            border-radius: 6px;
            padding: 5px 10px;
        }

        .tooltip-arrow {
            border-top-color: var(--primary-green) !important;
        }

        .social-icon {
            width: 40px;
            height: 40px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: all 0.3s;
        }

        .social-icon:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
        }

        .social-icon.facebook {
            background-color: #1877f2;
            color: white;
        }

        .social-icon.instagram {
            background: linear-gradient(45deg, #405DE6, #5851DB, #833AB4, #C13584, #E1306C, #FD1D1D);
            color: white;
        }

        .social-icon.telegram {
            background-color: #0088cc;
            color: white;
        }

        .social-icon.twitter {
            background-color: #1DA1F2;
            color: white;
        }
    </style>
</head>

<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-store me-2"></i>Vendor Dashboard
            </a>
            <div class="ms-auto">
                <a href="index.php" class="btn btn-outline-light btn-sm me-2">
                    <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                </a>
                <a href="../public/logout.php" class="btn btn-outline-light btn-sm">
                    <i class="fas fa-sign-out-alt me-1"></i>Logout
                </a>
            </div>
        </div>
    </nav>

    <!-- Dashboard Layout -->
    <div class="container-fluid mt-5 pt-3">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 d-md-block sidebar collapse">
                <div class="position-sticky pt-3">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link text-white" href="index.php">
                                <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="products.php">
                                <i class="fas fa-box me-2"></i>Products
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="orders.php">
                                <i class="fas fa-shopping-cart me-2"></i>Orders
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="earnings.php">
                                <i class="fas fa-money-bill-wave me-2"></i>Earnings
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white active" href="profile.php">
                                <i class="fas fa-user me-2"></i>Profile
                            </a>
                        </li>
                        <li class="nav-item mt-4">
                            <a class="nav-link text-danger" href="../public/logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i>Logout
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <!-- Page Header -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h1 class="h2 mb-1"><i class="fas fa-user-circle me-2"></i>Vendor Profile</h1>
                        <p class="text-muted mb-0">Manage your personal information, store details, and security settings</p>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="index.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                        </a>
                        <button class="btn btn-success" onclick="window.print()">
                            <i class="fas fa-print me-2"></i>Print Profile
                        </button>
                    </div>
                </div>

                <!-- Success/Error Messages -->
                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show mb-4">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-check-circle fa-2x me-3"></i>
                            <div>
                                <h5 class="mb-1">Success!</h5>
                                <p class="mb-0"><?php echo htmlspecialchars($success); ?></p>
                            </div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger alert-dismissible fade show mb-4">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-exclamation-triangle fa-2x me-3"></i>
                            <div>
                                <h5 class="mb-2">Please fix the following errors:</h5>
                                <ul class="mb-0">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo htmlspecialchars($error); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="row">
                    <!-- Left Column: Forms -->
                    <div class="col-lg-8">
                        <!-- Personal Information Card -->
                        <div class="card shadow mb-4">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-user-edit me-2"></i>Personal Information</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" enctype="multipart/form-data" id="profile-form">
                                    <div class="row">
                                        <!-- Profile Image -->
                                        <div class="col-md-3 text-center mb-4">
                                            <div class="profile-image-container mb-3">
                                                <img id="profile-image-preview"
                                                    src="<?php echo get_user_avatar_local($_SESSION['user_id'], $vendor_data['profile_image'] ?? ''); ?>"
                                                    alt="Profile"
                                                    class="rounded-circle img-thumbnail shadow"
                                                    style="width: 150px; height: 150px; object-fit: cover; border: 3px solid var(--primary-green);">
                                            </div>
                                            <div class="mb-3">
                                                <label for="profile_image" class="btn btn-outline-primary upload-btn">
                                                    <i class="fas fa-camera me-2"></i>Change Photo
                                                    <input type="file" id="profile_image" name="profile_image"
                                                        accept="image/*" class="d-none" onchange="previewImage(this, 'profile-image-preview')">
                                                </label>
                                            </div>
                                            <small class="text-muted">Max 5MB. JPG, PNG, GIF</small>
                                        </div>

                                        <!-- Form Fields -->
                                        <div class="col-md-9">
                                            <div class="row">
                                                <div class="col-md-6 mb-3">
                                                    <label for="first_name" class="form-label fw-bold">
                                                        <i class="fas fa-user me-1"></i>First Name *
                                                    </label>
                                                    <input type="text" class="form-control" id="first_name" name="first_name"
                                                        value="<?php echo htmlspecialchars($vendor_data['first_name'] ?? ''); ?>"
                                                        required
                                                        placeholder="Enter your first name">
                                                </div>

                                                <div class="col-md-6 mb-3">
                                                    <label for="last_name" class="form-label fw-bold">
                                                        <i class="fas fa-user me-1"></i>Last Name *
                                                    </label>
                                                    <input type="text" class="form-control" id="last_name" name="last_name"
                                                        value="<?php echo htmlspecialchars($vendor_data['last_name'] ?? ''); ?>"
                                                        required
                                                        placeholder="Enter your last name">
                                                </div>

                                                <div class="col-md-6 mb-3">
                                                    <label for="email" class="form-label fw-bold">
                                                        <i class="fas fa-envelope me-1"></i>Email Address
                                                    </label>
                                                    <div class="input-group">
                                                        <input type="email" class="form-control" id="email"
                                                            value="<?php echo htmlspecialchars($vendor_data['email'] ?? ''); ?>"
                                                            disabled>
                                                        <span class="input-group-text bg-success text-white">
                                                            <i class="fas fa-check"></i> Verified
                                                        </span>
                                                    </div>
                                                    <small class="text-muted">Email cannot be changed</small>
                                                </div>

                                                <div class="col-md-6 mb-3">
                                                    <label for="phone" class="form-label fw-bold">
                                                        <i class="fas fa-phone me-1"></i>Phone Number *
                                                    </label>
                                                    <div class="input-group">
                                                        <span class="input-group-text bg-primary text-white">
                                                            <i class="fas fa-phone-alt"></i>
                                                        </span>
                                                        <input type="tel" class="form-control" id="phone" name="phone"
                                                            value="<?php echo htmlspecialchars($vendor_data['phone'] ?? ''); ?>"
                                                            pattern="\+251[0-9]{9}"
                                                            placeholder="+251911234567"
                                                            required>
                                                    </div>
                                                    <small class="text-muted">Ethiopian format: +251911234567</small>
                                                </div>

                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label fw-bold">
                                                        <i class="fas fa-user-tag me-1"></i>Account Type
                                                    </label>
                                                    <div class="input-group">
                                                        <span class="input-group-text bg-warning">
                                                            <i class="fas fa-store"></i>
                                                        </span>
                                                        <input type="text" class="form-control" value="Vendor" disabled>
                                                    </div>
                                                </div>

                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label fw-bold">
                                                        <i class="fas fa-calendar-alt me-1"></i>Member Since
                                                    </label>
                                                    <div class="input-group">
                                                        <span class="input-group-text bg-info text-white">
                                                            <i class="fas fa-clock"></i>
                                                        </span>
                                                        <input type="text" class="form-control"
                                                            value="<?php echo format_date($vendor_data['created_at'] ?? ''); ?>"
                                                            disabled>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Submit Button -->
                                            <div class="mt-4">
                                                <button type="submit" name="update_profile" class="btn btn-primary btn-lg px-4">
                                                    <i class="fas fa-save me-2"></i>Update Profile Information
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- Store Information Card -->
                        <div class="card shadow mb-4">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-store me-2"></i>Store Information</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" enctype="multipart/form-data" id="store-form">
                                    <div class="row">
                                        <!-- Store Logo & Banner -->
                                        <div class="col-md-3 text-center mb-4">
                                            <div class="store-logo-container mb-3">
                                                <img id="store-logo-preview"
                                                    src="<?php echo !empty($store_info['store_logo']) ? '../' . htmlspecialchars($store_info['store_logo']) : '../assets/images/stores/default-logo.png'; ?>"
                                                    alt="Store Logo"
                                                    class="img-thumbnail shadow"
                                                    style="width: 150px; height: 150px; object-fit: cover; border: 3px solid var(--success);">
                                            </div>
                                            <div class="mb-3">
                                                <label for="store_logo" class="btn btn-outline-success upload-btn">
                                                    <i class="fas fa-camera me-2"></i>Change Logo
                                                    <input type="file" id="store_logo" name="store_logo"
                                                        accept="image/*" class="d-none" onchange="previewImage(this, 'store-logo-preview')">
                                                </label>
                                            </div>
                                            <small class="text-muted">Square logo. Max 2MB</small>
                                        </div>

                                        <div class="col-md-9">
                                            <!-- Store Banner -->
                                            <div class="mb-4">
                                                <label for="store_banner" class="form-label fw-bold">
                                                    <i class="fas fa-images me-1"></i>Store Banner
                                                </label>
                                                <div class="input-group mb-2">
                                                    <input type="file" class="form-control" id="store_banner" name="store_banner"
                                                        accept="image/*" onchange="previewImage(this, 'store-banner-preview')">
                                                    <label class="input-group-text" for="store_banner">
                                                        <i class="fas fa-upload"></i>
                                                    </label>
                                                </div>
                                                <small class="text-muted">Banner image for your store page. Max 5MB.</small>
                                                <?php if (!empty($store_info['store_banner'])): ?>
                                                    <img id="store-banner-preview"
                                                        src="../<?php echo htmlspecialchars($store_info['store_banner']); ?>"
                                                        alt="Store Banner"
                                                        class="img-thumbnail mt-2 shadow"
                                                        style="width: 100%; height: 150px; object-fit: cover; display: block;">
                                                <?php else: ?>
                                                    <img id="store-banner-preview"
                                                        src=""
                                                        alt="Store Banner"
                                                        class="img-thumbnail mt-2 shadow"
                                                        style="width: 100%; height: 150px; object-fit: cover; display: none;">
                                                <?php endif; ?>
                                            </div>

                                            <!-- Store Details -->
                                            <div class="row">
                                                <div class="col-md-12 mb-3">
                                                    <label for="store_name" class="form-label fw-bold">
                                                        <i class="fas fa-signature me-1"></i>Store Name *
                                                    </label>
                                                    <input type="text" class="form-control" id="store_name" name="store_name"
                                                        value="<?php echo htmlspecialchars($store_info['store_name'] ?? ''); ?>"
                                                        required
                                                        placeholder="Enter your store name">
                                                </div>

                                                <div class="col-md-12 mb-3">
                                                    <label for="store_description" class="form-label fw-bold">
                                                        <i class="fas fa-align-left me-1"></i>Store Description
                                                    </label>
                                                    <textarea class="form-control" id="store_description" name="store_description"
                                                        rows="3" placeholder="Describe your store to customers..."><?php echo htmlspecialchars($store_info['store_description'] ?? ''); ?></textarea>
                                                    <small class="text-muted">Tell customers about your store</small>
                                                </div>

                                                <div class="col-md-6 mb-3">
                                                    <label for="store_address" class="form-label fw-bold">
                                                        <i class="fas fa-map-marker-alt me-1"></i>Store Address
                                                    </label>
                                                    <input type="text" class="form-control" id="store_address" name="store_address"
                                                        value="<?php echo htmlspecialchars($store_info['store_address'] ?? ''); ?>"
                                                        placeholder="Enter store address">
                                                </div>

                                                <div class="col-md-6 mb-3">
                                                    <label for="store_phone" class="form-label fw-bold">
                                                        <i class="fas fa-phone me-1"></i>Store Phone
                                                    </label>
                                                    <input type="tel" class="form-control" id="store_phone" name="store_phone"
                                                        value="<?php echo htmlspecialchars($store_info['store_phone'] ?? ''); ?>"
                                                        pattern="\+251[0-9]{9}"
                                                        placeholder="+251911234567">
                                                </div>

                                                <div class="col-md-6 mb-3">
                                                    <label for="store_email" class="form-label fw-bold">
                                                        <i class="fas fa-envelope me-1"></i>Store Email
                                                    </label>
                                                    <input type="email" class="form-control" id="store_email" name="store_email"
                                                        value="<?php echo htmlspecialchars($store_info['store_email'] ?? ''); ?>"
                                                        placeholder="store@example.com">
                                                </div>
                                            </div>

                                            <!-- Social Media Section -->
                                            <div class="mt-4">
                                                <h5 class="mb-3"><i class="fas fa-share-alt me-2"></i>Social Media Links</h5>
                                                <div class="row g-3">
                                                    <div class="col-md-6">
                                                        <div class="input-group">
                                                            <span class="input-group-text social-icon facebook">
                                                                <i class="fab fa-facebook-f"></i>
                                                            </span>
                                                            <input type="url" class="form-control" id="facebook" name="facebook"
                                                                placeholder="https://facebook.com/yourstore"
                                                                value="<?php echo htmlspecialchars($social_data['facebook'] ?? ''); ?>">
                                                        </div>
                                                    </div>

                                                    <div class="col-md-6">
                                                        <div class="input-group">
                                                            <span class="input-group-text social-icon instagram">
                                                                <i class="fab fa-instagram"></i>
                                                            </span>
                                                            <input type="url" class="form-control" id="instagram" name="instagram"
                                                                placeholder="https://instagram.com/yourstore"
                                                                value="<?php echo htmlspecialchars($social_data['instagram'] ?? ''); ?>">
                                                        </div>
                                                    </div>

                                                    <div class="col-md-6">
                                                        <div class="input-group">
                                                            <span class="input-group-text social-icon telegram">
                                                                <i class="fab fa-telegram-plane"></i>
                                                            </span>
                                                            <input type="url" class="form-control" id="telegram" name="telegram"
                                                                placeholder="https://t.me/yourstore"
                                                                value="<?php echo htmlspecialchars($social_data['telegram'] ?? ''); ?>">
                                                        </div>
                                                    </div>

                                                    <div class="col-md-6">
                                                        <div class="input-group">
                                                            <span class="input-group-text social-icon twitter">
                                                                <i class="fab fa-twitter"></i>
                                                            </span>
                                                            <input type="url" class="form-control" id="twitter" name="twitter"
                                                                placeholder="https://twitter.com/yourstore"
                                                                value="<?php echo htmlspecialchars($social_data['twitter'] ?? ''); ?>">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Submit Button -->
                                            <div class="mt-4">
                                                <button type="submit" name="update_store" class="btn btn-success btn-lg px-4">
                                                    <i class="fas fa-save me-2"></i>Update Store Information
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- Change Password Card -->
                        <div class="card shadow">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-key me-2"></i>Change Password</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" id="password-form">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label for="current_password" class="form-label fw-bold">
                                                <i class="fas fa-lock me-1"></i>Current Password *
                                            </label>
                                            <div class="input-group">
                                                <input type="password" class="form-control" id="current_password" name="current_password" required>
                                                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('current_password', this)">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <label for="new_password" class="form-label fw-bold">
                                                <i class="fas fa-lock me-1"></i>New Password *
                                            </label>
                                            <div class="input-group">
                                                <input type="password" class="form-control" id="new_password" name="new_password" required>
                                                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('new_password', this)">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            </div>
                                            <div class="password-strength-meter mt-2">
                                                <div class="progress" style="height: 5px;">
                                                    <div id="password-strength-bar" class="progress-bar" role="progressbar" style="width: 0%"></div>
                                                </div>
                                            </div>
                                            <small id="password-strength-text" class="text-muted">Password strength</small>
                                        </div>

                                        <div class="col-md-6">
                                            <label for="confirm_password" class="form-label fw-bold">
                                                <i class="fas fa-lock me-1"></i>Confirm New Password *
                                            </label>
                                            <div class="input-group">
                                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                                <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('confirm_password', this)">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Password Requirements -->
                                    <div class="alert alert-info mt-4">
                                        <h6><i class="fas fa-info-circle me-2"></i>Password Security Tips:</h6>
                                        <ul class="mb-0 small">
                                            <li>Use at least 8 characters</li>
                                            <li>Include uppercase (A-Z) and lowercase (a-z) letters</li>
                                            <li>Add numbers (0-9) for better security</li>
                                            <li>Include special characters (!@#$%^&*)</li>
                                            <li>Don't use personal information or common words</li>
                                            <li>Never share your password with anyone</li>
                                        </ul>
                                    </div>

                                    <!-- Submit Button -->
                                    <div class="mt-4">
                                        <button type="submit" name="change_password" class="btn btn-warning btn-lg px-4">
                                            <i class="fas fa-key me-2"></i>Change Password
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column: Preview & Stats -->
                    <div class="col-lg-4">
                        <!-- Store Preview Card -->
                        <div class="card shadow store-preview-card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-eye me-2"></i>Store Preview</h5>
                            </div>
                            <div class="card-body text-center">
                                <!-- Store Banner -->
                                <div class="store-banner mb-3">
                                    <?php if (!empty($store_info['store_banner'])): ?>
                                        <img src="../<?php echo htmlspecialchars($store_info['store_banner']); ?>"
                                            alt="Store Banner"
                                            class="img-fluid rounded shadow"
                                            style="height: 150px; width: 100%; object-fit: cover;">
                                    <?php else: ?>
                                        <div class="bg-gradient-primary text-white p-4 rounded shadow"
                                            style="height: 150px; display: flex; align-items: center; justify-content: center;">
                                            <i class="fas fa-store fa-3x"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Store Logo -->
                                <div class="store-logo" style="margin-top: -50px;">
                                    <img src="<?php echo !empty($store_info['store_logo']) ? '../' . htmlspecialchars($store_info['store_logo']) : '../assets/images/stores/default-logo.png'; ?>"
                                        alt="Store Logo"
                                        class="rounded-circle img-thumbnail border-4 border-white shadow"
                                        style="width: 100px; height: 100px; object-fit: cover;">
                                </div>

                                <!-- Store Name & Description -->
                                <h4 class="mt-3 mb-2 fw-bold"><?php echo htmlspecialchars($store_info['store_name'] ?? ''); ?></h4>
                                <p class="text-muted mb-3">
                                    <?php if (!empty($store_info['store_description'])): ?>
                                        <?php echo substr(htmlspecialchars($store_info['store_description']), 0, 100); ?>
                                        <?php if (strlen($store_info['store_description']) > 100): ?>...<?php endif; ?>
                                    <?php else: ?>
                                        <em>No description provided</em>
                                    <?php endif; ?>
                                </p>

                                <!-- Store Rating -->
                                <div class="store-rating mb-4">
                                    <div class="text-warning fs-4">
                                        <?php echo get_star_rating_local($store_info['rating'] ?? 0); ?>
                                    </div>
                                    <span class="text-muted">Rating: <?php echo number_format($store_info['rating'] ?? 0, 1); ?>/5</span>
                                </div>

                                <!-- View Store Button -->
                                <a href="../public/store.php?id=<?php echo $_SESSION['user_id']; ?>"
                                    class="btn btn-primary btn-lg w-100"
                                    target="_blank">
                                    <i class="fas fa-external-link-alt me-2"></i>View My Store
                                </a>
                            </div>
                        </div>

                        <!-- Account Status Card -->
                        <div class="card shadow mb-4">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-user-check me-2"></i>Account Status</h5>
                            </div>
                            <div class="card-body">
                                <div class="list-group list-group-flush">
                                    <div class="list-group-item d-flex justify-content-between align-items-center">
                                        <span><i class="fas fa-user-tag me-2"></i>Account Type</span>
                                        <span class="badge bg-success rounded-pill">Vendor</span>
                                    </div>
                                    <div class="list-group-item d-flex justify-content-between align-items-center">
                                        <span><i class="fas fa-check-circle me-2"></i>Status</span>
                                        <span class="badge bg-success rounded-pill">Active</span>
                                    </div>
                                    <div class="list-group-item d-flex justify-content-between align-items-center">
                                        <span><i class="fas fa-envelope me-2"></i>Email Verified</span>
                                        <span class="text-success">
                                            <i class="fas fa-check-circle"></i>
                                        </span>
                                    </div>
                                    <div class="list-group-item d-flex justify-content-between align-items-center">
                                        <span><i class="fas fa-phone me-2"></i>Phone Verified</span>
                                        <span class="text-success">
                                            <i class="fas fa-check-circle"></i>
                                        </span>
                                    </div>
                                    <div class="list-group-item d-flex justify-content-between align-items-center">
                                        <span><i class="fas fa-sign-in-alt me-2"></i>Last Login</span>
                                        <span><?php echo format_date($vendor_data['last_login'] ?? '', 'M d, Y H:i'); ?></span>
                                    </div>
                                    <div class="list-group-item d-flex justify-content-between align-items-center">
                                        <span><i class="fas fa-calendar-plus me-2"></i>Member Since</span>
                                        <span><?php echo format_date($vendor_data['created_at'] ?? '', 'M d, Y'); ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Store Statistics Card -->
                        <div class="card shadow mb-4">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Store Statistics</h5>
                            </div>
                            <div class="card-body">
                                <div class="row text-center">
                                    <div class="col-6 mb-4">
                                        <div class="stat-card p-3 border-left-primary">
                                            <div class="display-6 fw-bold mb-1"><?php echo $total_products; ?></div>
                                            <small class="text-muted">Active Products</small>
                                            <div class="mt-2">
                                                <i class="fas fa-box text-primary stat-icon"></i>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-6 mb-4">
                                        <div class="stat-card p-3 border-left-success">
                                            <div class="display-6 fw-bold mb-1"><?php echo $total_orders; ?></div>
                                            <small class="text-muted">Total Orders</small>
                                            <div class="mt-2">
                                                <i class="fas fa-shopping-cart text-success stat-icon"></i>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="stat-card p-3 border-left-warning">
                                            <div class="display-6 fw-bold mb-1"><?php echo format_price($total_revenue); ?></div>
                                            <small class="text-muted">Total Revenue</small>
                                            <div class="mt-2">
                                                <i class="fas fa-money-bill-wave text-warning stat-icon"></i>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="stat-card p-3 border-left-info">
                                            <div class="display-6 fw-bold mb-1"><?php echo number_format($store_info['rating'] ?? 0, 1); ?></div>
                                            <small class="text-muted">Average Rating</small>
                                            <div class="mt-2">
                                                <i class="fas fa-star text-info stat-icon"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Quick Actions Card -->
                        <div class="card shadow">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-bolt me-2"></i>Quick Actions</h5>
                            </div>
                            <div class="card-body">
                                <div class="d-grid gap-2">
                                    <a href="products.php" class="btn btn-outline-primary">
                                        <i class="fas fa-plus-circle me-2"></i>Add New Product
                                    </a>
                                    <a href="orders.php" class="btn btn-outline-success">
                                        <i class="fas fa-shopping-cart me-2"></i>View Recent Orders
                                    </a>
                                    <a href="earnings.php" class="btn btn-outline-warning">
                                        <i class="fas fa-chart-line me-2"></i>Check Earnings
                                    </a>
                                    <a href="../public/logout.php" class="btn btn-outline-danger">
                                        <i class="fas fa-sign-out-alt me-2"></i>Logout Account
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-white py-4 mt-5">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-6">
                    <p class="mb-0">&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars(SITE_NAME); ?>. All rights reserved.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <a href="../public/contact.php" class="text-white text-decoration-none me-3">
                        <i class="fas fa-phone-alt me-1"></i>Contact
                    </a>
                    <a href="../public/terms.php" class="text-white text-decoration-none me-3">
                        <i class="fas fa-file-contract me-1"></i>Terms
                    </a>
                    <a href="../public/privacy.php" class="text-white text-decoration-none">
                        <i class="fas fa-shield-alt me-1"></i>Privacy
                    </a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Delete Store Modal -->
    <div class="modal fade" id="deleteStoreModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-exclamation-triangle me-2"></i>Close Store</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-danger">
                        <h6><i class="fas fa-exclamation-circle me-2"></i>Warning: This action has serious consequences!</h6>
                        <p class="mb-2">Closing your store will:</p>
                        <ul class="mb-0">
                            <li>Make all your products unavailable</li>
                            <li>Cancel any pending orders</li>
                            <li>Remove your store from search results</li>
                            <li>You will need admin approval to reopen</li>
                        </ul>
                    </div>
                    <p>Are you sure you want to close your store? This action is reversible but requires admin approval.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" onclick="confirmStoreClosure()">
                        <i class="fas fa-store-slash me-2"></i>Yes, Close My Store
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Toggle password visibility
        function togglePassword(inputId, button) {
            const input = document.getElementById(inputId);
            const icon = button.querySelector('i');

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
        const newPasswordInput = document.getElementById('new_password');
        if (newPasswordInput) {
            newPasswordInput.addEventListener('input', function() {
                const password = this.value;
                const strengthBar = document.getElementById('password-strength-bar');
                const strengthText = document.getElementById('password-strength-text');

                let strength = 0;
                let text = '';
                let color = '';

                // Length check
                if (password.length >= 6) strength += 20;
                if (password.length >= 8) strength += 10;
                if (password.length >= 12) strength += 10;

                // Character type checks
                if (/[A-Z]/.test(password)) strength += 20;
                if (/[a-z]/.test(password)) strength += 20;
                if (/[0-9]/.test(password)) strength += 20;
                if (/[^A-Za-z0-9]/.test(password)) strength += 20;

                // Cap at 100
                strength = Math.min(strength, 100);

                if (strengthBar) {
                    strengthBar.style.width = strength + '%';
                }

                if (strength < 40) {
                    color = 'danger';
                    text = 'Weak - Add more characters';
                } else if (strength < 70) {
                    color = 'warning';
                    text = 'Medium - Could be stronger';
                } else if (strength < 90) {
                    color = 'info';
                    text = 'Good - Strong password';
                } else {
                    color = 'success';
                    text = 'Excellent - Very strong!';
                }

                if (strengthBar) {
                    strengthBar.className = 'progress-bar bg-' + color;
                }
                if (strengthText) {
                    strengthText.textContent = text;
                    strengthText.className = 'text-' + color;
                }
            });
        }

        // Image preview
        function previewImage(input, previewId) {
            const preview = document.getElementById(previewId);
            const file = input.files[0];

            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                }
                reader.readAsDataURL(file);
            }
        }

        // Phone number formatting
        const phoneInput = document.getElementById('phone');
        if (phoneInput) {
            phoneInput.addEventListener('input', function() {
                formatPhoneNumber(this);
            });
        }

        const storePhoneInput = document.getElementById('store_phone');
        if (storePhoneInput) {
            storePhoneInput.addEventListener('input', function() {
                formatPhoneNumber(this);
            });
        }

        function formatPhoneNumber(input) {
            let value = input.value.replace(/\D/g, '');

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

            input.value = value;
        }

        // Form validation
        const forms = document.querySelectorAll('form');
        forms.forEach(form => {
            form.addEventListener('submit', function(e) {
                // Validate phone numbers
                const phoneInputs = this.querySelectorAll('input[type="tel"]');
                phoneInputs.forEach(input => {
                    if (input.value && !/^\+251[0-9]{9}$/.test(input.value)) {
                        e.preventDefault();
                        alert('Please enter a valid Ethiopian phone number (format: +251911234567).');
                        input.focus();
                        return;
                    }
                });

                // Validate passwords
                if (this.querySelector('#new_password')) {
                    const newPassword = document.getElementById('new_password').value;
                    const confirmPassword = document.getElementById('confirm_password').value;

                    if (newPassword && newPassword.length < 6) {
                        e.preventDefault();
                        alert('New password must be at least 6 characters long.');
                        document.getElementById('new_password').focus();
                        return;
                    }

                    if (newPassword && newPassword !== confirmPassword) {
                        e.preventDefault();
                        alert('New passwords do not match. Please confirm your new password.');
                        document.getElementById('confirm_password').focus();
                        return;
                    }
                }

                // Validate store name
                if (this.querySelector('#store_name')) {
                    const storeName = document.getElementById('store_name').value;
                    if (!storeName.trim()) {
                        e.preventDefault();
                        alert('Store name is required.');
                        document.getElementById('store_name').focus();
                        return;
                    }
                }

                // Show loading state
                const submitButtons = this.querySelectorAll('button[type="submit"]');
                submitButtons.forEach(button => {
                    button.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Processing...';
                    button.disabled = true;
                });
            });
        });

        // Store closure confirmation
        function confirmStoreClosure() {
            if (confirm('⚠️ Are you absolutely sure?\n\nThis will:\n• Make all products unavailable\n• Cancel pending orders\n• Remove store from searches\n• Require admin approval to reopen\n\nType "CLOSE STORE" to confirm:')) {
                const confirmation = prompt('Type "CLOSE STORE" to confirm:');
                if (confirmation === 'CLOSE STORE') {
                    alert('Store closure feature is not implemented yet. Please contact admin.');
                    // In production: window.location.href = 'close_store.php';
                } else {
                    alert('Store closure cancelled.');
                }
            }
        }

        // Initialize Bootstrap tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });

        // Auto-hide alerts after 5 seconds
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);

        // File size validation
        const fileInputs = document.querySelectorAll('input[type="file"]');
        fileInputs.forEach(input => {
            input.addEventListener('change', function() {
                const file = this.files[0];
                if (!file) return;

                let maxSize;
                if (this.id === 'profile_image' || this.id === 'store_banner') {
                    maxSize = 5 * 1024 * 1024; // 5MB
                } else if (this.id === 'store_logo') {
                    maxSize = 2 * 1024 * 1024; // 2MB
                }

                if (file.size > maxSize) {
                    alert(`File is too large. Maximum size is ${maxSize / (1024*1024)}MB.`);
                    this.value = '';
                }
            });
        });
    </script>
</body>

</html>