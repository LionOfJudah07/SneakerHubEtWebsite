<?php
require_once '../config.php';

// Require admin login
require_admin();

$page_title = 'Products Management - ' . SITE_NAME;

// Handle product actions
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_status'])) {
        // Update product status
        $product_id = intval($_POST['product_id']);
        $status = sanitize_input($_POST['status']);

        try {
            $db = new Database();
            $db->query("UPDATE products SET status = :status, updated_at = NOW() WHERE id = :id");
            $db->bind(':status', $status);
            $db->bind(':id', $product_id);
            $db->execute();

            $success = 'Product status updated successfully!';
        } catch (Exception $e) {
            $errors[] = 'Failed to update product status: ' . $e->getMessage();
        }
    }
}

// Handle delete product
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $product_id = intval($_GET['delete']);

    try {
        $db = new Database();

        // Get product images to delete from server
        $db->query("SELECT images FROM products WHERE id = :id");
        $db->bind(':id', $product_id);
        $product = $db->single();

        if ($product) {
            // Delete product
            $db->query("DELETE FROM products WHERE id = :id");
            $db->bind(':id', $product_id);
            $db->execute();

            // Delete product images
            if (!empty($product['images'])) {
                $images = json_decode($product['images'], true);
                foreach ($images as $image) {
                    if (file_exists('../' . $image)) {
                        unlink('../' . $image);
                    }
                }
            }

            $_SESSION['success'] = 'Product deleted successfully!';
        }
    } catch (Exception $e) {
        $_SESSION['error'] = 'Failed to delete product: ' . $e->getMessage();
    }

    redirect('products.php');
}

// Handle bulk actions
if (isset($_POST['bulk_action'])) {
    $action = sanitize_input($_POST['bulk_action']);
    $product_ids = $_POST['product_ids'] ?? [];

    if (empty($product_ids)) {
        $errors[] = 'No products selected.';
    } else {
        try {
            $db = new Database();

            if ($action === 'activate') {
                $placeholders = implode(',', array_fill(0, count($product_ids), '?'));
                $db->query("UPDATE products SET status = 'active', updated_at = NOW() WHERE id IN ($placeholders)");
                foreach ($product_ids as $k => $id) {
                    $db->bind($k + 1, $id, PDO::PARAM_INT);
                }
                $db->execute();
                $success = count($product_ids) . ' products activated!';
            } elseif ($action === 'deactivate') {
                $placeholders = implode(',', array_fill(0, count($product_ids), '?'));
                $db->query("UPDATE products SET status = 'inactive', updated_at = NOW() WHERE id IN ($placeholders)");
                foreach ($product_ids as $k => $id) {
                    $db->bind($k + 1, $id, PDO::PARAM_INT);
                }
                $db->execute();
                $success = count($product_ids) . ' products deactivated!';
            } elseif ($action === 'delete') {
                // Get images to delete
                $placeholders = implode(',', array_fill(0, count($product_ids), '?'));
                $db->query("SELECT images FROM products WHERE id IN ($placeholders)");
                foreach ($product_ids as $k => $id) {
                    $db->bind($k + 1, $id, PDO::PARAM_INT);
                }
                $products = $db->resultSet();

                foreach ($products as $product) {
                    if (!empty($product['images'])) {
                        $images = json_decode($product['images'], true);
                        foreach ($images as $image) {
                            if (file_exists('../' . $image)) {
                                unlink('../' . $image);
                            }
                        }
                    }
                }

                $db->query("DELETE FROM products WHERE id IN ($placeholders)");
                foreach ($product_ids as $k => $id) {
                    $db->bind($k + 1, $id, PDO::PARAM_INT);
                }
                $db->execute();
                $success = count($product_ids) . ' products deleted!';
            }
        } catch (Exception $e) {
            $errors[] = 'Failed to perform bulk action: ' . $e->getMessage();
        }
    }
}

// Get filter parameters
$status_filter = $_GET['status'] ?? '';
$category_filter = $_GET['category'] ?? '';
$search_query = $_GET['search'] ?? '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 20;

