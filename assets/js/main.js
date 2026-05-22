// Main JavaScript for SneakerHub Ethiopia

document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Initialize popovers
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    var popoverList = popoverTriggerList.map(function(popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
    
    // Add to cart functionality
    initializeAddToCartButtons();
    
    // Wishlist functionality
    initializeWishlistButtons();
    
    // Search functionality
    initializeSearch();
    
    // Image lazy loading
    initializeLazyLoading();
    
    // Form validation
    initializeFormValidation();
    
    // Quantity selectors
    initializeQuantitySelectors();
    
    // Notifications
    initializeNotifications();
});

// Add to cart functionality
function initializeAddToCartButtons() {
    document.querySelectorAll('.add-to-cart').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            const productId = this.dataset.productId;
            const productName = this.dataset.productName;
            const quantity = this.dataset.quantity || 1;
            
            // Disable button during request
            const originalHtml = this.innerHTML;
            this.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Adding...';
            this.disabled = true;
            
            // Make API request
            fetch(`/sneaker-commerce/api/cart.php?action=add&product_id=${productId}&quantity=${quantity}`, {
                credentials: 'same-origin'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update cart count in navbar
                    updateCartCount(data.cart_count);
                    
                    // Show success message
                    showNotification(`${productName} added to cart!`, 'success');
                    
                    // Update button state if on product page
                    const viewCartBtn = document.querySelector('.view-cart-btn');
                    if (viewCartBtn) {
                        viewCartBtn.classList.remove('d-none');
                    }
                } else {
                    showNotification(data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('An error occurred. Please try again.', 'error');
            })
            .finally(() => {
                // Restore button
                this.innerHTML = originalHtml;
                this.disabled = false;
            });
        });
    });
}

// Wishlist functionality
function initializeWishlistButtons() {
    document.querySelectorAll('.wishlist-btn').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            const productId = this.dataset.productId;
            const icon = this.querySelector('i');
            const isInWishlist = icon.classList.contains('fas');
            
            // Toggle visual state immediately for better UX
            if (isInWishlist) {
                icon.classList.remove('fas');
                icon.classList.add('far');
                this.title = 'Add to Wishlist';
            } else {
                icon.classList.remove('far');
                icon.classList.add('fas');
                this.title = 'Remove from Wishlist';
            }
            
            // Make API request
            fetch(`/sneaker-commerce/api/wishlist.php?action=${isInWishlist ? 'remove' : 'add'}&product_id=${productId}`, {
                credentials: 'same-origin'
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    // Revert visual state if API failed
                    if (isInWishlist) {
                        icon.classList.remove('far');
                        icon.classList.add('fas');
                        this.title = 'Remove from Wishlist';
                    } else {
                        icon.classList.remove('fas');
                        icon.classList.add('far');
                        this.title = 'Add to Wishlist';
                    }
                    showNotification(data.message, 'error');
                } else {
                    showNotification(isInWishlist ? 'Removed from wishlist' : 'Added to wishlist', 'success');
                    
                    // Update wishlist count if element exists
                    const wishlistCount = document.querySelector('.wishlist-count');
                    if (wishlistCount) {
                        const currentCount = parseInt(wishlistCount.textContent) || 0;
                        wishlistCount.textContent = isInWishlist ? currentCount - 1 : currentCount + 1;
                        
                        // Hide badge if count is 0
                        if (wishlistCount.textContent === '0') {
                            wishlistCount.style.display = 'none';
                        } else {
                            wishlistCount.style.display = 'flex';
                        }
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('An error occurred. Please try again.', 'error');
                
                // Revert visual state
                if (isInWishlist) {
                    icon.classList.remove('far');
                    icon.classList.add('fas');
                    this.title = 'Remove from Wishlist';
                } else {
                    icon.classList.remove('fas');
                    icon.classList.add('far');
                    this.title = 'Add to Wishlist';
                }
            });
        });
    });
}

