<?php
require_once '../config.php';

// Require vendor login
require_vendor();

$page_title = 'Manage Products - ' . SITE_NAME;

// Get vendor data
$user = new User();
$vendor_data = $user->getUserById($_SESSION['user_id']);

// Handle product actions
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_product'])) {
        // Add new product
        $product_data = [
            'vendor_id' => $_SESSION['user_id'],
            'name' => sanitize_input($_POST['name']),
            'sku' => sanitize_input($_POST['sku']),
            'description' => sanitize_input($_POST['description']),
            'price' => floatval($_POST['price']),
            'discount_price' => !empty($_POST['discount_price']) ? floatval($_POST['discount_price']) : null,
            'category' => sanitize_input($_POST['category']),
            'brand' => sanitize_input($_POST['brand']),
            'size' => sanitize_input($_POST['size'] ?? ''),
            'color' => sanitize_input($_POST['color'] ?? ''),
            'material' => sanitize_input($_POST['material'] ?? ''),
            'stock_quantity' => intval($_POST['stock_quantity']),
            'status' => 'pending', // Vendor products need admin approval
            'created_at' => date('Y-m-d H:i:s')
        ];

        // Validate required fields
        $required = ['name', 'sku', 'price', 'category', 'brand', 'stock_quantity'];
        foreach ($required as $field) {
            if (empty($product_data[$field])) {
                $errors[] = ucfirst(str_replace('_', ' ', $field)) . ' is required.';
            }
        }

        // Validate price
        if ($product_data['price'] <= 0) {
            $errors[] = 'Price must be greater than 0.';
        }

        // Validate discount price
        if (!empty($product_data['discount_price'])) {
            if ($product_data['discount_price'] >= $product_data['price']) {
                $errors[] = 'Discount price must be less than regular price.';
            }
        }

        // Validate stock
        if ($product_data['stock_quantity'] < 0) {
            $errors[] = 'Stock quantity cannot be negative.';
        }

        // Handle image upload
        $uploaded_images = [];
        if (!empty($_FILES['images']['name'][0])) {
            $image_count = min(count($_FILES['images']['name']), 5); // Limit to 5 images

            for ($i = 0; $i < $image_count; $i++) {
                if ($_FILES['images']['error'][$i] === UPLOAD_ERR_OK) {
                    $file = [
                        'name' => $_FILES['images']['name'][$i],
                        'type' => $_FILES['images']['type'][$i],
                        'tmp_name' => $_FILES['images']['tmp_name'][$i],
                        'error' => $_FILES['images']['error'][$i],
                        'size' => $_FILES['images']['size'][$i]
                    ];

                    $upload_result = upload_image($file, '../assets/images/products/');
                    if ($upload_result['success']) {
                        $uploaded_images[] = $upload_result['file_path'];
                    } else {
                        $errors[] = 'Image upload failed: ' . implode(', ', $upload_result['errors']);
                        break;
                    }
                }
            }
        }

        if (empty($errors)) {
            try {
                // Add images to product data
                if (!empty($uploaded_images)) {
                    $product_data['images'] = json_encode($uploaded_images);
                }

                $product = new Product();
                $product_id = $product->create($product_data);

                $success = 'Product added successfully! It will be visible after admin approval.';

                // Clear form data
                $_POST = [];
            } catch (Exception $e) {
                $errors[] = 'Failed to add product: ' . $e->getMessage();

                // Delete uploaded images if product creation failed
                foreach ($uploaded_images as $image) {
                    if (file_exists('../' . $image)) {
                        unlink('../' . $image);
                    }
                }
            }
        }
    } elseif (isset($_POST['update_product'])) {
        // Update product
        $product_id = intval($_POST['product_id']);

        // Check if product belongs to vendor
        $db = new Database();
        $db->query("SELECT vendor_id FROM products WHERE id = :id");
        $db->bind(':id', $product_id);
        $product = $db->single();

        if (!$product || $product['vendor_id'] != $_SESSION['user_id']) {
            $errors[] = 'Product not found or access denied.';
        } else {
            $update_data = [
                'name' => sanitize_input($_POST['name']),
                'sku' => sanitize_input($_POST['sku']),
                'description' => sanitize_input($_POST['description']),
                'price' => floatval($_POST['price']),
                'discount_price' => !empty($_POST['discount_price']) ? floatval($_POST['discount_price']) : null,
                'category' => sanitize_input($_POST['category']),
                'brand' => sanitize_input($_POST['brand']),
                'size' => sanitize_input($_POST['size'] ?? ''),
                'color' => sanitize_input($_POST['color'] ?? ''),
                'material' => sanitize_input($_POST['material'] ?? ''),
                'stock_quantity' => intval($_POST['stock_quantity']),
                'updated_at' => date('Y-m-d H:i:s')
            ];

            // Handle new image upload
            $uploaded_images = [];
            if (!empty($_FILES['images']['name'][0])) {
                $image_count = min(count($_FILES['images']['name']), 5); // Limit to 5 images

                for ($i = 0; $i < $image_count; $i++) {
                    if ($_FILES['images']['error'][$i] === UPLOAD_ERR_OK) {
                        $file = [
                            'name' => $_FILES['images']['name'][$i],
                            'type' => $_FILES['images']['type'][$i],
                            'tmp_name' => $_FILES['images']['tmp_name'][$i],
                            'error' => $_FILES['images']['error'][$i],
                            'size' => $_FILES['images']['size'][$i]
                        ];

                        $upload_result = upload_image($file, '../assets/images/products/');
                        if ($upload_result['success']) {
                            $uploaded_images[] = $upload_result['file_path'];
                        } else {
                            $errors[] = 'Image upload failed: ' . implode(', ', $upload_result['errors']);
                            break;
                        }
                    }
                }

                if (empty($errors) && !empty($uploaded_images)) {
                    // Get existing images
                    $db->query("SELECT images FROM products WHERE id = :id");
                    $db->bind(':id', $product_id);
                    $existing = $db->single();

                    $existing_images = [];
                    if (!empty($existing['images'])) {
                        $existing_images = json_decode($existing['images'], true);
                        // Limit total images to 5
                        $existing_images = array_slice($existing_images, 0, 5 - count($uploaded_images));
                    }

                    // Combine existing and new images
                    $all_images = array_merge($existing_images, $uploaded_images);
                    $update_data['images'] = json_encode($all_images);
                }
            }

            if (empty($errors)) {
                try {
                    $product_obj = new Product();
                    $product_obj->update($product_id, $update_data);

                    $success = 'Product updated successfully!';
                } catch (Exception $e) {
                    $errors[] = 'Failed to update product: ' . $e->getMessage();

                    // Delete uploaded images if update failed
                    foreach ($uploaded_images as $image) {
                        if (file_exists('../' . $image)) {
                            unlink('../' . $image);
                        }
                    }
                }
            }
        }
    }
}

