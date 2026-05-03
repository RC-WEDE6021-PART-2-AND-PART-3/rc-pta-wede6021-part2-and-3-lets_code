<?php
/**
 * register.php
 * User Registration Page — Pastimes
 * WEDE6021 POE
 *
 * Handles new user registration with:
 * - Server-side validation
 * - Password hashing (password_hash)
 * - Sticky form on error
 * - Seller request checkbox
 * - Prepared statements for SQL injection prevention
 */

session_start();
require_once 'includes/DBConn.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect('index.php');
}

$page_title = 'Create Account';

// ============================================================
// STICKY FORM VALUES (persisted on error)
// ============================================================
$sticky = [
    'first_name'    => '',
    'last_name'     => '',
    'email'         => '',
    'username'      => '',
    'request_seller'=> false,
];

$errors   = [];
$success  = '';

// ============================================================
// PROCESS REGISTRATION FORM (POST)
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // -- Retrieve and sanitize inputs --
    $sticky['first_name']     = sanitize($_POST['first_name'] ?? '');
    $sticky['last_name']      = sanitize($_POST['last_name'] ?? '');
    $sticky['email']          = sanitize($_POST['email'] ?? '');
    $sticky['username']       = sanitize($_POST['username'] ?? '');
    $password                 = $_POST['password'] ?? '';        // Raw — for hashing
    $confirm_password         = $_POST['confirm_password'] ?? '';
    $sticky['request_seller'] = isset($_POST['request_seller']);
    $terms                    = isset($_POST['terms']);

    // -- Validation --
    if (empty($sticky['first_name'])) {
        $errors['first_name'] = 'First name is required.';
    } elseif (strlen($sticky['first_name']) > 50) {
        $errors['first_name'] = 'First name must be 50 characters or fewer.';
    }

    if (empty($sticky['last_name'])) {
        $errors['last_name'] = 'Last name is required.';
    }

    if (empty($sticky['email'])) {
        $errors['email'] = 'Email address is required.';
    } elseif (!filter_var($sticky['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address.';
    }

    if (empty($sticky['username'])) {
        $errors['username'] = 'Username is required.';
    } elseif (strlen($sticky['username']) < 3) {
        $errors['username'] = 'Username must be at least 3 characters.';
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $sticky['username'])) {
        $errors['username'] = 'Username may only contain letters, numbers and underscores.';
    }

    if (empty($password)) {
        $errors['password'] = 'Password is required.';
    } elseif (strlen($password) < 8) {
        $errors['password'] = 'Password must be at least 8 characters.';
    }

    if (empty($confirm_password)) {
        $errors['confirm_password'] = 'Please confirm your password.';
    } elseif ($password !== $confirm_password) {
        $errors['confirm_password'] = 'Passwords do not match.';
    }

    if (!$terms) {
        $errors['terms'] = 'You must accept the Terms of Service to register.';
    }

    // -- Check for duplicate email / username (using prepared statement) --
    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ? OR username = ? LIMIT 1");
        $stmt->bind_param('ss', $sticky['email'], $sticky['username']);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            // Determine which field is duplicate
            $dupStmt = $conn->prepare("SELECT email, username FROM users WHERE email = ? OR username = ?");
            $dupStmt->bind_param('ss', $sticky['email'], $sticky['username']);
            $dupStmt->execute();
            $dupResult = $dupStmt->get_result()->fetch_assoc();
            if ($dupResult['email'] === $sticky['email']) {
                $errors['email'] = 'This email address is already registered.';
            }
            if ($dupResult['username'] === $sticky['username']) {
                $errors['username'] = 'This username is already taken.';
            }
            $dupStmt->close();
        }
        $stmt->close();
    }

    // -- If no errors, insert user --
    if (empty($errors)) {
        // Hash password securely using bcrypt
        $password_hash   = password_hash($password, PASSWORD_DEFAULT);
        $role            = 'buyer';
        $seller_status   = $sticky['request_seller'] ? 'pending' : 'none';

        $stmt = $conn->prepare(
            "INSERT INTO users (first_name, last_name, email, username, password_hash, role, seller_status, account_status)
             VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')"
        );
        $stmt->bind_param(
            'sssssss',
            $sticky['first_name'],
            $sticky['last_name'],
            $sticky['email'],
            $sticky['username'],
            $password_hash,
            $role,
            $seller_status
        );

        if ($stmt->execute()) {
            // Registration successful — account needs admin approval before login
            $_SESSION['flash_message'] = 'Account created! An admin will review and approve your account before you can log in.';
            $_SESSION['flash_type']    = 'info';
            $stmt->close();
            redirect('login.php');
        } else {
            $errors['general'] = 'Registration failed. Please try again.';
        }
        $stmt->close();
    }
}

