<?php
/**
 * Common helper functions
 */

function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

function format_price($price) {
    return 'ETB ' . number_format($price, 2);
}

function get_user_ip() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        return $_SERVER['REMOTE_ADDR'];
    }
}

function generate_order_number() {
    return 'ORD-' . date('Ymd') . '-' . strtoupper(uniqid());
}

function generate_sku($product_name, $brand) {
    $prefix = substr(strtoupper($brand), 0, 3);
    $product_code = substr(strtoupper(preg_replace('/[^A-Z]/', '', $product_name)), 0, 3);
    $random = strtoupper(substr(uniqid(), -4));
    return $prefix . '-' . $product_code . '-' . $random;
}

function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function validate_phone($phone) {
    // Ethiopian phone validation (+251XXXXXXXXX)
    return preg_match('/^\+251[0-9]{9}$/', $phone);
}

function format_date($date_string, $format = 'F j, Y, g:i a') {
    $date = new DateTime($date_string);
    return $date->format($format);
}

function calculate_discount_percentage($original_price, $discount_price) {
    if ($original_price > 0) {
        $discount = (($original_price - $discount_price) / $original_price) * 100;
        return round($discount);
    }
    return 0;
}

function get_product_condition_label($condition) {
    $labels = [
        'new' => '<span class="badge bg-success">New</span>',
        'used' => '<span class="badge bg-warning">Used</span>',
        'refurbished' => '<span class="badge bg-info">Refurbished</span>'
    ];
    return $labels[$condition] ?? '<span class="badge bg-secondary">Unknown</span>';
}

function get_order_status_badge($status) {
    $badges = [
        'pending' => '<span class="badge bg-warning">Pending</span>',
        'processing' => '<span class="badge bg-info">Processing</span>',
        'shipped' => '<span class="badge bg-primary">Shipped</span>',
        'delivered' => '<span class="badge bg-success">Delivered</span>',
        'cancelled' => '<span class="badge bg-danger">Cancelled</span>'
    ];
    return $badges[$status] ?? '<span class="badge bg-secondary">' . $status . '</span>';
}

function get_payment_status_badge($status) {
    $badges = [
        'pending' => '<span class="badge bg-warning">Pending</span>',
        'paid' => '<span class="badge bg-success">Paid</span>',
        'failed' => '<span class="badge bg-danger">Failed</span>',
        'refunded' => '<span class="badge bg-info">Refunded</span>'
    ];
    return $badges[$status] ?? '<span class="badge bg-secondary">' . $status . '</span>';
}

function redirect($url) {
    header("Location: $url");
    exit();
}

