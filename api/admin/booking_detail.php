<?php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../utils/db.php';
require_once __DIR__ . '/../../utils/response.php';
require_once __DIR__ . '/../../utils/jwt.php';

// Authenticate admin
$userData = JWTManager::authenticate();
if ($userData['role'] !== 'admin') {
    send_error('Access denied. Admin only.', [], 403);
}

$bookingId = $_GET['id'] ?? null;
if (!$bookingId) {
    send_error('Booking ID required.', [], 400);
}

try {
    $pdo = Database::getInstance();
    
    // Fetch booking with all details
    $stmt = $pdo->prepare("
        SELECT b.*, 
               u.first_name as guest_first_name, u.last_name as guest_last_name, 
               u.email as guest_email, u.phone as guest_phone,
               p.name as property_name, p.address, p.city, p.state, p.country,
               h.first_name as host_first_name, h.last_name as host_last_name, h.email as host_email
        FROM bookings b
        JOIN users u ON b.guest_id = u.id
        JOIN properties p ON b.property_id = p.id
        JOIN users h ON b.host_id = h.id
        WHERE b.id = ?
    ");
    $stmt->execute([$bookingId]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$booking) {
        send_error('Booking not found.', [], 404);
    }

    send_success('Booking details retrieved successfully.', ['booking' => $booking]);

} catch (Exception $e) {
    error_log("Admin booking detail error: " . $e->getMessage());
    send_error('Failed to retrieve booking details.', [], 500);
}
