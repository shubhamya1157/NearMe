<?php
// backend/auth.php
// Starts the session and provides procedural authentication helpers

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Check if the user is currently authenticated
 *
 * @return bool
 */
function is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Enforce that the user is logged in; redirects to login page if not.
 *
 * @param string $redirect_to
 */
function require_login($redirect_to = '../frontend/login.html') {
    if (!is_logged_in()) {
        header("Location: " . $redirect_to);
        exit;
    }
}

/**
 * Returns the currently authenticated user's ID
 *
 * @return int|null
 */
function get_current_user_id() {
    return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
}

/**
 * Checks whether the user has completed their profile details
 *
 * @param array|null $user
 * @return bool
 */
function is_profile_complete($user) {
    if (!$user || !is_array($user)) {
        return false;
    }
    return !empty($user['username']) &&
           !empty($user['name']) &&
           isset($user['latitude']) && $user['latitude'] !== null && $user['latitude'] !== '' &&
           isset($user['longitude']) && $user['longitude'] !== null && $user['longitude'] !== '';
}
?>