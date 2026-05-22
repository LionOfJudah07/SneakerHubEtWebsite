<?php
// admin/test-session.php
session_start();

echo "<h2>Session Debug in Admin</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

if (isset($_SESSION['user_id'])) {
    echo "<p>✓ user_id: " . $_SESSION['user_id'] . "</p>";
} else {
    echo "<p style='color:red;'>✗ user_id NOT SET</p>";
}

if (isset($_SESSION['user_type'])) {
    echo "<p>✓ user_type: " . $_SESSION['user_type'] . "</p>";
} else {
    echo "<p style='color:red;'>✗ user_type NOT SET</p>";
}

echo '<p><a href="../public/login.php">Go to Login</a></p>';
echo '<p><a href="index.php">Try Admin Page</a></p>';
