<?php
session_start();
// Simple admin check
if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header('Location: login.php');
    exit();
}
require_once 'includes/functions.php';
require_once 'config/database.php';
$conn = getDBConnection();

// Fetch summary counts
$orderCount = $conn->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$userCount = $conn->query("SELECT COUNT(*) FROM users")->fetchColumn();
$productCount = $conn->query("SELECT COUNT(*) FROM products")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - NEXTGEN</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --admin-primary: #4B49AC;
            --admin-secondary: #98BDFF;
            --admin-bg: #F4F6FC;
            --admin-card: #fff;
            --admin-sidebar: #4B49AC;
            --admin-sidebar-active: #fff;
            --admin-sidebar-text: #fff;
            --admin-sidebar-active-text: #4B49AC;
        }
        body {
            background: var(--admin-bg);
        }
        .sidebar {
            min-height: 100vh;
            background: var(--admin-sidebar);
            color: var(--admin-sidebar-text);
        }
        .sidebar .nav-link {
            color: var(--admin-sidebar-text);
            font-weight: 500;
            border-radius: 8px;
            margin-bottom: 0.5rem;
            transition: background 0.2s, color 0.2s;
        }
        .sidebar .nav-link.active, .sidebar .nav-link:hover {
            background: var(--admin-sidebar-active);
            color: var(--admin-sidebar-active-text);
        }
        .dashboard-header {
            background: var(--admin-card);
            border-bottom: 2px solid var(--admin-primary);
        }
        .card {
            border: none;
            border-radius: 1rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            background: var(--admin-card);
        }
        .card-icon {
            font-size: 2.5rem;
            color: var(--admin-primary);
        }
        .btn-primary, .sidebar .btn.btn-light {
            background: var(--admin-primary);
            color: var(--admin-sidebar-text);
            border: none;
        }
        .btn-primary:hover, .sidebar .btn.btn-light:hover {
            background: var(--admin-secondary);
            color: var(--admin-sidebar-active-text);
        }
        .sidebar .btn.btn-light {
            color: var(--admin-primary);
            font-weight: 600;
            background: var(--admin-sidebar-active);
        }
        .sidebar-logo {
            font-size:2rem;
            font-weight:700;
            letter-spacing:2px;
            color: var(--admin-primary);
            background: var(--admin-sidebar-active);
            padding:0.5rem 1.5rem;
            border-radius:1rem;
            display:inline-block;
            box-shadow:0 2px 8px rgba(0,0,0,0.05);
        }
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
                <h1 class="h3 mb-0">Admin Dashboard</h1>
                <span class="text-muted">Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</span>
            </div>
            <div class="container-fluid">
                <div class="row g-4 mb-4">
                    <div class="col-md-4">
                        <div class="card p-4 text-center">
                            <div class="card-icon mb-2"><i class="fas fa-box"></i></div>
                            <h5 class="mb-1">Orders</h5>
                            <h2 class="fw-bold"><?php echo $orderCount; ?></h2>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card p-4 text-center">
                            <div class="card-icon mb-2"><i class="fas fa-users"></i></div>
                            <h5 class="mb-1">Users</h5>
                            <h2 class="fw-bold"><?php echo $userCount; ?></h2>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card p-4 text-center">
                            <div class="card-icon mb-2"><i class="fas fa-mobile-alt"></i></div>
                            <h5 class="mb-1">Products</h5>
                            <h2 class="fw-bold"><?php echo $productCount; ?></h2>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-12">
                        <div class="card p-4">
                            <h4 class="mb-3"><i class="fas fa-info-circle me-2"></i>Quick Actions</h4>
                            <div class="d-flex flex-wrap gap-3">
                                <a href="admin_orders.php" class="btn btn-primary"><i class="fas fa-box me-2"></i>Manage Orders</a>
                                <a href="admin_orders.php?search_note=1" class="btn btn-warning"><i class="fas fa-sticky-note me-2"></i>Orders with Notes</a>
                                <a href="admin_orders.php?tracking=missing" class="btn btn-info"><i class="fas fa-shipping-fast me-2"></i>Missing Tracking</a>
                                <a href="admin_orders.php?bulk=1" class="btn btn-secondary"><i class="fas fa-tasks me-2"></i>Bulk Processing</a>
                                <a href="admin_orders.php?export_csv=1" class="btn btn-success"><i class="fas fa-file-csv me-2"></i>Export Orders</a>
                                <a href="admin_orders.php#filters" class="btn btn-dark"><i class="fas fa-search me-2"></i>Advanced Search</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
</body>
</html> 