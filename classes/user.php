<?php

/**
 * User class for managing users
 */
class User
{
    private $db;

    public function __construct()
    {
        require_once __DIR__ . '/Database.php';
        $this->db = new Database();
    }

    public function register($data)
    {
        try {
            // Validate required fields
            $required = ['email', 'password', 'first_name', 'last_name', 'phone', 'user_type'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    throw new Exception(ucfirst(str_replace('_', ' ', $field)) . " is required.");
                }
            }

            // Validate email
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                throw new Exception("Invalid email address.");
            }

            // Validate phone for Ethiopian numbers
            if (!preg_match('/^\+251[0-9]{9}$/', $data['phone'])) {
                throw new Exception("Invalid phone number. Use format: +251911234567");
            }

            // Check if email exists
            if ($this->emailExists($data['email'])) {
                throw new Exception("Email already registered.");
            }

            // Hash password
            $data['password_hash'] = password_hash($data['password'], PASSWORD_DEFAULT);
            unset($data['password']);

            // Remove confirm_password if exists
            if (isset($data['confirm_password'])) {
                unset($data['confirm_password']);
            }

            // Prepare user data for insertion
            $user_data = [
                'email' => $data['email'],
                'password_hash' => $data['password_hash'],
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'phone' => $data['phone'],
                'user_type' => $data['user_type'],
                'status' => 'active',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];

            // Start transaction
            $this->db->beginTransaction();

