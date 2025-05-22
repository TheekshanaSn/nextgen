<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'You must be logged in to submit a review.']);
    exit();
}

$userId = $_SESSION['user_id'];
$productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
$rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
$comment = trim($_POST['comment'] ?? '');

if ($productId <= 0 || $rating < 1 || $rating > 5 || $comment === '') {
    echo json_encode(['success' => false, 'message' => 'Invalid review data.']);
    exit();
}

$conn = getDBConnection();
// Prevent duplicate review by the same user for the same product
$stmt = $conn->prepare("SELECT id FROM reviews WHERE user_id = ? AND product_id = ?");
$stmt->execute([$userId, $productId]);
if ($stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'You have already reviewed this product.']);
    exit();
}

$stmt = $conn->prepare("INSERT INTO reviews (user_id, product_id, rating, comment) VALUES (?, ?, ?, ?)");
if ($stmt->execute([$userId, $productId, $rating, $comment])) {
    echo json_encode(['success' => true, 'message' => 'Review submitted successfully.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to submit review.']);
} 