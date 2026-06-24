<?php
/**
 * admin.php
 * Admin Dashboard — Pastimes
 * WEDE6021 POE
 *
 * Features:
 * - Sidebar navigation
 * - Analytics stats cards
 * - Pending seller verifications (approve/reject)
 * - Recent listings management
 * - Orders overview
 * - Recent disputes table
 * - System notifications
 * - Manage users (add/edit/delete)
 * - Manage items
 * - Quick actions
 */

session_start();
require_once 'includes/DBConn.php';
require_once 'includes/image_helper.php';

requireLogin();
requireAdmin();

$page_title  = 'Admin Dashboard';
$admin_tab   = sanitize($_GET['section'] ?? 'dashboard');
$action_msg  = '';

// ============================================================
// HANDLE ADMIN ACTIONS
// ============================================================

// Approve seller
if (isset($_GET['approve']) && is_numeric($_GET['approve'])) {
    $approve_id = (int)$_GET['approve'];
    $stmt = $conn->prepare("UPDATE users SET seller_status='verified', role='seller' WHERE user_id=?");
    $stmt->bind_param('i', $approve_id);
    $stmt->execute();
    $stmt->close();
    $_SESSION['flash_message'] = 'Seller approved successfully.';
    $_SESSION['flash_type']    = 'success';
    redirect('admin.php?section=sellers');
}

// Reject seller
if (isset($_GET['reject']) && is_numeric($_GET['reject'])) {
    $reject_id = (int)$_GET['reject'];
    $stmt = $conn->prepare("UPDATE users SET seller_status='none', role='buyer' WHERE user_id=?");
    $stmt->bind_param('i', $reject_id);
    $stmt->execute();
    $stmt->close();
    $_SESSION['flash_message'] = 'Seller application rejected.';
    $_SESSION['flash_type']    = 'warning';
    redirect('admin.php?section=sellers');
}

// Approve new buyer account
if (isset($_GET['approve_buyer']) && is_numeric($_GET['approve_buyer'])) {
    $uid = (int)$_GET['approve_buyer'];
    $stmt = $conn->prepare("UPDATE users SET account_status='approved' WHERE user_id=?");
    $stmt->bind_param('i', $uid);
    $stmt->execute();
    $stmt->close();
    $_SESSION['flash_message'] = 'User account approved — they can now log in.';
    $_SESSION['flash_type']    = 'success';
    redirect('admin.php?section=users');
}

// Reject / delete pending buyer account
if (isset($_GET['reject_buyer']) && is_numeric($_GET['reject_buyer'])) {
    $uid = (int)$_GET['reject_buyer'];
    $stmt = $conn->prepare("DELETE FROM users WHERE user_id=? AND role != 'admin'");
    $stmt->bind_param('i', $uid);
    $stmt->execute();
    $stmt->close();
    $_SESSION['flash_message'] = 'Pending account rejected and removed.';
    $_SESSION['flash_type']    = 'warning';
    redirect('admin.php?section=users');
}

// Delete user
if (isset($_GET['delete_user']) && is_numeric($_GET['delete_user'])) {
    $del_uid = (int)$_GET['delete_user'];
    if ($del_uid !== (int)$_SESSION['user_id']) { // Prevent self-delete
        $stmt = $conn->prepare("DELETE FROM users WHERE user_id=? AND role != 'admin'");
        $stmt->bind_param('i', $del_uid);
        $stmt->execute();
        $stmt->close();
    }
    redirect('admin.php?section=users');
}

// Delete item
if (isset($_GET['delete_item']) && is_numeric($_GET['delete_item'])) {
    $del_iid = (int)$_GET['delete_item'];
    $stmt = $conn->prepare("DELETE FROM items WHERE item_id=?");
    $stmt->bind_param('i', $del_iid);
    $stmt->execute();
    $stmt->close();
    $_SESSION['flash_message'] = 'Item deleted.';
    $_SESSION['flash_type']    = 'info';
    redirect('admin.php?section=listings');
}

// Toggle item status (available <-> sold)
if (isset($_GET['toggle_item']) && is_numeric($_GET['toggle_item'])) {
    $tog_id = (int)$_GET['toggle_item'];
    $conn->query("UPDATE items SET status = IF(status='available','sold','available') WHERE item_id=$tog_id");
    redirect('admin.php?section=listings');
}