// Handle delete product
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $product_id = intval($_GET['delete']);

    try {
        $db = new Database();

        // Check if product belongs to vendor
        $db->query("SELECT vendor_id, images FROM products WHERE id = :id");
        $db->bind(':id', $product_id);
        $product = $db->single();

        if ($product && $product['vendor_id'] == $_SESSION['user_id']) {
            // Delete product
            $db->query("DELETE FROM products WHERE id = :id");
            $db->bind(':id', $product_id);
            $db->execute();

            // Delete product images
            if (!empty($product['images'])) {
                $images = json_decode($product['images'], true);
                foreach ($images as $image) {
                    $image_path = '../' . $image;
                    if (file_exists($image_path) && is_file($image_path)) {
                        unlink($image_path);
                    }
                }
            }

            $_SESSION['success'] = 'Product deleted successfully!';
        } else {
            $_SESSION['error'] = 'Product not found or access denied.';
        }
    } catch (Exception $e) {
        $_SESSION['error'] = 'Failed to delete product: ' . $e->getMessage();
    }

    header('Location: products.php');
    exit();
}

// Handle delete image
if (isset($_GET['delete_image']) && isset($_GET['product_id'])) {
    $product_id = intval($_GET['product_id']);
    $image_index = intval($_GET['delete_image']);

    try {
        $db = new Database();

        // Check if product belongs to vendor
        $db->query("SELECT vendor_id, images FROM products WHERE id = :id");
        $db->bind(':id', $product_id);
        $product = $db->single();

        if ($product && $product['vendor_id'] == $_SESSION['user_id']) {
            $images = json_decode($product['images'], true);

            if (isset($images[$image_index])) {
                // Delete image file
                $image_path = '../' . $images[$image_index];
                if (file_exists($image_path) && is_file($image_path)) {
                    unlink($image_path);
                }

                // Remove image from array
                unset($images[$image_index]);
                $images = array_values($images); // Re-index array

                // Update product
                $db->query("UPDATE products SET images = :images WHERE id = :id");
                $db->bind(':images', !empty($images) ? json_encode($images) : null);
                $db->bind(':id', $product_id);
                $db->execute();

                $_SESSION['success'] = 'Image deleted successfully!';
            }
        }
    } catch (Exception $e) {
        $_SESSION['error'] = 'Failed to delete image: ' . $e->getMessage();
    }

    header('Location: products.php?edit=' . $product_id);
    exit();
}

// Get filter parameters
$status_filter = $_GET['status'] ?? '';
$category_filter = $_GET['category'] ?? '';
$search_query = $_GET['search'] ?? '';

// Build query for fetching products
$db = new Database();

// Build base query
$query = "SELECT * FROM products WHERE vendor_id = :vendor_id";
$params = [':vendor_id' => $_SESSION['user_id']];

if (!empty($status_filter)) {
    $query .= " AND status = :status";
    $params[':status'] = $status_filter;
}

if (!empty($category_filter)) {
    $query .= " AND category = :category";
    $params[':category'] = $category_filter;
}

if (!empty($search_query)) {
    $query .= " AND (name ILIKE :search OR sku ILIKE :search OR description ILIKE :search)";
    $params[':search'] = '%' . $search_query . '%';
}

// Get total count for pagination
$count_query = "SELECT COUNT(*) as total FROM products WHERE vendor_id = :vendor_id";
$count_params = [':vendor_id' => $_SESSION['user_id']];

if (!empty($status_filter)) {
    $count_query .= " AND status = :status";
    $count_params[':status'] = $status_filter;
}

if (!empty($category_filter)) {
    $count_query .= " AND category = :category";
    $count_params[':category'] = $category_filter;
}

if (!empty($search_query)) {
    $count_query .= " AND (name ILIKE :search OR sku ILIKE :search OR description ILIKE :search)";
    $count_params[':search'] = '%' . $search_query . '%';
}

$db->query($count_query);
foreach ($count_params as $key => $value) {
    $db->bind($key, $value);
}

$total_count = 0;
try {
    $result = $db->single();
    $total_count = $result ? $result['total'] : 0;
} catch (Exception $e) {
    error_log("Error counting products: " . $e->getMessage());
}

