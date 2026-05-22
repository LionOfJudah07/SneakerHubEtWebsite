<?php
// Buyer dashboard sidebar menu
?>
<div class="card shadow-sm">
    <div class="card-header bg-primary text-white">
        <h6 class="mb-0"><i class="fas fa-bars me-2"></i>Dashboard Menu</h6>
    </div>
    <div class="list-group list-group-flush">
        <a href="index.php" class="list-group-item list-group-item-action">
            <i class="fas fa-tachometer-alt me-2"></i>Dashboard
        </a>
        <a href="orders.php" class="list-group-item list-group-item-action active">
            <i class="fas fa-shopping-bag me-2"></i>My Orders
            <?php if (isset($pending_count) && $pending_count > 0): ?>
            <span class="badge bg-warning float-end"><?php echo $pending_count; ?></span>
            <?php endif; ?>
        </a>
        <a href="wishlist.php" class="list-group-item list-group-item-action">
            <i class="fas fa-heart me-2"></i>Wishlist
            <?php if (!empty($_SESSION['wishlist'])): ?>
            <span class="badge bg-danger float-end"><?php echo count($_SESSION['wishlist']); ?></span>
            <?php endif; ?>
        </a>
        <a href="profile.php" class="list-group-item list-group-item-action">
            <i class="fas fa-user me-2"></i>My Profile
        </a>
        <a href="addresses.php" class="list-group-item list-group-item-action">
            <i class="fas fa-address-book me-2"></i>Address Book
        </a>
        <a href="payment-methods.php" class="list-group-item list-group-item-action">
            <i class="fas fa-credit-card me-2"></i>Payment Methods
        </a>
        <a href="reviews.php" class="list-group-item list-group-item-action">
            <i class="fas fa-star me-2"></i>My Reviews
        </a>
        <a href="../public/logout.php" class="list-group-item list-group-item-action text-danger">
            <i class="fas fa-sign-out-alt me-2"></i>Logout
        </a>
    </div>
</div>