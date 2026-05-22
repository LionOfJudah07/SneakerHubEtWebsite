<?php
// public/cart.php

// Start session first
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include required files
require_once '../includes/config.php';
require_once '../functions.php';

// Initialize Product class
require_once '../classes/Product.php';
$product = new Product();

$page_title = 'Shopping Cart - ' . SITE_NAME;
$cart_count = get_cart_count();

// Handle cart actions
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    
    if ($action === 'add' && isset($_GET['product_id'])) {
        $product_id = intval($_GET['product_id']);
        $quantity = isset($_GET['quantity']) ? intval($_GET['quantity']) : 1;
        $size = $_GET['size'] ?? '';
        $color = $_GET['color'] ?? '';
        
        // Get product to check stock
        $product_data = $product->getProductById($product_id);
        
        if ($product_data && ($product_data['stock_quantity'] ?? 0) >= $quantity) {
            // Use the correct function name based on your functions.php
            if (function_exists('add_to_cart_session')) {
                add_to_cart_session($product_id, $quantity, $size, $color);
            } elseif (function_exists('add_to_cart')) {
                add_to_cart($product_id, $quantity, $size, $color);
            } else {
                // Fallback: direct session manipulation
                if (!isset($_SESSION['cart'])) {
                    $_SESSION['cart'] = [];
                }
                
                // Create a unique key for this product with size and color
                $key = $product_id . '_' . $size . '_' . $color;
                
                if (isset($_SESSION['cart'][$key])) {
                    $_SESSION['cart'][$key]['quantity'] += $quantity;
                } else {
                    $_SESSION['cart'][$key] = [
                        'product_id' => $product_id,
                        'quantity' => $quantity,
                        'size' => $size,
                        'color' => $color,
                        'added_at' => time()
                    ];
                }
                
                // Update cart count
                if (function_exists('update_cart_count')) {
                    update_cart_count();
                } elseif (function_exists('session_update_cart_count')) {
                    session_update_cart_count();
                }
            }
            
            // Set success message
            if (function_exists('set_success')) {
                set_success('Product added to cart!');
            } elseif (function_exists('session_set_success')) {
                session_set_success('Product added to cart!');
            } else {
                $_SESSION['flash_success'] = 'Product added to cart!';
            }
        } else {
            // Set error message
            $message = 'Product is out of stock or quantity not available.';
            if (function_exists('set_error')) {
                set_error($message);
            } elseif (function_exists('session_set_error')) {
                session_set_error($message);
            } else {
                $_SESSION['flash_error'] = $message;
            }
        }
        
        // Redirect to prevent form resubmission
        header('Location: cart.php');
        exit();
        
    } elseif ($action === 'remove' && isset($_GET['key'])) {
        $key = $_GET['key']; // Keep as string since it's composite key
        
        // Use the correct function name
        $removed = false;
        if (function_exists('remove_from_cart_session')) {
            $removed = remove_from_cart_session($key);
        } elseif (function_exists('remove_from_cart')) {
            $removed = remove_from_cart($key);
        } else {
            // Fallback: direct session manipulation
            if (isset($_SESSION['cart'][$key])) {
                unset($_SESSION['cart'][$key]);
                $removed = true;
                
                // Update cart count
                if (function_exists('update_cart_count')) {
                    update_cart_count();
                } elseif (function_exists('session_update_cart_count')) {
                    session_update_cart_count();
                }
            }
        }
        
        if ($removed) {
            $message = 'Product removed from cart.';
        } else {
            $message = 'Failed to remove product from cart.';
        }
        
        // Set message
        if (function_exists('set_success')) {
            set_success($message);
        } elseif (function_exists('session_set_success')) {
            session_set_success($message);
        } else {
            $_SESSION['flash_success'] = $message;
        }
        
        header('Location: cart.php');
        exit();
        
    } elseif ($action === 'update' && isset($_POST['update_cart'])) {
        if (isset($_POST['quantity']) && is_array($_POST['quantity'])) {
            foreach ($_POST['quantity'] as $key => $quantity) {
                $quantity = intval($quantity);
                
                if ($quantity > 0) {
                    // Update quantity
                    if (function_exists('update_cart_quantity')) {
                        update_cart_quantity($key, $quantity);
                    } elseif (function_exists('session_update_cart_quantity')) {
                        session_update_cart_quantity($key, $quantity);
                    } else {
                        // Fallback
                        if (isset($_SESSION['cart'][$key])) {
                            $_SESSION['cart'][$key]['quantity'] = $quantity;
                        }
                    }
                } else {
                    // Remove item if quantity is 0
                    if (function_exists('remove_from_cart_session')) {
                        remove_from_cart_session($key);
                    } elseif (function_exists('remove_from_cart')) {
                        remove_from_cart($key);
                    } else {
                        // Fallback
                        if (isset($_SESSION['cart'][$key])) {
                            unset($_SESSION['cart'][$key]);
                        }
                    }
                }
            }
            
            // Update cart count
            if (function_exists('update_cart_count')) {
                update_cart_count();
            } elseif (function_exists('session_update_cart_count')) {
                session_update_cart_count();
            }
            
            $message = 'Cart updated successfully.';
            if (function_exists('set_success')) {
                set_success($message);
            } elseif (function_exists('session_set_success')) {
                session_set_success($message);
            } else {
                $_SESSION['flash_success'] = $message;
            }
        }
        
        header('Location: cart.php');
        exit();
        
    } elseif ($action === 'clear') {
        // Clear cart
        if (function_exists('clear_cart_session')) {
            clear_cart_session();
        } elseif (function_exists('clear_cart')) {
            clear_cart();
        } elseif (function_exists('session_clear_cart')) {
            session_clear_cart();
        } else {
            // Fallback
            unset($_SESSION['cart']);
            if (isset($_SESSION['cart_count'])) {
                unset($_SESSION['cart_count']);
            }
        }
        
        $message = 'Cart cleared.';
        if (function_exists('set_success')) {
            set_success($message);
        } elseif (function_exists('session_set_success')) {
            session_set_success($message);
        } else {
            $_SESSION['flash_success'] = $message;
        }
        
        header('Location: cart.php');
        exit();
    }
}

