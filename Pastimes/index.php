<?php
/**
 * index.php
 * Pastimes — Home Page
 * WEDE6021 POE
 */

session_start();
require_once 'includes/DBConn.php';
require_once 'includes/image_helper.php';

$page_title = 'Home';

// ============================================================
// FETCH FEATURED LISTINGS (4 available items)
// ============================================================
$featured_items = [];
$sql = "
    SELECT i.*, u.username AS seller_name,
           COALESCE(p.file_path, 'images/placeholder.jpg') AS primary_photo
    FROM items i
    JOIN users u ON i.seller_id = u.user_id
    LEFT JOIN item_photos p ON p.item_id = i.item_id AND p.is_primary = 1
    WHERE i.status = 'available'
    ORDER BY i.created_at DESC
    LIMIT 4
";
$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $featured_items[] = $row;
    }
}

include 'includes/header.php';
?>

<!-- ============================================================
     HERO SECTION
     ============================================================ -->
<section class="hero">
    <div class="hero-bg"></div>
    <!-- Hero image -->
     <div style="position:absolute;right:0;top:0;bottom:0;width:100%;background:linear-gradient(135deg,#e8f0fe,#c8e6c9);">
        <img src="images/hero.png" alt="Hero Image" style="width:100%;height:100%;object-fit:cover;">
     </div>
    <div class="container" style="position:relative;z-index:2;">
        <div class="hero-content">
            <h1>Give Great Clothing<br><span>a Second Life</span></h1>
            <p>Discover great deals on quality second-hand fashion. List your pre-loved clothes for sale and find unique branded items at affordable prices.</p>
            <div class="hero-cta">
                <a href="browse.php" class="btn btn-primary btn-lg">Shop Now</a>
                <a href="sell.php" class="btn btn-outline btn-lg">Sell Now</a>
            </div>
        </div>
    </div>
</section>

<!-- ============================================================
     TRUST BAR
     ============================================================ -->
<section class="trust-bar">
    <div class="container">
        <div class="trust-item">
            <div class="trust-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M8 16s6-5.686 6-10A6 6 0 0 0 2 6c0 4.314 6 10 6 10zm0-7a3 3 0 1 1 0-6 3 3 0 0 1 0 6z"/>
                </svg>
            </div>
            <div>
                <h4>Eco-Friendly</h4>
                <p>Promote sustainable fashion.</p>
            </div>
        </div>
        <div class="trust-item">
            <div class="trust-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M10.067.87a2.89 2.89 0 0 0-4.134 0l-.622.638-.89-.011a2.89 2.89 0 0 0-2.924 2.924l.01.89-.636.622a2.89 2.89 0 0 0 0 4.134l.637.622-.011.89a2.89 2.89 0 0 0 2.924 2.924l.89-.01.622.636a2.89 2.89 0 0 0 4.134 0l.622-.637.89.011a2.89 2.89 0 0 0 2.924-2.924l-.01-.89.636-.622a2.89 2.89 0 0 0 0-4.134l-.637-.622.011-.89a2.89 2.89 0 0 0-2.924-2.924l-.89.01-.622-.636zm.287 5.984-3 3a.5.5 0 0 1-.708 0l-1.5-1.5a.5.5 0 1 1 .708-.708L7 8.793l2.646-2.647a.5.5 0 0 1 .708.708z"/>
                </svg>
            </div>
            <div>
                <h4>Verified Sellers</h4>
                <p>Admin-approved trustworthy sellers.</p>
            </div>
        </div>
        <div class="trust-item">
            <div class="trust-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M8 1a2 2 0 0 1 2 2v4H6V3a2 2 0 0 1 2-2zm3 6V3a3 3 0 0 0-6 0v4a2 2 0 0 0-2 2v5a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2z"/>
                </svg>
            </div>
            <div>
                <h4>Secure Transactions</h4>
                <p>Safe and protected payments.</p>
            </div>
        </div>
    </div>
