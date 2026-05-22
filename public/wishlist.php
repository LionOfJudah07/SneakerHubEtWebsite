<?php
require_once '../config.php';

$page_title = 'My Wishlist - ' . SITE_NAME;

// Handle remove from wishlist
if (isset($_GET['remove']) && is_numeric($_GET['remove'])) {
    $product_id = intval($_GET['remove']);
    remove_from_wishlist_session($product_id);
    set_success('Product removed from wishlist.');
    redirect('wishlist.php');
}

// Handle clear wishlist
if (isset($_GET['action']) && $_GET['action'] === 'clear') {
    $_SESSION['wishlist'] = [];
    set_success('Wishlist cleared.');
    redirect('wishlist.php');
}

// Get wishlist items
$wishlist_items = [];
$product = new Product();

if (!empty($_SESSION['wishlist'])) {
    foreach ($_SESSION['wishlist'] as $product_id) {
        $product_data = $product->getProductById($product_id);
        if ($product_data) {
            $wishlist_items[] = $product_data;
        }
    }
}

$cart_count = get_cart_count();
// Handle simple add to wishlist (for AJAX calls)
if (isset($_GET['action']) && $_GET['action'] === 'add_simple' && isset($_GET['product_id']) && is_numeric($_GET['product_id'])) {
    $product_id = intval($_GET['product_id']);
    
    if (!isset($_SESSION['wishlist'])) {
        $_SESSION['wishlist'] = [];
    }
    
    if (!in_array($product_id, $_SESSION['wishlist'])) {
        $_SESSION['wishlist'][] = $product_id;
    }
    
    // Return JSON response for AJAX
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'count' => count($_SESSION['wishlist'])]);
    exit();
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
<body>
    <!-- Navigation -->
    <?php include 'includes/navbar.php'; ?>

    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="bg-light py-3">
        <div class="container">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                <li class="breadcrumb-item active">Wishlist</li>
            </ol>
        </div>
    </nav>

    <!-- Wishlist Content -->
    <div class="container py-5">
        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-heart me-2"></i>My Wishlist</h5>
                        <span class="badge bg-light text-dark"><?php echo count($wishlist_items); ?> items</span>
                    </div>
                    
                    <div class="card-body">
                        <?php if (empty($wishlist_items)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-heart fa-4x text-muted mb-3"></i>
                            <h3>Your wishlist is empty</h3>
                            <p class="text-muted mb-4">Save items you like to your wishlist for easy access.</p>
                            <a href="shop.php" class="btn btn-primary btn-lg">
                                <i class="fas fa-shopping-bag me-2"></i>Start Shopping
                            </a>
                        </div>
                        <?php else: ?>
                        <!-- Wishlist Actions -->
                        <div class="d-flex justify-content-between mb-4">
                            <div>
                                <a href="shop.php" class="btn btn-outline-primary">
                                    <i class="fas fa-arrow-left me-2"></i>Continue Shopping
                                </a>
                            </div>
                            <div>
                                <a href="wishlist.php?action=clear" 
                                   class="btn btn-outline-danger"
                                   onclick="return confirm('Clear entire wishlist?')">
                                    <i class="fas fa-trash-alt me-2"></i>Clear Wishlist
                                </a>
                            </div>
                        </div>
                        
                        <!-- Wishlist Items -->
                        <div class="row">
                            <?php foreach ($wishlist_items as $item): ?>
                            <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                                <div class="card product-card h-100">
                                    <div class="position-relative">
                                        <img src="<?php echo !empty($item['images']) ? '../' . $item['images'][0] : '../assets/images/products/default.jpg'; ?>" 
                                             class="card-img-top" alt="<?php echo htmlspecialchars($item['name']); ?>"
                                             style="height: 200px; object-fit: cover;">
                                        <?php if ($item['discount_price']): ?>
                                        <span class="badge bg-danger position-absolute top-0 start-0 m-2">
                                            -<?php echo calculate_discount_percentage($item['price'], $item['discount_price']); ?>%
                                        </span>
                                        <?php endif; ?>
                                        <div class="card-img-overlay d-flex justify-content-end align-items-start p-2">
                                            <a href="wishlist.php?remove=<?php echo $item['id']; ?>" 
                                               class="btn btn-sm btn-light rounded-circle"
                                               onclick="return confirm('Remove from wishlist?')">
                                                <i class="fas fa-times text-danger"></i>
                                            </a>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <h6 class="card-title"><?php echo htmlspecialchars($item['name']); ?></h6>
                                        <p class="card-text text-muted small mb-2">
                                            <i class="fas fa-tag me-1"></i> <?php echo htmlspecialchars($item['brand']); ?>
                                            <br>
                                            <i class="fas fa-list me-1"></i> <?php echo htmlspecialchars($item['category']); ?>
                                        </p>
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <div>
                                                <?php if ($item['discount_price']): ?>
                                                <span class="text-danger fw-bold"><?php echo format_price($item['discount_price']); ?></span>
                                                <span class="text-muted text-decoration-line-through small"><?php echo format_price($item['price']); ?></span>
                                                <?php else: ?>
                                                <span class="fw-bold"><?php echo format_price($item['price']); ?></span>
                                                <?php endif; ?>
                                            </div>
                                            <div>
                                                <i class="fas fa-star text-warning small"></i>
                                                <span class="small"><?php echo number_format($item['rating'], 1); ?></span>
                                            </div>
                                        </div>
                                        <div class="small text-muted mb-2">
                                            <i class="fas fa-box me-1"></i> 
                                            <?php if ($item['stock_quantity'] > 0): ?>
                                            <span class="text-success">In Stock (<?php echo $item['stock_quantity']; ?>)</span>
                                            <?php else: ?>
                                            <span class="text-danger">Out of Stock</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="card-footer bg-transparent">
                                        <div class="d-grid gap-2">
                                            <a href="product-detail.php?id=<?php echo $item['id']; ?>" class="btn btn-outline-primary btn-sm">View Details</a>
                                            <button class="btn btn-primary btn-sm add-to-cart" 
                                                    data-product-id="<?php echo $item['id']; ?>"
                                                    data-product-name="<?php echo htmlspecialchars($item['name']); ?>"
                                                    <?php echo $item['stock_quantity'] <= 0 ? 'disabled' : ''; ?>>
                                                <i class="fas fa-cart-plus"></i> Add to Cart
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <!-- Move to Cart All -->
                        <div class="text-center mt-4">
                            <button id="move-all-to-cart" class="btn btn-success btn-lg">
                                <i class="fas fa-cart-plus me-2"></i>Move All to Cart
                            </button>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Recommendations -->
                <?php if (!empty($wishlist_items)): ?>
                <div class="card shadow-sm mt-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0"><i class="fas fa-fire me-2"></i>You Might Also Like</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        $recommended = $product->getFeaturedProducts(4);
                        ?>
                        <div class="row">
                            <?php foreach ($recommended as $item): ?>
                            <div class="col-lg-3 col-md-4 col-sm-6 mb-3">
                                <div class="card h-100">
                                    <img src="<?php echo !empty($item['images']) ? '../' . $item['images'][0] : '../assets/images/products/default.jpg'; ?>" 
                                         class="card-img-top" alt="<?php echo htmlspecialchars($item['name']); ?>"
                                         style="height: 150px; object-fit: cover;">
                                    <div class="card-body">
                                        <h6 class="card-title"><?php echo htmlspecialchars($item['name']); ?></h6>
                                        <p class="card-text text-muted small mb-2"><?php echo htmlspecialchars($item['brand']); ?></p>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="fw-bold"><?php echo format_price($item['price']); ?></span>
                                            <div>
                                                <button class="btn btn-sm btn-outline-primary add-to-cart" 
                                                        data-product-id="<?php echo $item['id']; ?>"
                                                        data-product-name="<?php echo htmlspecialchars($item['name']); ?>">
                                                    <i class="fas fa-cart-plus"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-danger add-to-wishlist" 
                                                        data-product-id="<?php echo $item['id']; ?>">
                                                    <i class="fas fa-heart"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <?php include 'includes/footer.php'; ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script src="../assets/js/main.js"></script>
    
    <script>
        // Add to cart from wishlist
        document.querySelectorAll('.add-to-cart').forEach(button => {
            button.addEventListener('click', function() {
                const productId = this.dataset.productId;
                const productName = this.dataset.productName;
                
                fetch('../api/cart.php?action=add&product_id=' + productId)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Update cart count
                            document.querySelectorAll('.cart-count').forEach(el => {
                                el.textContent = data.cart_count;
                            });
                            
                            // Show success message
                            showAlert(productName + ' added to cart!', 'success');
                        } else {
                            showAlert('Error: ' + data.message, 'danger');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showAlert('An error occurred. Please try again.', 'danger');
                    });
            });
        });
        
        // Add to wishlist
        document.querySelectorAll('.add-to-wishlist').forEach(button => {
            button.addEventListener('click', function() {
                const productId = this.dataset.productId;
                
                fetch('../api/wishlist.php?action=add&product_id=' + productId)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showAlert('Added to wishlist!', 'success');
                            // Update wishlist count if needed
                            setTimeout(() => {
                                location.reload();
                            }, 1000);
                        }
                    });
            });
        });
        
        // Move all to cart
        document.getElementById('move-all-to-cart').addEventListener('click', function() {
            const productIds = <?php echo json_encode(array_column($wishlist_items, 'id')); ?>;
            
            if (productIds.length === 0) {
                showAlert('Wishlist is empty.', 'warning');
                return;
            }
            
            const promises = productIds.map(productId => {
                return fetch('../api/cart.php?action=add&product_id=' + productId)
                    .then(response => response.json());
            });
            
            Promise.all(promises)
                .then(results => {
                    const successCount = results.filter(r => r.success).length;
                    
                    // Clear wishlist after moving to cart
                    fetch('../api/wishlist.php?action=clear')
                        .then(() => {
                            showAlert(`Moved ${successCount} items to cart!`, 'success');
                            setTimeout(() => {
                                location.reload();
                            }, 1500);
                        });
                })
                .catch(error => {
                    console.error('Error:', error);
                    showAlert('An error occurred. Please try again.', 'danger');
                });
        });
        
        // Show alert message
        function showAlert(message, type) {
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
            alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
            alertDiv.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            document.body.appendChild(alertDiv);
            
            setTimeout(() => {
                alertDiv.remove();
            }, 3000);
        }
    </script>
</body>
</html>