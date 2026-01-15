<?php
// /api/properties/list.php

// --- Example Request ---
// {
//   "latitude": 40.7128,
//   "longitude": -74.0060,
//   "page": 1
// }
// --- Example Response ---
// {
//   "status": "success",
//   "pagination": {
//     "current_page": 1,
//     "total_pages": 10,
//     "total_results": 100
//   },
//   "properties": [
//     {
//       "id": 15,
//       "name": "Cozy Studio Near Park",
//       "image": "http://example.com/uploads/studio_main.jpg",
//       "distance": 0.5,
//       "price": 120,
//       "price_type": "night",
//       "city": "New York",
//       "state": "NY"
//     }
//   ]
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
    $user_lat = $data['latitude'] ?? null;
    $user_lon = $data['longitude'] ?? null;
    $page = isset($data['page']) ? (int)$data['page'] : 1;
    $limit = 10;
    $offset = ($page - 1) * $limit;

    if ($user_lat === null || $user_lon === null) {
        send_response('error', 'Missing required fields: latitude, longitude.');
    }

    // Get DB connection
    $pdo = get_db_connection();

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

    send_response('success', null, [
        'pagination' => $pagination_data,
        'properties' => $response_properties
    ]);

} catch (Exception $e) {
    send_response('error', $e->getMessage());
}
?>
