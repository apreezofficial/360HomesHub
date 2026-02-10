<?php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../utils/db.php';
require_once __DIR__ . '/../../utils/response.php';
require_once __DIR__ . '/../../utils/jwt.php';

// Authenticate user (admin or guest)
$userData = JWTManager::authenticate();
$userId = $userData['user_id'] ?? null;

if (!$userId) {
    send_error('Unauthorized.', [], 401);
}

$input = json_decode(file_get_contents('php://input'), true);
$bookingId = $input['booking_id'] ?? null;

if (!$bookingId) {
    send_error('Booking ID required.', [], 400);
}

try {
    $pdo = Database::getInstance();
    
    // Fetch booking
    $stmt = $pdo->prepare("SELECT * FROM bookings WHERE id = ?");
    $stmt->execute([$bookingId]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$booking) {
        send_error('Booking not found.', [], 404);
    }

    // Check authorization (admin or booking owner)
    if ($userData['role'] !== 'admin' && $booking['guest_id'] != $userId) {
        send_error('Access denied.', [], 403);
    }

    // Generate unique payment reference
    $paymentRef = 'PAY-' . strtoupper(uniqid()) . '-' . $bookingId;
    
    // Generate payment link (using base64 encoded reference)
    $paymentLink = 'http://localhost/360homeshub/checkout.php?ref=' . base64_encode($paymentRef);

    // Update booking with payment link and reference
    $updateStmt = $pdo->prepare("
        UPDATE bookings 
        SET payment_link = ?, payment_ref = ?, status = 'awaiting_payment'
        WHERE id = ?
    ");
    $updateStmt->execute([$paymentLink, $paymentRef, $bookingId]);

    send_success('Payment link generated successfully.', [
        'payment_link' => $paymentLink,
        'payment_ref' => $paymentRef,
        'amount' => $booking['total_amount']
    ]);

} catch (Exception $e) {
    error_log("Generate payment link error: " . $e->getMessage());
    send_error('Failed to generate payment link.', [], 500);
}
