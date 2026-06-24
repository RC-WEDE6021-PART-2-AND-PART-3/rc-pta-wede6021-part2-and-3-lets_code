<?php
/**
 * cart.php
 * Shopping Cart Page — Pastimes
 * WEDE6021 POE
 *
 * Features:
 * - View cart items
 * - Update quantities
 * - Remove items / Clear cart
 * - Order summary with totals
 * - Proceed to checkout
 */

session_start();
require_once 'includes/DBConn.php';
require_once 'includes/image_helper.php';

requireLogin();

$page_title = 'Shopping Cart';
$user_id    = $_SESSION['user_id'];

// ============================================================
// HANDLE CART ACTIONS
// ============================================================

// Add item to cart (from browse/item pages)
if (isset($_GET['add'])) {
    $add_item_id = (int)$_GET['add'];
    // Verify item is available
    $chk = $conn->prepare("SELECT item_id FROM items WHERE item_id=? AND status='available'");
    $chk->bind_param('i', $add_item_id);
    $chk->execute();
    if ($chk->get_result()->num_rows > 0) {
        // Insert or update quantity
        $ins = $conn->prepare(
            "INSERT INTO cart (user_id, item_id, quantity) VALUES (?,?,1)
             ON DUPLICATE KEY UPDATE quantity = quantity + 1"
        );
        $ins->bind_param('ii', $user_id, $add_item_id);
        $ins->execute();
        $ins->close();
        $_SESSION['flash_message'] = 'Item added to your cart!';
        $_SESSION['flash_type']    = 'success';
    }
    $chk->close();
    redirect('cart.php');
}

// Remove single item
if (isset($_GET['remove'])) {
    $rem_id = (int)$_GET['remove'];
    $del    = $conn->prepare("DELETE FROM cart WHERE user_id=? AND item_id=?");
    $del->bind_param('ii', $user_id, $rem_id);
    $del->execute();
    $del->close();
    redirect('cart.php');
}

// Clear entire cart
if (isset($_GET['clear'])) {
    $clr = $conn->prepare("DELETE FROM cart WHERE user_id=?");
    $clr->bind_param('i', $user_id);
    $clr->execute();
    $clr->close();
    redirect('cart.php');
}

// Update quantity (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_qty'])) {
    $upd_item_id = (int)($_POST['item_id'] ?? 0);
    $upd_qty     = max(1, (int)($_POST['qty'] ?? 1));
    $upd         = $conn->prepare("UPDATE cart SET quantity=? WHERE user_id=? AND item_id=?");
    $upd->bind_param('iii', $upd_qty, $user_id, $upd_item_id);
    $upd->execute();
    $upd->close();
    redirect('cart.php');
}

// ============================================================
// FETCH CART ITEMS (associative array)
// ============================================================
$cart_sql  = "
    SELECT c.cart_id, c.item_id, c.quantity,
           i.item_name, i.brand, i.size, i.colour, i.price, i.condition,
           i.seller_id, u.username AS seller_name, u.seller_status,
           COALESCE(p.file_path, 'images/placeholder.jpg') AS photo
    FROM cart c
    JOIN items i ON i.item_id = c.item_id
    JOIN users u ON u.user_id = i.seller_id
    LEFT JOIN item_photos p ON p.item_id = i.item_id AND p.is_primary = 1
    WHERE c.user_id = ?
    ORDER BY c.added_at DESC
";
$cart_stmt = $conn->prepare($cart_sql);
$cart_stmt->bind_param('i', $user_id);
$cart_stmt->execute();
$cart_result = $cart_stmt->get_result();

$cart_items = [];
$subtotal   = 0.0;
while ($row = $cart_result->fetch_assoc()) {
    $row['line_total'] = $row['price'] * $row['quantity'];
    $subtotal         += $row['line_total'];
    $cart_items[]      = $row;
}
$cart_stmt->close();

// Calculate totals
$delivery_fee  = count($cart_items) > 0 ? 60.00 : 0.00;
$service_fee   = count($cart_items) > 0 ? round($subtotal * 0.015, 2) : 0.00;
$total         = $subtotal + $delivery_fee + $service_fee;

include 'includes/header.php';
?>

