<?php
// backend/profile.php
// Handles updating user profile details (username, name, phone, address, coordinates, hobbies)

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

require_login('../frontend/login.html');

$user_id = get_current_user_id();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
    $address = isset($_POST['address']) ? trim($_POST['address']) : '';
    $latitude = isset($_POST['latitude']) && $_POST['latitude'] !== '' ? (float)$_POST['latitude'] : null;
    $longitude = isset($_POST['longitude']) && $_POST['longitude'] !== '' ? (float)$_POST['longitude'] : null;
    
    // Hobbies can come as a comma-separated string or an array of tags
    $hobbies_input = $_POST['hobbies'] ?? '';
    if (is_array($hobbies_input)) {
        $hobbies_arr = array_filter(array_map('trim', $hobbies_input));
        $hobbies = implode(', ', $hobbies_arr);
    } else {
        $parts = explode(',', (string)$hobbies_input);
        $clean_parts = [];
        foreach ($parts as $p) {
            $t = trim($p);
            if ($t !== '') {
                $clean_parts[] = $t;
            }
        }
        $hobbies = implode(', ', array_unique($clean_parts));
    }

    // Basic validation
    if (empty($username) || empty($name)) {
        header("Location: ../frontend/profile.php?error=missing_required_fields");
        exit;
    }

    // Clean username (alphanumeric, underscores, lowercase)
    $clean_username = preg_replace('/[^a-zA-Z0-9_]/', '', strtolower($username));
    if (empty($clean_username)) {
        header("Location: ../frontend/profile.php?error=invalid_username");
        exit;
    }

    // Validate coordinates if provided
    if ($latitude !== null && ($latitude < -90 || $latitude > 90)) {
        header("Location: ../frontend/profile.php?error=invalid_latitude");
        exit;
    }
    if ($longitude !== null && ($longitude < -180 || $longitude > 180)) {
        header("Location: ../frontend/profile.php?error=invalid_longitude");
        exit;
    }

    // Check username uniqueness
    $escaped_username = mysqli_real_escape_string($conn, $clean_username);
    $check_user_sql = "SELECT id FROM users WHERE username = '$escaped_username' AND id != $user_id LIMIT 1";
    $check_res = mysqli_query($conn, $check_user_sql);
    if ($check_res && mysqli_num_rows($check_res) > 0) {
        header("Location: ../frontend/profile.php?error=username_taken");
        exit;
    }

    // Escape all fields for SQL
    $escaped_name = mysqli_real_escape_string($conn, $name);
    $escaped_phone = mysqli_real_escape_string($conn, $phone);
    $escaped_address = mysqli_real_escape_string($conn, $address);
    $escaped_hobbies = mysqli_real_escape_string($conn, $hobbies);
    $lat_sql = ($latitude !== null) ? (string)$latitude : "NULL";
    $lon_sql = ($longitude !== null) ? (string)$longitude : "NULL";

    $update_sql = "UPDATE users SET 
        username = '$escaped_username',
        name = '$escaped_name',
        phone = '$escaped_phone',
        address = '$escaped_address',
        latitude = $lat_sql,
        longitude = $lon_sql,
        hobbies = '$escaped_hobbies'
        WHERE id = $user_id";

    $update_res = mysqli_query($conn, $update_sql);

    if ($update_res) {
        $_SESSION['username'] = $clean_username;
        $_SESSION['name'] = $name;

        header("Location: ../frontend/dashboard.php?success=profile_updated");
        exit;
    } else {
        header("Location: ../frontend/profile.php?error=update_failed");
        exit;
    }
}

// If GET request, return current user profile JSON
$get_sql = "SELECT id, email, username, name, phone, address, latitude, longitude, hobbies FROM users WHERE id = $user_id LIMIT 1";
$get_res = mysqli_query($conn, $get_sql);

if ($get_res && mysqli_num_rows($get_res) === 1) {
    $current_user = mysqli_fetch_assoc($get_res);
    header('Content-Type: application/json');
    echo json_encode(['status' => 'success', 'data' => $current_user]);
    exit;
} else {
    header('Content-Type: application/json', true, 404);
    echo json_encode(['status' => 'error', 'message' => 'User not found']);
    exit;
}
?>