<?php
/**
 * login.php
 * User Login Page — Pastimes
 * WEDE6021 POE
 *
 * Handles login with:
 * - Username + password authentication
 * - password_verify() against hashed password
 * - Sticky form on failed login
 * - Session management
 * - Associative array fetch from DB
 * - Displays "User John Doe is logged in" on success
 */

session_start();
require_once 'includes/DBConn.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect('index.php');
}

$page_title = 'Login';

// ============================================================
// STICKY FORM VALUES
// ============================================================
$sticky_username = '';
$error_message   = '';

// Flash message from register redirect
$info_message = '';
if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'login_required') {
        $info_message = 'Please log in to continue.';
    }
}

// ============================================================
// PROCESS LOGIN FORM (POST)
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Retrieve and sanitize inputs (sticky form)
    $sticky_username = sanitize($_POST['username'] ?? '');
    $password        = $_POST['password'] ?? '';   // Raw — for password_verify()

    // Basic empty validation
    if (empty($sticky_username)) {
        $error_message = 'Please enter your username.';
    } elseif (empty($password)) {
        $error_message = 'Please enter your password.';
    } else {
        // Fetch user record using prepared statement + associative array
        $stmt = $conn->prepare(
            "SELECT user_id, first_name, last_name, email, username,
                    password_hash, role, seller_status, account_status
             FROM users
             WHERE username = ?
             LIMIT 1"
        );
        $stmt->bind_param('s', $sticky_username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            // Fetch using associative array (POE requirement)
            $user = $result->fetch_assoc();

            // Verify password against stored hash using password_verify()
            if (password_verify($password, $user['password_hash'])) {

                // Block accounts awaiting admin approval
                if (($user['account_status'] ?? 'approved') === 'pending') {
                    $error_message = 'Your account is pending admin approval. You will be notified once approved.';
                } else {

                // Regenerate session ID to prevent fixation attacks
                session_regenerate_id(true);

                // Store user data in session
                $_SESSION['user_id']       = $user['user_id'];
                $_SESSION['first_name']    = $user['first_name'];
                $_SESSION['last_name']     = $user['last_name'];
                $_SESSION['email']         = $user['email'];
                $_SESSION['username']      = $user['username'];
                $_SESSION['role']          = $user['role'];
                $_SESSION['seller_status'] = $user['seller_status'];

                // POE Requirement: Display "User John Doe is logged in"
                $_SESSION['flash_message'] = "User {$user['first_name']} {$user['last_name']} is logged in";
                $_SESSION['flash_type']    = 'success';

                $stmt->close();

                // Redirect based on role
                if ($user['role'] === 'admin') {
                    redirect('admin.php');
                } else {
                    redirect('index.php');
                }

                } // end account_status check
            } else {
                // Wrong password — sticky form keeps username
                $error_message = 'Incorrect password. Please try again.';
            }
        } else {
            // User not found
            $error_message = 'No account found with that username.';
        }
        $stmt->close();
    }
}

include 'includes/header.php';
?>

<!-- ============================================================
     PAGE HERO
     ============================================================ -->
<div class="page-hero" style="min-height:200px;">
    <div style="position:absolute;right:0;top:0;bottom:0;width:50%;background:linear-gradient(135deg,#0d3a6e,#082B59);"></div>
    <div style="position:absolute;right:0;top:0;bottom:0;width:45%;background:linear-gradient(to left,rgba(13,139,139,0.3),transparent);z-index:1;"></div>
    <div class="container">
        <h1 style="font-size:2rem;">Welcome Back!</h1>
        <p style="max-width:380px;line-height:1.7;">Login to your Pastimes account to continue buying or selling pre-loved branded clothing.</p>
    </div>
</div>

<!-- ============================================================
     LOGIN CONTENT
     ============================================================ -->
