<?php
/**
 * checkout.php
 * Checkout Page — Pastimes
 * WEDE6021 POE
 *
 * Features:
 * - Delivery address selection (residential/work)
 * - Order summary
 * - Confirm order (creates order records, clears cart)
 * - Order reference number generated
 */

session_start();
require_once 'includes/DBConn.php';
require_once 'includes/image_helper.php';

requireLogin();

$page_title = 'Checkout';
$user_id    = $_SESSION['user_id'];

// Fetch cart items
$cart_sql  = "
    SELECT c.cart_id, c.item_id, c.quantity,
           i.item_name, i.brand, i.size, i.price, i.condition, i.seller_id,
           COALESCE(p.file_path,'images/placeholder.jpg') AS photo
    FROM cart c
    JOIN items i ON i.item_id = c.item_id
    LEFT JOIN item_photos p ON p.item_id = i.item_id AND p.is_primary = 1
    WHERE c.user_id = ?
    ORDER BY c.added_at DESC
";
$cart_stmt = $conn->prepare($cart_sql);
$cart_stmt->bind_param('i', $user_id);
$cart_stmt->execute();
$cart_result = $cart_stmt->get_result();
$cart_items  = [];
$subtotal    = 0.0;
while ($row = $cart_result->fetch_assoc()) {
    $row['line_total'] = $row['price'] * $row['quantity'];
    $subtotal         += $row['line_total'];
    $cart_items[]      = $row;
}
$cart_stmt->close();

// Redirect if cart is empty
if (empty($cart_items)) {
    redirect('cart.php');
}

// Fetch user addresses
$addr_stmt = $conn->prepare("SELECT * FROM addresses WHERE user_id = ? ORDER BY address_type");
$addr_stmt->bind_param('i', $user_id);
$addr_stmt->execute();
$addr_result = $addr_stmt->get_result();
$addresses   = [];
while ($row = $addr_result->fetch_assoc()) {
    $addresses[] = $row;
}
$addr_stmt->close();

$delivery_fee = 60.00;
$service_fee  = round($subtotal * 0.015, 2);
$total        = $subtotal + $delivery_fee + $service_fee;

$order_error   = '';
$order_success = false;
$order_ref     = '';

// ============================================================
// PROCESS CHECKOUT
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_order'])) {
    $selected_address = (int)($_POST['address_id'] ?? 0);

    if (!$selected_address) {
        $order_error = 'Please select a delivery address.';
    } else {
        // Verify the address belongs to the user
        $aChk = $conn->prepare("SELECT address_id FROM addresses WHERE address_id=? AND user_id=?");
        $aChk->bind_param('ii', $selected_address, $user_id);
        $aChk->execute();
        if ($aChk->get_result()->num_rows === 0) {
            $order_error = 'Invalid address selected.';
        }
        $aChk->close();

        if (empty($order_error)) {
            // Generate unique order reference
            $order_ref = 'ORD-' . strtoupper(substr(uniqid(), -8));

            // Insert order records for each cart item
            $order_stmt = $conn->prepare(
                "INSERT INTO orders (buyer_id, item_id, address_id, quantity, total_amount, order_ref, status)
                 VALUES (?, ?, ?, ?, ?, ?, 'pending')"
            );

            $all_ok = true;
            foreach ($cart_items as $ci) {
                $item_total = $ci['price'] * $ci['quantity'];
                $order_stmt->bind_param('iiiids', $user_id, $ci['item_id'], $selected_address, $ci['quantity'], $item_total, $order_ref);
                if (!$order_stmt->execute()) {
                    $all_ok = false;
                    break;
                }
                // Mark item as sold
                $soldStmt = $conn->prepare("UPDATE items SET status='sold' WHERE item_id=?");
                $soldStmt->bind_param('i', $ci['item_id']);
                $soldStmt->execute();
                $soldStmt->close();
            }
            $order_stmt->close();

            if ($all_ok) {
                // Clear cart
                $clrStmt = $conn->prepare("DELETE FROM cart WHERE user_id=?");
                $clrStmt->bind_param('i', $user_id);
                $clrStmt->execute();
                $clrStmt->close();

                $order_success = true;
                $_SESSION['last_order_ref'] = $order_ref;
            } else {
                $order_error = 'Order placement failed. Please try again.';
            }
        }
    }
}

include 'includes/header.php';
?>

<!-- Page Hero -->
<div class="page-hero" style="min-height:140px;">
    <div style="position:absolute;right:0;top:0;bottom:0;width:45%;background:linear-gradient(135deg,#0d3a6e,#082B59);opacity:.9;"></div>
    <div class="container">
        <h1>Checkout</h1>
        <p>Almost there! Confirm your delivery details to complete your order.</p>
    </div>
</div>

