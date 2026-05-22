<?php
require_once '../config.php';
require_once '../functions.php';

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Store any redirect URL before logout
$redirect_url = isset($_GET['redirect']) ? $_GET['redirect'] : '../public/index.php';

// Clear all session variables
$_SESSION = [];

// Destroy the session
session_destroy();

// Clear remember me cookie
setcookie('remember_token', '', time() - 3600, '/');

// Set logout message
session_start(); // Start new session for message
$_SESSION['logout_message'] = 'You have been logged out successfully.';

// Redirect to home page
header('Location: ' . $redirect_url);
exit();
