<?php
// backend/find_people.php
// Finds nearby users and calculates distance and matching hobbies using procedural PHP

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

/**
 * Calculates distance in kilometers between two coordinates using the Haversine formula
 */
function calculate_distance_km($lat1, $lon1, $lat2, $lon2) {
    if ($lat1 === null || $lon1 === null || $lat2 === null || $lon2 === null) {
        return null;
    }
    if ($lat1 === '' || $lon1 === '' || $lat2 === '' || $lon2 === '') {
        return null;
    }

    $earth_radius = 6371; // Earth's radius in km

    $lat1 = deg2rad((float)$lat1);
    $lon1 = deg2rad((float)$lon1);
    $lat2 = deg2rad((float)$lat2);
    $lon2 = deg2rad((float)$lon2);

    $dlat = $lat2 - $lat1;
    $dlon = $lon2 - $lon1;

    $val = sin($dlat / 2) * sin($dlat / 2) +
           cos($lat1) * cos($lat2) *
           sin($dlon / 2) * sin($dlon / 2);

    $val = min(1.0, max(0.0, $val));
    $c = 2 * atan2(sqrt($val), sqrt(1 - $val));
    return round($earth_radius * $c, 1);
}

/**
 * Converts comma-separated hobbies string into an array of clean strings
 */
function parse_hobbies($hobbies_str) {
    if (empty($hobbies_str)) {
        return [];
    }

    $parts = explode(',', $hobbies_str);
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
 * Finds which hobbies match between two users
 */
function match_hobbies($my_hobbies, $their_hobbies) {
    $my_normalized = array_map('strtolower', array_map('trim', $my_hobbies));
    $matched = [];
    $unmatched = [];

    foreach ($their_hobbies as $hobby) {
        $trimmed = trim($hobby);
        if ($trimmed === '') continue;

        if (in_array(strtolower($trimmed), $my_normalized)) {
            $matched[] = $trimmed;
        } else {
            $unmatched[] = $trimmed;
        }
    }

    return [
        'matched' => $matched,
        'unmatched' => $unmatched,
        'count' => count($matched)
    ];
}

/**
 * Fetches other users from the database, calculates distance and matching hobbies
 */
function get_nearby_people($conn, $current_user_id, $max_distance = null, $search_term = '') {
    $escaped_id = (int)$current_user_id;

    // Get current user's profile
    $user_sql = "SELECT id, latitude, longitude, hobbies FROM users WHERE id = $escaped_id LIMIT 1";
    $user_res = mysqli_query($conn, $user_sql);
    if (!$user_res || mysqli_num_rows($user_res) === 0) {
        return [];
    }
    $current_user = mysqli_fetch_assoc($user_res);

    $my_lat = $current_user['latitude'];
    $my_lon = $current_user['longitude'];
    $my_hobbies = parse_hobbies($current_user['hobbies'] ?? '');

    // Fetch other registered users who have filled their basic details
    $sql = "SELECT id, email, username, name, phone, address, latitude, longitude, hobbies 
            FROM users 
            WHERE id != $escaped_id AND name IS NOT NULL AND latitude IS NOT NULL";

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

        // Calculate matching hobbies
        $their_hobbies = parse_hobbies($row['hobbies'] ?? '');
        $hobby_match = match_hobbies($my_hobbies, $their_hobbies);

        // Filter by max distance if specified
        if ($max_distance !== null && $max_distance > 0) {
            if ($dist === null || $dist > $max_distance) {
                continue;
            }
        }

        // Filter by search term if specified (matches name, username, address, or hobby)
        if (!empty($search_term)) {
            $q = strtolower(trim($search_term));
            $name_match = strpos(strtolower($row['name'] ?? ''), $q) !== false;
            $user_match = strpos(strtolower($row['username'] ?? ''), $q) !== false;
            $addr_match = strpos(strtolower($row['address'] ?? ''), $q) !== false;
            $hobb_match = strpos(strtolower($row['hobbies'] ?? ''), $q) !== false;

            if (!$name_match && !$user_match && !$addr_match && !$hobb_match) {
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

    // Sort by distance ascending
    usort($people, function($a, $b) {
        if ($a['distance_km'] === null) return 1;
        if ($b['distance_km'] === null) return -1;
        return $a['distance_km'] <=> $b['distance_km'];
    });

    return $people;
}
?>