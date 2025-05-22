<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Simple admin check (adjust as needed)
if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header('Location: login.php');
    exit();
}

$conn = getDBConnection();

// Handle order status update
if (isset($_POST['order_id'], $_POST['new_status'])) {
    $orderId = (int)$_POST['order_id'];
    $newStatus = $_POST['new_status'];
    $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->execute([$newStatus, $orderId]);
    header('Location: admin_orders.php');
    exit();
}

// Handle bulk actions
if (isset($_POST['bulk_action'], $_POST['order_ids']) && is_array($_POST['order_ids'])) {
    $orderIds = array_map('intval', $_POST['order_ids']);
    $inQuery = implode(',', array_fill(0, count($orderIds), '?'));
    $bulkAction = $_POST['bulk_action'];
    if (strpos($bulkAction, 'status_') === 0) {
        $newStatus = substr($bulkAction, 7);
        $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id IN ($inQuery)");
        $stmt->execute(array_merge([$newStatus], $orderIds));
    } elseif ($bulkAction === 'delete') {
        $stmt = $conn->prepare("DELETE FROM orders WHERE id IN ($inQuery)");
        $stmt->execute($orderIds);
    }
    header('Location: admin_orders.php');
    exit();
}

// Filtering and search
$where = [];
$params = [];
if (!empty($_GET['status'])) {
    $where[] = 'status = ?';
    $params[] = $_GET['status'];
}
if (!empty($_GET['date_from'])) {
    $where[] = 'DATE(created_at) >= ?';
    $params[] = $_GET['date_from'];
}
if (!empty($_GET['date_to'])) {
    $where[] = 'DATE(created_at) <= ?';
    $params[] = $_GET['date_to'];
}
if (!empty($_GET['user_id'])) {
    $where[] = 'user_id = ?';
    $params[] = $_GET['user_id'];
}
if (!empty($_GET['search'])) {
    $where[] = '(id = ? OR user_id = ?)';
    $params[] = $_GET['search'];
    $params[] = $_GET['search'];
}
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

// Export CSV
if (isset($_GET['export_csv']) && $_GET['export_csv'] == 1) {
    $stmt = $conn->prepare("SELECT * FROM orders $whereSql ORDER BY created_at DESC");
    $stmt->execute($params);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename=orders_export.csv');
    $out = fopen('php://output', 'w');
    fputcsv($out, array_keys(reset($orders)));
    foreach ($orders as $order) {
        fputcsv($out, $order);
    }
    fclose($out);
    exit();
}

