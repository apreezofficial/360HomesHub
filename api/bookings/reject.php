<?php

require_once __DIR__ . '/../config.php'; // CORS and common API setup
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../utils/db.php'; // Database connection
require_once __DIR__ . '/../../utils/response.php'; // JSON response handler
require_once __DIR__ . '/../../utils/jwt.php'; // JWT authentication
require_once __DIR__ . '/../../api/notifications/notify.php'; // Notification helper

// --- JWT Authentication ---
$userData = JWTManager::authenticate();
$user_id = $userData['user_id'] ?? null;

if (!$user_id) {
    send_error("Unauthorized. Invalid or missing token.", [], 401);
}

// --- Input Validation ---
$input = json_decode(file_get_contents('php://input'), true);
$booking_id = $input['booking_id'] ?? null;
$rejection_reason = $input['rejection_reason'] ?? null;

if (!$booking_id) {
    send_error("Missing required field: booking_id.");
}
if (!$rejection_reason) {
    send_error("Missing required field: rejection_reason.");
}

// --- Database Operations ---
try {
    $pdo = get_db_connection(); // Get database connection

    // Fetch booking details to verify ownership and current status
    $stmt = $pdo->prepare("
        SELECT b.id, b.status, b.guest_id, b.host_id, p.name as property_name, b.check_in, b.check_out
        FROM bookings b
        JOIN properties p ON b.property_id = p.id
        WHERE b.id = :booking_id
    ");
    $stmt->bindParam(':booking_id', $booking_id, PDO::PARAM_INT);
    $stmt->execute();
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$booking) {
        send_error("Booking not found.", [], 404);
    }

    // --- Authorization Check ---
    if ((int)$booking['host_id'] !== (int)$user_id) {
        send_error("Forbidden. You are not the host of this booking.", [], 403);
    }

    // Ensure booking is in a state that can be rejected (e.g., pending)
    if ($booking['status'] !== 'pending') {
        send_error("Booking is not in a pending state. Current status: " . $booking['status'], [], 409);
    }

    // Sanitize rejection reason
    $sanitized_rejection_reason = htmlspecialchars(trim($rejection_reason), ENT_QUOTES, 'UTF-8');
    if (empty($sanitized_rejection_reason)) {
        send_error("Rejection reason cannot be empty or contain only invalid characters.");
    }

    // --- Update Booking Status ---
    $sql = "UPDATE bookings SET status = 'rejected', rejection_reason = :rejection_reason WHERE id = :booking_id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':booking_id', $booking_id, PDO::PARAM_INT);
    $stmt->bindParam(':rejection_reason', $sanitized_rejection_reason, PDO::PARAM_STR);

    if ($stmt->execute()) {
        // --- Send Notifications ---
        $guest_id = (int)$booking['guest_id'];
        $property_name = $booking['property_name'];
        $check_in_date_str = $booking['check_in'];
        $check_out_date_str = $booking['check_out'];

        sendNotification($guest_id, "Booking Rejected", "Your booking request for '" . $property_name . "' from " . $check_in_date_str . " to " . $check_out_date_str . " has been rejected by the host. Reason: " . $sanitized_rejection_reason, 'important');

        $admin_user_id = 1;
        sendNotification($admin_user_id, "Booking Rejected", "Booking ID " . $booking_id . " has been rejected by host " . $user_id . ". Reason: " . $sanitized_rejection_reason, 'important');

        // Prepare response data
        $updated_booking_data = [
            'id' => (int)$booking_id,
            'status' => 'rejected',
            'rejection_reason' => $sanitized_rejection_reason,
            'message' => 'Booking rejected successfully.'
        ];

        send_success("Booking rejected successfully.", $updated_booking_data);
    } else {
        send_error("Failed to update booking status.", [], 500);
    }

} catch (PDOException $e) {
    error_log("Database error rejecting booking " . $booking_id . ": " . $e->getMessage());
    send_error("Database error. Could not reject booking.", [], 500);
} catch (Exception $e) {
    error_log("General error rejecting booking " . $booking_id . ": " . $e->getMessage());
    send_error("An unexpected error occurred during booking rejection.", [], 500);
}