            try {
                // Insert user
                $this->db->query("INSERT INTO users (email, password_hash, first_name, last_name, phone, user_type, status, created_at, updated_at) 
                                 VALUES (:email, :password_hash, :first_name, :last_name, :phone, :user_type, :status, :created_at, :updated_at)");

                $this->db->bind(':email', $user_data['email']);
                $this->db->bind(':password_hash', $user_data['password_hash']);
                $this->db->bind(':first_name', $user_data['first_name']);
                $this->db->bind(':last_name', $user_data['last_name']);
                $this->db->bind(':phone', $user_data['phone']);
                $this->db->bind(':user_type', $user_data['user_type']);
                $this->db->bind(':status', $user_data['status']);
                $this->db->bind(':created_at', $user_data['created_at']);
                $this->db->bind(':updated_at', $user_data['updated_at']);

                $this->db->execute();
                $user_id = $this->db->lastInsertId();

                // If vendor, create vendor profile
                if ($user_data['user_type'] === 'vendor') {
                    $store_name = isset($data['store_name']) ? $data['store_name'] : '';
                    $store_description = isset($data['store_description']) ? $data['store_description'] : '';

                    // Check if vendors table exists
                    $this->db->query("INSERT INTO vendors (user_id, store_name, store_description, created_at, updated_at) 
                                     VALUES (:user_id, :store_name, :store_description, :created_at, :updated_at)");

                    $this->db->bind(':user_id', $user_id);
                    $this->db->bind(':store_name', $store_name);
                    $this->db->bind(':store_description', $store_description);
                    $this->db->bind(':created_at', date('Y-m-d H:i:s'));
                    $this->db->bind(':updated_at', date('Y-m-d H:i:s'));

                    $this->db->execute();
                }

                // Commit transaction
                $this->db->commit();

                return $user_id;
            } catch (Exception $e) {
                // Rollback on error
                $this->db->rollback();
                throw new Exception("Registration failed: " . $e->getMessage());
            }
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function login($email, $password)
    {
        try {
            // Check if user exists and is active
            $this->db->query("SELECT * FROM users WHERE email = :email AND status = 'active'");
            $this->db->bind(':email', $email);
            $user = $this->db->single();

            if (!$user) {
                error_log("Login failed: User not found or not active - $email");
                return false;
            }

            // Debug: Log what we found
            error_log("Login attempt for: $email, User ID: " . $user['id']);

            // Verify password
            if (!password_verify($password, $user['password_hash'])) {
                error_log("Login failed: Password incorrect for $email");
                return false;
            }

            // Update last login
            $this->db->query("UPDATE users SET last_login = NOW() WHERE id = :id");
            $this->db->bind(':id', $user['id']);
            $this->db->execute();

            error_log("Login successful for: $email, User type: " . $user['user_type']);
            return $user;
        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            return false;
        }
    }

    public function getUserById($id)
    {
        try {
            $this->db->query("SELECT * FROM users WHERE id = :id AND status = 'active'");
            $this->db->bind(':id', $id);
            return $this->db->single();
        } catch (Exception $e) {
            error_log("Get user by ID error: " . $e->getMessage());
            return null;
        }
    }

    public function getUserByEmail($email)
    {
        try {
            $this->db->query("SELECT * FROM users WHERE email = :email AND status = 'active'");
            $this->db->bind(':email', $email);
            return $this->db->single();
        } catch (Exception $e) {
            error_log("Get user by email error: " . $e->getMessage());
            return null;
        }
    }

    public function updateProfile($user_id, $data)
    {
        try {
            $allowed_fields = ['first_name', 'last_name', 'phone', 'profile_image'];
            $update_fields = [];
            $params = ['id' => $user_id];

            foreach ($data as $key => $value) {
                if (in_array($key, $allowed_fields) && !empty($value)) {
                    $update_fields[] = "$key = :$key";
                    $params[$key] = $value;
                }
            }

            if (empty($update_fields)) {
                return false;
            }

            $update_fields[] = "updated_at = :updated_at";
            $params['updated_at'] = date('Y-m-d H:i:s');

            $sql = "UPDATE users SET " . implode(', ', $update_fields) . " WHERE id = :id";

            $this->db->query($sql);
            foreach ($params as $key => $value) {
                $this->db->bind(':' . $key, $value);
            }

            return $this->db->execute();
        } catch (Exception $e) {
            error_log("Update profile error: " . $e->getMessage());
            return false;
        }
    }

    public function changePassword($user_id, $current_password, $new_password)
    {
        try {
            $user = $this->getUserById($user_id);

            if (!$user) {
                throw new Exception("User not found.");
            }

            if (!password_verify($current_password, $user['password_hash'])) {
                throw new Exception("Current password is incorrect.");
            }

            $new_hash = password_hash($new_password, PASSWORD_DEFAULT);

            $this->db->query("UPDATE users SET password_hash = :password_hash, updated_at = :updated_at WHERE id = :id");
            $this->db->bind(':password_hash', $new_hash);
            $this->db->bind(':updated_at', date('Y-m-d H:i:s'));
            $this->db->bind(':id', $user_id);

            return $this->db->execute();
        } catch (Exception $e) {
            error_log("Change password error: " . $e->getMessage());
            throw new Exception($e->getMessage());
        }
    }

    public function emailExists($email)
    {
        try {
            $this->db->query("SELECT COUNT(*) as count FROM users WHERE email = :email");
            $this->db->bind(':email', $email);
            $result = $this->db->single();
            return $result['count'] > 0;
        } catch (Exception $e) {
            error_log("Email exists check error: " . $e->getMessage());
            return false;
        }
    }

    public function getAllUsers($type = null, $limit = 100, $offset = 0)
    {
        try {
            $sql = "SELECT * FROM users WHERE status = 'active'";
            $params = [];

            if ($type) {
                $sql .= " AND user_type = :type";
                $params['type'] = $type;
            }

            $sql .= " ORDER BY created_at DESC LIMIT :limit OFFSET :offset";

            $this->db->query($sql);
            $this->db->bind(':limit', $limit, PDO::PARAM_INT);
            $this->db->bind(':offset', $offset, PDO::PARAM_INT);

            foreach ($params as $key => $value) {
                $this->db->bind(':' . $key, $value);
            }

            return $this->db->resultSet();
        } catch (Exception $e) {
            error_log("Get all users error: " . $e->getMessage());
            return [];
        }
    }

    public function countUsers($type = null)
    {
        try {
            $sql = "SELECT COUNT(*) as count FROM users WHERE status = 'active'";
            $params = [];

            if ($type) {
                $sql .= " AND user_type = :type";
                $params['type'] = $type;
            }

            $this->db->query($sql);

            foreach ($params as $key => $value) {
                $this->db->bind(':' . $key, $value);
            }

            $result = $this->db->single();
            return $result['count'];
        } catch (Exception $e) {
            error_log("Count users error: " . $e->getMessage());
            return 0;
        }
    }

    public function deactivateUser($user_id)
    {
        try {
            $this->db->query("UPDATE users SET status = 'inactive', updated_at = :updated_at WHERE id = :id");
            $this->db->bind(':updated_at', date('Y-m-d H:i:s'));
            $this->db->bind(':id', $user_id);
            return $this->db->execute();
        } catch (Exception $e) {
            error_log("Deactivate user error: " . $e->getMessage());
            return false;
        }
    }

    public function activateUser($user_id)
    {
        try {
            $this->db->query("UPDATE users SET status = 'active', updated_at = :updated_at WHERE id = :id");
            $this->db->bind(':updated_at', date('Y-m-d H:i:s'));
            $this->db->bind(':id', $user_id);
            return $this->db->execute();
        } catch (Exception $e) {
            error_log("Activate user error: " . $e->getMessage());
            return false;
        }
    }

    public function updateUserType($user_id, $user_type)
    {
        try {
            $this->db->query("UPDATE users SET user_type = :user_type, updated_at = :updated_at WHERE id = :id");
            $this->db->bind(':user_type', $user_type);
            $this->db->bind(':updated_at', date('Y-m-d H:i:s'));
            $this->db->bind(':id', $user_id);
            return $this->db->execute();
        } catch (Exception $e) {
            error_log("Update user type error: " . $e->getMessage());
            return false;
        }
    }

    public function searchUsers($search_term, $type = null)
    {
        try {
            $sql = "SELECT * FROM users WHERE status = 'active' 
                    AND (email ILIKE :search OR first_name ILIKE :search OR last_name ILIKE :search OR phone ILIKE :search)";
            $params = ['search' => '%' . $search_term . '%'];

            if ($type) {
                $sql .= " AND user_type = :type";
                $params['type'] = $type;
            }

            $sql .= " ORDER BY created_at DESC";

            $this->db->query($sql);

            foreach ($params as $key => $value) {
                $this->db->bind(':' . $key, $value);
            }

            return $this->db->resultSet();
        } catch (Exception $e) {
            error_log("Search users error: " . $e->getMessage());
            return [];
        }
    }

    public function getVendorProfile($user_id)
    {
        try {
            $this->db->query("SELECT v.*, u.email, u.first_name, u.last_name, u.phone 
                             FROM vendors v 
                             JOIN users u ON v.user_id = u.id 
                             WHERE v.user_id = :user_id AND u.status = 'active'");
            $this->db->bind(':user_id', $user_id);
            return $this->db->single();
        } catch (Exception $e) {
            error_log("Get vendor profile error: " . $e->getMessage());
            return null;
        }
    }

    public function updateVendorProfile($user_id, $data)
    {
        try {
            $allowed_fields = [
                'store_name',
                'store_description',
                'business_license',
                'tax_id',
                'bank_account',
                'bank_name',
                'account_holder'
            ];
            $update_fields = [];
            $params = ['user_id' => $user_id];

            foreach ($data as $key => $value) {
                if (in_array($key, $allowed_fields) && !empty($value)) {
                    $update_fields[] = "$key = :$key";
                    $params[$key] = $value;
                }
            }

            if (empty($update_fields)) {
                return false;
            }

            $update_fields[] = "updated_at = :updated_at";
            $params['updated_at'] = date('Y-m-d H:i:s');

            $sql = "UPDATE vendors SET " . implode(', ', $update_fields) . " WHERE user_id = :user_id";

            $this->db->query($sql);
            foreach ($params as $key => $value) {
                $this->db->bind(':' . $key, $value);
            }

            return $this->db->execute();
        } catch (Exception $e) {
            error_log("Update vendor profile error: " . $e->getMessage());
            return false;
        }
    }

    public function getAllVendors($limit = 100, $offset = 0)
    {
        try {
            $sql = "SELECT v.*, u.email, u.first_name, u.last_name, u.phone, u.created_at 
                    FROM vendors v 
                    JOIN users u ON v.user_id = u.id 
                    WHERE u.status = 'active' 
                    ORDER BY v.created_at DESC 
                    LIMIT :limit OFFSET :offset";

            $this->db->query($sql);
            $this->db->bind(':limit', $limit, PDO::PARAM_INT);
            $this->db->bind(':offset', $offset, PDO::PARAM_INT);

            return $this->db->resultSet();
        } catch (Exception $e) {
            error_log("Get all vendors error: " . $e->getMessage());
            return [];
        }
    }

    // Simple registration method
    public function simpleRegister($data)
    {
        try {
            return $this->register($data);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
}
