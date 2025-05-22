
<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/search.php';

// Get database connection
$conn = getDBConnection();

// Get filters
$categoriesSelected = [];
if (isset($_GET['category']) && is_array($_GET['category'])) {
    $categoriesSelected = $_GET['category'];
} elseif (isset($_GET['category'])) {
    $categoriesSelected = [$_GET['category']];
}

$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$minPrice = isset($_GET['min_price']) && $_GET['min_price'] !== '' ? (float)$_GET['min_price'] : null;
$maxPrice = isset($_GET['max_price']) && $_GET['max_price'] !== '' ? (float)$_GET['max_price'] : null;
$brandSelected = isset($_GET['brand']) && $_GET['brand'] !== '' ? sanitizeInput($_GET['brand']) : null;
$sort = isset($_GET['sort']) ? sanitizeInput($_GET['sort']) : 'newest';

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 12;
$offset = ($page - 1) * $perPage;

// Determine if we should filter by categories or show all
$categoryFilter = null;
if (!empty($categoriesSelected) && !in_array('all', $categoriesSelected)) {
    $categoryFilter = $categoriesSelected;
}

// Map sort parameter to our search function parameters
$sort_by = 'name';
$sort_order = 'ASC';
switch ($sort) {
    case 'price_asc':
        $sort_by = 'price';
        $sort_order = 'ASC';
        break;
    case 'price_desc':
        $sort_by = 'price';
        $sort_order = 'DESC';
        break;
    case 'name_asc':
        $sort_by = 'name';
        $sort_order = 'ASC';
        break;
    case 'name_desc':
        $sort_by = 'name';
        $sort_order = 'DESC';
        break;
    default:
        $sort_by = 'created_at';
        $sort_order = 'DESC';
}

// Get products using our updated search function
$products = searchProducts(
    search_term: $search,
    category_id: null,
    brand: $brandSelected,
    min_price: $minPrice,
    max_price: $maxPrice,
    sort_by: $sort_by,
    sort_order: $sort_order,
    category_slugs: $categoryFilter
);

// Get total count for pagination
$totalProducts = count($products);
$totalPages = ceil($totalProducts / $perPage);

// Apply pagination
$products = array_slice($products, $offset, $perPage);

// Get categories for filter
$stmt = $conn->query("SELECT * FROM categories ORDER BY name");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all available brands for filter
$brands = getAllBrands();

// Get price range
$priceRange = getPriceRange();

