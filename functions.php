<?php
// functions.php - Helper functions for the application

// Check if user is admin
function is_admin() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin';
}

// Check if user is vendor
function is_vendor() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'vendor';
}

// Check if user is buyer
function is_buyer() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'buyer';
}

// Require admin access
function require_admin() {
    if (!is_admin()) {
        $_SESSION['error'] = 'Access denied. Admin privileges required.';
        header('Location: ../index.php');
        exit();
    }
}

// Require vendor access
function require_vendor() {
    if (!is_vendor()) {
        $_SESSION['error'] = 'Access denied. Vendor account required.';
        header('Location: ../index.php');
        exit();
    }
}

// Require buyer access
function require_buyer() {
    if (!is_buyer()) {
        $_SESSION['error'] = 'Access denied. Please login as a buyer.';
        header('Location: ../index.php');
        exit();
    }
}

// Check if logged in
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

// Format price with currency
function format_price($amount) {
    if (!is_numeric($amount)) {
        $amount = 0;
    }
    return 'ETB ' . number_format(floatval($amount), 2);
}

// Format date
function format_date($date, $format = 'F j, Y') {
    if (empty($date) || $date == '0000-00-00 00:00:00') {
        return 'N/A';
    }
    return date($format, strtotime($date));
}

// Get cart item count
function get_cart_count() {
    if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
        return 0;
    }
    $count = 0;
    foreach ($_SESSION['cart'] as $item) {
        $count += $item['quantity'] ?? 1;
    }
    return $count;
}

// Check if product is in wishlist
function is_in_wishlist_session($product_id) {
    return isset($_SESSION['wishlist']) && in_array($product_id, $_SESSION['wishlist']);
}

// Calculate discount percentage
function calculate_discount_percentage($original, $discounted) {
    if ($original <= 0 || $discounted >= $original) {
        return 0;
    }
    return round((($original - $discounted) / $original) * 100);
}

// Get order status badge
function get_order_status_badge($status) {
    $badges = [
        'pending' => 'badge bg-warning',
        'processing' => 'badge bg-info',
        'shipped' => 'badge bg-primary',
        'delivered' => 'badge bg-success',
        'cancelled' => 'badge bg-danger',
        'refunded' => 'badge bg-secondary'
    ];
    $class = $badges[$status] ?? 'badge bg-secondary';
    $label = ucfirst($status);
    return "<span class='{$class}'>{$label}</span>";
}

// Get payment status badge
function get_payment_status_badge($status) {
    $badges = [
        'pending' => 'badge bg-warning',
        'paid' => 'badge bg-success',
        'failed' => 'badge bg-danger',
        'refunded' => 'badge bg-info'
    ];
    $class = $badges[$status] ?? 'badge bg-secondary';
    $label = ucfirst($status);
    return "<span class='{$class}'>{$label}</span>";
}

// Get user avatar
function get_user_avatar($user_id, $profile_image = null) {
    if (!empty($profile_image) && file_exists($profile_image)) {
        return $profile_image;
    }
    return 'assets/images/users/default.png';
}

