<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode([
        'success' => false,
        'message' => 'Please login to add items to wishlist'
    ]);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$productId = isset($data['product_id']) ? (int)$data['product_id'] : 0;

if ($productId <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid product'
    ]);
    exit();
}

try {
    $added = toggleWishlist($_SESSION['user_id'], $productId);
    echo json_encode([
        'success' => true,
        'message' => $added ? 'Added to wishlist' : 'Removed from wishlist'
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error updating wishlist'
    ]);
} 