// Function to build query parameters for URLs
function buildQuery($params, $overrides = []) {
    $query = array_merge($params, $overrides);
    return http_build_query($query);
}
$currentParams = $_GET;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products - NEXTGEN</title>
    
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
        <?php if (!empty($search) && empty($products)): ?>
            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                <strong>No products found!</strong> We couldn't find any products matching "<?php echo htmlspecialchars($search); ?>".
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- Filters Sidebar -->
            <div class="col-md-3">
                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <h5 class="card-title mb-3">Filters</h5>
                        
                        <form action="products.php" method="GET" id="filterForm">
                            <!-- Search -->
                            <div class="mb-3">
                                <label for="search" class="form-label">Search</label>
                                <input type="text" class="form-control" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                            
                            <!-- Categories -->
                            <div class="mb-3">
                                <label class="form-label">Categories</label>
                                <div class="form-check d-flex align-items-center mb-1">
                                    <input class="form-check-input me-2" type="checkbox" name="category[]" id="category_all" value="all" <?php echo (empty($categoriesSelected) || in_array('all', $categoriesSelected)) ? 'checked' : ''; ?>>
                                    <label class="form-check-label d-flex align-items-center" for="category_all">
                                        <i class="fas fa-th me-2" style="color: var(--primary-color);"></i>All
                                    </label>
                                </div>
                                <?php foreach ($categories as $cat): ?>
                                    <div class="form-check d-flex align-items-center mb-1">
                                        <input class="form-check-input me-2" type="checkbox" name="category[]" 
                                               id="category_<?php echo $cat['id']; ?>" 
                                               value="<?php echo htmlspecialchars($cat['slug']); ?>"
                                               <?php echo in_array($cat['slug'], $categoriesSelected) ? 'checked' : ''; ?>>
                                        <label class="form-check-label d-flex align-items-center" for="category_<?php echo $cat['id']; ?>">
                                            <i class="fas fa-mobile-alt me-2" style="color: var(--primary-color);"></i>
                                            <?php echo htmlspecialchars($cat['name']); ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <!-- Brands -->
                            <div class="mb-3">
                                <label for="brand" class="form-label">Brand</label>
                                <select class="form-select" id="brand" name="brand">
                                    <option value="">All Brands</option>
                                    <?php foreach ($brands as $brand): ?>
                                        <option value="<?php echo htmlspecialchars($brand); ?>" <?php echo $brandSelected === $brand ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($brand); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <!-- Price Range -->
                            <div class="mb-3">
                                <label class="form-label">Price Range</label>
                                <div class="row">
                                    <div class="col-6">
                                        <input type="number" class="form-control" name="min_price" 
                                               placeholder="Min" min="<?php echo $priceRange['min_price']; ?>" max="<?php echo $priceRange['max_price']; ?>"
                                               value="<?php echo $minPrice !== null ? htmlspecialchars($minPrice) : ''; ?>">
                                    </div>
                                    <div class="col-6">
                                        <input type="number" class="form-control" name="max_price" 
                                               placeholder="Max" min="<?php echo $priceRange['min_price']; ?>" max="<?php echo $priceRange['max_price']; ?>"
                                               value="<?php echo $maxPrice !== null ? htmlspecialchars($maxPrice) : ''; ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Sort -->
                            <div class="mb-3">
                                <label for="sort" class="form-label">Sort By</label>
                                <select class="form-select" id="sort" name="sort">
                                    <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Newest</option>
                                    <option value="price_asc" <?php echo $sort === 'price_asc' ? 'selected' : ''; ?>>Price: Low to High</option>
                                    <option value="price_desc" <?php echo $sort === 'price_desc' ? 'selected' : ''; ?>>Price: High to Low</option>
                                    <option value="name_asc" <?php echo $sort === 'name_asc' ? 'selected' : ''; ?>>Name: A to Z</option>
                                    <option value="name_desc" <?php echo $sort === 'name_desc' ? 'selected' : ''; ?>>Name: Z to A</option>
                                </select>
                            </div>
                            
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary flex-fill">Apply Filters</button>
                                <a href="products.php" class="btn btn-outline-secondary flex-fill">Clear Filters</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Products Grid -->
            <div class="col-md-9">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="mb-0">
                        <?php if (!empty($search)): ?>
                            Search Results for "<?php echo htmlspecialchars($search); ?>"
                        <?php else: ?>
                            Products
                        <?php endif; ?>
                    </h2>
                    <div class="text-muted">
                        Showing <?php echo count($products); ?> of <?php echo $totalProducts; ?> products
                    </div>
                </div>
                
                <!-- Active Filters Display -->
                <?php if (!empty($search) || !empty($categoriesSelected) && !in_array('all', $categoriesSelected) || $brandSelected !== null || $minPrice !== null || $maxPrice !== null): ?>
                <div class="mb-3">
                    <div class="d-flex flex-wrap gap-2 align-items-center">
                        <span class="text-muted">Active filters:</span>
                        <?php if (!empty($search)): ?>
                            <span class="badge bg-primary">Search: <?php echo htmlspecialchars($search); ?></span>
                        <?php endif; ?>
                        
                        <?php if (!empty($categoriesSelected) && !in_array('all', $categoriesSelected)): ?>
                            <?php foreach ($categoriesSelected as $catSlug): ?>
                                <?php 
                                    $catName = '';
                                    foreach ($categories as $cat) {
                                        if ($cat['slug'] === $catSlug) {
                                            $catName = $cat['name'];
                                            break;
                                        }
                                    }
                                ?>
                                <span class="badge bg-secondary">Category: <?php echo htmlspecialchars($catName); ?></span>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        
                        <?php if ($brandSelected !== null): ?>
                            <span class="badge bg-info text-dark">Brand: <?php echo htmlspecialchars($brandSelected); ?></span>
                        <?php endif; ?>
                        
                        <?php if ($minPrice !== null): ?>
                            <span class="badge bg-info text-dark">Min Price: <?php echo formatPrice($minPrice); ?></span>
                        <?php endif; ?>
                        
                        <?php if ($maxPrice !== null): ?>
                            <span class="badge bg-info text-dark">Max Price: <?php echo formatPrice($maxPrice); ?></span>
                        <?php endif; ?>
                        
                        <a href="products.php" class="btn btn-sm btn-outline-danger">Clear All</a>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="row" id="productGrid">
                    <?php foreach ($products as $product): ?>
                        <?php
                        $isNew = isset($product['created_at']) && (strtotime($product['created_at']) > strtotime('-30 days'));
                        $isBestSeller = isset($product['sales']) && $product['sales'] > 10;
                        $isLowStock = isset($product['stock']) && $product['stock'] <= 5;
                        ?>
                        <div class="col-md-4 mb-4">
                            <div class="card h-100 position-relative text-center p-3 border-0 shadow-sm">
                                <div class="position-absolute top-0 start-0 m-2" style="z-index:2;">
                                    <?php if ($isNew): ?>
                                        <span class="badge bg-success">New</span>
                                    <?php endif; ?>
                                    <?php if ($isBestSeller): ?>
                                        <span class="badge bg-warning text-dark">Best Seller</span>
                                    <?php endif; ?>
                                    <?php if ($isLowStock): ?>
                                        <span class="badge bg-danger">Low Stock</span>
                                    <?php endif; ?>
                                </div>
                                <img src="<?php echo htmlspecialchars($product['image']); ?>" class="card-img-top mb-3 img-fluid rounded" alt="<?php echo htmlspecialchars($product['name']); ?>" style="height:180px;object-fit:cover;">
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h5>
                                    <p class="card-text text-muted">
                                        <?php echo htmlspecialchars($product['category_name']); ?> | 
                                        <span class="fw-medium"><?php echo htmlspecialchars($product['brand']); ?></span>
                                    </p>
                                    <p class="card-text product-price text-primary fw-bold" style="font-size:1.5rem;">
                                        <?php echo formatPrice($product['price']); ?>
                                    </p>
                                    <p class="mb-2">
                                        <?php if (isset($product['stock']) && $product['stock'] > 5): ?>
                                            <span class="text-success">In Stock</span>
                                        <?php elseif (isset($product['stock']) && $product['stock'] > 0): ?>
                                            <span class="text-danger">Only <?php echo $product['stock']; ?> left!</span>
                                        <?php else: ?>
                                            <span class="text-danger">Out of Stock</span>
                                        <?php endif; ?>
                                    </p>
                                    <div class="d-flex justify-content-center gap-2">
                                        <a href="product.php?id=<?php echo $product['id']; ?>" class="btn btn-outline-primary btn-sm">View Details</a>
                                        <?php if (!isset($product['stock']) || $product['stock'] > 0): ?>
                                        <form method="post" action="cart.php" class="d-inline">
                                            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                            <input type="hidden" name="quantity" value="1">
                                            <button type="submit" name="add_to_cart" class="btn btn-primary btn-sm"><i class="fas fa-cart-plus"></i></button>
                                        </form>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Empty State if no products are found -->
                <?php if (empty($products)): ?>
                <div class="text-center p-5">
                    <div class="mb-4">
                        <i class="fas fa-search fa-4x text-muted"></i>
                    </div>
                    <h3>No products found</h3>
                    <p class="text-muted">Try adjusting your search or filter criteria</p>
                    <a href="products.php" class="btn btn-primary mt-3">Clear Filters</a>
                </div>
                <?php endif; ?>
                
                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <nav aria-label="Page navigation" class="mt-4">
                        <ul class="pagination justify-content-center">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?<?php echo buildQuery($currentParams, ['page' => $page - 1]); ?>">
                                        Previous
                                    </a>
                                </li>
                            <?php endif; ?>
                            
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?<?php echo buildQuery($currentParams, ['page' => $i]); ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($page < $totalPages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?<?php echo buildQuery($currentParams, ['page' => $page + 1]); ?>">
                                        Next
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
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
    // Handle "All" checkbox behavior
    document.addEventListener('DOMContentLoaded', function() {
        const allCategoryCheckbox = document.getElementById('category_all');
        const categoryCheckboxes = document.querySelectorAll('input[name="category[]"]:not(#category_all)');
        
        // When "All" is checked, uncheck other categories
        allCategoryCheckbox.addEventListener('change', function() {
            if (this.checked) {
                categoryCheckboxes.forEach(checkbox => {
                    checkbox.checked = false;
                });
            }
        });
        
        // When any other category is checked, uncheck "All"
        categoryCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                if (this.checked) {
                    allCategoryCheckbox.checked = false;
                }
                
                // If no categories are selected, check "All"
                let anyChecked = false;
                categoryCheckboxes.forEach(cb => {
                    if (cb.checked) anyChecked = true;
                });
                
                if (!anyChecked) {
                    allCategoryCheckbox.checked = true;
                }
            });
        });
    });
    </script>
</body>
</html>