// Setup pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 20;
$total_pages = ceil($total_count / $per_page);
$offset = ($page - 1) * $per_page;

$query .= " ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
$params[':limit'] = $per_page;
$params[':offset'] = $offset;

// Get products
try {
    $db->query($query);
    foreach ($params as $key => $value) {
        $db->bind($key, $value);
    }
    $products = $db->resultSet();
} catch (Exception $e) {
    $products = [];
    error_log("Error loading products: " . $e->getMessage());
}

// Get edit product if specified
$edit_product = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $product_id = intval($_GET['edit']);
    $db->query("SELECT * FROM products WHERE id = :id AND vendor_id = :vendor_id");
    $db->bind(':id', $product_id);
    $db->bind(':vendor_id', $_SESSION['user_id']);
    $edit_product = $db->single();

    if ($edit_product) {
        $edit_product['images'] = !empty($edit_product['images']) ? json_decode($edit_product['images'], true) : [];
    }
}

// Get categories for filter
$db->query("SELECT DISTINCT category FROM products WHERE vendor_id = :vendor_id AND category IS NOT NULL ORDER BY category");
$db->bind(':vendor_id', $_SESSION['user_id']);
$categories = $db->resultSet();

// Get cart count if function exists
$cart_count = function_exists('get_cart_count') ? get_cart_count() : 0;
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
    <!-- Custom CSS -->
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        .sidebar {
            min-height: calc(100vh - 56px);
            background: #1a1d20;
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
            background-color: #0d6efd;
        }

        .table img {
            object-fit: cover;
            border: 1px solid #dee2e6;
            transition: transform 0.2s;
        }

        .table img:hover {
            transform: scale(1.1);
        }

        .badge {
            font-weight: 500;
            padding: 0.35em 0.65em;
        }

        .product-actions .btn {
            padding: 0.25rem 0.5rem;
            margin: 0 0.125rem;
        }

        .modal-header {
            background: linear-gradient(135deg, #0d6efd 0%, #0b5ed7 100%);
        }

        .modal-header.warning {
            background: linear-gradient(135deg, #ffc107 0%, #e6a800 100%);
        }

        .image-preview-container {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 10px;
        }

        .image-preview {
            position: relative;
            width: 100px;
            height: 100px;
        }

        .image-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 4px;
        }

        .image-preview .remove-btn {
            position: absolute;
            top: -5px;
            right: -5px;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: #dc3545;
            color: white;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            cursor: pointer;
        }

        .stat-card {
            border-radius: 10px;
            border: none;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s;
        }

        .stat-card:hover {
            transform: translateY(-2px);
        }

        .pagination .page-item.active .page-link {
            background-color: #0d6efd;
            border-color: #0d6efd;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #86b7fe;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
        }

        .alert {
            border: none;
            border-left: 4px solid;
        }

        .alert-success {
            border-left-color: #198754;
            background-color: #d1e7dd;
        }

        .alert-danger {
            border-left-color: #dc3545;
            background-color: #f8d7da;
        }

        .table-hover tbody tr:hover {
            background-color: rgba(13, 110, 253, 0.05);
        }

        @media (max-width: 768px) {
            .sidebar {
                min-height: auto;
                padding: 10px;
            }

            .table-responsive {
                font-size: 0.9rem;
            }

            .product-actions .btn {
                padding: 0.2rem 0.4rem;
                font-size: 0.8rem;
            }
        }
    </style>
</head>

<body>
    <!-- Vendor Navigation -->
    <?php if (file_exists('includes/navbar.php')): ?>
        <?php include 'includes/navbar.php'; ?>
    <?php else: ?>
        <!-- Fallback Navigation -->
        <nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
            <div class="container-fluid">
                <a class="navbar-brand" href="index.php">
                    <i class="fas fa-store me-2"></i>Vendor Panel
                </a>
                <div class="ms-auto">
                    <a href="index.php" class="btn btn-outline-light btn-sm me-2">Dashboard</a>
                    <a href="../public/logout.php" class="btn btn-outline-light btn-sm">Logout</a>
                </div>
            </div>
        </nav>
    <?php endif; ?>

    <!-- Dashboard Layout -->
    <div class="container-fluid mt-5 pt-3">
        <div class="row">
            <!-- Vendor Sidebar -->
            <div class="col-md-3 col-lg-2 d-md-block sidebar collapse">
                <?php if (file_exists('includes/sidebar.php')): ?>
                    <?php include 'includes/sidebar.php'; ?>
                <?php else: ?>
                    <!-- Fallback Sidebar -->
                    <div class="position-sticky pt-3">
                        <ul class="nav flex-column">
                            <li class="nav-item">
                                <a class="nav-link text-white" href="index.php">
                                    <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link text-white active" href="products.php">
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
                                    <i class="fas fa-money-bill me-2"></i>Earnings
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link text-white" href="profile.php">
                                    <i class="fas fa-user me-2"></i>Profile
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link text-white" href="../public/logout.php">
                                    <i class="fas fa-sign-out-alt me-2"></i>Logout
                                </a>
                            </li>
                        </ul>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <!-- Page Header -->
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <div>
                        <h1 class="h2 mb-0">Manage Products</h1>
                        <p class="text-muted mb-0">Manage your product listings and inventory</p>
                    </div>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addProductModal">
                            <i class="fas fa-plus-circle me-2"></i>Add New Product
                        </button>
                    </div>
                </div>

                <!-- Success/Error Messages -->
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show mb-4">
                        <i class="fas fa-check-circle me-2"></i>
                        <?php echo htmlspecialchars($_SESSION['success']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        <?php unset($_SESSION['success']); ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show mb-4">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <?php echo htmlspecialchars($_SESSION['error']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        <?php unset($_SESSION['error']); ?>
                    </div>
                <?php endif; ?>

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

                <!-- Quick Stats -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stat-card border-left-primary">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-muted mb-1">Total Products</h6>
                                        <h3 class="mb-0"><?php echo $total_count; ?></h3>
                                    </div>
                                    <div class="rounded-circle bg-primary p-3">
                                        <i class="fas fa-box fa-2x text-white"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php
                    // Get active products count
                    $db->query("SELECT COUNT(*) as active_count FROM products WHERE vendor_id = :vendor_id AND status = 'active'");
                    $db->bind(':vendor_id', $_SESSION['user_id']);
                    $active_count = $db->single()['active_count'] ?? 0;

                    // Get pending products count
                    $db->query("SELECT COUNT(*) as pending_count FROM products WHERE vendor_id = :vendor_id AND status = 'pending'");
                    $db->bind(':vendor_id', $_SESSION['user_id']);
                    $pending_count = $db->single()['pending_count'] ?? 0;

                    // Get low stock count
                    $db->query("SELECT COUNT(*) as low_stock_count FROM products WHERE vendor_id = :vendor_id AND stock_quantity > 0 AND stock_quantity <= 10");
                    $db->bind(':vendor_id', $_SESSION['user_id']);
                    $low_stock_count = $db->single()['low_stock_count'] ?? 0;
                    ?>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stat-card border-left-success">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-muted mb-1">Active</h6>
                                        <h3 class="mb-0"><?php echo $active_count; ?></h3>
                                    </div>
                                    <div class="rounded-circle bg-success p-3">
                                        <i class="fas fa-check-circle fa-2x text-white"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stat-card border-left-warning">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-muted mb-1">Pending Review</h6>
                                        <h3 class="mb-0"><?php echo $pending_count; ?></h3>
                                    </div>
                                    <div class="rounded-circle bg-warning p-3">
                                        <i class="fas fa-clock fa-2x text-white"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stat-card border-left-danger">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-muted mb-1">Low Stock</h6>
                                        <h3 class="mb-0"><?php echo $low_stock_count; ?></h3>
                                    </div>
                                    <div class="rounded-circle bg-danger p-3">
                                        <i class="fas fa-exclamation-triangle fa-2x text-white"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filter Section -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h6 class="mb-0"><i class="fas fa-filter me-2"></i>Filter Products</h6>
                    </div>
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select">
                                    <option value="">All Status</option>
                                    <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Category</label>
                                <select name="category" class="form-select">
                                    <option value="">All Categories</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <?php if (!empty($cat['category'])): ?>
                                            <option value="<?php echo htmlspecialchars($cat['category']); ?>"
                                                <?php echo $category_filter === $cat['category'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($cat['category']); ?>
                                            </option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Search Products</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                                    <input type="text" name="search" class="form-control"
                                        placeholder="Search by name, SKU, or description"
                                        value="<?php echo htmlspecialchars($search_query); ?>">
                                    <button class="btn btn-primary" type="submit">Search</button>
                                </div>
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <a href="products.php" class="btn btn-outline-secondary w-100">
                                    <i class="fas fa-times me-1"></i>Clear
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Products Table -->
                <div class="card shadow-sm">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h6 class="mb-0"><i class="fas fa-list me-2"></i>Your Products</h6>
                        <span class="badge bg-primary"><?php echo $total_count; ?> products</span>
                    </div>
                    <div class="card-body">
                        <?php if (empty($products)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-box-open fa-4x text-muted mb-4"></i>
                                <h3>No products found</h3>
                                <p class="text-muted mb-4">Start by adding your first product to sell on our platform.</p>
                                <button type="button" class="btn btn-primary btn-lg" data-bs-toggle="modal" data-bs-target="#addProductModal">
                                    <i class="fas fa-plus me-2"></i>Add Your First Product
                                </button>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th width="80">Image</th>
                                            <th>Product Details</th>
                                            <th>SKU</th>
                                            <th>Pricing</th>
                                            <th>Stock</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($products as $product_item): ?>
                                            <?php
                                            $images = !empty($product_item['images']) ? json_decode($product_item['images'], true) : [];
                                            $first_image = !empty($images) && !empty($images[0]) ? '../' . $images[0] : '../assets/images/products/default.jpg';
                                            ?>
                                            <tr>
                                                <td>
                                                    <img src="<?php echo htmlspecialchars($first_image); ?>"
                                                        alt="<?php echo htmlspecialchars($product_item['name']); ?>"
                                                        class="img-thumbnail rounded"
                                                        style="width: 60px; height: 60px; object-fit: cover;">
                                                </td>
                                                <td>
                                                    <h6 class="mb-1"><?php echo htmlspecialchars($product_item['name']); ?></h6>
                                                    <p class="text-muted small mb-1">
                                                        <i class="fas fa-tag me-1"></i><?php echo htmlspecialchars($product_item['category']); ?>
                                                    </p>
                                                    <p class="text-muted small mb-0">
                                                        <i class="fas fa-copyright me-1"></i><?php echo htmlspecialchars($product_item['brand']); ?>
                                                    </p>
                                                </td>
                                                <td>
                                                    <span class="badge bg-dark"><?php echo htmlspecialchars($product_item['sku']); ?></span>
                                                </td>
                                                <td>
                                                    <?php if (!empty($product_item['discount_price'])): ?>
                                                        <span class="text-danger fw-bold"><?php echo format_price($product_item['discount_price']); ?></span>
                                                        <br>
                                                        <small class="text-muted text-decoration-line-through"><?php echo format_price($product_item['price']); ?></small>
                                                    <?php else: ?>
                                                        <span class="fw-bold"><?php echo format_price($product_item['price']); ?></span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($product_item['stock_quantity'] > 10): ?>
                                                        <span class="badge bg-success">
                                                            <i class="fas fa-check me-1"></i><?php echo $product_item['stock_quantity']; ?>
                                                        </span>
                                                    <?php elseif ($product_item['stock_quantity'] > 0): ?>
                                                        <span class="badge bg-warning">
                                                            <i class="fas fa-exclamation me-1"></i><?php echo $product_item['stock_quantity']; ?>
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="badge bg-danger">
                                                            <i class="fas fa-times me-1"></i>Out of Stock
                                                        </span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php
                                                    $status_colors = [
                                                        'active' => 'success',
                                                        'inactive' => 'secondary',
                                                        'pending' => 'warning'
                                                    ];
                                                    $status_color = $status_colors[$product_item['status']] ?? 'secondary';
                                                    ?>
                                                    <span class="badge bg-<?php echo $status_color; ?>">
                                                        <?php echo ucfirst($product_item['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="product-actions">
                                                        <a href="../public/product-detail.php?id=<?php echo $product_item['id']; ?>"
                                                            class="btn btn-sm btn-outline-primary" title="View" target="_blank">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        <a href="products.php?edit=<?php echo $product_item['id']; ?>"
                                                            class="btn btn-sm btn-outline-warning" title="Edit">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <a href="products.php?delete=<?php echo $product_item['id']; ?>"
                                                            class="btn btn-sm btn-outline-danger"
                                                            onclick="return confirm('Are you sure you want to delete this product? This action cannot be undone.')"
                                                            title="Delete">
                                                            <i class="fas fa-trash"></i>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Pagination -->
                            <?php if ($total_pages > 1): ?>
                                <nav aria-label="Page navigation" class="mt-4">
                                    <ul class="pagination justify-content-center">
                                        <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">
                                                <i class="fas fa-chevron-left me-1"></i>Previous
                                            </a>
                                        </li>

                                        <?php
                                        // Show limited pagination
                                        $start_page = max(1, $page - 2);
                                        $end_page = min($total_pages, $page + 2);

                                        for ($i = $start_page; $i <= $end_page; $i++): ?>
                                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>">
                                                    <?php echo $i; ?>
                                                </a>
                                            </li>
                                        <?php endfor; ?>

                                        <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">
                                                Next<i class="fas fa-chevron-right ms-1"></i>
                                            </a>
                                        </li>
                                    </ul>
                                </nav>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Add Product Modal -->
    <div class="modal fade" id="addProductModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header text-white">
                    <h5 class="modal-title"><i class="fas fa-plus-circle me-2"></i>Add New Product</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" enctype="multipart/form-data" id="add-product-form">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="name" class="form-label">Product Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="name" name="name"
                                    value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" required
                                    placeholder="Enter product name">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="sku" class="form-label">SKU <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="sku" name="sku"
                                    value="<?php echo htmlspecialchars($_POST['sku'] ?? ''); ?>" required
                                    placeholder="Unique product identifier">
                                <small class="text-muted">Stock Keeping Unit (must be unique)</small>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="category" class="form-label">Category <span class="text-danger">*</span></label>
                                <select class="form-select" id="category" name="category" required>
                                    <option value="">Select Category</option>
                                    <option value="Running Shoes" <?php echo ($_POST['category'] ?? '') === 'Running Shoes' ? 'selected' : ''; ?>>Running Shoes</option>
                                    <option value="Basketball Shoes" <?php echo ($_POST['category'] ?? '') === 'Basketball Shoes' ? 'selected' : ''; ?>>Basketball Shoes</option>
                                    <option value="Casual Sneakers" <?php echo ($_POST['category'] ?? '') === 'Casual Sneakers' ? 'selected' : ''; ?>>Casual Sneakers</option>
                                    <option value="Training Shoes" <?php echo ($_POST['category'] ?? '') === 'Training Shoes' ? 'selected' : ''; ?>>Training Shoes</option>
                                    <option value="Football Cleats" <?php echo ($_POST['category'] ?? '') === 'Football Cleats' ? 'selected' : ''; ?>>Football Cleats</option>
                                    <option value="Skate Shoes" <?php echo ($_POST['category'] ?? '') === 'Skate Shoes' ? 'selected' : ''; ?>>Skate Shoes</option>
                                    <option value="Sandals" <?php echo ($_POST['category'] ?? '') === 'Sandals' ? 'selected' : ''; ?>>Sandals</option>
                                    <option value="Other" <?php echo ($_POST['category'] ?? '') === 'Other' ? 'selected' : ''; ?>>Other</option>
                                </select>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="brand" class="form-label">Brand <span class="text-danger">*</span></label>
                                <select class="form-select" id="brand" name="brand" required>
                                    <option value="">Select Brand</option>
                                    <option value="Nike" <?php echo ($_POST['brand'] ?? '') === 'Nike' ? 'selected' : ''; ?>>Nike</option>
                                    <option value="Adidas" <?php echo ($_POST['brand'] ?? '') === 'Adidas' ? 'selected' : ''; ?>>Adidas</option>
                                    <option value="Puma" <?php echo ($_POST['brand'] ?? '') === 'Puma' ? 'selected' : ''; ?>>Puma</option>
                                    <option value="Reebok" <?php echo ($_POST['brand'] ?? '') === 'Reebok' ? 'selected' : ''; ?>>Reebok</option>
                                    <option value="New Balance" <?php echo ($_POST['brand'] ?? '') === 'New Balance' ? 'selected' : ''; ?>>New Balance</option>
                                    <option value="Converse" <?php echo ($_POST['brand'] ?? '') === 'Converse' ? 'selected' : ''; ?>>Converse</option>
                                    <option value="Vans" <?php echo ($_POST['brand'] ?? '') === 'Vans' ? 'selected' : ''; ?>>Vans</option>
                                    <option value="Other" <?php echo ($_POST['brand'] ?? '') === 'Other' ? 'selected' : ''; ?>>Other</option>
                                </select>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="price" class="form-label">Price (ETB) <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">ETB</span>
                                    <input type="number" class="form-control" id="price" name="price"
                                        value="<?php echo htmlspecialchars($_POST['price'] ?? ''); ?>"
                                        min="1" step="0.01" required placeholder="0.00">
                                </div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="discount_price" class="form-label">Discount Price (ETB)</label>
                                <div class="input-group">
                                    <span class="input-group-text">ETB</span>
                                    <input type="number" class="form-control" id="discount_price" name="discount_price"
                                        value="<?php echo htmlspecialchars($_POST['discount_price'] ?? ''); ?>"
                                        min="0" step="0.01" placeholder="Optional">
                                </div>
                                <small class="text-muted">Must be less than regular price</small>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="stock_quantity" class="form-label">Stock Quantity <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="stock_quantity" name="stock_quantity"
                                    value="<?php echo htmlspecialchars($_POST['stock_quantity'] ?? '0'); ?>"
                                    min="0" required>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="size" class="form-label">Size</label>
                                <select class="form-select" id="size" name="size">
                                    <option value="">Select Size</option>
                                    <?php
                                    $sizes = ['US 5', 'US 6', 'US 7', 'US 8', 'US 9', 'US 10', 'US 11', 'US 12', 'US 13'];
                                    foreach ($sizes as $size): ?>
                                        <option value="<?php echo $size; ?>" <?php echo ($_POST['size'] ?? '') === $size ? 'selected' : ''; ?>>
                                            <?php echo $size; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="color" class="form-label">Color</label>
                                <input type="text" class="form-control" id="color" name="color"
                                    value="<?php echo htmlspecialchars($_POST['color'] ?? ''); ?>"
                                    placeholder="e.g., Black, White, Red">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="material" class="form-label">Material</label>
                                <input type="text" class="form-control" id="material" name="material"
                                    value="<?php echo htmlspecialchars($_POST['material'] ?? ''); ?>"
                                    placeholder="e.g., Leather, Canvas, Mesh">
                            </div>

                            <div class="col-12 mb-3">
                                <label for="description" class="form-label">Description <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="description" name="description" rows="4" required
                                    placeholder="Describe your product in detail"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                            </div>

                            <div class="col-12 mb-3">
                                <label for="images" class="form-label">Product Images</label>
                                <input type="file" class="form-control" id="images" name="images[]"
                                    accept="image/*" multiple onchange="previewImages(this)">
                                <small class="text-muted">Upload up to 5 images (JPEG, PNG, GIF). Max 5MB each.</small>
                                <div id="image-preview" class="image-preview-container mt-2"></div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_product" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Add Product
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Product Modal (shown when edit parameter is present) -->
    <?php if ($edit_product): ?>
        <div class="modal fade show" id="editProductModal" tabindex="-1" style="display: block; padding-right: 17px;">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header warning text-white">
                        <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit Product</h5>
                        <a href="products.php" class="btn-close btn-close-white"></a>
                    </div>
                    <form method="POST" enctype="multipart/form-data" id="edit-product-form">
                        <input type="hidden" name="product_id" value="<?php echo $edit_product['id']; ?>">
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="edit_name" class="form-label">Product Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="edit_name" name="name"
                                        value="<?php echo htmlspecialchars($edit_product['name']); ?>" required>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="edit_sku" class="form-label">SKU <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="edit_sku" name="sku"
                                        value="<?php echo htmlspecialchars($edit_product['sku']); ?>" required>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="edit_category" class="form-label">Category <span class="text-danger">*</span></label>
                                    <select class="form-select" id="edit_category" name="category" required>
                                        <option value="">Select Category</option>
                                        <?php
                                        $categories_list = ['Running Shoes', 'Basketball Shoes', 'Casual Sneakers', 'Training Shoes', 'Football Cleats', 'Skate Shoes', 'Sandals', 'Other'];
                                        foreach ($categories_list as $cat): ?>
                                            <option value="<?php echo $cat; ?>" <?php echo $edit_product['category'] === $cat ? 'selected' : ''; ?>>
                                                <?php echo $cat; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="edit_brand" class="form-label">Brand <span class="text-danger">*</span></label>
                                    <select class="form-select" id="edit_brand" name="brand" required>
                                        <option value="">Select Brand</option>
                                        <?php
                                        $brands_list = ['Nike', 'Adidas', 'Puma', 'Reebok', 'New Balance', 'Converse', 'Vans', 'Other'];
                                        foreach ($brands_list as $brand): ?>
                                            <option value="<?php echo $brand; ?>" <?php echo $edit_product['brand'] === $brand ? 'selected' : ''; ?>>
                                                <?php echo $brand; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="edit_price" class="form-label">Price (ETB) <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text">ETB</span>
                                        <input type="number" class="form-control" id="edit_price" name="price"
                                            value="<?php echo $edit_product['price']; ?>"
                                            min="1" step="0.01" required>
                                    </div>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="edit_discount_price" class="form-label">Discount Price (ETB)</label>
                                    <div class="input-group">
                                        <span class="input-group-text">ETB</span>
                                        <input type="number" class="form-control" id="edit_discount_price" name="discount_price"
                                            value="<?php echo $edit_product['discount_price'] ?? ''; ?>"
                                            min="0" step="0.01">
                                    </div>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="edit_stock_quantity" class="form-label">Stock Quantity <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" id="edit_stock_quantity" name="stock_quantity"
                                        value="<?php echo $edit_product['stock_quantity']; ?>"
                                        min="0" required>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="edit_size" class="form-label">Size</label>
                                    <select class="form-select" id="edit_size" name="size">
                                        <option value="">Select Size</option>
                                        <?php
                                        $sizes = ['US 5', 'US 6', 'US 7', 'US 8', 'US 9', 'US 10', 'US 11', 'US 12', 'US 13'];
                                        foreach ($sizes as $size): ?>
                                            <option value="<?php echo $size; ?>" <?php echo ($edit_product['size'] ?? '') === $size ? 'selected' : ''; ?>>
                                                <?php echo $size; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="edit_color" class="form-label">Color</label>
                                    <input type="text" class="form-control" id="edit_color" name="color"
                                        value="<?php echo htmlspecialchars($edit_product['color'] ?? ''); ?>">
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="edit_material" class="form-label">Material</label>
                                    <input type="text" class="form-control" id="edit_material" name="material"
                                        value="<?php echo htmlspecialchars($edit_product['material'] ?? ''); ?>">
                                </div>

                                <div class="col-12 mb-3">
                                    <label for="edit_description" class="form-label">Description <span class="text-danger">*</span></label>
                                    <textarea class="form-control" id="edit_description" name="description" rows="4" required><?php echo htmlspecialchars($edit_product['description']); ?></textarea>
                                </div>

                                <!-- Existing Images -->
                                <?php if (!empty($edit_product['images'])): ?>
                                    <div class="col-12 mb-3">
                                        <label class="form-label">Existing Images</label>
                                        <div class="image-preview-container">
                                            <?php foreach ($edit_product['images'] as $index => $image): ?>
                                                <div class="image-preview">
                                                    <img src="<?php echo '../' . $image; ?>"
                                                        alt="Product Image <?php echo $index + 1; ?>">
                                                    <a href="products.php?delete_image=<?php echo $index; ?>&product_id=<?php echo $edit_product['id']; ?>"
                                                        class="remove-btn"
                                                        onclick="return confirm('Delete this image?')">
                                                        <i class="fas fa-times"></i>
                                                    </a>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <div class="col-12 mb-3">
                                    <label for="edit_images" class="form-label">Add More Images</label>
                                    <input type="file" class="form-control" id="edit_images" name="images[]"
                                        accept="image/*" multiple onchange="previewEditImages(this)">
                                    <small class="text-muted">Add more images (JPEG, PNG, GIF). Max 5MB each.</small>
                                    <div id="edit-image-preview" class="image-preview-container mt-2"></div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <a href="products.php" class="btn btn-secondary">Cancel</a>
                            <button type="submit" name="update_product" class="btn btn-warning">
                                <i class="fas fa-save me-2"></i>Update Product
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="modal-backdrop fade show"></div>
    <?php endif; ?>

    <!-- Vendor Footer -->
    <?php if (file_exists('includes/footer.php')): ?>
        <?php include 'includes/footer.php'; ?>
    <?php else: ?>
        <!-- Fallback Footer -->
        <footer class="bg-dark text-white py-4 mt-5">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-md-6">
                        <p class="mb-0">&copy; <?php echo date('Y'); ?> Snaker-Mart. All rights reserved.</p>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <a href="../public/contact.php" class="text-white text-decoration-none me-3">Contact</a>
                        <a href="../public/terms.php" class="text-white text-decoration-none me-3">Terms</a>
                        <a href="../public/privacy.php" class="text-white text-decoration-none">Privacy</a>
                    </div>
                </div>
            </div>
        </footer>
    <?php endif; ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Image preview for add product form
        function previewImages(input) {
            const preview = document.getElementById('image-preview');
            if (preview) {
                preview.innerHTML = '';

                if (input.files) {
                    const fileCount = Math.min(input.files.length, 5); // Limit to 5 images

                    for (let i = 0; i < fileCount; i++) {
                        const reader = new FileReader();
                        const file = input.files[i];

                        if (file.size > 5 * 1024 * 1024) {
                            alert(`File "${file.name}" is too large. Maximum size is 5MB.`);
                            continue;
                        }

                        if (!file.type.match('image/jpeg') && !file.type.match('image/png') && !file.type.match('image/gif')) {
                            alert(`File "${file.name}" is not a valid image. Only JPEG, PNG, and GIF files are allowed.`);
                            continue;
                        }

                        reader.onload = function(e) {
                            const div = document.createElement('div');
                            div.className = 'image-preview';
                            div.innerHTML = `
                                <img src="${e.target.result}" alt="Preview">
                                <button type="button" class="remove-btn" onclick="removeImage(this, ${i})">
                                    <i class="fas fa-times"></i>
                                </button>
                            `;
                            preview.appendChild(div);
                        };
                        reader.readAsDataURL(file);
                    }
                }
            }
        }

        // Image preview for edit product form
        function previewEditImages(input) {
            const preview = document.getElementById('edit-image-preview');
            if (preview) {
                preview.innerHTML = '';

                if (input.files) {
                    const fileCount = Math.min(input.files.length, 5); // Limit to 5 images

                    for (let i = 0; i < fileCount; i++) {
                        const reader = new FileReader();
                        const file = input.files[i];

                        if (file.size > 5 * 1024 * 1024) {
                            alert(`File "${file.name}" is too large. Maximum size is 5MB.`);
                            continue;
                        }

                        if (!file.type.match('image/jpeg') && !file.type.match('image/png') && !file.type.match('image/gif')) {
                            alert(`File "${file.name}" is not a valid image. Only JPEG, PNG, and GIF files are allowed.`);
                            continue;
                        }

                        reader.onload = function(e) {
                            const div = document.createElement('div');
                            div.className = 'image-preview';
                            div.innerHTML = `
                                <img src="${e.target.result}" alt="Preview">
                                <button type="button" class="remove-btn" onclick="removeEditImage(this, ${i})">
                                    <i class="fas fa-times"></i>
                                </button>
                            `;
                            preview.appendChild(div);
                        };
                        reader.readAsDataURL(file);
                    }
                }
            }
        }

        // Remove image from preview
        function removeImage(button, index) {
            const container = button.parentElement;
            container.remove();

            // Update file input
            const input = document.getElementById('images');
            const dt = new DataTransfer();

            for (let i = 0; i < input.files.length; i++) {
                if (i !== index) {
                    dt.items.add(input.files[i]);
                }
            }

            input.files = dt.files;
        }

        function removeEditImage(button, index) {
            const container = button.parentElement;
            container.remove();

            // Update file input
            const input = document.getElementById('edit_images');
            const dt = new DataTransfer();

            for (let i = 0; i < input.files.length; i++) {
                if (i !== index) {
                    dt.items.add(input.files[i]);
                }
            }

            input.files = dt.files;
        }

        // Price validation
        document.addEventListener('DOMContentLoaded', function() {
            const priceInput = document.getElementById('price');
            const discountInput = document.getElementById('discount_price');

            if (priceInput && discountInput) {
                priceInput.addEventListener('change', validatePrice);
                discountInput.addEventListener('change', validatePrice);
            }

            function validatePrice() {
                const price = parseFloat(priceInput.value) || 0;
                const discount = parseFloat(discountInput.value) || 0;

                if (discount > 0 && discount >= price) {
                    alert('Discount price must be less than regular price.');
                    discountInput.value = '';
                    discountInput.focus();
                }
            }

            // Edit form price validation
            const editPriceInput = document.getElementById('edit_price');
            const editDiscountInput = document.getElementById('edit_discount_price');

            if (editPriceInput && editDiscountInput) {
                editPriceInput.addEventListener('change', validateEditPrice);
                editDiscountInput.addEventListener('change', validateEditPrice);
            }

            function validateEditPrice() {
                const price = parseFloat(editPriceInput.value) || 0;
                const discount = parseFloat(editDiscountInput.value) || 0;

                if (discount > 0 && discount >= price) {
                    alert('Discount price must be less than regular price.');
                    editDiscountInput.value = '';
                    editDiscountInput.focus();
                }
            }

            // Auto-show edit modal if needed
            <?php if ($edit_product): ?>
                const editModal = new bootstrap.Modal(document.getElementById('editProductModal'));
                editModal.show();
            <?php endif; ?>

            // Initialize tooltips
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function(tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });

        // Form validation
        const addProductForm = document.getElementById('add-product-form');
        if (addProductForm) {
            addProductForm.addEventListener('submit', function(e) {
                const price = parseFloat(document.getElementById('price').value) || 0;
                const discount = parseFloat(document.getElementById('discount_price').value) || 0;
                const stock = parseInt(document.getElementById('stock_quantity').value) || 0;
                const sku = document.getElementById('sku').value.trim();
                const name = document.getElementById('name').value.trim();

                if (price <= 0) {
                    e.preventDefault();
                    alert('Price must be greater than 0.');
                    document.getElementById('price').focus();
                    return;
                }

                if (discount > 0 && discount >= price) {
                    e.preventDefault();
                    alert('Discount price must be less than regular price.');
                    document.getElementById('discount_price').focus();
                    return;
                }

                if (stock < 0) {
                    e.preventDefault();
                    alert('Stock quantity cannot be negative.');
                    document.getElementById('stock_quantity').focus();
                    return;
                }

                if (sku.length < 3) {
                    e.preventDefault();
                    alert('SKU must be at least 3 characters long.');
                    document.getElementById('sku').focus();
                    return;
                }

                if (name.length < 3) {
                    e.preventDefault();
                    alert('Product name must be at least 3 characters long.');
                    document.getElementById('name').focus();
                    return;
                }

                // Check image file sizes
                const imageInput = document.getElementById('images');
                if (imageInput && imageInput.files.length > 0) {
                    let totalSize = 0;
                    for (let file of imageInput.files) {
                        totalSize += file.size;
                    }

                    if (totalSize > 25 * 1024 * 1024) { // 25MB total limit
                        e.preventDefault();
                        alert('Total image size exceeds 25MB limit. Please reduce image sizes or upload fewer images.');
                        return;
                    }
                }
            });
        }
    </script>
</body>

</html>