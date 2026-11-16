<?php
// backend/register.php
// Handles new user registration using procedural PHP and MySQLi

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../frontend/register.html");
    exit;
}

$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$password = isset($_POST['password']) ? trim($_POST['password']) : '';
$confirm_password = isset($_POST['confirm_password']) ? trim($_POST['confirm_password']) : '';

// Validation
if (empty($email) || empty($password)) {
    header("Location: ../frontend/register.html?error=empty_fields");
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header("Location: ../frontend/register.html?error=invalid_email");
    exit;
}

if (strlen($password) < 6) {
    header("Location: ../frontend/register.html?error=password_too_short");
    exit;
}

if ($password !== $confirm_password) {
    header("Location: ../frontend/register.html?error=password_mismatch");
    exit;
}

// Check if email already exists
$escaped_email = mysqli_real_escape_string($conn, $email);
$check_sql = "SELECT id FROM users WHERE email = '$escaped_email' LIMIT 1";
$check_result = mysqli_query($conn, $check_sql);

if ($check_result && mysqli_num_rows($check_result) > 0) {
    header("Location: ../frontend/register.html?error=email_exists");
    exit;
}

// Hash the password securely
$hashed_password = password_hash($password, PASSWORD_DEFAULT);
$escaped_password = mysqli_real_escape_string($conn, $hashed_password);

// Insert the new user account
$insert_sql = "INSERT INTO users (email, password) VALUES ('$escaped_email', '$escaped_password')";
$insert_result = mysqli_query($conn, $insert_sql);

if ($insert_result) {
    $new_user_id = mysqli_insert_id($conn);

    // Initialize session and auto-login
    session_regenerate_id(true);
    $_SESSION['user_id'] = $new_user_id;
    $_SESSION['email'] = $email;
    $_SESSION['username'] = null;
    $_SESSION['name'] = null;

    // Direct the new user to complete their profile (name, username, phone, location, hobbies)
    header("Location: ../frontend/profile.php?setup=1");
    exit;
} else {
    // Database insert failed
    header("Location: ../frontend/register.html?error=registration_failed");
    exit;
}
?>