<div style="background:var(--off-white);padding:50px 0;">
    <div class="container">
        <div style="display:grid;grid-template-columns:280px 1fr;gap:50px;align-items:start;max-width:860px;margin:0 auto;">

            <!-- Left Sidebar Features -->
            <div style="display:flex;flex-direction:column;gap:28px;">
                <div class="auth-sidebar-feature">
                    <div class="auth-feature-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="currentColor" viewBox="0 0 16 16">
                            <path d="M8 16s6-5.686 6-10A6 6 0 0 0 2 6c0 4.314 6 10 6 10zm0-7a3 3 0 1 1 0-6 3 3 0 0 1 0 6z"/>
                        </svg>
                    </div>
                    <div>
                        <h4>Eco-Friendly</h4>
                        <p style="font-size:.82rem;color:var(--text-mid);">Give clothes a second life and help reduce fashion waste.</p>
                    </div>
                </div>
                <div class="auth-sidebar-feature">
                    <div class="auth-feature-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="currentColor" viewBox="0 0 16 16">
                            <path d="M10.067.87a2.89 2.89 0 0 0-4.134 0l-.622.638-.89-.011a2.89 2.89 0 0 0-2.924 2.924l.01.89-.636.622a2.89 2.89 0 0 0 0 4.134l.637.622-.011.89a2.89 2.89 0 0 0 2.924 2.924l.89-.01.622.636a2.89 2.89 0 0 0 4.134 0l.622-.637.89.011a2.89 2.89 0 0 0 2.924-2.924l-.01-.89.636-.622a2.89 2.89 0 0 0 0-4.134l-.637-.622.011-.89a2.89 2.89 0 0 0-2.924-2.924l-.89.01zm.287 5.984-3 3a.5.5 0 0 1-.708 0l-1.5-1.5a.5.5 0 1 1 .708-.708L7 8.793l2.646-2.647a.5.5 0 0 1 .708.708z"/>
                        </svg>
                    </div>
                    <div>
                        <h4>Verified Sellers</h4>
                        <p style="font-size:.82rem;color:var(--text-mid);">All sellers are verified by admins to ensure quality and trust.</p>
                    </div>
                </div>
                <div class="auth-sidebar-feature">
                    <div class="auth-feature-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="currentColor" viewBox="0 0 16 16">
                            <path d="M8 1a2 2 0 0 1 2 2v4H6V3a2 2 0 0 1 2-2zm3 6V3a3 3 0 0 0-6 0v4a2 2 0 0 0-2 2v5a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2z"/>
                        </svg>
                    </div>
                    <div>
                        <h4>Secure &amp; Safe</h4>
                        <p style="font-size:.82rem;color:var(--text-mid);">Your information is protected with industry-standard security.</p>
                    </div>
                </div>
                <!-- Clothing image placeholder -->
                <div class="auth-sidebar-image" style="height:180px;background:linear-gradient(135deg,#e8f5f5,#c8e6c9);border-radius:12px;display:flex;align-items:center;justify-content:center;color:var(--teal);font-size:3rem;">
                    👗
                </div>
            </div>

            <!-- Login Form Card -->
            <div class="auth-card">
                <div class="auth-card-header">
                    <div class="auth-card-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" fill="currentColor" viewBox="0 0 16 16">
                            <path d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm2-3a2 2 0 1 1-4 0 2 2 0 0 1 4 0zm4 8c0 1-1 1-1 1H3s-1 0-1-1 1-4 6-4 6 3 6 4zm-1-.004c-.001-.246-.154-.986-.832-1.664C11.516 10.68 10.289 10 8 10c-2.29 0-3.516.68-4.168 1.332-.678.678-.83 1.418-.832 1.664h10z"/>
                        </svg>
                    </div>
                    <h2>Login</h2>
                    <p>Login to your Pastimes account</p>
                </div>

                <!-- Info / Error Messages -->
                <?php if (!empty($info_message)): ?>
                <div class="alert alert-info"><?php echo htmlspecialchars($info_message); ?></div>
                <?php endif; ?>

                <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
                <?php endif; ?>

                <!-- Login Form -->
                <form method="POST" action="login.php" novalidate>

                    <!-- Username (sticky — pre-filled on error) -->
                    <div class="form-group">
                        <label for="username">Username</label>
                        <div class="input-wrapper">
                            <span class="input-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="currentColor" viewBox="0 0 16 16">
                                    <path d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/>
                                </svg>
                            </span>
                            <input type="text" id="username" name="username"
                                   class="form-control <?php echo !empty($error_message) && empty($sticky_username) ? 'is-invalid' : ''; ?>"
                                   placeholder="Enter your username"
                                   value="<?php echo htmlspecialchars($sticky_username); ?>"
                                   required autocomplete="username">
                        </div>
                    </div>

                    <!-- Password -->
                    <div class="form-group">
                        <label for="password">Password</label>
                        <div class="input-wrapper">
                            <span class="input-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="currentColor" viewBox="0 0 16 16">
                                    <path d="M8 1a2 2 0 0 1 2 2v4H6V3a2 2 0 0 1 2-2zm3 6V3a3 3 0 0 0-6 0v4a2 2 0 0 0-2 2v5a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2z"/>
                                </svg>
                            </span>
                            <input type="password" id="password" name="password"
                                   class="form-control"
                                   placeholder="Enter your password"
                                   required autocomplete="current-password">
                            <button type="button" class="toggle-password" title="Show/Hide password">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                    <path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8zM1.173 8a13.133 13.133 0 0 1 1.66-2.043C4.12 4.668 5.88 3.5 8 3.5c2.12 0 3.879 1.168 5.168 2.457A13.133 13.133 0 0 1 14.828 8c-.058.087-.122.183-.195.288-.335.48-.83 1.12-1.465 1.755C11.879 11.332 10.119 12.5 8 12.5c-2.12 0-3.879-1.168-5.168-2.457A13.134 13.134 0 0 1 1.172 8z"/>
                                    <path d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5zM4.5 8a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0z"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Remember Me + Forgot Password -->
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
                        <div class="form-check" style="margin-bottom:0;">
                            <input type="checkbox" id="remember_me" name="remember_me">
                            <label for="remember_me">Remember me</label>
                        </div>
                        <a href="#" style="font-size:0.83rem;color:var(--teal);font-weight:500;">Forgot your password?</a>
                    </div>

                    <!-- Submit -->
                    <button type="submit" class="btn btn-primary btn-block btn-lg">Login</button>

                    <div class="auth-divider">or</div>

                    <!-- Google Sign In (UI only) -->
                    <button type="button" class="google-btn">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 48 48">
                            <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/>
                            <path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/>
                            <path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/>
                            <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/>
                        </svg>
                        Continue with Google
                    </button>
                </form>

                <div class="auth-footer-link">
                    Don't have an account? <a href="register.php">Register here</a>
                </div>
                <div class="auth-footer-link" style="margin-top:10px;">
                    Just browsing? <a href="browse.php" style="color:var(--teal);font-weight:500;">Browse as Guest →</a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bottom Trust Bar -->
<section class="page-trust-bar">
    <div class="container">
        <div class="trust-feature">
            <div class="trust-feature-icon">🚚</div>
            <div><h4>Fast Delivery</h4><p>Reliable couriers to your home or work.</p></div>
        </div>
        <div class="trust-feature">
            <div class="trust-feature-icon">💬</div>
            <div><h4>Message Sellers</h4><p>Chat directly with sellers before you buy.</p></div>
        </div>
        <div class="trust-feature">
            <div class="trust-feature-icon">⭐</div>
            <div><h4>Quality Items</h4><p>Pre-loved branded items in excellent condition.</p></div>
        </div>
        <div class="trust-feature">
            <div class="trust-feature-icon">💰</div>
            <div><h4>Great Prices</h4><p>Top brands for less. Save more.</p></div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