// Search functionality
function initializeSearch() {
    const searchForm = document.querySelector('.search-form');
    const searchInput = document.querySelector('.search-input');
    
    if (searchForm && searchInput) {
        // Auto-complete functionality
        searchInput.addEventListener('input', debounce(function(e) {
            const query = e.target.value.trim();
            
            if (query.length >= 2) {
                fetch(`/sneaker-commerce/api/products.php?action=list&search=${encodeURIComponent(query)}&limit=5`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showSearchSuggestions(data.products, searchInput);
                        }
                    });
            } else {
                hideSearchSuggestions();
            }
        }, 300));
        
        // Hide suggestions when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.search-container')) {
                hideSearchSuggestions();
            }
        });
    }
}

// Image lazy loading
function initializeLazyLoading() {
    if ('IntersectionObserver' in window) {
        const lazyImages = document.querySelectorAll('img.lazy');
        
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src;
                    img.classList.remove('lazy');
                    imageObserver.unobserve(img);
                }
            });
        });
        
        lazyImages.forEach(img => imageObserver.observe(img));
    } else {
        // Fallback for older browsers
        document.querySelectorAll('img.lazy').forEach(img => {
            img.src = img.dataset.src;
            img.classList.remove('lazy');
        });
    }
}

// Form validation
function initializeFormValidation() {
    // Phone number formatting
    const phoneInputs = document.querySelectorAll('input[type="tel"]');
    phoneInputs.forEach(input => {
        input.addEventListener('input', function(e) {
            let value = this.value.replace(/\D/g, '');
            
            if (value.startsWith('0')) {
                value = '+251' + value.substring(1);
            } else if (value.startsWith('251')) {
                value = '+' + value;
            } else if (value.startsWith('9') && value.length >= 9) {
                value = '+251' + value;
            }
            
            if (value.length > 13) {
                value = value.substring(0, 13);
            }
            
            this.value = value;
        });
    });
    
    // Password strength indicator
    const passwordInputs = document.querySelectorAll('input[type="password"]');
    passwordInputs.forEach(input => {
        if (input.id.includes('password') && !input.id.includes('confirm')) {
            input.addEventListener('input', function(e) {
                const strength = checkPasswordStrength(this.value);
                updatePasswordStrengthIndicator(this, strength);
            });
        }
    });
    
    // Confirm password validation
    const confirmPasswordInputs = document.querySelectorAll('input[id*="confirm_password"]');
    confirmPasswordInputs.forEach(input => {
        const passwordInput = document.querySelector('input[id*="new_password"], input[id*="password"]');
        if (passwordInput) {
            input.addEventListener('input', function(e) {
                const password = passwordInput.value;
                const confirm = this.value;
                
                if (password && confirm) {
                    if (password === confirm) {
                        this.classList.remove('is-invalid');
                        this.classList.add('is-valid');
                    } else {
                        this.classList.remove('is-valid');
                        this.classList.add('is-invalid');
                    }
                }
            });
        }
    });
}

// Quantity selectors
function initializeQuantitySelectors() {
    document.querySelectorAll('.quantity-selector').forEach(selector => {
        const minusBtn = selector.querySelector('.quantity-minus');
        const plusBtn = selector.querySelector('.quantity-plus');
        const input = selector.querySelector('.quantity-input');
        
        if (minusBtn && plusBtn && input) {
            minusBtn.addEventListener('click', function() {
                let value = parseInt(input.value) || 1;
                if (value > 1) {
                    input.value = value - 1;
                    input.dispatchEvent(new Event('change'));
                }
            });
            
            plusBtn.addEventListener('click', function() {
                let value = parseInt(input.value) || 1;
                const max = parseInt(input.max) || 99;
                if (value < max) {
                    input.value = value + 1;
                    input.dispatchEvent(new Event('change'));
                }
            });
            
            input.addEventListener('change', function() {
                let value = parseInt(this.value) || 1;
                const min = parseInt(this.min) || 1;
                const max = parseInt(this.max) || 99;
                
                if (value < min) value = min;
                if (value > max) value = max;
                
                this.value = value;
                
                // Update any associated data
                if (this.dataset.productId) {
                    // Update cart quantity via API
                    updateCartQuantity(this.dataset.productId, value);
                }
            });
        }
    });
}