// Helper function to get cart items with details
function get_cart_items() {
    global $product;
    
    if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
        return [];
    }
    
    $cart_items = [];
    foreach ($_SESSION['cart'] as $key => $cart_item) {
        $product_data = $product->getProductById($cart_item['product_id']);
        if ($product_data) {
            $price = !empty($product_data['discount_price']) && $product_data['discount_price'] > 0 
                ? $product_data['discount_price'] 
                : $product_data['price'];
            
            // Get first image
            $image = '../assets/images/products/default.jpg';
            if (!empty($product_data['images'])) {
                if (is_string($product_data['images'])) {
                    $images = json_decode($product_data['images'], true);
                    if ($images && is_array($images) && !empty($images[0])) {
                        $image = '../' . $images[0];
                    }
                } elseif (is_array($product_data['images']) && !empty($product_data['images'][0])) {
                    $image = '../' . $product_data['images'][0];
                }
            }
            
            $cart_items[] = [
                'key' => $key,
                'product_id' => $cart_item['product_id'],
                'name' => $product_data['name'],
                'brand' => $product_data['brand'] ?? '',
                'size' => $cart_item['size'] ?? '',
                'color' => $cart_item['color'] ?? '',
                'price' => $price,
                'quantity' => $cart_item['quantity'],
                'subtotal' => $price * $cart_item['quantity'],
                'stock' => $product_data['stock_quantity'] ?? 0,
                'image' => $image
            ];
        }
    }
    
    return $cart_items;
}

// Helper function to get cart total
function get_cart_total_amount() {
    $cart_items = get_cart_items();
    $total = 0;
    foreach ($cart_items as $item) {
        $total += $item['subtotal'];
    }
    return $total;
}

// Helper function to calculate VAT
function calculate_vat_amount($amount) {
    return $amount * 0.15; // 15% VAT
}

