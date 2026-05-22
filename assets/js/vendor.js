// Vendor Dashboard JavaScript

document.addEventListener('DOMContentLoaded', function() {
    // Initialize vendor dashboard
    initializeVendorStats();
    initializeProductManagement();
    initializeOrderManagement();
    initializeEarningsDashboard();
    initializeStoreSettings();
    
    // Real-time updates
    startVendorUpdates();
});

// Initialize vendor stats
function initializeVendorStats() {
    // Load vendor statistics
    loadVendorStats();
    
    // Initialize vendor charts
    initializeVendorCharts();
}

// Load vendor statistics
function loadVendorStats() {
    fetch('/sneaker-commerce/api/vendor.php?action=stats')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateStatsDisplay(data.stats);
            }
        })
        .catch(error => console.error('Error loading vendor stats:', error));
}

// Update stats display
function updateStatsDisplay(stats) {
    // Update total sales
    const salesElement = document.querySelector('.stat-total-sales');
    if (salesElement) {
        salesElement.textContent = formatPrice(stats.total_sales);
    }
    
    // Update total orders
    const ordersElement = document.querySelector('.stat-total-orders');
    if (ordersElement) {
        ordersElement.textContent = stats.total_orders.toLocaleString();
    }
    
    // Update pending orders
    const pendingElement = document.querySelector('.stat-pending-orders');
    if (pendingElement) {
        pendingElement.textContent = stats.pending_orders.toLocaleString();
    }
    
    // Update total products
    const productsElement = document.querySelector('.stat-total-products');
    if (productsElement) {
        productsElement.textContent = stats.total_products.toLocaleString();
    }
    
    // Update low stock
    const lowStockElement = document.querySelector('.stat-low-stock');
    if (lowStockElement) {
        lowStockElement.textContent = stats.low_stock.toLocaleString();
    }
    
    // Update earnings
    const earningsElement = document.querySelector('.stat-total-earnings');
    if (earningsElement) {
        earningsElement.textContent = formatPrice(stats.total_earnings);
    }
    
    // Update pending withdrawal
    const pendingWithdrawalElement = document.querySelector('.stat-pending-withdrawal');
    if (pendingWithdrawalElement) {
        pendingWithdrawalElement.textContent = formatPrice(stats.pending_withdrawal);
    }
    
    // Update available balance
    const balanceElement = document.querySelector('.stat-available-balance');
    if (balanceElement) {
        balanceElement.textContent = formatPrice(stats.available_balance);
    }
}

