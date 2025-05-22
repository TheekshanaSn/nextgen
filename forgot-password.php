<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

$message = '';
$showPasswordForm = false;
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['email'])) {
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        if (empty($email)) {
            $message = 'Please enter your email address.';
        } else {
            $db = new Database();
            $conn = $db->getConnection();
            $stmt = $conn->prepare("SELECT id, name FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            if ($user) {
                $showPasswordForm = true;
            } else {
                $message = 'No account found with that email address.';
            }
        }
    } elseif (isset($_POST['new_password'], $_POST['confirm_password'], $_POST['email_hidden'])) {
        $email = filter_input(INPUT_POST, 'email_hidden', FILTER_SANITIZE_EMAIL);
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        if (empty($new_password) || empty($confirm_password)) {
            $message = 'Please fill in all fields.';
            $showPasswordForm = true;
        } elseif ($new_password !== $confirm_password) {
            $message = 'Passwords do not match.';
            $showPasswordForm = true;
        } elseif (strlen($new_password) < 8) {
            $message = 'Password must be at least 8 characters long.';
            $showPasswordForm = true;
        } else {
            $db = new Database();
            $conn = $db->getConnection();
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt->execute([$hashed_password, $email]);
            $message = 'Your password has been reset successfully. You can now <a href="login.php">login</a>.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - NEXTGEN</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow fade-in">
                    <div class="card-body p-5">
                        <h2 class="text-center mb-4">Forgot Password</h2>
                        <?php if (!empty($message)): ?>
                            <div class="alert <?php echo strpos($message, 'successfully') !== false ? 'alert-success' : 'alert-danger'; ?>">
                                <?php echo $message; ?>
                            </div>
                        <?php endif; ?>
                        <?php if ($showPasswordForm): ?>
                            <form method="POST" action="">
                                <input type="hidden" name="email_hidden" value="<?php echo htmlspecialchars($email); ?>">
                                <div class="mb-3">
                                    <label for="new_password" class="form-label">New Password</label>
                                    <input type="password" class="form-control" id="new_password" name="new_password" required minlength="8">
                                    <div class="form-text">Password must be at least 8 characters long</div>
                                </div>
                                <div class="mb-3">
                                    <label for="confirm_password" class="form-label">Confirm New Password</label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required minlength="8">
                                </div>
                                <button type="submit" class="btn btn-primary w-100">Reset Password</button>
                            </form>
                        <?php else: ?>
                            <form method="POST" action="">
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email Address</label>
                                    <input type="email" class="form-control" id="email" name="email" required>
                                </div>
                                <button type="submit" class="btn btn-primary w-100">Verify Email</button>
                            </form>
                        <?php endif; ?>
                        <div class="text-center mt-4">
                            <p>Remember your password? <a href="login.php">Login here</a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Custom JS -->
    <script src="assets/js/main.js"></script>
</body>
</html> 