// Sanitize input
function sanitize_input($data) {
    if (is_array($data)) {
        return array_map('sanitize_input', $data);
    }
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

// Redirect helper
function redirect($url) {
    header("Location: " . $url);
    exit();
}

// Calculate VAT
function calculate_vat($amount, $rate = 0.15) {
    return $amount * $rate;
}

// Get Ethiopian regions
function get_ethiopian_regions() {
    return [
        'Addis Ababa',
        'Oromia',
        'Amhara',
        'Tigray',
        'Southern Nations',
        'Somali',
        'Afar',
        'Benishangul-Gumuz',
        'Gambela',
        'Harari',
        'Sidama',
        'Dire Dawa'
    ];
}

// Get product condition label
function get_product_condition_label($condition) {
    $labels = [
        'new' => 'New',
        'used' => 'Used',
        'refurbished' => 'Refurbished'
    ];
    return $labels[$condition] ?? ucfirst($condition);
}

// CART HELPER FUNCTIONS
function add_to_cart_session($product_id, $quantity = 1, $size = '', $color = '') {
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    
    // Check if product already exists in cart
    $found = false;
    foreach ($_SESSION['cart'] as $key => $item) {
        if ($item['product_id'] == $product_id && 
            $item['size'] == $size && 
            $item['color'] == $color) {
            
            // Update quantity
            $_SESSION['cart'][$key]['quantity'] += $quantity;
            $found = true;
            break;
        }
    }
    
    // Add new item if not found
    if (!$found) {
        $_SESSION['cart'][] = [
            'product_id' => $product_id,
            'quantity' => $quantity,
            'size' => $size,
            'color' => $color
        ];
    }
    
    return true;
}

function remove_from_cart_session($key) {
    if (isset($_SESSION['cart'][$key])) {
        unset($_SESSION['cart'][$key]);
        // Re-index array
        $_SESSION['cart'] = array_values($_SESSION['cart']);
        return true;
    }
    return false;
}

function update_cart_quantity($key, $quantity) {
    if (isset($_SESSION['cart'][$key])) {
        if ($quantity > 0) {
            $_SESSION['cart'][$key]['quantity'] = $quantity;
        } else {
            remove_from_cart_session($key);
        }
        return true;
    }
    return false;
}

function clear_cart_session() {
    $_SESSION['cart'] = [];
    return true;
}

function get_cart_items_with_details() {
    $cart_items = [];
    
    if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
        return $cart_items;
    }
    
    // Use direct database query instead of Product class to avoid circular dependency
    $db = new Database();
    
    foreach ($_SESSION['cart'] as $key => $item) {
        $db->query("
            SELECT p.*, u.store_name 
            FROM products p 
            JOIN users u ON p.vendor_id = u.id 
            WHERE p.id = :product_id AND p.status = 'active'
        ");
        $db->bind(':product_id', $item['product_id']);
        $product_data = $db->single();
        
        if ($product_data) {
            // Handle images
            $image = 'assets/images/products/default.jpg';
            if (!empty($product_data['images'])) {
                $images = json_decode($product_data['images'], true);
                if (is_array($images) && !empty($images[0])) {
                    $image = $images[0];
                }
            }
            
            $price = $product_data['discount_price'] ?? $product_data['price'];
            $subtotal = $price * $item['quantity'];
            
            $cart_items[] = [
                'key' => $key,
                'product_id' => $item['product_id'],
                'name' => $product_data['name'],
                'brand' => $product_data['brand'],
                'image' => $image,
                'price' => $price,
                'quantity' => $item['quantity'],
                'size' => $item['size'] ?? '',
                'color' => $item['color'] ?? '',
                'stock' => $product_data['stock_quantity'] ?? 0,
                'subtotal' => $subtotal
            ];
        }
    }
    
    return $cart_items;
}

function get_cart_total() {
    $cart_items = get_cart_items_with_details();
    $total = 0;
    
    foreach ($cart_items as $item) {
        $total += $item['subtotal'];
    }
    
    return $total;
}

// FLASH MESSAGE FUNCTIONS
function set_success($message) {
    $_SESSION['flash']['success'] = $message;
}

function set_error($message) {
    $_SESSION['flash']['error'] = $message;
}

function has_flash_message($type = null) {
    if ($type) {
        return isset($_SESSION['flash'][$type]);
    }
    return isset($_SESSION['flash']);
}

function get_flash_message($type) {
    if (isset($_SESSION['flash'][$type])) {
        $message = $_SESSION['flash'][$type];
        unset($_SESSION['flash'][$type]);
        return $message;
    }
    return '';
}

// PASSWORD FUNCTIONS
function hash_password($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

function verify_password($password, $hash) {
    return password_verify($password, $hash);
}

// VALIDATION FUNCTIONS - ONLY DECLARED ONCE
function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function validate_phone($phone) {
    return preg_match('/^\+251[0-9]{9}$/', $phone);
}

// ========== NEW FUNCTIONS ADDED ==========
// LOGIN USER FUNCTION (THIS WAS MISSING)
function login_user($user_id, $user_type, $remember = false) {
    // Start session if not already started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Set session variables
    $_SESSION['user_id'] = $user_id;
    $_SESSION['user_type'] = $user_type;
    $_SESSION['logged_in_at'] = time();
    
    // Set remember me cookie if requested
    if ($remember) {
        $token = bin2hex(random_bytes(32));
        $expiry = time() + (30 * 24 * 60 * 60); // 30 days
        setcookie('remember_token', $token, $expiry, '/');
    }
}

// GET USER DASHBOARD FUNCTION
function get_user_dashboard($user_type) {
    switch ($user_type) {
        case 'admin':
            return '../admin/index.php';
        case 'vendor':
            return '../vendor/index.php';
        case 'buyer':
        default:
            return '../buyer/index.php';
    }
}

// REQUIRE LOGIN FUNCTION
function require_login() {
    if (!is_logged_in()) {
        $_SESSION['redirect_to'] = $_SERVER['REQUEST_URI'];
        header('Location: ../public/login.php');
        exit();
    }
}

// LOGOUT USER FUNCTION
function logout_user() {
    // Clear all session variables
    $_SESSION = [];
    
    // Delete session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Destroy the session
    session_destroy();
    
    // Clear remember me cookie
    setcookie('remember_token', '', time() - 3600, '/');
}

// GET CURRENT USER INFO FUNCTIONS
function get_current_user_id() {
    return $_SESSION['user_id'] ?? null;
}

function get_current_user_type() {
    return $_SESSION['user_type'] ?? null;
}
// IMAGE UPLOAD FUNCTION (ADD THIS TO YOUR functions.php)
function upload_image($file, $target_dir, $max_size = 5242880) { // 5MB default
    $errors = [];
    $file_name = basename($file["name"]);
    $target_file = $target_dir . uniqid() . '_' . $file_name;
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
    
    // Check if image file is actual image
    $check = @getimagesize($file["tmp_name"]);
    if ($check === false) {
        $errors[] = "File is not an image.";
    }
    
    // Check file size
    if ($file["size"] > $max_size) {
        $errors[] = "File is too large. Maximum size is " . ($max_size / 1024 / 1024) . "MB.";
    }
    
    // Allow certain file formats
    $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    if (!in_array($imageFileType, $allowed_types)) {
        $errors[] = "Only JPG, JPEG, PNG, GIF & WEBP files are allowed.";
    }
    
    // Create directory if it doesn't exist
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    if (empty($errors)) {
        if (move_uploaded_file($file["tmp_name"], $target_file)) {
            return [
                'success' => true,
                'file_path' => $target_file,
                'file_name' => $file_name
            ];
        } else {
            $errors[] = "Sorry, there was an error uploading your file.";
        }
    }
    
    return [
        'success' => false,
        'errors' => $errors
    ];
}

// WISHLIST FUNCTIONS - Add these to your functions.php

/**
 * Add product to wishlist session
 */
function add_to_wishlist_session($product_id) {
    if (!isset($_SESSION['wishlist'])) {
        $_SESSION['wishlist'] = [];
    }
    
    if (!in_array($product_id, $_SESSION['wishlist'])) {
        $_SESSION['wishlist'][] = $product_id;
        return true;
    }
    return false;
}

/**
 * Remove product from wishlist session
 */
function remove_from_wishlist_session($product_id) {
    if (isset($_SESSION['wishlist'])) {
        $key = array_search($product_id, $_SESSION['wishlist']);
        if ($key !== false) {
            unset($_SESSION['wishlist'][$key]);
            $_SESSION['wishlist'] = array_values($_SESSION['wishlist']);
            return true;
        }
    }
    return false;
}

/**
 * Check if product is in wishlist
 */
function is_in_wishlist($product_id) {
    return isset($_SESSION['wishlist']) && in_array($product_id, $_SESSION['wishlist']);
}

/**
 * Get wishlist count
 */
function get_wishlist_count() {
    return isset($_SESSION['wishlist']) ? count($_SESSION['wishlist']) : 0;
}

/**
 * Get wishlist items with product details
 */
function get_wishlist_items() {
    $wishlist_items = [];
    
    if (!isset($_SESSION['wishlist']) || empty($_SESSION['wishlist'])) {
        return $wishlist_items;
    }
    
    // Use direct database query
    $db = new Database();
    
    foreach ($_SESSION['wishlist'] as $product_id) {
        $db->query("SELECT * FROM products WHERE id = :id AND status = 'active'");
        $db->bind(':id', $product_id);
        $product = $db->single();
        
        if ($product) {
            $wishlist_items[] = $product;
        }
    }
    
    return $wishlist_items;
}
?>