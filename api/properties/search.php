<?php
// /api/properties/search.php

// --- Example Request ---
// {
//   "latitude": 40.7128,
//   "longitude": -74.0060,
//   "keyword": "apartment",
//   "type": "apartment",
//   "price_min": 100,
//   "price_max": 500,
//   "bedrooms": 2,
//   "bathrooms": 1,
//   "booking_type": "instant",
//   "free_cancellation": true
// }
// --- Example Response ---
// {
//   "status": "success",
//   "results_count": 1,
//   "applied_filters": {
//     "keyword": "apartment",
//     "type": "apartment",
//     "price_min": 100,
//     "price_max": 500,
//     "bedrooms": 2,
//     "bathrooms": 1,
//     "booking_type": "instant",
//     "free_cancellation": true
//   },
//   "properties": [
//     {
//       "id": 12,
//       "name": "Luxury Downtown Apartment",
//       "image": "http://example.com/uploads/image1.jpg",
//       "distance": 0.1,
//       "price": 250,
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
    $jwt = get_jwt_from_header();
    if (!$jwt) send_response('error', 'Authentication token not provided.');

    $decoded = validate_jwt($jwt);
    if (!$decoded) send_response('error', 'Invalid or expired token.');

    $data = json_decode(file_get_contents('php://input'), true);
    $user_lat = $data['latitude'] ?? null;
    $user_lon = $data['longitude'] ?? null;

    if ($user_lat === null || $user_lon === null) {
        send_response('error', 'Missing required fields: latitude, longitude.');
    }

    $pdo = get_db_connection();

    $sql = "SELECT p.*, pi.image_url FROM properties p
            LEFT JOIN (
                SELECT property_id, MIN(image_url) as image_url
                FROM property_images GROUP BY property_id
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
            'image' => $property['image_url'],
            'distance' => round($property['distance'], 2),
            'price' => (float) $property['price'],
            'price_type' => $property['price_type'],
            'city' => $property['city'],
            'state' => $property['state'],
        ];
    }

    send_response('success', null, [
        'results_count' => count($response_properties),
        'applied_filters' => $applied_filters,
        'properties' => $response_properties
    ]);

} catch (Exception $e) {
    send_response('error', $e->getMessage());
}
?>
