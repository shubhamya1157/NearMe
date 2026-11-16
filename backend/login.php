<?php
// backend/login.php
// Validates credentials, creates a session, and redirects to dashboard or profile setup

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../frontend/login.html");
    exit;
}

$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$password = isset($_POST['password']) ? trim($_POST['password']) : '';

if (empty($email) || empty($password)) {
    header("Location: ../frontend/login.html?error=empty_fields");
    exit;
}

// Find user by email
$escaped_email = mysqli_real_escape_string($conn, $email);
$query = "SELECT * FROM users WHERE email = '$escaped_email' LIMIT 1";
$result = mysqli_query($conn, $query);

if ($result && mysqli_num_rows($result) === 1) {
    $user = mysqli_fetch_assoc($result);

    // Support both hashed passwords and plain-text development accounts
    $is_password_valid = password_verify($password, $user['password']) || ($password === $user['password']);

    if ($is_password_valid) {
        // Regenerate session ID to prevent session fixation
        session_regenerate_id(true);

        $_SESSION['user_id'] = (int)$user['id'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['name'] = $user['name'];

        // If the user has not completed their profile yet, redirect them to setup
        if (!is_profile_complete($user)) {
            header("Location: ../frontend/profile.php?setup=1");
            exit;
        }

        // Profile complete, proceed directly to dashboard
        header("Location: ../frontend/dashboard.php");
        exit;
    }
}

// Login failed
header("Location: ../frontend/login.html?error=invalid_credentials");
exit;
?>