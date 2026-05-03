<?php
/**
 * item.php
 * Item Detail Page — Pastimes
 * WEDE6021 POE
 *
 * Displays full product detail including:
 * - Photo gallery
 * - Item description and details
 * - Seller info card
 * - Add to Cart / Message Seller
 * - Related items
 * - Buyer messaging form
 */

session_start();
require_once 'includes/DBConn.php';
require_once 'includes/image_helper.php';

// Get item ID from URL
$item_id = (int)($_GET['id'] ?? 0);
if (!$item_id) {
    redirect('browse.php');
}

// ============================================================
// FETCH ITEM WITH SELLER INFO (associative array)
// ============================================================
$stmt = $conn->prepare("
    SELECT i.*, u.username AS seller_name, u.first_name AS seller_first,
           u.last_name AS seller_last, u.seller_status,
           u.created_at AS seller_since
    FROM items i
    JOIN users u ON i.seller_id = u.user_id
    WHERE i.item_id = ? AND i.status = 'available'
    LIMIT 1
");
$stmt->bind_param('i', $item_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['flash_message'] = 'Item not found or no longer available.';
    $_SESSION['flash_type']    = 'warning';
    redirect('browse.php');
}

$item = $result->fetch_assoc();
$stmt->close();

$page_title = htmlspecialchars($item['item_name']);

// Fetch all photos
$photos = [];
$pStmt = $conn->prepare("SELECT * FROM item_photos WHERE item_id = ? ORDER BY is_primary DESC");
$pStmt->bind_param('i', $item_id);
$pStmt->execute();
$pResult = $pStmt->get_result();
while ($row = $pResult->fetch_assoc()) {
    $photos[] = $row;
}
$pStmt->close();

// If no photos, add placeholder
if (empty($photos)) {
    $photos[] = ['photo_id' => 0, 'file_path' => 'images/placeholder.jpg', 'is_primary' => 1];
}

// Seller stats
$sellerStats = ['listings' => 0, 'response' => '96%', 'rating' => '4.9'];
$sStmt = $conn->prepare("SELECT COUNT(*) as cnt FROM items WHERE seller_id = ? AND status='available'");
$sStmt->bind_param('i', $item['seller_id']);
$sStmt->execute();
$sellerStats['listings'] = $sStmt->get_result()->fetch_assoc()['cnt'] ?? 0;
$sStmt->close();

// Related items (same category, excluding this item)
$related = [];
$rStmt = $conn->prepare("
    SELECT i.item_id, i.item_name, i.brand, i.price, i.size, i.condition,
           COALESCE(p.file_path,'images/placeholder.jpg') AS primary_photo
    FROM items i
    LEFT JOIN item_photos p ON p.item_id = i.item_id AND p.is_primary = 1
    WHERE i.category = ? AND i.item_id != ? AND i.status = 'available'
    LIMIT 5
");
$rStmt->bind_param('si', $item['category'], $item_id);
$rStmt->execute();
$rResult = $rStmt->get_result();
while ($row = $rResult->fetch_assoc()) {
    $related[] = $row;
}
$rStmt->close();

// ============================================================
// HANDLE MESSAGE SEND
// ============================================================
$message_sent  = false;
$message_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    if (!isLoggedIn()) {
        redirect('login.php?msg=login_required');
    }
    $msg_text    = trim($_POST['message_text'] ?? '');
    $sender_id   = $_SESSION['user_id'];
    $receiver_id = $item['seller_id'];

    if (empty($msg_text)) {
        $message_error = 'Please enter a message.';
    } elseif ($sender_id === $receiver_id) {
        $message_error = 'You cannot message yourself.';
    } else {
        $mStmt = $conn->prepare(
            "INSERT INTO messages (item_id, sender_id, receiver_id, message_text) VALUES (?, ?, ?, ?)"
        );
        $mStmt->bind_param('iiis', $item_id, $sender_id, $receiver_id, $msg_text);
        if ($mStmt->execute()) {
            $message_sent = true;
        } else {
            $message_error = 'Failed to send message. Please try again.';
        }
        $mStmt->close();
    }
}

// ============================================================
// HANDLE ADD TO CART
// ============================================================
if (isset($_GET['add_to_cart']) && isLoggedIn()) {
    $uid = $_SESSION['user_id'];
    // Check if already in cart
    $cStmt = $conn->prepare("SELECT cart_id, quantity FROM cart WHERE user_id=? AND item_id=?");
    $cStmt->bind_param('ii', $uid, $item_id);
    $cStmt->execute();
    $cResult = $cStmt->get_result();
    if ($cResult->num_rows > 0) {
        $cartRow = $cResult->fetch_assoc();
        $newQty  = $cartRow['quantity'] + 1;
        $upStmt  = $conn->prepare("UPDATE cart SET quantity=? WHERE cart_id=?");
        $upStmt->bind_param('ii', $newQty, $cartRow['cart_id']);
        $upStmt->execute();
        $upStmt->close();
    } else {
        $insStmt = $conn->prepare("INSERT INTO cart (user_id, item_id, quantity) VALUES (?,?,1)");
        $insStmt->bind_param('ii', $uid, $item_id);
        $insStmt->execute();
        $insStmt->close();
    }
    $cStmt->close();
    $_SESSION['flash_message'] = "Added to cart!";
    $_SESSION['flash_type']    = 'success';
    redirect("item.php?id=$item_id");
}

include 'includes/header.php';
?>

<div style="background:var(--off-white);padding-bottom:60px;">
    <div class="container">

        <!-- Breadcrumb -->
        <nav class="breadcrumb">
            <a href="index.php">Home</a> ›
            <a href="browse.php">Browse</a> ›
            <a href="browse.php?category=<?php echo urlencode($item['category']); ?>"><?php echo htmlspecialchars($item['category']); ?></a> ›
            <span><?php echo htmlspecialchars($item['item_name']); ?></span>
        </nav>

        <!-- Item Layout -->
        <div class="item-layout">

            <!-- ================================================
                 PHOTO GALLERY
                 ================================================ -->
            <div class="image-gallery">
                <!-- Thumbnails -->
                <div class="gallery-thumbs">
                    <?php foreach ($photos as $i => $photo): ?>
                    <div class="gallery-thumb <?php echo $i === 0 ? 'active' : ''; ?>">
                        <img src="<?php echo getItemImage($photo['file_path'], $item['brand']); ?>"
                             alt="Photo <?php echo $i+1; ?>"
                             onerror="this.src='images/placeholder.jpg'">
                    </div>
                    <?php endforeach; ?>
                </div>
                <!-- Main Image -->
                <div class="gallery-main">
                    <img src="<?php echo getItemImage($photos[0]['file_path'], $item['brand']); ?>"
                         alt="<?php echo htmlspecialchars($item['item_name']); ?>"
                         onerror="this.src='images/placeholder.jpg'"
                         id="main-gallery-img">
                    <button class="gallery-wishlist" title="Save to Wishlist">♡</button>
                </div>
            </div>

            <!-- ================================================
                 ITEM DETAILS (CENTRE)
                 ================================================ -->
            <div>
                <!-- Badges -->
                <div class="item-detail-badges">
                    <span class="condition-badge <?php echo $item['condition']; ?>" style="position:static;">
                        <?php echo ucfirst($item['condition']); ?> Condition
                    </span>
                    <?php if ($item['seller_status'] === 'verified'): ?>
                    <span class="verified-badge">
                        ✓ Verified Seller
                    </span>
                    <?php endif; ?>
                </div>

                <h1 class="item-detail-title"><?php echo htmlspecialchars($item['item_name']); ?></h1>
                <p class="item-detail-meta">
                    <?php echo htmlspecialchars($item['brand']); ?>
                    <span>•</span>
                    Size <?php echo htmlspecialchars($item['size']); ?>
                    <?php if (!empty($item['colour'])): ?>
                    <span>•</span> <?php echo htmlspecialchars($item['colour']); ?>
                    <?php endif; ?>
                </p>

                <div class="item-price">R<?php echo number_format($item['price'], 0); ?></div>
                <p class="item-price-note">Inclusive of VAT. Excludes delivery.</p>

                <!-- Features -->
                <ul class="item-features">
                    <li>
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M10.067.87a2.89 2.89 0 0 0-4.134 0l-.622.638-.89-.011a2.89 2.89 0 0 0-2.924 2.924l.01.89-.636.622a2.89 2.89 0 0 0 0 4.134l.637.622-.011.89a2.89 2.89 0 0 0 2.924 2.924l.89-.01.622.636a2.89 2.89 0 0 0 4.134 0l.622-.637.89.011a2.89 2.89 0 0 0 2.924-2.924l-.01-.89.636-.622a2.89 2.89 0 0 0 0-4.134l-.637-.622.011-.89a2.89 2.89 0 0 0-2.924-2.924l-.89.01-.622-.636zm.287 5.984-3 3a.5.5 0 0 1-.708 0l-1.5-1.5a.5.5 0 1 1 .708-.708L7 8.793l2.646-2.647a.5.5 0 0 1 .708.708z"/></svg>
                        <div><strong>100% Authentic</strong>Original <?php echo htmlspecialchars($item['brand']); ?> product</div>
                    </li>
                    <li>
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M8 3.5a.5.5 0 0 0-1 0V9a.5.5 0 0 0 .252.434l3.5 2a.5.5 0 0 0 .496-.868L8 8.71V3.5z"/><path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm7-8A7 7 0 1 1 1 8a7 7 0 0 1 14 0z"/></svg>
                        <div><strong>Pre-loved</strong>Gently used and in <?php echo $item['condition']; ?> condition</div>
                    </li>
                    <li>
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M8 1a2 2 0 0 1 2 2v4H6V3a2 2 0 0 1 2-2zm3 6V3a3 3 0 0 0-6 0v4a2 2 0 0 0-2 2v5a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2z"/></svg>
                        <div><strong>Quality Checked</strong>Inspected for quality and flaws</div>
                    </li>
                </ul>

                <!-- Action Buttons -->
                <div class="item-actions">
                    <?php if (isLoggedIn()): ?>
                    <a href="item.php?id=<?php echo $item_id; ?>&add_to_cart=1" class="btn btn-primary btn-lg btn-block">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M0 1.5A.5.5 0 0 1 .5 1H2a.5.5 0 0 1 .485.379L2.89 3H14.5a.5.5 0 0 1 .491.592l-1.5 8A.5.5 0 0 1 13 12H4a.5.5 0 0 1-.491-.408L2.01 3.607 1.61 2H.5a.5.5 0 0 1-.5-.5z"/></svg>
                        Add to Cart
                    </a>
                    <a href="#message" class="btn btn-outline btn-lg btn-block">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M14 1a1 1 0 0 1 1 1v8a1 1 0 0 1-1 1H4.414A2 2 0 0 0 3 11.586l-2 2V2a1 1 0 0 1 1-1h12z"/></svg>
                        Message Seller
                    </a>
                    <?php else: ?>
                    <a href="login.php" class="btn btn-primary btn-lg btn-block">Login to Add to Cart</a>
                    <a href="login.php" class="btn btn-outline btn-lg btn-block">Login to Message Seller</a>
                    <?php endif; ?>
                </div>

                <!-- Description -->
                <div class="item-desc-section">
                    <h3>Description</h3>
                    <p><?php echo nl2br(htmlspecialchars($item['description'])); ?></p>
                </div>

                <!-- Details -->
                <div class="item-desc-section">
                    <h3>Details</h3>
                    <div class="item-details-grid">
                        <div class="detail-row"><span class="detail-label">Brand</span><span class="detail-value"><?php echo htmlspecialchars($item['brand']); ?></span></div>
                        <div class="detail-row"><span class="detail-label">Condition</span><span class="detail-value"><?php echo ucfirst($item['condition']); ?></span></div>
                        <div class="detail-row"><span class="detail-label">Size</span><span class="detail-value"><?php echo htmlspecialchars($item['size']); ?></span></div>
                        <?php if (!empty($item['colour'])): ?>
                        <div class="detail-row"><span class="detail-label">Colour</span><span class="detail-value"><?php echo htmlspecialchars($item['colour']); ?></span></div>
                        <?php endif; ?>
                        <div class="detail-row"><span class="detail-label">Category</span><span class="detail-value"><?php echo htmlspecialchars($item['category']); ?></span></div>
                        <div class="detail-row"><span class="detail-label">Posted</span><span class="detail-value"><?php echo date('d M Y', strtotime($item['created_at'])); ?></span></div>
                    </div>
                </div>
            </div>

            <!-- ================================================
                 RIGHT SIDEBAR
                 ================================================ -->
            <div>
                <!-- Seller Card -->
                <div class="seller-card">
                    <h4>About the Seller</h4>
                    <div class="seller-info">
                        <div class="seller-avatar">
                            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="currentColor" viewBox="0 0 16 16"><path d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm2-3a2 2 0 1 1-4 0 2 2 0 0 1 4 0zm4 8c0 1-1 1-1 1H3s-1 0-1-1 1-4 6-4 6 3 6 4z"/></svg>
                        </div>
                        <div>
                            <div class="seller-name">
                                <?php echo htmlspecialchars($item['seller_name']); ?>
                                <?php if ($item['seller_status'] === 'verified'): ?>
                                <span style="color:var(--teal);font-size:.7rem;">● Verified</span>
                                <?php endif; ?>
                            </div>
                            <div class="seller-since">Member since <?php echo date('M Y', strtotime($item['seller_since'])); ?></div>
                            <div class="stars">★★★★★</div>
                        </div>
                    </div>
                    <div class="seller-stats">
                        <div class="seller-stat"><strong><?php echo $sellerStats['listings']; ?></strong><span>Listings</span></div>
                        <div class="seller-stat"><strong><?php echo $sellerStats['response']; ?></strong><span>Response Rate</span></div>
                        <div class="seller-stat"><strong><?php echo $sellerStats['rating']; ?></strong><span>Rating</span></div>
                    </div>
                    <div class="seller-actions">
                        <a href="profile.php?seller=<?php echo $item['seller_id']; ?>" class="btn btn-outline btn-sm">View Profile</a>
                        <a href="#message" class="btn btn-primary btn-sm">Message Seller</a>
                    </div>
                </div>

                <!-- Delivery Card -->
                <div class="delivery-card">
                    <h4>Delivery &amp; Returns</h4>
                    <div class="delivery-item">
                        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="currentColor" viewBox="0 0 16 16"><path d="M0 3.5A1.5 1.5 0 0 1 1.5 2h9A1.5 1.5 0 0 1 12 3.5V5h1.02a1.5 1.5 0 0 1 1.17.563l1.481 1.85a1.5 1.5 0 0 1 .329.938V10.5a1.5 1.5 0 0 1-1.5 1.5H14a2 2 0 1 1-4 0H5a2 2 0 1 1-3.998-.085A1.5 1.5 0 0 1 0 10.5v-7z"/></svg>
                        <div><strong>Delivery</strong>R60 – R90 delivery in 2–4 working days</div>
                    </div>
                    <div class="delivery-item">
                        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="currentColor" viewBox="0 0 16 16"><path d="M11.534 7h3.932a.25.25 0 0 1 .192.41l-1.966 2.36a.25.25 0 0 1-.384 0l-1.966-2.36a.25.25 0 0 1 .192-.41zm-11 2h3.932a.25.25 0 0 0 .192-.41L2.692 6.23a.25.25 0 0 0-.384 0L.342 8.59A.25.25 0 0 0 .534 9z"/></svg>
                        <div><strong>Returns</strong>7-day return policy. Buyer pays return shipping.</div>
                    </div>
                    <div class="delivery-item">
                        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="currentColor" viewBox="0 0 16 16"><path d="M8 1a2 2 0 0 1 2 2v4H6V3a2 2 0 0 1 2-2zm3 6V3a3 3 0 0 0-6 0v4a2 2 0 0 0-2 2v5a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2z"/></svg>
                        <div><strong>Secure Payment</strong>Your payment and personal information are safe.</div>
                    </div>
                </div>
            </div>

        </div><!-- /.item-layout -->

        <!-- ================================================
             MESSAGE SELLER FORM
             ================================================ -->
        <div id="message" style="margin-top:40px;background:var(--white);border:1px solid var(--mid-gray);border-radius:var(--radius-md);padding:28px;max-width:680px;">
            <h3 style="font-size:1.1rem;font-weight:600;color:var(--navy);margin-bottom:6px;">Send a Message to <?php echo htmlspecialchars($item['seller_name']); ?></h3>
            <p style="font-size:.85rem;color:var(--text-light);margin-bottom:20px;">Ask about the item before you buy.</p>

            <?php if ($message_sent): ?>
            <div class="alert alert-success">✓ Your message has been sent to the seller!</div>
            <?php elseif (!empty($message_error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($message_error); ?></div>
            <?php endif; ?>

            <?php if (isLoggedIn() && !$message_sent): ?>
            <form method="POST" action="item.php?id=<?php echo $item_id; ?>#message">
                <div class="form-group">
                    <label for="message_text">Your Message</label>
                    <textarea id="message_text" name="message_text" class="form-control"
                              rows="4" placeholder="Hi, is this item still available?..."
                              required maxlength="1000"
                              data-maxlength="1000" data-counter="msg-count"></textarea>
                    <div class="char-count"><span id="msg-count">0/1000</span></div>
                </div>
                <button type="submit" name="send_message" class="btn btn-primary">Send Message</button>
            </form>
            <?php elseif (!isLoggedIn()): ?>
            <div class="alert alert-info">
                <a href="login.php">Login</a> to message the seller about this item.
            </div>
            <?php endif; ?>
        </div>

    </div>
</div>

<!-- Related Products -->
<?php if (!empty($related)): ?>
<section class="related-section">
    <div class="container">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;">
            <h2 style="font-size:1.3rem;font-weight:700;color:var(--navy);">You May Also Like</h2>
            <a href="browse.php?category=<?php echo urlencode($item['category']); ?>" style="font-size:.85rem;color:var(--teal);">View all ›</a>
        </div>
        <div class="related-grid">
            <?php foreach ($related as $rel): ?>
            <div class="product-card">
                <div class="product-card-image">
                    <a href="item.php?id=<?php echo $rel['item_id']; ?>">
                        <img src="<?php echo getItemImage($rel['primary_photo'], $rel['brand']); ?>"
                             alt="<?php echo htmlspecialchars($rel['item_name']); ?>"
                             onerror="this.src='images/placeholder.jpg'"
                             style="width:100%;height:160px;object-fit:cover;">
                    </a>
                    <button class="wishlist-btn" style="width:28px;height:28px;font-size:.75rem;">♡</button>
                </div>
                <div class="product-card-body" style="padding:10px;">
                    <p style="font-size:.8rem;font-weight:600;color:var(--navy);margin-bottom:2px;">
                        <?php echo htmlspecialchars($rel['item_name']); ?>
                    </p>
                    <p style="font-size:.75rem;color:var(--text-light);margin-bottom:6px;">
                        Size <?php echo htmlspecialchars($rel['size']); ?>
                    </p>
                    <div style="display:flex;justify-content:space-between;align-items:center;">
                        <span style="font-size:.88rem;font-weight:700;color:var(--navy);">R<?php echo number_format($rel['price'], 0); ?></span>
                        <span class="condition-badge <?php echo $rel['condition']; ?>" style="position:static;font-size:.65rem;padding:2px 8px;">
                            <?php echo ucfirst($rel['condition']); ?>
                        </span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Trust Bar -->
<section class="page-trust-bar">
    <div class="container">
        <div class="trust-feature"><div class="trust-feature-icon">🌿</div><div><h4>Eco-Friendly Choice</h4><p>Buying pre-loved reduces fashion waste and helps our planet.</p></div></div>
        <div class="trust-feature"><div class="trust-feature-icon">✅</div><div><h4>Verified Sellers</h4><p>All sellers are admin-verified to ensure quality and trust.</p></div></div>
        <div class="trust-feature"><div class="trust-feature-icon">🔒</div><div><h4>Secure Transactions</h4><p>Your payments and personal information are safe and protected.</p></div></div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
