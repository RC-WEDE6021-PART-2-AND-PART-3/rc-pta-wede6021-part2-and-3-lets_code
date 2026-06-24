<?php
/**
 * header.php
 * Site-wide Navigation Header — Pastimes
 * WEDE6021 POE
 *
 * Include this file at the top of every public-facing page.
 * Requires session to be started before include.
 */

// Determine active page for nav highlighting
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) . ' — Pastimes' : 'Pastimes — Pre-loved Brands. New Stories.'; ?></title>
    <meta name="description" content="Buy and sell quality second-hand branded clothing. Pre-loved brands, new stories.">

    <!-- Google Fonts: Poppins -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Main Stylesheet -->
    <link rel="stylesheet" href="<?php echo isset($root_path) ? $root_path : ''; ?>css/style.css">

    <?php if (isset($extra_css)) echo $extra_css; ?>
</head>
<body>

<!-- ============================================================
     ANNOUNCEMENT BAR
     ============================================================ -->
<div class="announcement-bar">
    <div class="container">
        <span>🌿 Sustainable fashion. Great style. Better for the planet.</span>
        <span>Buy pre-loved. Reduce waste. Make a difference. ♡</span>
    </div>
</div>

<!-- ============================================================
     MAIN NAVBAR
     ============================================================ -->
