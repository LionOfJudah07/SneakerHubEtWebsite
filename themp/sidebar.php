<?php
// Check if session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in and is a vendor
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'vendor') {
    // If not vendor, redirect to login
    header('Location: ../../public/login.php');
    exit();
}

// Get vendor data from session if not passed
if (!isset($vendor_data)) {
    require_once __DIR__ . '/../../classes/User.php';
    $user = new User();
    $vendor_data = $user->getUserById($_SESSION['user_id']);
}

// Get current page for active link
$current_page = basename($_SERVER['PHP_SELF']);

// Set store name
$store_name = isset($vendor_data['store_name']) && !empty($vendor_data['store_name']) 
    ? $vendor_data['store_name'] 
    : $vendor_data['first_name'] . "'s Store";
?>

<!-- Vendor Sidebar -->
<div class="col-md-3 col-lg-2 d-md-block sidebar collapse" style="background: linear-gradient(135deg, #0b0f0e 0%, #1a1f1e 100%); min-height: calc(100vh - 76px);">
    <div class="position-sticky pt-3">
        <!-- Store Info -->
        <div class="text-center mb-4 px-3">
            <div class="mb-3">
                <?php if (!empty($vendor_data['profile_image'])): ?>
                    <img src="../../assets/uploads/<?php echo htmlspecialchars($vendor_data['profile_image']); ?>"
                        alt="Store Logo"
                        class="rounded-circle"
                        style="width: 80px; height: 80px; object-fit: cover; border: 3px solid #16a34a;">
                <?php else: ?>
                    <div class="rounded-circle d-inline-flex align-items-center justify-content-center"
                        style="width: 80px; height: 80px; background: linear-gradient(135deg, #16a34a, #0f7a35);">
                        <i class="fas fa-store fa-2x text-white"></i>
                    </div>
                <?php endif; ?>
            </div>
            <h5 class="mb-1 text-white"><?php echo htmlspecialchars($store_name); ?></h5>
            <small class="text-light">Vendor Store</small>
        </div>

        <!-- Navigation -->
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page == 'index.php' ? 'active' : 'text-white'; ?>" href="index.php">
                    <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link <?php echo $current_page == 'products.php' ? 'active' : 'text-white'; ?>" href="products.php">
                    <i class="fas fa-box me-2"></i>Products
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link <?php echo $current_page == 'orders.php' ? 'active' : 'text-white'; ?>" href="orders.php">
                    <i class="fas fa-shopping-cart me-2"></i>Orders
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link <?php echo $current_page == 'earnings.php' ? 'active' : 'text-white'; ?>" href="earnings.php">
                    <i class="fas fa-money-bill-wave me-2"></i>Earnings
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link <?php echo $current_page == 'profile.php' ? 'active' : 'text-white'; ?>" href="profile.php">
                    <i class="fas fa-user me-2"></i>Profile
                </a>
            </li>

            <li class="nav-item mt-4 pt-3 border-top border-secondary">
                <div class="nav-link text-white">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <?php if (!empty($vendor_data['profile_image'])): ?>
                                <img src="../../assets/uploads/<?php echo htmlspecialchars($vendor_data['profile_image']); ?>"
                                    alt="Profile"
                                    class="rounded-circle"
                                    style="width: 40px; height: 40px; object-fit: cover;">
                            <?php else: ?>
                                <div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center"
                                    style="width: 40px; height: 40px;">
                                    <i class="fas fa-user text-white"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="mb-0"><?php echo htmlspecialchars($vendor_data['first_name'] . ' ' . $vendor_data['last_name']); ?></h6>
                            <small class="text-light">Vendor</small>
                        </div>
                    </div>
                </div>
            </li>

            <li class="nav-item">
                <a class="nav-link text-white" href="../../logout.php">
                    <i class="fas fa-sign-out-alt me-2"></i>Logout
                </a>
            </li>
        </ul>

        <!-- Quick Stats -->
        <?php
        // Get some quick stats for the sidebar
        try {
            require_once __DIR__ . '/../../classes/Database.php';
            $db = new Database();
            
            // Total products
            $db->query("SELECT COUNT(*) as count FROM products WHERE vendor_id = :vendor_id");
            $db->bind(':vendor_id', $_SESSION['user_id']);
            $total_products = $db->single()['count'];
            
            // Pending orders
            $db->query("SELECT COUNT(DISTINCT o.id) as count 
                        FROM orders o 
                        JOIN order_items oi ON o.id = oi.order_id 
                        JOIN products p ON oi.product_id = p.id 
                        WHERE p.vendor_id = :vendor_id AND o.status = 'pending'");
            $db->bind(':vendor_id', $_SESSION['user_id']);
            $pending_orders = $db->single()['count'];
        } catch (Exception $e) {
            $total_products = 0;
            $pending_orders = 0;
        }
        ?>
        
        <div class="mt-4 pt-3 border-top border-secondary px-3">
            <h6 class="text-uppercase text-light mb-3">Quick Stats</h6>
            <div class="row text-center g-2">
                <div class="col-6">
                    <div class="bg-dark p-2 rounded border border-secondary">
                        <div class="h5 mb-1 text-primary"><?php echo $total_products; ?></div>
                        <small class="text-light">Products</small>
                    </div>
                </div>
                <div class="col-6">
                    <div class="bg-dark p-2 rounded border border-secondary">
                        <div class="h5 mb-1 text-warning"><?php echo $pending_orders; ?></div>
                        <small class="text-light">Pending Orders</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Mobile Sidebar Toggle Button -->
<button class="navbar-toggler d-md-none position-fixed" 
        type="button" 
        style="top: 15px; left: 15px; z-index: 1050; background: rgba(22, 163, 74, 0.9); border: none; border-radius: 5px;" 
        data-bs-toggle="collapse" 
        data-bs-target="#mobileSidebar">
    <span class="navbar-toggler-icon"></span>
</button>

<!-- Mobile Sidebar -->
<div class="collapse d-md-none" id="mobileSidebar" style="position: fixed; top: 0; left: 0; width: 280px; height: 100vh; z-index: 1040; background: linear-gradient(135deg, #0b0f0e 0%, #1a1f1e 100%); overflow-y: auto;">
    <div class="p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h5 class="text-white mb-0">
                <i class="fas fa-store me-2"></i>
                <?php echo htmlspecialchars($store_name); ?>
            </h5>
            <button type="button" class="btn-close btn-close-white" data-bs-toggle="collapse" data-bs-target="#mobileSidebar"></button>
        </div>

        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page == 'index.php' ? 'active' : 'text-white'; ?>" href="index.php">
                    <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page == 'products.php' ? 'active' : 'text-white'; ?>" href="products.php">
                    <i class="fas fa-box me-2"></i>Products
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page == 'orders.php' ? 'active' : 'text-white'; ?>" href="orders.php">
                    <i class="fas fa-shopping-cart me-2"></i>Orders
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page == 'earnings.php' ? 'active' : 'text-white'; ?>" href="earnings.php">
                    <i class="fas fa-money-bill-wave me-2"></i>Earnings
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $current_page == 'profile.php' ? 'active' : 'text-white'; ?>" href="profile.php">
                    <i class="fas fa-user me-2"></i>Profile
                </a>
            </li>
            <li class="nav-item mt-4 pt-3 border-top border-secondary">
                <a class="nav-link text-white" href="../../logout.php">
                    <i class="fas fa-sign-out-alt me-2"></i>Logout
                </a>
            </li>
        </ul>

        <!-- Quick Stats for Mobile -->
        <div class="mt-4 pt-3 border-top border-secondary">
            <h6 class="text-uppercase text-light mb-3">Quick Stats</h6>
            <div class="row text-center g-2">
                <div class="col-6">
                    <div class="bg-dark p-2 rounded border border-secondary">
                        <div class="h5 mb-1 text-primary"><?php echo $total_products; ?></div>
                        <small class="text-light">Products</small>
                    </div>
                </div>
                <div class="col-6">
                    <div class="bg-dark p-2 rounded border border-secondary">
                        <div class="h5 mb-1 text-warning"><?php echo $pending_orders; ?></div>
                        <small class="text-light">Pending</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Overlay for mobile sidebar -->
<div class="collapse d-md-none" id="mobileSidebar">
    <div class="offcanvas-backdrop fade show" data-bs-toggle="collapse" data-bs-target="#mobileSidebar"></div>
</div>

<style>
    .sidebar .nav-link {
        color: #adb5bd;
        padding: 0.75rem 1rem;
        border-radius: 0.5rem;
        margin: 0.25rem 0.5rem;
        transition: all 0.3s ease;
    }

    .sidebar .nav-link:hover {
        color: #fff;
        background: rgba(22, 163, 74, 0.1);
        transform: translateX(5px);
    }

    .sidebar .nav-link.active {
        color: #fff;
        background: linear-gradient(135deg, #16a34a, #0f7a35);
        box-shadow: 0 4px 15px rgba(22, 163, 74, 0.3);
    }

    /* Ensure main content doesn't overlap sidebar on desktop */
    @media (min-width: 768px) {
        .col-md-9, .col-lg-10 {
            margin-left: 16.666667% !important;
        }
    }

    /* Mobile sidebar backdrop */
    .offcanvas-backdrop {
        position: fixed;
        top: 0;
        left: 0;
        z-index: 1039;
        width: 100vw;
        height: 100vh;
        background-color: rgba(0, 0, 0, 0.5);
    }

    /* Mobile sidebar animation */
    #mobileSidebar.collapse:not(.show) {
        display: none;
    }

    #mobileSidebar.collapsing {
        position: fixed;
        height: 100vh;
        transition: all 0.3s ease;
    }

    #mobileSidebar.show {
        display: block;
    }
</style>

<script>
    // Close mobile sidebar when clicking on a link
    document.addEventListener('DOMContentLoaded', function() {
        const mobileSidebarLinks = document.querySelectorAll('#mobileSidebar .nav-link');
        mobileSidebarLinks.forEach(link => {
            link.addEventListener('click', function() {
                const mobileSidebar = document.getElementById('mobileSidebar');
                if (mobileSidebar.classList.contains('show')) {
                    const bsCollapse = new bootstrap.Collapse(mobileSidebar);
                    bsCollapse.hide();
                }
            });
        });

        // Close mobile sidebar when clicking outside
        document.addEventListener('click', function(event) {
            const mobileSidebar = document.getElementById('mobileSidebar');
            const toggleButton = document.querySelector('[data-bs-target="#mobileSidebar"]');
            
            if (mobileSidebar.classList.contains('show') && 
                !mobileSidebar.contains(event.target) && 
                !toggleButton.contains(event.target)) {
                const bsCollapse = new bootstrap.Collapse(mobileSidebar);
                bsCollapse.hide();
            }
        });
    });
</script>