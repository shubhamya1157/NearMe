<?php
// frontend/dashboard.php
// Main dashboard: displays user profile, nearby people cards, and hobby matching

require_once __DIR__ . '/../backend/db.php';
require_once __DIR__ . '/../backend/auth.php';
require_once __DIR__ . '/../backend/find_people.php';

require_login('login.html');

$user_id = get_current_user_id();

// Fetch current user details
$escaped_id = (int)$user_id;
$user_sql = "SELECT * FROM users WHERE id = $escaped_id LIMIT 1";
$user_res = mysqli_query($conn, $user_sql);

if (!$user_res || mysqli_num_rows($user_res) === 0) {
    header("Location: login.html?error=session_expired");
    exit;
}

$current_user = mysqli_fetch_assoc($user_res);
$has_completed_profile = is_profile_complete($current_user);

$my_name = htmlspecialchars($current_user['name'] ?? 'NearMe User', ENT_QUOTES, 'UTF-8');
$my_username = htmlspecialchars($current_user['username'] ?? 'username', ENT_QUOTES, 'UTF-8');
$my_address = htmlspecialchars($current_user['address'] ?? 'No address provided', ENT_QUOTES, 'UTF-8');
$my_lat = $current_user['latitude'];
$my_lon = $current_user['longitude'];
$my_hobbies_list = parse_hobbies($current_user['hobbies'] ?? '');

// Initial fetch of nearby people for server-side rendering
$people = get_nearby_people($conn, $user_id);

// Sort initial list by distance ascending
usort($people, function($a, $b) {
    if ($a['distance_km'] === null) return 1;
    if ($b['distance_km'] === null) return -1;
    return $a['distance_km'] <=> $b['distance_km'];
});

// Calculate statistics
$total_people = count($people);
$total_matches = count(array_filter($people, function($p) { return $p['match_count'] > 0; }));
$closest_dist = '—';
$with_dist = array_filter($people, function($p) { return $p['distance_km'] !== null; });
if (!empty($with_dist)) {
    $min_val = min(array_column($with_dist, 'distance_km'));
    $closest_dist = $min_val . ' km';
}

// Collect unique community hobbies for the filter dropdown
$community_hobbies = [];
foreach ($people as $p) {
    if (!empty($p['hobbies_list'])) {
        foreach ($p['hobbies_list'] as $h) {
            $community_hobbies[strtolower($h)] = $h;
        }
    }
}
ksort($community_hobbies);

// Helper for initial avatars
function get_initials($name) {
    $parts = preg_split('/\s+/', trim($name));
    if (count($parts) === 1) {
        return strtoupper(substr($parts[0], 0, 2));
    }
    return strtoupper(substr($parts[0], 0, 1) . substr($parts[count($parts) - 1], 0, 1));
}