include 'includes/header.php';
?>

<!-- ============================================================
     PAGE HERO
     ============================================================ -->
<div class="page-hero" style="min-height:160px;">
    <div style="position:absolute;right:0;top:0;bottom:0;width:45%;background:linear-gradient(to left,#0d3a6e,transparent);"></div>
    <div class="container">
        <h1 style="font-size:1.8rem;">Create Your Account</h1>
        <p>Join Pastimes and start buying or selling pre-loved branded clothing today.</p>
    </div>
</div>

<!-- ============================================================
     REGISTER CONTENT
     ============================================================ -->
<div style="background:var(--off-white);padding:50px 0;">
    <div class="container">
        <div style="display:grid;grid-template-columns:280px 1fr;gap:40px;align-items:start;max-width:960px;margin:0 auto;">

            <!-- Left Features -->
            <div style="display:flex;flex-direction:column;gap:24px;">
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
            </div>

            <!-- Registration Form Card -->
            <div class="auth-card">
                <div class="auth-card-header">
                    <div class="auth-card-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" fill="currentColor" viewBox="0 0 16 16">
                            <path d="M6 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm2-3a2 2 0 1 1-4 0 2 2 0 0 1 4 0zm4 8c0 1-1 1-1 1H1s-1 0-1-1 1-4 6-4 6 3 6 4zm-1-.004c-.001-.246-.154-.986-.832-1.664C9.516 10.68 8.289 10 6 10c-2.29 0-3.516.68-4.168 1.332-.678.678-.83 1.418-.832 1.664h10z"/>
                            <path fill-rule="evenodd" d="M13.5 5a.5.5 0 0 1 .5.5V7h1.5a.5.5 0 0 1 0 1H14v1.5a.5.5 0 0 1-1 0V8h-1.5a.5.5 0 0 1 0-1H13V5.5a.5.5 0 0 1 .5-.5z"/>
                        </svg>
                    </div>
                    <h2>Register</h2>
                    <p>Fill in the details below to create your Pastimes account.</p>
                </div>

                <!-- General Error -->
                <?php if (!empty($errors['general'])): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($errors['general']); ?></div>
                <?php endif; ?>

                <!-- Registration Form -->
                <form method="POST" action="register.php" novalidate>

                    <!-- First Name -->
                    <div class="form-group">
                        <label for="first_name">First Name <span class="required">*</span></label>
                        <div class="input-wrapper">
                            <span class="input-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="currentColor" viewBox="0 0 16 16">
                                    <path d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/>
                                </svg>
                            </span>
                            <input type="text" id="first_name" name="first_name" class="form-control <?php echo isset($errors['first_name']) ? 'is-invalid' : ''; ?>"
                                   placeholder="Enter your first name"
                                   value="<?php echo htmlspecialchars($sticky['first_name']); ?>"
                                   required maxlength="50">
                        </div>
                        <?php if (isset($errors['first_name'])): ?>
                        <span class="invalid-feedback"><?php echo htmlspecialchars($errors['first_name']); ?></span>
                        <?php endif; ?>
                    </div>

                    <!-- Last Name -->
                    <div class="form-group">
                        <label for="last_name">Last Name <span class="required">*</span></label>
                        <div class="input-wrapper">
                            <span class="input-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="currentColor" viewBox="0 0 16 16">
                                    <path d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/>
                                </svg>
                            </span>
                            <input type="text" id="last_name" name="last_name" class="form-control <?php echo isset($errors['last_name']) ? 'is-invalid' : ''; ?>"
                                   placeholder="Enter your last name"
                                   value="<?php echo htmlspecialchars($sticky['last_name']); ?>"
                                   required maxlength="50">
                        </div>
                        <?php if (isset($errors['last_name'])): ?>
                        <span class="invalid-feedback"><?php echo htmlspecialchars($errors['last_name']); ?></span>
                        <?php endif; ?>
                    </div>

                    <!-- Email -->
                    <div class="form-group">
                        <label for="email">Email Address <span class="required">*</span></label>
                        <div class="input-wrapper">
                            <span class="input-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="currentColor" viewBox="0 0 16 16">
                                    <path d="M0 4a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V4zm2-1a1 1 0 0 0-1 1v.217l7 4.2 7-4.2V4a1 1 0 0 0-1-1H2z"/>
                                </svg>
                            </span>
                            <input type="email" id="email" name="email" class="form-control <?php echo isset($errors['email']) ? 'is-invalid' : ''; ?>"
                                   placeholder="Enter your email address"
                                   value="<?php echo htmlspecialchars($sticky['email']); ?>"
                                   required>
                        </div>
                        <?php if (isset($errors['email'])): ?>
                        <span class="invalid-feedback"><?php echo htmlspecialchars($errors['email']); ?></span>
                        <?php endif; ?>
                    </div>

                    <!-- Username -->
                    <div class="form-group">
                        <label for="username">Username <span class="required">*</span></label>
                        <div class="input-wrapper">
                            <span class="input-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="currentColor" viewBox="0 0 16 16">
                                    <path d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"/>
                                </svg>
                            </span>
                            <input type="text" id="username" name="username" class="form-control <?php echo isset($errors['username']) ? 'is-invalid' : ''; ?>"
                                   placeholder="Choose a username"
                                   value="<?php echo htmlspecialchars($sticky['username']); ?>"
                                   required minlength="3" maxlength="50"
                                   pattern="[a-zA-Z0-9_]+">
                        </div>
                        <?php if (isset($errors['username'])): ?>
                        <span class="invalid-feedback"><?php echo htmlspecialchars($errors['username']); ?></span>
                        <?php endif; ?>
                    </div>

                    <!-- Password -->
                    <div class="form-group">
                        <label for="password">Password <span class="required">*</span></label>
                        <div class="input-wrapper">
                            <span class="input-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="currentColor" viewBox="0 0 16 16">
                                    <path d="M8 1a2 2 0 0 1 2 2v4H6V3a2 2 0 0 1 2-2zm3 6V3a3 3 0 0 0-6 0v4a2 2 0 0 0-2 2v5a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2z"/>
                                </svg>
                            </span>
                            <input type="password" id="password" name="password"
                                   class="form-control <?php echo isset($errors['password']) ? 'is-invalid' : ''; ?>"
                                   placeholder="Create a password (min. 8 characters)"
                                   required minlength="8">
                            <button type="button" class="toggle-password" title="Show/Hide password">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                    <path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8zM1.173 8a13.133 13.133 0 0 1 1.66-2.043C4.12 4.668 5.88 3.5 8 3.5c2.12 0 3.879 1.168 5.168 2.457A13.133 13.133 0 0 1 14.828 8c-.058.087-.122.183-.195.288-.335.48-.83 1.12-1.465 1.755C11.879 11.332 10.119 12.5 8 12.5c-2.12 0-3.879-1.168-5.168-2.457A13.134 13.134 0 0 1 1.172 8z"/>
                                    <path d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5zM4.5 8a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0z"/>
                                </svg>
                            </button>
                        </div>
                        <?php if (isset($errors['password'])): ?>
                        <span class="invalid-feedback"><?php echo htmlspecialchars($errors['password']); ?></span>
                        <?php endif; ?>
                    </div>

                    <!-- Confirm Password -->
                    <div class="form-group">
                        <label for="confirm_password">Confirm Password <span class="required">*</span></label>
                        <div class="input-wrapper">
                            <span class="input-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="currentColor" viewBox="0 0 16 16">
                                    <path d="M8 1a2 2 0 0 1 2 2v4H6V3a2 2 0 0 1 2-2zm3 6V3a3 3 0 0 0-6 0v4a2 2 0 0 0-2 2v5a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2z"/>
                                </svg>
                            </span>
                            <input type="password" id="confirm_password" name="confirm_password"
                                   class="form-control <?php echo isset($errors['confirm_password']) ? 'is-invalid' : ''; ?>"
                                   placeholder="Confirm your password"
                                   required>
                            <button type="button" class="toggle-password" title="Show/Hide password">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                                    <path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8zM1.173 8a13.133 13.133 0 0 1 1.66-2.043C4.12 4.668 5.88 3.5 8 3.5c2.12 0 3.879 1.168 5.168 2.457A13.133 13.133 0 0 1 14.828 8c-.058.087-.122.183-.195.288-.335.48-.83 1.12-1.465 1.755C11.879 11.332 10.119 12.5 8 12.5c-2.12 0-3.879-1.168-5.168-2.457A13.134 13.134 0 0 1 1.172 8z"/>
                                    <path d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5zM4.5 8a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0z"/>
                                </svg>
                            </button>
                        </div>
                        <?php if (isset($errors['confirm_password'])): ?>
                        <span class="invalid-feedback"><?php echo htmlspecialchars($errors['confirm_password']); ?></span>
                        <?php endif; ?>
                    </div>

                    <!-- Seller Request -->
                    <div class="form-group">
                        <div class="form-check">
                            <input type="checkbox" id="request_seller" name="request_seller"
                                   <?php echo $sticky['request_seller'] ? 'checked' : ''; ?>>
                            <label for="request_seller">
                                I want to become a seller (admin approval required before selling)
                            </label>
                        </div>
                    </div>

                    <!-- Terms -->
                    <div class="form-group">
                        <div class="form-check">
                            <input type="checkbox" id="terms" name="terms"
                                   class="<?php echo isset($errors['terms']) ? 'is-invalid' : ''; ?>" required>
                            <label for="terms">
                                I agree to the <a href="#">Terms of Service</a> and <a href="#">Privacy Policy</a>.
                            </label>
                        </div>
                        <?php if (isset($errors['terms'])): ?>
                        <span class="invalid-feedback" style="display:block;"><?php echo htmlspecialchars($errors['terms']); ?></span>
                        <?php endif; ?>
                    </div>

                    <!-- Submit -->
                    <button type="submit" class="btn btn-primary btn-block btn-lg" style="margin-top:8px;">
                        Register
                    </button>
                </form>

                <div class="auth-footer-link">
                    Already have an account? <a href="login.php">Login here</a>
                </div>
                <div class="auth-footer-link" style="margin-top:10px;">
                    Just browsing? <a href="browse.php" style="color:var(--teal);font-weight:500;">Browse as Guest →</a>
                </div>
            </div><!-- /.auth-card -->

        </div>
    </div>
</div>

<!-- Bottom Trust Bar -->
<section class="page-trust-bar">
    <div class="container">
        <div class="trust-feature">
            <div class="trust-feature-icon">🚚</div>
            <div>
                <h4>Fast Delivery</h4>
                <p>Reliable couriers to your home or work.</p>
            </div>
        </div>
        <div class="trust-feature">
            <div class="trust-feature-icon">💬</div>
            <div>
                <h4>Message Sellers</h4>
                <p>Chat directly with sellers before you buy.</p>
            </div>
        </div>
        <div class="trust-feature">
            <div class="trust-feature-icon">⭐</div>
            <div>
                <h4>Quality Items</h4>
                <p>Pre-loved branded items in excellent condition.</p>
            </div>
        </div>
        <div class="trust-feature">
            <div class="trust-feature-icon">💰</div>
            <div>
                <h4>Great Prices</h4>
                <p>Top brands for less. Save more.</p>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
