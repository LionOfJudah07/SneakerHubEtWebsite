<?php
// admin/user-view.php

// Include config first to get database constants and session started
require_once __DIR__ . '/../config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

require_once '../classes/Database.php';

// Initialize Database
$database = new Database();

// Get user ID from query parameter
$user_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($user_id <= 0) {
    header('Location: users.php');
    exit();
}

// Get user details
$database->query("SELECT * FROM users WHERE id = :id");
$database->bind(':id', $user_id);
$user = $database->single();

if (!$user) {
    header('Location: users.php');
    exit();
}

// Get user addresses - FIXED TABLE NAME
$database->query("SELECT * FROM user_addresses WHERE user_id = :user_id ORDER BY is_default DESC, created_at DESC");
$database->bind(':user_id', $user_id);
$addresses = $database->resultSet();

// Get user orders
$database->query("SELECT * FROM orders WHERE buyer_id = :user_id ORDER BY created_at DESC");
$database->bind(':user_id', $user_id);
$orders = $database->resultSet();

// Get cart items count
$database->query("SELECT COUNT(*) as cart_count FROM cart_items WHERE user_id = :user_id");
$database->bind(':user_id', $user_id);
$cart_result = $database->single();
$cart_count = $cart_result ? $cart_result['cart_count'] : 0;

// Get wishlist items count
$database->query("SELECT COUNT(*) as wishlist_count FROM wishlist_items WHERE user_id = :user_id");
$database->bind(':user_id', $user_id);
$wishlist_result = $database->single();
$wishlist_count = $wishlist_result ? $wishlist_result['wishlist_count'] : 0;

// Handle address deletion
if (isset($_POST['delete_address'])) {
    $address_id = intval($_POST['address_id']);
    $database->query("DELETE FROM user_addresses WHERE id = :id AND user_id = :user_id");
    $database->bind(':id', $address_id);
    $database->bind(':user_id', $user_id);
    $database->execute();

    header("Location: user-view.php?id=" . $user_id);
    exit();
}

// Handle status update
if (isset($_POST['update_status'])) {
    $new_status = $_POST['status'];
    $database->query("UPDATE users SET status = :status, updated_at = CURRENT_TIMESTAMP WHERE id = :id");
    $database->bind(':status', $new_status);
    $database->bind(':id', $user_id);
    $database->execute();

    header("Location: user-view.php?id=" . $user_id);
    exit();
}

