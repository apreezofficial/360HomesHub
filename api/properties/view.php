<?php
// /api/properties/view.php

// --- Example Request ---
// {
//   "property_id": 12,
//   "latitude": 40.7128,
//   "longitude": -74.0060
// }
// --- Example Response ---
// {
//   "status": "success",
//   "property": {
//     "id": 12,
//     "name": "Luxury Downtown Apartment",
//     "description": "A beautiful apartment in the heart of the city.",
//     "type": "apartment",
//     "price": 250,
//     "price_type": "night",
//     "bedrooms": 2,
//     "bathrooms": 2,
//     "area": 1200,
//     "booking_type": "instant",
//     "free_cancellation": true,
//     "amenities": ["wifi", "pool", "gym"],
//     "city": "New York",
//     "state": "NY",
//     "latitude": 40.7138,
//     "longitude": -74.0070,
//     "distance": 0.1,
//     "images": [
//       "http://example.com/uploads/image1.jpg",
//       "http://example.com/uploads/image2.jpg"
//     ],
//     "host": {
//       "id": 5,
//       "first_name": "John",
//       "last_name": "Doe",
//       "avatar": "http://example.com/avatars/johndoe.jpg"
//     }
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
    if (!$jwt) {
        send_response('error', 'Authentication token not provided.');
    }
    $decoded = validate_jwt($jwt);
    if (!$decoded) {
        send_response('error', 'Invalid or expired token.');
    }

    // Get input data
    $data = json_decode(file_get_contents('php://input'), true);
    $property_id = $data['property_id'] ?? null;
    $user_lat = $data['latitude'] ?? null;
    $user_lon = $data['longitude'] ?? null;

    if (!$property_id || $user_lat === null || $user_lon === null) {
        send_response('error', 'Missing required fields: property_id, latitude, longitude.');
    }

    // Get DB connection
    $pdo = get_db_connection();

    // Fetch property details
    $stmt = $pdo->prepare("
        SELECT p.*, u.first_name, u.last_name, u.avatar
        FROM properties p
        JOIN users u ON p.host_id = u.id
        WHERE p.id = ?
    ");
    $stmt->execute([$property_id]);
    $property = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$property) {
        send_response('error', 'Property not found.');
    }

    // Fetch property images
    $img_stmt = $pdo->prepare("SELECT image_url FROM property_images WHERE property_id = ?");
    $img_stmt->execute([$property_id]);
    $images = $img_stmt->fetchAll(PDO::FETCH_COLUMN);

    // Calculate distance
    $distance = calculateDistance($user_lat, $user_lon, $property['latitude'], $property['longitude']);

    // Format response
    $response_data = [
        'id' => (int) $property['id'],
        'name' => $property['name'],
        'description' => $property['description'],
        'type' => $property['type'],
        'price' => (float) $property['price'],
        'price_type' => $property['price_type'],
        'bedrooms' => (int) $property['bedrooms'],
        'bathrooms' => (int) $property['bathrooms'],
        'area' => (int) $property['area'],
        'booking_type' => $property['booking_type'],
        'free_cancellation' => (bool) $property['free_cancellation'],
        'amenities' => json_decode($property['amenities']),
        'city' => $property['city'],
        'state' => $property['state'],
        'latitude' => (float) $property['latitude'],
        'longitude' => (float) $property['longitude'],
        'distance' => round($distance, 2),
        'images' => $images,
        'host' => [
            'id' => (int) $property['host_id'],
            'first_name' => $property['first_name'],
            'last_name' => $property['last_name'],
            'avatar' => $property['avatar']
        ]
    ];

    send_response('success', null, ['property' => $response_data]);

} catch (Exception $e) {
    send_response('error', $e->getMessage());
}
?>
