<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Admin check
if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header('Location: login.php');
    exit();
}

$conn = getDBConnection();
$reviews = $conn->query("
    SELECT r.id, p.name AS product_name, u.name AS user_name, r.rating, r.comment, r.created_at
    FROM reviews r
    JOIN products p ON r.product_id = p.id
    JOIN users u ON r.user_id = u.id
    ORDER BY r.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Reviews - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: #F4F6FC; }
        .card { border-radius: 1rem; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
        .sidebar { min-height: 100vh; background: #4B49AC; color: #fff; }
        .sidebar .nav-link { color: #fff; font-weight: 500; border-radius: 8px; margin-bottom: 0.5rem; }
        .sidebar .nav-link.active, .sidebar .nav-link:hover { background: #fff; color: #4B49AC; }
        .sidebar-logo { font-size:2rem; font-weight:700; letter-spacing:2px; color: #4B49AC; background: #fff; padding:0.5rem 1.5rem; border-radius:1rem; display:inline-block; }
    </style>
</head>
<body>
<div class="row g-0">
    <!-- Sidebar -->
    <div class="col-md-2 sidebar d-flex flex-column p-3">
        <div class="mb-4 text-center">
            <span class="sidebar-logo">NEXTGEN</span>
        </div>
        <nav class="nav flex-column mb-auto">
            <a href="admin.php" class="nav-link<?= basename($_SERVER['PHP_SELF']) == 'admin.php' ? ' active' : '' ?>"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</a>
            <a href="admin_analytics.php" class="nav-link<?= basename($_SERVER['PHP_SELF']) == 'admin_analytics.php' ? ' active' : '' ?>"><i class="fas fa-chart-line me-2"></i>Analytics</a>
            <a href="admin_orders.php" class="nav-link<?= basename($_SERVER['PHP_SELF']) == 'admin_orders.php' ? ' active' : '' ?>"><i class="fas fa-box me-2"></i>Order Management</a>
            <a href="admin_users.php" class="nav-link<?= basename($_SERVER['PHP_SELF']) == 'admin_users.php' ? ' active' : '' ?>"><i class="fas fa-users me-2"></i>User Management</a>
            <a href="admin_products.php" class="nav-link<?= basename($_SERVER['PHP_SELF']) == 'admin_products.php' ? ' active' : '' ?>"><i class="fas fa-mobile-alt me-2"></i>Product Management</a>
            <a href="admin_audit_logs.php" class="nav-link<?= basename($_SERVER['PHP_SELF']) == 'admin_audit_logs.php' ? ' active' : '' ?>"><i class="fas fa-clipboard-list me-2"></i>Audit Logs</a>
            <a href="admin_reviews.php" class="nav-link<?= basename($_SERVER['PHP_SELF']) == 'admin_reviews.php' ? ' active' : '' ?>"><i class="fas fa-star me-2"></i>Customer Reviews</a>
        </nav>
        <div class="mt-auto text-center">
            <a href="logout.php" class="btn btn-light w-100 mt-4"><i class="fas fa-sign-out-alt me-2"></i>Logout</a>
        </div>
    </div>
    <!-- Main Content -->
    <div class="col-md-10">
        <div class="dashboard-header d-flex justify-content-between align-items-center p-4 mb-4">
            <h1 class="h3 mb-0">Customer Reviews</h1>
        </div>
        <div class="container-fluid">
            <div class="card p-4">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Product Name</th>
                                <th>Username</th>
                                <th>Rating</th>
                                <th>Comment</th>
                                <th>Created At</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($reviews as $review): ?>
                            <tr>
                                <td><?= $review['id'] ?></td>
                                <td><?= htmlspecialchars($review['product_name']) ?></td>
                                <td><?= htmlspecialchars($review['user_name']) ?></td>
                                <td><?= $review['rating'] ?></td>
                                <td><?= htmlspecialchars($review['comment']) ?></td>
                                <td><?= $review['created_at'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 