<?php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../utils/db.php'; // Database connection
require_once __DIR__ . '/../../utils/response.php'; // JSON response handler
require_once __DIR__ . '/../../utils/jwt.php'; // JWT authentication
require_once __DIR__ . '/../../api/notifications/notify.php'; // Notification helper

header("Content-Type: application/json");

// --- JWT Authentication ---
$userData = JWTManager::authenticate();
$user_id = $userData['user_id'] ?? null; // This is the logged-in user's ID

if (!$user_id) {
    send_json_response(401, ["message" => "Unauthorized. Invalid or missing token."]);
    exit;
}

// --- Input Validation ---
$input = json_decode(file_get_contents('php://input'), true);
$booking_id = $input['booking_id'] ?? null;
$rejection_reason = $input['rejection_reason'] ?? null;

if (!$booking_id) {
    send_json_response(400, ["message" => "Missing required field: booking_id."]);
    exit;
}
if (!$rejection_reason) {
    send_json_response(400, ["message" => "Missing required field: rejection_reason."]);
    exit;
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
        send_json_response(404, ["message" => "Booking not found."]);
        exit;
    }

    // --- Authorization Check ---
    // Ensure the logged-in user is the host of this booking
    if ((int)$booking['host_id'] !== (int)$user_id) {
        send_json_response(403, ["message" => "Forbidden. You are not the host of this booking."]);
        exit;
    }

    // Ensure booking is in a state that can be rejected (e.g., pending)
    if ($booking['status'] !== 'pending') {
        send_json_response(409, ["message" => "Booking is not in a pending state. Current status: {$booking['status']}."]);
        exit;
    }

    // Sanitize rejection reason
    $sanitized_rejection_reason = filter_var($rejection_reason, FILTER_SANITIZE_STRING);
    if (empty($sanitized_rejection_reason)) {
        send_json_response(400, ["message" => "Rejection reason cannot be empty or contain only invalid characters."]);
        exit;
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

        // Notify Guest (Important)
        $guest_notification_title = "Booking Rejected";
        $guest_notification_message = "Your booking request for '{$property_name}' from {$check_in_date_str} to {$check_out_date_str} has been rejected by the host. Reason: {$sanitized_rejection_reason}";
        sendNotification($guest_id, $guest_notification_title, $guest_notification_message, 'important');

        // Notify Admin (Important) - Optional
        // Assuming admin user ID is 1
        $admin_user_id = 1; // This might need to be dynamic or configurable
        sendNotification($admin_user_id, "Booking Rejected", "Booking ID {$booking_id} for property {$booking['property_id']} has been rejected by host {$user_id}. Guest ID: {$guest_id}. Reason: {$sanitized_rejection_reason}", 'important');

        // Prepare response data
        $updated_booking_data = [
            'id' => (int)$booking_id,
            'status' => 'rejected',
            'rejection_reason' => $sanitized_rejection_reason,
            'message' => 'Booking rejected successfully.'
        ];

        send_json_response(200, $updated_booking_data);
    } else {
        send_json_response(500, ["message" => "Failed to update booking status."]);
    }

} catch (PDOException $e) {
    error_log("Database error rejecting booking {$booking_id}: " . $e->getMessage());
    send_json_response(500, ["message" => "Database error. Could not reject booking."]);
} catch (Exception $e) {
    error_log("General error rejecting booking {$booking_id}: " . $e->getMessage());
    send_json_response(500, ["message" => "An unexpected error occurred during booking rejection."]);
}
?>

/*
 * Example Request JSON:
 * {
 *   "booking_id": 5,
 *   "rejection_reason": "Property is currently under maintenance."
 * }
 */

/*
 * Example Response JSON (Success - 200 OK):
 * {
 *   "id": 5,
 *   "status": "rejected",
 *   "rejection_reason": "Property is currently under maintenance.",
 *   "message": "Booking rejected successfully."
 * }
 */

/*
 * Example Response JSON (Error - Unauthorized):
 * {
 *   "message": "Unauthorized. Invalid or missing token."
 * }
 */

/*
 * Example Response JSON (Error - Forbidden):
 * {
 *   "message": "Forbidden. You are not the host of this booking."
 * }
 */

/*
 * Example Response JSON (Error - Booking Not Found):
 * {
 *   "message": "Booking not found."
 * }
 */

/*
 * Example Response JSON (Error - Missing Fields):
 * {
 *   "message": "Missing required field: booking_id."
 * }
 */

/*
 * Example Response JSON (Error - Already Processed):
 * {
 *   "message": "Booking is not in a pending state. Current status: paid."
 * }
 */