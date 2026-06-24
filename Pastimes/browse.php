<?php
/**
 * browse.php
 * Browse Listings Page — Pastimes
 * WEDE6021 POE
 *
 * Displays all available clothing items with:
 * - Filter sidebar (category, brand, size, condition, price)
 * - Grid layout with product cards
 * - Pagination
 * - Sort options
 * - Add to cart / Message seller buttons
 */

session_start();
require_once 'includes/DBConn.php';
require_once 'includes/image_helper.php';

$page_title = 'Browse Clothing';

// ============================================================
// FILTER & SORT PARAMETERS
// ============================================================
$search    = sanitize($_GET['search']    ?? '');
$category  = sanitize($_GET['category'] ?? '');
$brand     = sanitize($_GET['brand']    ?? '');
$condition = sanitize($_GET['condition'] ?? '');
$size      = sanitize($_GET['size']     ?? '');
$min_price = (float)($_GET['min_price'] ?? 0);
$max_price = (float)($_GET['max_price'] ?? 5000);
$sort      = sanitize($_GET['sort']     ?? 'newest');
$page      = max(1, (int)($_GET['page'] ?? 1));
$per_page  = 8;
$offset    = ($page - 1) * $per_page;

// ============================================================
// BUILD DYNAMIC QUERY WITH PREPARED STATEMENTS
// ============================================================
$where_clauses = ["i.status = 'available'"];
$bind_types    = '';
$bind_values   = [];

if (!empty($search)) {
    $where_clauses[] = "(i.item_name LIKE ? OR i.brand LIKE ? OR i.description LIKE ?)";
    $s = "%$search%";
    $bind_types .= 'sss';
    $bind_values = array_merge($bind_values, [$s, $s, $s]);
}
if (!empty($category)) {
    $where_clauses[] = "i.category = ?";
    $bind_types .= 's';
    $bind_values[] = $category;
}
if (!empty($brand)) {
    $where_clauses[] = "i.brand = ?";
    $bind_types .= 's';
    $bind_values[] = $brand;
}
if (!empty($condition)) {
    $where_clauses[] = "i.condition = ?";
    $bind_types .= 's';
    $bind_values[] = $condition;
}
if (!empty($size)) {
    $where_clauses[] = "i.size = ?";
    $bind_types .= 's';
    $bind_values[] = $size;
}
$where_clauses[] = "i.price >= ?";
$where_clauses[] = "i.price <= ?";
$bind_types .= 'dd';
$bind_values[] = $min_price;
$bind_values[] = $max_price;

$where_sql = implode(' AND ', $where_clauses);

// Sort order
$order_sql = match($sort) {
    'price_asc'  => 'i.price ASC',
    'price_desc' => 'i.price DESC',
    'oldest'     => 'i.created_at ASC',
    default      => 'i.created_at DESC',
};

// Count total results
$count_sql  = "SELECT COUNT(*) as total FROM items i WHERE $where_sql";
$count_stmt = $conn->prepare($count_sql);
if (!empty($bind_types)) {
    $count_stmt->bind_param($bind_types, ...$bind_values);
}
$count_stmt->execute();
$total_items = $count_stmt->get_result()->fetch_assoc()['total'] ?? 0;
$count_stmt->close();
$total_pages = ceil($total_items / $per_page);

// Fetch items
$items_sql  = "
    SELECT i.*, u.username AS seller_name,
           COALESCE(p.file_path, 'images/placeholder.jpg') AS primary_photo
    FROM items i
    JOIN users u ON i.seller_id = u.user_id
    LEFT JOIN item_photos p ON p.item_id = i.item_id AND p.is_primary = 1
    WHERE $where_sql
    ORDER BY $order_sql
    LIMIT ? OFFSET ?
";
$items_stmt = $conn->prepare($items_sql);
$page_types = $bind_types . 'ii';
$page_vals  = array_merge($bind_values, [$per_page, $offset]);
$items_stmt->bind_param($page_types, ...$page_vals);
$items_stmt->execute();
$items_result = $items_stmt->get_result();

$items = [];
while ($row = $items_result->fetch_assoc()) {
    $items[] = $row;
}
$items_stmt->close();

// Fetch filter options
$categories = [];
$res = $conn->query("SELECT DISTINCT category, COUNT(*) as cnt FROM items WHERE status='available' GROUP BY category ORDER BY category");
while ($r = $res->fetch_assoc()) $categories[] = $r;

