<?php
// admin/simple-admin.php

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

echo "<h2>Session Debug</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

// Check if logged in
if (!isset($_SESSION['user_id'])) {
    echo "<p style='color:red;'>NOT LOGGED IN</p>";
    echo "<p><a href='../public/login.php'>Go to Login</a></p>";
    exit();
}

// Check if admin
if ($_SESSION['user_type'] !== 'admin') {
    echo "<p style='color:red;'>NOT ADMIN - You are: " . ($_SESSION['user_type'] ?? 'NOT SET') . "</p>";
    echo "<p><a href='../public/logout.php'>Logout</a> and login as admin</p>";
    exit();
}

// If we get here, user is admin
echo "<h1 style='color:green;'>✓ WELCOME TO ADMIN PANEL</h1>";
echo "<p>Admin ID: " . $_SESSION['user_id'] . "</p>";
echo "<p>Admin Email: " . ($_SESSION['user_email'] ?? $_SESSION['email'] ?? 'Not set') . "</p>";
