<?php
// Root config.php - Main configuration file

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Site constants
define('SITE_NAME', 'Sneaker Mart');
define('SITE_URL', 'http://localhost/sneaker-mart');
define('SHIPPING_COST', 150.00); // Only defined here

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'sneaker_commerce');
define('DB_USER', 'postgres');
define('DB_PASS', 'admin');
define('DB_PORT', '5432');

// Error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Base path
define('BASE_PATH', dirname(__FILE__));

// Include database class
require_once __DIR__ . '/classes/Database.php';

// Auto-load classes
spl_autoload_register(function ($class_name) {
    $file = __DIR__ . '/classes/' . $class_name . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

// Include functions - BUT FIRST CHECK IF THEY EXIST
if (!function_exists('sanitize_input')) {
    // Include functions file only if core functions don't exist
    $functions_file = __DIR__ . '/functions.php';
    if (file_exists($functions_file)) {
        require_once $functions_file;
    }
}

// Initialize session arrays if not set
if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];
if (!isset($_SESSION['wishlist'])) $_SESSION['wishlist'] = [];
if (!isset($_SESSION['flash'])) $_SESSION['flash'] = [];

// =============================================================================
// AUTHENTICATION FUNCTIONS - ONLY DEFINE IF NOT ALREADY DEFINED
// =============================================================================

if (!function_exists('is_logged_in')) {
    /**
     * Check if user is logged in
     * @return bool
     */
    function is_logged_in() {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }
}

if (!function_exists('is_admin')) {
    /**
     * Check if user is admin
     * @return bool
     */
    function is_admin() {
        return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin';
    }
}

if (!function_exists('is_vendor')) {
    /**
     * Check if user is vendor
     * @return bool
     */
    function is_vendor() {
        return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'vendor';
    }
}

if (!function_exists('is_buyer')) {
    /**
     * Check if user is buyer
     * @return bool
     */
    function is_buyer() {
        return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'buyer';
    }
}

if (!function_exists('require_admin')) {
    /**
     * Require admin access - redirects if not admin
     */
    function require_admin() {
        if (!is_logged_in()) {
            $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
            $_SESSION['flash']['error'] = 'Please login to access the admin panel.';
            header('Location: ../public/login.php');
            exit();
        }
        
        if (!is_admin()) {
            $_SESSION['flash']['error'] = 'Access denied. Admin privileges required.';
            
            if (is_vendor()) {
                header('Location: ../vendor/');
            } elseif (is_buyer()) {
                header('Location: ../buyer/');
            } else {
                header('Location: ../public/index.php');
            }
            exit();
        }
    }
}

// =============================================================================
// USER PROFILE FUNCTIONS
// =============================================================================

if (!function_exists('get_user_avatar')) {
    /**
     * Get user avatar URL
     * @param int $user_id User ID
     * @return string Avatar URL
     */
    function get_user_avatar($user_id) {
        $default_avatar = '../assets/images/avatars/default.png';
        
        if (empty($user_id)) {
            return $default_avatar;
        }
        
        $avatar_dir = __DIR__ . '/assets/images/avatars/';
        
        $extensions = ['png', 'jpg', 'jpeg'];
        foreach ($extensions as $ext) {
            $avatar_path = $avatar_dir . $user_id . '.' . $ext;
            if (file_exists($avatar_path)) {
                return '../assets/images/avatars/' . $user_id . '.' . $ext;
            }
        }
        
        return $default_avatar;
    }
}

if (!function_exists('get_session_user')) {
    /**
     * Get current user data from session
     * @return array|null User data or null if not logged in
     */
    function get_session_user() {
        if (!is_logged_in()) {
            return null;
        }
        
        try {
            $db = new Database();
            $db->query("SELECT * FROM users WHERE id = :id");
            $db->bind(':id', $_SESSION['user_id']);
            return $db->single();
        } catch (Exception $e) {
            error_log("Error getting session user: " . $e->getMessage());
            return null;
        }
    }
}

if (!function_exists('get_cart_count')) {
    /**
     * Get cart count for current user
     * @return int Cart item count
     */
    function get_cart_count() {
        if (!is_logged_in()) {
            $count = 0;
            foreach ($_SESSION['cart'] as $item) {
                $count += $item['quantity'] ?? 1;
            }
            return $count;
        }
        
        try {
            $db = new Database();
            $db->query("SELECT COUNT(*) as count FROM cart_items WHERE user_id = :user_id");
            $db->bind(':user_id', $_SESSION['user_id']);
            $result = $db->single();
            return $result['count'] ?? 0;
        } catch (Exception $e) {
            error_log("Error getting cart count: " . $e->getMessage());
            return 0;
        }
    }
}

// =============================================================================
// HELPER FUNCTIONS - ONLY DEFINE IF NOT ALREADY DEFINED
// =============================================================================

if (!function_exists('format_price')) {
    /**
     * Format price
     * @param float $price Price to format
     * @return string Formatted price
     */
    function format_price($price) {
        if (empty($price) || !is_numeric($price)) {
            return 'ETB 0.00';
        }
        return 'ETB ' . number_format(floatval($price), 2);
    }
}

if (!function_exists('format_date')) {
    /**
     * Format date
     * @param string $date Date string
     * @param string $format Output format
     * @return string Formatted date
     */
    function format_date($date, $format = 'F j, Y') {
        if (empty($date)) {
            return '';
        }
        
        try {
            $datetime = new DateTime($date);
            return $datetime->format($format);
        } catch (Exception $e) {
            return $date;
        }
    }
}

if (!function_exists('get_order_status_badge')) {
    /**
     * Get order status badge HTML
     * @param string $status Order status
     * @return string HTML badge
     */
    function get_order_status_badge($status) {
        $badge_classes = [
            'pending' => 'badge bg-warning',
            'processing' => 'badge bg-info',
            'shipped' => 'badge bg-primary',
            'delivered' => 'badge bg-success',
            'cancelled' => 'badge bg-danger'
        ];
        
        $class = $badge_classes[strtolower($status)] ?? 'badge bg-secondary';
        $label = ucfirst($status);
        
        return '<span class="' . $class . '">' . $label . '</span>';
    }
}

if (!function_exists('get_payment_status_badge')) {
    /**
     * Get payment status badge HTML
     * @param string $status Payment status
     * @return string HTML badge
     */
    function get_payment_status_badge($status) {
        $badge_classes = [
            'pending' => 'badge bg-warning',
            'paid' => 'badge bg-success',
            'failed' => 'badge bg-danger',
            'refunded' => 'badge bg-info'
        ];
        
        $class = $badge_classes[strtolower($status)] ?? 'badge bg-secondary';
        $label = ucfirst($status);
        
        return '<span class="' . $class . '">' . $label . '</span>';
    }
}
?>