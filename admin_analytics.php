<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Simple admin check
if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header('Location: login.php');
    exit();
}

$conn = getDBConnection();

// Get date range from request or default to last 30 days
$end_date = date('Y-m-d');
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));

// Calculate total income from all sold products
$total_income_query = "SELECT SUM(total_amount) as total_income FROM orders WHERE status IN ('completed', 'shipped', 'delivered')";
$total_income_stmt = $conn->query($total_income_query);
$total_income = $total_income_stmt->fetch(PDO::FETCH_ASSOC)['total_income'] ?? 0;

// Calculate total number of sold products (sum of all quantities sold)
$total_sold_products_query = "SELECT SUM(oi.quantity) as total_sold_products FROM order_items oi JOIN orders o ON oi.order_id = o.id WHERE o.status IN ('completed', 'shipped', 'delivered')";
$total_sold_products_stmt = $conn->query($total_sold_products_query);
$total_sold_products = $total_sold_products_stmt->fetch(PDO::FETCH_ASSOC)['total_sold_products'] ?? 0;

// Revenue Analytics
$revenue_query = "SELECT DATE(created_at) as date, SUM(total_amount) as revenue FROM orders WHERE status IN ('completed', 'shipped', 'delivered') AND created_at BETWEEN ? AND ? GROUP BY DATE(created_at) ORDER BY date";
$stmt = $conn->prepare($revenue_query);
$stmt->execute([$start_date, $end_date]);
$revenue_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Product Performance
$product_performance = "SELECT p.name, COUNT(oi.product_id) as total_sold, SUM(oi.quantity) as quantity_sold, p.stock as stock_quantity FROM order_items oi JOIN products p ON oi.product_id = p.id JOIN orders o ON oi.order_id = o.id WHERE o.status IN ('completed', 'shipped', 'delivered') GROUP BY p.id ORDER BY total_sold DESC LIMIT 10";
$product_data = $conn->query($product_performance)->fetchAll(PDO::FETCH_ASSOC);

// Customer Analytics
$customer_analytics = "SELECT COUNT(DISTINCT CASE WHEN order_count = 1 THEN user_id END) as new_customers, COUNT(DISTINCT CASE WHEN order_count > 1 THEN user_id END) as returning_customers, AVG(total_spent) as avg_customer_value FROM (SELECT user_id, COUNT(*) as order_count, SUM(total_amount) as total_spent FROM orders WHERE status IN ('completed', 'shipped', 'delivered') GROUP BY user_id) as customer_stats";
$customer_data = $conn->query($customer_analytics)->fetch(PDO::FETCH_ASSOC);

// Order Status Distribution
$order_status = "SELECT status, COUNT(*) as count FROM orders GROUP BY status";
$status_data = $conn->query($order_status)->fetchAll(PDO::FETCH_ASSOC);

