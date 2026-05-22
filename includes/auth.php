<?php

/**
 * Authentication functions
 */

function is_logged_in()
{
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function require_login()
{
    if (!is_logged_in()) {
        $_SESSION['redirect_to'] = $_SERVER['REQUEST_URI'];
        redirect('public/login.php');
    }
}

function require_admin()
{
    require_login();
    if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
        $_SESSION['error'] = 'Access denied. Admin privileges required.';
        redirect('public/index.php');
    }
}

function require_vendor()
{
    require_login();
    if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'vendor') {
        $_SESSION['error'] = 'Access denied. Vendor account required.';
        redirect('public/index.php');
    }
}

function require_buyer()
{
    require_login();
    if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'buyer') {
        $_SESSION['error'] = 'Access denied. Buyer account required.';
        redirect('public/index.php');
    }
}

function hash_password($password)
{
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}

function verify_password($password, $hash)
{
    return password_verify($password, $hash);
}

function login_user($user_id, $user_type, $remember = false)
{
    $_SESSION['user_id'] = $user_id;
    $_SESSION['user_type'] = $user_type;
    $_SESSION['login_time'] = time();

    if ($remember) {
        $token = bin2hex(random_bytes(32));
        $expiry = time() + (30 * 24 * 60 * 60); // 30 days

        // Store remember token in database - you need to create this table
        $db = new Database();
        $db->query("INSERT INTO user_sessions (user_id, session_id, expires_at) VALUES (:user_id, :token, :expires_at)");
        $db->bind(':user_id', $user_id);
        $db->bind(':token', $token);
        $db->bind(':expires_at', date('Y-m-d H:i:s', $expiry));
        $db->execute();

        setcookie('remember_token', $token, $expiry, '/', '', false, true);
    }

    // Update last login - FIXED: Use correct column name 'last_login'
    $db = new Database();
    $db->query("UPDATE users SET last_login = NOW() WHERE id = :id");
    $db->bind(':id', $user_id);
    $db->execute();
}

function logout_user()
{
    // Delete remember token if exists
    if (isset($_COOKIE['remember_token'])) {
        $db = new Database();
        $db->query("DELETE FROM user_sessions WHERE session_id = :token");
        $db->bind(':token', $_COOKIE['remember_token']);
        $db->execute();

        setcookie('remember_token', '', time() - 3600, '/');
    }

    // Clear session
    session_unset();
    session_destroy();

    // Start new session for messages
    session_start();
}

function check_remember_token()
{
    if (!is_logged_in() && isset($_COOKIE['remember_token'])) {
        $db = new Database();
        $db->query("SELECT user_id FROM user_sessions WHERE session_id = :token AND expires_at > NOW()");
        $db->bind(':token', $_COOKIE['remember_token']);
        $result = $db->single();

        if ($result) {
            $user_id = $result['user_id'];

            // Get user type - FIXED: Use correct column name 'status' instead of 'is_active'
            $db->query("SELECT user_type FROM users WHERE id = :id AND status = 'active'");
            $db->bind(':id', $user_id);
            $user = $db->single();

            if ($user) {
                login_user($user_id, $user['user_type']);

                // Update token expiry
                $new_expiry = time() + (30 * 24 * 60 * 60);
                $db->query("UPDATE user_sessions SET expires_at = :expires_at, last_activity = NOW() WHERE session_id = :token");
                $db->bind(':expires_at', date('Y-m-d H:i:s', $new_expiry));
                $db->bind(':token', $_COOKIE['remember_token']);
                $db->execute();

                setcookie('remember_token', $_COOKIE['remember_token'], $new_expiry, '/', '', false, true);
            }
        }
    }
}

function generate_password_reset_token($email)
{
    $db = new Database();

    // Check if user exists - FIXED: Use correct column name 'status'
    $db->query("SELECT id FROM users WHERE email = :email AND status = 'active'");
    $db->bind(':email', $email);
    $user = $db->single();

    if (!$user) {
        return false;
    }

    // Generate token
    $token = bin2hex(random_bytes(32));
    $expires_at = date('Y-m-d H:i:s', time() + (60 * 60)); // 1 hour

    // Delete existing tokens
    $db->query("DELETE FROM password_resets WHERE email = :email");
    $db->bind(':email', $email);
    $db->execute();

    // Insert new token
    $db->query("INSERT INTO password_resets (email, token, expires_at) VALUES (:email, :token, :expires_at)");
    $db->bind(':email', $email);
    $db->bind(':token', hash('sha256', $token));
    $db->bind(':expires_at', $expires_at);
    $db->execute();

    return $token;
}

function verify_password_reset_token($token)
{
    $db = new Database();
    $db->query("SELECT email FROM password_resets WHERE token = :token AND expires_at > NOW()");
    $db->bind(':token', hash('sha256', $token));
    $result = $db->single();

    return $result ? $result['email'] : false;
}

function delete_password_reset_token($token)
{
    $db = new Database();
    $db->query("DELETE FROM password_resets WHERE token = :token");
    $db->bind(':token', hash('sha256', $token));
    $db->execute();
}

// Helper function for password validation
function validate_password($password)
{
    $errors = [];

    if (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long";
    }

    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = "Password must contain at least one uppercase letter";
    }

    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = "Password must contain at least one lowercase letter";
    }

    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = "Password must contain at least one number";
    }

    if (!preg_match('/[^A-Za-z0-9]/', $password)) {
        $errors[] = "Password must contain at least one special character";
    }

    return $errors;
}

// Function to get current user info
function get_current_user()
{
    if (!is_logged_in()) {
        return null;
    }

    $db = new Database();
    $db->query("SELECT * FROM users WHERE id = :id");
    $db->bind(':id', $_SESSION['user_id']);
    return $db->single();
}

// Function to check if user has verified email
function has_verified_email()
{
    $user = get_current_user();
    return $user && $user['email_verified'] == true;
}

// Function to require email verification
function require_email_verification()
{
    if (!has_verified_email()) {
        $_SESSION['error'] = 'Please verify your email address to access this feature.';
        redirect('public/verify-email.php');
    }
}
