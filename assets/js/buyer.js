// Buyer Dashboard JavaScript

document.addEventListener('DOMContentLoaded', function() {
    // Initialize buyer dashboard
    initializeOrderTracking();
    initializeAddressManagement();
    initializePaymentMethods();
    initializeWishlist();
    initializeReviews();
    
    // Order actions
    initializeOrderActions();
    
    // Profile updates
    initializeProfileUpdates();
});

// Initialize order tracking
function initializeOrderTracking() {
    const orderProgress = document.querySelector('.order-progress');
    if (orderProgress) {
        updateOrderProgress();
        
        // Update progress every 30 seconds for pending orders
        setInterval(updateOrderProgress, 30000);
    }
}

// Update order progress
function updateOrderProgress() {
    const orderId = document.querySelector('[data-order-id]')?.dataset.orderId;
    if (!orderId) return;
    
    fetch(`/sneaker-commerce/api/orders.php?action=track&order_id=${orderId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateProgressBar(data.status);
                updateStatusBadge(data.status);
                
                // Update estimated delivery
                if (data.estimated_delivery) {
                    updateDeliveryEstimate(data.estimated_delivery);
                }
                
                // Show tracking info if available
                if (data.tracking_number) {
                    showTrackingInfo(data.tracking_number, data.carrier);
                }
            }
        })
        .catch(error => console.error('Error tracking order:', error));
}

// Update progress bar
function updateProgressBar(status) {
    const steps = document.querySelectorAll('.progress-step');
    const statusOrder = ['pending', 'confirmed', 'processing', 'shipped', 'delivered'];
    
    steps.forEach((step, index) => {
        const stepStatus = step.dataset.status;
        if (statusOrder.indexOf(status) >= index) {
            step.classList.add('active');
        } else {
            step.classList.remove('active');
        }
    });
}

// Update status badge
function updateStatusBadge(status) {
    const badge = document.querySelector('.order-status-badge');
    if (badge) {
        badge.className = `badge bg-${getStatusColor(status)}`;
        badge.textContent = status.charAt(0).toUpperCase() + status.slice(1);
    }
}

// Get status color
function getStatusColor(status) {
    const colors = {
        'pending': 'warning',
        'confirmed': 'info',
        'processing': 'primary',
        'shipped': 'info',
        'delivered': 'success',
        'cancelled': 'danger',
        'refunded': 'secondary'
    };
    return colors[status] || 'secondary';
}

// Update delivery estimate
function updateDeliveryEstimate(estimatedDelivery) {
    const element = document.querySelector('.delivery-estimate');
    if (element) {
        const date = new Date(estimatedDelivery);
        const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
        element.textContent = date.toLocaleDateString('en-ET', options);
    }
}

// Show tracking info
function showTrackingInfo(trackingNumber, carrier) {
    const trackingElement = document.querySelector('.tracking-info');
    if (trackingElement) {
        trackingElement.innerHTML = `
            <p><strong>Tracking Number:</strong> ${trackingNumber}</p>
            <p><strong>Carrier:</strong> ${carrier}</p>
            <a href="#" class="btn btn-sm btn-outline-primary track-btn">Track Package</a>
        `;
        
        // Add tracking button event
        trackingElement.querySelector('.track-btn').addEventListener('click', function(e) {
            e.preventDefault();
            window.open(`https://track24.net/?code=${trackingNumber}`, '_blank');
        });
    }
}

// Initialize address management
function initializeAddressManagement() {
    // Address form validation
    const addressForm = document.getElementById('addressForm');
    if (addressForm) {
        addressForm.addEventListener('submit', function(e) {
            if (!validateAddressForm()) {
                e.preventDefault();
            }
        });
    }
    
    // Set default address
    document.querySelectorAll('.set-default-address').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const addressId = this.dataset.addressId;
            setDefaultAddress(addressId);
        });
    });
    
    // Delete address
    document.querySelectorAll('.delete-address').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const addressId = this.dataset.addressId;
            deleteAddress(addressId);
        });
    });
}

