<?php
// frontend/profile.php
// Profile setup and edit page

require_once __DIR__ . '/../backend/db.php';
require_once __DIR__ . '/../backend/auth.php';

require_login('login.html');

$user_id = get_current_user_id();

// Fetch current user details from database
$escaped_id = (int)$user_id;
$query = "SELECT * FROM users WHERE id = $escaped_id LIMIT 1";
$result = mysqli_query($conn, $query);

if (!$result || mysqli_num_rows($result) === 0) {
    header("Location: login.html?error=session_expired");
    exit;
}

$user = mysqli_fetch_assoc($result);

// Pre-fill values safely
$val_email = htmlspecialchars($user['email'] ?? '', ENT_QUOTES, 'UTF-8');
$val_name = htmlspecialchars($user['name'] ?? '', ENT_QUOTES, 'UTF-8');
$val_username = htmlspecialchars($user['username'] ?? '', ENT_QUOTES, 'UTF-8');
$val_phone = htmlspecialchars($user['phone'] ?? '', ENT_QUOTES, 'UTF-8');
$val_address = htmlspecialchars($user['address'] ?? '', ENT_QUOTES, 'UTF-8');
$val_latitude = ($user['latitude'] !== null) ? htmlspecialchars($user['latitude'], ENT_QUOTES, 'UTF-8') : '';
$val_longitude = ($user['longitude'] !== null) ? htmlspecialchars($user['longitude'], ENT_QUOTES, 'UTF-8') : '';
$val_hobbies = htmlspecialchars($user['hobbies'] ?? '', ENT_QUOTES, 'UTF-8');