<div style="background:var(--off-white);padding:40px 0 60px;">
    <div class="container">

        <?php if ($order_success): ?>
        <!-- ================================================
             ORDER CONFIRMATION
             ================================================ -->
        <div style="max-width:600px;margin:0 auto;text-align:center;background:var(--white);border-radius:var(--radius-lg);padding:50px 40px;border:1px solid var(--mid-gray);box-shadow:var(--shadow-md);">
            <div style="font-size:4rem;margin-bottom:16px;">🎉</div>
            <h2 style="color:var(--navy);margin-bottom:10px;">Order Placed Successfully!</h2>
            <p style="color:var(--text-mid);margin-bottom:24px;">Thank you for shopping with Pastimes. Your order has been received and is being processed.</p>
            <div style="background:var(--teal-pale);border-radius:var(--radius-sm);padding:20px;margin-bottom:28px;">
                <p style="font-size:.85rem;color:var(--text-light);margin-bottom:6px;">Order Reference</p>
                <p style="font-size:1.4rem;font-weight:700;color:var(--teal);letter-spacing:2px;"><?php echo htmlspecialchars($order_ref); ?></p>
                <p style="font-size:.78rem;color:var(--text-light);margin-top:6px;">Session ID: <?php echo session_id(); ?></p>
            </div>
            <p style="font-size:.85rem;color:var(--text-light);margin-bottom:28px;">You will receive a confirmation email at <strong><?php echo htmlspecialchars($_SESSION['email']); ?></strong></p>
            <div style="display:flex;gap:12px;justify-content:center;">
                <a href="profile.php?tab=orders" class="btn btn-primary">View My Orders</a>
                <a href="browse.php" class="btn btn-outline">Continue Shopping</a>
            </div>
        </div>

        <?php else: ?>
        <!-- ================================================
             CHECKOUT FORM
             ================================================ -->
        <div class="checkout-layout">
            <div>
                <?php if (!empty($order_error)): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($order_error); ?></div>
                <?php endif; ?>

                <form method="POST" action="checkout.php">
                    <!-- Delivery Address -->
                    <div class="profile-card" style="margin-bottom:24px;">
                        <div class="profile-card-header">
                            <h3>Select Delivery Address</h3>
                            <a href="profile.php?tab=addresses" class="btn btn-outline btn-sm">Add New Address</a>
                        </div>
                        <div class="profile-card-body">
                            <?php if (empty($addresses)): ?>
                            <div class="alert alert-warning">
                                No delivery addresses saved. <a href="profile.php?tab=addresses">Add one here</a>.
                            </div>
                            <?php else: ?>
                            <?php foreach ($addresses as $i => $addr): ?>
                            <div class="address-card <?php echo $i === 0 ? 'selected' : ''; ?>"
                                 onclick="this.closest('form').querySelector('[name=address_id]').value='<?php echo $addr['address_id']; ?>';document.querySelectorAll('.address-card').forEach(c=>c.classList.remove('selected'));this.classList.add('selected');">
                                <input type="radio" class="address-radio" name="address_id" value="<?php echo $addr['address_id']; ?>"
                                       <?php echo $i === 0 ? 'checked' : ''; ?>>
                                <div>
                                    <span class="address-tag"><?php echo ucfirst($addr['address_type']); ?></span>
                                    <h4><?php echo $_SESSION['first_name'] . ' ' . $_SESSION['last_name']; ?></h4>
                                    <p>
                                        <?php echo htmlspecialchars($addr['street']); ?>,
                                        <?php echo htmlspecialchars($addr['city']); ?>,
                                        <?php echo htmlspecialchars($addr['province']); ?>,
                                        <?php echo htmlspecialchars($addr['postal_code']); ?>
                                    </p>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Order Items Summary -->
                    <div class="profile-card">
                        <div class="profile-card-header"><h3>Your Items</h3></div>
                        <div class="profile-card-body" style="padding:0;">
                            <?php foreach ($cart_items as $ci): ?>
                            <div style="display:flex;gap:14px;align-items:center;padding:16px 24px;border-bottom:1px solid var(--mid-gray);">
                                <img src="<?php echo getItemImage($ci['photo'], $ci['brand']); ?>"
                                     alt="<?php echo htmlspecialchars($ci['item_name']); ?>"
                                     onerror="this.src='images/placeholder.jpg'"
                                     style="width:60px;height:60px;border-radius:8px;object-fit:cover;flex-shrink:0;">
                                <div style="flex:1;">
                                    <p style="font-weight:600;font-size:.9rem;color:var(--navy);"><?php echo htmlspecialchars($ci['item_name']); ?></p>
                                    <p style="font-size:.78rem;color:var(--text-light);"><?php echo htmlspecialchars($ci['brand']); ?> &bull; Size <?php echo htmlspecialchars($ci['size']); ?></p>
                                    <p style="font-size:.78rem;color:var(--text-light);">Qty: <?php echo $ci['quantity']; ?></p>
                                </div>
                                <span style="font-weight:700;color:var(--navy);">R<?php echo number_format($ci['line_total'], 0); ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <input type="hidden" name="address_id" value="<?php echo !empty($addresses) ? $addresses[0]['address_id'] : 0; ?>">

                    <!-- Submit -->
                    <?php if (!empty($addresses)): ?>
                    <div style="margin-top:24px;">
                        <button type="submit" name="confirm_order" class="btn btn-primary btn-lg btn-block">
                            🔒 Confirm Order — R<?php echo number_format($total, 0); ?>
                        </button>
                    </div>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Order Summary Sidebar -->
            <div>
                <div class="order-summary">
                    <h3>Order Summary</h3>
                    <div class="summary-row"><span>Subtotal</span><span>R<?php echo number_format($subtotal, 0); ?></span></div>
                    <div class="summary-row"><span>Delivery</span><span>R<?php echo number_format($delivery_fee, 0); ?></span></div>
                    <div class="summary-row"><span>Service fee</span><span>R<?php echo number_format($service_fee, 0); ?></span></div>
                    <div class="summary-row total"><span>Total (incl. VAT)</span><span>R<?php echo number_format($total, 0); ?></span></div>
                    <div class="secure-note" style="margin-top:20px;">
                        🔒
                        <div><strong style="color:var(--navy);display:block;">Secure Checkout</strong>Your payment and personal information are safe.</div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
