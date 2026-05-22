<?php
require_once '../includes/config.php';
require_once '../functions.php';
require_once '../includes/session.php';

// Require admin login
require_admin();

$page_title = 'System Settings - ' . SITE_NAME;

// Initialize Database
require_once '../classes/Database.php';
$db = new Database();

// Define default constants if not defined
if (!defined('SITE_EMAIL')) {
    define('SITE_EMAIL', 'admin@' . ($_SERVER['HTTP_HOST'] ?? 'localhost'));
}

// Handle form submissions
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save_general_settings'])) {
        // General Settings
        $site_name = sanitize_input($_POST['site_name'] ?? '');
        $site_email = sanitize_input($_POST['site_email'] ?? '');
        $site_phone = sanitize_input($_POST['site_phone'] ?? '');
        $site_address = sanitize_input($_POST['site_address'] ?? '');
        $currency = sanitize_input($_POST['currency'] ?? 'ETB');
        $currency_symbol = sanitize_input($_POST['currency_symbol'] ?? 'ETB');
        $timezone = sanitize_input($_POST['timezone'] ?? 'UTC');
        $maintenance_mode = isset($_POST['maintenance_mode']) ? 1 : 0;
        $maintenance_message = sanitize_input($_POST['maintenance_message'] ?? '');

        // Validate required fields
        if (empty($site_name)) {
            $errors[] = 'Site name is required';
        }

        if (empty($site_email)) {
            $errors[] = 'Site email is required';
        } elseif (!filter_var($site_email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email format';
        }

        if (empty($errors)) {
            try {
                $db->beginTransaction();

                // Update each setting
                $settings = [
                    'site_name' => $site_name,
                    'site_email' => $site_email,
                    'site_phone' => $site_phone,
                    'site_address' => $site_address,
                    'currency' => $currency,
                    'currency_symbol' => $currency_symbol,
                    'timezone' => $timezone,
                    'maintenance_mode' => $maintenance_mode,
                    'maintenance_message' => $maintenance_message
                ];

                foreach ($settings as $key => $value) {
                    $db->query("INSERT INTO settings (setting_key, setting_value) 
                                VALUES (:key, :value)
                                ON CONFLICT (setting_key) 
                                DO UPDATE SET setting_value = :value, updated_at = NOW()");
                    $db->bind(':key', $key);
                    $db->bind(':value', $value);
                    $db->execute();
                }

                $db->commit();
                $success = 'General settings updated successfully!';

                // Update config constants for current session
                if (defined('SITE_NAME')) {
                    // SITE_NAME is already defined as constant, we can't redefine it
                    // But we can store in session for current user
                    $_SESSION['site_name'] = $site_name;
                }

                $_SESSION['site_email'] = $site_email;
            } catch (Exception $e) {
                $db->rollBack();
                $errors[] = 'Failed to update settings: ' . $e->getMessage();
            }
        }
    } elseif (isset($_POST['save_email_settings'])) {
        // Email Settings
        $smtp_host = sanitize_input($_POST['smtp_host'] ?? '');
        $smtp_port = sanitize_input($_POST['smtp_port'] ?? '');
        $smtp_username = sanitize_input($_POST['smtp_username'] ?? '');
        $smtp_password = sanitize_input($_POST['smtp_password'] ?? '');
        $smtp_encryption = sanitize_input($_POST['smtp_encryption'] ?? '');
        $email_from_name = sanitize_input($_POST['email_from_name'] ?? '');
        $email_from_address = sanitize_input($_POST['email_from_address'] ?? '');

        // Validate SMTP settings
        if (!empty($smtp_host) && empty($smtp_port)) {
            $errors[] = 'SMTP port is required when SMTP host is provided';
        }

        if (empty($email_from_name)) {
            $errors[] = 'Email from name is required';
        }

        if (empty($email_from_address)) {
            $errors[] = 'Email from address is required';
        } elseif (!filter_var($email_from_address, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email from address';
        }

        if (empty($errors)) {
            try {
                $db->beginTransaction();

                $settings = [
                    'smtp_host' => $smtp_host,
                    'smtp_port' => $smtp_port,
                    'smtp_username' => $smtp_username,
                    'smtp_password' => !empty($smtp_password) ? base64_encode($smtp_password) : '',
                    'smtp_encryption' => $smtp_encryption,
                    'email_from_name' => $email_from_name,
                    'email_from_address' => $email_from_address
                ];

                foreach ($settings as $key => $value) {
                    $db->query("INSERT INTO settings (setting_key, setting_value) 
                                VALUES (:key, :value)
                                ON CONFLICT (setting_key) 
                                DO UPDATE SET setting_value = :value, updated_at = NOW()");
                    $db->bind(':key', $key);
                    $db->bind(':value', $value);
                    $db->execute();
                }

                $db->commit();
                $success = 'Email settings updated successfully!';
            } catch (Exception $e) {
                $db->rollBack();
                $errors[] = 'Failed to update email settings: ' . $e->getMessage();
            }
        }
    } elseif (isset($_POST['save_payment_settings'])) {
        // Payment Settings
        $enable_payments = isset($_POST['enable_payments']) ? 1 : 0;
        $default_payment_method = sanitize_input($_POST['default_payment_method'] ?? '');
        $currency = sanitize_input($_POST['currency'] ?? 'ETB');
        $tax_rate = floatval($_POST['tax_rate'] ?? 0);
        $shipping_fee = floatval($_POST['shipping_fee'] ?? 0);
        $free_shipping_threshold = floatval($_POST['free_shipping_threshold'] ?? 0);

        // Validate payment settings
        if ($tax_rate < 0 || $tax_rate > 100) {
            $errors[] = 'Tax rate must be between 0 and 100';
        }

        if ($shipping_fee < 0) {
            $errors[] = 'Shipping fee cannot be negative';
        }

        if ($free_shipping_threshold < 0) {
            $errors[] = 'Free shipping threshold cannot be negative';
        }

        if (empty($errors)) {
            try {
                $db->beginTransaction();

                $settings = [
                    'enable_payments' => $enable_payments,
                    'default_payment_method' => $default_payment_method,
                    'currency' => $currency,
                    'tax_rate' => $tax_rate,
                    'shipping_fee' => $shipping_fee,
                    'free_shipping_threshold' => $free_shipping_threshold
                ];

                foreach ($settings as $key => $value) {
                    $db->query("INSERT INTO settings (setting_key, setting_value) 
                                VALUES (:key, :value)
                                ON CONFLICT (setting_key) 
                                DO UPDATE SET setting_value = :value, updated_at = NOW()");
                    $db->bind(':key', $key);
                    $db->bind(':value', $value);
                    $db->execute();
                }

                $db->commit();
                $success = 'Payment settings updated successfully!';
            } catch (Exception $e) {
                $db->rollBack();
                $errors[] = 'Failed to update payment settings: ' . $e->getMessage();
            }
        }
    } elseif (isset($_POST['test_email'])) {
        // Test Email Functionality
        $test_email = sanitize_input($_POST['test_email'] ?? '');

        if (empty($test_email)) {
            $errors[] = 'Test email address is required';
        } elseif (!filter_var($test_email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid test email address';
        } else {
            // Get site name from settings or use default
            $site_name = isset($_SESSION['site_name']) ? $_SESSION['site_name'] : SITE_NAME;
            $site_email = isset($_SESSION['site_email']) ? $_SESSION['site_email'] : SITE_EMAIL;

            // Send test email
            $subject = 'Test Email from ' . $site_name;
            $message = "This is a test email sent from your website's admin panel.\n\n";
            $message .= "If you received this email, your email settings are working correctly.\n";
            $message .= "Time sent: " . date('Y-m-d H:i:s') . "\n";
            $message .= "Site: " . $site_name . "\n";

            $headers = "From: " . $site_email . "\r\n";
            $headers .= "Reply-To: " . $site_email . "\r\n";
            $headers .= "X-Mailer: PHP/" . phpversion();

            if (mail($test_email, $subject, $message, $headers)) {
                $success = 'Test email sent successfully to ' . $test_email;
            } else {
                $errors[] = 'Failed to send test email. Please check your email settings.';
            }
        }
    } elseif (isset($_POST['clear_cache'])) {
        // Clear Cache
        $cache_types = $_POST['cache_types'] ?? [];

        if (empty($cache_types)) {
            $errors[] = 'Please select at least one cache type to clear';
        } else {
            try {
                $cleared = [];

                if (in_array('system', $cache_types)) {
                    // Clear session cache
                    session_regenerate_id(true);
                    $cleared[] = 'System Cache';
                }

                if (in_array('template', $cache_types)) {
                    // Clear template cache (if exists)
                    $template_cache_dir = '../cache/templates/';
                    if (is_dir($template_cache_dir)) {
                        $files = glob($template_cache_dir . '*');
                        foreach ($files as $file) {
                            if (is_file($file)) {
                                unlink($file);
                            }
                        }
                        $cleared[] = 'Template Cache';
                    }
                }

                if (in_array('image', $cache_types)) {
                    // Clear image cache (if exists)
                    $image_cache_dir = '../cache/images/';
                    if (is_dir($image_cache_dir)) {
                        $files = glob($image_cache_dir . '*');
                        foreach ($files as $file) {
                            if (is_file($file)) {
                                unlink($file);
                            }
                        }
                        $cleared[] = 'Image Cache';
                    }
                }

                if (in_array('database', $cache_types)) {
                    // Clear database cache (if using query caching)
                    try {
                        $db->query("SELECT pg_terminate_backend(pid) FROM pg_stat_activity WHERE state = 'idle' AND query_start < NOW() - INTERVAL '1 hour'");
                        $db->execute();
                        $cleared[] = 'Database Cache';
                    } catch (Exception $e) {
                        // Ignore if query fails
                    }
                }

                if (!empty($cleared)) {
                    $success = 'Successfully cleared: ' . implode(', ', $cleared);
                } else {
                    $success = 'Cache cleared (no cache directories found)';
                }
            } catch (Exception $e) {
                $errors[] = 'Failed to clear cache: ' . $e->getMessage();
            }
        }
    } elseif (isset($_POST['backup_database'])) {
        // Backup Database
        $backup_type = sanitize_input($_POST['backup_type'] ?? '');

        try {
            // Create backup directory if it doesn't exist
            $backup_dir = '../backups/';
            if (!is_dir($backup_dir)) {
                mkdir($backup_dir, 0755, true);
            }

            $timestamp = date('Y-m-d_H-i-s');
            $backup_file = $backup_dir . 'backup_' . $timestamp . '.sql';

            if ($backup_type === 'full') {
                // Full backup using pg_dump (requires shell access)
                $command = "pg_dump -h " . DB_HOST . " -U " . DB_USER . " -d " . DB_NAME . " -f " . $backup_file;
                putenv("PGPASSWORD=" . DB_PASS);
                exec($command, $output, $return_var);

                if ($return_var === 0) {
                    $success = 'Full database backup created: ' . basename($backup_file);
                } else {
                    $errors[] = 'Failed to create full backup using pg_dump';
                }
            } else {
                // Basic backup (structure only)
                $tables = ['users', 'products', 'categories', 'orders', 'order_items', 'reviews', 'vendors', 'settings'];

                $backup_content = "-- Database Backup - " . SITE_NAME . "\n";
                $backup_content .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
                $backup_content .= "-- Backup Type: Structure Only\n\n";

                foreach ($tables as $table) {
                    try {
                        $db->query("SELECT column_name, data_type, is_nullable, column_default 
                                   FROM information_schema.columns 
                                   WHERE table_name = :table 
                                   ORDER BY ordinal_position");
                        $db->bind(':table', $table);
                        $columns = $db->resultSet();

                        if (!empty($columns)) {
                            $backup_content .= "-- Table: $table\n";
                            $backup_content .= "CREATE TABLE IF NOT EXISTS $table (\n";

                            $column_defs = [];
                            foreach ($columns as $col) {
                                $def = "  " . $col['column_name'] . " " . $col['data_type'];
                                if ($col['is_nullable'] === 'NO') {
                                    $def .= " NOT NULL";
                                }
                                if (!empty($col['column_default'])) {
                                    $def .= " DEFAULT " . $col['column_default'];
                                }
                                $column_defs[] = $def;
                            }
                            $backup_content .= implode(",\n", $column_defs);
                            $backup_content .= "\n);\n\n";
                        }
                    } catch (Exception $e) {
                        // Table might not exist, skip it
                        continue;
                    }
                }

                if (file_put_contents($backup_file, $backup_content)) {
                    $success = 'Database structure backup created: ' . basename($backup_file);
                } else {
                    $errors[] = 'Failed to create backup file';
                }
            }
        } catch (Exception $e) {
            $errors[] = 'Backup failed: ' . $e->getMessage();
        }
    }
}

// Get current settings from database
$settings = [];
try {
    $db->query("SELECT setting_key, setting_value FROM settings");
    $settings_result = $db->resultSet();

    foreach ($settings_result as $row) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
} catch (Exception $e) {
    // If settings table doesn't exist, use defaults
    error_log("Settings table error: " . $e->getMessage());
}

// Decode password if set
if (isset($settings['smtp_password']) && !empty($settings['smtp_password'])) {
    $settings['smtp_password'] = base64_decode($settings['smtp_password']);
}

// Define default values if settings don't exist
$default_settings = [
    'site_name' => defined('SITE_NAME') ? SITE_NAME : 'Sneaker Mart',
    'site_email' => defined('SITE_EMAIL') ? SITE_EMAIL : 'admin@' . ($_SERVER['HTTP_HOST'] ?? 'localhost'),
    'site_phone' => '',
    'site_address' => '',
    'currency' => 'ETB',
    'currency_symbol' => 'ETB',
    'timezone' => 'UTC',
    'maintenance_mode' => '0',
    'maintenance_message' => 'Site is under maintenance. Please check back later.',
    'smtp_host' => '',
    'smtp_port' => '587',
    'smtp_username' => '',
    'smtp_password' => '',
    'smtp_encryption' => 'tls',
    'email_from_name' => defined('SITE_NAME') ? SITE_NAME : 'Sneaker Mart',
    'email_from_address' => defined('SITE_EMAIL') ? SITE_EMAIL : 'admin@' . ($_SERVER['HTTP_HOST'] ?? 'localhost'),
    'enable_payments' => '1',
    'default_payment_method' => 'cash_on_delivery',
    'tax_rate' => '0',
    'shipping_fee' => '0',
    'free_shipping_threshold' => '0'
];

// Merge settings with defaults
$settings = array_merge($default_settings, $settings);

// Get system info
$system_info = [
    'PHP Version' => phpversion(),
    'Database' => 'PostgreSQL',
    'Server Software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
    'Server Name' => $_SERVER['SERVER_NAME'] ?? 'localhost',
    'Max Upload Size' => ini_get('upload_max_filesize'),
    'Max Post Size' => ini_get('post_max_size'),
    'Memory Limit' => ini_get('memory_limit'),
    'Timezone' => date_default_timezone_get(),
    'Disk Free Space' => function_exists('disk_free_space') ? round(disk_free_space(__DIR__) / (1024 * 1024 * 1024), 2) . ' GB' : 'Unknown'
];

// Get recent backups
$backup_files = [];
$backup_dir = '../backups/';
if (is_dir($backup_dir)) {
    $files = glob($backup_dir . '*.sql');
    if ($files) {
        usort($files, function ($a, $b) {
            return filemtime($b) - filemtime($a);
        });
        $backup_files = array_slice($files, 0, 5); // Get 5 most recent backups
    }
}

// Handle backup deletion
if (isset($_GET['delete_backup'])) {
    $backup_file = sanitize_input($_GET['delete_backup']);
    $file_path = $backup_dir . $backup_file;

    if (file_exists($file_path) && unlink($file_path)) {
        $_SESSION['success'] = 'Backup deleted successfully!';
    } else {
        $_SESSION['error'] = 'Failed to delete backup file.';
    }
    header('Location: settings.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Select2 for better selects -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            padding-top: 0;
        }

        .sidebar {
            min-height: 100vh;
            background-color: #343a40;
            padding-top: 20px;
            position: fixed;
            top: 0;
            left: 0;
            width: 240px;
            overflow-y: auto;
            z-index: 1000;
        }

        .main-content {
            margin-left: 240px;
            padding: 20px;
        }

        @media (max-width: 768px) {
            .sidebar {
                position: static;
                width: 100%;
                min-height: auto;
            }

            .main-content {
                margin-left: 0;
            }
        }

        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
        }

        .card-header {
            background-color: #fff;
            border-bottom: 1px solid #eee;
            font-weight: 600;
        }

        .nav-tabs {
            border-bottom: 1px solid #dee2e6;
            margin-bottom: 20px;
        }

        .nav-tabs .nav-link {
            border: none;
            color: #495057;
            font-weight: 500;
            padding: 10px 20px;
        }

        .nav-tabs .nav-link.active {
            color: #667eea;
            border-bottom: 3px solid #667eea;
            background: transparent;
        }

        .tab-content {
            padding: 20px;
            background: white;
            border-radius: 0 0 10px 10px;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.25rem rgba(102, 126, 234, 0.25);
        }

        .system-info-table td:first-child {
            font-weight: 600;
            width: 200px;
        }

        .backup-file {
            padding: 10px;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            margin-bottom: 10px;
        }

        .backup-file .size {
            font-size: 0.875rem;
            color: #6c757d;
        }

        .sidebar .nav-link {
            color: #adb5bd;
            padding: 10px 20px;
            margin-bottom: 5px;
            border-radius: 5px;
            transition: all 0.3s;
        }

        .sidebar .nav-link:hover {
            color: #fff;
            background-color: #495057;
        }

        .sidebar .nav-link.active {
            color: #fff;
            background-color: #007bff;
        }

        .maintenance-alert {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .select2-container--bootstrap-5 .select2-selection {
            min-height: 38px;
            border-color: #ced4da;
        }
    </style>
</head>

<body>
    <!-- Admin Sidebar -->
    <div class="sidebar d-print-none">
        <div class="position-sticky pt-3">
            <div class="text-center mb-4">
                <h4 class="text-white">
                    <i class="fas fa-shoe-prints"></i> <?php echo htmlspecialchars(SITE_NAME); ?>
                </h4>
                <p class="text-white-50 small">Admin Panel</p>
            </div>

            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link" href="index.php">
                        <i class="fas fa-tachometer-alt me-2"></i>
                        Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="products.php">
                        <i class="fas fa-box me-2"></i>
                        Products
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="users.php">
                        <i class="fas fa-users me-2"></i>
                        Users
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="orders.php">
                        <i class="fas fa-shopping-cart me-2"></i>
                        Orders
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="categories.php">
                        <i class="fas fa-tags me-2"></i>
                        Categories
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" href="settings.php">
                        <i class="fas fa-cog me-2"></i>
                        Settings
                    </a>
                </li>
            </ul>

            <hr class="bg-light my-4">

            <div class="px-3">
                <div class="d-flex align-items-center text-white mb-3">
                    <i class="fas fa-user-circle fa-2x me-2"></i>
                    <div>
                        <strong><?php echo htmlspecialchars($_SESSION['first_name'] ?? 'Admin'); ?></strong>
                        <div class="small text-muted">Administrator</div>
                    </div>
                </div>

                <div class="d-grid gap-2">
                    <a href="../public/index.php" class="btn btn-outline-light btn-sm">
                        <i class="fas fa-home me-2"></i>View Site
                    </a>
                    <a href="../public/logout.php" class="btn btn-outline-danger btn-sm">
                        <i class="fas fa-sign-out-alt me-2"></i>Logout
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Page Header -->
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
            <h1 class="h2">System Settings</h1>
            <div class="btn-toolbar mb-2 mb-md-0">
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.print()">
                    <i class="fas fa-print me-2"></i>Print
                </button>
            </div>
        </div>

        <!-- Success/Error Messages -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show mb-4">
                <i class="fas fa-check-circle me-2"></i>
                <?php echo htmlspecialchars($_SESSION['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                <?php unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show mb-4">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <?php echo htmlspecialchars($_SESSION['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                <?php unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <!-- Maintenance Mode Alert -->
        <?php if ($settings['maintenance_mode'] == '1'): ?>
            <div class="maintenance-alert">
                <div class="d-flex align-items-center">
                    <i class="fas fa-exclamation-triangle fa-2x me-3"></i>
                    <div>
                        <h5 class="mb-1">Maintenance Mode is ENABLED</h5>
                        <p class="mb-0">Your site is currently in maintenance mode. Regular users cannot access the site.</p>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show mb-4">
                <i class="fas fa-check-circle me-2"></i>
                <?php echo htmlspecialchars($success); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger alert-dismissible fade show mb-4">
                <h5><i class="fas fa-exclamation-triangle me-2"></i>Please fix the following errors:</h5>
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Settings Tabs -->
        <ul class="nav nav-tabs" id="settingsTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="general-tab" data-bs-toggle="tab" data-bs-target="#general" type="button">
                    <i class="fas fa-cog me-2"></i>General
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="email-tab" data-bs-toggle="tab" data-bs-target="#email" type="button">
                    <i class="fas fa-envelope me-2"></i>Email
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="payment-tab" data-bs-toggle="tab" data-bs-target="#payment" type="button">
                    <i class="fas fa-credit-card me-2"></i>Payment
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="system-tab" data-bs-toggle="tab" data-bs-target="#system" type="button">
                    <i class="fas fa-server me-2"></i>System
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="tools-tab" data-bs-toggle="tab" data-bs-target="#tools" type="button">
                    <i class="fas fa-tools me-2"></i>Tools
                </button>
            </li>
        </ul>

        <div class="tab-content" id="settingsTabsContent">
            <!-- General Settings Tab -->
            <div class="tab-pane fade show active" id="general" role="tabpanel">
                <form method="POST">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="site_name" class="form-label">Site Name *</label>
                            <input type="text" class="form-control" id="site_name" name="site_name"
                                value="<?php echo htmlspecialchars($settings['site_name']); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="site_email" class="form-label">Site Email *</label>
                            <input type="email" class="form-control" id="site_email" name="site_email"
                                value="<?php echo htmlspecialchars($settings['site_email']); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="site_phone" class="form-label">Site Phone</label>
                            <input type="text" class="form-control" id="site_phone" name="site_phone"
                                value="<?php echo htmlspecialchars($settings['site_phone']); ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="currency" class="form-label">Currency</label>
                            <input type="text" class="form-control" id="currency" name="currency"
                                value="<?php echo htmlspecialchars($settings['currency']); ?>">
                        </div>
                        <div class="col-md-12 mb-3">
                            <label for="site_address" class="form-label">Site Address</label>
                            <textarea class="form-control" id="site_address" name="site_address" rows="3"><?php echo htmlspecialchars($settings['site_address']); ?></textarea>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="timezone" class="form-label">Timezone</label>
                            <select class="form-select select2-timezone" id="timezone" name="timezone">
                                <?php
                                $timezones = timezone_identifiers_list();
                                foreach ($timezones as $tz) {
                                    echo '<option value="' . htmlspecialchars($tz) . '"';
                                    if ($settings['timezone'] == $tz) echo ' selected';
                                    echo '>' . htmlspecialchars($tz) . '</option>';
                                }
                                ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="currency_symbol" class="form-label">Currency Symbol</label>
                            <input type="text" class="form-control" id="currency_symbol" name="currency_symbol"
                                value="<?php echo htmlspecialchars($settings['currency_symbol']); ?>">
                        </div>
                    </div>

                    <hr class="my-4">

                    <h5 class="mb-3">Maintenance Mode</h5>
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="maintenance_mode" name="maintenance_mode"
                                    value="1" <?php echo $settings['maintenance_mode'] == '1' ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="maintenance_mode">
                                    Enable Maintenance Mode
                                </label>
                            </div>
                            <small class="text-muted">When enabled, only administrators can access the site.</small>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label for="maintenance_message" class="form-label">Maintenance Message</label>
                            <textarea class="form-control" id="maintenance_message" name="maintenance_message" rows="3"><?php echo htmlspecialchars($settings['maintenance_message']); ?></textarea>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end mt-4">
                        <button type="submit" name="save_general_settings" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Save General Settings
                        </button>
                    </div>
                </form>
            </div>

            <!-- Email Settings Tab -->
            <div class="tab-pane fade" id="email" role="tabpanel">
                <form method="POST">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="smtp_host" class="form-label">SMTP Host</label>
                            <input type="text" class="form-control" id="smtp_host" name="smtp_host"
                                value="<?php echo htmlspecialchars($settings['smtp_host']); ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="smtp_port" class="form-label">SMTP Port</label>
                            <input type="number" class="form-control" id="smtp_port" name="smtp_port"
                                value="<?php echo htmlspecialchars($settings['smtp_port']); ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="smtp_username" class="form-label">SMTP Username</label>
                            <input type="text" class="form-control" id="smtp_username" name="smtp_username"
                                value="<?php echo htmlspecialchars($settings['smtp_username']); ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="smtp_password" class="form-label">SMTP Password</label>
                            <input type="password" class="form-control" id="smtp_password" name="smtp_password"
                                value="">
                            <small class="text-muted">Leave blank to keep current password</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="smtp_encryption" class="form-label">SMTP Encryption</label>
                            <select class="form-select" id="smtp_encryption" name="smtp_encryption">
                                <option value="">None</option>
                                <option value="tls" <?php echo $settings['smtp_encryption'] == 'tls' ? 'selected' : ''; ?>>TLS</option>
                                <option value="ssl" <?php echo $settings['smtp_encryption'] == 'ssl' ? 'selected' : ''; ?>>SSL</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="email_from_name" class="form-label">Email From Name *</label>
                            <input type="text" class="form-control" id="email_from_name" name="email_from_name"
                                value="<?php echo htmlspecialchars($settings['email_from_name']); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="email_from_address" class="form-label">Email From Address *</label>
                            <input type="email" class="form-control" id="email_from_address" name="email_from_address"
                                value="<?php echo htmlspecialchars($settings['email_from_address']); ?>" required>
                        </div>
                    </div>

                    <hr class="my-4">

                    <!-- Test Email Section -->
                    <h5 class="mb-3">Test Email Settings</h5>
                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label for="test_email" class="form-label">Test Email Address</label>
                            <input type="email" class="form-control" id="test_email" name="test_email"
                                placeholder="Enter email address to send test email">
                        </div>
                        <div class="col-md-4 d-flex align-items-end mb-3">
                            <button type="submit" name="test_email" class="btn btn-info w-100">
                                <i class="fas fa-paper-plane me-2"></i>Send Test Email
                            </button>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end mt-4">
                        <button type="submit" name="save_email_settings" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Save Email Settings
                        </button>
                    </div>
                </form>
            </div>

            <!-- Payment Settings Tab -->
            <div class="tab-pane fade" id="payment" role="tabpanel">
                <form method="POST">
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="enable_payments" name="enable_payments"
                                    value="1" <?php echo $settings['enable_payments'] == '1' ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="enable_payments">
                                    Enable Payments
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="default_payment_method" class="form-label">Default Payment Method</label>
                            <select class="form-select" id="default_payment_method" name="default_payment_method">
                                <option value="cash_on_delivery" <?php echo $settings['default_payment_method'] == 'cash_on_delivery' ? 'selected' : ''; ?>>Cash on Delivery</option>
                                <option value="bank_transfer" <?php echo $settings['default_payment_method'] == 'bank_transfer' ? 'selected' : ''; ?>>Bank Transfer</option>
                                <option value="telebirr" <?php echo $settings['default_payment_method'] == 'telebirr' ? 'selected' : ''; ?>>TeleBirr</option>
                                <option value="cbe_birr" <?php echo $settings['default_payment_method'] == 'cbe_birr' ? 'selected' : ''; ?>>CBE Birr</option>
                                <option value="hello_cash" <?php echo $settings['default_payment_method'] == 'hello_cash' ? 'selected' : ''; ?>>HelloCash</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="currency" class="form-label">Currency</label>
                            <input type="text" class="form-control" id="currency" name="currency"
                                value="<?php echo htmlspecialchars($settings['currency']); ?>">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="tax_rate" class="form-label">Tax Rate (%)</label>
                            <input type="number" class="form-control" id="tax_rate" name="tax_rate"
                                value="<?php echo htmlspecialchars($settings['tax_rate']); ?>" min="0" max="100" step="0.01">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="shipping_fee" class="form-label">Shipping Fee (ETB)</label>
                            <input type="number" class="form-control" id="shipping_fee" name="shipping_fee"
                                value="<?php echo htmlspecialchars($settings['shipping_fee']); ?>" min="0" step="0.01">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="free_shipping_threshold" class="form-label">Free Shipping Threshold (ETB)</label>
                            <input type="number" class="form-control" id="free_shipping_threshold" name="free_shipping_threshold"
                                value="<?php echo htmlspecialchars($settings['free_shipping_threshold']); ?>" min="0" step="0.01">
                        </div>
                    </div>

                    <div class="d-flex justify-content-end mt-4">
                        <button type="submit" name="save_payment_settings" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Save Payment Settings
                        </button>
                    </div>
                </form>
            </div>

            <!-- System Info Tab -->
            <div class="tab-pane fade" id="system" role="tabpanel">
                <div class="row">
                    <div class="col-md-6">
                        <h5 class="mb-3">System Information</h5>
                        <table class="table system-info-table">
                            <tbody>
                                <?php foreach ($system_info as $key => $value): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($key); ?></td>
                                        <td><?php echo htmlspecialchars($value); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h5 class="mb-3">Database Information</h5>
                        <div class="alert alert-info">
                            <i class="fas fa-database me-2"></i>
                            <strong>Database:</strong> <?php echo htmlspecialchars(DB_NAME); ?><br>
                            <strong>Host:</strong> <?php echo htmlspecialchars(DB_HOST); ?><br>
                            <strong>User:</strong> <?php echo htmlspecialchars(DB_USER); ?>
                        </div>

                        <h5 class="mt-4 mb-3">PHP Extensions</h5>
                        <div class="row">
                            <div class="col-md-6">
                                <ul class="list-unstyled">
                                    <li><i class="fas fa-check text-success me-2"></i>PDO: <?php echo extension_loaded('pdo') ? 'Enabled' : 'Disabled'; ?></li>
                                    <li><i class="fas fa-check text-success me-2"></i>pgsql: <?php echo extension_loaded('pgsql') ? 'Enabled' : 'Disabled'; ?></li>
                                    <li><i class="fas fa-check text-success me-2"></i>JSON: <?php echo extension_loaded('json') ? 'Enabled' : 'Disabled'; ?></li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <ul class="list-unstyled">
                                    <li><i class="fas fa-check text-success me-2"></i>GD: <?php echo extension_loaded('gd') ? 'Enabled' : 'Disabled'; ?></li>
                                    <li><i class="fas fa-check text-success me-2"></i>Mbstring: <?php echo extension_loaded('mbstring') ? 'Enabled' : 'Disabled'; ?></li>
                                    <li><i class="fas fa-check text-success me-2"></i>OpenSSL: <?php echo extension_loaded('openssl') ? 'Enabled' : 'Disabled'; ?></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tools Tab -->
            <div class="tab-pane fade" id="tools" role="tabpanel">
                <!-- Cache Management -->
                <h5 class="mb-3">Cache Management</h5>
                <form method="POST" class="mb-4">
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Select Cache Types to Clear:</label>
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="cache_system" name="cache_types[]" value="system">
                                        <label class="form-check-label" for="cache_system">
                                            <i class="fas fa-cog me-2"></i>System Cache
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="cache_template" name="cache_types[]" value="template">
                                        <label class="form-check-label" for="cache_template">
                                            <i class="fas fa-file-alt me-2"></i>Template Cache
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="cache_image" name="cache_types[]" value="image">
                                        <label class="form-check-label" for="cache_image">
                                            <i class="fas fa-image me-2"></i>Image Cache
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="cache_database" name="cache_types[]" value="database">
                                        <label class="form-check-label" for="cache_database">
                                            <i class="fas fa-database me-2"></i>Database Cache
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <button type="submit" name="clear_cache" class="btn btn-warning">
                                <i class="fas fa-broom me-2"></i>Clear Selected Cache
                            </button>
                        </div>
                    </div>
                </form>

                <hr class="my-4">

                <!-- Database Backup -->
                <h5 class="mb-3">Database Backup</h5>
                <form method="POST" class="mb-4">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="backup_type" class="form-label">Backup Type</label>
                            <select class="form-select" id="backup_type" name="backup_type">
                                <option value="structure">Structure Only (Safe)</option>
                                <option value="full">Full Backup (Requires pg_dump)</option>
                            </select>
                            <small class="text-muted">Structure backup creates SQL with table definitions only.</small>
                        </div>
                        <div class="col-md-6 d-flex align-items-end mb-3">
                            <button type="submit" name="backup_database" class="btn btn-success w-100">
                                <i class="fas fa-download me-2"></i>Create Backup
                            </button>
                        </div>
                    </div>
                </form>

                <!-- Recent Backups -->
                <?php if (!empty($backup_files)): ?>
                    <h5 class="mb-3">Recent Backups</h5>
                    <div class="mb-4">
                        <?php foreach ($backup_files as $file): ?>
                            <div class="backup-file">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <i class="fas fa-file-archive text-primary me-2"></i>
                                        <strong><?php echo basename($file); ?></strong>
                                        <div class="size">
                                            Size: <?php echo round(filesize($file) / 1024, 2); ?> KB
                                            | Created: <?php echo date('Y-m-d H:i:s', filemtime($file)); ?>
                                        </div>
                                    </div>
                                    <div>
                                        <a href="<?php echo $file; ?>" class="btn btn-sm btn-outline-primary me-2" download>
                                            <i class="fas fa-download"></i>
                                        </a>
                                        <a href="settings.php?delete_backup=<?php echo urlencode(basename($file)); ?>"
                                            class="btn btn-sm btn-outline-danger"
                                            onclick="return confirm('Are you sure you want to delete this backup?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <hr class="my-4">

                <!-- System Logs -->
                <h5 class="mb-3">System Logs</h5>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    System logs are stored in the server's error log. Contact your server administrator for access.
                </div>
            </div>
        </div>
    </main>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Select2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        // Initialize Select2 for timezone select
        $(document).ready(function() {
            $('.select2-timezone').select2({
                theme: 'bootstrap-5',
                placeholder: 'Select timezone',
                allowClear: true
            });

            // Tab persistence
            const hash = window.location.hash;
            if (hash) {
                const tabTrigger = document.querySelector(hash + '-tab');
                if (tabTrigger) {
                    new bootstrap.Tab(tabTrigger).show();
                }
            }

            // Update URL when tab changes
            const tabEls = document.querySelectorAll('button[data-bs-toggle="tab"]');
            tabEls.forEach(tabEl => {
                tabEl.addEventListener('shown.bs.tab', function(event) {
                    const target = event.target.getAttribute('data-bs-target');
                    window.location.hash = target;
                });
            });

            // Show password toggle for SMTP password
            const passwordField = document.getElementById('smtp_password');
            if (passwordField) {
                const passwordToggle = document.createElement('span');
                passwordToggle.className = 'input-group-text';
                passwordToggle.style.cursor = 'pointer';
                passwordToggle.innerHTML = '<i class="fas fa-eye"></i>';
                passwordToggle.onclick = function() {
                    const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
                    passwordField.setAttribute('type', type);
                    this.innerHTML = type === 'password' ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
                };

                // Wrap in input group if not already
                if (!passwordField.parentNode.classList.contains('input-group')) {
                    const wrapper = document.createElement('div');
                    wrapper.className = 'input-group';
                    passwordField.parentNode.insertBefore(wrapper, passwordField);
                    wrapper.appendChild(passwordField);
                    wrapper.appendChild(passwordToggle);
                }
            }
        });
    </script>
</body>

</html>