// Initialize vendor charts
function initializeVendorCharts() {
    // Sales Chart
    const salesCtx = document.getElementById('salesChart');
    if (salesCtx) {
        const salesChart = new Chart(salesCtx, {
            type: 'line',
            data: {
                labels: [],
                datasets: [{
                    label: 'Sales',
                    data: [],
                    borderColor: '#4e73df',
                    backgroundColor: 'rgba(78, 115, 223, 0.05)',
                    borderWidth: 2,
                    pointRadius: 3,
                    pointBackgroundColor: '#4e73df',
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'ETB ' + value.toLocaleString();
                            }
                        }
                    }
                }
            }
        });
        
        // Load sales data
        loadSalesData(salesChart);
    }
    
    // Orders Chart
    const ordersCtx = document.getElementById('ordersChart');
    if (ordersCtx) {
        new Chart(ordersCtx, {
            type: 'bar',
            data: {
                labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                datasets: [{
                    label: 'Orders',
                    data: [12, 19, 8, 15, 22, 18, 14],
                    backgroundColor: '#1cc88a'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });
    }
    
    // Products Chart
    const productsCtx = document.getElementById('productsChart');
    if (productsCtx) {
        new Chart(productsCtx, {
            type: 'doughnut',
            data: {
                labels: ['Active', 'Pending', 'Out of Stock', 'Inactive'],
                datasets: [{
                    data: [45, 5, 3, 2],
                    backgroundColor: ['#4e73df', '#f6c23e', '#e74a3b', '#858796']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '70%'
            }
        });
    }
}

// Load sales data
function loadSalesData(chart) {
    fetch('/sneaker-commerce/api/vendor.php?action=sales_data&period=weekly')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                chart.data.labels = data.data.map(item => item.date);
                chart.data.datasets[0].data = data.data.map(item => item.sales);
                chart.update();
            }
        })
        .catch(error => console.error('Error loading sales data:', error));
}

// Initialize product management
function initializeProductManagement() {
    // Product form validation
    const productForm = document.getElementById('productForm');
    if (productForm) {
        productForm.addEventListener('submit', function(e) {
            if (!validateProductForm()) {
                e.preventDefault();
            }
        });
        
        // Image upload
        initializeImageUpload();
        
        // Price calculation
        initializePriceCalculation();
    }
    
    // Bulk product actions
    initializeBulkProductActions();
    
    // Quick edit
    initializeQuickEdit();
}

// Validate product form
function validateProductForm() {
    const requiredFields = ['name', 'sku', 'price', 'stock_quantity', 'category'];
    let isValid = true;
    
    requiredFields.forEach(field => {
        const input = document.querySelector(`[name="${field}"]`);
        if (input && !input.value.trim()) {
            showFormError(input, `Please enter ${field.replace('_', ' ')}`);
            isValid = false;
        }
    });
    
    // Validate price
    const priceInput = document.querySelector('[name="price"]');
    if (priceInput && (isNaN(priceInput.value) || parseFloat(priceInput.value) <= 0)) {
        showFormError(priceInput, 'Please enter a valid price');
        isValid = false;
    }
    
    // Validate stock quantity
    const stockInput = document.querySelector('[name="stock_quantity"]');
    if (stockInput && (isNaN(stockInput.value) || parseInt(stockInput.value) < 0)) {
        showFormError(stockInput, 'Please enter a valid stock quantity');
        isValid = false;
    }
    
    // Validate discount price
    const discountInput = document.querySelector('[name="discount_price"]');
    if (discountInput && discountInput.value) {
        const price = parseFloat(priceInput.value);
        const discount = parseFloat(discountInput.value);
        
        if (isNaN(discount) || discount <= 0 || discount >= price) {
            showFormError(discountInput, 'Discount price must be less than regular price');
            isValid = false;
        }
    }
    
    return isValid;
}

// Show form error
function showFormError(input, message) {
    const feedback = document.createElement('div');
    feedback.className = 'invalid-feedback';
    feedback.textContent = message;
    
    input.classList.add('is-invalid');
    input.parentNode.appendChild(feedback);
    
    // Remove error on input
    input.addEventListener('input', function() {
        this.classList.remove('is-invalid');
        if (feedback.parentNode) {
            feedback.parentNode.removeChild(feedback);
        }
    }, { once: true });
}

// Initialize image upload
function initializeImageUpload() {
    const imageInput = document.getElementById('product_images');
    const previewContainer = document.getElementById('imagePreview');
    
    if (imageInput && previewContainer) {
        imageInput.addEventListener('change', function(e) {
            previewContainer.innerHTML = '';
            
            Array.from(this.files).forEach(file => {
                if (!file.type.match('image.*')) {
                    showNotification('Please select only image files', 'error');
                    return;
                }
                
                if (file.size > 5 * 1024 * 1024) { // 5MB
                    showNotification('Image size should be less than 5MB', 'error');
                    return;
                }
                
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.createElement('div');
                    preview.className = 'image-preview-item';
                    preview.innerHTML = `
                        <img src="${e.target.result}" alt="Preview">
                        <button type="button" class="btn btn-sm btn-danger remove-image">
                            <i class="fas fa-times"></i>
                        </button>
                    `;
                    
                    previewContainer.appendChild(preview);
                    
                    // Add remove button event
                    preview.querySelector('.remove-image').addEventListener('click', function() {
                        preview.remove();
                    });
                };
                reader.readAsDataURL(file);
            });
        });
    }
}