// Revenue Forecasting (Simple moving average)
$forecast_query = "SELECT DATE(created_at) as date, AVG(total_amount) OVER (ORDER BY created_at ROWS BETWEEN 6 PRECEDING AND CURRENT ROW) as forecast FROM orders WHERE status IN ('completed', 'shipped', 'delivered') AND created_at BETWEEN ? AND ? GROUP BY DATE(created_at) ORDER BY date";
$stmt = $conn->prepare($forecast_query);
$stmt->execute([$start_date, $end_date]);
$forecast_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics Dashboard - NEXTGEN</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
            margin-bottom: 1.5rem;
        }
        .metric-card {
            text-align: center;
            padding: 1.5rem;
        }
        .metric-value {
            font-size: 2rem;
            font-weight: bold;
            color: var(--admin-primary);
        }
        .metric-label {
            color: #666;
            font-size: 0.9rem;
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
                <h1 class="h3 mb-0">Analytics Dashboard</h1>
                <div>
                    <form class="d-flex gap-2">
                        <input type="date" class="form-control" name="start_date" value="<?= $start_date ?>">
                        <input type="date" class="form-control" name="end_date" value="<?= $end_date ?>">
                        <button type="submit" class="btn btn-primary">Update</button>
                    </form>
                </div>
            </div>

            <div class="container-fluid">
                <!-- Key Metrics -->
                <div class="row">
                    <div class="col-md-3">
                        <div class="card metric-card">
                            <div class="metric-value">LKR <?= number_format($total_income, 2) ?></div>
                            <div class="metric-label">Total Income</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card metric-card">
                            <div class="metric-value"><?= number_format($total_sold_products) ?></div>
                            <div class="metric-label">Total Sold Products</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card metric-card">
                            <div class="metric-value"><?= number_format($customer_data['new_customers']) ?></div>
                            <div class="metric-label">New Customers</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card metric-card">
                            <div class="metric-value"><?= number_format($customer_data['returning_customers']) ?></div>
                            <div class="metric-label">Returning Customers</div>
                        </div>
                    </div>
                </div>

                <!-- Revenue Chart -->
                <div class="card p-4">
                    <h4>Revenue Analytics</h4>
                    <canvas id="revenueChart"></canvas>
                </div>

                <!-- Product Performance -->
                <div class="card p-4">
                    <h4>Top Selling Products</h4>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Total Sold</th>
                                    <th>Quantity</th>
                                    <th>Stock Level</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($product_data as $product): ?>
                                <tr>
                                    <td><?= htmlspecialchars($product['name']) ?></td>
                                    <td><?= $product['total_sold'] ?></td>
                                    <td><?= $product['quantity_sold'] ?></td>
                                    <td><?= $product['stock_quantity'] ?></td>
                                    <td>
                                        <?php if ($product['stock_quantity'] <= 5): ?>
                                            <span class="badge bg-danger">Low Stock</span>
                                        <?php elseif ($product['stock_quantity'] <= 10): ?>
                                            <span class="badge bg-warning">Medium Stock</span>
                                        <?php else: ?>
                                            <span class="badge bg-success">In Stock</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Order Status Distribution -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="card p-4">
                            <h4>Order Status Distribution</h4>
                            <canvas id="orderStatusChart"></canvas>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card p-4">
                            <h4>Revenue Forecast</h4>
                            <canvas id="forecastChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Revenue Chart
        const revenueCtx = document.getElementById('revenueChart').getContext('2d');
        new Chart(revenueCtx, {
            type: 'line',
            data: {
                labels: <?= json_encode(array_column($revenue_data, 'date')) ?>,
                datasets: [{
                    label: 'Daily Revenue',
                    data: <?= json_encode(array_column($revenue_data, 'revenue')) ?>,
                    borderColor: '#4B49AC',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    title: {
                        display: true,
                        text: 'Daily Revenue'
                    }
                }
            }
        });

        // Order Status Chart
        const statusCtx = document.getElementById('orderStatusChart').getContext('2d');
        new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode(array_column($status_data, 'status')) ?>,
                datasets: [{
                    data: <?= json_encode(array_column($status_data, 'count')) ?>,
                    backgroundColor: [
                        '#4B49AC',
                        '#98BDFF',
                        '#FFB74D',
                        '#4CAF50',
                        '#F44336'
                    ]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    title: {
                        display: true,
                        text: 'Order Status Distribution'
                    }
                }
            }
        });

        // Forecast Chart
        const forecastCtx = document.getElementById('forecastChart').getContext('2d');
        new Chart(forecastCtx, {
            type: 'line',
            data: {
                labels: <?= json_encode(array_column($forecast_data, 'date')) ?>,
                datasets: [{
                    label: 'Revenue Forecast',
                    data: <?= json_encode(array_column($forecast_data, 'forecast')) ?>,
                    borderColor: '#4CAF50',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    title: {
                        display: true,
                        text: '7-Day Moving Average Forecast'
                    }
                }
            }
        });
    </script>
</body>
</html> 