// Notifications
function initializeNotifications() {
    // Auto-hide alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
    alerts.forEach(alert => {
        setTimeout(() => {
            if (alert.parentNode) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            }
        }, 5000);
    });
}

// Helper function to update cart count
function updateCartCount(count) {
    const cartCountElements = document.querySelectorAll('.cart-count');
    cartCountElements.forEach(element => {
        element.textContent = count;
        
        // Show/hide badge
        if (count > 0) {
            element.style.display = 'flex';
        } else {
            element.style.display = 'none';
        }
    });
    
    // Update cart total if element exists
    const cartTotal = document.querySelector('.cart-total');
    if (cartTotal) {
        fetch('/sneaker-commerce/api/cart.php?action=get')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    cartTotal.textContent = formatPrice(data.grand_total);
                }
            });
    }
}

// Helper function to show notifications
function showNotification(message, type = 'info') {
    // Remove existing notifications
    const existing = document.querySelector('.notification-container');
    if (existing) {
        existing.remove();
    }
    
    // Create notification container
    const container = document.createElement('div');
    container.className = 'notification-container position-fixed';
    container.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    
    // Create notification
    const alert = document.createElement('div');
    alert.className = `alert alert-${type} alert-dismissible fade show shadow`;
    alert.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    container.appendChild(alert);
    document.body.appendChild(container);
    
    // Auto-remove after 5 seconds
    setTimeout(() => {
        if (container.parentNode) {
            container.remove();
        }
    }, 5000);
}

// Helper function to format price
function formatPrice(amount) {
    return 'ETB ' + parseFloat(amount).toLocaleString('en-ET', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

// Helper function for debounce
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Helper function to check password strength
function checkPasswordStrength(password) {
    let strength = 0;
    
    if (password.length >= 8) strength += 25;
    if (password.length >= 12) strength += 10;
    if (/[A-Z]/.test(password)) strength += 25;
    if (/[0-9]/.test(password)) strength += 25;
    if (/[^A-Za-z0-9]/.test(password)) strength += 25;
    
    return Math.min(strength, 100);
}

// Helper function to update password strength indicator
function updatePasswordStrengthIndicator(input, strength) {
    let container = input.parentNode.querySelector('.password-strength');
    
    if (!container) {
        container = document.createElement('div');
        container.className = 'password-strength mt-2';
        input.parentNode.appendChild(container);
    }
    
    let text = '';
    let color = '';
    
    if (strength < 40) {
        text = 'Weak password';
        color = 'danger';
    } else if (strength < 70) {
        text = 'Medium password';
        color = 'warning';
    } else if (strength < 90) {
        text = 'Good password';
        color = 'info';
    } else {
        text = 'Strong password';
        color = 'success';
    }
    
    container.innerHTML = `
        <div class="progress" style="height: 5px;">
            <div class="progress-bar bg-${color}" role="progressbar" style="width: ${strength}%"></div>
        </div>
        <small class="text-${color}">${text}</small>
    `;
}

// Helper function to update cart quantity via API
function updateCartQuantity(productId, quantity) {
    fetch('/sneaker-commerce/api/cart.php?action=update', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            product_id: productId,
            quantity: quantity
        }),
        credentials: 'same-origin'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateCartCount(data.cart_count);
            
            // Update subtotal if element exists
            const subtotalElement = document.querySelector(`.subtotal-${productId}`);
            if (subtotalElement && data.cart_items) {
                const item = data.cart_items.find(i => i.product.id == productId);
                if (item) {
                    subtotalElement.textContent = formatPrice(item.subtotal);
                }
            }
            
            // Update total if element exists
            const totalElement = document.querySelector('.cart-grand-total');
            if (totalElement) {
                totalElement.textContent = formatPrice(data.grand_total);
            }
        }
    });
}

// Helper functions for search suggestions (to be implemented based on UI)
function showSearchSuggestions(products, input) {
    // Implementation depends on your UI design
    console.log('Search suggestions:', products);
}

function hideSearchSuggestions() {
    // Implementation depends on your UI design
}

// Export functions for use in other files
window.SneakerHub = {
    showNotification,
    formatPrice,
    updateCartCount,
    updateCartQuantity
};