// Initialize price calculation
function initializePriceCalculation() {
    const priceInput = document.querySelector('[name="price"]');
    const discountInput = document.querySelector('[name="discount_price"]');
    const discountPercentage = document.querySelector('.discount-percentage');
    
    if (priceInput && discountInput && discountPercentage) {
        const calculateDiscount = () => {
            const price = parseFloat(priceInput.value) || 0;
            const discount = parseFloat(discountInput.value) || 0;
            
            if (price > 0 && discount > 0 && discount < price) {
                const percentage = Math.round(((price - discount) / price) * 100);
                discountPercentage.textContent = `${percentage}% OFF`;
                discountPercentage.style.display = 'block';
            } else {
                discountPercentage.style.display = 'none';
            }
        };
        
        priceInput.addEventListener('input', calculateDiscount);
        discountInput.addEventListener('input', calculateDiscount);
    }
}

// Initialize bulk product actions
function initializeBulkProductActions() {
    // Select all products
    const selectAll = document.getElementById('selectAllProducts');
    if (selectAll) {
        selectAll.addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.product-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
            updateProductBulkActions();
        });
    }
    
    // Individual product checkboxes
    document.querySelectorAll('.product-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', updateProductBulkActions);
    });
    
    // Bulk action form
    const bulkActionForm = document.getElementById('productBulkActionForm');
    if (bulkActionForm) {
        bulkActionForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const selectedProducts = getSelectedProducts();
            const action = this.querySelector('select[name="bulk_action"]').value;
            
            if (selectedProducts.length === 0) {
                showNotification('Please select at least one product', 'warning');
                return;
            }
            
            if (!action) {
                showNotification('Please select an action', 'warning');
                return;
            }
            
            performProductBulkAction(action, selectedProducts);
        });
    }
}

// Get selected products
function getSelectedProducts() {
    const selected = [];
    document.querySelectorAll('.product-checkbox:checked').forEach(checkbox => {
        selected.push(checkbox.value);
    });
    return selected;
}

// Update product bulk actions
function updateProductBulkActions() {
    const selectedCount = getSelectedProducts().length;
    const bulkActionForm = document.getElementById('productBulkActionForm');
    
    if (bulkActionForm) {
        const actionSelect = bulkActionForm.querySelector('select[name="bulk_action"]');
        const actionButton = bulkActionForm.querySelector('button[type="submit"]');
        
        if (selectedCount === 0) {
            actionSelect.disabled = true;
            actionButton.disabled = true;
            actionButton.textContent = 'Apply';
        } else {
            actionSelect.disabled = false;
            actionButton.disabled = false;
            actionButton.textContent = `Apply to ${selectedCount} product(s)`;
        }
    }
}

// Perform product bulk action
function performProductBulkAction(action, products) {
    const formData = new FormData();
    formData.append('action', action);
    formData.append('products', JSON.stringify(products));
    
    fetch('/sneaker-commerce/api/vendor.php?action=bulk_product_update', {
        method: 'POST',
        body: formData,
        credentials: 'same-origin'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(data.message, 'success');
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('An error occurred. Please try again.', 'error');
    });
}

// Initialize quick edit
function initializeQuickEdit() {
    // Quick status update
    document.querySelectorAll('.quick-status-update').forEach(select => {
        select.addEventListener('change', function() {
            const productId = this.dataset.productId;
            const status = this.value;
            
            updateProductStatus(productId, status);
        });
    });
    
    // Quick price update
    document.querySelectorAll('.quick-price-edit').forEach(input => {
        input.addEventListener('blur', function() {
            const productId = this.dataset.productId;
            const price = this.value;
            
            if (this.dataset.originalValue !== price) {
                updateProductPrice(productId, price);
            }
        });
    });
    
    // Quick stock update
    document.querySelectorAll('.quick-stock-edit').forEach(input => {
        input.addEventListener('blur', function() {
            const productId = this.dataset.productId;
            const stock = this.value;
            
            if (this.dataset.originalValue !== stock) {
                updateProductStock(productId, stock);
            }
        });
    });
}

