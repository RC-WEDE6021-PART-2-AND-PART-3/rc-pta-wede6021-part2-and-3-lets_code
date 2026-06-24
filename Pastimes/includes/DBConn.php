<?php
/**
 * DBConn.php
 * Database Connection File — Pastimes ClothingStore
 * WEDE6021 POE
 *
 * Creates a MySQLi connection to the ClothingStore database.
 * Include this file in all scripts that require database access.
 */

// ============================================================
// DATABASE CONFIGURATION
// ============================================================
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'ClothingStore');
define('DB_CHARSET', 'utf8mb4');

// ============================================================
// ESTABLISH CONNECTION
// ============================================================
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check for connection errors
if ($conn->connect_error) {
    // In production, log this error rather than displaying it
    error_log("Pastimes DB Connection Failed: " . $conn->connect_error);
    die(json_encode([
        'error' => true,
        'message' => 'Database connection failed. Please try again later.'
    ]));
}

// Set character encoding to prevent injection and encoding issues
if (!$conn->set_charset(DB_CHARSET)) {
    error_log("Error loading character set utf8mb4: " . $conn->error);
}

// ============================================================
// HELPER FUNCTION: Sanitize input
// ============================================================
/**
 * Sanitize a string value for safe display output
 * NOTE: Always use prepared statements for DB queries — do NOT
 * rely on sanitisation alone for SQL safety.
 *
 * @param string $data Raw input string
 * @return string Sanitized string
 */
function sanitize($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Redirect helper
 *
 * @param string $url Destination URL
 */
function redirect($url) {
    header("Location: $url");
    exit();
}

/**
 * Check if user is logged in
 *
 * @return bool
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Check if logged-in user is admin
 *
 * @return bool
 */
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

/**
 * Check if logged-in user is a verified seller
 *
 * @return bool
 */
function isVerifiedSeller() {
    return isset($_SESSION['seller_status']) && $_SESSION['seller_status'] === 'verified';
}

/**
 * Require login — redirect to login if not authenticated
 */
function requireLogin() {
    if (!isLoggedIn()) {
        redirect('login.php?msg=login_required');
    }
}

/**
 * Require admin — redirect if not admin
 */
function requireAdmin() {
    if (!isAdmin()) {
        redirect('index.php?msg=access_denied');
    }
}

/**
 * Require verified seller — redirect if not verified
 */
function requireSeller() {
    if (!isVerifiedSeller()) {
        redirect('profile.php?msg=seller_required');
    }
}
?>
