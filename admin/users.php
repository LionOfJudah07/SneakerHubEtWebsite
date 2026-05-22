<?php
require_once '../config.php';

// Require admin login
require_admin();

$page_title = 'Users Management - ' . SITE_NAME;

// Handle user actions
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_status'])) {
        // Update user status
        $user_id = intval($_POST['user_id']);
        $status = sanitize_input($_POST['status']);

        try {
            $db = new Database();
            $db->query("UPDATE users SET status = :status, updated_at = NOW() WHERE id = :id");
            $db->bind(':status', $status);
            $db->bind(':id', $user_id);
            $db->execute();

            $success = 'User status updated successfully!';
        } catch (Exception $e) {
            $errors[] = 'Failed to update user status: ' . $e->getMessage();
        }
    } elseif (isset($_POST['update_role'])) {
        // Update user role
        $user_id = intval($_POST['user_id']);
        $user_type = sanitize_input($_POST['user_type']);

        try {
            $db = new Database();
            $db->query("UPDATE users SET user_type = :user_type, updated_at = NOW() WHERE id = :id");
            $db->bind(':user_type', $user_type);
            $db->bind(':id', $user_id);
            $db->execute();

            $success = 'User role updated successfully!';
        } catch (Exception $e) {
            $errors[] = 'Failed to update user role: ' . $e->getMessage();
        }
    }
}

// Handle delete user
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $user_id = intval($_GET['delete']);

    try {
        $db = new Database();

        // Don't allow deleting yourself
        if ($user_id == $_SESSION['user_id']) {
            $_SESSION['error'] = 'You cannot delete your own account!';
            redirect('users.php');
        }

        // Get user info
        $db->query("SELECT email FROM users WHERE id = :id");
        $db->bind(':id', $user_id);
        $user = $db->single();

        if ($user) {
            // Soft delete - just mark as inactive
            $db->query("UPDATE users SET status = 'inactive', updated_at = NOW() WHERE id = :id");
            $db->bind(':id', $user_id);
            $db->execute();

            $_SESSION['success'] = 'User deactivated successfully!';
        }
    } catch (Exception $e) {
        $_SESSION['error'] = 'Failed to delete user: ' . $e->getMessage();
    }

    redirect('users.php');
}

// Get filter parameters
$status_filter = $_GET['status'] ?? '';
$type_filter = $_GET['type'] ?? '';
$search_query = $_GET['search'] ?? '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 20;

// Initialize Database
$db = new Database();

// Build base query for counting
$count_query = "SELECT COUNT(*) as total FROM users WHERE 1=1";
$count_params = [];

// Build filtered query for fetching data
$query = "SELECT * FROM users WHERE 1=1";
$params = [];

// Add filters
if (!empty($status_filter)) {
    $query .= " AND status = :status";
    $count_query .= " AND status = :status";
    $params[':status'] = $status_filter;
    $count_params[':status'] = $status_filter;
}

if (!empty($type_filter)) {
    $query .= " AND user_type = :user_type";
    $count_query .= " AND user_type = :user_type";
    $params[':user_type'] = $type_filter;
    $count_params[':user_type'] = $type_filter;
}

if (!empty($search_query)) {
    $query .= " AND (first_name ILIKE :search OR last_name ILIKE :search OR email ILIKE :search OR phone ILIKE :search)";
    $count_query .= " AND (first_name ILIKE :search OR last_name ILIKE :search OR email ILIKE :search OR phone ILIKE :search)";
    $params[':search'] = '%' . $search_query . '%';
    $count_params[':search'] = '%' . $search_query . '%';
}

// Get total count for pagination
$db->query($count_query);
foreach ($count_params as $key => $value) {
    $db->bind($key, $value);
}
$total_count_result = $db->single();
$total_count = $total_count_result ? $total_count_result['total'] : 0;

// Setup pagination
$total_pages = ceil($total_count / $per_page);
$offset = ($page - 1) * $per_page;

// Add sorting and pagination to main query
$query .= " ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
$params[':limit'] = $per_page;
$params[':offset'] = $offset;

// Get users
$db->query($query);
foreach ($params as $key => $value) {
    if ($key === ':limit' || $key === ':offset') {
        $db->bind($key, $value, PDO::PARAM_INT);
    } else {
        $db->bind($key, $value);
    }
}

$users = $db->resultSet();

$cart_count = get_cart_count();

// Helper functions if they don't exist
if (!function_exists('get_user_avatar')) {
    function get_user_avatar($user_id, $profile_image)
    {
        if (!empty($profile_image) && file_exists('../' . $profile_image)) {
            return '../' . $profile_image;
        }
        return '../assets/images/users/default-avatar.png';
    }
}

