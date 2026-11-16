<?php
// backend/logout.php
// Clears session data, destroys session, and redirects to login page

require_once __DIR__ . '/auth.php';

// Unset all session variables
$_SESSION = [];

// Delete the session cookie if present
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Destroy session
session_destroy();

// Redirect to login page
header("Location: ../frontend/login.html?logged_out=1");
exit;
?>