// Update product status
function updateProductStatus(productId, status) {
    fetch('/sneaker-commerce/api/vendor.php?action=update_product_status', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            product_id: productId,
            status: status
        }),
        credentials: 'same-origin'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Product status updated', 'success');
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('An error occurred. Please try again.', 'error');
    });
}

// Update product price
function updateProductPrice(productId, price) {
    fetch('/sneaker-commerce/api/vendor.php?action=update_product_price', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            product_id: productId,
            price: price
        }),
        credentials: 'same-origin'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Product price updated', 'success');
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('An error occurred. Please try again.', 'error');
    });
}

// Update product stock
function updateProductStock(productId, stock) {
    fetch('/sneaker-commerce/api/vendor.php?action=update_product_stock', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            product_id: productId,
            stock_quantity: stock
        }),
        credentials: 'same-origin'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Product stock updated', 'success');
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('An error occurred. Please try again.', 'error');
    });
}

// Initialize order management
function initializeOrderManagement() {
    // Order status updates
    document.querySelectorAll('.update-order-status').forEach(select => {
        select.addEventListener('change', function() {
            const orderId = this.dataset.orderId;
            const status = this.value;
            
            updateOrderStatus(orderId, status);
        });
    });
    
    // Mark as shipped
    document.querySelectorAll('.mark-as-shipped').forEach(button => {
        button.addEventListener('click', function() {
            const orderId = this.dataset.orderId;
            markAsShipped(orderId);
        });
    });
    
    // Print shipping label
    document.querySelectorAll('.print-shipping-label').forEach(button => {
        button.addEventListener('click', function() {
            const orderId = this.dataset.orderId;
            printShippingLabel(orderId);
        });
    });
    
    // View order details
    document.querySelectorAll('.view-order-details').forEach(button => {
        button.addEventListener('click', function() {
            const orderId = this.dataset.orderId;
            viewOrderDetails(orderId);
        });
    });
}

// Update order status
function updateOrderStatus(orderId, status) {
    fetch('/sneaker-commerce/api/vendor.php?action=update_order_status', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            order_id: orderId,
            status: status
        }),
        credentials: 'same-origin'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Order status updated', 'success');
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('An error occurred. Please try again.', 'error');
    });
}

// Mark as shipped
function markAsShipped(orderId) {
    const trackingNumber = prompt('Enter tracking number:');
    if (!trackingNumber) return;
    
    const carrier = prompt('Enter carrier name:');
    if (!carrier) return;
    
    fetch('/sneaker-commerce/api/vendor.php?action=mark_as_shipped', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            order_id: orderId,
            tracking_number: trackingNumber,
            carrier: carrier
        }),
        credentials: 'same-origin'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Order marked as shipped', 'success');
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('An error occurred. Please try again.', 'error');
    });
}

// Print shipping label
function printShippingLabel(orderId) {
    window.open(`/sneaker-commerce/vendor/print-shipping-label.php?order_id=${orderId}`, '_blank');
}

// View order details
function viewOrderDetails(orderId) {
    window.open(`/sneaker-commerce/vendor/order-details.php?id=${orderId}`, '_blank');
}

// Initialize earnings dashboard
function initializeEarningsDashboard() {
    // Withdrawal request
    const withdrawalForm = document.getElementById('withdrawalForm');
    if (withdrawalForm) {
        withdrawalForm.addEventListener('submit', function(e) {
            e.preventDefault();
            requestWithdrawal(this);
        });
    }
    
    // Update available balance
    updateAvailableBalance();
}

