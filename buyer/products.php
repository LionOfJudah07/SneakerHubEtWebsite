<?php
require_once '../config.php';

// Require vendor login
require_vendor();

$page_title = 'Manage Products - ' . SITE_NAME;

// Get vendor data
$user = new User();
$vendor_data = $user->getVendorProfile($_SESSION['user_id']);

// Get action
$action = $_GET['action'] ?? '';
$product_id = $_GET['id'] ?? 0;

// Handle product actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product = new Product();
    
    if ($action === 'add') {
        try {
            // Handle file uploads
            $images = [];
            if (!empty($_FILES['images']['name'][0])) {
                $target_dir = '../assets/images/products/';
                
                foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
                    if ($_FILES['images']['error'][$key] === UPLOAD_ERR_OK) {
                        $file_name = uniqid() . '_' . basename($_FILES['images']['name'][$key]);
                        $target_file = $target_dir . $file_name;
                        
                        if (move_uploaded_file($tmp_name, $target_file)) {
                            $images[] = 'assets/images/products/' . $file_name;
                        }
                    }
                }
            }
            
            // Prepare product data
            $product_data = [
                'vendor_id' => $vendor_data['id'],
                'name' => sanitize_input($_POST['name']),
                'description' => sanitize_input($_POST['description']),
                'category' => sanitize_input($_POST['category']),
                'brand' => sanitize_input($_POST['brand']),
                'price' => floatval($_POST['price']),
                'discount_price' => !empty($_POST['discount_price']) ? floatval($_POST['discount_price']) : null,
                'size_range' => sanitize_input($_POST['size_range']),
                'colors' => sanitize_input($_POST['colors']),
                'stock_quantity' => intval($_POST['stock_quantity']),
                'condition' => sanitize_input($_POST['condition']),
                'images' => $images,
                'sku' => !empty($_POST['sku']) ? sanitize_input($_POST['sku']) : null
            ];
            
            $product_id = $product->create($product_data);
            
            $_SESSION['success'] = 'Product added successfully!';
            redirect('products.php');
            
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
        
    } elseif ($action === 'edit' && $product_id) {
        try {
            // Get existing product
            $existing_product = $product->getProductById($product_id);
            
            // Check if product belongs to vendor
            if ($existing_product['vendor_id'] != $vendor_data['id']) {
                throw new Exception('You do not have permission to edit this product.');
            }
            
            // Handle file uploads
            $images = $existing_product['images'] ?? [];
            if (!empty($_FILES['images']['name'][0])) {
                $target_dir = '../assets/images/products/';
                
                foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
                    if ($_FILES['images']['error'][$key] === UPLOAD_ERR_OK) {
                        $file_name = uniqid() . '_' . basename($_FILES['images']['name'][$key]);
                        $target_file = $target_dir . $file_name;
                        
                        if (move_uploaded_file($tmp_name, $target_file)) {
                            $images[] = 'assets/images/products/' . $file_name;
                        }
                    }
                }
            }
            
            // Remove images if requested
            if (!empty($_POST['remove_images'])) {
                $remove_images = $_POST['remove_images'];
                $images = array_filter($images, function($image) use ($remove_images) {
                    return !in_array($image, $remove_images);
                });
            }
            
            // Prepare update data
            $update_data = [
                'name' => sanitize_input($_POST['name']),
                'description' => sanitize_input($_POST['description']),
                'category' => sanitize_input($_POST['category']),
                'brand' => sanitize_input($_POST['brand']),
                'price' => floatval($_POST['price']),
                'discount_price' => !empty($_POST['discount_price']) ? floatval($_POST['discount_price']) : null,
                'size_range' => sanitize_input($_POST['size_range']),
                'colors' => sanitize_input($_POST['colors']),
                'stock_quantity' => intval($_POST['stock_quantity']),
                'condition' => sanitize_input($_POST['condition']),
                'images' => $images,
                'is_active' => isset($_POST['is_active']) ? 1 : 0,
                'sku' => !empty($_POST['sku']) ? sanitize_input($_POST['sku']) : $existing_product['sku']
            ];
            
            $product->update($product_id, $update_data);
            
            $_SESSION['success'] = 'Product updated successfully!';
            redirect('products.php');
            
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}

