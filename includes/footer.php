<footer class="bg-dark text-light py-5 mt-5">
    <div class="container">
        <div class="row">
            <div class="col-md-4 mb-4">
                <h5>NEXTGEN</h5>
                <p>Your trusted partner for mobile devices and accessories in Sri Lanka. We offer the latest smartphones, tablets, and accessories at competitive prices.</p>
                <div class="social-links">
                    <a href="#" class="text-light me-3"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="text-light me-3"><i class="fab fa-twitter"></i></a>
                    <a href="#" class="text-light me-3"><i class="fab fa-instagram"></i></a>
                    <a href="#" class="text-light"><i class="fab fa-linkedin-in"></i></a>
                </div>
            </div>
            
            <div class="col-md-2 mb-4">
                <h5>Quick Links</h5>
                <ul class="list-unstyled">
                    <li><a href="index.php" class="text-light">Home</a></li>
                    <li><a href="products.php" class="text-light">Products</a></li>
                    <li><a href="about.php" class="text-light">About Us</a></li>
                    <li><a href="contact.php" class="text-light">Contact</a></li>
                </ul>
            </div>
            
            <div class="col-md-3 mb-4">
                <h5>Categories</h5>
                <ul class="list-unstyled">
                    <?php
                    $db = new Database();
                    $conn = $db->getConnection();
                    $stmt = $conn->query("SELECT * FROM categories LIMIT 5");
                    while($category = $stmt->fetch()) {
                        echo '<li><a href="products.php?category=' . $category['slug'] . '" class="text-light">' . htmlspecialchars($category['name']) . '</a></li>';
                    }
                    ?>
                </ul>
            </div>
            
            <div class="col-md-3 mb-4">
                <h5>Contact Info</h5>
                <ul class="list-unstyled">
                    <li><i class="fas fa-map-marker-alt me-2"></i> 123 Main Street, Colombo, Sri Lanka</li>
                    <li><i class="fas fa-phone me-2"></i> +94 11 234 5678</li>
                    <li><i class="fas fa-envelope me-2"></i> info@nextgen.lk</li>
                </ul>
            </div>
        </div>
        
        <hr class="my-4">
        
        <div class="row">
            <div class="col-md-6">
                <p class="mb-0">&copy; <?php echo date('Y'); ?> NEXTGEN. All rights reserved.</p>
            </div>
            <div class="col-md-6 text-md-end">
                <a href="privacy.php" class="text-light me-3">Privacy Policy</a>
                <a href="terms.php" class="text-light">Terms & Conditions</a>
            </div>
        </div>
    </div>
</footer> 