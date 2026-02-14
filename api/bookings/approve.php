<?php

require_once __DIR__ . '/../config.php'; // CORS and common API setup
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../utils/db.php'; // Database connection
require_once __DIR__ . '/../../utils/response.php'; // JSON response handler
require_once __DIR__ . '/../../utils/jwt.php'; // JWT authentication
require_once __DIR__ . '/../../api/notifications/notify.php'; // Notification helper
require_once __DIR__ . '/../../utils/activity_logger.php'; // Activity logger


// --- JWT Authentication ---
$userData = JWTManager::authenticate();
$user_id = $userData['user_id'] ?? null; // This is the logged-in user's ID

if (!$user_id) {
    send_error("Unauthorized. Invalid or missing token.", [], 401);
}

// --- Input Validation ---
$input = json_decode(file_get_contents('php://input'), true);
$booking_id = $input['booking_id'] ?? null;

if (!$booking_id) {
    send_error("Missing required field: booking_id.");
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
        ActivityLogger::log(
            $user_id,
            'booking_approve_failed',
            "Failed to approve booking - Booking not found",
            'booking',
            $booking_id,
            ['reason' => 'booking_not_found']
        );
        send_error("Booking not found.", [], 404);
    }

    // --- Authorization Check ---
    // Ensure the logged-in user is the host of this booking
    if ((int)$booking['host_id'] !== (int)$user_id) {
        ActivityLogger::log(
            $user_id,
            'booking_approve_unauthorized',
            "Unauthorized booking approval attempt",
            'booking',
            $booking_id,
            ['actual_host_id' => $booking['host_id'], 'guest_id' => $booking['guest_id']]
        );
        send_error("Forbidden. You are not the host of this booking.", [], 403);
    }

    // Ensure booking is in a state that can be approved (e.g., pending)
    if ($booking['status'] !== 'pending') {
        send_error("Booking is not in a pending state. Current status: {$booking['status']}.", [], 409);
    }

    // --- Update Booking Status ---
    $sql = "UPDATE bookings SET status = 'approved' WHERE id = :booking_id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':booking_id', $booking_id, PDO::PARAM_INT);

    if ($stmt->execute()) {
        // Log successful approval
        ActivityLogger::logBooking(
            $user_id,
            'approved',
            $booking_id,
            [
                'guest_id' => $booking['guest_id'],
                'property_name' => $booking['property_name'],
                'check_in' => $booking['check_in'],
                'check_out' => $booking['check_out']
            ]
        );

        // --- Send Notifications ---
        $guest_id = (int)$booking['guest_id'];
        $property_name = $booking['property_name'];
        $check_in_date_str = $booking['check_in'];
        $check_out_date_str = $booking['check_out'];

        // Notify Guest (Important)
        $guest_notification_title = "Booking Approved!";
        $guest_notification_message = "Your booking request for '{$property_name}' from {$check_in_date_str} to {$check_out_date_str} has been approved by the host. You can now proceed to payment.";
        sendNotification($guest_id, $guest_notification_title, $guest_notification_message, 'important');

        // Notify Admin (Important) - Optional, but good practice
        // Assuming admin user ID is 1
        $admin_user_id = 1; // This might need to be dynamic or configurable
        sendNotification($admin_user_id, "Booking Approved", "Booking ID {$booking_id} for property {$booking['property_id']} has been approved by host {$user_id}. Guest ID: {$guest_id}.", 'important');


        // Prepare response data
        $updated_booking_data = [
            'id' => (int)$booking_id,
            'status' => 'approved',
            'message' => 'Booking approved successfully.'
        ];

        send_success("Booking approved successfully.", $updated_booking_data);
    } else {
        ActivityLogger::log(
            $user_id,
            'booking_approve_failed',
            "Failed to update booking status to approved",
            'booking',
            $booking_id
        );
        send_error("Failed to update booking status.", [], 500);
    }

} catch (PDOException $e) {
    error_log("Database error approving booking {$booking_id}: " . $e->getMessage());
    ActivityLogger::log(
        $user_id ?? null,
        'booking_approve_db_error',
        "Database error during booking approval",
        'booking',
        $booking_id ?? null,
        ['error' => $e->getMessage()]
    );
    send_error("Database error. Could not approve booking.", [], 500);
} catch (Exception $e) {
    error_log("General error approving booking {$booking_id}: " . $e->getMessage());
    ActivityLogger::log(
        $user_id ?? null,
        'booking_approve_error',
        "Error during booking approval",
        'booking',
        $booking_id ?? null,
        ['error' => $e->getMessage()]
    );
    send_error("An unexpected error occurred during booking approval.", [], 500);
}