$is_setup = isset($_GET['setup']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $is_setup ? 'Setup Profile' : 'Edit Profile'; ?> — NearMe</title>
    <link rel="stylesheet" href="css/index.css">
    <link rel="stylesheet" href="css/dashboard.css">
</head>
<body>

    <!-- Header Navigation -->
    <header class="site-header">
        <div class="nav-container">
            <a href="dashboard.php" class="brand-logo">
                <span class="brand-icon">📍</span>
                <span>NearMe</span>
            </a>
            <ul class="nav-links">
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="../backend/logout.php" class="btn btn-secondary btn-sm">Logout</a></li>
            </ul>
        </div>
    </header>

    <!-- Main Content -->
    <main class="main-content">
        <div class="profile-card-container">
            <?php if ($is_setup): ?>
                <div class="alert alert-info">
                    <span>ℹ️</span>
                    <span>Please add your name, username, phone, address using location, and hobbies to get started.</span>
                </div>
            <?php endif; ?>

            <!-- Error Alerts -->
            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-error">
                    <span>⚠️</span>
                    <span>
                        <?php 
                        $err = $_GET['error'];
                        if ($err === 'username_taken') {
                            echo "This username is already taken. Please choose another username.";
                        } elseif ($err === 'missing_required_fields') {
                            echo "Name and username are required.";
                        } elseif ($err === 'invalid_username') {
                            echo "Username can only contain letters, numbers, and underscores.";
                        } elseif ($err === 'invalid_latitude' || $err === 'invalid_longitude') {
                            echo "Please enter valid geographic coordinates.";
                        } else {
                            echo "Failed to save profile. Please try again.";
                        }
                        ?>
                    </span>
                </div>
            <?php endif; ?>

            <div class="profile-header-row">
                <div>
                    <h2><?php echo $is_setup ? 'Complete Your Profile' : 'Edit Profile'; ?></h2>
                    <p style="color: var(--text-muted); font-size: 0.9rem;">
                        Logged in as: <strong><?php echo $val_email; ?></strong>
                    </p>
                </div>
                <?php if (!$is_setup): ?>
                    <a href="dashboard.php" class="btn btn-secondary btn-sm">&larr; Back to Dashboard</a>
                <?php endif; ?>
            </div>

            <form action="../backend/profile.php" method="POST">
                <!-- Name and Username -->
                <div class="form-grid">
                    <div class="form-group">
                        <label for="name">Full Name <span style="color: var(--danger);">*</span></label>
                        <input type="text" id="name" name="name" class="form-control" placeholder="Enter your full name" value="<?php echo $val_name; ?>" required autofocus>
                    </div>

                    <div class="form-group">
                        <label for="username">Username <span style="color: var(--danger);">*</span></label>
                        <input type="text" id="username" name="username" class="form-control" placeholder="e.g. rahul12" value="<?php echo $val_username; ?>" required>
                    </div>
                </div>

                <!-- Phone Number -->
                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input type="tel" id="phone" name="phone" class="form-control" placeholder="Enter your phone number" value="<?php echo $val_phone; ?>">
                </div>

                <!-- Location Coordinates & Auto-detection -->
                <div class="location-box">
                    <div class="location-box-header">
                        <div>
                            <label style="font-weight: 600; font-size: 0.95rem;">Location Coordinates (Latitude &amp; Longitude)</label>
                            <p style="font-size: 0.82rem; color: var(--text-muted); margin-top: 0.2rem;">
                                Used to calculate distance between you and nearby users.
                            </p>
                        </div>
                        <button type="button" id="btnGetLocation" class="btn btn-primary btn-sm">
                            📍 Get Current Location
                        </button>
                    </div>

                    <div id="locationStatus" style="font-size: 0.85rem; margin-top: 0.5rem; display: none;"></div>

                    <div class="form-grid" style="margin-top: 0.75rem;">
                        <div class="form-group" style="margin-bottom: 0.5rem;">
                            <label for="latitude" style="font-size: 0.85rem;">Latitude</label>
                            <input type="number" step="any" id="latitude" name="latitude" class="form-control" placeholder="e.g. 26.9124" value="<?php echo $val_latitude; ?>" required>
                        </div>

                        <div class="form-group" style="margin-bottom: 0.5rem;">
                            <label for="longitude" style="font-size: 0.85rem;">Longitude</label>
                            <input type="number" step="any" id="longitude" name="longitude" class="form-control" placeholder="e.g. 75.7873" value="<?php echo $val_longitude; ?>" required>
                        </div>
                    </div>
                </div>

                <!-- Address (Filled using location or manually) -->
                <div class="form-group">
                    <label for="address">Address / City</label>
                    <input type="text" id="address" name="address" class="form-control" placeholder="Enter your address or city (e.g. Malviya Nagar, Jaipur)" value="<?php echo $val_address; ?>">
                    <span class="form-hint">Can be auto-detected when you click "Get Current Location" or typed manually.</span>
                </div>

                <!-- Hobbies -->
                <div class="form-group">
                    <label for="hobbies">Hobbies</label>
                    <input type="text" id="hobbies" name="hobbies" class="form-control" placeholder="Enter hobbies separated by commas, e.g. Cricket, Music, Reading, Coding" value="<?php echo $val_hobbies; ?>">
                    <span class="form-hint">Separate multiple hobbies with commas. NearMe matches these with other users.</span>
                    
                    <!-- Quick common hobby suggestions -->
                    <div style="margin-top: 0.65rem;">
                        <span style="font-size: 0.8rem; color: var(--text-muted); font-weight: 500;">Quick add:</span>
                        <div class="hobby-chips-row">
                            <span class="hobby-suggestion" data-hobby="Cricket">+ Cricket</span>
                            <span class="hobby-suggestion" data-hobby="Football">+ Football</span>
                            <span class="hobby-suggestion" data-hobby="Music">+ Music</span>
                            <span class="hobby-suggestion" data-hobby="Coding">+ Coding</span>
                            <span class="hobby-suggestion" data-hobby="Gaming">+ Gaming</span>
                            <span class="hobby-suggestion" data-hobby="Reading">+ Reading</span>
                            <span class="hobby-suggestion" data-hobby="Cooking">+ Cooking</span>
                            <span class="hobby-suggestion" data-hobby="Traveling">+ Traveling</span>
                            <span class="hobby-suggestion" data-hobby="Photography">+ Photography</span>
                            <span class="hobby-suggestion" data-hobby="Fitness">+ Fitness</span>
                        </div>
                    </div>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 0.75rem; margin-top: 1.75rem;">
                    <?php if (!$is_setup): ?>
                        <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
                    <?php endif; ?>
                    <button type="submit" class="btn btn-primary" style="padding: 0.75rem 2rem;">
                        Save Profile
                    </button>
                </div>
            </form>
        </div>
    </main>

    <!-- Footer -->
    <footer class="site-footer">
        <p>&copy; 2026 NearMe. Built with Procedural PHP &amp; MySQL.</p>
    </footer>

    <!-- Scripts -->
    <script src="js/location.js"></script>
    <script>
        // Quick add hobby chip helper
        document.querySelectorAll('.hobby-suggestion').forEach(chip => {
            chip.addEventListener('click', function () {
                const hobby = this.getAttribute('data-hobby');
                const hobbiesInput = document.getElementById('hobbies');
                let current = hobbiesInput.value.trim();
                if (current === '') {
                    hobbiesInput.value = hobby;
                } else {
                    const parts = current.split(',').map(s => s.trim().toLowerCase());
                    if (!parts.includes(hobby.toLowerCase())) {
                        hobbiesInput.value = current + ', ' + hobby;
                    }
                }
            });
        });
    </script>
</body>
</html>
