<?php
// /api/dashboard/home.php

// --- Example Request ---
// {
//   "latitude": 40.7128,
//   "longitude": -74.0060
// }
// --- Example Response ---
// {
//   "status": "success",
//   "data": {
//     "welcome_message": "Welcome back, John!",
//     "unread_notifications": 12,
//     "unread_messages": 5,
//     "property_categories": {
//       "apartment": 50,
//       "house": 30,
//       "studio": 20,
//       "duplex": 10,
//       "hotel": 5
//     },
//     "nearby_properties": [
//       {
//         "id": 15,
//         "name": "Cozy Studio Near Park",
//         "image": "http://example.com/uploads/studio_main.jpg",
//         "distance": 0.5,
//         "price": 120,
//         "price_type": "night",
//         "city": "New York",
//         "state": "NY"
//       }
//     ]
//   }
// }

header('Content-Type: application/json');

require_once '../../vendor/autoload.php';
require_once '../../utils/db.php';
require_once '../../utils/jwt.php';
require_once '../../utils/geo.php';
require_once '../../utils/response.php';

try {
    // Authenticate user
    $jwt = get_jwt_from_header();
    if (!$jwt) send_response('error', 'Authentication token not provided.');

    $decoded = validate_jwt($jwt);
    if (!$decoded) send_response('error', 'Invalid or expired token.');
    $user_id = $decoded->data->user_id;

    // Get input data
    $data = json_decode(file_get_contents('php://input'), true);
    $user_lat = $data['latitude'] ?? null;
    $user_lon = $data['longitude'] ?? null;

    if ($user_lat === null || $user_lon === null) {
        send_response('error', 'Missing required fields: latitude, longitude.');
    }

    $pdo = get_db_connection();

    // 1. Welcome Message
    $user_stmt = $pdo->prepare("SELECT first_name FROM users WHERE id = ?");
    $user_stmt->execute([$user_id]);
    $user = $user_stmt->fetch(PDO::FETCH_ASSOC);
    $welcome_message = "Welcome back, " . ($user ? $user['first_name'] : 'Guest') . "!";

    // 2. Unread Notifications Count
    $notif_stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $notif_stmt->execute([$user_id]);
    $unread_notifications = (int) $notif_stmt->fetchColumn();

    // 3. Unread Messages Count
    $msg_stmt = $pdo->prepare("SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND is_read = 0");
    $msg_stmt->execute([$user_id]);
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
            SELECT property_id, MIN(image_url) as image_url
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

    send_response('success', null, ['data' => $dashboard_data]);

} catch (Exception $e) {
    send_response('error', $e->getMessage());
}
?>