// Fetch filtered orders
$stmt = $conn->prepare("SELECT * FROM orders $whereSql ORDER BY created_at DESC");
$stmt->execute($params);
$orders = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Order Management</title>
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
        .table thead th {
            background: var(--admin-secondary);
            color: var(--admin-sidebar-active-text);
            border: none;
        }
        .table tbody tr {
            background: var(--admin-card);
        }
        .table tbody tr td {
            vertical-align: middle;
        }
        .form-select, .btn-sm {
            border-radius: 20px;
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
                <h1 class="h3 mb-0">Order Management</h1>
                <a href="admin.php" class="btn btn-primary"><i class="fas fa-arrow-left me-2"></i>Back to Dashboard</a>
            </div>
            <div class="container-fluid">
                <div class="card p-4">
                    <!-- Bulk Actions, Filters, and Export -->
                    <form method="GET" class="row g-2 mb-3 align-items-end">
                        <div class="col-md-2">
                            <label>Status</label>
                            <select name="status" class="form-select">
                                <option value="">All</option>
                                <option value="pending" <?= isset($_GET['status']) && $_GET['status']==='pending'?'selected':'' ?>>Pending</option>
                                <option value="processing" <?= isset($_GET['status']) && $_GET['status']==='processing'?'selected':'' ?>>Processing</option>
                                <option value="shipped" <?= isset($_GET['status']) && $_GET['status']==='shipped'?'selected':'' ?>>Shipped</option>
                                <option value="delivered" <?= isset($_GET['status']) && $_GET['status']==='delivered'?'selected':'' ?>>Delivered</option>
                                <option value="cancelled" <?= isset($_GET['status']) && $_GET['status']==='cancelled'?'selected':'' ?>>Cancelled</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label>Date From</label>
                            <input type="date" name="date_from" class="form-control" value="<?= isset($_GET['date_from']) ? $_GET['date_from'] : '' ?>">
                        </div>
                        <div class="col-md-2">
                            <label>Date To</label>
                            <input type="date" name="date_to" class="form-control" value="<?= isset($_GET['date_to']) ? $_GET['date_to'] : '' ?>">
                        </div>
                        <div class="col-md-2">
                            <label>User ID</label>
                            <input type="text" name="user_id" class="form-control" placeholder="User ID" value="<?= isset($_GET['user_id']) ? $_GET['user_id'] : '' ?>">
                        </div>
                        <div class="col-md-2">
                            <label>Search</label>
                            <input type="text" name="search" class="form-control" placeholder="Order ID or Name" value="<?= isset($_GET['search']) ? $_GET['search'] : '' ?>">
                        </div>
                        <div class="col-md-2 d-flex gap-2">
                            <button type="submit" class="btn btn-primary">Filter</button>
                            <button type="submit" name="export_csv" value="1" class="btn btn-success">Export CSV</button>
                        </div>
                    </form>
                    <!-- Bulk Actions Form -->
                    <form method="POST" id="bulk-action-form">
                        <div class="d-flex mb-2 gap-2">
                            <select name="bulk_action" class="form-select w-auto">
                                <option value="">Bulk Actions</option>
                                <option value="status_pending">Mark as Pending</option>
                                <option value="status_processing">Mark as Processing</option>
                                <option value="status_shipped">Mark as Shipped</option>
                                <option value="status_delivered">Mark as Delivered</option>
                                <option value="status_cancelled">Mark as Cancelled</option>
                                <option value="delete">Delete</option>
                            </select>
                            <button type="submit" class="btn btn-secondary">Apply</button>
                        </div>
                        <div class="table-responsive">
                            <table class="table align-middle">
                                <thead>
                                    <tr>
                                        <th><input type="checkbox" id="select-all"></th>
                                        <th>ID</th>
                                        <th>User ID</th>
                                        <th>Total</th>
                                        <th>Status</th>
                                        <th>Created At</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($orders as $order): ?>
                                        <tr>
                                            <td><input type="checkbox" name="order_ids[]" value="<?= $order['id'] ?>"></td>
                                            <td><?= $order['id'] ?></td>
                                            <td><?= $order['user_id'] ?></td>
                                            <td>LKR <?= number_format($order['total_amount'], 2) ?></td>
                                            <td>
                                                <span class="badge bg-<?php
                                                    switch($order['status']) {
                                                        case 'pending': echo 'secondary'; break;
                                                        case 'processing': echo 'info'; break;
                                                        case 'shipped': echo 'primary'; break;
                                                        case 'delivered': echo 'success'; break;
                                                        case 'cancelled': echo 'danger'; break;
                                                        default: echo 'secondary';
                                                    }
                                                ?>">
                                                    <?= htmlspecialchars(ucfirst($order['status'])) ?>
                                                </span>
                                            </td>
                                            <td><?= htmlspecialchars($order['created_at']) ?></td>
                                            <td>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                                    <select name="new_status" class="form-select form-select-sm d-inline w-auto">
                                                        <option value="pending" <?= $order['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                                                        <option value="processing" <?= $order['status'] === 'processing' ? 'selected' : '' ?>>Processing</option>
                                                        <option value="shipped" <?= $order['status'] === 'shipped' ? 'selected' : '' ?>>Shipped</option>
                                                        <option value="delivered" <?= $order['status'] === 'delivered' ? 'selected' : '' ?>>Delivered</option>
                                                        <option value="cancelled" <?= $order['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                                    </select>
                                                    <button type="submit" class="btn btn-sm btn-primary">Update</button>
                                                </form>
                                                <a href="admin_order_view.php?id=<?= $order['id'] ?>" class="btn btn-sm btn-info ms-1">View</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
    <script>
    // Select All Checkbox Logic
    const selectAll = document.getElementById('select-all');
    if (selectAll) {
        selectAll.addEventListener('change', function() {
            document.querySelectorAll('input[name="order_ids[]"]').forEach(cb => {
                cb.checked = selectAll.checked;
            });
        });
    }
    </script>
</body>
</html> 