<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terms and Conditions - NEXTGEN</title>
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
            <div class="col-md-8">
                <div class="card shadow fade-in">
                    <div class="card-body p-5">
                        <h2 class="text-center mb-4">Terms and Conditions</h2>
                        <p>Welcome to NEXTGEN! By accessing or using our website, you agree to be bound by the following terms and conditions. Please read them carefully before registering or making any purchases.</p>
                        <h5>1. Account Registration</h5>
                        <ul>
                            <li>You must provide accurate and complete information during registration.</li>
                            <li>You are responsible for maintaining the confidentiality of your account and password.</li>
                            <li>Multiple accounts for the same user are not allowed.</li>
                        </ul>
                        <h5>2. Orders and Payments</h5>
                        <ul>
                            <li>All orders are subject to acceptance and availability.</li>
                            <li>Prices are subject to change without notice.</li>
                            <li>Payments must be made using the methods provided at checkout.</li>
                        </ul>
                        <h5>3. Shipping and Delivery</h5>
                        <ul>
                            <li>We aim to process and ship orders promptly, but delivery times may vary.</li>
                            <li>Shipping fees and policies are displayed at checkout.</li>
                        </ul>
                        <h5>4. Returns and Refunds</h5>
                        <ul>
                            <li>Returns are accepted within 7 days of delivery, subject to our return policy.</li>
                            <li>Refunds will be processed to the original payment method after inspection.</li>
                        </ul>
                        <h5>5. User Conduct</h5>
                        <ul>
                            <li>Users must not misuse the website or engage in fraudulent activities.</li>
                            <li>Reviews and comments must be respectful and not contain offensive content.</li>
                        </ul>
                        <h5>6. Privacy</h5>
                        <ul>
                            <li>Your personal information is handled according to our Privacy Policy.</li>
                        </ul>
                        <h5>7. Intellectual Property</h5>
                        <ul>
                            <li>All content, images, and trademarks on this site are the property of NEXTGEN or its partners.</li>
                        </ul>
                        <h5>8. Limitation of Liability</h5>
                        <ul>
                            <li>NEXTGEN is not liable for any indirect or consequential damages arising from the use of our site.</li>
                        </ul>
                        <h5>9. Changes to Terms</h5>
                        <ul>
                            <li>We reserve the right to update these terms at any time. Continued use of the site constitutes acceptance of the new terms.</li>
                        </ul>
                        <div class="text-center mt-4">
                            <!-- Return button removed as requested -->
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
    <!-- Return button script removed -->
</body>
</html> 