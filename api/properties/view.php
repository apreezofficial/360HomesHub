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
$property_id = $data['property_id'] ?? null;
$user_lat = $data['latitude'] ?? null;
$user_lon = $data['longitude'] ?? null;

if (!$property_id || $user_lat === null || $user_lon === null) {
    send_error('Missing required fields: property_id, latitude, longitude.', [], 400);
}

try {
    $pdo = Database::getInstance();

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
        send_error('Property not found.', [], 404);
    }

    // Fetch property images
    $img_stmt = $pdo->prepare("SELECT image_url FROM property_images WHERE property_id = ?");
    $img_stmt->execute([$property_id]);
    $images = $img_stmt->fetchAll(PDO::FETCH_COLUMN);

    // Fetch amenities
    $amenities_stmt = $pdo->prepare("
        SELECT a.name FROM amenities a
        INNER JOIN property_amenities pa ON a.id = pa.amenity_id
        WHERE pa.property_id = ?
    ");
    $amenities_stmt->execute([$property_id]);
    $amenities = $amenities_stmt->fetchAll(PDO::FETCH_COLUMN);

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
        'cancellation_policy' => (bool) $property['cancellation_policy'],
        'house_rules' => $property['house_rules'],
        'important_information' => $property['important_information'],
        'amenities' => $amenities,
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

    send_success('Property details retrieved successfully.', ['property' => $response_data]);

} catch (Exception $e) {
    send_error('An error occurred while fetching property details: ' . $e->getMessage(), [], 500);
}