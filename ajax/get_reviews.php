<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

$productId = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;
$reviews = getProductReviews($productId);

foreach ($reviews as $review): ?>
    <div class="card mb-3">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="mb-0"><?= htmlspecialchars($review['user_name']) ?></h6>
                <small class="text-muted"><?= date('F j, Y', strtotime($review['created_at'])) ?></small>
            </div>
            <div class="mb-2">
                <?php for ($i = 1; $i <= 5; $i++): ?>
                    <i class="fas fa-star<?= $i <= $review['rating'] ? ' text-warning' : ' text-secondary' ?>"></i>
                <?php endfor; ?>
            </div>
            <p class="mb-0"><?= nl2br(htmlspecialchars($review['comment'])) ?></p>
        </div>
    </div>
<?php endforeach; ?> 