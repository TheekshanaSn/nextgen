<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Fetch 4 featured products from the database for homepage
$featuredProducts = getProducts(null, null, 4);

// Fetch 4 categories for homepage
$conn = getDBConnection();
$categories = $conn->query("SELECT * FROM categories LIMIT 4")->fetchAll();

// Fetch best sellers (top 8 by order count)
$bestSellers = $conn->query("
    SELECT p.*, COUNT(oi.id) as sales
    FROM products p
    JOIN order_items oi ON p.id = oi.product_id
    GROUP BY p.id
    ORDER BY sales DESC
    LIMIT 8
")->fetchAll();

// Fetch banners for hero carousel
$banners = $conn->query("SELECT * FROM banners WHERE is_active = 1 ORDER BY sort_order ASC")->fetchAll();

// Category filter
$selectedCategory = isset($_GET['category']) ? $_GET['category'] : null;
$allProducts = getProducts($selectedCategory, null, 20);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NEXTGEN - Sri Lankan Mobile Shop</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <!-- Custom CSS -->
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <!-- Header -->
    <?php include 'includes/header.php'; ?>

    <!-- Hero Carousel -->
    <section class="hero-section p-0">
        <div id="heroCarousel" class="carousel slide" data-bs-ride="carousel">
            <div class="carousel-inner">
                <?php foreach ($banners as $i => $banner): ?>
                    <div class="carousel-item<?php echo $i === 0 ? ' active' : ''; ?>">
                        <img src="<?php echo htmlspecialchars($banner['image']); ?>" class="d-block w-100" alt="<?php echo htmlspecialchars($banner['title']); ?>">
                        <div class="carousel-caption d-none d-md-block">
                            <h1 class="display-3 fw-bold text-shadow"><?php echo htmlspecialchars($banner['title']); ?></h1>
                            <p class="lead"><?php echo htmlspecialchars($banner['subtitle']); ?></p>
                            <?php if ($banner['cta_text'] && $banner['cta_link']): ?>
                                <a href="<?php echo htmlspecialchars($banner['cta_link']); ?>" class="btn btn-primary btn-lg"><?php echo htmlspecialchars($banner['cta_text']); ?></a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <button class="carousel-control-prev" type="button" data-bs-target="#heroCarousel" data-bs-slide="prev">
                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Previous</span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#heroCarousel" data-bs-slide="next">
                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Next</span>
            </button>
        </div>
    </section>

    <!-- Category Filter -->
    <div class="mb-4 text-center">
        <a href="index.php" class="btn btn-outline-primary m-1<?php if (!isset($_GET['category'])) echo ' active'; ?>">All</a>
        <?php foreach ($categories as $cat): ?>
            <a href="index.php?category=<?php echo urlencode($cat['slug']); ?>" class="btn btn-outline-primary m-1<?php if (isset($_GET['category']) && $_GET['category'] == $cat['slug']) echo ' active'; ?>">
                <?php echo htmlspecialchars($cat['name']); ?>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- All Products Section -->
    <section class="py-5 bg-light">
        <div class="container">
            <h2 class="text-center mb-4">Shop All Products</h2>
            <div class="row justify-content-center">
                <?php foreach ($allProducts as $i => $product): ?>
                    <div class="col-6 col-md-3 mb-4">
                        <div class="card h-100 text-center p-3 border-0 shadow-sm position-relative">
                            <?php
                            $isNew = isset($product['created_at']) && (strtotime($product['created_at']) > strtotime('-30 days'));
                            $isBestSeller = isset($product['sales']) && $product['sales'] > 10;
                            $isLowStock = isset($product['stock']) && $product['stock'] <= 5;
                            ?>
                            <div class="position-absolute top-0 start-0 m-2" style="z-index:2;">
                                <?php if ($isNew): ?>
                                    <span class="badge bg-success">New</span>
                                <?php endif; ?>
                                <?php if ($isBestSeller): ?>
                                    <span class="badge bg-warning text-dark">Best Seller</span>
                                <?php endif; ?>
                                <?php if ($isLowStock): ?>
                                    <span class="badge bg-danger">Low Stock</span>
                                <?php endif; ?>
                            </div>
                            <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="mb-3 img-fluid rounded" style="height:180px;object-fit:cover;">
                            <h5><?php echo htmlspecialchars($product['name']); ?></h5>
                            <p class="product-price text-primary fw-bold" style="font-size:1.5rem;">
                                <?php echo formatPrice($product['price']); ?>
                            </p>
                            <div class="d-flex justify-content-center gap-2">
                                <a href="product.php?id=<?php echo $product['id']; ?>" class="btn btn-outline-primary btn-sm">View Details</a>
                                <form method="post" action="cart.php" class="d-inline">
                                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                    <input type="hidden" name="quantity" value="1">
                                    <button type="submit" name="add_to_cart" class="btn btn-primary btn-sm"><i class="fas fa-cart-plus"></i></button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <?php if (($i+1) % 4 == 0): ?>
                        </div><div class="row justify-content-center">
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Featured Categories -->
    <section class="categories-section py-5">
        <div class="container">
            <h2 class="text-center mb-4">Shop by Category</h2>
            <div class="row justify-content-center">
                <?php foreach ($categories as $cat): ?>
                    <div class="col-6 col-md-3 mb-4">
                        <div class="card h-100 text-center p-3 border-0 shadow-sm">
                            <i class="bi bi-phone-fill mb-3" style="font-size: 2.5rem; color: var(--primary-color);"></i>
                            <h5><?php echo htmlspecialchars($cat['name']); ?></h5>
                            <a href="products.php?category=<?php echo urlencode($cat['slug']); ?>" class="btn btn-outline-primary mt-2">View</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Best Sellers Carousel -->
    <section class="py-5 bg-light">
        <div class="container">
            <h2 class="text-center mb-4">Best Sellers</h2>
            <div id="bestSellersCarousel" class="carousel slide" data-bs-ride="carousel">
                <div class="carousel-inner">
                    <?php for ($i = 0; $i < count($bestSellers); $i += 4): ?>
                        <div class="carousel-item<?php echo $i === 0 ? ' active' : ''; ?>">
                            <div class="row justify-content-center">
                                <?php for ($j = $i; $j < $i + 4 && $j < count($bestSellers); $j++): $product = $bestSellers[$j]; ?>
                                    <div class="col-6 col-md-3 mb-4">
                                        <div class="card h-100 text-center p-3 border-0 shadow-sm">
                                            <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="mb-3 img-fluid rounded">
                                            <h5><?php echo htmlspecialchars($product['name']); ?></h5>
                                            <p class="product-price"><?php echo formatPrice($product['price']); ?></p>
                                            <a href="product.php?id=<?php echo $product['id']; ?>" class="btn btn-primary btn-sm">Add to Cart</a>
                                        </div>
                                    </div>
                                <?php endfor; ?>
                            </div>
                        </div>
                    <?php endfor; ?>
                </div>
                <button class="carousel-control-prev" type="button" data-bs-target="#bestSellersCarousel" data-bs-slide="prev">
                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                    <span class="visually-hidden">Previous</span>
                </button>
                <button class="carousel-control-next" type="button" data-bs-target="#bestSellersCarousel" data-bs-slide="next">
                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                    <span class="visually-hidden">Next</span>
                </button>
            </div>
        </div>
    </section>

    <!-- Promo Banners -->
    <section class="py-5">
        <div class="container">
            <div id="promoCarousel" class="carousel slide" data-bs-ride="carousel">
                <div class="carousel-inner">
                    <!-- First Slide -->
                    <div class="carousel-item active">
                        <div class="row g-2">
                            <div class="col-md-6">
                                <div class="promo-banner position-relative rounded overflow-hidden shadow">
                                    <img src="assets/images/promo-1.png" class="w-80" alt="Promo 1">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="promo-banner position-relative rounded overflow-hidden shadow">
                                    <img src="assets/images/promo-2.png" class="w-60" alt="Promo 2">
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Second Slide -->
                    <div class="carousel-item">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <div class="promo-banner position-relative rounded overflow-hidden shadow">
                                    <img src="assets/images/promo-3.png" class="w-100" alt="Promo 3">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="promo-banner position-relative rounded overflow-hidden shadow">
                                    <img src="assets/images/promo-4.png" class="w-100" alt="Promo 4">
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Third Slide -->
                    <div class="carousel-item">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <div class="promo-banner position-relative rounded overflow-hidden shadow">
                                    <img src="assets/images/promo-5.png" class="w-100" alt="Promo 5">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="promo-banner position-relative rounded overflow-hidden shadow">
                                    <img src="assets/images/promo-1.png" class="w-100" alt="Promo 1">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Carousel Controls -->
                <button class="carousel-control-prev" type="button" data-bs-target="#promoCarousel" data-bs-slide="prev">
                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                    <span class="visually-hidden">Previous</span>
                </button>
                <button class="carousel-control-next" type="button" data-bs-target="#promoCarousel" data-bs-slide="next">
                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                    <span class="visually-hidden">Next</span>
                </button>
                <!-- Carousel Indicators -->
                <div class="carousel-indicators">
                    <button type="button" data-bs-target="#promoCarousel" data-bs-slide-to="0" class="active" aria-current="true" aria-label="Slide 1"></button>
                    <button type="button" data-bs-target="#promoCarousel" data-bs-slide-to="1" aria-label="Slide 2"></button>
                    <button type="button" data-bs-target="#promoCarousel" data-bs-slide-to="2" aria-label="Slide 3"></button>
                </div>
            </div>
        </div>
    </section>

    <style>
        /* Custom styles for the carousel */
        #promoCarousel {
            position: relative;
        }
        #promoCarousel .carousel-control-prev,
        #promoCarousel .carousel-control-next {
            width: 5%;
            opacity: 0.8;
        }
        #promoCarousel .carousel-control-prev-icon,
        #promoCarousel .carousel-control-next-icon {
            background-color: rgba(0, 0, 0, 0.5);
            padding: 20px;
            border-radius: 50%;
        }
        #promoCarousel .carousel-indicators {
            margin-bottom: 0;
        }
        #promoCarousel .carousel-indicators button {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            margin: 0 5px;
        }
        .promo-banner {
            height: 222px; /* Fixed height for all promo banners */
        }
        .promo-banner img {
            height: 100%;
            object-fit: cover;
        }
    </style>

    <script>
        // Initialize carousel with custom settings
        document.addEventListener('DOMContentLoaded', function() {
            var myCarousel = new bootstrap.Carousel(document.getElementById('promoCarousel'), {
                interval: 1500, // Change slide every 5 seconds
                wrap: true, // Continuous loop
                keyboard: true, // Keyboard navigation
                pause: 'hover' // Pause on mouse hover
            });
        });
    </script>

    <!-- Featured Products Section -->
    <section class="py-5 bg-light">
        <div class="container">
            <h2 class="text-center mb-4">Featured Products</h2>
            <div class="row justify-content-center">
                <?php foreach ($featuredProducts as $product): ?>
                    <div class="col-6 col-md-3 mb-4">
                        <div class="card h-100 text-center p-3 border-0 shadow-sm">
                            <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="mb-3 img-fluid rounded" style="height:180px;object-fit:cover;">
                            <h5><?php echo htmlspecialchars($product['name']); ?></h5>
                            <p class="product-price"><?php echo formatPrice($product['price']); ?></p>
                            <div class="d-flex justify-content-center gap-2">
                                <a href="product.php?id=<?php echo $product['id']; ?>" class="btn btn-outline-primary btn-sm">View Details</a>
                                <form method="post" action="cart.php" class="d-inline">
                                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                    <input type="hidden" name="quantity" value="1">
                                    <button type="submit" name="add_to_cart" class="btn btn-primary btn-sm"><i class="fas fa-cart-plus"></i></button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Why Shop With Us -->
    <section class="py-5 bg-light mt-5">
        <div class="container">
            <h2 class="text-center mb-4">Why Shop With Us?</h2>
            <div class="row text-center">
                <div class="col-6 col-md-3 mb-4">
                    <i class="fas fa-shipping-fast fa-2x mb-2 text-primary"></i>
                    <h6>Fast Delivery</h6>
                </div>
                <div class="col-6 col-md-3 mb-4">
                    <i class="fas fa-certificate fa-2x mb-2 text-primary"></i>
                    <h6>Genuine Products</h6>
                </div>
                <div class="col-6 col-md-3 mb-4">
                    <i class="fas fa-lock fa-2x mb-2 text-primary"></i>
                    <h6>Secure Payments</h6>
                </div>
                <div class="col-6 col-md-3 mb-4">
                    <i class="fas fa-headset fa-2x mb-2 text-primary"></i>
                    <h6>24/7 Support</h6>
                </div>
            </div>
        </div>
    </section>

    <!-- Customer Reviews Carousel
    <section class="py-5">
        <div class="container">
            <h2 class="text-center mb-4">What Our Customers Say</h2>
            <div id="reviewsCarousel" class="carousel slide" data-bs-ride="carousel">
                <div class="carousel-inner">
                    <div class="carousel-item active">
                        <div class="row justify-content-center">
                            <div class="col-md-6">
                                <div class="card shadow-sm p-4 text-center">
                                    <img src="https://randomuser.me/api/portraits/men/32.jpg" class="rounded-circle mb-3" style="width:70px; height:70px; object-fit:cover;" alt="Customer 1">
                                    <blockquote class="blockquote mb-2">“Amazing service and super fast delivery. Highly recommended!”</blockquote>
                                    <footer class="blockquote-footer">Nimal Perera</footer>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card shadow-sm p-4 text-center">
                                    <img src="https://randomuser.me/api/portraits/women/44.jpg" class="rounded-circle mb-3" style="width:70px; height:70px; object-fit:cover;" alt="Customer 2">
                                    <blockquote class="blockquote mb-2">“Best prices for genuine products. Will shop again!”</blockquote>
                                    <footer class="blockquote-footer">Dilani Fernando</footer>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="carousel-item">
                        <div class="row justify-content-center">
                            <div class="col-md-6">
                                <div class="card shadow-sm p-4 text-center">
                                    <img src="https://randomuser.me/api/portraits/men/65.jpg" class="rounded-circle mb-3" style="width:70px; height:70px; object-fit:cover;" alt="Customer 3">
                                    <blockquote class="blockquote mb-2">“Great selection and easy checkout process.”</blockquote>
                                    <footer class="blockquote-footer">Suresh Jayasinghe</footer>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card shadow-sm p-4 text-center">
                                    <img src="https://randomuser.me/api/portraits/women/68.jpg" class="rounded-circle mb-3" style="width:70px; height:70px; object-fit:cover;" alt="Customer 4">
                                    <blockquote class="blockquote mb-2">“Customer support was very helpful and friendly.”</blockquote>
                                    <footer class="blockquote-footer">Anusha Wijeratne</footer>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <button class="carousel-control-prev" type="button" data-bs-target="#reviewsCarousel" data-bs-slide="prev">
                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                    <span class="visually-hidden">Previous</span>
                </button>
                <button class="carousel-control-next" type="button" data-bs-target="#reviewsCarousel" data-bs-slide="next">
                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                    <span class="visually-hidden">Next</span>
                </button>
            </div>
        </div>
    </section> -->

    <!-- Brands Section -->
    <section class="py-5 bg-light mt-5">
        <div class="container">
            <h2 class="text-center mb-4">Brands We Carry</h2>
            <div class="row justify-content-center align-items-center g-4">
                <div class="col-4 col-md-2 text-center">
                    <img src="https://cdn.jsdelivr.net/gh/simple-icons/simple-icons/icons/apple.svg" alt="Apple" style="height:40px;">
                </div>
                <div class="col-4 col-md-2 text-center">
                    <img src="https://cdn.jsdelivr.net/gh/simple-icons/simple-icons/icons/samsung.svg" alt="Samsung" style="height:40px;">
                </div>
                <div class="col-4 col-md-2 text-center">
                    <img src="https://cdn.jsdelivr.net/gh/simple-icons/simple-icons/icons/xiaomi.svg" alt="Xiaomi" style="height:40px;">
                </div>
                <div class="col-4 col-md-2 text-center">
                    <img src="https://cdn.jsdelivr.net/gh/simple-icons/simple-icons/icons/oppo.svg" alt="Oppo" style="height:40px;">
                </div>
                <div class="col-4 col-md-2 text-center">
                    <img src="https://cdn.jsdelivr.net/gh/simple-icons/simple-icons/icons/huawei.svg" alt="Huawei" style="height:40px;">
                </div>
            </div>
        </div>
    </section>

    <!-- Newsletter Signup -->
    <section class="py-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="card shadow-sm p-4">
                        <h3 class="mb-3 text-center">Get the Latest Deals!</h3>
                        <form action="#" method="POST" class="row g-2 justify-content-center">
                            <div class="col-12 col-md-8">
                                <input type="email" class="form-control form-control-lg" name="newsletter_email" placeholder="Enter your email" required>
                            </div>
                            <div class="col-12 col-md-4">
                                <button type="submit" class="btn btn-primary btn-lg w-100">Subscribe</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <?php include 'includes/footer.php'; ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Custom JS -->
    <script src="assets/js/main.js"></script>
</body>
</html> 