function is_ajax_request() {
    return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

function json_response($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit();
}

function upload_image($file, $target_dir, $max_size = MAX_FILE_SIZE) {
    $errors = [];
    $file_name = basename($file["name"]);
    $target_file = $target_dir . uniqid() . '_' . $file_name;
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
    
    // Check if image file is actual image
    $check = getimagesize($file["tmp_name"]);
    if ($check === false) {
        $errors[] = "File is not an image.";
    }
    
    // Check file size
    if ($file["size"] > $max_size) {
        $errors[] = "File is too large. Maximum size is " . ($max_size / 1024 / 1024) . "MB.";
    }
    
    // Allow certain file formats
    if (!in_array($imageFileType, ALLOWED_IMAGE_TYPES)) {
        $errors[] = "Only JPG, JPEG, PNG & GIF files are allowed.";
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

function delete_file($file_path) {
    if (file_exists($file_path) && is_file($file_path)) {
        return unlink($file_path);
    }
    return false;
}

function get_ethiopian_regions() {
    global $ethiopian_regions;
    return $ethiopian_regions;
}

function get_delivery_estimate($region) {
    global $delivery_estimates;
    return $delivery_estimates[$region] ?? '7-10';
}

function calculate_vat($amount) {
    return $amount * VAT_RATE;
}

function calculate_shipping($region, $weight = 0) {
    $base_cost = SHIPPING_COST;
    $regional_multiplier = 1;
    
    if ($region == 'Addis Ababa') {
        $regional_multiplier = 1;
    } elseif (in_array($region, ['Oromia', 'Amhara'])) {
        $regional_multiplier = 1.5;
    } else {
        $regional_multiplier = 2;
    }
    
    $weight_cost = $weight * 10; // 10 ETB per kg
    
    return $base_cost * $regional_multiplier + $weight_cost;
}

function generate_pagination($total_items, $items_per_page, $current_page, $url) {
    $total_pages = ceil($total_items / $items_per_page);
    
    if ($total_pages <= 1) {
        return '';
    }
    
    $pagination = '<nav aria-label="Page navigation"><ul class="pagination">';
    
    // Previous button
    if ($current_page > 1) {
        $pagination .= '<li class="page-item"><a class="page-link" href="' . $url . '?page=' . ($current_page - 1) . '">Previous</a></li>';
    }
    
    // Page numbers
    $start_page = max(1, $current_page - 2);
    $end_page = min($total_pages, $current_page + 2);
    
    for ($i = $start_page; $i <= $end_page; $i++) {
        $active = ($i == $current_page) ? ' active' : '';
        $pagination .= '<li class="page-item' . $active . '"><a class="page-link" href="' . $url . '?page=' . $i . '">' . $i . '</a></li>';
    }
    
    // Next button
    if ($current_page < $total_pages) {
        $pagination .= '<li class="page-item"><a class="page-link" href="' . $url . '?page=' . ($current_page + 1) . '">Next</a></li>';
    }
    
    $pagination .= '</ul></nav>';
    
    return $pagination;
}

function get_user_avatar($user_id, $profile_image) {
    if ($profile_image && file_exists($profile_image)) {
        return SITE_URL . '/' . $profile_image;
    }
    return SITE_URL . '/assets/images/users/default-avatar.png';
}

function clean_string($string) {
    $string = preg_replace('/[^A-Za-z0-9\- ]/', '', $string);
    $string = preg_replace('/\s+/', ' ', $string);
    return trim($string);
}

function generate_csrf_token() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token($token) {
    if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        return false;
    }
    return true;
}

function get_cart_items_with_details() {
    $cart_items = [];
    
    if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
        return $cart_items;
    }
    
    // Use direct database query
    try {
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
    } catch (Exception $e) {
        error_log("Cart error: " . $e->getMessage());
    }
    
    return $cart_items;
}

// NEW FUNCTIONS ADDED FOR LOGIN SYSTEM

function hash_password($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

function verify_password($password, $hash) {
    return password_verify($password, $hash);
}

function is_logged_in() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function require_login() {
    if (!is_logged_in()) {
        $_SESSION['redirect_to'] = $_SERVER['REQUEST_URI'];
        redirect('../public/login.php');
    }
}

function require_user_type($allowed_types) {
    require_login();
    
    if (!isset($_SESSION['user_type'])) {
        redirect('../public/login.php');
    }
    
    if (is_array($allowed_types)) {
        if (!in_array($_SESSION['user_type'], $allowed_types)) {
            redirect('../public/access-denied.php');
        }
    } elseif ($_SESSION['user_type'] !== $allowed_types) {
        redirect('../public/access-denied.php');
    }
}

function get_current_user_id() {
    return $_SESSION['user_id'] ?? null;
}

function get_current_user_type() {
    return $_SESSION['user_type'] ?? null;
}

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

function login_user($user_id, $user_type, $remember = false) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Regenerate session ID for security
    session_regenerate_id(true);
    
    // Set session variables
    $_SESSION['user_id'] = $user_id;
    $_SESSION['user_type'] = $user_type;
    $_SESSION['logged_in_at'] = time();
    
    // Set remember me cookie if requested
    if ($remember) {
        $token = bin2hex(random_bytes(32));
        $expiry = time() + (30 * 24 * 60 * 60); // 30 days
        setcookie('remember_token', $token, $expiry, '/');
        
        // Store token in database (you need to add this method to User class)
        // $user = new User();
        // $user->setRememberToken($user_id, $token, date('Y-m-d H:i:s', $expiry));
    }
}

function check_remember_me() {
    if (!is_logged_in() && isset($_COOKIE['remember_token'])) {
        $token = $_COOKIE['remember_token'];
        
        // Check token in database and login user
        // You need to implement this based on your database structure
        // $user = new User();
        // $user_data = $user->validateRememberToken($token);
        
        // if ($user_data) {
        //     login_user($user_data['id'], $user_data['user_type'], false);
        // }
    }
}
?>