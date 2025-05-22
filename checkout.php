<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    $_SESSION['redirect_after_login'] = 'checkout.php';
    header('Location: login.php');
    exit();
}

// Get user details
$userId = $_SESSION['user_id'];
$conn = getDBConnection();
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

// Get cart items
$cart = getCart();
$cartItems = [];
$total = 0;

if (!empty($cart)) {
    foreach ($cart as $productId => $quantity) {
        $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([$productId]);
        $product = $stmt->fetch();
        
        if ($product) {
            $product['quantity'] = $quantity;
            $product['subtotal'] = $product['price'] * $quantity;
            $cartItems[] = $product;
            $total += $product['subtotal'];
        }
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    $paymentMethod = $_POST['payment_method'] ?? 'card';
    $cardNumber = $_POST['card_number'] ?? '';
    $expiryDate = $_POST['expiry_date'] ?? '';
    $cvv = $_POST['cvv'] ?? '';
    $paypalEmail = $_POST['paypal_email'] ?? '';
    $bankRef = $_POST['bank_ref'] ?? '';
    
    // Get shipping details from POST or use user's default values
    $shippingName = $_POST['shipping_name'] ?? $user['name'];
    $shippingEmail = $_POST['shipping_email'] ?? $user['email'];
    $shippingPhone = $_POST['shipping_phone'] ?? $user['phone'];
    $shippingAddress = $_POST['shipping_address'] ?? $user['address'];
    $shippingCity = $_POST['shipping_city'] ?? $user['city'];
    
    // Basic card validation
    $isValid = true;
    $errors = [];
    
    // Validate shipping information
    if (empty($shippingName)) {
        $isValid = false;
        $errors[] = "Full name is required";
    }
    if (empty($shippingEmail)) {
        $isValid = false;
        $errors[] = "Email is required";
    }
    if (empty($shippingPhone)) {
        $isValid = false;
        $errors[] = "Phone number is required";
    }
    if (empty($shippingAddress)) {
        $isValid = false;
        $errors[] = "Address is required";
    }
    if (empty($shippingCity)) {
        $isValid = false;
        $errors[] = "City is required";
    }
    
    // Validate payment method
    if ($paymentMethod === 'card') {
        // Validate card number (16 digits)
        if (!preg_match('/^\d{16}$/', $cardNumber)) {
            $isValid = false;
            $errors[] = "Invalid card number";
        }
        // Validate expiry date (MM/YY format)
        if (!preg_match('/^(0[1-9]|1[0-2])\/([0-9]{2})$/', $expiryDate)) {
            $isValid = false;
            $errors[] = "Invalid expiry date";
        }
        // Validate CVV (3-4 digits)
        if (!preg_match('/^\d{3,4}$/', $cvv)) {
            $isValid = false;
            $errors[] = "Invalid CVV";
        }
    } elseif ($paymentMethod === 'paypal') {
        if (!filter_var($paypalEmail, FILTER_VALIDATE_EMAIL)) {
            $isValid = false;
            $errors[] = "Invalid PayPal email";
        }
    } elseif ($paymentMethod === 'bank') {
        if (empty($bankRef)) {
            $isValid = false;
            $errors[] = "Bank reference is required";
        }
    }
    
    if ($isValid) {
        try {
            // Start transaction
            $conn->beginTransaction();
            
            // Create order with proper error handling
            $stmt = $conn->prepare("INSERT INTO orders (user_id, total_amount, status, shipping_address, payment_method, created_at) VALUES (?, ?, 'pending', ?, ?, NOW())");
            
            if (!$stmt->execute([$userId, $total, $shippingAddress, $paymentMethod])) {
                throw new Exception("Failed to create order: " . implode(" ", $stmt->errorInfo()));
            }
            
            $orderId = $conn->lastInsertId();
            
            if (!$orderId) {
                throw new Exception("Failed to get order ID");
            }
            
            // Create order items with error handling
            $stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
            
            foreach ($cartItems as $item) {
                if (!$stmt->execute([$orderId, $item['id'], $item['quantity'], $item['price']])) {
                    throw new Exception("Failed to add order item: " . implode(" ", $stmt->errorInfo()));
                }
            }
            
            // Commit transaction
            $conn->commit();
            
            // Clear cart by removing all items from session
            $_SESSION['cart'] = [];
            
            // Redirect to success page
            header('Location: order-success.php?order_id=' . $orderId);
            exit();
            
        } catch (Exception $e) {
            // Rollback transaction on error
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            
            // Log the error for debugging
            error_log("Order processing error: " . $e->getMessage());
            
            $errors[] = "An error occurred while processing your order: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - NEXTGEN</title>
    
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
        <h1 class="mb-4">Checkout</h1>
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <div class="row">
            <!-- Checkout Form -->
            <div class="col-md-8">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Shipping Information</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="checkout.php" id="checkoutForm">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="shipping_name" class="form-label">Full Name</label>
                                    <input type="text" class="form-control" id="shipping_name" name="shipping_name" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="shipping_email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="shipping_email" name="shipping_email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="shipping_phone" class="form-label">Phone</label>
                                    <input type="tel" class="form-control" id="shipping_phone" name="shipping_phone" value="<?php echo htmlspecialchars($user['phone']); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="shipping_city" class="form-label">City</label>
                                    <input type="text" class="form-control" id="shipping_city" name="shipping_city" value="<?php echo htmlspecialchars($user['city']); ?>" required>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="shipping_address" class="form-label">Address</label>
                                <textarea class="form-control" id="shipping_address" name="shipping_address" rows="2" required><?php echo htmlspecialchars($user['address']); ?></textarea>
                            </div>
                            
                            <hr>
                            
                            <h5 class="mb-3">Payment Information</h5>
                            
                            <!-- Payment Method Selection -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">Payment Method</h5>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="payment_method" id="pay_card" value="card" checked>
                                            <label class="form-check-label" for="pay_card"><i class="fas fa-credit-card me-1"></i> Credit/Debit Card</label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="payment_method" id="pay_paypal" value="paypal">
                                            <label class="form-check-label" for="pay_paypal"><i class="fab fa-cc-paypal me-1"></i> PayPal</label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="payment_method" id="pay_bank" value="bank">
                                            <label class="form-check-label" for="pay_bank"><i class="fas fa-university me-1"></i> Bank Transfer</label>
                                        </div>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="payment_method" id="pay_cod" value="cod">
                                            <label class="form-check-label" for="pay_cod"><i class="fas fa-money-bill-wave me-1"></i> Cash on Delivery</label>
                                        </div>
                                    </div>
                                    <div id="card-fields">
                                        <div class="row">
                                            <div class="col-md-4 mb-3">
                                                <label for="card_number" class="form-label">Card Number</label>
                                                <input type="text" class="form-control" id="card_number" name="card_number" maxlength="16">
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label for="expiry_date" class="form-label">Expiry Date (MM/YY)</label>
                                                <input type="text" class="form-control" id="expiry_date" name="expiry_date" maxlength="5">
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label for="cvv" class="form-label">CVV</label>
                                                <input type="text" class="form-control" id="cvv" name="cvv" maxlength="4">
                                            </div>
                                        </div>
                                    </div>
                                    <div id="paypal-fields" style="display:none;">
                                        <div class="mb-3">
                                            <label for="paypal_email" class="form-label">PayPal Email</label>
                                            <input type="email" class="form-control" id="paypal_email" name="paypal_email">
                                        </div>
                                    </div>
                                    <div id="bank-fields" style="display:none;">
                                        <div class="mb-3">
                                            <label for="bank_ref" class="form-label">Bank Reference Number</label>
                                            <input type="text" class="form-control" id="bank_ref" name="bank_ref">
                                            <div class="form-text">Please transfer the total to our bank account and enter your reference number here.</div>
                                        </div>
                                    </div>
                                    <div id="cod-fields" style="display:none;">
                                        <div class="alert alert-info mb-0">You will pay in cash upon delivery.</div>
                                    </div>
                                </div>
                            </div>
                            
                            <button type="submit" name="place_order" class="btn btn-primary">
                                <i class="fas fa-lock me-2"></i>Complete Payment
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Order Summary -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Order Summary</h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($cartItems as $item): ?>
                            <div class="d-flex justify-content-between mb-2">
                                <span><?php echo htmlspecialchars($item['name']); ?> x <?php echo $item['quantity']; ?></span>
                                <span><?php echo formatPrice($item['subtotal']); ?></span>
                            </div>
                        <?php endforeach; ?>
                        
                        <hr>
                        
                        <div class="d-flex justify-content-between mb-2">
                            <span>Subtotal</span>
                            <span><?php echo formatPrice($total); ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Shipping</span>
                            <span>Free</span>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between">
                            <span class="h5 mb-0">Total</span>
                            <span class="h5 mb-0"><?php echo formatPrice($total); ?></span>
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
        // Card number formatting
        document.getElementById('card_number').addEventListener('input', function(e) {
            this.value = this.value.replace(/\D/g, '');
        });
        
        // Expiry date formatting
        document.getElementById('expiry_date').addEventListener('input', function(e) {
            let value = this.value.replace(/\D/g, '');
            if (value.length >= 2) {
                value = value.slice(0,2) + '/' + value.slice(2);
            }
            this.value = value;
        });
        
        // CVV formatting
        document.getElementById('cvv').addEventListener('input', function(e) {
            this.value = this.value.replace(/\D/g, '');
        });
        
        // Form validation
        document.getElementById('checkoutForm').addEventListener('submit', function(e) {
            const cardNumber = document.getElementById('card_number').value;
            const expiryDate = document.getElementById('expiry_date').value;
            const cvv = document.getElementById('cvv').value;
            
            if (cardNumber.length !== 16) {
                e.preventDefault();
                alert('Please enter a valid 16-digit card number');
                return;
            }
            
            if (!/^(0[1-9]|1[0-2])\/([0-9]{2})$/.test(expiryDate)) {
                e.preventDefault();
                alert('Please enter a valid expiry date (MM/YY)');
                return;
            }
            
            if (cvv.length < 3 || cvv.length > 4) {
                e.preventDefault();
                alert('Please enter a valid CVV (3-4 digits)');
                return;
            }
        });

        // Show/hide payment fields
        const cardFields = document.getElementById('card-fields');
        const paypalFields = document.getElementById('paypal-fields');
        const bankFields = document.getElementById('bank-fields');
        const codFields = document.getElementById('cod-fields');
        const radios = document.querySelectorAll('input[name="payment_method"]');
        radios.forEach(radio => {
            radio.addEventListener('change', function() {
                cardFields.style.display = this.value === 'card' ? '' : 'none';
                paypalFields.style.display = this.value === 'paypal' ? '' : 'none';
                bankFields.style.display = this.value === 'bank' ? '' : 'none';
                codFields.style.display = this.value === 'cod' ? '' : 'none';
            });
        });
    </script>
</body>
</html> 