// Handle user type update
if (isset($_POST['update_user_type'])) {
    $new_type = $_POST['user_type'];
    $database->query("UPDATE users SET user_type = :user_type, updated_at = CURRENT_TIMESTAMP WHERE id = :id");
    $database->bind(':user_type', $new_type);
    $database->bind(':id', $user_id);
    $database->execute();

    header("Location: user-view.php?id=" . $user_id);
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Details - <?php echo SITE_NAME; ?> Admin</title>
    <link rel="stylesheet" href="../css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .user-details-container {
            padding: 20px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .user-header {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .user-basic-info {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 20px;
        }

        .user-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #007bff;
        }

        .user-stats {
            display: flex;
            gap: 30px;
            margin-top: 20px;
        }

        .stat-box {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            flex: 1;
        }

        .stat-number {
            font-size: 24px;
            font-weight: bold;
            color: #007bff;
        }

        .stat-label {
            color: #6c757d;
            font-size: 14px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .info-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .info-card h3 {
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #007bff;
            color: #333;
        }

        .address-item {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 10px;
            border-left: 4px solid #007bff;
        }

        .default-badge {
            background: #28a745;
            color: white;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 12px;
            margin-left: 10px;
        }

        .order-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        .order-table th,
        .order-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
        }

        .order-table th {
            background: #f8f9fa;
            font-weight: 600;
        }

        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-active {
            background: #d4edda;
            color: #155724;
        }

        .status-suspended {
            background: #f8d7da;
            color: #721c24;
        }

        .status-buyer {
            background: #cce5ff;
            color: #004085;
        }

        .status-vendor {
            background: #d1ecf1;
            color: #0c5460;
        }

        .status-admin {
            background: #f8d7da;
            color: #721c24;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s;
        }

        .btn-edit {
            background: #007bff;
            color: white;
        }

        .btn-delete {
            background: #dc3545;
            color: white;
        }

        .btn-back {
            background: #6c757d;
            color: white;
        }

        .btn:hover {
            opacity: 0.9;
            transform: translateY(-2px);
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }

        .form-control {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            font-size: 14px;
        }

        .text-success {
            color: #28a745;
        }

        .text-danger {
            color: #dc3545;
        }

        .text-warning {
            color: #ffc107;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .tab-container {
            margin-top: 20px;
        }

        .tabs {
            display: flex;
            border-bottom: 2px solid #dee2e6;
            margin-bottom: 20px;
        }

        .tab {
            padding: 10px 20px;
            cursor: pointer;
            border: none;
            background: none;
            font-weight: 500;
            color: #6c757d;
            transition: all 0.3s;
        }

        .tab.active {
            color: #007bff;
            border-bottom: 3px solid #007bff;
            margin-bottom: -2px;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }
    </style>
</head>

<body>
    <?php
    // Use the function from config.php to check admin access
    require_admin();

    // Include admin header if it exists
    if (file_exists('admin-header.php')) {
        include 'admin-header.php';
    } else {
        // Simple header if admin-header.php doesn't exist
        echo '<div style="background: #007bff; color: white; padding: 15px; display: flex; justify-content: space-between; align-items: center;">
                <h2 style="margin: 0;">' . SITE_NAME . ' Admin</h2>
                <div>
                    <a href="users.php" style="color: white; text-decoration: none; margin-right: 15px;">Users</a>
                    <a href="../logout.php" style="color: white; text-decoration: none;">Logout</a>
                </div>
              </div>';
    }
    ?>

    <div class="user-details-container">
        <div class="user-header">
            <div class="section-header">
                <h1><i class="fas fa-user-circle"></i> User Details</h1>
                <a href="users.php" class="btn btn-back"><i class="fas fa-arrow-left"></i> Back to Users</a>
            </div>

            <div class="user-basic-info">
                <img src="<?php echo !empty($user['profile_image']) ? '../' . htmlspecialchars($user['profile_image']) : get_user_avatar($user_id); ?>"
                    alt="User Avatar" class="user-avatar" onerror="this.src='https://via.placeholder.com/100?text=User'">
                <div>
                    <h2><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h2>
                    <p><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($user['email']); ?></p>
                    <p><i class="fas fa-phone"></i> <?php echo htmlspecialchars($user['phone'] ?: 'Not provided'); ?></p>
                    <p><i class="fas fa-user-tag"></i>
                        <span class="status-badge status-<?php echo strtolower($user['user_type']); ?>">
                            <?php echo ucfirst($user['user_type']); ?>
                        </span>
                        <span class="status-badge status-<?php echo strtolower($user['status']); ?>">
                            <?php echo ucfirst($user['status']); ?>
                        </span>
                    </p>
                    <p><i class="fas fa-calendar"></i> Member since: <?php echo format_date($user['created_at'], 'F j, Y'); ?></p>
                </div>
            </div>

            <div class="user-stats">
                <div class="stat-box">
                    <div class="stat-number"><?php echo count($orders); ?></div>
                    <div class="stat-label">Total Orders</div>
                </div>
                <div class="stat-box">
                    <div class="stat-number"><?php echo $cart_count; ?></div>
                    <div class="stat-label">Cart Items</div>
                </div>
                <div class="stat-box">
                    <div class="stat-number"><?php echo $wishlist_count; ?></div>
                    <div class="stat-label">Wishlist Items</div>
                </div>
                <div class="stat-box">
                    <div class="stat-number">
                        <?php
                        $total_spent = 0;
                        foreach ($orders as $order) {
                            if ($order['payment_status'] === 'paid' || $order['payment_status'] === 'completed') {
                                $total_spent += $order['total_amount'];
                            }
                        }
                        echo format_price($total_spent);
                        ?>
                    </div>
                    <div class="stat-label">Total Spent</div>
                </div>
            </div>
        </div>

        <!-- Tabs Navigation -->
        <div class="tab-container">
            <div class="tabs">
                <button class="tab active" onclick="switchTab('account')">Account</button>
                <button class="tab" onclick="switchTab('addresses')">Addresses (<?php echo count($addresses); ?>)</button>
                <button class="tab" onclick="switchTab('orders')">Orders (<?php echo count($orders); ?>)</button>
                <?php if ($user['user_type'] === 'vendor'): ?>
                    <button class="tab" onclick="switchTab('vendor')">Vendor Info</button>
                <?php endif; ?>
            </div>

            <!-- Account Tab -->
            <div id="account-tab" class="tab-content active">
                <div class="info-card">
                    <h3><i class="fas fa-info-circle"></i> Account Information</h3>
                    <div class="form-group">
                        <label>Email:</label>
                        <p><?php echo htmlspecialchars($user['email']); ?></p>
                    </div>
                    <div class="form-group">
                        <label>Email Verification:</label>
                        <span class="<?php echo $user['email_verified'] ? 'text-success' : 'text-danger'; ?>">
                            <i class="fas <?php echo $user['email_verified'] ? 'fa-check-circle' : 'fa-times-circle'; ?>"></i>
                            <?php echo $user['email_verified'] ? 'Verified' : 'Not Verified'; ?>
                        </span>
                    </div>
                    <div class="form-group">
                        <label>Phone Verification:</label>
                        <span class="<?php echo $user['phone_verified'] ? 'text-success' : 'text-danger'; ?>">
                            <i class="fas <?php echo $user['phone_verified'] ? 'fa-check-circle' : 'fa-times-circle'; ?>"></i>
                            <?php echo $user['phone_verified'] ? 'Verified' : 'Not Verified'; ?>
                        </span>
                    </div>
                    <div class="form-group">
                        <label>Last Login:</label>
                        <span><?php echo $user['last_login'] ? format_date($user['last_login'], 'M j, Y H:i') : 'Never'; ?></span>
                    </div>
                    <div class="form-group">
                        <label>Created At:</label>
                        <span><?php echo format_date($user['created_at'], 'M j, Y H:i'); ?></span>
                    </div>
                    <div class="form-group">
                        <label>Last Updated:</label>
                        <span><?php echo format_date($user['updated_at'], 'M j, Y H:i'); ?></span>
                    </div>

                    <hr>

                    <form method="POST" class="form-group">
                        <label><strong>Update User Type:</strong></label>
                        <select name="user_type" class="form-control" style="margin-bottom: 10px; max-width: 200px;">
                            <option value="buyer" <?php echo $user['user_type'] === 'buyer' ? 'selected' : ''; ?>>Buyer</option>
                            <option value="vendor" <?php echo $user['user_type'] === 'vendor' ? 'selected' : ''; ?>>Vendor</option>
                            <option value="admin" <?php echo $user['user_type'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                        </select>
                        <button type="submit" name="update_user_type" class="btn btn-edit">
                            <i class="fas fa-save"></i> Update Type
                        </button>
                    </form>

                    <form method="POST" class="form-group">
                        <label><strong>Update Status:</strong></label>
                        <select name="status" class="form-control" style="margin-bottom: 10px; max-width: 200px;">
                            <option value="active" <?php echo $user['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo $user['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                            <option value="suspended" <?php echo $user['status'] === 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                        </select>
                        <button type="submit" name="update_status" class="btn btn-edit">
                            <i class="fas fa-save"></i> Update Status
                        </button>
                    </form>
                </div>
            </div>

            <!-- Addresses Tab -->
            <div id="addresses-tab" class="tab-content">
                <div class="info-card">
                    <div class="section-header">
                        <h3><i class="fas fa-map-marker-alt"></i> Addresses</h3>
                        <span class="status-badge"><?php echo count($addresses); ?> addresses</span>
                    </div>
                    <?php if (!empty($addresses)): ?>
                        <div style="display: grid; gap: 15px;">
                            <?php foreach ($addresses as $address): ?>
                                <div class="address-item">
                                    <div style="display: flex; justify-content: space-between; align-items: start;">
                                        <div style="flex: 1;">
                                            <div style="display: flex; align-items: center; margin-bottom: 5px;">
                                                <strong><?php echo htmlspecialchars($address['full_name']); ?></strong>
                                                <?php if ($address['is_default']): ?>
                                                    <span class="default-badge">Default</span>
                                                <?php endif; ?>
                                                <span style="margin-left: auto; font-size: 12px; color: #6c757d;">
                                                    <?php echo ucfirst($address['type']); ?>
                                                </span>
                                            </div>
                                            <p style="margin: 5px 0;"><i class="fas fa-phone"></i> <?php echo htmlspecialchars($address['phone']); ?></p>
                                            <p style="margin: 5px 0;"><?php echo htmlspecialchars($address['address_line1']); ?></p>
                                            <?php if (!empty($address['address_line2'])): ?>
                                                <p style="margin: 5px 0;"><?php echo htmlspecialchars($address['address_line2']); ?></p>
                                            <?php endif; ?>
                                            <p style="margin: 5px 0; color: #6c757d;">
                                                <?php echo htmlspecialchars($address['city'] . ', ' . $address['region'] . ' ' . $address['postal_code']); ?>
                                            </p>
                                            <small style="color: #6c757d;">
                                                Created: <?php echo format_date($address['created_at'], 'M j, Y'); ?>
                                            </small>
                                        </div>
                                        <form method="POST" style="margin-left: 15px;">
                                            <input type="hidden" name="address_id" value="<?php echo $address['id']; ?>">
                                            <button type="submit" name="delete_address" class="btn btn-delete"
                                                onclick="return confirm('Are you sure you want to delete this address?')"
                                                title="Delete Address">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p style="text-align: center; color: #6c757d; padding: 20px;">
                            <i class="fas fa-map-marker-alt fa-2x" style="margin-bottom: 10px; display: block; opacity: 0.5;"></i>
                            No addresses found for this user.
                        </p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Orders Tab -->
            <div id="orders-tab" class="tab-content">
                <div class="info-card">
                    <div class="section-header">
                        <h3><i class="fas fa-shopping-bag"></i> Orders</h3>
                        <span class="status-badge"><?php echo count($orders); ?> orders</span>
                    </div>
                    <?php if (!empty($orders)): ?>
                        <div style="overflow-x: auto;">
                            <table class="order-table">
                                <thead>
                                    <tr>
                                        <th>Order #</th>
                                        <th>Date</th>
                                        <th>Items</th>
                                        <th>Total</th>
                                        <th>Status</th>
                                        <th>Payment</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($orders as $order): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($order['order_number']); ?></strong></td>
                                            <td><?php echo format_date($order['created_at'], 'M j, Y'); ?></td>
                                            <td>
                                                <?php
                                                // Get item count for this order
                                                $database->query("SELECT COUNT(*) as item_count FROM order_items WHERE order_id = :order_id");
                                                $database->bind(':order_id', $order['id']);
                                                $item_result = $database->single();
                                                echo $item_result ? $item_result['item_count'] : 0;
                                                ?> items
                                            </td>
                                            <td><strong><?php echo format_price($order['total_amount']); ?></strong></td>
                                            <td>
                                                <span class="status-badge status-<?php echo strtolower($order['status']); ?>">
                                                    <?php echo ucfirst($order['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="status-badge status-<?php echo strtolower($order['payment_status']); ?>">
                                                    <?php echo ucfirst($order['payment_status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="order-view.php?id=<?php echo $order['id']; ?>" class="btn btn-edit" style="padding: 5px 10px;">
                                                    <i class="fas fa-eye"></i> View
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p style="text-align: center; color: #6c757d; padding: 20px;">
                            <i class="fas fa-shopping-bag fa-2x" style="margin-bottom: 10px; display: block; opacity: 0.5;"></i>
                            No orders found for this user.
                        </p>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($user['user_type'] === 'vendor'): ?>
                <?php
                // Get vendor details
                $database->query("SELECT * FROM vendors WHERE user_id = :user_id");
                $database->bind(':user_id', $user_id);
                $vendor = $database->single();

                // Get vendor products
                $database->query("SELECT COUNT(*) as product_count FROM products WHERE vendor_id = :user_id");
                $database->bind(':user_id', $user_id);
                $product_result = $database->single();
                $product_count = $product_result ? $product_result['product_count'] : 0;

                // Get vendor earnings
                $database->query("SELECT SUM(earnings) as total_earnings FROM vendor_earnings WHERE vendor_id = :user_id AND status = 'completed'");
                $database->bind(':user_id', $user_id);
                $earnings_result = $database->single();
                $total_earnings = $earnings_result ? $earnings_result['total_earnings'] : 0;

                // Get pending earnings
                $database->query("SELECT SUM(earnings) as pending_earnings FROM vendor_earnings WHERE vendor_id = :user_id AND status = 'pending'");
                $database->bind(':user_id', $user_id);
                $pending_result = $database->single();
                $pending_earnings = $pending_result ? $pending_result['pending_earnings'] : 0;
                ?>

                <!-- Vendor Tab -->
                <div id="vendor-tab" class="tab-content">
                    <div class="info-card">
                        <h3><i class="fas fa-store"></i> Vendor Information</h3>
                        <?php if ($vendor): ?>
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 20px;">
                                <div>
                                    <label>Store Name:</label>
                                    <p style="font-weight: 500; font-size: 18px;"><?php echo htmlspecialchars($vendor['store_name']); ?></p>
                                </div>
                                <div>
                                    <label>Rating:</label>
                                    <p>
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="fas fa-star <?php echo $i <= round($vendor['rating']) ? 'text-warning' : 'text-muted'; ?>"></i>
                                        <?php endfor; ?>
                                        <span style="margin-left: 5px;">(<?php echo number_format($vendor['rating'], 1); ?>)</span>
                                    </p>
                                </div>
                            </div>

                            <div class="user-stats" style="margin: 20px 0;">
                                <div class="stat-box">
                                    <div class="stat-number"><?php echo $product_count; ?></div>
                                    <div class="stat-label">Products</div>
                                </div>
                                <div class="stat-box">
                                    <div class="stat-number"><?php echo number_format($vendor['total_sales']); ?></div>
                                    <div class="stat-label">Total Sales</div>
                                </div>
                                <div class="stat-box">
                                    <div class="stat-number"><?php echo format_price($total_earnings); ?></div>
                                    <div class="stat-label">Total Earnings</div>
                                </div>
                                <div class="stat-box">
                                    <div class="stat-number"><?php echo format_price($pending_earnings); ?></div>
                                    <div class="stat-label">Pending Earnings</div>
                                </div>
                            </div>

                            <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-top: 20px;">
                                <h4>Business Details</h4>
                                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-top: 10px;">
                                    <div>
                                        <label>Business License:</label>
                                        <p><?php echo htmlspecialchars($vendor['business_license'] ?: 'Not provided'); ?></p>
                                    </div>
                                    <div>
                                        <label>Tax ID:</label>
                                        <p><?php echo htmlspecialchars($vendor['tax_id'] ?: 'Not provided'); ?></p>
                                    </div>
                                    <div>
                                        <label>Bank Account:</label>
                                        <p><?php echo htmlspecialchars($vendor['bank_account'] ?: 'Not provided'); ?></p>
                                    </div>
                                    <div>
                                        <label>Bank Name:</label>
                                        <p><?php echo htmlspecialchars($vendor['bank_name'] ?: 'Not provided'); ?></p>
                                    </div>
                                </div>
                            </div>

                            <?php if (!empty($vendor['store_description'])): ?>
                                <div style="margin-top: 20px;">
                                    <label>Store Description:</label>
                                    <p style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-top: 5px;">
                                        <?php echo nl2br(htmlspecialchars($vendor['store_description'])); ?>
                                    </p>
                                </div>
                            <?php endif; ?>

                            <div style="margin-top: 20px; color: #6c757d; font-size: 14px;">
                                <p><i class="fas fa-calendar"></i> Vendor since: <?php echo format_date($vendor['created_at'], 'F j, Y'); ?></p>
                                <p><i class="fas fa-history"></i> Last updated: <?php echo format_date($vendor['updated_at'], 'F j, Y H:i'); ?></p>
                            </div>
                        <?php else: ?>
                            <p style="text-align: center; color: #6c757d; padding: 20px;">
                                <i class="fas fa-store fa-2x" style="margin-bottom: 10px; display: block; opacity: 0.5;"></i>
                                No vendor profile found for this user.
                            </p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function switchTab(tabName) {
            // Hide all tab contents
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });

            // Remove active class from all tabs
            document.querySelectorAll('.tab').forEach(tab => {
                tab.classList.remove('active');
            });

            // Show selected tab content
            document.getElementById(tabName + '-tab').classList.add('active');

            // Activate selected tab
            event.target.classList.add('active');
        }

        // Initialize first tab as active
        document.addEventListener('DOMContentLoaded', function() {
            switchTab('account');
        });
    </script>
</body>

</html>