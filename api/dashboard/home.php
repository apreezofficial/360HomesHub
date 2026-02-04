<?php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../utils/db.php';
require_once __DIR__ . '/../../utils/response.php';
require_once __DIR__ . '/../../utils/jwt.php';
require_once __DIR__ . '/../../utils/geo.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_error('Invalid request method.', [], 405);
}

// Authenticate user via JWT
$userData = JWTManager::authenticate();
$userId = $userData['user_id'] ?? null;

if (!$userId) {
    send_error('Authentication failed.', [], 401);
}

// Get input data
$data = json_decode(file_get_contents('php://input'), true);
$user_lat = $data['latitude'] ?? null;
$user_lon = $data['longitude'] ?? null;

if ($user_lat === null || $user_lon === null) {
    send_error('Missing required fields: latitude, longitude.', [], 400);
}

try {
    $pdo = Database::getInstance();

    // 1. Welcome Message
    $user_stmt = $pdo->prepare("SELECT first_name FROM users WHERE id = ?");
    $user_stmt->execute([$userId]);
    $user = $user_stmt->fetch(PDO::FETCH_ASSOC);
    $welcome_message = "Welcome back, " . ($user ? $user['first_name'] : 'Guest') . "!";

    // 2. Unread Notifications Count
    $notif_stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $notif_stmt->execute([$userId]);
    $unread_notifications = (int) $notif_stmt->fetchColumn();

    // 3. Unread Messages Count
    $msg_stmt = $pdo->prepare("SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND is_read = 0");
    $msg_stmt->execute([$userId]);
    $unread_messages = (int) $msg_stmt->fetchColumn();

    // 4. Property Category Counts
    $categories = ['apartment', 'house', 'studio', 'duplex', 'hotel'];
    $category_counts = array_fill_keys($categories, 0);
    $cat_stmt = $pdo->query("SELECT type, COUNT(*) as count FROM properties GROUP BY type");
    while ($row = $cat_stmt->fetch(PDO::FETCH_ASSOC)) {
        if (in_array($row['type'], $categories)) {
            $category_counts[$row['type']] = (int) $row['count'];
        }
    }

    // 5. Nearby Properties
    $prop_stmt = $pdo->prepare("
        SELECT p.id, p.name, p.price, p.price_type, p.city, p.state, p.latitude, p.longitude, pi.image_url
        FROM properties p
        LEFT JOIN (
            SELECT property_id, MIN(media_url) as image_url
            FROM property_images GROUP BY property_id
        ) pi ON p.id = pi.property_id
    ");
    $prop_stmt->execute();
    $properties = $prop_stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($properties as &$property) {
        $property['distance'] = calculateDistance($user_lat, $user_lon, $property['latitude'], $property['longitude']);
    }
    unset($property);

    usort($properties, fn($a, $b) => $a['distance'] <=> $b['distance']);
    $nearby_properties = array_slice($properties, 0, 10); // Get top 10 nearest

    $response_properties = [];
    foreach ($nearby_properties as $prop) {
        $response_properties[] = [
            'id' => (int) $prop['id'],
            'name' => $prop['name'],
            'image' => $prop['image_url'],
            'distance' => round($prop['distance'], 2),
            'price' => (float) $prop['price'],
            'price_type' => $prop['price_type'],
            'city' => $prop['city'],
            'state' => $prop['state'],
        ];
    }
    
    // Assemble final response
    $dashboard_data = [
        'welcome_message' => $welcome_message,
        'unread_notifications' => $unread_notifications,
        'unread_messages' => $unread_messages,
        'property_categories' => $category_counts,
        'nearby_properties' => $response_properties,
    ];

    send_success('Dashboard data retrieved successfully.', $dashboard_data);

} catch (Exception $e) {
    send_error('An error occurred while fetching dashboard data: ' . $e->getMessage(), [], 500);
}