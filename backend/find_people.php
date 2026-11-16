<?php
// backend/find_people.php
// Finds nearby users and calculates distance and matching hobbies using procedural PHP

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

/**
 * Calculates the great-circle distance between two coordinates using the Haversine formula
 *
 * @param float|null $lat1 Current user latitude
 * @param float|null $lon1 Current user longitude
 * @param float|null $lat2 Target user latitude
 * @param float|null $lon2 Target user longitude
 * @return float|null Distance in kilometers, or null if coordinates are missing
 */
function calculate_distance_km($lat1, $lon1, $lat2, $lon2) {
    if ($lat1 === null || $lon1 === null || $lat2 === null || $lon2 === null) {
        return null;
    }
    if ($lat1 === '' || $lon1 === '' || $lat2 === '' || $lon2 === '') {
        return null;
    }

    $earth_radius = 6371; // Earth radius in km

    $lat1 = deg2rad((float)$lat1);
    $lon1 = deg2rad((float)$lon1);
    $lat2 = deg2rad((float)$lat2);
    $lon2 = deg2rad((float)$lon2);

    $dlat = $lat2 - $lat1;
    $dlon = $lon2 - $lon1;

    $val = sin($dlat / 2) * sin($dlat / 2) +
           cos($lat1) * cos($lat2) *
           sin($dlon / 2) * sin($dlon / 2);

    // Prevent floating point inaccuracy exceeding 1
    $val = min(1.0, max(0.0, $val));

    $c = 2 * atan2(sqrt($val), sqrt(1 - $val));
    $distance = $earth_radius * $c;

    return round($distance, 1);
}

/**
 * Parses comma-separated or JSON hobbies string into an array of clean, trimmed hobby strings
 *
 * @param string|null $hobbies_str
 * @return array
 */
function parse_hobbies($hobbies_str) {
    if (empty($hobbies_str)) {
        return [];
    }

    $trimmed_str = trim($hobbies_str);
    if ($trimmed_str === '') {
        return [];
    }

    // Check if JSON formatted
    if (strpos($trimmed_str, '[') === 0) {
        $decoded = json_decode($trimmed_str, true);
        if (is_array($decoded)) {
            $cleaned = [];
            foreach ($decoded as $item) {
                $t = trim((string)$item);
                if ($t !== '') {
                    $cleaned[] = $t;
                }
            }
            return array_values(array_unique($cleaned));
        }
    }

    // Comma-separated format
    $parts = explode(',', $trimmed_str);
    $cleaned = [];
    foreach ($parts as $part) {
        $t = trim($part);
        if ($t !== '') {
            $cleaned[] = $t;
        }
    }
    return array_values(array_unique($cleaned));
}

/**
 * Compares two lists of hobbies and returns matched and unmatched items
 *
 * @param array $my_hobbies
 * @param array $their_hobbies
 * @return array ['matched' => array, 'unmatched' => array, 'count' => int]
 */
function match_hobbies($my_hobbies, $their_hobbies) {
    $my_normalized = [];
    foreach ($my_hobbies as $h) {
        $my_normalized[strtolower(trim($h))] = true;
    }

    $matched = [];
    $unmatched = [];

    foreach ($their_hobbies as $hobby) {
        $key = strtolower(trim($hobby));
        if (isset($my_normalized[$key])) {
            $matched[] = $hobby;
        } else {
            $unmatched[] = $hobby;
        }
    }

    return [
        'matched' => $matched,
        'unmatched' => $unmatched,
        'count' => count($matched)
    ];
}

/**
 * Retrieves nearby users relative to the given user, calculating distance and matching hobbies
 *
 * @param mysqli $conn
 * @param int $current_user_id
 * @param float|null $radius_km
 * @param string|null $filter_hobby
 * @param int $min_match
 * @param string $search_term
 * @return array
 */
