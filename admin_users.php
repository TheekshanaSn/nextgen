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

// Handle user deletion
if (isset($_POST['delete_user_id'])) {
    $deleteId = (int)$_POST['delete_user_id'];
    // Prevent admin from deleting themselves
    if ($deleteId !== $_SESSION['user_id']) {
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$deleteId]);
    }
    header('Location: admin_users.php');
    exit();
}

// Fetch all users
$stmt = $conn->query("SELECT * FROM users ORDER BY created_at DESC");
$users = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - User Management</title>
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
        .btn-sm {
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
                <a href="admin.php" class="nav-link"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</a>
                <a href="admin_orders.php" class="nav-link"><i class="fas fa-box me-2"></i>Order Management</a>
                <a href="admin_users.php" class="nav-link active"><i class="fas fa-users me-2"></i>User Management</a>
                <a href="admin_products.php" class="nav-link"><i class="fas fa-mobile-alt me-2"></i>Product Management</a>
            </nav>
            <div class="mt-auto text-center">
                <a href="logout.php" class="btn btn-light w-100 mt-4"><i class="fas fa-sign-out-alt me-2"></i>Logout</a>
            </div>
        </div>
        <!-- Main Content -->
        <div class="col-md-10">
            <div class="dashboard-header d-flex justify-content-between align-items-center p-4 mb-4">
                <h1 class="h3 mb-0">User Management</h1>
                <a href="admin.php" class="btn btn-primary"><i class="fas fa-arrow-left me-2"></i>Back to Dashboard</a>
            </div>
            <div class="container-fluid">
                <div class="card p-4">
                    <?php if (empty($users)): ?>
                        <div class="alert alert-info">No users found.</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table align-middle">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Role</th>
                                        <th>Created At</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $user): ?>
                                        <tr>
                                            <td><?= $user['id'] ?></td>
                                            <td><?= htmlspecialchars($user['name']) ?></td>
                                            <td><?= htmlspecialchars($user['email']) ?></td>
                                            <td>
                                                <span class="badge bg-<?= $user['role'] === 'admin' ? 'primary' : 'secondary' ?>">
                                                    <?= htmlspecialchars(ucfirst($user['role'])) ?>
                                                </span>
                                            </td>
                                            <td><?= htmlspecialchars($user['created_at']) ?></td>
                                            <td>
                                                <a href="admin_user_edit.php?id=<?= $user['id'] ?>" class="btn btn-sm btn-info">Edit</a>
                                                <a href="admin_user_activity.php?id=<?= $user['id'] ?>" class="btn btn-sm btn-warning">View Activity</a>
                                                <?php if ($user['id'] !== $_SESSION['user_id']): ?>
                                                    <form method="POST" action="" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this user?');">
                                                        <input type="hidden" name="delete_user_id" value="<?= $user['id'] ?>">
                                                        <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                                    </form>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
</body>
</html> 