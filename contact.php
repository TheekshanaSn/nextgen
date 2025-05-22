<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

$success = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Simple validation (expand as needed)
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    if ($name && $email && $subject && $message && filter_var($email, FILTER_VALIDATE_EMAIL)) {
        // Here you would normally send the email or store the message
        $success = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - NEXTGEN</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <section class="py-5 bg-light">
        <div class="container">
            <h1 class="text-center mb-4">Contact Us</h1>
            <div class="row g-5">
                <div class="col-md-6">
                    <div class="card shadow-sm mb-4">
                        <div class="card-body">
                            <h5 class="mb-3">Send us a message</h5>
                            <form id="contactForm" method="POST" action="#">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Name</label>
                                    <input type="text" class="form-control" id="name" name="name" required>
                                </div>
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="email" name="email" required>
                                </div>
                                <div class="mb-3">
                                    <label for="subject" class="form-label">Subject</label>
                                    <input type="text" class="form-control" id="subject" name="subject" required>
                                </div>
                                <div class="mb-3">
                                    <label for="message" class="form-label">Message</label>
                                    <textarea class="form-control" id="message" name="message" rows="4" required></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary">Send Message</button>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-4">
                        <h5>Contact Information</h5>
                        <ul class="list-unstyled">
                            <li><i class="fas fa-map-marker-alt me-2"></i>123 Galle Road, Colombo, Sri Lanka</li>
                            <li><i class="fas fa-phone me-2"></i>+94 77 123 4567</li>
                            <li><i class="fas fa-envelope me-2"></i>info@nextgen.lk</li>
                        </ul>
                    </div>
                    <div>
                        <h5>Find Us</h5>
                        <div class="ratio ratio-16x9 rounded shadow">
                            <iframe src="https://www.google.com/maps?q=Colombo,+Sri+Lanka&output=embed" allowfullscreen loading="lazy"></iframe>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <?php include 'includes/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="assets/js/main.js"></script>
    <!-- Success Modal -->
    <div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="successModalLabel">Success</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body text-center">
            <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
            <p class="mb-0">Message sent successfully!</p>
          </div>
        </div>
      </div>
    </div>
    <script>
    <?php if ($success): ?>
      var successModal = new bootstrap.Modal(document.getElementById('successModal'));
      window.addEventListener('DOMContentLoaded', function() {
        successModal.show();
      });
    <?php endif; ?>
    </script>
</body>
</html> 