function get_nearby_people($conn, $current_user_id, $radius_km = null, $filter_hobby = null, $min_match = 0, $search_term = '') {
    $escaped_id = (int)$current_user_id;

    // Fetch current user details
    $user_sql = "SELECT id, email, username, name, phone, address, latitude, longitude, hobbies FROM users WHERE id = $escaped_id LIMIT 1";
    $user_res = mysqli_query($conn, $user_sql);

    if (!$user_res || mysqli_num_rows($user_res) === 0) {
        return [];
    }

    $current_user = mysqli_fetch_assoc($user_res);
    $my_lat = $current_user['latitude'];
    $my_lon = $current_user['longitude'];
    $my_hobbies = parse_hobbies($current_user['hobbies'] ?? '');

    // Fetch other registered users who have completed their basic profile
    $sql = "SELECT id, email, username, name, phone, address, latitude, longitude, hobbies, created_at 
            FROM users 
            WHERE id != $escaped_id 
              AND username IS NOT NULL 
              AND name IS NOT NULL";

    $result = mysqli_query($conn, $sql);
    if (!$result) {
        return [];
    }

    $people = [];
    while ($row = mysqli_fetch_assoc($result)) {
        // Calculate distance
        $dist = null;
        if ($my_lat !== null && $my_lon !== null && $row['latitude'] !== null && $row['longitude'] !== null) {
            $dist = calculate_distance_km($my_lat, $my_lon, $row['latitude'], $row['longitude']);
        }

        // Parse their hobbies & find intersection with current user
        $their_hobbies = parse_hobbies($row['hobbies'] ?? '');
        $hobby_match = match_hobbies($my_hobbies, $their_hobbies);

        // Apply distance radius filter
        if ($radius_km !== null && $radius_km > 0) {
            if ($dist === null || $dist > $radius_km) {
                continue;
            }
        }

        // Apply minimum shared hobbies filter
        if ($min_match > 0 && $hobby_match['count'] < $min_match) {
            continue;
        }

        // Apply specific hobby filter
        if (!empty($filter_hobby)) {
            $hobby_lower = strtolower(trim($filter_hobby));
            $found_hobby = false;
            foreach ($their_hobbies as $h) {
                if (strtolower(trim($h)) === $hobby_lower) {
                    $found_hobby = true;
                    break;
                }
            }
            if (!$found_hobby) {
                continue;
            }
        }

        // Apply text search filter (matches name, username, address, or hobbies)
        if (!empty($search_term)) {
            $needle = strtolower(trim($search_term));
            $name_m = strpos(strtolower($row['name'] ?? ''), $needle) !== false;
            $user_m = strpos(strtolower($row['username'] ?? ''), $needle) !== false;
            $addr_m = strpos(strtolower($row['address'] ?? ''), $needle) !== false;
            $hobb_m = strpos(strtolower($row['hobbies'] ?? ''), $needle) !== false;

            if (!$name_m && !$user_m && !$addr_m && !$hobb_m) {
                continue;
            }
        }

        $row['distance_km'] = $dist;
        $row['hobbies_list'] = $their_hobbies;
        $row['matched_hobbies'] = $hobby_match['matched'];
        $row['unmatched_hobbies'] = $hobby_match['unmatched'];
        $row['match_count'] = $hobby_match['count'];

        $people[] = $row;
    }

    return $people;
}

// If accessed directly over HTTP / AJAX, serve JSON response
if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) {
    if (!is_logged_in()) {
        header('Content-Type: application/json', true, 401);
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
        exit;
    }

    $radius = isset($_GET['radius']) && $_GET['radius'] !== '' && (float)$_GET['radius'] > 0 ? (float)$_GET['radius'] : null;
    $hobby = isset($_GET['hobby']) && trim($_GET['hobby']) !== '' ? trim($_GET['hobby']) : null;
    $min_match = isset($_GET['min_match']) ? (int)$_GET['min_match'] : 0;
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $sort = isset($_GET['sort']) ? trim($_GET['sort']) : 'distance';

    $people = get_nearby_people($conn, $_SESSION['user_id'], $radius, $hobby, $min_match, $search);

    // Sort results
    if ($sort === 'match') {
        usort($people, function($a, $b) {
            if ($b['match_count'] === $a['match_count']) {
                // Secondary sort: nearest distance
                if ($a['distance_km'] === null) return 1;
                if ($b['distance_km'] === null) return -1;
                return $a['distance_km'] <=> $b['distance_km'];
            }
            return $b['match_count'] <=> $a['match_count'];
        });
    } elseif ($sort === 'name') {
        usort($people, function($a, $b) {
            return strcasecmp($a['name'], $b['name']);
        });
    } else { // default: distance
        usort($people, function($a, $b) {
            if ($a['distance_km'] === null) return 1;
            if ($b['distance_km'] === null) return -1;
            return $a['distance_km'] <=> $b['distance_km'];
        });
    }

    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'success',
        'count' => count($people),
        'data' => $people
    ]);
    exit;
}
?>