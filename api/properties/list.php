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
$page = isset($data['page']) ? (int)$data['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

if ($user_lat === null || $user_lon === null) {
    send_error('Missing required fields: latitude, longitude.', [], 400);
}

try {
    $pdo = Database::getInstance();

    // Count total properties for pagination
    $total_stmt = $pdo->query("SELECT COUNT(*) FROM properties");
    $total_results = (int) $total_stmt->fetchColumn();
    $total_pages = ceil($total_results / $limit);

    // Fetch all properties with their main image
    $stmt = $pdo->prepare("
        SELECT p.*, pi.image_url
        FROM properties p
        LEFT JOIN (
            SELECT property_id, MIN(image_url) as image_url
            FROM property_images
            GROUP BY property_id
        ) pi ON p.id = pi.property_id
    ");
    $stmt->execute();
    $properties = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate distance for each property
    foreach ($properties as &$property) {
        $property['distance'] = calculateDistance(
            $user_lat, $user_lon,
            $property['latitude'], $property['longitude']
        );
    }
    unset($property); // Unset reference

    // Sort properties by distance
    usort($properties, function($a, $b) {
        return $a['distance'] <=> $b['distance'];
    });

    // Paginate the sorted results
    $paginated_properties = array_slice($properties, $offset, $limit);

    // Format response
    $response_properties = [];
    foreach ($paginated_properties as $property) {
        $response_properties[] = [
            'id' => (int) $property['id'],
            'name' => $property['name'],
            'image' => $property['image_url'],
            'distance' => round($property['distance'], 2),
            'price' => (float) $property['price'],
            'price_type' => $property['price_type'],
            'city' => $property['city'],
            'state' => $property['state'],
        ];
    }

    $pagination_data = [
        'current_page' => $page,
        'total_pages' => $total_pages,
        'total_results' => $total_results,
    ];

    send_success('Properties listed successfully.', [
        'pagination' => $pagination_data,
        'properties' => $response_properties
    ]);

} catch (Exception $e) {
    send_error('An error occurred while fetching properties: ' . $e->getMessage(), [], 500);
}