// Request withdrawal
function requestWithdrawal(form) {
    const formData = new FormData(form);
    const amount = parseFloat(formData.get('amount'));
    const availableBalance = parseFloat(document.querySelector('.available-balance')?.textContent?.replace(/[^0-9.]/g, '') || 0);
    
    if (amount > availableBalance) {
        showNotification('Withdrawal amount exceeds available balance', 'error');
        return;
    }
    
    if (amount < 100) { // Minimum withdrawal amount
        showNotification('Minimum withdrawal amount is ETB 100', 'error');
        return;
    }
    
    fetch('/sneaker-commerce/api/vendor.php?action=request_withdrawal', {
        method: 'POST',
        body: formData,
        credentials: 'same-origin'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Withdrawal request submitted', 'success');
            form.reset();
            updateAvailableBalance();
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('An error occurred. Please try again.', 'error');
    });
}

// Update available balance
function updateAvailableBalance() {
    fetch('/sneaker-commerce/api/vendor.php?action=get_balance')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const balanceElement = document.querySelector('.available-balance');
                if (balanceElement) {
                    balanceElement.textContent = formatPrice(data.balance);
                }
            }
        })
        .catch(error => console.error('Error updating balance:', error));
}

// Initialize store settings
function initializeStoreSettings() {
    // Store form validation
    const storeForm = document.getElementById('storeForm');
    if (storeForm) {
        storeForm.addEventListener('submit', function(e) {
            if (!validateStoreForm()) {
                e.preventDefault();
            }
        });
    }
    
    // Store logo upload
    const logoInput = document.getElementById('store_logo');
    if (logoInput) {
        logoInput.addEventListener('change', function(e) {
            previewStoreLogo(this);
        });
    }
}

// Validate store form
function validateStoreForm() {
    const storeName = document.querySelector('[name="store_name"]');
    const storeEmail = document.querySelector('[name="store_email"]');
    const storePhone = document.querySelector('[name="store_phone"]');
    
    let isValid = true;
    
    if (storeName && !storeName.value.trim()) {
        showFormError(storeName, 'Please enter store name');
        isValid = false;
    }
    
    if (storeEmail && !storeEmail.value.trim()) {
        showFormError(storeEmail, 'Please enter store email');
        isValid = false;
    }
    
    if (storePhone && !storePhone.value.trim()) {
        showFormError(storePhone, 'Please enter store phone');
        isValid = false;
    }
    
    return isValid;
}

// Preview store logo
function previewStoreLogo(input) {
    const preview = document.getElementById('store_logo_preview');
    if (preview && input.files && input.files[0]) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            preview.src = e.target.result;
        };
        
        reader.readAsDataURL(input.files[0]);
    }
}

// Start vendor updates
function startVendorUpdates() {
    // Update stats every 60 seconds
    setInterval(loadVendorStats, 60000);
    
    // Check for new orders every 30 seconds
    setInterval(checkNewOrders, 30000);
    
    // Update earnings every 5 minutes
    setInterval(updateAvailableBalance, 300000);
}

// Check for new orders
function checkNewOrders() {
    fetch('/sneaker-commerce/api/vendor.php?action=new_orders_count')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.count > 0) {
                updateNewOrdersBadge(data.count);
                if (data.count > 0 && !document.hidden) {
                    showNewOrdersNotification(data.count);
                }
            }
        })
        .catch(error => console.error('Error checking new orders:', error));
}

// Update new orders badge
function updateNewOrdersBadge(count) {
    const badge = document.querySelector('.new-orders-badge');
    if (badge) {
        badge.textContent = count;
        badge.style.display = count > 0 ? 'flex' : 'none';
    }
}

// Show new orders notification
function showNewOrdersNotification(count) {
    showNotification(`You have ${count} new order${count > 1 ? 's' : ''}`, 'info');
}

// Format price
function formatPrice(amount) {
    return 'ETB ' + parseFloat(amount).toLocaleString('en-ET', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

// Show notification
function showNotification(message, type = 'info') {
    if (window.SneakerHub && window.SneakerHub.showNotification) {
        window.SneakerHub.showNotification(message, type);
    }
}