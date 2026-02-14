<?php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../utils/db.php';
require_once __DIR__ . '/../../utils/response.php';
require_once __DIR__ . '/../../utils/jwt.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
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
    send_error('Only hosts can approve bookings.', [], 403);
}

$input = json_decode(file_get_contents('php://input'), true);

$bookingId = isset($input['booking_id']) ? intval($input['booking_id']) : null;

if (!$bookingId) {
    send_error('Booking ID is required.', [], 400);
}

$pdo = Database::getInstance();

try {
    $pdo->beginTransaction();

    // Get booking and verify it belongs to host's property
    $stmt = $pdo->prepare("
        SELECT b.*, p.host_id, p.name as property_name, u.first_name, u.last_name, u.email
        FROM bookings b
        INNER JOIN properties p ON b.property_id = p.id
        INNER JOIN users u ON b.user_id = u.id
        WHERE b.id = ? AND p.host_id = ?
    ");
    $stmt->execute([$bookingId, $userId]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$booking) {
        $pdo->rollBack();
        send_error('Booking not found or you do not have permission to approve it.', [], 404);
    }

    if ($booking['status'] !== 'pending') {
        $pdo->rollBack();
        send_error('Only pending bookings can be approved.', [], 400);
    }

    // Update booking status to 'approved' (awaiting guest payment)
    $stmt = $pdo->prepare("UPDATE bookings SET status = 'approved', updated_at = NOW() WHERE id = ?");
    $stmt->execute([$bookingId]);

    // Notify Guest (Important)
    require_once __DIR__ . '/../../api/notifications/notify.php';
    $guestId = $booking['user_id'];
    $propertyName = $booking['property_name'];
    $checkIn = $booking['check_in'];
    $checkOut = $booking['check_out'];
    
    sendNotification(
        $guestId, 
        "Booking Approved!", 
        "Your booking request for '{$propertyName}' ({$checkIn} to {$checkOut}) has been approved. Please proceed to payment to confirm your reservation.", 
        'important'
    );

    $pdo->commit();

    send_success('Booking approved successfully. Guest has been notified to proceed with payment.', [
        'booking_id' => $bookingId,
        'status' => 'approved'
    ]);

} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Approve booking error for user ID $userId: " . $e->getMessage());
    send_error('Failed to approve booking.', [], 500);
}
