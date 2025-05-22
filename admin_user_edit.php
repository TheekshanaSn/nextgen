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
$userId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$userId) {
    header('Location: admin_users.php');
    exit();
}

// Fetch user
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();
if (!$user) {
    header('Location: admin_users.php');
    exit();
}

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitizeInput($_POST['name']);
    $email = sanitizeInput($_POST['email']);
    $phone = sanitizeInput($_POST['phone']);
    $address = sanitizeInput($_POST['address']);
    $role = $_POST['role'] === 'admin' ? 'admin' : 'user';
    $profile_picture = $user['profile_picture'];
    $change_password = !empty($_POST['new_password']);

    // Prevent admin from demoting themselves
    if ($userId === $_SESSION['user_id'] && $role !== 'admin') {
        $message = 'You cannot change your own role.';
        $messageType = 'danger';
    } else {
        // Handle profile picture upload
        if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            $max_size = 5 * 1024 * 1024; // 5MB
            if (!in_array($_FILES['profile_picture']['type'], $allowed_types)) {
                $message = 'Invalid file type. Please upload JPG, PNG or GIF';
                $messageType = 'danger';
            } elseif ($_FILES['profile_picture']['size'] > $max_size) {
                $message = 'File is too large. Maximum size is 5MB';
                $messageType = 'danger';
            } else {
                $upload_dir = 'uploads/profile_pictures/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                $file_extension = pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
                $new_filename = 'profile_' . $userId . '_' . time() . '.' . $file_extension;
                $upload_path = $upload_dir . $new_filename;
                if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $upload_path)) {
                    if ($user['profile_picture'] && file_exists($user['profile_picture'])) {
                        unlink($user['profile_picture']);
                    }
                    $profile_picture = $upload_path;
                } else {
                    $message = 'Error uploading profile picture';
                    $messageType = 'danger';
                }
            }
        }
        // Validate email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = 'Invalid email format';
            $messageType = 'danger';
        } else {
            // Check if email is already taken by another user
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$email, $userId]);
            if ($stmt->fetch()) {
                $message = 'Email is already taken';
                $messageType = 'danger';
            } else {
                // Update user details
                $updateSql = "UPDATE users SET name = ?, email = ?, phone = ?, address = ?, profile_picture = ?, role = ? WHERE id = ?";
                $conn->prepare($updateSql)->execute([$name, $email, $phone, $address, $profile_picture, $role, $userId]);
                // Change password if requested
                if ($change_password) {
                    $new_password = $_POST['new_password'];
                    $confirm_password = $_POST['confirm_password'];
                    if (strlen($new_password) < 6) {
                        $message = 'Password must be at least 6 characters long';
                        $messageType = 'danger';
                    } elseif ($new_password !== $confirm_password) {
                        $message = 'Passwords do not match';
                        $messageType = 'danger';
                    } else {
                        $hashed_password = hashPassword($new_password);
                        $conn->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([$hashed_password, $userId]);
                        $message = 'User updated and password changed successfully';
                        $messageType = 'success';
                    }
                } else {
                    $message = 'User updated successfully';
                    $messageType = 'success';
                }
                // Refresh user data
                $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
                $stmt->execute([$userId]);
                $user = $stmt->fetch();
                // Redirect after update
                header('Location: admin_users.php?msg=updated');
                exit();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User - Admin</title>
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
                <h1 class="h3 mb-0">Edit User</h1>
                <a href="admin_users.php" class="btn btn-primary"><i class="fas fa-arrow-left me-2"></i>Back to Users</a>
            </div>
            <div class="container-fluid">
                <div class="card p-4">
                    <?php if ($message): ?>
                        <div class="alert alert-<?= $messageType ?> alert-dismissible fade show">
                            <?= $message ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    <form method="POST" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-4 text-center mb-4">
                                <div class="position-relative d-inline-block">
                                    <?php if ($user['profile_picture']): ?>
                                        <img src="<?= htmlspecialchars($user['profile_picture']) ?>" class="rounded-circle mb-3" alt="Profile Picture" style="width: 150px; height: 150px; object-fit: cover;">
                                    <?php else: ?>
                                        <i class="fas fa-user-circle fa-5x text-primary mb-3"></i>
                                    <?php endif; ?>
                                    <label for="profile_picture" class="position-absolute bottom-0 end-0 bg-primary text-white rounded-circle p-2" style="cursor: pointer;">
                                        <i class="fas fa-camera"></i>
                                    </label>
                                    <input type="file" id="profile_picture" name="profile_picture" class="d-none" accept="image/*">
                                </div>
                                <div class="small text-muted">Click the camera icon to change profile picture</div>
                            </div>
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Full Name</label>
                                    <input type="text" class="form-control" id="name" name="name" value="<?= htmlspecialchars($user['name']) ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email Address</label>
                                    <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label for="phone" class="form-label">Phone Number</label>
                                    <input type="tel" class="form-control" id="phone" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                                </div>
                                <div class="mb-3">
                                    <label for="address" class="form-label">Address</label>
                                    <textarea class="form-control" id="address" name="address" rows="3"><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
                                </div>
                                <div class="mb-3">
                                    <label for="role" class="form-label">Role</label>
                                    <select class="form-select" id="role" name="role" <?= $userId === $_SESSION['user_id'] ? 'disabled' : '' ?>>
                                        <option value="user" <?= $user['role'] === 'user' ? 'selected' : '' ?>>User</option>
                                        <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                                    </select>
                                    <?php if ($userId === $_SESSION['user_id']): ?>
                                        <input type="hidden" name="role" value="admin">
                                    <?php endif; ?>
                                </div>
                                <div class="mb-3">
                                    <label for="new_password" class="form-label">New Password <span class="text-muted small">(leave blank to keep current)</span></label>
                                    <input type="password" class="form-control" id="new_password" name="new_password" autocomplete="new-password">
                                </div>
                                <div class="mb-3">
                                    <label for="confirm_password" class="form-label">Confirm New Password</label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" autocomplete="new-password">
                                </div>
                                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>Update User</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
    <script>
        // Preview profile picture before upload
        document.getElementById('profile_picture').addEventListener('change', function(e) {
            if (e.target.files && e.target.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const img = document.querySelector('.position-relative img') || 
                              document.querySelector('.position-relative i');
                    if (img.tagName === 'IMG') {
                        img.src = e.target.result;
                    } else {
                        const newImg = document.createElement('img');
                        newImg.src = e.target.result;
                        newImg.className = 'rounded-circle mb-3';
                        newImg.style.width = '150px';
                        newImg.style.height = '150px';
                        newImg.style.objectFit = 'cover';
                        img.parentNode.replaceChild(newImg, img);
                    }
                }
                reader.readAsDataURL(e.target.files[0]);
            }
        });
    </script>
</body>
</html> 