// Handle delete action
if ($action === 'delete' && $product_id) {
    $product = new Product();
    $existing_product = $product->getProductById($product_id);
    
    if ($existing_product && $existing_product['vendor_id'] == $vendor_data['id']) {
        $product->delete($product_id);
        $_SESSION['success'] = 'Product deleted successfully!';
    } else {
        $_SESSION['error'] = 'You do not have permission to delete this product.';
    }
    
    redirect('products.php');
}

// Get products for listing
$filter = $_GET['filter'] ?? '';
$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 10;

// Prepare filters
$filters = ['vendor_id' => $vendor_data['id']];
if (!empty($filter)) {
    if ($filter === 'low_stock') {
        $filters['max_stock'] = 10;
    } elseif ($filter === 'out_of_stock') {
        $filters['max_stock'] = 0;
    } elseif ($filter === 'inactive') {
        $filters['is_active'] = false;
    }
}
if (!empty($search)) $filters['search'] = $search;
if (!empty($category)) $filters['category'] = $category;

// Get products
$product = new Product();
$total_products = $product->countProducts($filters);
$products = $product->getProducts($filters, $per_page, ($page - 1) * $per_page);

// Get categories for filter
$categories = $product->getCategories();

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
    <?php include '../public/includes/navbar.php'; ?>

    <!-- Dashboard Layout -->
    <div class="container-fluid py-4">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-lg-3">
                <?php include 'includes/sidebar.php'; ?>
            </div>
            
            <!-- Main Content -->
            <div class="col-lg-9">
                <!-- Page Header -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2 class="mb-1">Manage Products</h2>
                        <p class="text-muted mb-0">Add, edit, and manage your products</p>
                    </div>
                    <a href="products.php?action=add" class="btn btn-success">
                        <i class="fas fa-plus me-2"></i>Add New Product
                    </a>
                </div>
                
                <!-- Success/Error Messages -->
                <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show mb-4">
                    <i class="fas fa-check-circle me-2"></i>
                    <?php echo $_SESSION['success']; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    <?php unset($_SESSION['success']); ?>
                </div>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show mb-4">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?php echo $_SESSION['error']; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    <?php unset($_SESSION['error']); ?>
                </div>
                <?php endif; ?>
                
                <?php if (isset($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show mb-4">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>
                
                <?php if ($action === 'add' || $action === 'edit'): ?>
                <!-- Add/Edit Product Form -->
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-<?php echo $action === 'add' ? 'plus' : 'edit'; ?> me-2"></i>
                            <?php echo $action === 'add' ? 'Add New Product' : 'Edit Product'; ?>
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php
                        $edit_product = null;
                        if ($action === 'edit' && $product_id) {
                            $product_obj = new Product();
                            $edit_product = $product_obj->getProductById($product_id);
                            
                            if (!$edit_product || $edit_product['vendor_id'] != $vendor_data['id']) {
                                $_SESSION['error'] = 'Product not found or access denied.';
                                redirect('products.php');
                            }
                        }
                        ?>
                        
                        <form method="POST" enctype="multipart/form-data" id="product-form">
                            <div class="row">
                                <!-- Basic Information -->
                                <div class="col-md-6 mb-3">
                                    <label for="name" class="form-label">Product Name *</label>
                                    <input type="text" class="form-control" id="name" name="name" 
                                           value="<?php echo htmlspecialchars($edit_product['name'] ?? ''); ?>" required>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="category" class="form-label">Category *</label>
                                    <select class="form-select" id="category" name="category" required>
                                        <option value="">Select Category</option>
                                        <option value="Running" <?php echo ($edit_product['category'] ?? '') == 'Running' ? 'selected' : ''; ?>>Running</option>
                                        <option value="Basketball" <?php echo ($edit_product['category'] ?? '') == 'Basketball' ? 'selected' : ''; ?>>Basketball</option>
                                        <option value="Lifestyle" <?php echo ($edit_product['category'] ?? '') == 'Lifestyle' ? 'selected' : ''; ?>>Lifestyle</option>
                                        <option value="Training" <?php echo ($edit_product['category'] ?? '') == 'Training' ? 'selected' : ''; ?>>Training</option>
                                        <option value="Skateboarding" <?php echo ($edit_product['category'] ?? '') == 'Skateboarding' ? 'selected' : ''; ?>>Skateboarding</option>
                                        <option value="Football" <?php echo ($edit_product['category'] ?? '') == 'Football' ? 'selected' : ''; ?>>Football</option>
                                        <option value="Other" <?php echo ($edit_product['category'] ?? '') == 'Other' ? 'selected' : ''; ?>>Other</option>
                                    </select>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="brand" class="form-label">Brand *</label>
                                    <input type="text" class="form-control" id="brand" name="brand" 
                                           value="<?php echo htmlspecialchars($edit_product['brand'] ?? ''); ?>" required>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="sku" class="form-label">SKU (Optional)</label>
                                    <input type="text" class="form-control" id="sku" name="sku" 
                                           value="<?php echo htmlspecialchars($edit_product['sku'] ?? ''); ?>">
                                    <small class="text-muted">Leave blank to auto-generate</small>
                                </div>
                                
                                <!-- Pricing -->
                                <div class="col-md-4 mb-3">
                                    <label for="price" class="form-label">Price (ETB) *</label>
                                    <input type="number" step="0.01" class="form-control" id="price" name="price" 
                                           value="<?php echo $edit_product['price'] ?? ''; ?>" required min="0">
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <label for="discount_price" class="form-label">Discount Price (ETB)</label>
                                    <input type="number" step="0.01" class="form-control" id="discount_price" name="discount_price" 
                                           value="<?php echo $edit_product['discount_price'] ?? ''; ?>" min="0">
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <label for="condition" class="form-label">Condition *</label>
                                    <select class="form-select" id="condition" name="condition" required>
                                        <option value="new" <?php echo ($edit_product['condition'] ?? '') == 'new' ? 'selected' : ''; ?>>New</option>
                                        <option value="used" <?php echo ($edit_product['condition'] ?? '') == 'used' ? 'selected' : ''; ?>>Used</option>
                                        <option value="refurbished" <?php echo ($edit_product['condition'] ?? '') == 'refurbished' ? 'selected' : ''; ?>>Refurbished</option>
                                    </select>
                                </div>
                                
                                <!-- Stock & Variations -->
                                <div class="col-md-4 mb-3">
                                    <label for="stock_quantity" class="form-label">Stock Quantity *</label>
                                    <input type="number" class="form-control" id="stock_quantity" name="stock_quantity" 
                                           value="<?php echo $edit_product['stock_quantity'] ?? 0; ?>" required min="0">
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <label for="size_range" class="form-label">Size Range</label>
                                    <input type="text" class="form-control" id="size_range" name="size_range" 
                                           value="<?php echo htmlspecialchars($edit_product['size_range'] ?? ''); ?>"
                                           placeholder="e.g., 7,8,9,10,11">
                                    <small class="text-muted">Comma-separated sizes</small>
                                </div>
                                
                                <div class="col-md-4 mb-3">
                                    <label for="colors" class="form-label">Colors</label>
                                    <input type="text" class="form-control" id="colors" name="colors" 
                                           value="<?php echo htmlspecialchars($edit_product['colors'] ?? ''); ?>"
                                           placeholder="e.g., Red,Blue,Black">
                                    <small class="text-muted">Comma-separated colors</small>
                                </div>
                                
                                <!-- Description -->
                                <div class="col-12 mb-3">
                                    <label for="description" class="form-label">Description *</label>
                                    <textarea class="form-control" id="description" name="description" rows="4" required><?php echo htmlspecialchars($edit_product['description'] ?? ''); ?></textarea>
                                </div>
                                
                                <!-- Images -->
                                <div class="col-12 mb-3">
                                    <label class="form-label">Product Images</label>
                                    
                                    <?php if ($action === 'edit' && !empty($edit_product['images'])): ?>
                                    <div class="mb-3">
                                        <h6>Current Images</h6>
                                        <div class="row">
                                            <?php foreach ($edit_product['images'] as $index => $image): ?>
                                            <div class="col-md-2 col-4 mb-3">
                                                <div class="position-relative">
                                                    <img src="../<?php echo $image; ?>" 
                                                         alt="Product Image <?php echo $index + 1; ?>"
                                                         class="img-thumbnail" 
                                                         style="height: 100px; object-fit: cover;">
                                                    <div class="form-check position-absolute top-0 start-0 m-1">
                                                        <input class="form-check-input" type="checkbox" 
                                                               name="remove_images[]" value="<?php echo $image; ?>"
                                                               id="remove-<?php echo $index; ?>">
                                                        <label class="form-check-label text-white" for="remove-<?php echo $index; ?>">
                                                            <i class="fas fa-times"></i>
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <div class="mb-3">
                                        <label for="images" class="form-label">Upload New Images</label>
                                        <input type="file" class="form-control" id="images" name="images[]" 
                                               accept="image/*" multiple>
                                        <small class="text-muted">Upload up to 5 images (JPEG, PNG, GIF, Max 5MB each)</small>
                                    </div>
                                    
                                    <div id="image-preview" class="row"></div>
                                </div>
                                
                                <!-- Status -->
                                <?php if ($action === 'edit'): ?>
                                <div class="col-12 mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="is_active" name="is_active" 
                                               <?php echo ($edit_product['is_active'] ?? true) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="is_active">
                                            Product is active and visible to customers
                                        </label>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <!-- Submit Buttons -->
                                <div class="col-12">
                                    <div class="d-flex justify-content-between">
                                        <a href="products.php" class="btn btn-outline-secondary">Cancel</a>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save me-2"></i>
                                            <?php echo $action === 'add' ? 'Add Product' : 'Update Product'; ?>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                
                <?php else: ?>
                <!-- Filters -->
                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Filter</label>
                                <select name="filter" class="form-select">
                                    <option value="">All Products</option>
                                    <option value="low_stock" <?php echo $filter === 'low_stock' ? 'selected' : ''; ?>>Low Stock (< 10)</option>
                                    <option value="out_of_stock" <?php echo $filter === 'out_of_stock' ? 'selected' : ''; ?>>Out of Stock</option>
                                    <option value="inactive" <?php echo $filter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Category</label>
                                <select name="category" class="form-select">
                                    <option value="">All Categories</option>
                                    <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat; ?>" <?php echo $category === $cat ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Search</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" name="search" 
                                           placeholder="Search by name, brand, or SKU..." value="<?php echo htmlspecialchars($search); ?>">
                                    <button class="btn btn-primary" type="submit">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <a href="products.php" class="btn btn-outline-secondary w-100">Reset</a>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Products List -->
                <div class="card shadow-sm">
                    <div class="card-header bg-light d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Your Products</h5>
                        <span class="badge bg-primary"><?php echo $total_products; ?> products</span>
                    </div>
                    
                    <div class="card-body">
                        <?php if (empty($products)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-boxes fa-4x text-muted mb-3"></i>
                            <h4>No Products Found</h4>
                            <p class="text-muted mb-4">You haven't added any products yet.</p>
                            <a href="products.php?action=add" class="btn btn-success btn-lg">
                                <i class="fas fa-plus me-2"></i>Add Your First Product
                            </a>
                        </div>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>SKU</th>
                                        <th>Price</th>
                                        <th>Stock</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($products as $product_item): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <img src="<?php echo !empty($product_item['images']) ? '../' . $product_item['images'][0] : '../assets/images/products/default.jpg'; ?>" 
                                                     alt="<?php echo htmlspecialchars($product_item['name']); ?>"
                                                     class="img-thumbnail me-3" 
                                                     style="width: 60px; height: 60px; object-fit: cover;">
                                                <div>
                                                    <h6 class="mb-0"><?php echo htmlspecialchars($product_item['name']); ?></h6>
                                                    <small class="text-muted"><?php echo htmlspecialchars($product_item['brand']); ?></small>
                                                    <br>
                                                    <small class="text-muted"><?php echo htmlspecialchars($product_item['category']); ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($product_item['sku']); ?></td>
                                        <td>
                                            <?php if ($product_item['discount_price']): ?>
                                            <span class="text-danger fw-bold"><?php echo format_price($product_item['discount_price']); ?></span>
                                            <br>
                                            <small class="text-muted text-decoration-line-through"><?php echo format_price($product_item['price']); ?></small>
                                            <?php else: ?>
                                            <?php echo format_price($product_item['price']); ?>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                if ($product_item['stock_quantity'] == 0) echo 'danger';
                                                elseif ($product_item['stock_quantity'] < 10) echo 'warning';
                                                else echo 'success';
                                            ?>">
                                                <?php echo $product_item['stock_quantity']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php echo $product_item['is_active'] ? 
                                                '<span class="badge bg-success">Active</span>' : 
                                                '<span class="badge bg-secondary">Inactive</span>'; ?>
                                            <br>
                                            <small class="text-muted"><?php echo get_product_condition_label($product_item['condition']); ?></small>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="../public/product-detail.php?id=<?php echo $product_item['id']; ?>" 
                                                   class="btn btn-outline-primary" title="View" target="_blank">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="products.php?action=edit&id=<?php echo $product_item['id']; ?>" 
                                                   class="btn btn-outline-warning" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="products.php?action=delete&id=<?php echo $product_item['id']; ?>" 
                                                   class="btn btn-outline-danger" 
                                                   onclick="return confirm('Delete this product?')" title="Delete">
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
                        <?php if ($total_products > $per_page): ?>
                        <nav aria-label="Page navigation" class="mt-4">
                            <ul class="pagination justify-content-center">
                                <?php
                                $total_pages = ceil($total_products / $per_page);
                                $current_url = $_SERVER['PHP_SELF'] . '?' . http_build_query($_GET);
                                $current_url = preg_replace('/&page=\d+/', '', $current_url);
                                ?>
                                
                                <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="<?php echo $current_url . '&page=' . ($page - 1); ?>">Previous</a>
                                </li>
                                
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <?php if ($i == 1 || $i == $total_pages || ($i >= $page - 2 && $i <= $page + 2)): ?>
                                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                        <a class="page-link" href="<?php echo $current_url . '&page=' . $i; ?>"><?php echo $i; ?></a>
                                    </li>
                                    <?php elseif ($i == $page - 3 || $i == $page + 3): ?>
                                    <li class="page-item disabled">
                                        <span class="page-link">...</span>
                                    </li>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                                
                                <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="<?php echo $current_url . '&page=' . ($page + 1); ?>">Next</a>
                                </li>
                            </ul>
                        </nav>
                        <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <?php include '../public/includes/footer.php'; ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script src="../assets/js/vendor.js"></script>
    
    <script>
        // Image preview for file uploads
        document.getElementById('images').addEventListener('change', function(e) {
            const preview = document.getElementById('image-preview');
            preview.innerHTML = '';
            
            const files = e.target.files;
            for (let i = 0; i < files.length; i++) {
                const file = files[i];
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    const col = document.createElement('div');
                    col.className = 'col-md-2 col-4 mb-3';
                    
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.className = 'img-thumbnail';
                    img.style.height = '100px';
                    img.style.objectFit = 'cover';
                    
                    col.appendChild(img);
                    preview.appendChild(col);
                }
                
                reader.readAsDataURL(file);
            }
        });
        
        // Auto-calculate discount percentage
        const priceInput = document.getElementById('price');
        const discountInput = document.getElementById('discount_price');
        
        function calculateDiscount() {
            if (priceInput.value && discountInput.value) {
                const price = parseFloat(priceInput.value);
                const discount = parseFloat(discountInput.value);
                
                if (discount > 0 && discount < price) {
                    const percentage = ((price - discount) / price * 100).toFixed(1);
                    discountInput.nextElementSibling.textContent = 
                        `Discount: ${percentage}% off (Save: ETB ${(price - discount).toFixed(2)})`;
                }
            }
        }
        
        priceInput.addEventListener('input', calculateDiscount);
        discountInput.addEventListener('input', calculateDiscount);
    </script>
</body>
</html>