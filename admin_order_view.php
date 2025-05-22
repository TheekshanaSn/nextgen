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
$orderId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$orderId) {
    header('Location: admin_orders.php');
    exit();
}

// Fetch order
$stmt = $conn->prepare("SELECT o.*, u.name as user_name, u.email as user_email FROM orders o JOIN users u ON o.user_id = u.id WHERE o.id = ?");
$stmt->execute([$orderId]);
$order = $stmt->fetch();
if (!$order) {
    header('Location: admin_orders.php');
    exit();
}
// Fetch order items
$itemStmt = $conn->prepare("SELECT oi.*, p.name FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
$itemStmt->execute([$orderId]);
$items = $itemStmt->fetchAll();
// Fetch notes
$notesStmt = $conn->prepare("SELECT n.*, u.name as admin_name FROM order_notes n JOIN users u ON n.admin_id = u.id WHERE n.order_id = ? ORDER BY n.created_at DESC");
$notesStmt->execute([$orderId]);
$orderNotes = $notesStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order #<?= $order['id'] ?> - Admin View</title>
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
        .badge {
            font-size: 1em;
            border-radius: 0.5em;
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
                <a href="admin.php" class="nav-link"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</a>
                <a href="admin_orders.php" class="nav-link active"><i class="fas fa-box me-2"></i>Order Management</a>
                <a href="admin_users.php" class="nav-link"><i class="fas fa-users me-2"></i>User Management</a>
                <a href="admin_products.php" class="nav-link"><i class="fas fa-mobile-alt me-2"></i>Product Management</a>
            </nav>
            <div class="mt-auto text-center">
                <a href="logout.php" class="btn btn-light w-100 mt-4"><i class="fas fa-sign-out-alt me-2"></i>Logout</a>
            </div>
        </div>
        <!-- Main Content -->
        <div class="col-md-10">
            <div class="dashboard-header d-flex justify-content-between align-items-center p-4 mb-4">
                <h1 class="h3 mb-0">Order #<?= $order['id'] ?> Details</h1>
                <a href="admin_orders.php" class="btn btn-primary"><i class="fas fa-arrow-left me-2"></i>Back to Orders</a>
            </div>
            <div class="container-fluid">
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="card p-4 mb-4">
                            <h5 class="mb-3"><i class="fas fa-user me-2"></i>User Info</h5>
                            <p><strong>Name:</strong> <?= htmlspecialchars($order['user_name']) ?></p>
                            <p><strong>Email:</strong> <?= htmlspecialchars($order['user_email']) ?></p>
                        </div>
                        <div class="card p-4 mb-4">
                            <h5 class="mb-3"><i class="fas fa-truck me-2"></i>Shipping Address</h5>
                            <p><?= nl2br(htmlspecialchars($order['shipping_address'])) ?></p>
                        </div>
                        <div class="card p-4 mb-4">
                            <h5 class="mb-3"><i class="fas fa-sticky-note me-2"></i>Order Notes</h5>
                            <?php if (empty($orderNotes)): ?>
                                <div class="text-muted">No notes yet.</div>
                            <?php else: ?>
                                <ul class="list-group mb-3">
                                    <?php foreach ($orderNotes as $note): ?>
                                        <li class="list-group-item">
                                            <strong><?= htmlspecialchars($note['admin_name']) ?>:</strong>
                                            <?= nl2br(htmlspecialchars($note['note'])) ?>
                                            <br><small class="text-muted"><?= $note['created_at'] ?></small>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                            <form method="POST" class="d-flex gap-2">
                                <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                <input type="text" name="note" class="form-control" placeholder="Add a note..." required>
                                <button type="submit" name="add_note" class="btn btn-primary">Add</button>
                            </form>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card p-4 mb-4">
                            <h5 class="mb-3"><i class="fas fa-info-circle me-2"></i>Order Info</h5>
                            <form method="POST" action="">
                                <p><strong>Status:</strong> 
                                    <span class="badge bg-<?php
                                        switch($order['status']) {
                                            case 'pending': echo 'secondary'; break;
                                            case 'processing': echo 'info'; break;
                                            case 'shipped': echo 'primary'; break;
                                            case 'delivered': echo 'success'; break;
                                            case 'cancelled': echo 'danger'; break;
                                            default: echo 'secondary';
                                        }
                                    ?>"> <?= htmlspecialchars(ucfirst($order['status'])) ?> </span>
                                </p>
                                <div class="d-flex align-items-center mb-3">
                                    <select name="new_status" class="form-select form-select-sm w-auto me-2">
                                        <option value="pending" <?= $order['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                                        <option value="processing" <?= $order['status'] === 'processing' ? 'selected' : '' ?>>Processing</option>
                                        <option value="shipped" <?= $order['status'] === 'shipped' ? 'selected' : '' ?>>Shipped</option>
                                        <option value="delivered" <?= $order['status'] === 'delivered' ? 'selected' : '' ?>>Delivered</option>
                                        <option value="cancelled" <?= $order['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                    </select>
                                    <button type="submit" name="update_status" class="btn btn-sm btn-primary">Update Status</button>
                                </div>
                                <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                            </form>
                            <p><strong>Total:</strong> LKR <?= number_format($order['total_amount'], 2) ?></p>
                            <p><strong>Created At:</strong> <?= htmlspecialchars($order['created_at']) ?></p>
                        </div>
                        <div class="card p-4 mb-4">
                            <h5 class="mb-3"><i class="fas fa-shipping-fast me-2"></i>Tracking Number</h5>
                            <form method="POST" class="d-flex gap-2 align-items-center">
                                <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                <input type="text" name="tracking_number" class="form-control" placeholder="Enter tracking number" value="<?= htmlspecialchars($order['tracking_number']) ?>">
                                <button type="submit" name="update_tracking" class="btn btn-primary">Update</button>
                            </form>
                            <?php if (!empty($order['tracking_number'])): ?>
                                <div class="mt-2"><strong>Current:</strong> <?= htmlspecialchars($order['tracking_number']) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="card p-4 mb-4">
                    <h5 class="mb-3"><i class="fas fa-list me-2"></i>Order Items</h5>
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Quantity</th>
                                    <th>Price</th>
                                    <th>Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($items as $item): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($item['name']) ?></td>
                                        <td><?= $item['quantity'] ?></td>
                                        <td>LKR <?= number_format($item['price'], 2) ?></td>
                                        <td>LKR <?= number_format($item['price'] * $item['quantity'], 2) ?></td>
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
</body>
</html>

<?php
// Handle status update and tracking number update
if (isset($_POST['update_status'], $_POST['order_id'], $_POST['new_status'])) {
    $newStatus = $_POST['new_status'];
    $orderId = (int)$_POST['order_id'];
    $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->execute([$newStatus, $orderId]);
    // Send email notification
    $to = $order['user_email'];
    $subject = "Order #$orderId Status Updated";
    $message = "Hello " . $order['user_name'] . ",\n\nYour order #$orderId status has been updated to: $newStatus.\n\nThank you for shopping with us!";
    @mail($to, $subject, $message);
    header("Location: admin_order_view.php?id=$orderId");
    exit();
}
if (isset($_POST['update_tracking'], $_POST['order_id'], $_POST['tracking_number'])) {
    $tracking = trim($_POST['tracking_number']);
    $orderId = (int)$_POST['order_id'];
    $stmt = $conn->prepare("UPDATE orders SET tracking_number = ? WHERE id = ?");
    $stmt->execute([$tracking, $orderId]);
    header("Location: admin_order_view.php?id=$orderId");
    exit();
}
// Handle add note
if (isset($_POST['add_note'], $_POST['order_id'], $_POST['note'])) {
    $note = trim($_POST['note']);
    $orderId = (int)$_POST['order_id'];
    $adminId = $_SESSION['user_id'];
    if ($note !== '') {
        $stmt = $conn->prepare("INSERT INTO order_notes (order_id, admin_id, note) VALUES (?, ?, ?)");
        $stmt->execute([$orderId, $adminId, $note]);
    }
    header("Location: admin_order_view.php?id=$orderId");
    exit();
} 