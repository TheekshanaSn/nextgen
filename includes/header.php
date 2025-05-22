<?php
$cart_count = 0;
if(isset($_SESSION['cart'])) {
    $cart_count = count($_SESSION['cart']);
}

// Fetch user profile picture if logged in
$user_profile_picture = null;
if (isset($_SESSION['user_id'])) {
    require_once __DIR__ . '/../config/database.php';
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT profile_picture FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user_profile_picture = $stmt->fetchColumn();
}
?>
<header class="bg-white shadow-sm">
    <nav class="navbar navbar-expand-lg navbar-light">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <h1 class="h4 mb-0">NEXTGEN</h1>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="products.php">Products</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="about.php">About</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="contact.php">Contact</a>
                    </li>
                </ul>
                
                <div class="d-flex align-items-center">
                    <form class="d-flex me-3" action="products.php" method="GET">
                        <input class="form-control me-2" type="search" name="search" placeholder="Search products...">
                        <button class="btn btn-outline-primary" type="submit">Search</button>
                    </form>
                    
                    <a href="cart.php" class="btn btn-outline-primary position-relative me-2">
                        <i class="fas fa-shopping-cart"></i>
                        <?php if($cart_count > 0): ?>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                <?php echo $cart_count; ?>
                            </span>
                        <?php endif; ?>
                    </a>
                    
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <?php
                            $profile_img = $user_profile_picture ? htmlspecialchars($user_profile_picture) : 'assets/images/default-profile.png';
                        ?>
                        <a href="profile.php">
                            <img src="<?php echo $profile_img; ?>" alt="Profile" style="width:40px; height:40px; object-fit:cover; border-radius:50%;">
                        </a>
                    <?php else: ?>
                        <a href="login.php" class="btn btn-outline-primary me-2">Login</a>
                        <a href="register.php" class="btn btn-primary">Register</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>
</header> 