</section>

<!-- ============================================================
     FEATURED LISTINGS
     ============================================================ -->
<section class="section-pad">
    <div class="container">
        <div class="section-header">
            <span class="section-tag">Handpicked For You</span>
            <h2>Featured Listings</h2>
            <p>Explore our top picks of pre-loved branded clothing.</p>
        </div>

        <div class="products-grid">
            <?php if (empty($featured_items)): ?>
            <!-- Placeholder cards if DB has no items yet -->
            <?php
            $placeholders = [
                ['brand'=>'Nike',      'name'=>'Excellent Crewneck',   'price'=>'R320','condition'=>'excellent','img'=>'images/NIKE.png'],
                ['brand'=>"Levi's",    'name'=>'Classic Denim Jacket', 'price'=>'R445','condition'=>'good',     'img'=>"images/Levi's.png"],
                ['brand'=>'Kate Spade','name'=>'Classic Shoulder Bag', 'price'=>'R500','condition'=>'excellent','img'=>'images/Kate Spade.png'],
                ['brand'=>'Zara',      'name'=>'Striped T-Shirt',      'price'=>'R180','condition'=>'fair',     'img'=>'images/Zara.png'],
            ];
            foreach ($placeholders as $item):
            ?>
            <div class="product-card">
                <div class="product-card-image">
                    <img src="<?php echo htmlspecialchars($item['img']); ?>"
                         alt="<?php echo htmlspecialchars($item['brand']); ?>"
                         style="width:100%;height:200px;object-fit:cover;"
                         onerror="this.src='images/placeholder.jpg'">
                    <button class="wishlist-btn" title="Save">♡</button>
                    <span class="condition-badge <?php echo $item['condition']; ?>">
                        <?php echo ucfirst($item['condition']); ?>
                    </span>
                </div>
                <div class="product-card-body">
                    <div class="product-meta">
                        <h3><?php echo $item['brand']; ?></h3>
                        <span class="product-price"><?php echo $item['price']; ?></span>
                    </div>
                    <p class="product-subtitle"><?php echo $item['name']; ?></p>
                    <div class="product-card-actions">
                        <a href="login.php?msg=login_required" class="btn btn-primary btn-sm">Add to Cart</a>
                        <a href="browse.php" class="btn btn-outline-teal btn-sm">Browse</a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>

            <?php else: ?>
            <!-- Real items from database -->
            <?php foreach ($featured_items as $item): ?>
            <div class="product-card">
                <div class="product-card-image">
                    <img src="<?php echo getItemImage($item['primary_photo'], $item['brand']); ?>"
                         alt="<?php echo htmlspecialchars($item['item_name']); ?>"
                         onerror="this.src='images/placeholder.jpg'">
                    <button class="wishlist-btn" title="Save to Wishlist">♡</button>
                    <span class="condition-badge <?php echo htmlspecialchars($item['condition']); ?>">
                        <?php echo ucfirst(htmlspecialchars($item['condition'])); ?>
                    </span>
                </div>
                <div class="product-card-body">
                    <div class="product-meta">
                        <h3><?php echo htmlspecialchars($item['brand']); ?></h3>
                        <span class="product-price">R<?php echo number_format($item['price'], 0); ?></span>
                    </div>
                    <p class="product-subtitle"><?php echo htmlspecialchars($item['item_name']); ?></p>
                    <div class="product-card-actions">
                        <?php if (isLoggedIn()): ?>
                        <a href="cart.php?add=<?php echo $item['item_id']; ?>" class="btn btn-primary btn-sm">Add to Cart</a>
                        <a href="item.php?id=<?php echo $item['item_id']; ?>#message" class="btn btn-outline-teal btn-sm">Message</a>
                        <?php else: ?>
                        <a href="login.php" class="btn btn-primary btn-sm">Add to Cart</a>
                        <a href="item.php?id=<?php echo $item['item_id']; ?>" class="btn btn-outline-teal btn-sm">Message</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div style="text-align:center;margin-top:32px;">
            <a href="browse.php" class="btn btn-outline" style="gap:6px;">
                View All
                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z"/>
                </svg>
            </a>
        </div>
    </div>
