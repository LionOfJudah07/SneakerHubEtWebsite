<?php
// includes/config.php
// This is the MAIN config file that all classes include

// Prevent multiple includes
if (!defined('SNEAKER_MART_CONFIG_INCLUDES')) {
    define('SNEAKER_MART_CONFIG_INCLUDES', true);
    
    // Start session only if not already started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Site constants
    define('SITE_NAME', 'Sneaker Mart');
    define('SITE_URL', 'http://localhost/sneaker-mart');
    
    // Database configuration - PostgreSQL
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'sneaker_commerce');
    define('DB_USER', 'postgres');
    define('DB_PASS', 'admin'); // CHANGE THIS!
    define('DB_PORT', '5432');
    
    // Error reporting for development
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    
    // Set base directory
    define('BASE_PATH', dirname(dirname(__FILE__)));
    
    // Auto-load classes
    spl_autoload_register(function ($class_name) {
        $file = BASE_PATH . '/classes/' . $class_name . '.php';
        if (file_exists($file)) {
            require_once $file;
        }
    });
    
    // Include functions if it exists
    $functions_file = BASE_PATH . '/functions.php';
    if (file_exists($functions_file)) {
        require_once $functions_file;
    }
}