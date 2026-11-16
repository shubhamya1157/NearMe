<?php
// frontend/dashboard.php
// Main dashboard: displays user profile, nearby people, and hobby matching

require_once __DIR__ . '/../backend/db.php';
require_once __DIR__ . '/../backend/auth.php';
require_once __DIR__ . '/../backend/find_people.php';

require_login('login.html');

$user_id = get_current_user_id();

// Fetch current user details from database
$escaped_id = (int)$user_id;
$user_sql = "SELECT * FROM users WHERE id = $escaped_id LIMIT 1";
$user_res = mysqli_query($conn, $user_sql);

if (!$user_res || mysqli_num_rows($user_res) === 0) {
    header("Location: login.html?error=session_expired");
    exit;
}

$current_user = mysqli_fetch_assoc($user_res);
$has_completed_profile = is_profile_complete($current_user);

$my_name = htmlspecialchars($current_user['name'] ?? '', ENT_QUOTES, 'UTF-8');
$my_username = htmlspecialchars($current_user['username'] ?? '', ENT_QUOTES, 'UTF-8');
$my_email = htmlspecialchars($current_user['email'] ?? '', ENT_QUOTES, 'UTF-8');
$my_phone = htmlspecialchars($current_user['phone'] ?? '', ENT_QUOTES, 'UTF-8');
$my_address = htmlspecialchars($current_user['address'] ?? '', ENT_QUOTES, 'UTF-8');
$my_lat = $current_user['latitude'];
$my_lon = $current_user['longitude'];
$my_hobbies_list = parse_hobbies($current_user['hobbies'] ?? '');

// Filter parameters from GET request
$filter_distance = isset($_GET['distance']) && $_GET['distance'] !== '' ? (float)$_GET['distance'] : null;
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';