// Validate address form
function validateAddressForm() {
    const phoneInput = document.querySelector('#phone');
    const regionSelect = document.querySelector('#region');
    
    let isValid = true;
    
    // Validate phone
    if (phoneInput && !/^\+251[0-9]{9}$/.test(phoneInput.value)) {
        showFormError(phoneInput, 'Please enter a valid Ethiopian phone number (format: +251911234567)');
        isValid = false;
    }
    
    // Validate region
    if (regionSelect && !regionSelect.value) {
        showFormError(regionSelect, 'Please select a region');
        isValid = false;
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

// Set default address
function setDefaultAddress(addressId) {
    fetch(`/sneaker-commerce/api/addresses.php?action=set_default&id=${addressId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Default address updated', 'success');
                setTimeout(() => location.reload(), 1500);
            } else {
                showNotification(data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('An error occurred. Please try again.', 'error');
        });
}

// Delete address
function deleteAddress(addressId) {
    if (!confirm('Are you sure you want to delete this address?')) {
        return;
    }
    
    fetch(`/sneaker-commerce/api/addresses.php?action=delete&id=${addressId}`, {
        method: 'DELETE',
        credentials: 'same-origin'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Address deleted', 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('An error occurred. Please try again.', 'error');
    });
}

// Initialize payment methods
function initializePaymentMethods() {
    // Card form validation
    const cardForm = document.getElementById('cardForm');
    if (cardForm) {
        cardForm.addEventListener('submit', function(e) {
            if (!validateCardForm()) {
                e.preventDefault();
            }
        });
        
        // Format card number
        const cardNumberInput = document.getElementById('card_number');
        if (cardNumberInput) {
            cardNumberInput.addEventListener('input', function(e) {
                formatCardNumber(this);
            });
        }
    }
    
    // Set default card
    document.querySelectorAll('.set-default-card').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const cardId = this.dataset.cardId;
            setDefaultCard(cardId);
        });
    });
    
    // Delete card
    document.querySelectorAll('.delete-card').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const cardId = this.dataset.cardId;
            deleteCard(cardId);
        });
    });
}

// Validate card form
function validateCardForm() {
    const cardNumber = document.querySelector('#card_number');
    const expiryMonth = document.querySelector('#expiry_month');
    const expiryYear = document.querySelector('#expiry_year');
    const cvv = document.querySelector('#cvv');
    
    let isValid = true;
    
    // Validate card number (basic validation)
    if (cardNumber && cardNumber.value.replace(/\s/g, '').length < 13) {
        showFormError(cardNumber, 'Please enter a valid card number');
        isValid = false;
    }
    
    // Validate expiry date
    if (expiryMonth && expiryYear) {
        const currentYear = new Date().getFullYear();
        const currentMonth = new Date().getMonth() + 1;
        const selectedYear = parseInt(expiryYear.value);
        const selectedMonth = parseInt(expiryMonth.value);
        
        if (selectedYear < currentYear || (selectedYear === currentYear && selectedMonth < currentMonth)) {
            showFormError(expiryYear, 'Card has expired');
            isValid = false;
        }
    }
    
    // Validate CVV
    if (cvv && (cvv.value.length < 3 || cvv.value.length > 4)) {
        showFormError(cvv, 'Please enter a valid CVV');
        isValid = false;
    }
    
    return isValid;
}

// Format card number
function formatCardNumber(input) {
    let value = input.value.replace(/\D/g, '');
    
    // Add spaces every 4 digits
    value = value.replace(/(\d{4})(?=\d)/g, '$1 ');
    
    if (value.length > 19) { // 16 digits + 3 spaces
        value = value.substring(0, 19);
    }
    
    input.value = value;
}

// Set default card
function setDefaultCard(cardId) {
    fetch(`/sneaker-commerce/api/payment.php?action=set_default&id=${cardId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Default payment card updated', 'success');
                setTimeout(() => location.reload(), 1500);
            } else {
                showNotification(data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('An error occurred. Please try again.', 'error');
        });
}

// Delete card
function deleteCard(cardId) {
    if (!confirm('Are you sure you want to delete this payment card?')) {
        return;
    }
    
    fetch(`/sneaker-commerce/api/payment.php?action=delete&id=${cardId}`, {
        method: 'DELETE',
        credentials: 'same-origin'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Payment card deleted', 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('An error occurred. Please try again.', 'error');
    });
}

// Initialize wishlist
function initializeWishlist() {
    // Move to cart
    document.querySelectorAll('.move-to-cart').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const productId = this.dataset.productId;
            moveToCart(productId);
        });
    });
    
    // Clear wishlist
    const clearWishlistBtn = document.querySelector('.clear-wishlist');
    if (clearWishlistBtn) {
        clearWishlistBtn.addEventListener('click', function(e) {
            e.preventDefault();
            clearWishlist();
        });
    }
}

