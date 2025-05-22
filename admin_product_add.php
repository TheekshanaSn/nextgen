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
$message = '';
$messageType = '';

// Get categories for dropdown
$categories = $conn->query("SELECT * FROM categories ORDER BY name")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitizeInput($_POST['name']);
    $brand = sanitizeInput($_POST['brand']);
    $category_id = (int)$_POST['category_id'];
    $description = sanitizeInput($_POST['description']);
    $price = (float)$_POST['price'];
    $stock = (int)$_POST['stock'];
    $image = '';

    // Validate required fields
    if (empty($name) || empty($brand) || empty($category_id) || empty($price)) {
        $message = 'Please fill in all required fields';
        $messageType = 'danger';
    } else {
        // Handle image upload
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            $max_size = 5 * 1024 * 1024; // 5MB

            if (!in_array($_FILES['image']['type'], $allowed_types)) {
                $message = 'Invalid file type. Please upload JPG, PNG or GIF';
                $messageType = 'danger';
            } elseif ($_FILES['image']['size'] > $max_size) {
                $message = 'File is too large. Maximum size is 5MB';
                $messageType = 'danger';
            } else {
                $upload_dir = 'uploads/products/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }

                $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                $new_filename = 'product_' . time() . '.' . $file_extension;
                $upload_path = $upload_dir . $new_filename;

                if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                    $image = $upload_path;
                } else {
                    $message = 'Error uploading image';
                    $messageType = 'danger';
                }
            }
        } else {
            $message = 'Please select an image';
            $messageType = 'danger';
        }

        if (empty($message)) {
            // Insert product
            $stmt = $conn->prepare("INSERT INTO products (name, brand, category_id, description, price, image, stock) VALUES (?, ?, ?, ?, ?, ?, ?)");
            if ($stmt->execute([$name, $brand, $category_id, $description, $price, $image, $stock])) {
                header('Location: admin_products.php?msg=added');
                exit();
            } else {
                $message = 'Error adding product';
                $messageType = 'danger';
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
    <title>Add Product - Admin</title>
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
        .image-preview {
            width: 200px;
            height: 200px;
            object-fit: cover;
            border-radius: 8px;
            display: none;
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
                <a href="admin_users.php" class="nav-link"><i class="fas fa-users me-2"></i>User Management</a>
                <a href="admin_products.php" class="nav-link active"><i class="fas fa-mobile-alt me-2"></i>Product Management</a>
            </nav>
            <div class="mt-auto text-center">
                <a href="logout.php" class="btn btn-light w-100 mt-4"><i class="fas fa-sign-out-alt me-2"></i>Logout</a>
            </div>
        </div>
        <!-- Main Content -->
        <div class="col-md-10">
            <div class="dashboard-header d-flex justify-content-between align-items-center p-4 mb-4">
                <h1 class="h3 mb-0">Add New Product</h1>
                <a href="admin_products.php" class="btn btn-primary"><i class="fas fa-arrow-left me-2"></i>Back to Products</a>
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
                            <div class="col-md-4">
                                <div class="mb-4">
                                    <label for="image" class="form-label">Product Image</label>
                                    <input type="file" class="form-control" id="image" name="image" accept="image/*" required>
                                    <img id="imagePreview" class="image-preview mt-2" alt="Image Preview">
                                </div>
                            </div>
                            <div class="col-md-8">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="name" class="form-label">Product Name</label>
                                        <input type="text" class="form-control" id="name" name="name" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="brand" class="form-label">Brand</label>
                                        <input type="text" class="form-control" id="brand" name="brand" required>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="category_id" class="form-label">Category</label>
                                        <select class="form-select" id="category_id" name="category_id" required>
                                            <option value="">Select Category</option>
                                            <?php foreach ($categories as $category): ?>
                                                <option value="<?= $category['id'] ?>"><?= htmlspecialchars($category['name']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="price" class="form-label">Price</label>
                                        <div class="input-group">
                                            <span class="input-group-text">Rs</span>
                                            <input type="number" class="form-control" id="price" name="price" step="0.01" min="0" required>
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="stock" class="form-label">Stock</label>
                                    <input type="number" class="form-control" id="stock" name="stock" min="0" value="0" required>
                                </div>
                                <div class="mb-3">
                                    <label for="description" class="form-label">Description</label>
                                    <textarea class="form-control" id="description" name="description" rows="4"></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>Add Product</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Image preview
        document.getElementById('image').addEventListener('change', function(e) {
            const preview = document.getElementById('imagePreview');
            const file = e.target.files[0];
            
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                }
                reader.readAsDataURL(file);
            } else {
                preview.style.display = 'none';
            }
        });
    </script>
</body>
</html> 