$my_initials = get_initials($current_user['name'] ?? 'NM');
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
                <li><a href="../backend/logout.php" class="btn btn-secondary btn-sm">Sign Out</a></li>
            </ul>
        </div>
    </header>

    <!-- Main Content -->
    <main class="main-content">
        <!-- Incomplete Profile Warning Banner -->
        <?php if (!$has_completed_profile): ?>
            <div class="alert alert-warning" style="margin-bottom: 1.5rem;">
                <span>⚠️</span>
                <span style="flex: 1;">
                    <strong>Incomplete Profile:</strong> You haven't configured your location or username yet. To accurately discover people near you and match hobbies, please set your coordinates and interests.
                </span>
                <a href="profile.php" class="btn btn-primary btn-sm">Complete Profile</a>
            </div>
        <?php endif; ?>

        <!-- Success Alert if redirected from profile update -->
        <?php if (isset($_GET['success']) && $_GET['success'] === 'profile_updated'): ?>
            <div class="alert alert-success" style="margin-bottom: 1.5rem;">
                <span>✅</span>
                <span>Your profile has been saved successfully! Nearby matches have been refreshed.</span>
            </div>
        <?php endif; ?>

        <!-- Current User Profile Banner -->
        <section class="profile-banner">
            <div class="profile-banner-left">
                <div class="avatar-circle">
                    <?php echo $my_initials; ?>
                </div>
                <div class="profile-banner-info">
                    <h2><?php echo $my_name; ?></h2>
                    <p class="user-handle">@<?php echo $my_username; ?> &bull; <?php echo htmlspecialchars($current_user['email']); ?></p>
                    <div class="profile-banner-details">
                        <span>🏠 <?php echo $my_address; ?></span>
                        <?php if ($my_lat !== null && $my_lon !== null): ?>
                            <span>🛰️ <?php echo round($my_lat, 4); ?>, <?php echo round($my_lon, 4); ?></span>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($my_hobbies_list)): ?>
                        <div class="my-hobbies-bar">
                            <strong>My Hobbies:</strong>
                            <?php foreach ($my_hobbies_list as $hobby): ?>
                                <span class="chip-banner"><?php echo htmlspecialchars($hobby, ENT_QUOTES, 'UTF-8'); ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="profile-banner-actions">
                <a href="profile.php" class="btn btn-white btn-sm">
                    ✏️ Edit Profile
                </a>
            </div>
        </section>

        <!-- Stats Overview Cards -->
        <section class="stats-row">
            <div class="stat-card">
                <span class="stat-icon">👥</span>
                <div>
                    <div class="stat-value" id="statTotalFound"><?php echo $total_people; ?></div>
                    <div class="stat-label">People in Community</div>
                </div>
            </div>

            <div class="stat-card">
                <span class="stat-icon">🎯</span>
                <div>
                    <div class="stat-value" id="statMatchesCount"><?php echo $total_matches; ?></div>
                    <div class="stat-label">Shared Hobby Matches</div>
                </div>
            </div>

            <div class="stat-card">
                <span class="stat-icon">📍</span>
                <div>
                    <div class="stat-value" id="statClosestDist"><?php echo $closest_dist; ?></div>
                    <div class="stat-label">Closest Person Distance</div>
                </div>
            </div>
        </section>

        <!-- Discovery & Filter Panel -->
        <section class="filter-panel">
            <div class="filter-header">
                <h3>🔍 Find People Near Me &amp; Match Hobbies</h3>
                <button type="button" id="btnResetFilters" class="btn btn-secondary btn-sm">
                    🔄 Reset Filters
                </button>
            </div>

            <div class="filter-grid">
                <!-- Distance Radius Slider -->
                <div class="slider-container">
                    <div class="slider-label-row">
                        <label for="radiusSlider" style="font-size: 0.88rem; font-weight: 600;">Distance Radius:</label>
                        <span id="radiusBadge" class="slider-badge">Within 50 km</span>
                    </div>
                    <input type="range" id="radiusSlider" min="1" max="100" value="50" step="1" class="range-slider">
                </div>

                <!-- Specific Hobby Filter -->
                <div class="form-group" style="margin-bottom: 0;">
                    <label for="hobbySelect" style="font-size: 0.88rem; font-weight: 600; margin-bottom: 0.4rem; display: block;">Filter by Hobby:</label>
                    <select id="hobbySelect" class="form-control" style="padding: 0.55rem 0.75rem;">
                        <option value="">All Hobbies</option>
                        <?php foreach ($community_hobbies as $k => $displayName): ?>
                            <option value="<?php echo htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8'); ?>">
                                <?php echo htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Minimum Shared Matches Filter -->
                <div class="form-group" style="margin-bottom: 0;">
                    <label for="minMatchSelect" style="font-size: 0.88rem; font-weight: 600; margin-bottom: 0.4rem; display: block;">Shared Passions:</label>
                    <select id="minMatchSelect" class="form-control" style="padding: 0.55rem 0.75rem;">
                        <option value="0">Any / All People</option>
                        <option value="1">At least 1 shared hobby</option>
                        <option value="2">2+ shared hobbies</option>
                        <option value="3">3+ shared hobbies</option>
                    </select>
                </div>

                <!-- Search Input -->
                <div class="form-group" style="margin-bottom: 0;">
                    <label for="searchInput" style="font-size: 0.88rem; font-weight: 600; margin-bottom: 0.4rem; display: block;">Search People:</label>
                    <input type="text" id="searchInput" class="form-control" placeholder="Search name, @handle, city..." style="padding: 0.55rem 0.75rem;">
                </div>
            </div>

            <!-- Sort By Row -->
            <div style="display: flex; justify-content: flex-end; align-items: center; gap: 0.5rem; margin-top: 1rem; padding-top: 0.75rem; border-top: 1px dashed var(--border-color);">
                <label for="sortSelect" style="font-size: 0.82rem; font-weight: 600; color: var(--text-muted);">Sort By:</label>
                <select id="sortSelect" class="form-control" style="width: auto; padding: 0.35rem 0.75rem; font-size: 0.85rem;">
                    <option value="distance">Nearest Distance First</option>
                    <option value="match">Most Shared Hobbies</option>
                    <option value="name">Name (A-Z)</option>
                </select>
            </div>
        </section>

        <!-- People Grid (Rendered by PHP, updated dynamically by JS) -->
        <section id="peopleGrid" class="people-grid">
            <?php foreach ($people as $person): ?>
                <?php 
                    $has_match = $person['match_count'] > 0;
                    $initials = get_initials($person['name']);
                    $gradients = ['avatar-gradient-1', 'avatar-gradient-2', 'avatar-gradient-3', 'avatar-gradient-4', 'avatar-gradient-5'];
                    $grad_class = $gradients[((int)$person['id']) % count($gradients)];
                    $dist_text = ($person['distance_km'] !== null) ? $person['distance_km'] . ' km away' : 'Location not set';
                    $is_close = ($person['distance_km'] !== null && $person['distance_km'] <= 5);
                    $person_json = htmlspecialchars(json_encode($person), ENT_QUOTES, 'UTF-8');
                ?>
                <div class="user-card <?php echo $has_match ? 'has-match' : ''; ?>">
                    <div>
                        <div class="card-top">
                            <div class="card-user-info">
                                <div class="avatar-circle avatar-circle-sm <?php echo $grad_class; ?>">
                                    <?php echo $initials; ?>
                                </div>
                                <div class="card-names">
                                    <h4><?php echo htmlspecialchars($person['name'] ?? '', ENT_QUOTES, 'UTF-8'); ?></h4>
                                    <p class="card-handle">@<?php echo htmlspecialchars($person['username'] ?? '', ENT_QUOTES, 'UTF-8'); ?></p>
                                </div>
                            </div>
                            <span class="distance-badge <?php echo $is_close ? 'close' : ''; ?>">
                                📍 <?php echo $dist_text; ?>
                            </span>
                        </div>

                        <?php if (!empty($person['address'])): ?>
                            <div class="card-address">
                                <span>🏠</span>
                                <span><?php echo htmlspecialchars($person['address'], ENT_QUOTES, 'UTF-8'); ?></span>
                            </div>
                        <?php endif; ?>

                        <?php if ($has_match): ?>
                            <div class="card-match-banner">
                                ✨ <strong><?php echo $person['match_count']; ?></strong> hobby <?php echo $person['match_count'] === 1 ? 'match' : 'matches'; ?> in common!
                            </div>
                        <?php endif; ?>

                        <div class="card-hobbies-section">
                            <span class="section-label">Hobbies &amp; Interests</span>
                            <div class="hobbies-tags-wrap">
                                <?php if (!empty($person['matched_hobbies'])): ?>
                                    <?php foreach ($person['matched_hobbies'] as $h): ?>
                                        <span class="tag tag-matched">🎯 <?php echo htmlspecialchars($h, ENT_QUOTES, 'UTF-8'); ?></span>
                                    <?php endforeach; ?>
                                <?php endif; ?>

                                <?php if (!empty($person['unmatched_hobbies'])): ?>
                                    <?php foreach ($person['unmatched_hobbies'] as $h): ?>
                                        <span class="tag tag-unmatched"><?php echo htmlspecialchars($h, ENT_QUOTES, 'UTF-8'); ?></span>
                                    <?php endforeach; ?>
                                <?php endif; ?>

                                <?php if (empty($person['matched_hobbies']) && empty($person['unmatched_hobbies'])): ?>
                                    <span style="color: #94a3b8; font-size: 0.8rem;">No hobbies listed</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer">
                        <span style="font-size: 0.82rem; color: #64748b;">
                            <?php echo !empty($person['phone']) ? '📞 ' . htmlspecialchars($person['phone'], ENT_QUOTES, 'UTF-8') : 'No phone listed'; ?>
                        </span>
                        <button type="button" class="btn btn-secondary btn-sm btn-view-person" data-person="<?php echo $person_json; ?>">
                            View Details
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </section>

        <!-- Empty State Container -->
        <div id="emptyState" class="empty-state" style="<?php echo empty($people) ? 'display: block;' : 'display: none;'; ?>">
            <div class="empty-state-icon">🔭</div>
            <h3>No people found matching your criteria</h3>
            <p>Try widening your distance radius slider, choosing "All Hobbies", or clearing your search keywords to find people around you.</p>
        </div>
    </main>

    <!-- User Detail Modal -->
    <div id="userDetailModal" class="modal-overlay">
        <div class="modal-box">
            <button type="button" id="modalCloseBtn" class="modal-close-btn">&times;</button>
            <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1.25rem;">
                <div id="modalAvatar" class="avatar-circle avatar-gradient-1"></div>
                <div>
                    <h3 id="modalName" style="font-size: 1.35rem; font-weight: 700; color: var(--text-main);"></h3>
                    <p id="modalHandle" style="font-size: 0.9rem; color: var(--text-muted);"></p>
                </div>
            </div>

            <div style="margin-bottom: 1.25rem; background: #f8fafc; padding: 1rem; border-radius: var(--radius-sm); border: 1px solid var(--border-color);">
                <div style="font-size: 0.9rem; margin-bottom: 0.45rem;">
                    <strong style="color: var(--text-main);">Distance:</strong>
                    <span id="modalDistance" style="color: var(--primary); font-weight: 600;"></span>
                </div>
                <div style="font-size: 0.9rem; margin-bottom: 0.45rem;">
                    <strong style="color: var(--text-main);">Address:</strong>
                    <span id="modalAddress" style="color: #475569;"></span>
                </div>
                <div style="font-size: 0.9rem; margin-bottom: 0.45rem;">
                    <strong style="color: var(--text-main);">Phone:</strong>
                    <span id="modalPhone" style="color: #475569;"></span>
                </div>
                <div style="font-size: 0.9rem;">
                    <strong style="color: var(--text-main);">Email:</strong>
                    <span id="modalEmail" style="color: #475569;"></span>
                </div>
            </div>

            <div style="margin-bottom: 1.25rem;">
                <strong style="display: block; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-muted); margin-bottom: 0.4rem;">
                    🎯 Matched Hobbies With You
                </strong>
                <div id="modalMatchedHobbies" class="hobbies-tags-wrap"></div>
            </div>

            <div style="margin-bottom: 1.5rem;">
                <strong style="display: block; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-muted); margin-bottom: 0.4rem;">
                    All Hobbies &amp; Passions
                </strong>
                <div id="modalAllHobbies" class="hobbies-tags-wrap"></div>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 0.75rem;">
                <a id="modalPhoneBtn" href="#" class="btn btn-accent btn-sm" style="display: none;">
                    📞 Call / Message
                </a>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="site-footer">
        <p>&copy; 2026 NearMe Application. Built with Procedural PHP &amp; MySQL.</p>
    </footer>

    <!-- Scripts -->
    <script src="js/dashboard.js"></script>
</body>
</html>