// Move item to cart
function moveToCart(productId) {
    fetch(`/sneaker-commerce/api/cart.php?action=add&product_id=${productId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Remove from wishlist
                fetch(`/sneaker-commerce/api/wishlist.php?action=remove&product_id=${productId}`)
                    .then(response => response.json())
                    .then(wishlistData => {
                        if (wishlistData.success) {
                            showNotification('Item moved to cart', 'success');
                            updateWishlistCount();
                            updateCartCount(data.cart_count);
                            removeWishlistItem(productId);
                        }
                    });
            } else {
                showNotification(data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('An error occurred. Please try again.', 'error');
        });
}

// Clear wishlist
function clearWishlist() {
    if (!confirm('Are you sure you want to clear your entire wishlist?')) {
        return;
    }
    
    fetch('/sneaker-commerce/api/wishlist.php?action=clear', {
        method: 'DELETE',
        credentials: 'same-origin'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Wishlist cleared', 'success');
            updateWishlistCount();
            clearWishlistItems();
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('An error occurred. Please try again.', 'error');
    });
}

// Update wishlist count
function updateWishlistCount() {
    const countElement = document.querySelector('.wishlist-count');
    if (countElement) {
        fetch('/sneaker-commerce/api/wishlist.php?action=count')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    countElement.textContent = data.count;
                    if (data.count === 0) {
                        countElement.style.display = 'none';
                    } else {
                        countElement.style.display = 'flex';
                    }
                }
            });
    }
}

// Remove wishlist item from DOM
function removeWishlistItem(productId) {
    const itemElement = document.querySelector(`.wishlist-item-${productId}`);
    if (itemElement) {
        itemElement.remove();
        
        // Show empty message if no items left
        const items = document.querySelectorAll('.wishlist-item');
        if (items.length === 0) {
            showEmptyWishlistMessage();
        }
    }
}

// Clear wishlist items from DOM
function clearWishlistItems() {
    document.querySelectorAll('.wishlist-item').forEach(item => item.remove());
    showEmptyWishlistMessage();
}

// Show empty wishlist message
function showEmptyWishlistMessage() {
    const container = document.querySelector('.wishlist-container');
    if (container) {
        container.innerHTML = `
            <div class="text-center py-5">
                <i class="fas fa-heart fa-4x text-muted mb-3"></i>
                <h4>Your wishlist is empty</h4>
                <p class="text-muted mb-4">Save items you like to your wishlist for easy access.</p>
                <a href="/sneaker-commerce/public/shop.php" class="btn btn-primary">
                    <i class="fas fa-shopping-bag me-2"></i>Start Shopping
                </a>
            </div>
        `;
    }
}

// Initialize reviews
function initializeReviews() {
    // Star rating
    document.querySelectorAll('.star-rating').forEach(rating => {
        const stars = rating.querySelectorAll('.star');
        const input = rating.querySelector('input[type="hidden"]');
        
        stars.forEach(star => {
            star.addEventListener('click', function() {
                const value = this.dataset.value;
                input.value = value;
                setRating(stars, value);
            });
            
            star.addEventListener('mouseover', function() {
                const value = this.dataset.value;
                highlightStars(stars, value);
            });
        });
        
        rating.addEventListener('mouseleave', function() {
            const currentValue = input.value || 0;
            highlightStars(stars, currentValue);
        });
    });
    
    // Submit review
    const reviewForm = document.getElementById('reviewForm');
    if (reviewForm) {
        reviewForm.addEventListener('submit', function(e) {
            e.preventDefault();
            submitReview(this);
        });
    }
}

// Set star rating
function setRating(stars, value) {
    stars.forEach(star => {
        if (star.dataset.value <= value) {
            star.classList.add('text-warning');
            star.classList.remove('text-muted');
        } else {
            star.classList.remove('text-warning');
            star.classList.add('text-muted');
        }
    });
}

// Highlight stars on hover
function highlightStars(stars, value) {
    stars.forEach(star => {
        if (star.dataset.value <= value) {
            star.classList.add('text-warning');
        } else {
            star.classList.remove('text-warning');
            star.classList.add('text-muted');
        }
    });
}

// Submit review
function submitReview(form) {
    const formData = new FormData(form);
    
    fetch('/sneaker-commerce/api/reviews.php?action=submit', {
        method: 'POST',
        body: formData,
        credentials: 'same-origin'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Review submitted successfully', 'success');
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

// Initialize order actions
function initializeOrderActions() {
    // Cancel order
    document.querySelectorAll('.cancel-order').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const orderId = this.dataset.orderId;
            cancelOrder(orderId);
        });
    });
    
    // Track order
    document.querySelectorAll('.track-order').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const orderId = this.dataset.orderId;
            trackOrder(orderId);
        });
    });
    
    // Reorder
    document.querySelectorAll('.reorder').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const orderId = this.dataset.orderId;
            reorder(orderId);
        });
    });
    
    // Download invoice
    document.querySelectorAll('.download-invoice').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const orderId = this.dataset.orderId;
            downloadInvoice(orderId);
        });
    });
}

// Cancel order
function cancelOrder(orderId) {
    if (!confirm('Are you sure you want to cancel this order?')) {
        return;
    }
    
    fetch(`/sneaker-commerce/api/orders.php?action=cancel&order_id=${orderId}`, {
        method: 'POST',
        credentials: 'same-origin'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Order cancelled successfully', 'success');
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

// Track order
function trackOrder(orderId) {
    window.open(`/sneaker-commerce/buyer/order-tracking.php?id=${orderId}`, '_blank');
}

// Reorder
function reorder(orderId) {
    fetch(`/sneaker-commerce/api/orders.php?action=reorder&order_id=${orderId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Items added to cart', 'success');
                updateCartCount(data.cart_count);
                
                // Redirect to cart
                setTimeout(() => {
                    window.location.href = '/sneaker-commerce/public/cart.php';
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

// Download invoice
function downloadInvoice(orderId) {
    window.open(`/sneaker-commerce/public/invoice.php?order_id=${orderId}`, '_blank');
}

// Initialize profile updates
function initializeProfileUpdates() {
    // Profile image upload
    const profileImageInput = document.getElementById('profile_image');
    if (profileImageInput) {
        profileImageInput.addEventListener('change', function(e) {
            previewProfileImage(this);
        });
    }
    
    // Password toggle
    document.querySelectorAll('.toggle-password').forEach(button => {
        button.addEventListener('click', function() {
            const input = this.previousElementSibling;
            const icon = this.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
    });
}

// Preview profile image
function previewProfileImage(input) {
    const preview = document.getElementById('profile_image_preview');
    if (preview && input.files && input.files[0]) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            preview.src = e.target.result;
        };
        
        reader.readAsDataURL(input.files[0]);
    }
}

// Update cart count (from main.js)
function updateCartCount(count) {
    if (window.SneakerHub && window.SneakerHub.updateCartCount) {
        window.SneakerHub.updateCartCount(count);
    }
}

// Show notification (from main.js)
function showNotification(message, type = 'info') {
    if (window.SneakerHub && window.SneakerHub.showNotification) {
        window.SneakerHub.showNotification(message, type);
    }
}