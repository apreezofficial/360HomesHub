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

    $sql = "SELECT p.*, pi.media_url FROM properties p
            LEFT JOIN (
                SELECT property_id, MIN(media_url) as media_url
                FROM property_images 
                WHERE media_type = 'image'
                GROUP BY property_id
            ) pi ON p.id = pi.property_id
            WHERE 1=1";
    
    $params = [];
    $applied_filters = [];

    // Keyword filter
    if (!empty($data['keyword'])) {
        $sql .= " AND (p.name LIKE ? OR p.city LIKE ? OR p.state LIKE ?)";
        $keyword = '%' . $data['keyword'] . '%';
        array_push($params, $keyword, $keyword, $keyword);
        $applied_filters['keyword'] = $data['keyword'];
    }

    // Property type filter
    if (!empty($data['type'])) {
        $sql .= " AND p.type = ?";
        $params[] = $data['type'];
        $applied_filters['type'] = $data['type'];
    }

    // Price range filter
    if (isset($data['price_min'])) {
        $sql .= " AND p.price >= ?";
        $params[] = $data['price_min'];
        $applied_filters['price_min'] = $data['price_min'];
    }
    if (isset($data['price_max'])) {
        $sql .= " AND p.price <= ?";
        $params[] = $data['price_max'];
        $applied_filters['price_max'] = $data['price_max'];
    }

    // Bedrooms filter
    if (isset($data['bedrooms']) && $data['bedrooms'] > 0) {
        $sql .= " AND p.bedrooms >= ?";
        $params[] = $data['bedrooms'];
        $applied_filters['bedrooms'] = $data['bedrooms'];
    }

    // Bathrooms filter
    if (isset($data['bathrooms']) && $data['bathrooms'] > 0) {
        $sql .= " AND p.bathrooms >= ?";
        $params[] = $data['bathrooms'];
        $applied_filters['bathrooms'] = $data['bathrooms'];
    }

    // Booking type filter
    if (!empty($data['booking_type'])) {
        $sql .= " AND p.booking_type = ?";
        $params[] = $data['booking_type'];
        $applied_filters['booking_type'] = $data['booking_type'];
    }
    
    // Free cancellation filter
    if (isset($data['free_cancellation']) && $data['free_cancellation'] === true) {
        $sql .= " AND p.free_cancellation = 1";
        $applied_filters['free_cancellation'] = true;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $properties = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($properties as &$property) {
        $property['distance'] = calculateDistance($user_lat, $user_lon, $property['latitude'], $property['longitude']);
    }
    unset($property);

    usort($properties, fn($a, $b) => $a['distance'] <=> $b['distance']);

    $response_properties = [];
    foreach ($properties as $property) {
        $response_properties[] = [
            'id' => (int) $property['id'],
            'name' => $property['name'],
            'image' => $property['media_url'],
            'distance' => round($property['distance'], 2),
            'price' => (float) $property['price'],
            'price_type' => $property['price_type'],
            'city' => $property['city'],
            'state' => $property['state'],
        ];
    }

    send_success('Search results retrieved successfully.', [
        'results_count' => count($response_properties),
        'applied_filters' => $applied_filters,
        'properties' => $response_properties
    ]);

} catch (Exception $e) {
    send_error('An error occurred during search: ' . $e->getMessage(), [], 500);
}