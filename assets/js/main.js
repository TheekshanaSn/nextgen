// Dark Mode Toggle
const darkModeToggle = document.getElementById('darkModeToggle');
if (darkModeToggle) {
    darkModeToggle.addEventListener('click', () => {
        document.body.classList.toggle('dark-mode');
        localStorage.setItem('darkMode', document.body.classList.contains('dark-mode'));
    });

    // Check for saved dark mode preference
    if (localStorage.getItem('darkMode') === 'true') {
        document.body.classList.add('dark-mode');
    }
}

// Cart Functions
function updateCart(productId, quantity) {
    fetch('ajax/update_cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            product_id: productId,
            quantity: quantity
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateCartUI(data);
        } else {
            showAlert('Error updating cart', 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Error updating cart', 'danger');
    });
}

function updateCartUI(data) {
    // Update cart count
    const cartCount = document.querySelector('.cart-count');
    if (cartCount) {
        cartCount.textContent = data.cart_count;
    }

    // Update cart total
    const cartTotal = document.querySelector('.cart-total');
    if (cartTotal) {
        cartTotal.textContent = `Rs. ${data.cart_total.toFixed(2)}`;
    }

    // Update cart items
    const cartItems = document.querySelector('.cart-items');
    if (cartItems) {
        cartItems.innerHTML = data.cart_items;
    }
}

// Wishlist Functions
function toggleWishlist(productId) {
    fetch('ajax/toggle_wishlist.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            product_id: productId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const wishlistBtn = document.querySelector(`[data-product-id="${productId}"]`);
            if (wishlistBtn) {
                wishlistBtn.classList.toggle('active');
                const icon = wishlistBtn.querySelector('i');
                const text = wishlistBtn.textContent.trim();
                
                if (wishlistBtn.classList.contains('active')) {
                    wishlistBtn.innerHTML = `<i class="fas fa-heart me-2"></i>Remove from Wishlist`;
                } else {
                    wishlistBtn.innerHTML = `<i class="fas fa-heart me-2"></i>Add to Wishlist`;
                }
            }
            showAlert(data.message, 'success');
        } else {
            if (data.message === 'Please login to add items to wishlist') {
                window.location.href = 'login.php';
            } else {
                showAlert(data.message, 'danger');
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Error updating wishlist', 'danger');
    });
}

// Alert Function
function showAlert(message, type = 'info') {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    const alertContainer = document.querySelector('.alert-container') || document.body;
    alertContainer.appendChild(alertDiv);
    
    setTimeout(() => {
        alertDiv.remove();
    }, 5000);
}

// Form Validation
function validateForm(formId) {
    const form = document.getElementById(formId);
    if (!form) return true;

    let isValid = true;
    const inputs = form.querySelectorAll('input[required], select[required], textarea[required]');
    
    inputs.forEach(input => {
        if (!input.value.trim()) {
            isValid = false;
            input.classList.add('is-invalid');
        } else {
            input.classList.remove('is-invalid');
        }
    });

    return isValid;
}

// Product Image Gallery
function initProductGallery() {
    const mainImage = document.querySelector('.product-main-image');
    const thumbnails = document.querySelectorAll('.product-thumbnail');
    
    if (mainImage && thumbnails.length) {
        thumbnails.forEach(thumb => {
            thumb.addEventListener('click', () => {
                mainImage.src = thumb.src;
                thumbnails.forEach(t => t.classList.remove('active'));
                thumb.classList.add('active');
            });
        });
    }
}

// Initialize functions when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    initProductGallery();
    
    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Initialize popovers
    const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
});

// AJAX filtering for products page
$(document).ready(function() {
    var $filterForm = $('#filterForm');
    var $productGrid = $('#productGrid');
    if ($filterForm.length && $productGrid.length) {
        // Handle category checkboxes
        $('#category_all').on('change', function() {
            if ($(this).is(':checked')) {
                $('input[name="category[]"]').not(this).prop('checked', false);
            }
        });

        $('input[name="category[]"]').not('#category_all').on('change', function() {
            if ($(this).is(':checked')) {
                $('#category_all').prop('checked', false);
            }
            // If no categories are selected, check "All"
            if ($('input[name="category[]"]:checked').length === 0) {
                $('#category_all').prop('checked', true);
            }
        });

        // Handle form submission
        $filterForm.on('change', 'input, select', function() {
            $filterForm.submit();
        });

        $filterForm.on('submit', function(e) {
            e.preventDefault();
            var formData = $filterForm.serialize();
            $productGrid.html('<div class="text-center w-100 py-5"><div class="spinner-border text-primary" role="status"></div></div>');
            $.get('products.php', formData, function(data) {
                var html = $(data).find('#productGrid').html();
                $productGrid.html(html);
                // Update URL without reload
                if (history.pushState) {
                    var newUrl = 'products.php?' + formData;
                    window.history.pushState({path:newUrl}, '', newUrl);
                }
            });
        });
    }
}); 