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

// Check if user is a host
if (($userData['role'] ?? '') !== 'host') {
    send_error('Only hosts can access bookings.', [], 403);
}

$pdo = Database::getInstance();

// Get filter parameters
$status = $_GET['status'] ?? null; // 'pending', 'confirmed', 'cancelled', 'completed'
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = isset($_GET['limit']) ? min(50, max(1, intval($_GET['limit']))) : 20;
$offset = ($page - 1) * $limit;

try {
    // Build query - get bookings for properties owned by this host
    $where = ["p.host_id = ?"];
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
        INNER JOIN properties p ON b.property_id = p.id
        WHERE $whereClause
    ");
    $stmt->execute($params);
    $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Get bookings
    $params[] = $limit;
    $params[] = $offset;
    
    $stmt = $pdo->prepare("
        SELECT 
            b.id, b.property_id, b.user_id as guest_id, b.check_in, b.check_out,
            b.adults, b.children, b.rooms, b.total_price, b.status,
            b.created_at, b.updated_at,
            p.name as property_name, p.address as property_address,
            p.city as property_city,
            u.first_name as guest_first_name, u.last_name as guest_last_name,
            u.email as guest_email, u.phone as guest_phone, u.avatar as guest_avatar
        FROM bookings b
        INNER JOIN properties p ON b.property_id = p.id
        INNER JOIN users u ON b.user_id = u.id
        WHERE $whereClause
        ORDER BY b.created_at DESC 
        LIMIT ? OFFSET ?
    ");
    $stmt->execute($params);
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format bookings
    foreach ($bookings as &$booking) {
        $booking['total_price'] = (float)$booking['total_price'];
        $booking['formatted_price'] = '₦' . number_format($booking['total_price'], 2);
        
        // Calculate number of nights
        $checkIn = new DateTime($booking['check_in']);
        $checkOut = new DateTime($booking['check_out']);
        $nights = $checkIn->diff($checkOut)->days;
        $booking['nights'] = $nights;
        
        // Guest info
        $booking['guest'] = [
            'id' => $booking['guest_id'],
            'first_name' => $booking['guest_first_name'],
            'last_name' => $booking['guest_last_name'],
            'full_name' => trim($booking['guest_first_name'] . ' ' . $booking['guest_last_name']),
            'email' => $booking['guest_email'],
            'phone' => $booking['guest_phone'],
            'avatar' => $booking['guest_avatar']
        ];
        
        // Remove redundant fields
        unset($booking['guest_id'], $booking['guest_first_name'], $booking['guest_last_name'], 
              $booking['guest_email'], $booking['guest_phone'], $booking['guest_avatar']);
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
    error_log("Get host bookings error for user ID $userId: " . $e->getMessage());
    send_error('Failed to retrieve bookings.', [], 500);
}
