<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header('Location: login.php');
    exit();
}

$conn = getDBConnection();
// Fetch all activity logs with user name
$stmt = $conn->query("SELECT l.*, u.name as user_name FROM user_activity_logs l JOIN users u ON l.user_id = u.id ORDER BY l.created_at DESC");
$logs = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Logs - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --admin-primary: #4B49AC;
            --admin-secondary: #98BDFF;
            --admin-bg: #F4F6FC;
            --admin-card: #fff;
        }
        body { background: var(--admin-bg); }
        .card { border: none; border-radius: 1rem; box-shadow: 0 2px 8px rgba(0,0,0,0.05); background: var(--admin-card); }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="card p-4">
            <h2 class="mb-4">Audit Logs (All User/Admin Actions)</h2>
            <a href="admin.php" class="btn btn-secondary mb-3"><i class="fas fa-arrow-left me-2"></i>Back to Dashboard</a>
            <?php if (empty($logs)): ?>
                <div class="alert alert-info">No audit logs found.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Action</th>
                                <th>Details</th>
                                <th>Date/Time</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td><?= htmlspecialchars($log['user_name']) ?> (ID: <?= $log['user_id'] ?>)</td>
                                    <td><?= htmlspecialchars($log['action']) ?></td>
                                    <td><?= nl2br(htmlspecialchars($log['details'])) ?></td>
                                    <td><?= htmlspecialchars($log['created_at']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html> 