<!-- Page Hero -->
<div class="page-hero" style="min-height:160px;">
    <div style="position:absolute;right:0;top:0;bottom:0;width:45%;background:linear-gradient(135deg,#0d3a6e,#082B59);opacity:.9;"></div>
    <div class="container">
        <h1>Shopping Cart</h1>
        <p>Review your items and proceed to secure checkout.</p>
    </div>
</div>

<div style="background:var(--off-white);padding:40px 0 60px;">
    <div class="container">
        <?php if (empty($cart_items)): ?>
        <!-- Empty Cart State -->
        <div style="text-align:center;padding:80px 20px;background:var(--white);border-radius:var(--radius-md);border:1px solid var(--mid-gray);">
            <div style="font-size:4rem;margin-bottom:16px;">🛒</div>
            <h2 style="color:var(--navy);margin-bottom:8px;">Your cart is empty</h2>
            <p style="color:var(--text-light);margin-bottom:28px;">Looks like you haven't added anything yet. Start browsing!</p>
            <a href="browse.php" class="btn btn-primary btn-lg">Browse Clothing</a>
        </div>

        <?php else: ?>
        <div class="cart-layout">

            <!-- ================================================
                 CART ITEMS PANEL
                 ================================================ -->
            <div>
                <div class="cart-panel">
                    <div class="cart-panel-header">
                        <h3>
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16"><path d="M0 1.5A.5.5 0 0 1 .5 1H2a.5.5 0 0 1 .485.379L2.89 3H14.5a.5.5 0 0 1 .491.592l-1.5 8A.5.5 0 0 1 13 12H4a.5.5 0 0 1-.491-.408L2.01 3.607 1.61 2H.5a.5.5 0 0 1-.5-.5z"/></svg>
                            <?php echo count($cart_items); ?> item<?php echo count($cart_items) !== 1 ? 's' : ''; ?> in your cart
                        </h3>
                        <label style="display:flex;align-items:center;gap:8px;font-size:.85rem;cursor:pointer;">
                            <input type="checkbox" id="select-all"> Select All
                        </label>
                    </div>

                    <?php foreach ($cart_items as $cart_item): ?>
                    <div class="cart-item">
                        <input type="checkbox" class="cart-item-check" checked data-price="<?php echo $cart_item['price']; ?>">

                        <div class="cart-item-image">
                            <a href="item.php?id=<?php echo $cart_item['item_id']; ?>">
                                <img src="<?php echo getItemImage($cart_item['photo'], $cart_item['brand']); ?>"
                                     alt="<?php echo htmlspecialchars($cart_item['item_name']); ?>"
                                     onerror="this.src='images/placeholder.jpg'">
                            </a>
                        </div>

                        <div class="cart-item-info">
                            <h4><?php echo htmlspecialchars($cart_item['item_name']); ?></h4>
                            <p class="cart-item-meta">
                                Brand: <?php echo htmlspecialchars($cart_item['brand']); ?> &bull;
                                Size: <?php echo htmlspecialchars($cart_item['size']); ?>
                                <?php if (!empty($cart_item['colour'])): ?>&bull; Colour: <?php echo htmlspecialchars($cart_item['colour']); ?><?php endif; ?>
                            </p>
                            <span class="condition-badge <?php echo $cart_item['condition']; ?>" style="position:static;">
                                <?php echo ucfirst($cart_item['condition']); ?> Condition
                            </span>
                            <p style="font-size:.78rem;margin-top:6px;">
                                Seller: <?php echo htmlspecialchars($cart_item['seller_name']); ?>
                                <?php if ($cart_item['seller_status'] === 'verified'): ?>
                                <span style="color:var(--teal);font-weight:600;">✓ Verified Seller</span>
                                <?php endif; ?>
                            </p>
                            <div class="cart-item-actions">
                                <!-- Quantity Control -->
                                <form method="POST" action="cart.php" style="display:inline-flex;align-items:center;gap:6px;">
                                    <input type="hidden" name="item_id" value="<?php echo $cart_item['item_id']; ?>">
                                    <div class="qty-control">
                                        <button type="button" class="qty-btn qty-minus">−</button>
                                        <span class="qty-num"><?php echo $cart_item['quantity']; ?></span>
                                        <button type="button" class="qty-btn qty-plus">+</button>
                                        <input type="hidden" name="qty" value="<?php echo $cart_item['quantity']; ?>">
                                    </div>
                                    <button type="submit" name="update_qty" style="font-size:.75rem;background:none;border:none;color:var(--teal);cursor:pointer;">Update</button>
                                </form>
                                <a href="cart.php?remove=<?php echo $cart_item['item_id']; ?>" class="remove"
                                   onclick="return confirm('Remove this item from cart?')">
                                    🗑 Remove
                                </a>
                                <a href="#" style="color:var(--text-light);">♡ Save for later</a>
                            </div>
                        </div>

                        <div class="cart-item-price" data-price="<?php echo $cart_item['price']; ?>">
                            R<?php echo number_format($cart_item['line_total'], 0); ?>
                        </div>
                    </div>
                    <?php endforeach; ?>

                    <div class="cart-footer">
                        <a href="browse.php" style="font-size:.85rem;color:var(--teal);display:flex;align-items:center;gap:6px;">
                            ← Continue Shopping
                        </a>
                        <a href="cart.php?clear=1" class="btn btn-outline btn-sm"
                           onclick="return confirm('Clear all items from cart?')"
                           style="color:var(--danger);border-color:var(--danger);">
                            🗑 Clear Cart
                        </a>
                    </div>
                </div>
            </div>

            <!-- ================================================
                 ORDER SUMMARY
                 ================================================ -->
            <div>
                <div class="order-summary">
                    <h3>Order Summary</h3>

                    <div class="summary-row">
                        <span class="summary-label">Subtotal (<?php echo count($cart_items); ?> items)</span>
                        <span class="subtotal-amount">R<?php echo number_format($subtotal, 0); ?></span>
                    </div>
                    <div class="summary-row">
                        <span class="summary-label">Delivery</span>
                        <span>R<?php echo number_format($delivery_fee, 0); ?></span>
                    </div>
                    <div style="font-size:.78rem;color:var(--text-light);margin-top:-6px;margin-bottom:12px;">
                        Standard Delivery (2 – 4 working days)
                    </div>
                    <div class="summary-row">
                        <span class="summary-label">
                            Service fee
                            <span class="summary-info" title="Small fee to keep the platform running">ⓘ</span>
                        </span>
                        <span>R<?php echo number_format($service_fee, 0); ?></span>
                    </div>

                    <div class="summary-row total">
                        <span>Total (incl. VAT)</span>
                        <span>R<?php echo number_format($total, 0); ?></span>
                    </div>

                    <div class="checkout-btn-area">
                        <a href="checkout.php" class="btn btn-primary btn-block btn-lg">
                            🔒 Proceed to Checkout
                        </a>
                        <button type="button" class="payfast-btn">
                            Checkout with <span>PayFast</span>
                        </button>
                    </div>

                    <div class="secure-note">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M8 1a2 2 0 0 1 2 2v4H6V3a2 2 0 0 1 2-2zm3 6V3a3 3 0 0 0-6 0v4a2 2 0 0 0-2 2v5a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2z"/></svg>
                        <div>
                            <strong style="color:var(--navy);display:block;">Secure Checkout</strong>
                            Your payment and personal information are safe and protected.
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Trust Bar -->
<section class="page-trust-bar">
    <div class="container">
        <div class="trust-feature"><div class="trust-feature-icon">🌿</div><div><h4>Eco-Friendly Choice</h4><p>Buying second-hand reduces fashion waste and helps protect our planet.</p></div></div>
        <div class="trust-feature"><div class="trust-feature-icon">✅</div><div><h4>Verified Sellers</h4><p>All sellers are admin-verified to ensure quality and trust.</p></div></div>
        <div class="trust-feature"><div class="trust-feature-icon">🔒</div><div><h4>Secure Payments</h4><p>Your payments and personal information are safe and protected.</p></div></div>
        <div class="trust-feature"><div class="trust-feature-icon">🚚</div><div><h4>Fast Delivery</h4><p>Reliable delivery in 2 – 4 working days.</p></div></div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
