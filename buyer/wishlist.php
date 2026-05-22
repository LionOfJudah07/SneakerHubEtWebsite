<?php
require_once '../config.php';

// Require buyer login
require_buyer();

$page_title = 'My Wishlist - ' . SITE_NAME;

// Get user data
$user = new User();
$user_data = $user->getUserById($_SESSION['user_id']);

// Handle wishlist actions
if (isset($_GET['remove']) && is_numeric($_GET['remove'])) {
    $product_id = intval($_GET['remove']);
    remove_from_wishlist_session($product_id);
    $_SESSION['success'] = 'Product removed from wishlist.';
    redirect('wishlist.php');
}

if (isset($_GET['action'])) {
    if ($_GET['action'] === 'clear') {
        $_SESSION['wishlist'] = [];
        $_SESSION['success'] = 'Wishlist cleared.';
        redirect('wishlist.php');
    } elseif ($_GET['action'] === 'add_all_to_cart') {
        // Add all wishlist items to cart
        if (!empty($_SESSION['wishlist'])) {
            $added_count = 0;
            foreach ($_SESSION['wishlist'] as $product_id) {
                add_to_cart_session($product_id, 1);
                $added_count++;
            }
            $_SESSION['success'] = "Added {$added_count} items to cart!";
            redirect('wishlist.php');
        }
    }
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
                        <h2 class="mb-1">My Wishlist</h2>
                        <p class="text-muted mb-0">Save items you like for later</p>
                    </div>
                    <div>
                        <a href="../public/shop.php" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Continue Shopping
                        </a>
                    </div>
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
                
                <!-- Wishlist Content -->
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-heart me-2"></i>Saved Items</h5>
                        <span class="badge bg-light text-dark"><?php echo count($wishlist_items); ?> items</span>
                    </div>
                    
                    <div class="card-body">
                        <?php if (empty($wishlist_items)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-heart fa-4x text-muted mb-3"></i>
                            <h4>Your wishlist is empty</h4>
                            <p class="text-muted mb-4">Save items you like to your wishlist for easy access.</p>
                            <a href="../public/shop.php" class="btn btn-primary btn-lg">
                                <i class="fas fa-shopping-bag me-2"></i>Start Shopping
                            </a>
                        </div>
                        <?php else: ?>
                        <!-- Wishlist Actions -->
                        <div class="d-flex justify-content-between mb-4">
                            <div>
                                <a href="wishlist.php?action=add_all_to_cart" 
                                   class="btn btn-success"
                                   onclick="return confirm('Add all wishlist items to cart?')">
                                    <i class="fas fa-cart-plus me-2"></i>Add All to Cart
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
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th width="80">Image</th>
                                        <th>Product</th>
                                        <th>Price</th>
                                        <th>Stock</th>
                                        <th>Rating</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($wishlist_items as $item): ?>
                                    <tr>
                                        <td>
                                            <img src="<?php echo !empty($item['images']) ? '../' . $item['images'][0] : '../assets/images/products/default.jpg'; ?>" 
                                                 alt="<?php echo htmlspecialchars($item['name']); ?>"
                                                 class="img-thumbnail" 
                                                 style="width: 60px; height: 60px; object-fit: cover;">
                                        </td>
                                        <td>
                                            <h6 class="mb-1"><?php echo htmlspecialchars($item['name']); ?></h6>
                                            <p class="text-muted small mb-1"><?php echo htmlspecialchars($item['brand']); ?> | <?php echo htmlspecialchars($item['category']); ?></p>
                                            <p class="text-muted small mb-0">SKU: <?php echo htmlspecialchars($item['sku']); ?></p>
                                        </td>
                                        <td>
                                            <?php if ($item['discount_price']): ?>
                                            <span class="text-danger fw-bold"><?php echo format_price($item['discount_price']); ?></span>
                                            <br>
                                            <small class="text-muted text-decoration-line-through"><?php echo format_price($item['price']); ?></small>
                                            <?php else: ?>
                                            <?php echo format_price($item['price']); ?>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($item['stock_quantity'] > 0): ?>
                                            <span class="badge bg-success">In Stock (<?php echo $item['stock_quantity']; ?>)</span>
                                            <?php else: ?>
                                            <span class="badge bg-danger">Out of Stock</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <i class="fas fa-star text-warning small me-1"></i>
                                                <span class="small"><?php echo number_format($item['rating'], 1); ?></span>
                                                <span class="small text-muted ms-1">(<?php echo $item['review_count']; ?>)</span>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <a href="../public/product-detail.php?id=<?php echo $item['id']; ?>" 
                                                   class="btn btn-outline-primary" title="View">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="../public/cart.php?action=add&product_id=<?php echo $item['id']; ?>" 
                                                   class="btn btn-outline-success" title="Add to Cart"
                                                   <?php echo $item['stock_quantity'] <= 0 ? 'disabled' : ''; ?>>
                                                    <i class="fas fa-cart-plus"></i>
                                                </a>
                                                <a href="wishlist.php?remove=<?php echo $item['id']; ?>" 
                                                   class="btn btn-outline-danger" title="Remove">
                                                    <i class="fas fa-times"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Recommendations -->
                <?php if (!empty($wishlist_items)): ?>
                <div class="card shadow-sm mt-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0"><i class="fas fa-fire me-2"></i>Based on Your Wishlist</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        $recommended = $product->getFeaturedProducts(4);
                        ?>
                        <div class="row">
                            <?php foreach ($recommended as $item): ?>
                            <div class="col-md-3 col-sm-6 mb-3">
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
    <?php include '../public/includes/footer.php'; ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script src="../assets/js/buyer.js"></script>
    
    <script>
        // Add to cart
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