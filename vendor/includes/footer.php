<footer class="footer mt-auto py-3 bg-dark text-white">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-6">
                <span class="text-muted">
                    &copy; <?php echo date('Y'); ?> Snaker-Mart - Vendor Panel
                </span>
            </div>
            <div class="col-md-6 text-md-end">
                <?php if (isset($vendor_data) && !empty($vendor_data)): ?>
                    <span class="text-muted">
                        <i class="fas fa-store me-1"></i>
                        <?php
                        $store_name = isset($vendor_data['store_name']) && !empty($vendor_data['store_name'])
                            ? $vendor_data['store_name']
                            : (isset($vendor_data['first_name']) ? $vendor_data['first_name'] . "'s Store" : 'Vendor Store');
                        echo htmlspecialchars($store_name);
                        ?>
                    </span>
                    <span class="mx-2">|</span>
                <?php endif; ?>
                <a href="../public/index.php" class="text-white text-decoration-none me-3">
                    <i class="fas fa-external-link-alt me-1"></i>View Store
                </a>
                <a href="profile.php" class="text-white text-decoration-none">
                    <i class="fas fa-cog me-1"></i>Settings
                </a>
            </div>
        </div>
        <div class="row mt-2">
            <div class="col-12">
                <small class="text-muted">
                    <i class="fas fa-info-circle me-1"></i>
                    Need help? Contact admin
                </small>
            </div>
        </div>
    </div>
</footer>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- Custom JS -->
<script src="../assets/js/vendor.js"></script>

<script>
    // Simple JavaScript for vendor dashboard
    document.addEventListener('DOMContentLoaded', function() {
        // Toggle sidebar on mobile
        const sidebarToggler = document.querySelector('[data-bs-target="#vendorSidebar"]');
        if (sidebarToggler) {
            sidebarToggler.addEventListener('click', function() {
                const sidebar = document.getElementById('vendorSidebar');
                sidebar.classList.toggle('show');
            });
        }

        // Low stock warning if applicable
        const lowStockElement = document.querySelector('[data-low-stock]');
        if (lowStockElement) {
            const lowStockCount = parseInt(lowStockElement.getAttribute('data-low-stock'));
            if (lowStockCount > 0) {
                showLowStockWarning(lowStockCount);
            }
        }
    });

    function showLowStockWarning(count) {
        // Only show if not already showing
        if (document.querySelector('.low-stock-alert')) return;

        const alertHTML = `
        <div class="alert alert-warning alert-dismissible fade show position-fixed bottom-0 end-0 m-3 low-stock-alert" 
             style="z-index: 1050; max-width: 350px;">
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            <h6><i class="fas fa-exclamation-triangle me-2"></i>Low Stock Alert</h6>
            <p class="mb-0">You have ${count} product(s) with low stock.</p>
        </div>
    `;
        document.body.insertAdjacentHTML('beforeend', alertHTML);
    }
</script>