<?php
/**
 * profile.php
 * User Profile Page — Pastimes
 * WEDE6021 POE
 *
 * Features:
 * - View and edit personal info
 * - Manage delivery addresses
 * - View order history with totals
 * - View seller listings
 * - Seller status display
 */

session_start();
require_once 'includes/DBConn.php';
require_once 'includes/image_helper.php';

requireLogin();

$page_title = 'My Profile';
$user_id    = $_SESSION['user_id'];
$active_tab = sanitize($_GET['tab'] ?? 'profile');
$errors     = [];
$success    = '';

// ============================================================
// FETCH USER DATA (associative array)
// ============================================================
$stmt = $conn->prepare(
    "SELECT user_id, first_name, last_name, email, username, role, seller_status, created_at
     FROM users WHERE user_id = ?"
);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// ============================================================
// HANDLE PROFILE UPDATE
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $new_first = sanitize($_POST['first_name'] ?? '');
    $new_last  = sanitize($_POST['last_name']  ?? '');
    $new_email = sanitize($_POST['email']      ?? '');

    if (empty($new_first)) $errors['first_name'] = 'First name is required.';
    if (empty($new_last))  $errors['last_name']  = 'Last name is required.';
    if (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Invalid email.';

    // Check email uniqueness
    if (empty($errors)) {
        $chk = $conn->prepare("SELECT user_id FROM users WHERE email=? AND user_id!=?");
        $chk->bind_param('si', $new_email, $user_id);
        $chk->execute();
        if ($chk->get_result()->num_rows > 0) $errors['email'] = 'Email already in use.';
        $chk->close();
    }

    if (empty($errors)) {
        $upd = $conn->prepare("UPDATE users SET first_name=?, last_name=?, email=? WHERE user_id=?");
        $upd->bind_param('sssi', $new_first, $new_last, $new_email, $user_id);
        if ($upd->execute()) {
            $_SESSION['first_name'] = $new_first;
            $_SESSION['last_name']  = $new_last;
            $_SESSION['email']      = $new_email;
            $user['first_name']     = $new_first;
            $user['last_name']      = $new_last;
            $user['email']          = $new_email;
            $success = 'Profile updated successfully!';
        }
        $upd->close();
    }
    $active_tab = 'profile';
}

// ============================================================
// HANDLE ADD / EDIT ADDRESS
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_address'])) {
    $addr_id   = (int)($_POST['address_id'] ?? 0);
    $addr_type = sanitize($_POST['address_type'] ?? 'residential');
    $street    = sanitize($_POST['street']       ?? '');
    $city      = sanitize($_POST['city']         ?? '');
    $province  = sanitize($_POST['province']     ?? '');
    $postal    = sanitize($_POST['postal_code']  ?? '');

    if (empty($street) || empty($city) || empty($province) || empty($postal)) {
        $errors['address'] = 'All address fields are required.';
    }

    if (empty($errors)) {
        if ($addr_id > 0) {
            // Update existing
            $upd = $conn->prepare(
                "UPDATE addresses SET address_type=?,street=?,city=?,province=?,postal_code=?
                 WHERE address_id=? AND user_id=?"
            );
            $upd->bind_param('sssssii', $addr_type,$street,$city,$province,$postal,$addr_id,$user_id);
            $upd->execute();
            $upd->close();
        } else {
            // Insert new
            $ins = $conn->prepare(
                "INSERT INTO addresses (user_id,address_type,street,city,province,postal_code)
                 VALUES (?,?,?,?,?,?)"
            );
            $ins->bind_param('isssss', $user_id,$addr_type,$street,$city,$province,$postal);
            $ins->execute();
            $ins->close();
        }
        $success = 'Address saved successfully!';
    }
    $active_tab = 'addresses';
}

// ============================================================
// HANDLE DELETE ADDRESS
// ============================================================
if (isset($_GET['delete_addr'])) {
    $del_id = (int)$_GET['delete_addr'];
    $del    = $conn->prepare("DELETE FROM addresses WHERE address_id=? AND user_id=?");
    $del->bind_param('ii', $del_id, $user_id);
    $del->execute();
    $del->close();
    redirect('profile.php?tab=addresses&msg=deleted');
}

// ============================================================
// HANDLE DELETE LISTING (seller only)
// ============================================================
if (isset($_GET['delete_item']) && isVerifiedSeller()) {
    $del_item = (int)$_GET['delete_item'];
    $del      = $conn->prepare("DELETE FROM items WHERE item_id=? AND seller_id=?");
    $del->bind_param('ii', $del_item, $user_id);
    $del->execute();
    $del->close();
    redirect('profile.php?tab=listings&msg=deleted');
}

