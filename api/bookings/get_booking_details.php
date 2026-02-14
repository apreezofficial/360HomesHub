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

$bookingId = isset($_GET['id']) ? intval($_GET['id']) : null;

if (!$bookingId) {
    send_error('Booking ID is required.', [], 400);
}

$pdo = Database::getInstance();

try {
    // Get booking details
    $stmt = $pdo->prepare("
        SELECT 
            b.*,
            p.name as property_name, p.address as property_address,
            p.city as property_city, p.state as property_state, p.country as property_country,
            p.latitude, p.longitude, p.type as property_type,
            p.bedrooms, p.bathrooms, p.beds, p.guests_max,
            u.first_name as host_first_name, u.last_name as host_last_name,
            u.email as host_email, u.phone as host_phone, u.avatar as host_avatar
        FROM bookings b
        INNER JOIN properties p ON b.property_id = p.id
        INNER JOIN users u ON p.host_id = u.id
        WHERE b.id = ? AND b.user_id = ?
    ");
    $stmt->execute([$bookingId, $userId]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$booking) {
        send_error('Booking not found.', [], 404);
    }

    // Format booking
    $booking['total_price'] = (float)$booking['total_price'];
    $booking['formatted_price'] = '₦' . number_format($booking['total_price'], 2);
    
    // Calculate number of nights
    $checkIn = new DateTime($booking['check_in']);
    $checkOut = new DateTime($booking['check_out']);
    $nights = $checkIn->diff($checkOut)->days;
    $booking['nights'] = $nights;
    
    // Get property images
    $stmt = $pdo->prepare("SELECT image_url FROM property_images WHERE property_id = ? ORDER BY display_order ASC");
    $stmt->execute([$booking['property_id']]);
    $images = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Get property amenities
    $stmt = $pdo->prepare("
        SELECT a.name, a.icon 
        FROM property_amenities pa
        INNER JOIN amenities a ON pa.amenity_id = a.id
        WHERE pa.property_id = ?
    ");
    $stmt->execute([$booking['property_id']]);
    $amenities = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Host info
    $host = [
        'first_name' => $booking['host_first_name'],
        'last_name' => $booking['host_last_name'],
        'full_name' => trim($booking['host_first_name'] . ' ' . $booking['host_last_name']),
        'email' => $booking['host_email'],
        'phone' => $booking['host_phone'],
        'avatar' => $booking['host_avatar']
    ];
    
    // Property info
    $property = [
        'id' => $booking['property_id'],
        'name' => $booking['property_name'],
        'address' => $booking['property_address'],
        'city' => $booking['property_city'],
        'state' => $booking['property_state'],
        'country' => $booking['property_country'],
        'latitude' => $booking['latitude'],
        'longitude' => $booking['longitude'],
        'type' => $booking['property_type'],
        'bedrooms' => $booking['bedrooms'],
        'bathrooms' => $booking['bathrooms'],
        'beds' => $booking['beds'],
        'guests_max' => $booking['guests_max'],
        'images' => $images,
        'amenities' => $amenities
    ];
    
    // Price breakdown
    $pricePerNight = $booking['total_price'] / max(1, $nights);
    $serviceFee = $booking['total_price'] * 0.10;
    $cautionFee = 5000;
    $subtotal = $pricePerNight * $nights;
    
    $priceBreakdown = [
        'price_per_night' => round($pricePerNight, 2),
        'nights' => $nights,
        'subtotal' => round($subtotal, 2),
        'service_fee' => round($serviceFee, 2),
        'caution_fee' => $cautionFee,
        'total' => (float)$booking['total_price'],
        'formatted' => [
            'price_per_night' => '₦' . number_format($pricePerNight, 2),
            'subtotal' => '₦' . number_format($subtotal, 2),
            'service_fee' => '₦' . number_format($serviceFee, 2),
            'caution_fee' => '₦' . number_format($cautionFee, 2),
            'total' => '₦' . number_format($booking['total_price'], 2)
        ]
    ];
    
    // Clean up booking object
    $cleanBooking = [
        'id' => $booking['id'],
        'check_in' => $booking['check_in'],
        'check_out' => $booking['check_out'],
        'adults' => $booking['adults'],
        'children' => $booking['children'],
        'rooms' => $booking['rooms'],
        'total_price' => $booking['total_price'],
        'formatted_price' => $booking['formatted_price'],
        'status' => $booking['status'],
        'payment_status' => $booking['payment_status'],
        'rejection_reason' => $booking['rejection_reason'],
        'nights' => $nights,
        'created_at' => $booking['created_at'],
        'updated_at' => $booking['updated_at'],
        'property' => $property,
        'host' => $host,
        'price_breakdown' => $priceBreakdown
    ];

    send_success('Booking details retrieved successfully.', [
        'booking' => $cleanBooking
    ]);

} catch (Exception $e) {
    error_log("Get booking details error for user ID $userId: " . $e->getMessage());
    send_error('Failed to retrieve booking details.', [], 500);
}