// Get cart items
$cart_items = get_cart_items();
$cart_total = get_cart_total_amount();
$cart_subtotal = $cart_total;
$shipping_cost = defined('SHIPPING_COST') ? SHIPPING_COST : 100; // Default shipping cost
$vat = calculate_vat_amount($cart_subtotal);
$grand_total = $cart_subtotal + $vat + $shipping_cost;

// Get flash messages
$flash_success = '';
$flash_error = '';
if (function_exists('get_flash_message')) {
    $flash_success = get_flash_message('success');
    $flash_error = get_flash_message('error');
} elseif (function_exists('session_get_flash')) {
    $flash_success = session_get_flash('success');
    $flash_error = session_get_flash('error');
} else {
    // Fallback
    if (isset($_SESSION['flash_success'])) {
        $flash_success = $_SESSION['flash_success'];
        unset($_SESSION['flash_success']);
    }
    if (isset($_SESSION['flash_error'])) {
        $flash_error = $_SESSION['flash_error'];
        unset($_SESSION['flash_error']);
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link href="../assets/css/style.css" rel="stylesheet">
    <link href="../assets/css/bootstrap-custom.css" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #16a34a;
            --primary-dark: #0f7a35;
            --primary-light: #4ade80;
            --danger-color: #ef4444;
            --warning-color: #facc15;
            --info-color: #14b8a6;
            --dark-color: #0b0f0e;
        }
        
        body {
            background-color: #f9fafb;
        }
        
        .cart-section {
            padding: 2rem 0;
        }
        
        .cart-header {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            border-radius: 0.75rem 0.75rem 0 0;
            padding: 1.5rem;
        }
        
        .cart-table {
            background: white;
            border-radius: 0.75rem;
            overflow: hidden;
            box-shadow: 0 8px 30px rgba(0,0,0,0.08);
        }
        
        .cart-table th {
            background-color: #f8f9fa;
            border: none;
            padding: 1rem 1.25rem;
            font-weight: 700;
            color: #374151;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
        }
        
        .cart-table td {
            padding: 1.25rem;
            vertical-align: middle;
            border-color: #f3f4f6;
        }
        
        .cart-table tr:hover {
            background-color: #f9fafb;
        }
        
        .product-img {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 0.5rem;
            border: 2px solid #f3f4f6;
            transition: transform 0.3s ease;
        }
        
        .product-img:hover {
            transform: scale(1.05);
        }
        
        .quantity-control {
            width: 130px;
        }
        
        .quantity-control .btn {
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0;
        }
        
        .quantity-control input {
            height: 36px;
            text-align: center;
            font-weight: 600;
            border-color: #e5e7eb;
        }
        
        .summary-card {
            background: white;
            border-radius: 0.75rem;
            box-shadow: 0 8px 30px rgba(0,0,0,0.08);
            border: none;
            position: sticky;
            top: 20px;
        }
        
        .summary-header {
            background: linear-gradient(135deg, var(--dark-color), #1f2937);
            color: white;
            border-radius: 0.75rem 0.75rem 0 0;
            padding: 1.5rem;
        }
        
        .summary-body {
            padding: 1.5rem;
        }
        
        .summary-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem 0;
            border-bottom: 1px solid #f3f4f6;
        }
        
        .summary-item:last-child {
            border-bottom: none;
        }
        
        .summary-item.total {
            font-size: 1.25rem;
            font-weight: 800;
            color: var(--primary-color);
            padding-top: 1.5rem;
            margin-top: 0.5rem;
        }
        
        .cart-empty {
            text-align: center;
            padding: 4rem 2rem;
            background: white;
            border-radius: 0.75rem;
            box-shadow: 0 8px 30px rgba(0,0,0,0.08);
        }
        
        .cart-empty-icon {
            width: 120px;
            height: 120px;
            background: linear-gradient(135deg, rgba(22, 163, 74, 0.1), rgba(22, 163, 74, 0.2));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 2rem;
            color: var(--primary-color);
        }
        
        .badge-stock {
            font-size: 0.7rem;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
        }
        
        .badge-stock.low {
            background-color: #fef3c7;
            color: #92400e;
        }
        
        .badge-stock.out {
            background-color: #fee2e2;
            color: #991b1b;
        }
        
        .btn-action {
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0;
            border-radius: 0.5rem;
        }
        
        .btn-checkout {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            border: none;
            padding: 1rem;
            font-weight: 700;
            font-size: 1.1rem;
            transition: all 0.3s ease;
        }
        
        .btn-checkout:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(22, 163, 74, 0.3);
        }
        
        .flash-message {
            animation: slideDown 0.5s ease;
        }
        
        @keyframes slideDown {
            from {
                transform: translateY(-20px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        
        @media (max-width: 768px) {
            .cart-table {
                display: block;
                overflow-x: auto;
            }
            
            .product-img {
                width: 70px;
                height: 70px;
            }
            
            .quantity-control {
                width: 110px;
            }
            
            .cart-header, .summary-header {
                padding: 1rem;
            }
        }
        
        .breadcrumb-section {
            background: linear-gradient(135deg, rgba(22, 163, 74, 0.1), rgba(22, 163, 74, 0.05));
            padding: 1rem 0;
        }
        
        .breadcrumb-item a {
            color: var(--primary-color);
            font-weight: 600;
        }
        
        .text-primary {
            color: var(--primary-color) !important;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-primary:hover {
            background-color: var(--primary-dark);
            border-color: var(--primary-dark);
        }
        
        .btn-outline-primary {
            color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-outline-primary:hover {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            color: white;
        }
        
        .btn-outline-danger:hover {
            background-color: #ef4444;
            border-color: #ef4444;
            color: white;
        }
        
        .bg-primary {
            background-color: var(--primary-color) !important;
        }
        
        .breadcrumb {
            background-color: transparent !important;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <?php include 'includes/navbar.php'; ?>

    <!-- Breadcrumb -->
    <section class="breadcrumb-section">
        <div class="container">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 py-2">
                    <li class="breadcrumb-item"><a href="index.php"><i class="fas fa-home me-1"></i>Home</a></li>
                    <li class="breadcrumb-item active">Shopping Cart</li>
                </ol>
            </nav>
        </div>
    </section>

    <!-- Flash Messages -->
    <?php if (!empty($flash_success)): ?>
    <div class="container mt-3 flash-message">
        <div class="alert alert-success alert-dismissible fade show shadow-sm border-0" role="alert">
            <div class="d-flex align-items-center">
                <i class="fas fa-check-circle fa-lg me-3"></i>
                <div class="flex-grow-1">
                    <strong>Success!</strong> <?php echo htmlspecialchars($flash_success); ?>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if (!empty($flash_error)): ?>
    <div class="container mt-3 flash-message">
        <div class="alert alert-danger alert-dismissible fade show shadow-sm border-0" role="alert">
            <div class="d-flex align-items-center">
                <i class="fas fa-exclamation-circle fa-lg me-3"></i>
                <div class="flex-grow-1">
                    <strong>Error!</strong> <?php echo htmlspecialchars($flash_error); ?>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Cart Content -->
    <section class="cart-section">
        <div class="container">
            <div class="row g-4">
                <!-- Cart Items -->
                <div class="col-lg-8">
                    <div class="cart-table">
                        <div class="cart-header">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h3 class="mb-1"><i class="fas fa-shopping-cart me-2"></i>Shopping Cart</h3>
                                    <p class="mb-0 opacity-75">Review and manage your items</p>
                                </div>
                                <span class="badge bg-light text-dark fs-6 px-3 py-2">
                                    <i class="fas fa-box me-1"></i> <?php echo $cart_count; ?> item<?php echo $cart_count != 1 ? 's' : ''; ?>
                                </span>
                            </div>
                        </div>
                        
                        <?php if (empty($cart_items)): ?>
                        <div class="cart-empty">
                            <div class="cart-empty-icon">
                                <i class="fas fa-shopping-cart fa-3x"></i>
                            </div>
                            <h3 class="mb-3">Your Cart is Empty</h3>
                            <p class="text-muted mb-4">Looks like you haven't added any sneakers to your cart yet.</p>
                            <a href="shop.php" class="btn btn-primary btn-lg px-4">
                                <i class="fas fa-shopping-bag me-2"></i>Start Shopping
                            </a>
                        </div>
                        <?php else: ?>
                        <form method="POST" action="cart.php?action=update">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th width="120">Product</th>
                                            <th>Details</th>
                                            <th class="text-center">Price</th>
                                            <th class="text-center">Quantity</th>
                                            <th class="text-center">Subtotal</th>
                                            <th class="text-center">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($cart_items as $item): 
                                            $stock_class = '';
                                            $stock_text = '';
                                            if ($item['stock'] <= 0) {
                                                $stock_class = 'out';
                                                $stock_text = 'Out of Stock';
                                            } elseif ($item['stock'] < 5) {
                                                $stock_class = 'low';
                                                $stock_text = 'Low Stock';
                                            }
                                        ?>
                                        <tr>
                                            <td>
                                                <img src="<?php echo htmlspecialchars($item['image']); ?>" 
                                                     alt="<?php echo htmlspecialchars($item['name']); ?>"
                                                     class="product-img shadow-sm">
                                            </td>
                                            <td>
                                                <div>
                                                    <h6 class="mb-1 fw-bold"><?php echo htmlspecialchars($item['name']); ?></h6>
                                                    <p class="text-muted small mb-2"><?php echo htmlspecialchars($item['brand']); ?></p>
                                                    <?php if (!empty($item['size'])): ?>
                                                    <span class="badge bg-secondary me-1">
                                                        <i class="fas fa-ruler-vertical me-1"></i> <?php echo htmlspecialchars($item['size']); ?>
                                                    </span>
                                                    <?php endif; ?>
                                                    <?php if (!empty($item['color'])): ?>
                                                    <span class="badge bg-secondary">
                                                        <i class="fas fa-palette me-1"></i> <?php echo htmlspecialchars($item['color']); ?>
                                                    </span>
                                                    <?php endif; ?>
                                                    <?php if ($stock_text): ?>
                                                    <div class="mt-2">
                                                        <span class="badge badge-stock <?php echo $stock_class; ?>">
                                                            <i class="fas fa-exclamation-circle me-1"></i> <?php echo $stock_text; ?>
                                                        </span>
                                                    </div>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td class="text-center fw-bold text-primary">
                                                <?php echo format_price($item['price']); ?>
                                            </td>
                                            <td class="text-center">
                                                <div class="d-flex justify-content-center">
                                                    <div class="input-group quantity-control">
                                                        <button type="button" class="btn btn-outline-secondary btn-sm decrease-qty" 
                                                                data-key="<?php echo htmlspecialchars($item['key']); ?>">
                                                            <i class="fas fa-minus"></i>
                                                        </button>
                                                        <input type="number" 
                                                               class="form-control form-control-sm text-center quantity-input" 
                                                               name="quantity[<?php echo htmlspecialchars($item['key']); ?>]" 
                                                               value="<?php echo $item['quantity']; ?>" 
                                                               min="1" 
                                                               max="<?php echo $item['stock']; ?>"
                                                               data-key="<?php echo htmlspecialchars($item['key']); ?>"
                                                               data-price="<?php echo $item['price']; ?>"
                                                               <?php echo $item['stock'] <= 0 ? 'disabled' : ''; ?>>
                                                        <button type="button" class="btn btn-outline-secondary btn-sm increase-qty"
                                                                data-key="<?php echo htmlspecialchars($item['key']); ?>"
                                                                <?php echo $item['stock'] <= 0 ? 'disabled' : ''; ?>>
                                                            <i class="fas fa-plus"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                                <?php if ($item['quantity'] > $item['stock'] && $item['stock'] > 0): ?>
                                                <small class="text-danger mt-1 d-block">Only <?php echo $item['stock']; ?> available</small>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center fw-bold subtotal-cell" data-key="<?php echo htmlspecialchars($item['key']); ?>">
                                                <?php echo format_price($item['subtotal']); ?>
                                            </td>
                                            <td class="text-center">
                                                <a href="cart.php?action=remove&key=<?php echo urlencode($item['key']); ?>" 
                                                   class="btn btn-outline-danger btn-action"
                                                   onclick="return confirm('Remove \'<?php echo addslashes($item['name']); ?>\' from cart?')"
                                                   title="Remove Item">
                                                    <i class="fas fa-trash-alt"></i>
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <!-- Cart Actions -->
                            <div class="p-4 border-top">
                                <div class="d-flex justify-content-between align-items-center">
                                    <a href="shop.php" class="btn btn-outline-primary px-4">
                                        <i class="fas fa-arrow-left me-2"></i>Continue Shopping
                                    </a>
                                    <div class="d-flex gap-2">
                                        <button type="submit" name="update_cart" class="btn btn-outline-secondary px-4">
                                            <i class="fas fa-sync-alt me-2"></i>Update Cart
                                        </button>
                                        <a href="cart.php?action=clear" 
                                           class="btn btn-outline-danger px-4"
                                           onclick="return confirm('Clear entire cart? All items will be removed.')">
                                            <i class="fas fa-trash-alt me-2"></i>Clear Cart
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Order Summary -->
                <div class="col-lg-4">
                    <div class="summary-card">
                        <div class="summary-header">
                            <h4 class="mb-1"><i class="fas fa-receipt me-2"></i>Order Summary</h4>
                            <p class="mb-0 opacity-75">Review your order details</p>
                        </div>
                        
                        <div class="summary-body">
                            <?php if (!empty($cart_items)): ?>
                            <div class="mb-4">
                                <div class="summary-item">
                                    <span class="text-muted">Subtotal</span>
                                    <span class="fw-bold" id="cart-subtotal"><?php echo format_price($cart_subtotal); ?></span>
                                </div>
                                <div class="summary-item">
                                    <span class="text-muted">Shipping</span>
                                    <span class="fw-bold"><?php echo format_price($shipping_cost); ?></span>
                                </div>
                                <div class="summary-item">
                                    <span class="text-muted">VAT (15%)</span>
                                    <span class="fw-bold" id="vat-amount"><?php echo format_price($vat); ?></span>
                                </div>
                                <div class="summary-item total">
                                    <span>Total Amount</span>
                                    <span class="text-primary" id="cart-total"><?php echo format_price($grand_total); ?></span>
                                </div>
                                
                                <div class="alert alert-light border mt-3" role="alert">
                                    <div class="d-flex">
                                        <i class="fas fa-info-circle text-primary me-2 mt-1"></i>
                                        <div class="small">
                                            <strong>Free shipping</strong> available on orders over ETB 5,000
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Checkout Button -->
                            <div class="d-grid gap-3">
                                <?php if (is_logged_in()): ?>
                                <a href="checkout.php" class="btn btn-checkout">
                                    <i class="fas fa-lock me-2"></i>Proceed to Checkout
                                </a>
                                <?php else: ?>
                                <a href="login.php?redirect=checkout.php" class="btn btn-checkout">
                                    <i class="fas fa-sign-in-alt me-2"></i>Login to Checkout
                                </a>
                                <?php endif; ?>
                                
                                <div class="text-center">
                                    <a href="shop.php" class="text-muted small">
                                        <i class="fas fa-credit-card me-1"></i>
                                        Multiple payment options available
                                    </a>
                                </div>
                            </div>
                            <?php else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-receipt fa-4x text-muted mb-3"></i>
                                <p class="text-muted">Add items to your cart to see order summary</p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Security Badge -->
                    <div class="mt-4 text-center">
                        <div class="d-flex justify-content-center gap-4 mb-2">
                            <i class="fas fa-shield-alt fa-2x text-primary" title="Secure Checkout"></i>
                            <i class="fas fa-lock fa-2x text-primary" title="SSL Encryption"></i>
                            <i class="fas fa-truck-fast fa-2x text-primary" title="Fast Delivery"></i>
                        </div>
                        <p class="text-muted small mb-0">
                            <i class="fas fa-check-circle text-success me-1"></i>
                            100% Secure & Encrypted Checkout
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <?php include 'includes/footer.php'; ?>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Cart quantity management
        document.addEventListener('DOMContentLoaded', function() {
            // Increase quantity
            document.querySelectorAll('.increase-qty').forEach(button => {
                button.addEventListener('click', function() {
                    const key = this.dataset.key;
                    const input = document.querySelector(`.quantity-input[data-key="${key}"]`);
                    if (input.disabled) return;
                    
                    const max = parseInt(input.max);
                    let current = parseInt(input.value);
                    if (current < max) {
                        input.value = current + 1;
                        updateSubtotal(key);
                    } else {
                        showAlert('Maximum quantity reached. Only ' + max + ' items available.', 'warning');
                    }
                });
            });
            
            // Decrease quantity
            document.querySelectorAll('.decrease-qty').forEach(button => {
                button.addEventListener('click', function() {
                    const key = this.dataset.key;
                    const input = document.querySelector(`.quantity-input[data-key="${key}"]`);
                    if (input.disabled) return;
                    
                    const min = parseInt(input.min);
                    let current = parseInt(input.value);
                    if (current > min) {
                        input.value = current - 1;
                        updateSubtotal(key);
                    }
                });
            });
            
            // Input change handler
            document.querySelectorAll('.quantity-input').forEach(input => {
                input.addEventListener('change', function() {
                    const key = this.dataset.key;
                    if (this.disabled) return;
                    
                    const quantity = parseInt(this.value);
                    const max = parseInt(this.max);
                    const min = parseInt(this.min);
                    
                    if (isNaN(quantity) || quantity < min) {
                        this.value = min;
                    } else if (quantity > max) {
                        this.value = max;
                        showAlert('Maximum quantity reached. Only ' + max + ' items available.', 'warning');
                    }
                    
                    updateSubtotal(key);
                });
            });
            
            // Update subtotal for a specific item
            function updateSubtotal(key) {
                const input = document.querySelector(`.quantity-input[data-key="${key}"]`);
                const price = parseFloat(input.dataset.price);
                const quantity = parseInt(input.value);
                const subtotal = price * quantity;
                
                const subtotalCell = document.querySelector(`.subtotal-cell[data-key="${key}"]`);
                subtotalCell.textContent = 'ETB ' + subtotal.toFixed(2);
                subtotalCell.classList.add('text-primary');
                
                // Update cart totals
                updateCartTotals();
                
                // Remove highlight after animation
                setTimeout(() => {
                    subtotalCell.classList.remove('text-primary');
                }, 500);
            }
            
            // Update all cart totals
            function updateCartTotals() {
                let subtotal = 0;
                
                // Calculate new subtotal
                document.querySelectorAll('.quantity-input').forEach(input => {
                    if (!input.disabled) {
                        const price = parseFloat(input.dataset.price);
                        const quantity = parseInt(input.value);
                        subtotal += price * quantity;
                    }
                });
                
                // Calculate VAT (15%)
                const vat = subtotal * 0.15;
                const shipping = <?php echo $shipping_cost; ?>;
                const total = subtotal + vat + shipping;
                
                // Update display with animation
                animateValue('cart-subtotal', formatCurrency(subtotal));
                animateValue('vat-amount', formatCurrency(vat));
                animateValue('cart-total', formatCurrency(total));
            }
            
            // Format currency
            function formatCurrency(amount) {
                return 'ETB ' + amount.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
            }
            
            // Animate value change
            function animateValue(elementId, newValue) {
                const element = document.getElementById(elementId);
                element.textContent = newValue;
                element.classList.add('text-primary');
                
                setTimeout(() => {
                    element.classList.remove('text-primary');
                }, 500);
            }
            
            // Show alert
            function showAlert(message, type = 'info') {
                const alert = document.createElement('div');
                alert.className = `alert alert-${type} alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3 shadow`;
                alert.style.zIndex = '9999';
                alert.innerHTML = `
                    <div class="d-flex align-items-center">
                        <i class="fas fa-${type === 'warning' ? 'exclamation-triangle' : 'info-circle'} me-2"></i>
                        <div>${message}</div>
                        <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
                    </div>
                `;
                
                document.body.appendChild(alert);
                
                // Remove after 3 seconds
                setTimeout(() => {
                    if (alert.parentNode) {
                        alert.remove();
                    }
                }, 3000);
            }
            
            // Initialize cart totals
            updateCartTotals();
        });
    </script>
</body>
</html>