$brands = [];
$res = $conn->query("SELECT DISTINCT brand, COUNT(*) as cnt FROM items WHERE status='available' GROUP BY brand ORDER BY brand");
while ($r = $res->fetch_assoc()) $brands[] = $r;

$conditions = ['excellent' => 0, 'good' => 0, 'fair' => 0];
$res = $conn->query("SELECT `condition`, COUNT(*) as cnt FROM items WHERE status='available' GROUP BY `condition`");
while ($r = $res->fetch_assoc()) $conditions[$r['condition']] = $r['cnt'];

include 'includes/header.php';
?>

<!-- Page Hero -->
<div class="page-hero" style="min-height:160px;">
    <div style="position:absolute;right:0;top:0;bottom:0;width:100%;background:linear-gradient(135deg,#0d3a6e,#082B59);opacity:0.9;">
        <img src="images/Browse & sell hero picture.png" alt="Browse Hero Image" style="width:100%;height:100%;object-fit:cover;opacity:0.9;">
    </div>
    <div class="container">
        <h1>Browse Clothing</h1>
        <p>Find pre-loved branded clothing in excellent condition at affordable prices.</p>
    </div>
</div>

<!-- ============================================================
     BROWSE LAYOUT
     ============================================================ -->
<div style="background:var(--off-white);padding:40px 0 60px;">
    <div class="container">
        <div class="browse-layout">

            <!-- ================================================
                 FILTER SIDEBAR
                 ================================================ -->
            <aside class="filter-sidebar">
                <h3>
                    Filters
                    <a href="browse.php">Clear all</a>
                </h3>

                <form method="GET" action="browse.php" id="filter-form">
                    <?php if (!empty($search)): ?>
                    <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                    <?php endif; ?>

                    <!-- Categories -->
                    <div class="filter-section">
                        <h4>Categories <span>&#8963;</span></h4>
                        <?php foreach ($categories as $cat): ?>
                        <div class="filter-option">
                            <label>
                                <input type="checkbox" name="category" value="<?php echo htmlspecialchars($cat['category']); ?>"
                                    <?php echo $category === $cat['category'] ? 'checked' : ''; ?>
                                    onchange="document.getElementById('filter-form').submit()">
                                <?php echo htmlspecialchars($cat['category']); ?>
                            </label>
                            <span class="filter-count">(<?php echo $cat['cnt']; ?>)</span>
                        </div>
                        <?php endforeach; ?>
                        <?php if (empty($categories)): ?>
                        <?php foreach (['T-Shirts','Shirts','Hoodies & Sweatshirts','Jackets','Jeans','Dresses','Accessories'] as $c): ?>
                        <div class="filter-option">
                            <label><input type="checkbox" name="category" value="<?php echo $c; ?>"><?php echo $c; ?></label>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <!-- Brand -->
                    <div class="filter-section">
                        <h4>Brand <span>&#8963;</span></h4>
                        <div style="margin-bottom:10px;">
                            <input type="text" placeholder="Search brands..." class="form-control" style="font-size:.8rem;padding:8px 12px;" id="brand-search">
                        </div>
                        <?php foreach ($brands as $b): ?>
                        <div class="filter-option brand-option">
                            <label>
                                <input type="checkbox" name="brand" value="<?php echo htmlspecialchars($b['brand']); ?>"
                                    <?php echo $brand === $b['brand'] ? 'checked' : ''; ?>
                                    onchange="document.getElementById('filter-form').submit()">
                                <?php echo htmlspecialchars($b['brand']); ?>
                            </label>
                            <span class="filter-count">(<?php echo $b['cnt']; ?>)</span>
                        </div>
                        <?php endforeach; ?>
                        <?php if (empty($brands)): ?>
                        <?php foreach (['Nike','Adidas',"Levi's",'Zara','H&M','Puma'] as $br): ?>
                        <div class="filter-option brand-option">
                            <label><input type="checkbox" name="brand" value="<?php echo $br; ?>"><?php echo $br; ?></label>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <!-- Size -->
                    <div class="filter-section">
                        <h4>Size <span>&#8963;</span></h4>
                        <div class="size-grid">
                            <?php foreach (['XS','S','M','L','XL','XXL'] as $s): ?>
                            <button type="button" class="size-btn <?php echo $size === $s ? 'active' : ''; ?>"
                                    onclick="document.querySelector('[name=size]').value='<?php echo $s; ?>';document.getElementById('filter-form').submit();">
                                <?php echo $s; ?>
                            </button>
                            <?php endforeach; ?>
                        </div>
                        <input type="hidden" name="size" value="<?php echo htmlspecialchars($size); ?>">
                    </div>

                    <!-- Condition -->
                    <div class="filter-section">
                        <h4>Condition <span>&#8963;</span></h4>
                        <?php foreach (['excellent','good','fair'] as $c): ?>
                        <div class="filter-option">
                            <label>
                                <span class="condition-dot <?php echo $c; ?>"></span>
                                <input type="checkbox" name="condition" value="<?php echo $c; ?>"
                                    <?php echo $condition === $c ? 'checked' : ''; ?>
                                    onchange="document.getElementById('filter-form').submit()">
                                <?php echo ucfirst($c); ?>
                            </label>
                            <span class="filter-count">(<?php echo $conditions[$c]; ?>)</span>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Price Range -->
                    <div class="filter-section">
                        <h4>Price Range <span>&#8963;</span></h4>
                        <input type="range" id="price-range" name="max_price"
                               min="0" max="5000" step="50"
                               value="<?php echo (int)$max_price; ?>">
                        <div class="price-range-inputs">
                            <span>R0</span>
                            <span id="price-display">R<?php echo number_format($max_price, 0); ?></span>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block" style="margin-top:8px;">
                        Apply Filters
                    </button>
                </form>
            </aside>

            <!-- ================================================
                 PRODUCT GRID
                 ================================================ -->
            <div>
                <!-- Browse Header -->
                <div class="browse-header">
                    <p>Showing <?php echo ($offset + 1); ?>–<?php echo min($offset + $per_page, $total_items); ?> of <?php echo $total_items; ?> items</p>
                    <div style="display:flex;align-items:center;gap:12px;">
                        <select name="sort" class="form-control" style="width:auto;font-size:.85rem;padding:8px 32px 8px 12px;"
                                onchange="window.location='browse.php?sort='+this.value+'<?php echo !empty($category)?"&category=".urlencode($category):""; ?><?php echo !empty($brand)?"&brand=".urlencode($brand):""; ?>'">
                            <option value="newest"     <?php echo $sort==='newest'     ?'selected':''; ?>>Sort by: Newest First</option>
                            <option value="price_asc"  <?php echo $sort==='price_asc'  ?'selected':''; ?>>Price: Low to High</option>
                            <option value="price_desc" <?php echo $sort==='price_desc' ?'selected':''; ?>>Price: High to Low</option>
                            <option value="oldest"     <?php echo $sort==='oldest'     ?'selected':''; ?>>Oldest First</option>
                        </select>
                        <div class="view-toggle">
                            <button class="view-btn active" title="Grid view">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16">
                                    <path d="M1 2.5A1.5 1.5 0 0 1 2.5 1h3A1.5 1.5 0 0 1 7 2.5v3A1.5 1.5 0 0 1 5.5 7h-3A1.5 1.5 0 0 1 1 5.5v-3zm8 0A1.5 1.5 0 0 1 10.5 1h3A1.5 1.5 0 0 1 15 2.5v3A1.5 1.5 0 0 1 13.5 7h-3A1.5 1.5 0 0 1 9 5.5v-3zm-8 8A1.5 1.5 0 0 1 2.5 9h3A1.5 1.5 0 0 1 7 10.5v3A1.5 1.5 0 0 1 5.5 15h-3A1.5 1.5 0 0 1 1 13.5v-3zm8 0A1.5 1.5 0 0 1 10.5 9h3a1.5 1.5 0 0 1 1.5 1.5v3a1.5 1.5 0 0 1-1.5 1.5h-3A1.5 1.5 0 0 1 9 13.5v-3z"/>
                                </svg>
                            </button>
                            <button class="view-btn" title="List view">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16">
                                    <path fill-rule="evenodd" d="M2.5 12a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5zm0-4a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5zm0-4a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5z"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Product Cards Grid -->
                <?php if (empty($items)): ?>
                <div style="text-align:center;padding:60px 20px;color:var(--text-light);">
                    <div style="font-size:3rem;margin-bottom:16px;">🔍</div>
                    <h3 style="color:var(--navy);margin-bottom:8px;">No items found</h3>
                    <p>Try adjusting your filters or <a href="browse.php">clear all filters</a>.</p>
                </div>
                <?php else: ?>
                <div class="browse-grid">
                    <?php foreach ($items as $item): ?>
                    <div class="product-card">
                        <div class="product-card-image">
                            <a href="item.php?id=<?php echo $item['item_id']; ?>">
                                <img src="<?php echo getItemImage($item['primary_photo'], $item['brand']); ?>"
                                     alt="<?php echo htmlspecialchars($item['item_name']); ?>"
                                     onerror="this.src='images/placeholder.jpg'"
                                     style="width:100%;height:200px;object-fit:cover;">
                            </a>
                            <button class="wishlist-btn" title="Save to Wishlist">♡</button>
                            <span class="condition-badge <?php echo htmlspecialchars($item['condition']); ?>">
                                <?php echo ucfirst(htmlspecialchars($item['condition'])); ?>
                            </span>
                        </div>
                        <div class="product-card-body">
                            <a href="item.php?id=<?php echo $item['item_id']; ?>" style="text-decoration:none;">
                                <div class="product-meta">
                                    <h3><?php echo htmlspecialchars($item['item_name']); ?></h3>
                                </div>
                                <p class="product-subtitle">
                                    <?php echo htmlspecialchars($item['brand']); ?> &bull;
                                    Size <?php echo htmlspecialchars($item['size']); ?>
                                </p>
                                <p style="font-size:1rem;font-weight:700;color:var(--navy);margin-bottom:12px;">
                                    R<?php echo number_format($item['price'], 0); ?>
                                </p>
                            </a>
                            <div class="product-card-actions">
                                <?php if (isLoggedIn()): ?>
                                <a href="cart.php?add=<?php echo $item['item_id']; ?>" class="btn btn-primary btn-sm">Add to Cart</a>
                                <a href="item.php?id=<?php echo $item['item_id']; ?>#message" class="btn btn-outline-teal btn-sm">Message Seller</a>
                                <?php else: ?>
                                <a href="login.php" class="btn btn-primary btn-sm">Add to Cart</a>
                                <a href="item.php?id=<?php echo $item['item_id']; ?>" class="btn btn-outline-teal btn-sm">Message Seller</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                    <a href="?page=<?php echo $page-1; ?>&sort=<?php echo $sort; ?><?php echo !empty($category)?"&category=".urlencode($category):""; ?><?php echo !empty($brand)?"&brand=".urlencode($brand):""; ?>" class="page-btn">&#8249;</a>
                    <?php endif; ?>

                    <?php for ($p = 1; $p <= min($total_pages, 5); $p++): ?>
                    <a href="?page=<?php echo $p; ?>&sort=<?php echo $sort; ?><?php echo !empty($category)?"&category=".urlencode($category):""; ?>"
                       class="page-btn <?php echo $p === $page ? 'active' : ''; ?>">
                        <?php echo $p; ?>
                    </a>
                    <?php endfor; ?>

                    <?php if ($total_pages > 5): ?>
                    <span class="page-btn" style="cursor:default;">...</span>
                    <a href="?page=<?php echo $total_pages; ?>&sort=<?php echo $sort; ?>" class="page-btn"><?php echo $total_pages; ?></a>
                    <?php endif; ?>

                    <?php if ($page < $total_pages): ?>
                    <a href="?page=<?php echo $page+1; ?>&sort=<?php echo $sort; ?><?php echo !empty($category)?"&category=".urlencode($category):""; ?>" class="page-btn">&#8250;</a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Trust Bar -->
<section class="page-trust-bar">
    <div class="container">
        <div class="trust-feature">
            <div class="trust-feature-icon">🌿</div>
            <div><h4>Eco-Friendly Choice</h4><p>Buying second-hand reduces fashion waste and helps protect our planet.</p></div>
        </div>
        <div class="trust-feature">
            <div class="trust-feature-icon">✅</div>
            <div><h4>Verified Sellers</h4><p>All sellers are admin-verified to ensure quality and trust.</p></div>
        </div>
        <div class="trust-feature">
            <div class="trust-feature-icon">🔒</div>
            <div><h4>Secure Payments</h4><p>Your payments and personal information are safe and protected.</p></div>
        </div>
    </div>
</section>

<script>
// Brand search filter
document.getElementById('brand-search')?.addEventListener('input', function() {
    const q = this.value.toLowerCase();
    document.querySelectorAll('.brand-option').forEach(opt => {
        opt.style.display = opt.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
});
</script>

<?php include 'includes/footer.php'; ?>
