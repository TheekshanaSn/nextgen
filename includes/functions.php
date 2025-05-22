<?php
// Database connection
function getDBConnection() {
    $db = new Database();
    return $db->getConnection();
}

// User authentication
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}

function requireAdmin() {
    if (!isAdmin()) {
        header('Location: index.php');
        exit();
    }
}

// Input sanitization
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Password hashing
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

// Cart functions
function getCart() {
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    return $_SESSION['cart'];
}

function addToCart($productId, $quantity = 1) {
    $cart = getCart();
    if (isset($cart[$productId])) {
        $cart[$productId] += $quantity;
    } else {
        $cart[$productId] = $quantity;
    }
    $_SESSION['cart'] = $cart;
}

function updateCartItem($productId, $quantity) {
    $cart = getCart();
    if ($quantity <= 0) {
        unset($cart[$productId]);
    } else {
        $cart[$productId] = $quantity;
    }
    $_SESSION['cart'] = $cart;
}

function removeFromCart($productId) {
    $cart = getCart();
    unset($cart[$productId]);
    $_SESSION['cart'] = $cart;
}

function getCartTotal() {
    $cart = getCart();
    $total = 0;
    $conn = getDBConnection();
    
    foreach ($cart as $productId => $quantity) {
        $stmt = $conn->prepare("SELECT price FROM products WHERE id = ?");
        $stmt->execute([$productId]);
        $product = $stmt->fetch();
        if ($product) {
            $total += $product['price'] * $quantity;
        }
    }
    
    return $total;
}

// Product functions
function getProduct($productId) {
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT p.*, c.name as category_name 
                            FROM products p 
                            LEFT JOIN categories c ON p.category_id = c.id 
                            WHERE p.id = ?");
    $stmt->execute([$productId]);
    return $stmt->fetch();
}

function getProducts($category = null, $search = null, $limit = null, $offset = null) {
    $conn = getDBConnection();
    $sql = "SELECT p.*, c.name as category_name 
            FROM products p 
            LEFT JOIN categories c ON p.category_id = c.id 
            WHERE 1=1";
    $params = [];
    
    if ($category) {
        $sql .= " AND c.slug = ?";
        $params[] = $category;
    }
    
    if ($search) {
        $sql .= " AND (p.name LIKE ? OR p.description LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    if ($limit) {
        $sql .= " LIMIT ?";
        $params[] = $limit;
        
        if ($offset) {
            $sql .= " OFFSET ?";
            $params[] = $offset;
        }
    }
    
    $stmt = $conn->prepare($sql);
    for ($i = 0; $i < count($params); $i++) {
        // Bind LIMIT and OFFSET as integers if present
        if ((strpos($sql, 'LIMIT ?') !== false && $i === count($params) - 1) ||
            (strpos($sql, 'OFFSET ?') !== false && $i === count($params) - 1) ||
            (strpos($sql, 'LIMIT ? OFFSET ?') !== false && ($i === count($params) - 2 || $i === count($params) - 1))) {
            $stmt->bindValue($i + 1, (int)$params[$i], PDO::PARAM_INT);
        } else {
            $stmt->bindValue($i + 1, $params[$i]);
        }
    }
    $stmt->execute();
    return $stmt->fetchAll();
}

// Order functions
function createOrder($userId, $cart, $shippingAddress, $billingAddress, $paymentMethod) {
    $conn = getDBConnection();
    try {
        $conn->beginTransaction();
        
        // Create order
        $stmt = $conn->prepare("INSERT INTO orders (user_id, total, shipping_address, billing_address, payment_method) VALUES (?, ?, ?, ?, ?)");
        $total = getCartTotal();
        $stmt->execute([$userId, $total, $shippingAddress, $billingAddress, $paymentMethod]);
        $orderId = $conn->lastInsertId();
        
        // Create order items
        $stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
        foreach ($cart as $productId => $quantity) {
            $product = getProduct($productId);
            $stmt->execute([$orderId, $productId, $quantity, $product['price']]);
            
            // Update stock
            $newStock = $product['stock'] - $quantity;
            $updateStmt = $conn->prepare("UPDATE products SET stock = ? WHERE id = ?");
            $updateStmt->execute([$newStock, $productId]);
        }
        
        $conn->commit();
        return $orderId;
    } catch (Exception $e) {
        $conn->rollBack();
        throw $e;
    }
}

// Wishlist functions
function toggleWishlist($userId, $productId) {
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?");
    $stmt->execute([$userId, $productId]);
    
    if ($stmt->fetch()) {
        // Remove from wishlist
        $stmt = $conn->prepare("DELETE FROM wishlist WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$userId, $productId]);
        return false;
    } else {
        // Add to wishlist
        $stmt = $conn->prepare("INSERT INTO wishlist (user_id, product_id) VALUES (?, ?)");
        $stmt->execute([$userId, $productId]);
        return true;
    }
}

// Review functions
function addReview($userId, $productId, $rating, $comment) {
    $conn = getDBConnection();
    $stmt = $conn->prepare("INSERT INTO reviews (user_id, product_id, rating, comment) VALUES (?, ?, ?, ?)");
    return $stmt->execute([$userId, $productId, $rating, $comment]);
}

function getProductReviews($productId) {
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT r.*, u.name as user_name 
                           FROM reviews r 
                           JOIN users u ON r.user_id = u.id 
                           WHERE r.product_id = ? 
                           ORDER BY r.created_at DESC");
    $stmt->execute([$productId]);
    return $stmt->fetchAll();
}

// Utility functions
function formatPrice($price) {
    return 'Rs. ' . number_format($price, 2);
}

function generateSlug($text) {
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    $text = strtolower($text);
    return $text;
}

function uploadImage($file, $targetDir = 'assets/images/products/') {
    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0777, true);
    }
    
    $fileName = basename($file['name']);
    $targetPath = $targetDir . $fileName;
    $imageFileType = strtolower(pathinfo($targetPath, PATHINFO_EXTENSION));
    
    // Check if image file is a actual image or fake image
    $check = getimagesize($file['tmp_name']);
    if ($check === false) {
        return false;
    }
    
    // Check file size
    if ($file['size'] > 5000000) {
        return false;
    }
    
    // Allow certain file formats
    if ($imageFileType != 'jpg' && $imageFileType != 'png' && $imageFileType != 'jpeg') {
        return false;
    }
    
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        return $targetPath;
    }
    
    return false;
}

function log_user_activity($user_id, $action, $details = null) {
    $conn = getDBConnection();
    $stmt = $conn->prepare("INSERT INTO user_activity_logs (user_id, action, details) VALUES (?, ?, ?)");
    $stmt->execute([$user_id, $action, $details]);
}
?> 