// Initialize Database
$db = new Database();

// Build base query for counting
$count_query = "SELECT COUNT(*) as total FROM products p WHERE 1=1";
$count_params = [];

// Build filtered query for fetching data
$query = "SELECT p.*, u.first_name as vendor_name, u.store_name 
          FROM products p 
          LEFT JOIN users u ON p.vendor_id = u.id 
          WHERE 1=1";

$params = [];

// Add filters
if (!empty($status_filter)) {
    $query .= " AND p.status = :status";
    $count_query .= " AND p.status = :status";
    $params[':status'] = $status_filter;
    $count_params[':status'] = $status_filter;
}

if (!empty($category_filter)) {
    $query .= " AND p.category = :category";
    $count_query .= " AND p.category = :category";
    $params[':category'] = $category_filter;
    $count_params[':category'] = $category_filter;
}

if (!empty($search_query)) {
    $query .= " AND (p.name ILIKE :search OR p.sku ILIKE :search OR p.description ILIKE :search)";
    $count_query .= " AND (p.name ILIKE :search OR p.sku ILIKE :search OR p.description ILIKE :search)";
    $params[':search'] = '%' . $search_query . '%';
    $count_params[':search'] = '%' . $search_query . '%';
}

// Get total count for pagination
$db->query($count_query);
foreach ($count_params as $key => $value) {
    $db->bind($key, $value);
}
$total_count_result = $db->single();
$total_count = $total_count_result ? $total_count_result['total'] : 0;

// Setup pagination
$total_pages = ceil($total_count / $per_page);
$offset = ($page - 1) * $per_page;

// Add sorting and pagination to main query
$query .= " ORDER BY p.created_at DESC LIMIT :limit OFFSET :offset";
$params[':limit'] = $per_page;
$params[':offset'] = $offset;

// Get products
$db->query($query);
foreach ($params as $key => $value) {
    if ($key === ':limit' || $key === ':offset') {
        $db->bind($key, $value, PDO::PARAM_INT);
    } else {
        $db->bind($key, $value);
    }
}

$products = $db->resultSet();

// Get categories for filter
$db->query("SELECT DISTINCT category FROM products WHERE category IS NOT NULL AND category != '' ORDER BY category");
$categories = $db->resultSet();

