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
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Handle form submission
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $name = sanitizeInput($_POST['name']);
        $email = sanitizeInput($_POST['email']);
        $phone = sanitizeInput($_POST['phone']);
        $address = sanitizeInput($_POST['address']);
        
        // Handle profile picture upload
        $profile_picture = $user['profile_picture']; // Keep existing picture by default
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
                $new_filename = 'profile_' . $user_id . '_' . time() . '.' . $file_extension;
                $upload_path = $upload_dir . $new_filename;
                
                if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $upload_path)) {
                    // Delete old profile picture if exists
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
            try {
                // Check if email is already taken by another user
                $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
                $stmt->execute([$email, $user_id]);
                if ($stmt->fetch()) {
                    $message = 'Email is already taken';
                    $messageType = 'danger';
                } else {
                    // Update user details
                    $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, phone = ?, address = ?, profile_picture = ? WHERE id = ?");
                    $stmt->execute([$name, $email, $phone, $address, $profile_picture, $user_id]);
                    
                    $message = 'Profile updated successfully';
                    $messageType = 'success';
                    
                    // Refresh user data
                    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
                    $stmt->execute([$user_id]);
                    $user = $stmt->fetch();
                }
            } catch (Exception $e) {
                $message = 'Error updating profile';
                $messageType = 'danger';
            }
        }
    } elseif (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Verify current password
        if (!verifyPassword($current_password, $user['password'])) {
            $message = 'Current password is incorrect';
            $messageType = 'danger';
        } elseif ($new_password !== $confirm_password) {
            $message = 'New passwords do not match';
            $messageType = 'danger';
        } elseif (strlen($new_password) < 6) {
            $message = 'Password must be at least 6 characters long';
            $messageType = 'danger';
        } else {
            try {
                // Update password
                $hashed_password = hashPassword($new_password);
                $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([$hashed_password, $user_id]);
                
                $message = 'Password changed successfully';
                $messageType = 'success';
            } catch (Exception $e) {
                $message = 'Error changing password';
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
    <title>My Profile - NEXTGEN</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <!-- Header -->
    <?php include 'includes/header.php'; ?>

    <div class="container py-5">
        <div class="row">
            <!-- Profile Navigation -->
            <div class="col-md-3 mb-4">
                <div class="card">
                    <div class="card-body">
                        <div class="text-center mb-4">
                            <?php if ($user['profile_picture']): ?>
                                <img src="<?php echo htmlspecialchars($user['profile_picture']); ?>" 
                                     class="rounded-circle mb-3" 
                                     alt="Profile Picture"
                                     style="width: 150px; height: 150px; object-fit: cover;">
                            <?php else: ?>
                                <i class="fas fa-user-circle fa-5x text-primary mb-3"></i>
                            <?php endif; ?>
                            <h5 class="card-title"><?php echo htmlspecialchars($user['name']); ?></h5>
                            <p class="text-muted"><?php echo htmlspecialchars($user['email']); ?></p>
                        </div>
                        <div class="list-group">
                            <a href="#profile" class="list-group-item list-group-item-action active" data-bs-toggle="list">
                                <i class="fas fa-user me-2"></i>Profile
                            </a>
                            <a href="#password" class="list-group-item list-group-item-action" data-bs-toggle="list">
                                <i class="fas fa-key me-2"></i>Change Password
                            </a>
                            <a href="orders.php" class="list-group-item list-group-item-action">
                                <i class="fas fa-shopping-bag me-2"></i>My Orders
                            </a>
                            <a href="wishlist.php" class="list-group-item list-group-item-action">
                                <i class="fas fa-heart me-2"></i>Wishlist
                            </a>
                            <a href="logout.php" class="list-group-item list-group-item-action text-danger">
                                <i class="fas fa-sign-out-alt me-2"></i>Logout
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Profile Content -->
            <div class="col-md-9">
                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show">
                        <?php echo $message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <div class="tab-content">
                    <!-- Profile Details -->
                    <div class="tab-pane fade show active" id="profile">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Profile Information</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="profile.php" enctype="multipart/form-data">
                                    <div class="text-center mb-4">
                                        <div class="position-relative d-inline-block">
                                            <?php if ($user['profile_picture']): ?>
                                                <img src="<?php echo htmlspecialchars($user['profile_picture']); ?>" 
                                                     class="rounded-circle mb-3" 
                                                     alt="Profile Picture"
                                                     style="width: 150px; height: 150px; object-fit: cover;">
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
                                    
                                    <div class="mb-3">
                                        <label for="name" class="form-label">Full Name</label>
                                        <input type="text" class="form-control" id="name" name="name" 
                                               value="<?php echo htmlspecialchars($user['name']); ?>" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="email" class="form-label">Email Address</label>
                                        <input type="email" class="form-control" id="email" name="email" 
                                               value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="phone" class="form-label">Phone Number</label>
                                        <input type="tel" class="form-control" id="phone" name="phone" 
                                               value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="address" class="form-label">Address</label>
                                        <textarea class="form-control" id="address" name="address" rows="3"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                                    </div>
                                    
                                    <button type="submit" name="update_profile" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>Update Profile
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Change Password -->
                    <div class="tab-pane fade" id="password">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Change Password</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="profile.php">
                                    <div class="mb-3">
                                        <label for="current_password" class="form-label">Current Password</label>
                                        <input type="password" class="form-control" id="current_password" 
                                               name="current_password" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="new_password" class="form-label">New Password</label>
                                        <input type="password" class="form-control" id="new_password" 
                                               name="new_password" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="confirm_password" class="form-label">Confirm New Password</label>
                                        <input type="password" class="form-control" id="confirm_password" 
                                               name="confirm_password" required>
                                    </div>
                                    
                                    <button type="submit" name="change_password" class="btn btn-primary">
                                        <i class="fas fa-key me-2"></i>Change Password
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <?php include 'includes/footer.php'; ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Custom JS -->
    <script src="assets/js/main.js"></script>
    
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