<nav class="navbar">
    <div class="container">
        <!-- Brand Logo -->
        <a href="<?php echo isset($root_path) ? $root_path : ''; ?>index.php" class="navbar-brand">
            <img src="<?php echo isset($root_path) ? $root_path : ''; ?>images/logo.png"
                 alt="Pastimes Logo"
                 onerror="this.style.display='none'">
            <div>
                <span>PAST<em>IMES</em></span>
                <small class="brand-tagline">Pre-loved brands. New stories.</small>
            </div>
        </a>

        <!-- Navigation Links -->
        <ul class="nav-links">
            <li>
                <a href="<?php echo isset($root_path) ? $root_path : ''; ?>index.php"
                   class="<?php echo $current_page === 'index.php' ? 'active' : ''; ?>">
                    Home
                </a>
            </li>
            <li>
                <a href="<?php echo isset($root_path) ? $root_path : ''; ?>browse.php"
                   class="<?php echo $current_page === 'browse.php' ? 'active' : ''; ?>">
                    Browse
                </a>
            </li>
            <li>
                <a href="<?php echo isset($root_path) ? $root_path : ''; ?>sell.php"
                   class="<?php echo $current_page === 'sell.php' ? 'active' : ''; ?>">
                    Sell
                </a>
            </li>
            <li>
                <a href="<?php echo isset($root_path) ? $root_path : ''; ?>index.php#how-it-works"
                   class="<?php echo $current_page === 'how-it-works.php' ? 'active' : ''; ?>">
                    How It Works
                </a>
            </li>
            <?php if (isLoggedIn()): ?>
            <li class="dropdown">
                <a href="<?php echo isset($root_path) ? $root_path : ''; ?>profile.php"
                   class="<?php echo $current_page === 'profile.php' ? 'active' : ''; ?>">
                    Profile
                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="currentColor" viewBox="0 0 16 16">
                        <path d="M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z"/>
                    </svg>
                </a>
                <div class="dropdown-menu">
                    <a href="<?php echo isset($root_path) ? $root_path : ''; ?>profile.php">My Profile</a>
                    <a href="<?php echo isset($root_path) ? $root_path : ''; ?>profile.php?tab=orders">My Orders</a>
                    <a href="<?php echo isset($root_path) ? $root_path : ''; ?>profile.php?tab=addresses">Addresses</a>
                    <?php if (isVerifiedSeller()): ?>
                    <a href="<?php echo isset($root_path) ? $root_path : ''; ?>sell.php">List Item</a>
                    <?php endif; ?>
                    <?php if (isAdmin()): ?>
                    <a href="<?php echo isset($root_path) ? $root_path : ''; ?>admin.php">Admin Panel</a>
                    <?php endif; ?>
                    <a href="<?php echo isset($root_path) ? $root_path : ''; ?>logout.php" style="color: #e74c3c;">Logout</a>
                </div>
            </li>
            <?php endif; ?>
        </ul>

        <!-- Search Bar -->
        <form class="search-form" action="<?php echo isset($root_path) ? $root_path : ''; ?>browse.php" method="GET">
            <button type="submit">
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.099zm-5.242 1.656a5.5 5.5 0 1 1 0-11 5.5 5.5 0 0 1 0 11z"/>
                </svg>
            </button>
            <input type="text" name="search"
                   placeholder="Search for brands, items or categories..."
                   value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
        </form>

        <!-- Navbar Actions -->
        <div class="navbar-actions">
            <!-- Cart Icon -->
            <a href="<?php echo isset($root_path) ? $root_path : ''; ?>cart.php" class="cart-btn" title="Shopping Cart">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M0 1.5A.5.5 0 0 1 .5 1H2a.5.5 0 0 1 .485.379L2.89 3H14.5a.5.5 0 0 1 .491.592l-1.5 8A.5.5 0 0 1 13 12H4a.5.5 0 0 1-.491-.408L2.01 3.607 1.61 2H.5a.5.5 0 0 1-.5-.5zM5 12a2 2 0 1 0 0 4 2 2 0 0 0 0-4zm7 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4z"/>
                </svg>
                <?php
                // Show cart count if items exist
                $cart_count = 0;
                if (isLoggedIn() && isset($conn)) {
                    $uid = $_SESSION['user_id'];
                    $cq = $conn->prepare("SELECT COALESCE(SUM(quantity),0) as cnt FROM cart WHERE user_id=?");
                    if ($cq) {
                        $cq->bind_param('i', $uid);
                        $cq->execute();
                        $cr = $cq->get_result()->fetch_assoc();
                        $cart_count = (int)$cr['cnt'];
                        $cq->close();
                    }
                } elseif (isset($_SESSION['cart'])) {
                    $cart_count = array_sum(array_column($_SESSION['cart'], 'quantity'));
                }
                if ($cart_count > 0): ?>
                <span class="cart-count"><?php echo $cart_count; ?></span>
                <?php endif; ?>
            </a>

            <?php if (isLoggedIn()): ?>
            <!-- User Menu -->
            <div class="dropdown">
                <div class="user-menu">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16">
                        <path d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm2-3a2 2 0 1 1-4 0 2 2 0 0 1 4 0zm4 8c0 1-1 1-1 1H3s-1 0-1-1 1-4 6-4 6 3 6 4zm-1-.004c-.001-.246-.154-.986-.832-1.664C11.516 10.68 10.289 10 8 10c-2.29 0-3.516.68-4.168 1.332-.678.678-.83 1.418-.832 1.664h10z"/>
                    </svg>
                    <span><?php echo htmlspecialchars($_SESSION['username'] ?? 'Account'); ?></span>
                    <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" fill="currentColor" viewBox="0 0 16 16">
                        <path d="M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z"/>
                    </svg>
                </div>
                <div class="dropdown-menu" style="right:0;left:auto;">
                    <a href="<?php echo isset($root_path) ? $root_path : ''; ?>profile.php">My Profile</a>
                    <a href="<?php echo isset($root_path) ? $root_path : ''; ?>profile.php?tab=orders">My Orders</a>
                    <?php if (isAdmin()): ?>
                    <a href="<?php echo isset($root_path) ? $root_path : ''; ?>admin.php">Admin Panel</a>
                    <?php endif; ?>
                    <a href="<?php echo isset($root_path) ? $root_path : ''; ?>logout.php" style="color: #e74c3c;">
                        Logout
                    </a>
                </div>
            </div>
            <?php else: ?>
            <!-- Login / Register Buttons -->
            <a href="<?php echo isset($root_path) ? $root_path : ''; ?>login.php" class="btn-login">Login</a>
            <a href="<?php echo isset($root_path) ? $root_path : ''; ?>register.php" class="btn-register">Register</a>
            <?php endif; ?>
        </div>

        <!-- Mobile Hamburger -->
        <div class="hamburger" onclick="toggleMobileMenu()">
            <span></span><span></span><span></span>
        </div>
    </div>
</nav>

<?php
// Display flash messages
if (isset($_SESSION['flash_message'])): ?>
<div class="container" style="padding-top:16px;">
    <div class="alert alert-<?php echo htmlspecialchars($_SESSION['flash_type'] ?? 'info'); ?>">
        <?php echo htmlspecialchars($_SESSION['flash_message']); ?>
    </div>
</div>
<?php
    unset($_SESSION['flash_message']);
    unset($_SESSION['flash_type']);
endif;
?>

<script>
function toggleMobileMenu() {
    const nav = document.querySelector('.nav-links');
    if (!nav) return;
    if (nav.style.display === 'flex') {
        nav.removeAttribute('style');
    } else {
        Object.assign(nav.style, {
            display: 'flex', flexDirection: 'column', position: 'absolute',
            top: '100%', left: '0', right: '0', background: '#fff',
            padding: '16px 20px', boxShadow: '0 8px 20px rgba(0,0,0,0.12)', zIndex: '999'
        });
    }
}
</script>
