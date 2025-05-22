<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$conn = getDBConnection();

$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("
    SELECT w.*, p.name as product_name, p.price as product_price, p.image as product_image 
    FROM wishlist w 
    JOIN products p ON w.product_id = p.id 
    WHERE w.user_id = ?
");
$stmt->execute([$user_id]);
$wishlist = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wishlist - NEXTGEN</title>
    
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
        <h1 class="mb-4">My Wishlist</h1>
        
        <?php if (empty($wishlist)): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>Your wishlist is empty.
                <a href="products.php" class="alert-link ms-2">Browse Products</a>
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($wishlist as $item): ?>
                    <div class="col-md-4 mb-4">
                        <div class="card h-100">
                            <img src="<?php echo htmlspecialchars($item['product_image']); ?>" 
                                 class="card-img-top" 
                                 alt="<?php echo htmlspecialchars($item['product_name']); ?>"
                                 style="height: 200px; object-fit: cover;">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($item['product_name']); ?></h5>
                                <p class="card-text product-price"><?php echo formatPrice($item['product_price']); ?></p>
                                <div class="d-flex justify-content-between">
                                    <a href="product.php?id=<?php echo $item['product_id']; ?>" 
                                       class="btn btn-outline-primary">View Details</a>
                                    <button onclick="toggleWishlist(<?php echo $item['product_id']; ?>)" 
                                            class="btn btn-outline-danger">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

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

