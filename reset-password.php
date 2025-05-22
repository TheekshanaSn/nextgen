<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

$message = '';
$validToken = false;
$token = filter_input(INPUT_GET, 'token', FILTER_SANITIZE_STRING);

if (empty($token)) {
    header('Location: login.php');
    exit;
}

$db = new Database();
$conn = $db->getConnection();

// Verify token
$stmt = $conn->prepare("
    SELECT pr.*, u.email, u.name 
    FROM password_resets pr 
    JOIN users u ON pr.user_id = u.id 
    WHERE pr.token = ? AND pr.expires_at > NOW() AND pr.used = 0
");
$stmt->execute([$token]);
$reset = $stmt->fetch();

if ($reset) {
    $validToken = true;
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        if (empty($password) || empty($confirm_password)) {
            $message = 'Please fill in all fields.';
        } elseif ($password !== $confirm_password) {
            $message = 'Passwords do not match.';
        } elseif (strlen($password) < 8) {
            $message = 'Password must be at least 8 characters long.';
        } else {
            // Hash the new password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Update the user's password
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hashed_password, $reset['user_id']]);
            
            // Mark the reset token as used
            $stmt = $conn->prepare("UPDATE password_resets SET used = 1 WHERE id = ?");
            $stmt->execute([$reset['id']]);
            
            // Send confirmation email
            $to = $reset['email'];
            $subject = "Password Reset Successful";
            $message = "Hello " . $reset['name'] . ",\n\n";
            $message .= "Your password has been successfully reset.\n\n";
            $message .= "If you did not make this change, please contact support immediately.\n\n";
            $message .= "Best regards,\nYour Website Team";
            
            $headers = "From: noreply@yourwebsite.com\r\n";
            $headers .= "Reply-To: noreply@yourwebsite.com\r\n";
            $headers .= "X-Mailer: PHP/" . phpversion();
            
            send_mail($to, $subject, $message);
            
            // Redirect to login page
            header('Location: login.php?reset=success');
            exit;
        }
    }
} else {
    $message = 'Invalid or expired reset token.';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <div class="form-container">
            <h2>Reset Password</h2>
            <?php if (!empty($message)): ?>
                <div class="alert alert-error">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($validToken): ?>
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="password">New Password</label>
                        <input type="password" id="password" name="password" required minlength="8">
                    </div>
                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" required minlength="8">
                    </div>
                    <button type="submit" class="btn btn-primary">Reset Password</button>
                </form>
            <?php else: ?>
                <p>Please request a new password reset link.</p>
                <a href="forgot-password.php" class="btn btn-primary">Request Reset Link</a>
            <?php endif; ?>
            
            <div class="form-footer">
                <p>Remember your password? <a href="login.php">Login here</a></p>
            </div>
        </div>
    </div>
</body>
</html> 