</section>

<!-- ============================================================
     HOW IT WORKS
     ============================================================ -->
<section class="how-it-works-section" id="how-it-works">
    <div class="container">
        <div style="display:grid;grid-template-columns:1fr 2fr;gap:60px;align-items:center;">
            <div>
                <span class="section-tag">Simple &amp; Easy</span>
                <h2 style="font-size:1.9rem;font-weight:700;color:var(--navy);margin:10px 0 12px;">How It Works</h2>
                <p style="color:var(--text-mid);font-size:0.95rem;">It's Easy to Buy &amp; Sell</p>
            </div>
            <div class="steps-grid">
                <div class="step-item">
                    <div class="step-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="currentColor" viewBox="0 0 16 16">
                            <path d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm2-3a2 2 0 1 1-4 0 2 2 0 0 1 4 0zm4 8c0 1-1 1-1 1H3s-1 0-1-1 1-4 6-4 6 3 6 4z"/>
                        </svg>
                    </div>
                    <div class="step-text">
                        <h3>Sign Up</h3>
                        <p>Register for free to start buying and selling.</p>
                    </div>
                </div>
                <div class="step-item">
                    <div class="step-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="currentColor" viewBox="0 0 16 16">
                            <path d="M10.5 8.5a2.5 2.5 0 1 1-5 0 2.5 2.5 0 0 1 5 0z"/><path d="M2 4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2h-1.172a2 2 0 0 1-1.414-.586l-.828-.828A2 2 0 0 0 9.172 2H6.828a2 2 0 0 0-1.414.586l-.828.828A2 2 0 0 0 3.172 4H2zm.5 2a.5.5 0 1 1 0-1 .5.5 0 0 1 0 1zm9 2.5a3.5 3.5 0 1 1-7 0 3.5 3.5 0 0 1 7 0z"/>
                        </svg>
                    </div>
                    <div class="step-text">
                        <h3>List Items</h3>
                        <p>Upload photos and details of your branded items.</p>
                    </div>
                </div>
                <div class="step-item">
                    <div class="step-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="currentColor" viewBox="0 0 16 16">
                            <path d="M5.5 9.511c.076.954.83 1.697 2.182 1.785V12h.6v-.709c1.4-.098 2.218-.846 2.218-1.932 0-.987-.626-1.496-1.745-1.76l-.473-.112V5.57c.6.068.982.396 1.074.86h1.052c-.076-.919-.864-1.638-2.126-1.716V4h-.6v.719c-1.195.117-2.01.836-2.01 1.853 0 .9.606 1.472 1.613 1.707l.397.098v2.034c-.615-.093-1.022-.43-1.114-.9H5.5zm2.177-2.166c-.59-.137-.91-.416-.91-.836 0-.47.345-.822.915-.925v1.76h-.005zm.692 1.193c.717.166 1.048.435 1.048.91 0 .542-.412.914-1.135.982V8.518l.087.02z"/><path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                        </svg>
                    </div>
                    <div class="step-text">
                        <h3>Make Deals</h3>
                        <p>Buy quality items or sell your pre-loved clothes effortlessly.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ============================================================
     ECO CTA BANNER
     ============================================================ -->
<section class="section-pad-sm">
    <div class="container">
        <div class="cta-banner">
            <p>
                <span class="eco-icon">🌿</span>
                Give your clothes a second life and help reduce fashion waste.
                <span class="eco-icon">🌿</span>
            </p>
            <div class="cta-banner-actions">
                <a href="sell.php" class="btn btn-primary">Start Selling</a>
                <a href="browse.php" class="btn btn-outline">Learn More</a>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