if (!function_exists('format_date')) {
    function format_date($date, $format = 'M d, Y')
    {
        if (empty($date)) {
            return 'Never';
        }
        return date($format, strtotime($date));
    }
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
    <!-- Custom CSS -->
    <link href="../assets/css/style.css" rel="stylesheet">
    <link href="../assets/css/bootstrap-custom.css" rel="stylesheet">
    <style>
        .sidebar {
            min-height: 100vh;
            background-color: #343a40;
            padding-top: 20px;
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

        .main-content {
            padding-top: 20px;
            padding-left: 0;
        }

        @media (min-width: 768px) {
            .main-content {
                padding-left: 240px;
            }

            .sidebar {
                position: fixed;
                top: 0;
                left: 0;
                width: 240px;
                height: 100vh;
                overflow-y: auto;
                z-index: 1000;
            }
        }

        .table th {
            font-weight: 600;
            border-top: none;
        }

        .img-avatar {
            width: 40px;
            height: 40px;
            object-fit: cover;
            border-radius: 50%;
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
                    <a class="nav-link active" href="users.php">
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
                    <a class="nav-link" href="settings.php">
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
        <div class="container-fluid px-4 py-4">
            <!-- Page Header -->
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Users Management</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="user-add.php" class="btn btn-sm btn-primary">
                        <i class="fas fa-plus me-2"></i>Add New User
                    </a>
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

            <!-- Filter Section -->
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="">All Status</option>
                                <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">User Type</label>
                            <select name="type" class="form-select">
                                <option value="">All Types</option>
                                <option value="buyer" <?php echo $type_filter === 'buyer' ? 'selected' : ''; ?>>Buyer</option>
                                <option value="vendor" <?php echo $type_filter === 'vendor' ? 'selected' : ''; ?>>Vendor</option>
                                <option value="admin" <?php echo $type_filter === 'admin' ? 'selected' : ''; ?>>Admin</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Search</label>
                            <div class="input-group">
                                <input type="text" name="search" class="form-control"
                                    placeholder="Search by name, email, or phone"
                                    value="<?php echo htmlspecialchars($search_query); ?>">
                                <button class="btn btn-outline-primary" type="submit">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <a href="users.php" class="btn btn-outline-secondary w-100">
                                <i class="fas fa-times me-2"></i>Clear
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Users Table -->
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Type</th>
                                    <th>Status</th>
                                    <th>Joined</th>
                                    <th>Last Login</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($users)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center py-4">
                                            <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                            <h4>No users found</h4>
                                            <p class="text-muted">Try adjusting your filters or add a new user.</p>
                                            <a href="user-add.php" class="btn btn-primary">
                                                <i class="fas fa-plus me-2"></i>Add New User
                                            </a>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($users as $user): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <img src="<?php echo get_user_avatar($user['id'], $user['profile_image'] ?? ''); ?>"
                                                        alt="<?php echo htmlspecialchars($user['first_name'] ?? ''); ?>"
                                                        class="img-avatar me-3"
                                                        onerror="this.src='../assets/images/users/default-avatar.png'">
                                                    <div>
                                                        <h6 class="mb-1"><?php echo htmlspecialchars(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')); ?></h6>
                                                        <p class="text-muted small mb-0">ID: <?php echo $user['id']; ?></p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <?php echo htmlspecialchars($user['email'] ?? ''); ?>
                                                <?php if (isset($user['email_verified']) && $user['email_verified']): ?>
                                                    <br><small class="text-success"><i class="fas fa-check-circle"></i> Verified</small>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($user['phone'] ?? ''); ?></td>
                                            <td>
                                                <?php
                                                $user_type = $user['user_type'] ?? 'buyer';
                                                $type_badges = [
                                                    'buyer' => 'badge bg-primary',
                                                    'vendor' => 'badge bg-success',
                                                    'admin' => 'badge bg-danger'
                                                ];
                                                $badge_class = $type_badges[$user_type] ?? 'badge bg-secondary';
                                                ?>
                                                <span class="<?php echo $badge_class; ?>">
                                                    <?php echo ucfirst($user_type); ?>
                                                </span>
                                                <?php if ($user_type === 'vendor' && !empty($user['store_name'])): ?>
                                                    <br><small class="text-muted"><?php echo htmlspecialchars($user['store_name']); ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php
                                                $status = $user['status'] ?? 'inactive';
                                                $status_badges = [
                                                    'active' => 'badge bg-success',
                                                    'inactive' => 'badge bg-secondary',
                                                    'pending' => 'badge bg-warning'
                                                ];
                                                $badge_class = $status_badges[$status] ?? 'badge bg-secondary';
                                                ?>
                                                <span class="<?php echo $badge_class; ?>">
                                                    <?php echo ucfirst($status); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php echo format_date($user['created_at'] ?? ''); ?>
                                            </td>
                                            <td>
                                                <?php echo format_date($user['last_login'] ?? ''); ?>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="user-view.php?id=<?php echo $user['id']; ?>"
                                                        class="btn btn-outline-primary" title="View">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="user-edit.php?id=<?php echo $user['id']; ?>"
                                                        class="btn btn-outline-warning" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <div class="dropdown">
                                                        <button class="btn btn-outline-secondary dropdown-toggle"
                                                            type="button" data-bs-toggle="dropdown">
                                                            <i class="fas fa-cog"></i>
                                                        </button>
                                                        <ul class="dropdown-menu">
                                                            <li>
                                                                <button type="button" class="dropdown-item"
                                                                    data-bs-toggle="modal"
                                                                    data-bs-target="#statusModal"
                                                                    data-user-id="<?php echo $user['id']; ?>"
                                                                    data-current-status="<?php echo $user['status'] ?? 'inactive'; ?>">
                                                                    <i class="fas fa-exchange-alt me-2"></i>Change Status
                                                                </button>
                                                            </li>
                                                            <li>
                                                                <button type="button" class="dropdown-item"
                                                                    data-bs-toggle="modal"
                                                                    data-bs-target="#roleModal"
                                                                    data-user-id="<?php echo $user['id']; ?>"
                                                                    data-current-role="<?php echo $user['user_type'] ?? 'buyer'; ?>">
                                                                    <i class="fas fa-user-tag me-2"></i>Change Role
                                                                </button>
                                                            </li>
                                                            <li>
                                                                <hr class="dropdown-divider">
                                                            </li>
                                                            <li>
                                                                <a class="dropdown-item text-danger"
                                                                    href="users.php?delete=<?php echo $user['id']; ?>"
                                                                    onclick="return confirm('Are you sure you want to deactivate this user?')">
                                                                    <i class="fas fa-user-slash me-2"></i>Deactivate
                                                                </a>
                                                            </li>
                                                        </ul>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <nav aria-label="Page navigation" class="mt-4">
                            <ul class="pagination justify-content-center">
                                <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?<?php
                                                                $query_params = $_GET;
                                                                $query_params['page'] = $page - 1;
                                                                echo http_build_query($query_params);
                                                                ?>">
                                        Previous
                                    </a>
                                </li>

                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                        <a class="page-link" href="?<?php
                                                                    $query_params = $_GET;
                                                                    $query_params['page'] = $i;
                                                                    echo http_build_query($query_params);
                                                                    ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>

                                <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?<?php
                                                                $query_params = $_GET;
                                                                $query_params['page'] = $page + 1;
                                                                echo http_build_query($query_params);
                                                                ?>">
                                        Next
                                    </a>
                                </li>
                            </ul>
                        </nav>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <!-- Status Change Modal -->
    <div class="modal fade" id="statusModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header bg-warning text-white">
                        <h5 class="modal-title">Change User Status</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="status_user_id" name="user_id">

                        <div class="mb-3">
                            <label for="status" class="form-label">Select New Status</label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                                <option value="pending">Pending</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_status" class="btn btn-warning">Update Status</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Role Change Modal -->
    <div class="modal fade" id="roleModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header bg-info text-white">
                        <h5 class="modal-title">Change User Role</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="role_user_id" name="user_id">

                        <div class="mb-3">
                            <label for="user_type" class="form-label">Select New Role</label>
                            <select class="form-select" id="user_type" name="user_type" required>
                                <option value="buyer">Buyer</option>
                                <option value="vendor">Vendor</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>

                        <div class="alert alert-info">
                            <p class="mb-0"><strong>Note:</strong></p>
                            <ul class="mb-0 small">
                                <li><strong>Buyer:</strong> Can purchase products</li>
                                <li><strong>Vendor:</strong> Can sell products (requires store setup)</li>
                                <li><strong>Admin:</strong> Full system access</li>
                            </ul>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_role" class="btn btn-info">Update Role</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Status modal handling
        const statusModal = document.getElementById('statusModal');
        if (statusModal) {
            statusModal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const userId = button.getAttribute('data-user-id');
                const currentStatus = button.getAttribute('data-current-status');

                document.getElementById('status_user_id').value = userId;
                document.getElementById('status').value = currentStatus;
            });
        }

        // Role modal handling
        const roleModal = document.getElementById('roleModal');
        if (roleModal) {
            roleModal.addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const userId = button.getAttribute('data-user-id');
                const currentRole = button.getAttribute('data-current-role');

                document.getElementById('role_user_id').value = userId;
                document.getElementById('user_type').value = currentRole;
            });
        }
    </script>
</body>

</html>