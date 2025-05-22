<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$product = getProduct($productId);

if (!$product) {
    header('Location: products.php');
    exit();
}

// Get product reviews
$reviews = getProductReviews($productId);

// Get related products
$conn = getDBConnection();
$stmt = $conn->prepare("SELECT * FROM products WHERE category_id = ? AND id != ? ORDER BY RAND() LIMIT 4");
$stmt->execute([$product['category_id'], $productId]);
$relatedProducts = $stmt->fetchAll();

// Handle add to cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    if (!isLoggedIn()) {
        $_SESSION['redirect_after_login'] = 'product.php?id=' . $productId;
        header('Location: login.php');
        exit();
    }
    $quantity = (int)$_POST['quantity'];
    if ($quantity > 0 && $quantity <= $product['stock']) {
        addToCart($productId, $quantity);
        header('Location: cart.php');
        exit();
    }
}

// Parse images for gallery
$productImages = [$product['image']];
if (!empty($product['images'])) {
    $extraImages = array_filter(array_map('trim', explode(',', $product['images'])));
    foreach ($extraImages as $img) {
        if ($img && $img !== $product['image']) $productImages[] = $img;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name']); ?> - NEXTGEN</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <!-- Header -->
    <?php include 'includes/header.php'; ?>

    <div class="container py-5">
        <div class="row">
            <!-- Product Images -->
            <div class="col-md-6">
                <div class="product-gallery">
                    <img src="<?php echo htmlspecialchars($productImages[0]); ?>" 
                         class="img-fluid rounded product-main-image" 
                         alt="<?php echo htmlspecialchars($product['name']); ?>">
                    
                    <div class="row mt-3">
                        <?php foreach ($productImages as $idx => $img): ?>
                            <div class="col-3">
                                <img src="<?php echo htmlspecialchars($img); ?>" 
                                     class="img-fluid rounded product-thumbnail<?php echo $idx === 0 ? ' active' : ''; ?>" 
                                     alt="Thumbnail <?php echo $idx+1; ?>">
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <!-- Product Info -->
            <div class="col-md-6">
                <div class="product-info">
                    <h1 class="mb-3"><?php echo htmlspecialchars($product['name']); ?></h1>
                    
                    <div class="mb-3">
                        <span class="badge bg-primary"><?php echo htmlspecialchars($product['brand']); ?></span>
                        <span class="badge bg-secondary"><?php echo htmlspecialchars($product['category_name']); ?></span>
                    </div>
                    
                    <p class="product-price mb-4"><?php echo formatPrice($product['price']); ?></p>
                    
                    <div class="mb-4">
                        <h5>Description</h5>
                        <p><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
                    </div>
                    
                    <div class="mb-4">
                        <h5>Specifications</h5>
                        <ul class="list-unstyled">
                            <li><strong>Brand:</strong> <?php echo htmlspecialchars($product['brand']); ?></li>
                            <li><strong>Category:</strong> <?php echo htmlspecialchars($product['category_name']); ?></li>
                            <li>
                                <strong>Stock:</strong>
                                <?php if ($product['stock'] > 10): ?>
                                    <span class="badge bg-success">In Stock</span>
                                <?php elseif ($product['stock'] > 0): ?>
                                    <span class="badge bg-warning text-dark">Only <?php echo $product['stock']; ?> left!</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Out of Stock</span>
                                <?php endif; ?>
                            </li>
                        </ul>
                    </div>
                    
                    <form method="POST" action="product.php?id=<?php echo $productId; ?>" class="mb-4">
                        <div class="row align-items-center">
                            <div class="col-md-4">
                                <div class="input-group">
                                    <button type="button" class="btn btn-outline-secondary" onclick="decrementQuantity()">-</button>
                                    <input type="number" class="form-control text-center" id="quantity" name="quantity" 
                                           value="1" min="1" max="<?php echo $product['stock']; ?>">
                                    <button type="button" class="btn btn-outline-secondary" onclick="incrementQuantity()">+</button>
                                </div>
                            </div>
                            <div class="col-md-8">
                                <button type="submit" name="add_to_cart" class="btn btn-primary w-100">
                                    <i class="fas fa-cart-plus me-2"></i>Add to Cart
                                </button>
                            </div>
                        </div>
                    </form>
                    
                    <div class="d-flex gap-2">
                        <?php
                        $isInWishlist = false;
                        if (isLoggedIn()) {
                            $conn = getDBConnection();
                            $stmt = $conn->prepare("SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?");
                            $stmt->execute([$_SESSION['user_id'], $productId]);
                            $isInWishlist = $stmt->fetch() !== false;
                        }
                        ?>
                        <button onclick="toggleWishlist(<?php echo $productId; ?>)" 
                                class="btn btn-outline-primary<?php echo $isInWishlist ? ' active' : ''; ?>"
                                data-product-id="<?php echo $productId; ?>">
                            <i class="fas fa-heart me-2"></i><?php echo $isInWishlist ? 'Remove from Wishlist' : 'Add to Wishlist'; ?>
                        </button>
                        <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#shareModal">
                            <i class="fas fa-share-alt me-2"></i>Share
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Reviews Section -->
        <div class="row mt-5">
            <div class="col-12">
                <h3>Customer Reviews</h3>
                <div id="review-message"></div>
                <?php if (isLoggedIn()): ?>
                    <div class="card mb-4">
                        <div class="card-body">
                            <h5>Write a Review</h5>
                            <form action="ajax/add_review.php" method="POST" id="reviewForm">
                                <input type="hidden" name="product_id" value="<?php echo $productId; ?>">
                                <div class="mb-3">
                                    <label class="form-label">Rating</label>
                                    <div class="rating" id="star-rating">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="fas fa-star text-secondary" data-value="<?php echo $i; ?>"></i>
                                        <?php endfor; ?>
                                        <input type="hidden" name="rating" id="rating-value" required>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="comment" class="form-label">Your Review</label>
                                    <textarea class="form-control" id="comment" name="comment" rows="3" required></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary">Submit Review</button>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>
                <div id="reviews-list">
                    <?php foreach ($reviews as $review): ?>
                        <div class="card mb-3">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h6 class="mb-0"><?php echo htmlspecialchars($review['user_name']); ?></h6>
                                    <small class="text-muted"><?php echo date('F j, Y', strtotime($review['created_at'])); ?></small>
                                </div>
                                <div class="mb-2">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="fas fa-star<?php echo $i <= $review['rating'] ? ' text-warning' : ' text-secondary'; ?>"></i>
                                    <?php endfor; ?>
                                </div>
                                <p class="mb-0"><?php echo nl2br(htmlspecialchars($review['comment'])); ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <!-- Related Products -->
        <div class="row mt-5">
            <div class="col-12">
                <h3>Related Products</h3>
                <div class="row">
                    <?php foreach ($relatedProducts as $related): ?>
                        <div class="col-md-3 mb-4">
                            <div class="card h-100">
                                <img src="<?php echo htmlspecialchars($related['image']); ?>" 
                                     class="card-img-top" 
                                     alt="<?php echo htmlspecialchars($related['name']); ?>">
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo htmlspecialchars($related['name']); ?></h5>
                                    <p class="card-text product-price"><?php echo formatPrice($related['price']); ?></p>
                                    <a href="product.php?id=<?php echo $related['id']; ?>" 
                                       class="btn btn-outline-primary w-100">View Details</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Share Modal -->
    <div class="modal fade" id="shareModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Share Product</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="d-flex justify-content-around">
                        <a href="#" class="btn btn-outline-primary" onclick="shareOnFacebook()">
                            <i class="fab fa-facebook-f"></i> Facebook
                        </a>
                        <a href="#" class="btn btn-outline-info" onclick="shareOnTwitter()">
                            <i class="fab fa-twitter"></i> Twitter
                        </a>
                        <a href="#" class="btn btn-outline-success" onclick="shareOnWhatsApp()">
                            <i class="fab fa-whatsapp"></i> WhatsApp
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <?php include 'includes/footer.php'; ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Custom JS -->
    <script src="assets/js/main.js"></script>
    
    <script>
        function incrementQuantity() {
            const input = document.getElementById('quantity');
            const max = parseInt(input.getAttribute('max'));
            const value = parseInt(input.value);
            if (value < max) {
                input.value = value + 1;
            }
        }
        
        function decrementQuantity() {
            const input = document.getElementById('quantity');
            const value = parseInt(input.value);
            if (value > 1) {
                input.value = value - 1;
            }
        }
        
        function shareOnFacebook() {
            const url = encodeURIComponent(window.location.href);
            window.open(`https://www.facebook.com/sharer/sharer.php?u=${url}`, '_blank');
        }
        
        function shareOnTwitter() {
            const text = encodeURIComponent(document.title);
            const url = encodeURIComponent(window.location.href);
            window.open(`https://twitter.com/intent/tweet?text=${text}&url=${url}`, '_blank');
        }
        
        function shareOnWhatsApp() {
            const text = encodeURIComponent(document.title);
            const url = encodeURIComponent(window.location.href);
            window.open(`https://wa.me/?text=${text}%20${url}`, '_blank');
        }

        const reviewForm = document.getElementById('reviewForm');
        if (reviewForm) {
            reviewForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const form = this;
                const formData = new FormData(form);
                fetch('ajax/add_review.php', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    const msgDiv = document.getElementById('review-message');
                    msgDiv.innerHTML = '<div class="alert alert-' + (data.success ? 'success' : 'danger') + '">' + data.message + '</div>';
                    if (data.success) {
                        form.reset();
                        // Reload reviews
                        fetch('ajax/get_reviews.php?product_id=' + form.product_id.value)
                            .then(res => res.text())
                            .then(html => {
                                document.getElementById('reviews-list').innerHTML = html;
                            });
                    }
                });
            });
        }

        document.addEventListener('DOMContentLoaded', function() {
            const stars = document.querySelectorAll('#star-rating .fa-star');
            const ratingInput = document.getElementById('rating-value');
            let currentRating = 0;

            stars.forEach(star => {
                star.addEventListener('mouseenter', function() {
                    const val = parseInt(this.getAttribute('data-value'));
                    highlightStars(val);
                });
                star.addEventListener('mouseleave', function() {
                    highlightStars(currentRating);
                });
                star.addEventListener('click', function() {
                    currentRating = parseInt(this.getAttribute('data-value'));
                    ratingInput.value = currentRating;
                    highlightStars(currentRating);
                });
            });

            function highlightStars(rating) {
                stars.forEach(star => {
                    const val = parseInt(star.getAttribute('data-value'));
                    star.classList.toggle('text-warning', val <= rating);
                    star.classList.toggle('text-secondary', val > rating);
                });
            }
        });
    </script>
</body>
</html> 