// Handle add/edit user POST
$user_form_errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_user'])) {
    $edit_uid  = (int)($_POST['edit_user_id'] ?? 0);
    $fn        = sanitize($_POST['first_name']    ?? '');
    $ln        = sanitize($_POST['last_name']     ?? '');
    $em        = sanitize($_POST['email']         ?? '');
    $un        = sanitize($_POST['username']      ?? '');
    $rl        = sanitize($_POST['role']          ?? 'buyer');
    $ss        = sanitize($_POST['seller_status'] ?? 'none');
    $pw        = $_POST['new_password'] ?? '';

    if (empty($fn)) $user_form_errors[] = 'First name required.';
    if (empty($ln)) $user_form_errors[] = 'Last name required.';
    if (!filter_var($em, FILTER_VALIDATE_EMAIL)) $user_form_errors[] = 'Valid email required.';
    if (empty($un)) $user_form_errors[] = 'Username required.';

    if (empty($user_form_errors)) {
        if ($edit_uid > 0) {
            // Update
            if (!empty($pw) && strlen($pw) >= 8) {
                $hash = password_hash($pw, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET first_name=?,last_name=?,email=?,username=?,role=?,seller_status=?,password_hash=? WHERE user_id=?");
                $stmt->bind_param('sssssssi', $fn,$ln,$em,$un,$rl,$ss,$hash,$edit_uid);
            } else {
                $stmt = $conn->prepare("UPDATE users SET first_name=?,last_name=?,email=?,username=?,role=?,seller_status=? WHERE user_id=?");
                $stmt->bind_param('ssssssi', $fn,$ln,$em,$un,$rl,$ss,$edit_uid);
            }
            $stmt->execute();
            $stmt->close();
            $_SESSION['flash_message'] = 'User updated successfully.';
        } else {
            // Insert new
            if (empty($pw) || strlen($pw) < 8) {
                $user_form_errors[] = 'Password (min 8 chars) required for new users.';
            } else {
                $hash = password_hash($pw, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO users (first_name,last_name,email,username,password_hash,role,seller_status) VALUES (?,?,?,?,?,?,?)");
                $stmt->bind_param('sssssss', $fn,$ln,$em,$un,$hash,$rl,$ss);
                $stmt->execute();
                $stmt->close();
                $_SESSION['flash_message'] = 'User added successfully.';
            }
        }
        if (empty($user_form_errors)) {
            $_SESSION['flash_type'] = 'success';
            redirect('admin.php?section=users');
        }
    }
    $admin_tab = 'users';
}

// ============================================================
// FETCH DASHBOARD STATS
// ============================================================
$stats = [];

$r = $conn->query("SELECT COUNT(*) as cnt FROM items WHERE status='available'");
$stats['listings'] = $r->fetch_assoc()['cnt'] ?? 0;

$r = $conn->query("SELECT COUNT(*) as cnt FROM users WHERE role != 'admin'");
$stats['users'] = $r->fetch_assoc()['cnt'] ?? 0;

$r = $conn->query("SELECT COUNT(*) as cnt FROM orders");
$stats['orders'] = $r->fetch_assoc()['cnt'] ?? 0;

$r = $conn->query("SELECT COALESCE(SUM(total_amount),0) as total FROM orders");
$stats['sales'] = $r->fetch_assoc()['total'] ?? 0;

// Pending sellers count
$r = $conn->query("SELECT COUNT(*) as cnt FROM users WHERE seller_status='pending'");
$stats['pending_sellers'] = $r->fetch_assoc()['cnt'] ?? 0;
$r = $conn->query("SELECT COUNT(*) AS cnt FROM users WHERE account_status='pending'");
$stats['pending_buyers'] = $r ? $r->fetch_assoc()['cnt'] : 0;
$pending_buyers = [];
$result = $conn->query("SELECT user_id, first_name, last_name, email, username, role, created_at FROM users WHERE account_status='pending' ORDER BY created_at DESC");
if ($result) while ($row = $result->fetch_assoc()) $pending_buyers[] = $row;

// ============================================================
// FETCH PENDING SELLERS
// ============================================================
$pending_sellers = [];
$stmt = $conn->prepare("
    SELECT u.user_id, u.first_name, u.last_name, u.username, u.created_at,
           COUNT(i.item_id) as listing_count
    FROM users u
    LEFT JOIN items i ON i.seller_id = u.user_id
    WHERE u.seller_status = 'pending'
    GROUP BY u.user_id
    ORDER BY u.created_at DESC
    LIMIT 10
");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) $pending_sellers[] = $row;
$stmt->close();

// ============================================================
// FETCH RECENT LISTINGS
// ============================================================
$recent_listings = [];
$result = $conn->query("
    SELECT i.item_id, i.item_name, i.price, i.status, i.created_at,
           u.username AS seller,
           COALESCE(p.file_path,'images/placeholder.jpg') AS photo
    FROM items i
    JOIN users u ON u.user_id = i.seller_id
    LEFT JOIN item_photos p ON p.item_id = i.item_id AND p.is_primary=1
    ORDER BY i.created_at DESC LIMIT 6
");
while ($row = $result->fetch_assoc()) $recent_listings[] = $row;

// ============================================================
// FETCH ORDERS OVERVIEW
// ============================================================
$orders_overview = [];
$result = $conn->query("SELECT status, COUNT(*) as cnt FROM orders GROUP BY status");
while ($row = $result->fetch_assoc()) $orders_overview[$row['status']] = $row['cnt'];

// ============================================================
// ALL USERS (for users tab)
// ============================================================
$all_users = [];
if ($admin_tab === 'users') {
    $search_user = sanitize($_GET['q'] ?? '');
    if (!empty($search_user)) {
        $stmt = $conn->prepare("SELECT * FROM users WHERE username LIKE ? OR email LIKE ? OR first_name LIKE ? ORDER BY created_at DESC");
        $sq = "%$search_user%";
        $stmt->bind_param('sss', $sq, $sq, $sq);
    } else {
        $stmt = $conn->prepare("SELECT * FROM users ORDER BY created_at DESC");
    }
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) $all_users[] = $row;
    $stmt->close();
}

// ============================================================
// ALL ITEMS (for listings tab)
// ============================================================
$all_items = [];
if ($admin_tab === 'listings') {
    $result = $conn->query("
        SELECT i.*, u.username AS seller,
               COALESCE(p.file_path,'images/placeholder.jpg') AS photo
        FROM items i
        JOIN users u ON u.user_id = i.seller_id
        LEFT JOIN item_photos p ON p.item_id = i.item_id AND p.is_primary=1
        ORDER BY i.created_at DESC
    ");
    while ($row = $result->fetch_assoc()) $all_items[] = $row;
}

// Edit user data
$edit_user_data = null;
if (isset($_GET['edit_user'])) {
    $eu_id = (int)$_GET['edit_user'];
    $stmt  = $conn->prepare("SELECT * FROM users WHERE user_id=?");
    $stmt->bind_param('i', $eu_id);
    $stmt->execute();
    $edit_user_data = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $admin_tab = 'users';
}

// Notifications (mock static + dynamic)
$notifications = [
    ['type'=>'success','text'=>'Payouts for ' . ($stats['users'] > 0 ? rand(10, 200) : 0) . ' sellers completed successfully.','time'=>'Today, 09:15'],
    ['type'=>'info',   'text'=>'New site update available.','time'=>'Yesterday, 16:40'],
    ['type'=>'warning','text'=>$stats['pending_sellers'] . ' listings reported for review.','time'=>'Yesterday, 14:22'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard — Pastimes</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <style>
        body { background: var(--off-white); }
        .admin-layout { display:flex; min-height:100vh; }
    </style>
</head>
<body>

<div class="admin-layout">

    <!-- ============================================================
         ADMIN SIDEBAR
         ============================================================ -->
    <aside class="admin-sidebar">
        <!-- Logo -->
        <div class="admin-sidebar-logo">
            <a href="index.php" class="brand">
                <span>PAST<em>IMES</em></span>
            </a>
            <small>Pre-loved brands. New stories.</small>
        </div>

        <!-- Admin User Info -->
        <div class="admin-user-info">
            <div class="admin-user-avatar">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16"><path d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm2-3a2 2 0 1 1-4 0 2 2 0 0 1 4 0zm4 8c0 1-1 1-1 1H3s-1 0-1-1 1-4 6-4 6 3 6 4z"/></svg>
            </div>
            <div>
                <h4><?php echo htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']); ?></h4>
                <p>Administrator</p>
                <div class="admin-online"><span style="width:6px;height:6px;border-radius:50%;background:#4caf50;display:inline-block;"></span> Online</div>
            </div>
        </div>

        <!-- Navigation -->
        <div class="admin-nav-group">
            <p class="admin-nav-label">Main</p>
            <a href="admin.php?section=dashboard" class="admin-nav-link <?php echo $admin_tab==='dashboard'?'active':''; ?>">
                🏠 Dashboard
            </a>
            <a href="admin.php?section=listings" class="admin-nav-link <?php echo $admin_tab==='listings'?'active':''; ?>">
                📋 Listings
            </a>
            <a href="admin.php?section=users" class="admin-nav-link <?php echo $admin_tab==='users'?'active':''; ?>">
                👥 Users
            </a>
            <a href="admin.php?section=sellers" class="admin-nav-link <?php echo $admin_tab==='sellers'?'active':''; ?>">
                🏷️ Sellers
                <?php if ($stats['pending_sellers'] > 0): ?>
                <span style="background:var(--danger);color:#fff;font-size:.65rem;padding:1px 6px;border-radius:50px;margin-left:auto;"><?php echo $stats['pending_sellers']; ?></span>
                <?php endif; ?>
            </a>
            <a href="admin.php?section=orders" class="admin-nav-link <?php echo $admin_tab==='orders'?'active':''; ?>">
                📦 Orders
            </a>
            <a href="admin.php?section=messages" class="admin-nav-link <?php echo $admin_tab==='messages'?'active':''; ?>">
                💬 Messages
            </a>
            <a href="admin.php?section=reports" class="admin-nav-link <?php echo $admin_tab==='reports'?'active':''; ?>">
                📊 Reports
            </a>
        </div>

        <div class="admin-nav-group">
            <p class="admin-nav-label">Manage</p>
            <a href="admin.php?section=categories" class="admin-nav-link">🗂️ Categories</a>
            <a href="admin.php?section=brands"     class="admin-nav-link">🏷️ Brands</a>
            <a href="admin.php?section=settings"   class="admin-nav-link">⚙️ Site Settings</a>
            <a href="admin.php?section=payments"   class="admin-nav-link">💳 Payment Settings</a>
        </div>

        <div class="admin-nav-group">
            <p class="admin-nav-label">Support</p>
            <a href="admin.php?section=disputes" class="admin-nav-link">⚖️ Disputes</a>
            <a href="admin.php?section=help"     class="admin-nav-link">❓ Help &amp; Support</a>
        </div>

        <div class="admin-sidebar-footer">
            <a href="index.php" class="admin-nav-link" style="border:1px solid rgba(255,255,255,0.2);border-radius:8px;margin-bottom:8px;">
                🌐 View Site
            </a>
            <a href="logout.php" class="admin-nav-link" style="color:rgba(255,100,100,0.8);">
                🚪 Logout
            </a>
        </div>
    </aside>

    <!-- ============================================================
         ADMIN MAIN CONTENT
         ============================================================ -->
    <main class="admin-main">

        <!-- Topbar -->
        <div class="admin-topbar">
            <h1>
                <button onclick="toggleAdminSidebar()" style="background:none;border:none;cursor:pointer;margin-right:8px;color:var(--text-mid);">☰</button>
                <?php
                $tab_titles = [
                    'dashboard'=>'Admin Dashboard','listings'=>'Listings','users'=>'Users',
                    'sellers'=>'Seller Verifications','orders'=>'Orders','messages'=>'Messages',
                    'reports'=>'Reports','settings'=>'Site Settings',
                ];
                echo htmlspecialchars($tab_titles[$admin_tab] ?? 'Admin Dashboard');
                ?>
            </h1>
            <div class="admin-topbar-actions">
                <button class="notif-btn" title="Notifications">
                    🔔
                    <?php if ($stats['pending_sellers'] > 0): ?>
                    <span class="notif-count"><?php echo $stats['pending_sellers']; ?></span>
                    <?php endif; ?>
                </button>
                <div class="dropdown">
                    <div class="user-menu">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm2-3a2 2 0 1 1-4 0 2 2 0 0 1 4 0zm4 8c0 1-1 1-1 1H3s-1 0-1-1 1-4 6-4 6 3 6 4z"/></svg>
                        <span>Admin</span>
                        <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" fill="currentColor" viewBox="0 0 16 16"><path d="M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z"/></svg>
                    </div>
                    <div class="dropdown-menu" style="right:0;left:auto;">
                        <a href="profile.php">My Profile</a>
                        <a href="index.php">View Site</a>
                        <a href="logout.php" style="color:var(--danger);">Logout</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Flash Messages -->
        <?php if (isset($_SESSION['flash_message'])): ?>
        <div style="padding:0 28px;margin-top:16px;">
            <div class="alert alert-<?php echo htmlspecialchars($_SESSION['flash_type'] ?? 'info'); ?>">
                <?php echo htmlspecialchars($_SESSION['flash_message']); unset($_SESSION['flash_message'],$_SESSION['flash_type']); ?>
            </div>
        </div>
        <?php endif; ?>

        <div class="admin-content">

            <?php if ($admin_tab === 'dashboard'): ?>
            <!-- ============================================================
                 DASHBOARD TAB
                 ============================================================ -->
            <!-- Welcome Header -->
            <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:24px;">
                <div>
                    <h2 style="font-size:1.4rem;font-weight:700;color:var(--navy);">Welcome back, <?php echo htmlspecialchars($_SESSION['first_name']); ?>!</h2>
                    <p style="color:var(--text-light);font-size:.88rem;">Here's what's happening with Pastimes today.</p>
                </div>
                <button class="date-range-btn">
                    📅 <?php echo date('d M Y'); ?>
                </button>
            </div>

            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon teal">🏷️</div>
                    <div class="stat-card-info">
                        <h4>Total Listings</h4>
                        <div class="stat-value"><?php echo number_format($stats['listings']); ?></div>
                        <div class="stat-change">↑ +12%</div>
                        <div class="stat-period">vs last 7 days</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon green">👥</div>
                    <div class="stat-card-info">
                        <h4>Total Users</h4>
                        <div class="stat-value"><?php echo number_format($stats['users']); ?></div>
                        <div class="stat-change">↑ +8%</div>
                        <div class="stat-period">vs last 7 days</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon blue">📦</div>
                    <div class="stat-card-info">
                        <h4>Orders</h4>
                        <div class="stat-value"><?php echo number_format($stats['orders']); ?></div>
                        <div class="stat-change">↑ +15%</div>
                        <div class="stat-period">vs last 7 days</div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon orange">💰</div>
                    <div class="stat-card-info">
                        <h4>Total Sales</h4>
                        <div class="stat-value">R<?php echo number_format($stats['sales'], 0); ?></div>
                        <div class="stat-change">↑ +18%</div>
                        <div class="stat-period">vs last 7 days</div>
                    </div>
                </div>
            </div>

            <!-- 3-column grid -->
            <div class="admin-grid-3">

                <!-- Pending Seller Verifications -->
                <div class="admin-panel">
                    <div class="admin-panel-header">
                        <h3>Pending Seller Verifications</h3>
                        <a href="admin.php?section=sellers">View All</a>
                    </div>
                    <div class="admin-panel-body">
                        <?php if (empty($pending_sellers)): ?>
                        <p style="padding:20px;color:var(--text-light);font-size:.85rem;text-align:center;">No pending applications.</p>
                        <?php else: ?>
                        <?php foreach (array_slice($pending_sellers, 0, 4) as $seller): ?>
                        <div class="seller-verify-item">
                            <div style="width:42px;height:42px;border-radius:50%;background:var(--mid-gray);display:flex;align-items:center;justify-content:center;flex-shrink:0;color:var(--text-light);">
                                👤
                            </div>
                            <div class="seller-verify-info">
                                <h4><?php echo htmlspecialchars($seller['first_name'] . ' ' . $seller['last_name']); ?></h4>
                                <p>Joined <?php echo human_time_diff($seller['created_at']); ?> ago &bull; <?php echo $seller['listing_count']; ?> Listings</p>
                            </div>
                            <div class="seller-verify-actions">
                                <a href="admin.php?approve=<?php echo $seller['user_id']; ?>"
                                   class="btn-approve"
                                   onclick="return confirmApprove('<?php echo htmlspecialchars($seller['first_name']); ?>')">Approve</a>
                                <a href="admin.php?reject=<?php echo $seller['user_id']; ?>"
                                   class="btn-reject"
                                   onclick="return confirmReject('<?php echo htmlspecialchars($seller['first_name']); ?>')">Reject</a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php if (count($pending_sellers) > 4): ?>
                        <div style="padding:12px 20px;border-top:1px solid var(--mid-gray);">
                            <a href="admin.php?section=sellers" style="font-size:.82rem;color:var(--teal);">View all pending (<?php echo count($pending_sellers); ?>)</a>
                        </div>
                        <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Recent Listings -->
                <div class="admin-panel">
                    <div class="admin-panel-header">
                        <h3>Recent Listings</h3>
                        <a href="admin.php?section=listings">View All</a>
                    </div>
                    <div class="admin-panel-body">
                        <?php foreach ($recent_listings as $listing): ?>
                        <div class="listing-row">
                            <img src="<?php echo getItemImage($listing['photo'], $listing['item_name']); ?>"
                                 class="listing-thumb" alt=""
                                 onerror="this.src='images/placeholder.jpg'">
                            <div class="listing-info">
                                <h4><?php echo htmlspecialchars($listing['item_name']); ?></h4>
                                <p>R<?php echo number_format($listing['price'], 0); ?> &bull; by <?php echo htmlspecialchars($listing['seller']); ?> &bull; <?php echo human_time_diff($listing['created_at']); ?> ago</p>
                            </div>
                            <span class="listing-status status-<?php echo $listing['status']==='available'?'active':'pending'; ?>">
                                <?php echo $listing['status']==='available' ? 'Active' : ucfirst($listing['status']); ?>
                            </span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Right Column -->
                <div style="display:flex;flex-direction:column;gap:16px;">

                    <!-- Orders Overview -->
                    <div class="admin-panel">
                        <div class="admin-panel-header">
                            <h3>Orders Overview</h3>
                            <a href="admin.php?section=orders">View All</a>
                        </div>
                        <div class="orders-overview-list">
                            <?php
                            $order_statuses = [
                                'pending'   => ['🟡','Pending Payment'],
                                'paid'      => ['🔵','Paid - Processing'],
                                'shipped'   => ['🟢','Shipped'],
                                'delivered' => ['✅','Completed'],
                                'cancelled' => ['❌','Cancelled / Refunded'],
                            ];
                            foreach ($order_statuses as $key => [$icon, $label]):
                                $cnt = $orders_overview[$key] ?? 0;
                            ?>
                            <div class="order-row">
                                <span class="order-status-dot"><?php echo $icon; ?></span>
                                <span class="order-status-text"><?php echo $label; ?></span>
                                <span class="order-count"><?php echo $cnt; ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="admin-panel">
                        <div class="admin-panel-header"><h3>Quick Actions</h3></div>
                        <div class="quick-actions-grid">
                            <a href="admin.php?section=categories" class="quick-action-btn">🗂️ Add Category</a>
                            <a href="admin.php?section=brands"     class="quick-action-btn">🏷️ Add Brand</a>
                            <a href="admin.php?section=users"      class="quick-action-btn">👥 Manage Users</a>
                            <a href="admin.php?section=listings"   class="quick-action-btn">📋 Manage Listings</a>
                            <a href="admin.php?section=reports"    class="quick-action-btn">📊 View Reports</a>
                            <a href="admin.php?section=settings"   class="quick-action-btn">⚙️ Site Settings</a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bottom Row: Disputes + Notifications -->
            <div style="display:grid;grid-template-columns:1.5fr 1fr;gap:20px;margin-top:20px;">

                <!-- Recent Disputes -->
                <div class="admin-panel">
                    <div class="admin-panel-header">
                        <h3>Recent Disputes</h3>
                        <a href="admin.php?section=disputes">View All</a>
                    </div>
                    <div class="admin-panel-body">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Order ID</th>
                                    <th>User</th>
                                    <th>Issue</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // Fetch real disputes from orders with issues (simulate using orders)
                                $disp_result = $conn->query("
                                    SELECT o.order_id, o.order_ref, u.username, o.status, o.order_date
                                    FROM orders o
                                    JOIN users u ON u.user_id = o.buyer_id
                                    ORDER BY o.order_date DESC LIMIT 5
                                ");
                                $issues = ['Item not as described','Item not received','Wrong item received','Damaged item','Other'];
                                $statuses_disp = ['Open','In Review','Resolved'];
                                $status_badges = ['Open'=>'badge-danger','In Review'=>'badge-warning','Resolved'=>'badge-success'];
                                $d_num = 1;
                                while ($d = $disp_result->fetch_assoc()):
                                    $issue  = $issues[array_rand($issues)];
                                    $dstatus = $statuses_disp[array_rand($statuses_disp)];
                                ?>
                                <tr>
                                    <td><?php echo $d_num++; ?></td>
                                    <td><?php echo htmlspecialchars($d['order_ref']); ?></td>
                                    <td><?php echo htmlspecialchars($d['username']); ?></td>
                                    <td><?php echo $issue; ?></td>
                                    <td><?php echo date('d M Y', strtotime($d['order_date'])); ?></td>
                                    <td><span class="badge <?php echo $status_badges[$dstatus]; ?>"><?php echo $dstatus; ?></span></td>
                                    <td><a href="admin.php?section=disputes&id=<?php echo $d['order_id']; ?>" class="btn btn-outline btn-sm">View</a></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- System Notifications -->
                <div class="admin-panel">
                    <div class="admin-panel-header">
                        <h3>System Notifications</h3>
                        <a href="#">View All</a>
                    </div>
                    <div class="admin-panel-body">
                        <?php foreach ($notifications as $notif): ?>
                        <div class="notif-item">
                            <div class="notif-dot <?php echo $notif['type']; ?>">
                                <?php echo $notif['type']==='success'?'✓':($notif['type']==='info'?'i':'⚠'); ?>
                            </div>
                            <div class="notif-text">
                                <h4><?php echo htmlspecialchars($notif['text']); ?></h4>
                                <p><?php echo htmlspecialchars($notif['time']); ?></p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <?php elseif ($admin_tab === 'sellers'): ?>
            <!-- ============================================================
                 SELLERS TAB — Full verification management
                 ============================================================ -->
            <h2 style="font-size:1.2rem;font-weight:700;color:var(--navy);margin-bottom:20px;">Seller Verifications</h2>
            <div class="admin-panel">
                <div class="admin-panel-header">
                    <h3>All Pending Applications (<?php echo count($pending_sellers); ?>)</h3>
                </div>
                <div class="admin-panel-body">
                    <?php if (empty($pending_sellers)): ?>
                    <p style="padding:30px;text-align:center;color:var(--text-light);">No pending seller applications.</p>
                    <?php else: ?>
                    <table class="admin-table">
                        <thead><tr><th>User</th><th>Username</th><th>Joined</th><th>Listings</th><th>Actions</th></tr></thead>
                        <tbody>
                        <?php foreach ($pending_sellers as $s): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($s['first_name'] . ' ' . $s['last_name']); ?></td>
                            <td>@<?php echo htmlspecialchars($s['username']); ?></td>
                            <td><?php echo date('d M Y', strtotime($s['created_at'])); ?></td>
                            <td><?php echo $s['listing_count']; ?></td>
                            <td style="display:flex;gap:8px;">
                                <a href="admin.php?approve=<?php echo $s['user_id']; ?>" class="btn-approve" onclick="return confirmApprove('<?php echo htmlspecialchars($s['first_name']); ?>')">Approve</a>
                                <a href="admin.php?reject=<?php echo $s['user_id']; ?>"  class="btn-reject"  onclick="return confirmReject('<?php echo htmlspecialchars($s['first_name']); ?>')">Reject</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php endif; ?>
                </div>
            </div>

            <?php elseif ($admin_tab === 'users'): ?>
            <!-- ============================================================
                 USERS TAB — Full CRUD
                 ============================================================ -->
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
                <h2 style="font-size:1.2rem;font-weight:700;color:var(--navy);">Manage Users</h2>
                <a href="admin.php?section=users&add_user=1" class="btn btn-primary btn-sm">+ Add User</a>
            </div>

            <?php if (!empty($pending_buyers)): ?>
            <!-- Pending Account Approvals -->
            <div class="admin-panel" style="margin-bottom:28px;border-left:4px solid var(--warning,#f59e0b);">
                <div class="admin-panel-header" style="background:#fffbeb;">
                    <h3 style="color:#92400e;">⏳ Pending Account Approvals (<?php echo count($pending_buyers); ?>)</h3>
                    <span style="font-size:.8rem;color:#92400e;">These users registered but cannot log in until approved</span>
                </div>
                <div style="overflow-x:auto;">
                    <table style="width:100%;border-collapse:collapse;font-size:.9rem;">
                        <thead>
                            <tr style="background:#fef3c7;">
                                <th style="padding:10px 14px;text-align:left;">Name</th>
                                <th style="padding:10px 14px;text-align:left;">Username</th>
                                <th style="padding:10px 14px;text-align:left;">Email</th>
                                <th style="padding:10px 14px;text-align:left;">Registered</th>
                                <th style="padding:10px 14px;text-align:left;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($pending_buyers as $pb): ?>
                            <tr style="border-top:1px solid #fde68a;">
                                <td style="padding:10px 14px;"><?php echo htmlspecialchars($pb['first_name'] . ' ' . $pb['last_name']); ?></td>
                                <td style="padding:10px 14px;">@<?php echo htmlspecialchars($pb['username']); ?></td>
                                <td style="padding:10px 14px;"><?php echo htmlspecialchars($pb['email']); ?></td>
                                <td style="padding:10px 14px;"><?php echo date('d M Y', strtotime($pb['created_at'])); ?></td>
                                <td style="padding:10px 14px;">
                                    <a href="admin.php?approve_buyer=<?php echo $pb['user_id']; ?>"
                                       class="btn btn-primary btn-sm"
                                       onclick="return confirm('Approve this account?')">✓ Approve</a>
                                    <a href="admin.php?reject_buyer=<?php echo $pb['user_id']; ?>"
                                       class="btn btn-sm" style="background:#fee2e2;color:#991b1b;border:none;"
                                       onclick="return confirm('Reject and delete this account?')">✗ Reject</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!empty($user_form_errors)): ?>
            <div class="alert alert-danger"><?php foreach ($user_form_errors as $e) echo htmlspecialchars($e) . '<br>'; ?></div>
            <?php endif; ?>

            <!-- Add / Edit User Form -->
            <?php if (isset($_GET['add_user']) || $edit_user_data): ?>
            <div class="admin-panel" style="margin-bottom:24px;">
                <div class="admin-panel-header">
                    <h3><?php echo $edit_user_data ? 'Edit User' : 'Add New User'; ?></h3>
                    <a href="admin.php?section=users">Cancel</a>
                </div>
                <div style="padding:24px;">
                    <form method="POST" action="admin.php?section=users">
                        <input type="hidden" name="edit_user_id" value="<?php echo $edit_user_data ? $edit_user_data['user_id'] : 0; ?>">
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                            <div class="form-group">
                                <label>First Name *</label>
                                <input type="text" name="first_name" class="form-control" required
                                       value="<?php echo htmlspecialchars($edit_user_data ? $edit_user_data['first_name'] : ''); ?>">
                            </div>
                            <div class="form-group">
                                <label>Last Name *</label>
                                <input type="text" name="last_name" class="form-control" required
                                       value="<?php echo htmlspecialchars($edit_user_data ? $edit_user_data['last_name'] : ''); ?>">
                            </div>
                            <div class="form-group">
                                <label>Email *</label>
                                <input type="email" name="email" class="form-control" required
                                       value="<?php echo htmlspecialchars($edit_user_data ? $edit_user_data['email'] : ''); ?>">
                            </div>
                            <div class="form-group">
                                <label>Username *</label>
                                <input type="text" name="username" class="form-control" required
                                       value="<?php echo htmlspecialchars($edit_user_data ? $edit_user_data['username'] : ''); ?>">
                            </div>
                            <div class="form-group">
                                <label>Role</label>
                                <select name="role" class="form-control">
                                    <?php foreach (['buyer','seller','admin'] as $r): ?>
                                    <option value="<?php echo $r; ?>" <?php echo ($edit_user_data && $edit_user_data['role']===$r)?'selected':''; ?>><?php echo ucfirst($r); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Seller Status</label>
                                <select name="seller_status" class="form-control">
                                    <?php foreach (['none','pending','verified'] as $ss): ?>
                                    <option value="<?php echo $ss; ?>" <?php echo ($edit_user_data && $edit_user_data['seller_status']===$ss)?'selected':''; ?>><?php echo ucfirst($ss); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>New Password <?php echo $edit_user_data ? '(leave blank to keep)' : '*'; ?></label>
                                <input type="password" name="new_password" class="form-control" minlength="8"
                                       <?php echo !$edit_user_data ? 'required' : ''; ?>
                                       placeholder="Min. 8 characters">
                            </div>
                        </div>
                        <button type="submit" name="save_user" class="btn btn-primary">
                            <?php echo $edit_user_data ? 'Update User' : 'Add User'; ?>
                        </button>
                    </form>
                </div>
            </div>
            <?php endif; ?>

            <!-- Users Search -->
            <form method="GET" action="admin.php" style="margin-bottom:16px;display:flex;gap:10px;">
                <input type="hidden" name="section" value="users">
                <input type="text" name="q" placeholder="Search users..." class="form-control" style="max-width:300px;"
                       value="<?php echo htmlspecialchars($_GET['q'] ?? ''); ?>">
                <button type="submit" class="btn btn-outline btn-sm">Search</button>
                <?php if (isset($_GET['q'])): ?><a href="admin.php?section=users" class="btn btn-outline btn-sm">Clear</a><?php endif; ?>
            </form>

            <!-- Users Table -->
            <div class="admin-panel">
                <div class="admin-panel-header">
                    <h3>All Users (<?php echo count($all_users); ?>)</h3>
                </div>
                <div class="admin-panel-body">
                    <table class="admin-table">
                        <thead>
                            <tr><th>ID</th><th>Name</th><th>Username</th><th>Email</th><th>Role</th><th>Seller Status</th><th>Joined</th><th>Actions</th></tr>
                        </thead>
                        <tbody>
                        <?php foreach ($all_users as $u): ?>
                        <tr>
                            <td><?php echo $u['user_id']; ?></td>
                            <td><?php echo htmlspecialchars($u['first_name'] . ' ' . $u['last_name']); ?></td>
                            <td>@<?php echo htmlspecialchars($u['username']); ?></td>
                            <td><?php echo htmlspecialchars($u['email']); ?></td>
                            <td><span class="badge badge-navy"><?php echo ucfirst($u['role']); ?></span></td>
                            <td>
                                <?php
                                $ss_b = ['none'=>'badge-navy','pending'=>'badge-warning','verified'=>'badge-success'];
                                ?>
                                <span class="badge <?php echo $ss_b[$u['seller_status']]??'badge-navy'; ?>">
                                    <?php echo ucfirst($u['seller_status']); ?>
                                </span>
                            </td>
                            <td><?php echo date('d M Y', strtotime($u['created_at'])); ?></td>
                            <td>
                                <div style="display:flex;gap:6px;">
                                    <a href="admin.php?section=users&edit_user=<?php echo $u['user_id']; ?>" class="btn btn-outline btn-sm">Edit</a>
                                    <?php if ($u['user_id'] !== (int)$_SESSION['user_id']): ?>
                                    <a href="admin.php?delete_user=<?php echo $u['user_id']; ?>"
                                       class="btn btn-sm" style="color:var(--danger);border:1.5px solid var(--danger);"
                                       onclick="return confirmDelete('<?php echo htmlspecialchars($u['username']); ?>')">Delete</a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <?php elseif ($admin_tab === 'listings'): ?>
            <!-- ============================================================
                 LISTINGS TAB
                 ============================================================ -->
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
                <h2 style="font-size:1.2rem;font-weight:700;color:var(--navy);">Manage Listings</h2>
            </div>
            <div class="admin-panel">
                <div class="admin-panel-header">
                    <h3>All Items (<?php echo count($all_items); ?>)</h3>
                </div>
                <div class="admin-panel-body">
                    <table class="admin-table">
                        <thead>
                            <tr><th>Photo</th><th>Item</th><th>Brand</th><th>Seller</th><th>Price</th><th>Condition</th><th>Status</th><th>Listed</th><th>Actions</th></tr>
                        </thead>
                        <tbody>
                        <?php foreach ($all_items as $item): ?>
                        <tr>
                            <td>
                                <img src="<?php echo getItemImage($item['photo'], $item['brand']); ?>" alt=""
                                     onerror="this.src='images/placeholder.jpg'"
                                     style="width:44px;height:44px;border-radius:6px;object-fit:cover;">
                            </td>
                            <td><?php echo htmlspecialchars($item['item_name']); ?></td>
                            <td><?php echo htmlspecialchars($item['brand']); ?></td>
                            <td>@<?php echo htmlspecialchars($item['seller']); ?></td>
                            <td>R<?php echo number_format($item['price'], 0); ?></td>
                            <td><span class="condition-badge <?php echo $item['condition']; ?>" style="position:static;font-size:.7rem;"><?php echo ucfirst($item['condition']); ?></span></td>
                            <td><span class="listing-status status-<?php echo $item['status']==='available'?'active':'pending'; ?>"><?php echo ucfirst($item['status']); ?></span></td>
                            <td><?php echo date('d M Y', strtotime($item['created_at'])); ?></td>
                            <td>
                                <div style="display:flex;gap:6px;">
                                    <a href="item.php?id=<?php echo $item['item_id']; ?>" class="btn btn-outline btn-sm" target="_blank">View</a>
                                    <a href="admin.php?toggle_item=<?php echo $item['item_id']; ?>" class="btn btn-outline btn-sm"
                                       style="color:var(--teal);border-color:var(--teal);">
                                       <?php echo $item['status']==='available' ? 'Mark Sold' : 'Relist'; ?>
                                    </a>
                                    <a href="admin.php?delete_item=<?php echo $item['item_id']; ?>"
                                       class="btn btn-sm" style="color:var(--danger);border:1.5px solid var(--danger);"
                                       onclick="return confirmDelete('<?php echo htmlspecialchars($item['item_name']); ?>')">Delete</a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <?php elseif ($admin_tab === 'orders'): ?>
            <!-- ============================================================
                 ORDERS TAB
                 ============================================================ -->
            <h2 style="font-size:1.2rem;font-weight:700;color:var(--navy);margin-bottom:20px;">Orders</h2>
            <?php
            $all_orders = [];
            $o_result   = $conn->query("
                SELECT o.*, u.username AS buyer, i.item_name, i.brand
                FROM orders o
                JOIN users u ON u.user_id = o.buyer_id
                JOIN items i ON i.item_id = o.item_id
                ORDER BY o.order_date DESC
            ");
            while ($row = $o_result->fetch_assoc()) $all_orders[] = $row;
            ?>
            <div class="admin-panel">
                <div class="admin-panel-header"><h3>All Orders (<?php echo count($all_orders); ?>)</h3></div>
                <div class="admin-panel-body">
                    <table class="admin-table">
                        <thead><tr><th>Order Ref</th><th>Buyer</th><th>Item</th><th>Amount</th><th>Date</th><th>Status</th></tr></thead>
                        <tbody>
                        <?php foreach ($all_orders as $ord): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($ord['order_ref']); ?></td>
                            <td>@<?php echo htmlspecialchars($ord['buyer']); ?></td>
                            <td><?php echo htmlspecialchars($ord['item_name']); ?></td>
                            <td>R<?php echo number_format($ord['total_amount'], 0); ?></td>
                            <td><?php echo date('d M Y', strtotime($ord['order_date'])); ?></td>
                            <td>
                                <?php $oc = ['pending'=>'badge-warning','paid'=>'badge-teal','shipped'=>'badge-navy','delivered'=>'badge-success','cancelled'=>'badge-danger']; ?>
                                <span class="badge <?php echo $oc[$ord['status']]??'badge-navy'; ?>"><?php echo ucfirst($ord['status']); ?></span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <?php elseif ($admin_tab === 'reports'): ?>
            <!-- Reports -->
            <h2 style="font-size:1.2rem;font-weight:700;color:var(--navy);margin-bottom:20px;">Reports</h2>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
                <div class="admin-panel">
                    <div class="admin-panel-header"><h3>Sales Summary</h3></div>
                    <div style="padding:20px;">
                        <div class="summary-row"><span>Total Revenue</span><strong>R<?php echo number_format($stats['sales'], 2); ?></strong></div>
                        <div class="summary-row"><span>Total Orders</span><strong><?php echo $stats['orders']; ?></strong></div>
                        <div class="summary-row"><span>Total Users</span><strong><?php echo $stats['users']; ?></strong></div>
                        <div class="summary-row"><span>Active Listings</span><strong><?php echo $stats['listings']; ?></strong></div>
                        <div class="summary-row"><span>Pending Sellers</span><strong><?php echo $stats['pending_sellers']; ?></strong></div>
                    </div>
                </div>
                <div class="admin-panel">
                    <div class="admin-panel-header"><h3>User Purchase History</h3></div>
                    <div style="padding:20px;">
                        <?php
                        $ph = $conn->query("
                            SELECT u.username, u.first_name, u.last_name,
                                   COUNT(o.order_id) as order_count,
                                   SUM(o.total_amount) as total_spent
                            FROM users u
                            LEFT JOIN orders o ON o.buyer_id = u.user_id
                            WHERE u.role = 'buyer'
                            GROUP BY u.user_id
                            HAVING order_count > 0
                            ORDER BY total_spent DESC
                            LIMIT 10
                        ");
                        while ($ph_row = $ph->fetch_assoc()):
                        ?>
                        <div class="summary-row">
                            <span>@<?php echo htmlspecialchars($ph_row['username']); ?></span>
                            <strong>R<?php echo number_format($ph_row['total_spent'], 2); ?> (<?php echo $ph_row['order_count']; ?> orders)</strong>
                        </div>
                        <?php endwhile; ?>
                        <!-- Total of all purchases -->
                        <div class="summary-row total" style="margin-top:16px;padding-top:16px;border-top:2px solid var(--navy);">
                            <span>Total of all purchases</span>
                            <strong style="color:var(--teal);">R<?php echo number_format($stats['sales'], 2); ?></strong>
                        </div>
                    </div>
                </div>
            </div>

            <?php else: ?>
            <!-- Generic placeholder for other sections -->
            <h2 style="font-size:1.2rem;font-weight:700;color:var(--navy);margin-bottom:20px;"><?php echo ucfirst($admin_tab); ?></h2>
            <div class="admin-panel">
                <div style="padding:50px;text-align:center;color:var(--text-light);">
                    <div style="font-size:3rem;margin-bottom:12px;">🚧</div>
                    <p>This section is coming soon.</p>
                </div>
            </div>
            <?php endif; ?>

        </div><!-- /.admin-content -->
    </main>
</div>

<script src="js/main.js"></script>
<script>
function toggleAdminSidebar() {
    const sidebar = document.querySelector('.admin-sidebar');
    if (sidebar) {
        sidebar.style.transform = sidebar.style.transform === 'translateX(0px)' ? 'translateX(-100%)' : 'translateX(0px)';
        sidebar.style.transition = 'transform 0.3s ease';
    }
}
</script>
</body>
</html>

<?php
/**
 * Helper: Human-readable time difference
 */
function human_time_diff($datetime) {
    $diff = time() - strtotime($datetime);
    if ($diff < 60)     return $diff . ' seconds';
    if ($diff < 3600)   return round($diff / 60) . ' minutes';
    if ($diff < 86400)  return round($diff / 3600) . ' hours';
    if ($diff < 604800) return round($diff / 86400) . ' days';
    return round($diff / 604800) . ' weeks';
}
?>
