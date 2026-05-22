<?php
require_once '../config.php';

header('Content-Type: application/json');

// Allow CORS
header('Access-Control-Allow-Origin: ' . SITE_URL);
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$response = ['success' => false, 'message' => 'Invalid request'];

try {
    $db = new Database();
    $action = $_GET['action'] ?? '';
    
    switch ($action) {
        case 'login':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $data = json_decode(file_get_contents('php://input'), true);
                
                if (empty($data['email']) || empty($data['password'])) {
                    throw new Exception('Email and password are required.');
                }
                
                $user = new User();
                $user_data = $user->login($data['email'], $data['password']);
                
                $response = [
                    'success' => true,
                    'message' => 'Login successful',
                    'user' => [
                        'id' => $user_data['id'],
                        'first_name' => $user_data['first_name'],
                        'last_name' => $user_data['last_name'],
                        'email' => $user_data['email'],
                        'user_type' => $user_data['user_type'],
                        'store_name' => $user_data['store_name'] ?? ''
                    ]
                ];
            }
            break;
            
        case 'register':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $data = json_decode(file_get_contents('php://input'), true);
                
                $required = ['first_name', 'last_name', 'email', 'password', 'phone'];
                foreach ($required as $field) {
                    if (empty($data[$field])) {
                        throw new Exception(ucfirst($field) . ' is required.');
                    }
                }
                
                if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                    throw new Exception('Invalid email address.');
                }
                
                if (strlen($data['password']) < 6) {
                    throw new Exception('Password must be at least 6 characters.');
                }
                
                if (!validate_phone($data['phone'])) {
                    throw new Exception('Please enter a valid Ethiopian phone number (format: +251911234567).');
                }
                
                $user = new User();
                $user_id = $user->register($data);
                
                // Auto login after registration
                $user_data = $user->login($data['email'], $data['password']);
                
                $response = [
                    'success' => true,
                    'message' => 'Registration successful',
                    'user_id' => $user_id,
                    'user' => [
                        'id' => $user_data['id'],
                        'first_name' => $user_data['first_name'],
                        'last_name' => $user_data['last_name'],
                        'email' => $user_data['email'],
                        'user_type' => $user_data['user_type'],
                        'store_name' => $user_data['store_name'] ?? ''
                    ]
                ];
            }
            break;
            
        case 'check_email':
            if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['email'])) {
                $user = new User();
                $existing = $user->getUserByEmail($_GET['email']);
                
                $response = [
                    'success' => true,
                    'available' => !$existing,
                    'message' => $existing ? 'Email already registered' : 'Email available'
                ];
            }
            break;
            
        case 'forgot_password':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $data = json_decode(file_get_contents('php://input'), true);
                
                if (empty($data['email'])) {
                    throw new Exception('Email is required.');
                }
                
                $user = new User();
                $user_data = $user->getUserByEmail($data['email']);
                
                if (!$user_data) {
                    throw new Exception('Email not found.');
                }
                
                // Generate reset token
                $token = bin2hex(random_bytes(32));
                $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
                
                $db->query("
                    INSERT INTO password_resets (email, token, expires_at) 
                    VALUES (:email, :token, :expires_at)
                    ON CONFLICT (email) DO UPDATE 
                    SET token = :token, expires_at = :expires_at, created_at = NOW()
                ");
                $db->bind(':email', $data['email']);
                $db->bind(':token', $token);
                $db->bind(':expires_at', $expires);
                $db->execute();
                
                // In production, send email with reset link
                // $reset_link = SITE_URL . "/public/reset-password.php?token=" . $token;
                // send_email($data['email'], 'Password Reset', "Reset your password: $reset_link");
                
                $response = [
                    'success' => true,
                    'message' => 'Password reset instructions sent to your email.',
                    'token' => $token // In production, don't return token
                ];
            }
            break;
            
        case 'reset_password':
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $data = json_decode(file_get_contents('php://input'), true);
                
                if (empty($data['token']) || empty($data['password'])) {
                    throw new Exception('Token and new password are required.');
                }
                
                if (strlen($data['password']) < 6) {
                    throw new Exception('Password must be at least 6 characters.');
                }
                
                // Verify token
                $db->query("
                    SELECT email FROM password_resets 
                    WHERE token = :token AND expires_at > NOW()
                ");
                $db->bind(':token', $data['token']);
                $reset = $db->single();
                
                if (!$reset) {
                    throw new Exception('Invalid or expired token.');
                }
                
                // Update password
                $user = new User();
                $user->resetPassword($reset['email'], $data['password']);
                
                // Delete used token
                $db->query("DELETE FROM password_resets WHERE token = :token");
                $db->bind(':token', $data['token']);
                $db->execute();
                
                $response = [
                    'success' => true,
                    'message' => 'Password reset successful. You can now login with your new password.'
                ];
            }
            break;
            
        case 'logout':
            session_destroy();
            $response = [
                'success' => true,
                'message' => 'Logged out successfully'
            ];
            break;
            
        default:
            throw new Exception('Invalid action.');
    }
    
} catch (Exception $e) {
    $response = [
        'success' => false,
        'message' => $e->getMessage()
    ];
    http_response_code(400);
}

echo json_encode($response);
?>