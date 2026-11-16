<?php
// frontend/profile.php
// Profile setup and edit page

require_once __DIR__ . '/../backend/db.php';
require_once __DIR__ . '/../backend/auth.php';

require_login('login.html');

$user_id = get_current_user_id();

// Fetch current user details
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
    <title><?php echo $is_setup ? 'Complete Your Profile' : 'Edit Profile'; ?> — NearMe</title>
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
                <li><a href="../backend/logout.php" class="btn btn-secondary btn-sm">Sign Out</a></li>
            </ul>
        </div>
    </header>

    <!-- Main Content -->
    <main class="main-content">
        <div class="profile-form-container">
            <?php if ($is_setup): ?>
                <div class="alert alert-info">
                    <span>🎉</span>
                    <span><strong>Step 2 of 2:</strong> Please complete your profile with your location and hobbies so NearMe can find people around you!</span>
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
                            echo "This username is already taken. Please pick another one.";
                        } elseif ($err === 'missing_required_fields') {
                            echo "Full Name and Username are required.";
                        } elseif ($err === 'invalid_username') {
                            echo "Username can only contain letters, numbers, and underscores.";
                        } elseif ($err === 'invalid_latitude' || $err === 'invalid_longitude') {
                            echo "Invalid coordinates provided. Please check latitude and longitude.";
                        } else {
                            echo "Failed to update profile. Please try again.";
                        }
                        ?>
                    </span>
                </div>
            <?php endif; ?>

            <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 1.5rem;">
                <div>
                    <h2><?php echo $is_setup ? 'Set Up Your Profile' : 'Edit Your Profile'; ?></h2>
                    <p style="color: var(--text-muted); font-size: 0.92rem;">
                        Signed in as <strong><?php echo $val_email; ?></strong>
                    </p>
                </div>
                <?php if (!$is_setup): ?>
                    <a href="dashboard.php" class="btn btn-secondary btn-sm">&larr; Back to Dashboard</a>
                <?php endif; ?>
            </div>

            <form action="../backend/profile.php" method="POST">
                <!-- Basic Information -->
                <div class="form-grid-2">
                    <div class="form-group">
                        <label for="name">Full Name <span style="color: var(--danger);">*</span></label>
                        <input type="text" id="name" name="name" class="form-control" placeholder="e.g. Alex Walker" value="<?php echo $val_name; ?>" required autofocus>
                    </div>

                    <div class="form-group">
                        <label for="username">Username Handle <span style="color: var(--danger);">*</span></label>
                        <input type="text" id="username" name="username" class="form-control" placeholder="e.g. alex_w" value="<?php echo $val_username; ?>" pattern="[a-zA-Z0-9_]+" title="Letters, numbers, and underscores only" required>
                        <span class="form-hint">Used for your unique profile handle (@username).</span>
                    </div>
                </div>

                <div class="form-grid-2">
                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="tel" id="phone" name="phone" class="form-control" placeholder="e.g. +91 98765 43210" value="<?php echo $val_phone; ?>">
                        <span class="form-hint">Shared with nearby matches who want to connect.</span>
                    </div>

                    <div class="form-group">
                        <label for="address">Address / Locality</label>
                        <input type="text" id="address" name="address" class="form-control" placeholder="e.g. Station Road, Nayapura, Kota" value="<?php echo $val_address; ?>">
                        <span class="form-hint">Your neighborhood or city description.</span>
                    </div>
                </div>

                <!-- Geolocation & Coordinates -->
                <div class="location-picker-box">
                    <div class="location-picker-header">
                        <div>
                            <strong style="font-size: 0.95rem; color: var(--text-main);">📍 Location Coordinates (Latitude &amp; Longitude)</strong>
                            <p style="font-size: 0.82rem; color: var(--text-muted); margin-top: 0.15rem;">
                                Required to calculate distances between you and other users.
                            </p>
                        </div>
                        <button type="button" id="btnDetectLocation" class="btn btn-primary btn-sm">
                            🛰️ Auto-detect My Location
                        </button>
                    </div>

                    <div id="locationStatus" class="location-status"></div>

                    <div class="form-grid-2" style="margin-top: 0.75rem;">
                        <div class="form-group" style="margin-bottom: 0.5rem;">
                            <label for="latitude" style="font-size: 0.82rem;">Latitude (e.g. 25.180000)</label>
                            <input type="number" step="any" id="latitude" name="latitude" class="form-control" placeholder="25.180000" value="<?php echo $val_latitude; ?>" required>
                        </div>

                        <div class="form-group" style="margin-bottom: 0.5rem;">
                            <label for="longitude" style="font-size: 0.82rem;">Longitude (e.g. 75.830000)</label>
                            <input type="number" step="any" id="longitude" name="longitude" class="form-control" placeholder="75.830000" value="<?php echo $val_longitude; ?>" required>
                        </div>
                    </div>

                    <div style="margin-top: 0.85rem;">
                        <span style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600;">Quick Presets for Testing:</span>
                        <div class="preset-buttons">
                            <button type="button" class="preset-btn" data-preset="kota">📍 Kota (Demo Center)</button>
                            <button type="button" class="preset-btn" data-preset="jaipur">Jaipur</button>
                            <button type="button" class="preset-btn" data-preset="delhi">Delhi NCR</button>
                            <button type="button" class="preset-btn" data-preset="mumbai">Mumbai</button>
                            <button type="button" class="preset-btn" data-preset="bangalore">Bangalore</button>
                        </div>
                    </div>
                </div>

                <!-- Hobbies & Interests Selection -->
                <div class="hobby-manager-box">
                    <strong style="font-size: 0.95rem; color: var(--text-main);">🎯 Your Hobbies &amp; Interests</strong>
                    <p style="font-size: 0.82rem; color: var(--text-muted); margin-top: 0.15rem; margin-bottom: 0.85rem;">
                        NearMe compares these hobbies with nearby people to find common passions.
                    </p>

                    <!-- Selected Tags Container -->
                    <label style="font-size: 0.82rem; font-weight: 600; color: var(--text-main); margin-bottom: 0.35rem; display: block;">
                        Selected Hobbies:
                    </label>
                    <div id="selectedHobbiesContainer" class="selected-hobbies-container"></div>

                    <!-- Hidden Input passed with form submission -->
                    <input type="hidden" name="hobbies" id="hobbiesHiddenInput" value="<?php echo $val_hobbies; ?>">

                    <!-- Add Custom Hobby Row -->
                    <div class="add-hobby-row">
                        <input type="text" id="customHobbyInput" class="form-control" placeholder="Type a custom hobby (e.g. Robotics, Astronomy)...">
                        <button type="button" id="btnAddCustomHobby" class="btn btn-secondary">
                            + Add Hobby
                        </button>
                    </div>

                    <!-- Preset Popular Hobbies -->
                    <div class="preset-hobbies-section">
                        <h5>Popular Hobbies (Click to Toggle):</h5>
                        <div class="preset-hobby-chips">
                            <button type="button" class="chip-choice" data-hobby="Coding">💻 Coding</button>
                            <button type="button" class="chip-choice" data-hobby="Photography">📷 Photography</button>
                            <button type="button" class="chip-choice" data-hobby="Hiking">🥾 Hiking</button>
                            <button type="button" class="chip-choice" data-hobby="Gaming">🎮 Gaming</button>
                            <button type="button" class="chip-choice" data-hobby="Football">⚽ Football</button>
                            <button type="button" class="chip-choice" data-hobby="Cooking">🍳 Cooking</button>
                            <button type="button" class="chip-choice" data-hobby="Reading">📚 Reading</button>
                            <button type="button" class="chip-choice" data-hobby="Music">🎵 Music</button>
                            <button type="button" class="chip-choice" data-hobby="Traveling">✈️ Traveling</button>
                            <button type="button" class="chip-choice" data-hobby="Chess">♟️ Chess</button>
                            <button type="button" class="chip-choice" data-hobby="Yoga">🧘 Yoga</button>
                            <button type="button" class="chip-choice" data-hobby="Painting">🎨 Painting</button>
                            <button type="button" class="chip-choice" data-hobby="Running">🏃 Running</button>
                        </div>
                    </div>
                </div>

                <!-- Submit Button -->
                <div style="display: flex; justify-content: flex-end; gap: 0.75rem; margin-top: 1.5rem;">
                    <?php if (!$is_setup): ?>
                        <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
                    <?php endif; ?>
                    <button type="submit" class="btn btn-primary" style="padding: 0.75rem 2rem;">
                        💾 Save Profile &amp; Find People
                    </button>
                </div>
            </form>
        </div>
    </main>

    <!-- Footer -->
    <footer class="site-footer">
        <p>&copy; 2026 NearMe Application. Built with Procedural PHP &amp; MySQL.</p>
    </footer>

    <!-- Scripts -->
    <script src="js/location.js"></script>
    <script src="js/hobbies.js"></script>
</body>
</html>
