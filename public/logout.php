<?php
require_once '../config.php';

logout_user();

// Set logout message
$_SESSION['info'] = 'You have been logged out successfully.';

// Redirect to homepage
redirect('index.php');
?>