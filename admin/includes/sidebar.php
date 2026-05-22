<?php
// admin/includes/sidebar.php
?>
<div class="position-sticky pt-3">
    <ul class="nav flex-column">
        <li class="nav-item">
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : 'text-white'; ?>" href="index.php">
                <i class="fas fa-tachometer-alt me-2"></i>
                Dashboard
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'products.php' ? 'active' : 'text-white'; ?>" href="products.php">
                <i class="fas fa-box me-2"></i>
                Products
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : 'text-white'; ?>" href="users.php">
                <i class="fas fa-users me-2"></i>
                Users
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'vendors.php' ? 'active' : 'text-white'; ?>" href="vendors.php">
                <i class="fas fa-store me-2"></i>
                Vendors
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'orders.php' ? 'active' : 'text-white'; ?>" href="orders.php">
                <i class="fas fa-shopping-cart me-2"></i>
                Orders
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'categories.php' ? 'active' : 'text-white'; ?>" href="categories.php">
                <i class="fas fa-tags me-2"></i>
                Categories
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : 'text-white'; ?>" href="reports.php">
                <i class="fas fa-chart-bar me-2"></i>
                Reports
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : 'text-white'; ?>" href="settings.php">
                <i class="fas fa-cog me-2"></i>
                Settings
            </a>
        </li>
    </ul>

    <hr class="bg-light">

    <div class="dropdown mt-4">
        <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle"
            data-bs-toggle="dropdown">
            <img src="<?php echo get_user_avatar($_SESSION['user_id']); ?>"
                alt="Admin" width="32" height="32" class="rounded-circle me-2">
            <strong><?php echo htmlspecialchars($_SESSION['first_name']); ?></strong>
        </a>
        <ul class="dropdown-menu dropdown-menu-dark text-small shadow">
            <li><a class="dropdown-item" href="../buyer/profile.php">My Profile</a></li>
            <li>
                <hr class="dropdown-divider">
            </li>
            <li><a class="dropdown-item" href="../public/logout.php">Sign out</a></li>
        </ul>
    </div>
</div>