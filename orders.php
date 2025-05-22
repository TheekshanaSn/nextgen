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

// Fetch orders for the user
$stmt = $conn->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$orders = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - NEXTGEN</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <div class="container py-5">
        <h1 class="mb-4">My Orders</h1>
        <?php if (empty($orders)): ?>
            <div class="alert alert-info">No orders found.</div>
        <?php else: ?>
            <div class="accordion" id="ordersAccordion">
                <?php foreach ($orders as $index => $order): ?>
                    <div class="accordion-item mb-3">
                        <h2 class="accordion-header" id="heading<?= $order['id'] ?>">
                            <button class="accordion-button <?= $index > 0 ? 'collapsed' : '' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?= $order['id'] ?>" aria-expanded="<?= $index === 0 ? 'true' : 'false' ?>" aria-controls="collapse<?= $order['id'] ?>">
                                Order #<?= $order['id'] ?> - <?= htmlspecialchars($order['created_at']) ?> - <?= htmlspecialchars(ucfirst($order['status'])) ?> - LKR <?= number_format($order['total_amount'], 2) ?>
                            </button>
                        </h2>
                        <div id="collapse<?= $order['id'] ?>" class="accordion-collapse collapse <?= $index === 0 ? 'show' : '' ?>" aria-labelledby="heading<?= $order['id'] ?>" data-bs-parent="#ordersAccordion">
                            <div class="accordion-body">
                                <strong>Shipping Address:</strong> <?= nl2br(htmlspecialchars($order['shipping_address'])) ?><br>
                                <strong>Billing Address:</strong> <?= nl2br(htmlspecialchars($order['billing_address'])) ?><br>
                                <strong>Payment Method:</strong> <?= htmlspecialchars($order['payment_method']) ?><br>
                                <hr>
                                <h6>Order Items:</h6>
                                <ul>
                                    <?php
                                    $itemStmt = $conn->prepare("SELECT oi.*, p.name FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
                                    $itemStmt->execute([$order['id']]);
                                    $items = $itemStmt->fetchAll();
                                    foreach ($items as $item):
                                    ?>
                                        <li><?= htmlspecialchars($item['name']) ?> x <?= $item['quantity'] ?> (LKR <?= number_format($item['price'], 2) ?> each)</li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <a href="index.php" class="btn btn-primary mt-4">Back to Home</a>
    </div>
    <?php include 'includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
            