$cart_count = get_cart_count();
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
    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    <!-- Custom CSS -->
    <link href="../assets/css/style.css" rel="stylesheet">
    <link href="../assets/css/bootstrap-custom.css" rel="stylesheet">
    <link href="../assets/css/admin.css" rel="stylesheet">
    <style>
        .sidebar {
            min-height: 100vh;
            background-color: #343a40;
            padding-top: 20px;
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

        .main-content {
            padding-top: 20px;
            padding-left: 0;
        }

        @media (min-width: 768px) {
            .main-content {
                padding-left: 240px;
            }

            .sidebar {
                position: fixed;
                top: 0;
                left: 0;
                width: 240px;
                height: 100vh;
                overflow-y: auto;
                z-index: 1000;
            }
        }

        .table th {
            font-weight: 600;
            border-top: none;
        }

        .img-thumbnail {
            width: 60px;
            height: 60px;
            object-fit: cover;
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
                    <a class="nav-link active" href="products.php">
                        <i class="fas fa-box me-2"></i>
                        Products
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="users.php">
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
        <div class="container-fluid px-4 py-4">
            <!-- Page Header -->
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Products Management</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="product-add.php" class="btn btn-sm btn-primary">
                        <i class="fas fa-plus me-2"></i>Add New Product
                    </a>
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

            <!-- Filter Section -->
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="">All Status</option>
                                <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
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
                            <label class="form-label">Search</label>
                            <div class="input-group">
                                <input type="text" name="search" class="form-control"
                                    placeholder="Search by name, SKU, or description"
                                    value="<?php echo htmlspecialchars($search_query); ?>">
                                <button class="btn btn-outline-primary" type="submit">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <a href="products.php" class="btn btn-outline-secondary w-100">
                                <i class="fas fa-times me-2"></i>Clear
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Bulk Actions -->
            <form method="POST" id="bulk-form" class="mb-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center">
                        <div class="form-check me-3">
                            <input class="form-check-input" type="checkbox" id="select-all">
                            <label class="form-check-label" for="select-all">
                                Select All
                            </label>
                        </div>
                        <select name="bulk_action" class="form-select me-2" style="width: auto;">
                            <option value="">Bulk Actions</option>
                            <option value="activate">Activate</option>
                            <option value="deactivate">Deactivate</option>
                            <option value="delete">Delete</option>
                        </select>
                        <button type="submit" class="btn btn-sm btn-primary">
                            Apply
                        </button>
                    </div>
                    <div>
                        <span class="text-muted">
                            Showing <?php echo $offset + 1; ?>-<?php echo min($offset + $per_page, $total_count); ?> of <?php echo $total_count; ?> products
                        </span>
                    </div>
                </div>
            </form>

            <!-- Products Table -->
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th width="50">
                                        <input type="checkbox" class="form-check-input" id="table-select-all">
                                    </th>
                                    <th width="100">Image</th>
                                    <th>Product</th>
                                    <th>SKU</th>
                                    <th>Vendor</th>
                                    <th>Price</th>
                                    <th>Stock</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($products)): ?>
                                    <tr>
                                        <td colspan="9" class="text-center py-4">
                                            <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
                                            <h4>No products found</h4>
                                            <p class="text-muted">Try adjusting your filters or add a new product.</p>
                                            <a href="product-add.php" class="btn btn-primary">
                                                <i class="fas fa-plus me-2"></i>Add New Product
                                            </a>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($products as $product): ?>
                                        <tr>
                                            <td>
                                                <input type="checkbox" name="product_ids[]"
                                                    value="<?php echo $product['id']; ?>"
                                                    class="form-check-input product-checkbox">
                                            </td>
                                            <td>
                                                <?php
                                                $images = !empty($product['images']) ? json_decode($product['images'], true) : [];
                                                $first_image = !empty($images) ? '../' . $images[0] : '../assets/images/products/default.jpg';
                                                ?>
                                                <img src="<?php echo $first_image; ?>"
                                                    alt="<?php echo htmlspecialchars($product['name']); ?>"
                                                    class="img-thumbnail"
                                                    onerror="this.src='../assets/images/products/default.jpg'">
                                            </td>
                                            <td>
                                                <h6 class="mb-1"><?php echo htmlspecialchars($product['name']); ?></h6>
                                                <p class="text-muted small mb-1"><?php echo htmlspecialchars($product['category'] ?? 'Uncategorized'); ?></p>
                                                <p class="text-muted small mb-0"><?php echo htmlspecialchars($product['brand'] ?? 'No brand'); ?></p>
                                            </td>
                                            <td>
                                                <code><?php echo htmlspecialchars($product['sku'] ?? 'N/A'); ?></code>
                                            </td>
                                            <td>
                                                <?php echo htmlspecialchars($product['store_name'] ?? $product['vendor_name'] ?? 'Unknown'); ?>
                                            </td>
                                            <td>
                                                <?php if (!empty($product['discount_price']) && $product['discount_price'] > 0): ?>
                                                    <span class="text-danger fw-bold"><?php echo format_price($product['discount_price']); ?></span>
                                                    <br>
                                                    <small class="text-muted text-decoration-line-through"><?php echo format_price($product['price']); ?></small>
                                                <?php else: ?>
                                                    <?php echo format_price($product['price'] ?? 0); ?>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if (($product['stock_quantity'] ?? 0) > 10): ?>
                                                    <span class="badge bg-success"><?php echo $product['stock_quantity']; ?></span>
                                                <?php elseif (($product['stock_quantity'] ?? 0) > 0): ?>
                                                    <span class="badge bg-warning"><?php echo $product['stock_quantity']; ?></span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger">Out of Stock</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php
                                                $status = $product['status'] ?? 'inactive';
                                                $status_badges = [
                                                    'active' => 'badge bg-success',
                                                    'inactive' => 'badge bg-secondary',
                                                    'pending' => 'badge bg-warning'
                                                ];
                                                $badge_class = $status_badges[$status] ?? 'badge bg-secondary';
                                                ?>
                                                <span class="<?php echo $badge_class; ?>">
                                                    <?php echo ucfirst($status); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="../public/product-detail.php?id=<?php echo $product['id']; ?>"
                                                        class="btn btn-outline-primary" title="View" target="_blank">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="product-edit.php?id=<?php echo $product['id']; ?>"
                                                        class="btn btn-outline-warning" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <button type="button" class="btn btn-outline-secondary"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#statusModal"
                                                        data-product-id="<?php echo $product['id']; ?>"
                                                        data-current-status="<?php echo $product['status'] ?? 'inactive'; ?>"
                                                        title="Change Status">
                                                        <i class="fas fa-exchange-alt"></i>
                                                    </button>
                                                    <a href="products.php?delete=<?php echo $product['id']; ?>"
                                                        class="btn btn-outline-danger"
                                                        onclick="return confirm('Are you sure you want to delete this product? This action cannot be undone.')"
                                                        title="Delete">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <nav aria-label="Page navigation" class="mt-4">
                            <ul class="pagination justify-content-center">
                                <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?<?php
                                                                $query_params = $_GET;
                                                                $query_params['page'] = $page - 1;
                                                                echo http_build_query($query_params);
                                                                ?>">
                                        Previous
                                    </a>
                                </li>

                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                        <a class="page-link" href="?<?php
                                                                    $query_params = $_GET;
                                                                    $query_params['page'] = $i;
                                                                    echo http_build_query($query_params);
                                                                    ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>

                                <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?<?php
                                                                $query_params = $_GET;
                                                                $query_params['page'] = $page + 1;
                                                                echo http_build_query($query_params);
                                                                ?>">
                                        Next
                                    </a>
                                </li>
                            </ul>
                        </nav>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <!-- Status Change Modal -->
    <div class="modal fade" id="statusModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header bg-warning text-white">
                        <h5 class="modal-title">Change Product Status</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="status_product_id" name="product_id">

                        <div class="mb-3">
                            <label for="status" class="form-label">Select New Status</label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                                <option value="pending">Pending</option>
                            </select>
                        </div>

                        <div class="alert alert-info">
                            <p class="mb-0"><strong>Note:</strong></p>
                            <ul class="mb-0 small">
                                <li><strong>Active:</strong> Product is visible to all users</li>
                                <li><strong>Inactive:</strong> Product is hidden from the store</li>
                                <li><strong>Pending:</strong> Product is waiting for approval</li>
                            </ul>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_status" class="btn btn-warning">Update Status</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Select all functionality
        document.getElementById('select-all').addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.product-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
        });

        document.getElementById('table-select-all').addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.product-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
        });

        // Status modal handling
        const statusModal = document.getElementById('statusModal');
        statusModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const productId = button.getAttribute('data-product-id');
            const currentStatus = button.getAttribute('data-current-status');

            document.getElementById('status_product_id').value = productId;
            document.getElementById('status').value = currentStatus;
        });

        // Bulk form validation
        document.getElementById('bulk-form').addEventListener('submit', function(e) {
            const action = this.querySelector('select[name="bulk_action"]').value;
            const checkboxes = document.querySelectorAll('.product-checkbox:checked');

            if (!action) {
                e.preventDefault();
                alert('Please select a bulk action.');
                return;
            }

            if (checkboxes.length === 0) {
                e.preventDefault();
                alert('Please select at least one product.');
                return;
            }

            if (action === 'delete') {
                const confirmed = confirm(`Are you sure you want to delete ${checkboxes.length} product(s)? This action cannot be undone.`);
                if (!confirmed) {
                    e.preventDefault();
                }
            }
        });
    </script>
</body>

</html>