// ============================================================
// FETCH ADDRESSES
// ============================================================
$addresses = [];
$aStmt = $conn->prepare("SELECT * FROM addresses WHERE user_id=? ORDER BY address_type");
$aStmt->bind_param('i', $user_id);
$aStmt->execute();
$aResult = $aStmt->get_result();
while ($row = $aResult->fetch_assoc()) $addresses[] = $row;
$aStmt->close();

// ============================================================
// FETCH ORDER HISTORY (with totals)
// ============================================================
$orders = [];
$oStmt  = $conn->prepare("
    SELECT o.order_id, o.order_ref, o.order_date, o.status, o.total_amount,
           i.item_name, i.brand, i.size,
           COALESCE(p.file_path,'images/placeholder.jpg') AS photo,
           a.city, a.province
    FROM orders o
    JOIN items i ON i.item_id = o.item_id
    LEFT JOIN item_photos p ON p.item_id = i.item_id AND p.is_primary=1
    LEFT JOIN addresses a ON a.address_id = o.address_id
    WHERE o.buyer_id = ?
    ORDER BY o.order_date DESC
");
$oStmt->bind_param('i', $user_id);
$oStmt->execute();
$oResult = $oStmt->get_result();
$order_total = 0.0;
while ($row = $oResult->fetch_assoc()) {
    $orders[]     = $row;
    $order_total += $row['total_amount'];
}
$oStmt->close();

// ============================================================
// FETCH SELLER LISTINGS
// ============================================================
$listings = [];
if (isVerifiedSeller() || $_SESSION['seller_status'] === 'pending') {
    $lStmt = $conn->prepare("
        SELECT i.*, COALESCE(p.file_path,'images/placeholder.jpg') AS photo
        FROM items i
        LEFT JOIN item_photos p ON p.item_id=i.item_id AND p.is_primary=1
        WHERE i.seller_id=?
        ORDER BY i.created_at DESC
    ");
    $lStmt->bind_param('i', $user_id);
    $lStmt->execute();
    $lResult = $lStmt->get_result();
    while ($row = $lResult->fetch_assoc()) $listings[] = $row;
    $lStmt->close();
}

include 'includes/header.php';
?>

<div style="background:var(--off-white);padding:40px 0 60px;">
    <div class="container">
        <div class="profile-layout">

            <!-- ================================================
                 PROFILE SIDEBAR
                 ================================================ -->
            <div class="profile-sidebar">
                <div class="profile-sidebar-top">
                    <div class="profile-avatar">
                        <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" fill="currentColor" viewBox="0 0 16 16"><path d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm2-3a2 2 0 1 1-4 0 2 2 0 0 1 4 0zm4 8c0 1-1 1-1 1H3s-1 0-1-1 1-4 6-4 6 3 6 4z"/></svg>
                    </div>
                    <h3><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h3>
                    <p>@<?php echo htmlspecialchars($user['username']); ?></p>
                    <div class="profile-status">
                        <span class="status-dot"></span>
                        <?php
                        $role_label = ucfirst($user['role']);
                        if ($user['seller_status'] === 'verified') $role_label = 'Verified Seller';
                        elseif ($user['seller_status'] === 'pending') $role_label = 'Seller (Pending)';
                        echo htmlspecialchars($role_label);
                        ?>
                    </div>
                </div>
                <ul class="profile-nav">
                    <li><a href="profile.php?tab=profile"    class="<?php echo $active_tab==='profile'    ?'active':''; ?>">👤 My Profile</a></li>
                    <li><a href="profile.php?tab=orders"     class="<?php echo $active_tab==='orders'     ?'active':''; ?>">📦 My Orders (<?php echo count($orders); ?>)</a></li>
                    <li><a href="profile.php?tab=addresses"  class="<?php echo $active_tab==='addresses'  ?'active':''; ?>">📍 Delivery Addresses</a></li>
                    <?php if ($user['seller_status'] !== 'none'): ?>
                    <li><a href="profile.php?tab=listings"   class="<?php echo $active_tab==='listings'   ?'active':''; ?>">🏷️ My Listings (<?php echo count($listings); ?>)</a></li>
                    <?php endif; ?>
                    <li><a href="profile.php?tab=messages"   class="<?php echo $active_tab==='messages'   ?'active':''; ?>">💬 Messages</a></li>
                    <li><a href="profile.php?tab=security"   class="<?php echo $active_tab==='security'   ?'active':''; ?>">🔒 Security</a></li>
                    <li><a href="logout.php" style="color:var(--danger);">🚪 Logout</a></li>
                </ul>
            </div>

            <!-- ================================================
                 PROFILE CONTENT
                 ================================================ -->
            <div class="profile-content">

                <?php if (!empty($success)): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>
                <?php if (isset($_GET['msg']) && $_GET['msg']==='deleted'): ?>
                <div class="alert alert-info">Item deleted successfully.</div>
                <?php endif; ?>

                <!-- ============================================
                     TAB: PROFILE INFO
                     ============================================ -->
                <?php if ($active_tab === 'profile'): ?>
                <div class="profile-card">
                    <div class="profile-card-header">
                        <h3>Personal Information</h3>
                    </div>
                    <div class="profile-card-body">
                        <form method="POST" action="profile.php?tab=profile" novalidate>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>First Name</label>
                                    <input type="text" name="first_name" class="form-control <?php echo isset($errors['first_name'])?'is-invalid':''; ?>"
                                           value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                                    <?php if (isset($errors['first_name'])): ?><span class="invalid-feedback"><?php echo $errors['first_name']; ?></span><?php endif; ?>
                                </div>
                                <div class="form-group">
                                    <label>Last Name</label>
                                    <input type="text" name="last_name" class="form-control <?php echo isset($errors['last_name'])?'is-invalid':''; ?>"
                                           value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                                    <?php if (isset($errors['last_name'])): ?><span class="invalid-feedback"><?php echo $errors['last_name']; ?></span><?php endif; ?>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Email Address</label>
                                <input type="email" name="email" class="form-control <?php echo isset($errors['email'])?'is-invalid':''; ?>"
                                       value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                <?php if (isset($errors['email'])): ?><span class="invalid-feedback"><?php echo $errors['email']; ?></span><?php endif; ?>
                            </div>
                            <div class="form-group">
                                <label>Username</label>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['username']); ?>" disabled style="background:var(--light-gray);">
                                <small style="color:var(--text-light);">Username cannot be changed.</small>
                            </div>
                            <div class="form-group">
                                <label>Account Role</label>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars(ucfirst($user['role'])); ?>" disabled style="background:var(--light-gray);">
                            </div>
                            <div class="form-group">
                                <label>Seller Status</label>
                                <div style="display:flex;align-items:center;gap:10px;">
                                    <?php
                                    $status_colors = ['none'=>'#868e96','pending'=>'#e67e22','verified'=>'#2d9e5f'];
                                    $color = $status_colors[$user['seller_status']] ?? '#868e96';
                                    ?>
                                    <span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:<?php echo $color; ?>;"></span>
                                    <span style="font-weight:600;color:<?php echo $color; ?>;"><?php echo ucfirst($user['seller_status']); ?></span>
                                    <?php if ($user['seller_status'] === 'none'): ?>
                                    <a href="sell.php" class="btn btn-outline-teal btn-sm">Request Seller Status</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Member Since</label>
                                <input type="text" class="form-control" value="<?php echo date('d F Y', strtotime($user['created_at'])); ?>" disabled style="background:var(--light-gray);">
                            </div>
                            <button type="submit" name="update_profile" class="btn btn-primary">Save Changes</button>
                        </form>
                    </div>
                </div>

                <!-- ============================================
                     TAB: ORDERS
                     ============================================ -->
                <?php elseif ($active_tab === 'orders'): ?>
                <div class="profile-card">
                    <div class="profile-card-header">
                        <h3>Order History</h3>
                        <span style="font-size:.85rem;color:var(--text-light);"><?php echo count($orders); ?> orders</span>
                    </div>
                    <?php if (empty($orders)): ?>
                    <div class="profile-card-body" style="text-align:center;padding:50px;">
                        <div style="font-size:3rem;margin-bottom:12px;">📦</div>
                        <p style="color:var(--text-light);">You haven't placed any orders yet.</p>
                        <a href="browse.php" class="btn btn-primary" style="margin-top:16px;">Start Shopping</a>
                    </div>
                    <?php else: ?>
                    <div class="profile-card-body" style="padding:0;">
                        <?php foreach ($orders as $order): ?>
                        <div style="display:flex;gap:14px;align-items:center;padding:18px 24px;border-bottom:1px solid var(--mid-gray);">
                            <img src="<?php echo getItemImage($order['photo'], $order['brand']); ?>" alt=""
                                 onerror="this.src='images/placeholder.jpg'"
                                 style="width:60px;height:60px;border-radius:8px;object-fit:cover;flex-shrink:0;">
                            <div style="flex:1;">
                                <p style="font-weight:600;color:var(--navy);margin-bottom:2px;"><?php echo htmlspecialchars($order['item_name']); ?></p>
                                <p style="font-size:.78rem;color:var(--text-light);"><?php echo htmlspecialchars($order['brand']); ?> · Size <?php echo htmlspecialchars($order['size']); ?></p>
                                <p style="font-size:.78rem;color:var(--text-light);">Order: <?php echo htmlspecialchars($order['order_ref']); ?> · <?php echo date('d M Y', strtotime($order['order_date'])); ?></p>
                            </div>
                            <div style="text-align:right;">
                                <p style="font-weight:700;color:var(--navy);">R<?php echo number_format($order['total_amount'], 0); ?></p>
                                <?php
                                $sc = ['pending'=>'badge-warning','paid'=>'badge-teal','shipped'=>'badge-navy','delivered'=>'badge-success','cancelled'=>'badge-danger'];
                                $cls = $sc[$order['status']] ?? 'badge-navy';
                                ?>
                                <span class="badge <?php echo $cls; ?>"><?php echo ucfirst($order['status']); ?></span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <!-- Total report (POE requirement) -->
                        <div style="padding:16px 24px;background:var(--teal-pale);border-top:2px solid var(--teal);display:flex;justify-content:space-between;align-items:center;">
                            <strong style="color:var(--navy);">Total of all purchases:</strong>
                            <strong style="font-size:1.1rem;color:var(--teal);">R<?php echo number_format($order_total, 2); ?></strong>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- ============================================
                     TAB: ADDRESSES
                     ============================================ -->
                <?php elseif ($active_tab === 'addresses'): ?>
                <!-- Existing addresses -->
                <?php foreach ($addresses as $addr): ?>
                <div class="profile-card">
                    <div class="profile-card-header">
                        <h3><?php echo ucfirst($addr['address_type']); ?> Address</h3>
                        <div style="display:flex;gap:8px;">
                            <a href="profile.php?tab=addresses&edit=<?php echo $addr['address_id']; ?>" class="btn btn-outline btn-sm">Edit</a>
                            <a href="profile.php?delete_addr=<?php echo $addr['address_id']; ?>" class="btn btn-sm" style="color:var(--danger);border-color:var(--danger);"
                               onclick="return confirm('Delete this address?')">Delete</a>
                        </div>
                    </div>
                    <div class="profile-card-body">
                        <p><?php echo htmlspecialchars($addr['street']); ?>, <?php echo htmlspecialchars($addr['city']); ?>, <?php echo htmlspecialchars($addr['province']); ?>, <?php echo htmlspecialchars($addr['postal_code']); ?></p>
                    </div>
                </div>
                <?php endforeach; ?>

                <!-- Add / Edit Address Form -->
                <?php
                $edit_addr = null;
                if (isset($_GET['edit'])) {
                    $edit_id = (int)$_GET['edit'];
                    foreach ($addresses as $a) {
                        if ($a['address_id'] === $edit_id) { $edit_addr = $a; break; }
                    }
                }
                ?>
                <div class="profile-card">
                    <div class="profile-card-header">
                        <h3><?php echo $edit_addr ? 'Edit Address' : 'Add New Address'; ?></h3>
                    </div>
                    <div class="profile-card-body">
                        <?php if (isset($errors['address'])): ?><div class="alert alert-danger"><?php echo $errors['address']; ?></div><?php endif; ?>
                        <form method="POST" action="profile.php?tab=addresses">
                            <input type="hidden" name="address_id" value="<?php echo $edit_addr ? $edit_addr['address_id'] : 0; ?>">
                            <div class="form-group">
                                <label>Address Type</label>
                                <select name="address_type" class="form-control">
                                    <option value="residential" <?php echo (!$edit_addr || $edit_addr['address_type']==='residential') ? 'selected' : ''; ?>>Residential</option>
                                    <option value="work" <?php echo ($edit_addr && $edit_addr['address_type']==='work') ? 'selected' : ''; ?>>Work</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Street Address</label>
                                <input type="text" name="street" class="form-control" required
                                       value="<?php echo htmlspecialchars($edit_addr ? $edit_addr['street'] : ''); ?>">
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>City</label>
                                    <input type="text" name="city" class="form-control" required
                                           value="<?php echo htmlspecialchars($edit_addr ? $edit_addr['city'] : ''); ?>">
                                </div>
                                <div class="form-group">
                                    <label>Province</label>
                                    <select name="province" class="form-control">
                                        <?php foreach (['Western Cape','Eastern Cape','Northern Cape','North West','Gauteng','Limpopo','Mpumalanga','Free State','KwaZulu-Natal'] as $prov): ?>
                                        <option <?php echo ($edit_addr && $edit_addr['province']===$prov)?'selected':''; ?>><?php echo $prov; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Postal Code</label>
                                <input type="text" name="postal_code" class="form-control" required maxlength="10"
                                       value="<?php echo htmlspecialchars($edit_addr ? $edit_addr['postal_code'] : ''); ?>">
                            </div>
                            <button type="submit" name="save_address" class="btn btn-primary">
                                <?php echo $edit_addr ? 'Update Address' : 'Add Address'; ?>
                            </button>
                        </form>
                    </div>
                </div>

                <!-- ============================================
                     TAB: LISTINGS
                     ============================================ -->
                <?php elseif ($active_tab === 'listings'): ?>
                <div class="profile-card">
                    <div class="profile-card-header">
                        <h3>My Listings</h3>
                        <a href="sell.php" class="btn btn-primary btn-sm">+ New Listing</a>
                    </div>
                    <?php if (empty($listings)): ?>
                    <div class="profile-card-body" style="text-align:center;padding:50px;">
                        <p style="color:var(--text-light);">No listings yet.</p>
                        <a href="sell.php" class="btn btn-primary" style="margin-top:16px;">List an Item</a>
                    </div>
                    <?php else: ?>
                    <div class="profile-card-body" style="padding:0;">
                        <?php foreach ($listings as $listing): ?>
                        <div style="display:flex;gap:14px;align-items:center;padding:16px 24px;border-bottom:1px solid var(--mid-gray);">
                            <img src="<?php echo getItemImage($listing['photo'], $listing['brand']); ?>" alt=""
                                 onerror="this.src='images/placeholder.jpg'"
                                 style="width:60px;height:60px;border-radius:8px;object-fit:cover;">
                            <div style="flex:1;">
                                <p style="font-weight:600;color:var(--navy);"><?php echo htmlspecialchars($listing['item_name']); ?></p>
                                <p style="font-size:.78rem;color:var(--text-light);"><?php echo htmlspecialchars($listing['brand']); ?> · <?php echo htmlspecialchars($listing['category']); ?> · Size <?php echo htmlspecialchars($listing['size']); ?></p>
                                <p style="font-size:.78rem;color:var(--text-light);">Listed: <?php echo date('d M Y', strtotime($listing['created_at'])); ?></p>
                            </div>
                            <div style="text-align:right;">
                                <p style="font-weight:700;color:var(--navy);margin-bottom:6px;">R<?php echo number_format($listing['price'], 0); ?></p>
                                <?php $sc2=['available'=>'badge-success','sold'=>'badge-navy','pending'=>'badge-warning']; ?>
                                <span class="badge <?php echo $sc2[$listing['status']]??'badge-navy'; ?>"><?php echo ucfirst($listing['status']); ?></span>
                                <div style="display:flex;gap:6px;margin-top:8px;justify-content:flex-end;">
                                    <a href="item.php?id=<?php echo $listing['item_id']; ?>" class="btn btn-outline btn-sm">View</a>
                                    <a href="profile.php?delete_item=<?php echo $listing['item_id']; ?>&tab=listings"
                                       class="btn btn-sm" style="color:var(--danger);border:1.5px solid var(--danger);"
                                       onclick="return confirm('Delete this listing?')">Delete</a>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- ============================================
                     TAB: MESSAGES
                     ============================================ -->
                <?php elseif ($active_tab === 'messages'): ?>
                <?php
                $msgs = [];
                $mStmt = $conn->prepare("
                    SELECT m.*, i.item_name,
                           u.username AS other_user,
                           CASE WHEN m.sender_id=? THEN 'sent' ELSE 'received' END AS direction
                    FROM messages m
                    LEFT JOIN items i ON i.item_id = m.item_id
                    JOIN users u ON u.user_id = CASE WHEN m.sender_id=? THEN m.receiver_id ELSE m.sender_id END
                    WHERE m.sender_id=? OR m.receiver_id=?
                    ORDER BY m.sent_at DESC
                    LIMIT 20
                ");
                $mStmt->bind_param('iiii', $user_id, $user_id, $user_id, $user_id);
                $mStmt->execute();
                $mResult = $mStmt->get_result();
                while ($row = $mResult->fetch_assoc()) $msgs[] = $row;
                $mStmt->close();
                ?>
                <div class="profile-card">
                    <div class="profile-card-header"><h3>Messages</h3></div>
                    <?php if (empty($msgs)): ?>
                    <div class="profile-card-body" style="text-align:center;padding:50px;">
                        <p style="color:var(--text-light);">No messages yet.</p>
                    </div>
                    <?php else: ?>
                    <div class="profile-card-body" style="padding:0;">
                        <?php foreach ($msgs as $msg): ?>
                        <div style="padding:14px 24px;border-bottom:1px solid var(--mid-gray);">
                            <div style="display:flex;justify-content:space-between;margin-bottom:6px;">
                                <strong style="font-size:.85rem;color:var(--navy);">
                                    <?php echo $msg['direction']==='sent' ? 'To: ' : 'From: '; ?>
                                    <?php echo htmlspecialchars($msg['other_user']); ?>
                                </strong>
                                <span style="font-size:.75rem;color:var(--text-light);"><?php echo date('d M Y H:i', strtotime($msg['sent_at'])); ?></span>
                            </div>
                            <?php if ($msg['item_name']): ?>
                            <p style="font-size:.75rem;color:var(--teal);margin-bottom:4px;">Re: <?php echo htmlspecialchars($msg['item_name']); ?></p>
                            <?php endif; ?>
                            <p style="font-size:.85rem;color:var(--text-mid);"><?php echo htmlspecialchars($msg['message_text']); ?></p>
                            <?php if (!$msg['is_read'] && $msg['direction']==='received'): ?>
                            <span class="badge badge-teal" style="margin-top:4px;">New</span>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- ============================================
                     TAB: SECURITY
                     ============================================ -->
                <?php elseif ($active_tab === 'security'): ?>
                <div class="profile-card">
                    <div class="profile-card-header"><h3>Change Password</h3></div>
                    <div class="profile-card-body">
                        <?php
                        $pwd_success = '';
                        $pwd_error   = '';
                        if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['change_password'])) {
                            $curr  = $_POST['current_password'] ?? '';
                            $newp  = $_POST['new_password']     ?? '';
                            $confp = $_POST['confirm_new']      ?? '';
                            // Fetch current hash
                            $hStmt = $conn->prepare("SELECT password_hash FROM users WHERE user_id=?");
                            $hStmt->bind_param('i', $user_id);
                            $hStmt->execute();
                            $hRow = $hStmt->get_result()->fetch_assoc();
                            $hStmt->close();
                            if (!password_verify($curr, $hRow['password_hash'])) {
                                $pwd_error = 'Current password is incorrect.';
                            } elseif (strlen($newp) < 8) {
                                $pwd_error = 'New password must be at least 8 characters.';
                            } elseif ($newp !== $confp) {
                                $pwd_error = 'New passwords do not match.';
                            } else {
                                $newHash = password_hash($newp, PASSWORD_DEFAULT);
                                $uStmt   = $conn->prepare("UPDATE users SET password_hash=? WHERE user_id=?");
                                $uStmt->bind_param('si', $newHash, $user_id);
                                $uStmt->execute();
                                $uStmt->close();
                                $pwd_success = 'Password changed successfully!';
                            }
                        }
                        ?>
                        <?php if ($pwd_success): ?><div class="alert alert-success"><?php echo $pwd_success; ?></div><?php endif; ?>
                        <?php if ($pwd_error):   ?><div class="alert alert-danger"><?php echo $pwd_error; ?></div><?php endif; ?>
                        <form method="POST" action="profile.php?tab=security">
                            <div class="form-group">
                                <label>Current Password</label>
                                <input type="password" name="current_password" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label>New Password</label>
                                <input type="password" name="new_password" class="form-control" required minlength="8">
                            </div>
                            <div class="form-group">
                                <label>Confirm New Password</label>
                                <input type="password" name="confirm_new" class="form-control" required>
                            </div>
                            <button type="submit" name="change_password" class="btn btn-primary">Update Password</button>
                        </form>
                    </div>
                </div>
                <?php endif; ?>
            </div><!-- /.profile-content -->
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