// Fetch nearby people using procedural helper
$people = get_nearby_people($conn, $user_id, $filter_distance, $search_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — NearMe</title>
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
                <li><a href="profile.php">My Profile</a></li>
                <li><a href="../backend/logout.php" class="btn btn-secondary btn-sm">Logout</a></li>
            </ul>
        </div>
    </header>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Incomplete Profile Warning -->
        <?php if (!$has_completed_profile): ?>
            <div class="alert alert-warning" style="margin-bottom: 1.5rem;">
                <span>⚠️</span>
                <span style="flex: 1;">
                    Your profile is incomplete. Please add your name, username, and location to find people near you.
                </span>
                <a href="profile.php" class="btn btn-primary btn-sm">Complete Profile</a>
            </div>
        <?php endif; ?>

        <!-- Success Alert if redirected after saving profile -->
        <?php if (isset($_GET['success']) && $_GET['success'] === 'profile_updated'): ?>
            <div class="alert alert-success" style="margin-bottom: 1.5rem;">
                <span>✅</span>
                <span>Your profile has been saved successfully!</span>
            </div>
        <?php endif; ?>

        <!-- Current User Profile Card -->
        <section class="my-profile-card">
            <div class="my-profile-header">
                <div>
                    <h2><?php echo !empty($my_name) ? $my_name : 'Welcome!'; ?></h2>
                    <p class="user-meta">
                        <?php if (!empty($my_username)): ?>
                            <strong>@<?php echo $my_username; ?></strong> &bull;
                        <?php endif; ?>
                        <?php echo $my_email; ?>
                    </p>
                </div>
                <div style="display: flex; gap: 0.5rem;">
                    <a href="profile.php" class="btn btn-secondary btn-sm">✏️ Edit Profile</a>
                    <a href="../backend/logout.php" class="btn btn-secondary btn-sm">Logout</a>
                </div>
            </div>

            <div class="my-profile-details">
                <div class="detail-item">
                    <span class="detail-label">Phone:</span>
                    <span><?php echo !empty($my_phone) ? $my_phone : 'Not provided'; ?></span>
                </div>

                <div class="detail-item">
                    <span class="detail-label">Address:</span>
                    <span><?php echo !empty($my_address) ? $my_address : 'Not provided'; ?></span>
                </div>

                <div class="detail-item">
                    <span class="detail-label">Location:</span>
                    <span>
                        <?php if ($my_lat !== null && $my_lon !== null): ?>
                            <?php echo round($my_lat, 4); ?>, <?php echo round($my_lon, 4); ?>
                        <?php else: ?>
                            Not set
                        <?php endif; ?>
                    </span>
                </div>
            </div>

            <div class="my-hobbies-section">
                <span class="detail-label">My Hobbies:</span>
                <div class="hobbies-wrap">
                    <?php if (!empty($my_hobbies_list)): ?>
                        <?php foreach ($my_hobbies_list as $hobby): ?>
                            <span class="badge badge-hobby"><?php echo htmlspecialchars($hobby, ENT_QUOTES, 'UTF-8'); ?></span>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <span style="color: var(--text-muted); font-size: 0.85rem;">No hobbies added yet.</span>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <!-- Search & Distance Filter Bar -->
        <section class="filter-section">
            <form action="dashboard.php" method="GET" class="filter-form">
                <div class="filter-group">
                    <label for="distance">Max Distance:</label>
                    <select id="distance" name="distance" class="form-control">
                        <option value="">Any Distance</option>
                        <option value="5" <?php echo ($filter_distance == 5) ? 'selected' : ''; ?>>Within 5 km</option>
                        <option value="10" <?php echo ($filter_distance == 10) ? 'selected' : ''; ?>>Within 10 km</option>
                        <option value="25" <?php echo ($filter_distance == 25) ? 'selected' : ''; ?>>Within 25 km</option>
                        <option value="50" <?php echo ($filter_distance == 50) ? 'selected' : ''; ?>>Within 50 km</option>
                        <option value="100" <?php echo ($filter_distance == 100) ? 'selected' : ''; ?>>Within 100 km</option>
                    </select>
                </div>

                <div class="filter-group filter-search">
                    <label for="search">Search:</label>
                    <input type="text" id="search" name="search" class="form-control" placeholder="Search by name, handle, address, or hobby" value="<?php echo htmlspecialchars($search_query, ENT_QUOTES, 'UTF-8'); ?>">
                </div>

                <div class="filter-actions">
                    <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                    <?php if ($filter_distance !== null || !empty($search_query)): ?>
                        <a href="dashboard.php" class="btn btn-secondary btn-sm">Reset</a>
                    <?php endif; ?>
                </div>
            </form>
        </section>

        <!-- People Found List / Grid -->
        <section>
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                <h3 style="font-size: 1.2rem;">Nearby People &amp; Hobby Matches (<?php echo count($people); ?> found)</h3>
            </div>

            <?php if (empty($people)): ?>
                <div class="empty-box">
                    <p style="font-size: 1.1rem; font-weight: 600; margin-bottom: 0.5rem;">No users found</p>
                    <p style="color: var(--text-muted); font-size: 0.9rem;">
                        No other users found matching your distance or search criteria. Try choosing a larger distance or clearing your search filter.
                    </p>
                </div>
            <?php else: ?>
                <div class="people-grid">
                    <?php foreach ($people as $person): ?>
                        <?php 
                            $has_matched_hobbies = !empty($person['matched_hobbies']);
                        ?>
                        <div class="person-card <?php echo $has_matched_hobbies ? 'has-matched-hobbies' : ''; ?>">
                            <div class="person-card-header">
                                <div>
                                    <h4 class="person-name"><?php echo htmlspecialchars($person['name'] ?? '', ENT_QUOTES, 'UTF-8'); ?></h4>
                                    <p class="person-handle">@<?php echo htmlspecialchars($person['username'] ?? '', ENT_QUOTES, 'UTF-8'); ?></p>
                                </div>
                                <?php if ($person['distance_km'] !== null): ?>
                                    <span class="distance-pill">
                                        📍 <?php echo $person['distance_km']; ?> km away
                                    </span>
                                <?php else: ?>
                                    <span class="distance-pill" style="color: var(--text-muted); background: #f3f4f6;">
                                        Location not set
                                    </span>
                                <?php endif; ?>
                            </div>

                            <div class="person-contact-info">
                                <?php if (!empty($person['address'])): ?>
                                    <p>🏠 <?php echo htmlspecialchars($person['address'], ENT_QUOTES, 'UTF-8'); ?></p>
                                <?php endif; ?>
                                <?php if (!empty($person['phone'])): ?>
                                    <p>📞 <a href="tel:<?php echo htmlspecialchars($person['phone'], ENT_QUOTES, 'UTF-8'); ?>" style="color: var(--primary); text-decoration: none;"><?php echo htmlspecialchars($person['phone'], ENT_QUOTES, 'UTF-8'); ?></a></p>
                                <?php endif; ?>
                            </div>

                            <!-- Matched Hobbies Display -->
                            <?php if ($has_matched_hobbies): ?>
                                <div class="matched-hobbies-box">
                                    <span class="matched-title">🎯 Common Hobbies (<?php echo count($person['matched_hobbies']); ?>):</span>
                                    <div class="hobbies-wrap">
                                        <?php foreach ($person['matched_hobbies'] as $h): ?>
                                            <span class="badge badge-matched"><?php echo htmlspecialchars($h, ENT_QUOTES, 'UTF-8'); ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <!-- Other Hobbies Display -->
                            <?php if (!empty($person['unmatched_hobbies'])): ?>
                                <div style="margin-top: 0.6rem;">
                                    <span style="font-size: 0.8rem; color: var(--text-muted); display: block; margin-bottom: 0.25rem;">Other Hobbies:</span>
                                    <div class="hobbies-wrap">
                                        <?php foreach ($person['unmatched_hobbies'] as $h): ?>
                                            <span class="badge badge-unmatched"><?php echo htmlspecialchars($h, ENT_QUOTES, 'UTF-8'); ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <?php if (empty($person['matched_hobbies']) && empty($person['unmatched_hobbies'])): ?>
                                <p style="font-size: 0.85rem; color: var(--text-muted); margin-top: 0.5rem; font-style: italic;">No hobbies listed.</p>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </main>

    <!-- Footer -->
    <footer class="site-footer">
        <p>&copy; 2026 NearMe. Built with Procedural PHP &amp; MySQL.</p>
    </footer>

    <!-- Scripts -->
    <script src="js/dashboard.js"></script>
</body>
</html>
