<?php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../utils/db.php';
require_once __DIR__ . '/../../utils/response.php';
require_once __DIR__ . '/../../utils/jwt.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    send_error('Invalid request method.', [], 405);
}

// Authenticate user via JWT
$userData = JWTManager::authenticate();
$userId = $userData['user_id'] ?? null;

if (!$userId) {
    send_error('Authentication failed.', [], 401);
}

$pdo = Database::getInstance();

// Get filter parameters
$status = $_GET['status'] ?? null; // 'pending', 'confirmed', 'cancelled', 'completed', 'rejected'
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = isset($_GET['limit']) ? min(50, max(1, intval($_GET['limit']))) : 20;
$offset = ($page - 1) * $limit;

try {
    // Build query - get bookings made by this user
    $where = ["b.user_id = ?"];
    $params = [$userId];

    if ($status && in_array($status, ['pending', 'confirmed', 'cancelled', 'completed', 'rejected'])) {
        $where[] = "b.status = ?";
        $params[] = $status;
    }

    $whereClause = implode(' AND ', $where);

    // Get total count
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total 
        FROM bookings b
        WHERE $whereClause
    ");
    $stmt->execute($params);
    $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Get bookings
    $params[] = $limit;
    $params[] = $offset;
    
    $stmt = $pdo->prepare("
        SELECT 
            b.id, b.property_id, b.check_in, b.check_out,
            b.adults, b.children, b.rooms, b.total_price, b.status,
            b.payment_status, b.rejection_reason,
            b.created_at, b.updated_at,
            p.name as property_name, p.address as property_address,
            p.city as property_city, p.state as property_state,
            p.latitude, p.longitude,
            u.first_name as host_first_name, u.last_name as host_last_name,
            u.email as host_email, u.phone as host_phone, u.avatar as host_avatar
        FROM bookings b
        INNER JOIN properties p ON b.property_id = p.id
        INNER JOIN users u ON p.host_id = u.id
        WHERE $whereClause
        ORDER BY b.created_at DESC 
        LIMIT ? OFFSET ?
    ");
    $stmt->execute($params);
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format bookings and get images
    foreach ($bookings as &$booking) {
        $booking['total_price'] = (float)$booking['total_price'];
        $booking['formatted_price'] = '₦' . number_format($booking['total_price'], 2);
        
        // Calculate number of nights
        $checkIn = new DateTime($booking['check_in']);
        $checkOut = new DateTime($booking['check_out']);
        $nights = $checkIn->diff($checkOut)->days;
        $booking['nights'] = $nights;
        
        // Get property images
        $stmt = $pdo->prepare("SELECT image_url FROM property_images WHERE property_id = ? ORDER BY display_order ASC LIMIT 4");
        $stmt->execute([$booking['property_id']]);
        $images = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $booking['property_images'] = $images;
        $booking['property_main_image'] = $images[0] ?? null;
        
        // Host info
        $booking['host'] = [
            'first_name' => $booking['host_first_name'],
            'last_name' => $booking['host_last_name'],
            'full_name' => trim($booking['host_first_name'] . ' ' . $booking['host_last_name']),
            'email' => $booking['host_email'],
            'phone' => $booking['host_phone'],
            'avatar' => $booking['host_avatar']
        ];
        
        // Property info
        $booking['property'] = [
            'id' => $booking['property_id'],
            'name' => $booking['property_name'],
            'address' => $booking['property_address'],
            'city' => $booking['property_city'],
            'state' => $booking['property_state'],
            'latitude' => $booking['latitude'],
            'longitude' => $booking['longitude']
        ];
        
        // Remove redundant fields
        unset($booking['host_first_name'], $booking['host_last_name'], 
              $booking['host_email'], $booking['host_phone'], $booking['host_avatar'],
              $booking['property_name'], $booking['property_address'], 
              $booking['property_city'], $booking['property_state'],
              $booking['latitude'], $booking['longitude']);
    }

    $totalPages = ceil($total / $limit);

    send_success('Bookings retrieved successfully.', [
        'bookings' => $bookings,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total_items' => (int)$total,
            'items_per_page' => $limit
        ]
    ]);

} catch (Exception $e) {
    error_log("Get guest bookings error for user ID $userId: " . $e->getMessage());
    send_error('Failed to retrieve bookings.', [], 500);
}
