<?php
// backend/db.php
// Opens the MySQL connection using procedural MySQLi

$host = '127.0.0.1';
$user = getenv('NEARME_DB_USER') ?: 'nearme_app';
$password = getenv('NEARME_DB_PASSWORD') !== false ? getenv('NEARME_DB_PASSWORD') : 'local_db_password';
$database = 'nearme';
$port = 3306;

// Disable automatic exception throwing for mysqli in newer PHP versions so we can handle errors procedurally
mysqli_report(MYSQLI_REPORT_OFF);

// Attempt connection with primary credentials
$conn = @mysqli_connect($host, $user, $password, $database, $port);

// Fallback to root without password if nearme_app was not yet created (useful for fresh XAMPP/local setups)
if (!$conn && $user !== 'root') {
    $conn = @mysqli_connect($host, 'root', '', $database, $port);
}

// Check connection
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Set UTF-8 character encoding
mysqli_set_charset($conn, "utf8mb4");
?>