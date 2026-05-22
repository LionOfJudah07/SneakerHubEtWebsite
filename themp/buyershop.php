<?php
require_once '../config.php';
require_once '../functions.php';
require_once '../classes/Product.php';

$page_title = 'Shop - ' . SITE_NAME;

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get search and filter parameters
$search = isset($_GET['search']) ? sanitize_input($_GET['search']) : '';
$category = isset($_GET['category']) ? sanitize_input($_GET['category']) : '';
$brand = isset($_GET['brand']) ? sanitize_input($_GET['brand']) : '';
$sort = isset($_GET['sort']) ? sanitize_input($_GET['sort']) : 'newest';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 12;
$offset = ($page - 1) * $limit;

// Get cart count
$cart_count = get_cart_count();

// Get products
$product = new Product();
$products = $product->searchProducts($search, $category, $brand, $sort, $limit, $offset);
$total_products = $product->countProducts($search, $category, $brand);
$total_pages = ceil($total_products / $limit);

// Get categories for filter
$categories = $product->getCategories();

// Get brands for filter
$brands = $product->getBrands();
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
    $nav_path = 'includes/navbar.php';
    if (file_exists($nav_path)) {
        include $nav_path;
    } else {
        // Fallback navbar
        echo '<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
            <div class="container">
                <a class="navbar-brand" href="index.php">
                    <i class="fas fa-shoe-prints"></i> ' . SITE_NAME . '
                </a>
                <div class="navbar-nav ms-auto">
                    <a class="nav-link" href="index.php">Home</a>
                    <a class="nav-link active" href="shop.php">Shop</a>
                    <a class="nav-link" href="cart.php">Cart</a>
                    ' . (is_logged_in() ? '<a class="nav-link" href="../buyer/index.php">Dashboard</a>' : '<a class="nav-link" href="login.php">Login</a>') . '
                </div>
            </div>
        </nav>';
    }
    ?>

    <!-- Shop Header -->
    <div class="container-fluid bg-primary text-white py-5">
        <div class="container">
            <h1 class="display-4 mb-3">Shop Sneakers</h1>
            <p class="lead mb-0">Find your perfect pair from our extensive collection</p>
        </div>
    </div>

    <!-- Shop Content -->
    <div class="container-fluid py-4">
        <div class="container">
            <div class="row">
                <!-- Filters Sidebar -->
                <div class="col-lg-3">
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-dark text-white">
                            <h5 class="mb-0"><i class="fas fa-filter me-2"></i>Filters</h5>
                        </div>
                        <div class="card-body">
                            <!-- Search Form -->
                            <form method="GET" action="shop.php" class="mb-4">
                                <div class="input-group">
                                    <input type="text" class="form-control" name="search"
                                        placeholder="Search sneakers..." value="<?php echo htmlspecialchars($search); ?>">
                                    <button class="btn btn-primary" type="submit">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>
                            </form>

                            <!-- Categories Filter -->
                            <div class="mb-4">
                                <h6 class="text-muted mb-3">Categories</h6>
                                <div class="list-group list-group-flush">
                                    <a href="shop.php" class="list-group-item list-group-item-action d-flex justify-content-between <?php echo empty($category) ? 'active' : ''; ?>">
                                        All Categories
                                        <span class="badge bg-primary rounded-pill"><?php echo $total_products; ?></span>
                                    </a>
                                    <?php foreach ($categories as $cat): ?>
                                        <a href="shop.php?category=<?php echo urlencode($cat); ?>"
                                            class="list-group-item list-group-item-action d-flex justify-content-between <?php echo $category == $cat ? 'active' : ''; ?>">
                                            <?php echo htmlspecialchars($cat); ?>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <!-- Brands Filter -->
                            <?php if (!empty($brands)): ?>
                                <div class="mb-4">
                                    <h6 class="text-muted mb-3">Brands</h6>
                                    <div class="list-group list-group-flush">
                                        <a href="shop.php" class="list-group-item list-group-item-action <?php echo empty($brand) ? 'active' : ''; ?>">
                                            All Brands
                                        </a>
                                        <?php foreach ($brands as $br): ?>
                                            <a href="shop.php?brand=<?php echo urlencode($br); ?>"
                                                class="list-group-item list-group-item-action <?php echo $brand == $br ? 'active' : ''; ?>">
                                                <?php echo htmlspecialchars($br); ?>
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <!-- Sort Options -->
                            <div class="mb-4">
                                <h6 class="text-muted mb-3">Sort By</h6>
                                <form method="GET" action="shop.php" id="sort-form">
                                    <?php if (!empty($search)): ?><input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>"><?php endif; ?>
                                    <?php if (!empty($category)): ?><input type="hidden" name="category" value="<?php echo htmlspecialchars($category); ?>"><?php endif; ?>
                                    <?php if (!empty($brand)): ?><input type="hidden" name="brand" value="<?php echo htmlspecialchars($brand); ?>"><?php endif; ?>

                                    <select class="form-select" name="sort" onchange="document.getElementById('sort-form').submit()">
                                        <option value="newest" <?php echo $sort == 'newest' ? 'selected' : ''; ?>>Newest First</option>
                                        <option value="price_low" <?php echo $sort == 'price_low' ? 'selected' : ''; ?>>Price: Low to High</option>
                                        <option value="price_high" <?php echo $sort == 'price_high' ? 'selected' : ''; ?>>Price: High to Low</option>
                                        <option value="name_asc" <?php echo $sort == 'name_asc' ? 'selected' : ''; ?>>Name: A to Z</option>
                                        <option value="name_desc" <?php echo $sort == 'name_desc' ? 'selected' : ''; ?>>Name: Z to A</option>
                                        <option value="popular" <?php echo $sort == 'popular' ? 'selected' : ''; ?>>Most Popular</option>
                                    </select>
                                </form>
                            </div>

                            <!-- Clear Filters -->
                            <?php if (!empty($search) || !empty($category) || !empty($brand)): ?>
                                <div class="d-grid">
                                    <a href="shop.php" class="btn btn-outline-danger">
                                        <i class="fas fa-times me-2"></i>Clear All Filters
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Featured Products -->
                    <div class="card shadow-sm">
                        <div class="card-header bg-info text-white">
                            <h6 class="mb-0"><i class="fas fa-fire me-2"></i>Featured</h6>
                        </div>
                        <div class="card-body">
                            <?php
                            $featured = $product->getFeaturedProducts(3);
                            foreach ($featured as $item):
                            ?>
                                <div class="d-flex mb-3">
                                    <?php
                                    $image = '../assets/images/products/default.jpg';
                                    if (!empty($item['images'])) {
                                        $images = is_string($item['images']) ? json_decode($item['images'], true) : $item['images'];
                                        if (is_array($images) && !empty($images[0])) {
                                            $image = '../' . $images[0];
                                        }
                                    }
                                    ?>
                                    <img src="<?php echo $image; ?>"
                                        alt="<?php echo htmlspecialchars($item['name']); ?>"
                                        class="img-thumbnail me-2" style="width: 60px; height: 60px; object-fit: cover;">
                                    <div>
                                        <h6 class="mb-1"><?php echo htmlspecialchars($item['name']); ?></h6>
                                        <p class="text-muted mb-0 small"><?php echo format_price($item['price']); ?></p>
                                        <a href="product-detail.php?id=<?php echo $item['id']; ?>" class="btn btn-sm btn-outline-primary mt-1">View</a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Products Grid -->
                <div class="col-lg-9">
                    <!-- Results Header -->
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h4 class="mb-1"><?php echo $total_products; ?> Products Found</h4>
                            <?php if (!empty($search)): ?>
                                <p class="text-muted mb-0">Search results for: "<strong><?php echo htmlspecialchars($search); ?></strong>"</p>
                            <?php endif; ?>
                            <?php if (!empty($category)): ?>
                                <p class="text-muted mb-0">Category: <strong><?php echo htmlspecialchars($category); ?></strong></p>
                            <?php endif; ?>
                            <?php if (!empty($brand)): ?>
                                <p class="text-muted mb-0">Brand: <strong><?php echo htmlspecialchars($brand); ?></strong></p>
                            <?php endif; ?>
                        </div>
                        <div class="d-flex align-items-center">
                            <span class="text-muted me-3">Page <?php echo $page; ?> of <?php echo $total_pages; ?></span>
                            <div class="btn-group">
                                <a href="?page=<?php echo max(1, $page - 1); ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($category) ? '&category=' . urlencode($category) : ''; ?><?php echo !empty($brand) ? '&brand=' . urlencode($brand) : ''; ?><?php echo !empty($sort) ? '&sort=' . urlencode($sort) : ''; ?>"
                                    class="btn btn-outline-primary <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                                <a href="?page=<?php echo min($total_pages, $page + 1); ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($category) ? '&category=' . urlencode($category) : ''; ?><?php echo !empty($brand) ? '&brand=' . urlencode($brand) : ''; ?><?php echo !empty($sort) ? '&sort=' . urlencode($sort) : ''; ?>"
                                    class="btn btn-outline-primary <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Products -->
                    <?php if (empty($products)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-search fa-4x text-muted mb-4"></i>
                            <h3>No Products Found</h3>
                            <p class="text-muted mb-4">Try adjusting your search or filter criteria</p>
                            <a href="shop.php" class="btn btn-primary">Clear Filters</a>
                        </div>
                    <?php else: ?>
                        <div class="row">
                            <?php foreach ($products as $item): ?>
                                <div class="col-lg-4 col-md-6 mb-4">
                                    <div class="card h-100 shadow-sm">
                                        <!-- Product Image -->
                                        <div class="position-relative">
                                            <?php
                                            $image = '../assets/images/products/default.jpg';
                                            if (!empty($item['images'])) {
                                                $images = is_string($item['images']) ? json_decode($item['images'], true) : $item['images'];
                                                if (is_array($images) && !empty($images[0])) {
                                                    $image = '../' . $images[0];
                                                }
                                            }
                                            ?>
                                            <img src="<?php echo $image; ?>"
                                                class="card-img-top"
                                                alt="<?php echo htmlspecialchars($item['name']); ?>"
                                                style="height: 250px; object-fit: cover;">

                                            <!-- Wishlist Button -->
                                            <button class="btn btn-light btn-sm position-absolute top-0 end-0 m-2"
                                                onclick="toggleWishlist(<?php echo $item['id']; ?>, this)">
                                                <i class="fas fa-heart <?php echo is_in_wishlist_session($item['id']) ? 'text-danger' : 'text-muted'; ?>"></i>
                                            </button>

                                            <!-- Discount Badge -->
                                            <?php if (!empty($item['discount_price']) && $item['discount_price'] < $item['price']): ?>
                                                <span class="badge bg-danger position-absolute top-0 start-0 m-2">
                                                    -<?php echo calculate_discount_percentage($item['price'], $item['discount_price']); ?>%
                                                </span>
                                            <?php endif; ?>
                                        </div>

                                        <div class="card-body">
                                            <!-- Product Info -->
                                            <h5 class="card-title"><?php echo htmlspecialchars($item['name']); ?></h5>
                                            <p class="card-text text-muted small mb-2"><?php echo htmlspecialchars($item['brand']); ?></p>

                                            <!-- Price -->
                                            <div class="d-flex justify-content-between align-items-center mb-3">
                                                <div>
                                                    <?php if (!empty($item['discount_price']) && $item['discount_price'] < $item['price']): ?>
                                                        <span class="text-danger fw-bold fs-5"><?php echo format_price($item['discount_price']); ?></span>
                                                        <span class="text-muted text-decoration-line-through ms-2"><?php echo format_price($item['price']); ?></span>
                                                    <?php else: ?>
                                                        <span class="fw-bold fs-5"><?php echo format_price($item['price']); ?></span>
                                                    <?php endif; ?>
                                                </div>
                                                <span class="badge bg-info"><?php echo htmlspecialchars($item['condition']); ?></span>
                                            </div>

                                            <!-- Stock Status -->
                                            <div class="mb-3">
                                                <?php if ($item['stock_quantity'] > 0): ?>
                                                    <span class="badge bg-success">In Stock (<?php echo $item['stock_quantity']; ?>)</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger">Out of Stock</span>
                                                <?php endif; ?>
                                            </div>

                                            <!-- Buttons -->
                                            <div class="d-grid gap-2">
                                                <a href="product-detail.php?id=<?php echo $item['id']; ?>"
                                                    class="btn btn-outline-primary">
                                                    <i class="fas fa-eye me-2"></i>View Details
                                                </a>
                                                <?php if ($item['stock_quantity'] > 0): ?>
                                                    <button class="btn btn-primary add-to-cart"
                                                        data-product-id="<?php echo $item['id']; ?>"
                                                        data-product-name="<?php echo htmlspecialchars($item['name']); ?>">
                                                        <i class="fas fa-cart-plus me-2"></i>Add to Cart
                                                    </button>
                                                <?php else: ?>
                                                    <button class="btn btn-secondary disabled">
                                                        <i class="fas fa-ban me-2"></i>Out of Stock
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <nav aria-label="Page navigation" class="mt-4">
                            <ul class="pagination justify-content-center">
                                <!-- Previous -->
                                <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                    <a class="page-link"
                                        href="?page=<?php echo $page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($category) ? '&category=' . urlencode($category) : ''; ?><?php echo !empty($brand) ? '&brand=' . urlencode($brand) : ''; ?><?php echo !empty($sort) ? '&sort=' . urlencode($sort) : ''; ?>">
                                        <i class="fas fa-chevron-left"></i>
                                    </a>
                                </li>

                                <!-- Page Numbers -->
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <?php if ($i == 1 || $i == $total_pages || ($i >= $page - 2 && $i <= $page + 2)): ?>
                                        <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                            <a class="page-link"
                                                href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($category) ? '&category=' . urlencode($category) : ''; ?><?php echo !empty($brand) ? '&brand=' . urlencode($brand) : ''; ?><?php echo !empty($sort) ? '&sort=' . urlencode($sort) : ''; ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        </li>
                                    <?php elseif ($i == $page - 3 || $i == $page + 3): ?>
                                        <li class="page-item disabled">
                                            <span class="page-link">...</span>
                                        </li>
                                    <?php endif; ?>
                                <?php endfor; ?>

                                <!-- Next -->
                                <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                    <a class="page-link"
                                        href="?page=<?php echo $page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($category) ? '&category=' . urlencode($category) : ''; ?><?php echo !empty($brand) ? '&brand=' . urlencode($brand) : ''; ?><?php echo !empty($sort) ? '&sort=' . urlencode($sort) : ''; ?>">
                                        <i class="fas fa-chevron-right"></i>
                                    </a>
                                </li>
                            </ul>
                        </nav>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <?php
    $footer_path = 'includes/footer.php';
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
                        <a href="contact.php" class="text-white text-decoration-none me-3">Contact</a>
                        <a href="terms.php" class="text-white text-decoration-none me-3">Terms</a>
                        <a href="privacy.php" class="text-white text-decoration-none">Privacy</a>
                    </div>
                </div>
            </div>
        </footer>';
    }
    ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Add to Cart functionality
        document.querySelectorAll('.add-to-cart').forEach(button => {
            button.addEventListener('click', function() {
                const productId = this.getAttribute('data-product-id');
                const productName = this.getAttribute('data-product-name');

                // Show loading
                const originalText = this.innerHTML;
                this.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Adding...';
                this.disabled = true;

                // Simulate AJAX call (in production, use fetch or XMLHttpRequest)
                setTimeout(() => {
                    // In production: Make AJAX request to add-to-cart endpoint
                    // For now, redirect to cart page with add parameter
                    window.location.href = `cart.php?action=add&product_id=${productId}`;
                }, 500);
            });
        });

        // Toggle Wishlist
        function toggleWishlist(productId, button) {
            const heartIcon = button.querySelector('i');

            if (heartIcon.classList.contains('text-danger')) {
                // Remove from wishlist
                heartIcon.classList.remove('text-danger');
                heartIcon.classList.add('text-muted');

                // In production: Make AJAX request to remove from wishlist
                window.location.href = `wishlist.php?action=remove&product_id=${productId}&redirect=shop.php`;
            } else {
                // Add to wishlist
                heartIcon.classList.remove('text-muted');
                heartIcon.classList.add('text-danger');

                // In production: Make AJAX request to add to wishlist
                window.location.href = `wishlist.php?action=add&product_id=${productId}&redirect=shop.php`;
            }
        }

        // Update wishlist count in navbar
        function updateWishlistCount() {
            // This would be called after AJAX operations
            // For now, the page will refresh on wishlist actions
        }
    </script>
</body>

</html>