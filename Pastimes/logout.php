<?php
/**
 * logout.php
 * Logout Handler — Pastimes
 * WEDE6021 POE
 *
 * Destroys the current session and redirects to home page.
 */

session_start();

// Destroy all session data
$_SESSION = [];

// Delete the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Redirect to home with logout confirmation
header("Location: index.php?msg=logged_out");
exit();
?>
