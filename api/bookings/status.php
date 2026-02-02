<?php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../utils/db.php'; // Database connection
require_once __DIR__ . '/../../utils/response.php'; // JSON response handler
require_once __DIR__ . '/../../utils/jwt.php'; // JWT authentication

header("Content-Type: application/json");

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

    // Fetch booking details including guest_id and host_id
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
    // Ensure the logged-in user is either the guest or the host of this booking
    $is_guest = (int)$booking['guest_id'] === (int)$user_id;
    $is_host = (int)$booking['host_id'] === (int)$user_id;

    if (!$is_guest && !$is_host) {
        send_error("Forbidden. You are not associated with this booking.", [], 403);
    }

    // --- Prepare Response Data ---
    // Return essential booking details along with its status
    $response_data = [
        'booking_id' => (int)$booking['id'],
        'property_name' => $booking['property_name'],
        'check_in' => $booking['check_in'],
        'check_out' => $booking['check_out'],
        'status' => $booking['status'],
        'guest_id' => (int)$booking['guest_id'],
        'host_id' => (int)$booking['host_id'],
        'message' => 'Booking status retrieved successfully.'
    ];

    send_success("Booking status retrieved successfully.", $response_data);

} catch (PDOException $e) {
    error_log("Database error fetching status for booking {$booking_id}: " . $e->getMessage());
    send_error("Database error. Could not retrieve booking status.", [], 500);
} catch (Exception $e) {
    error_log("General error fetching status for booking {$booking_id}: " . $e->getMessage());
    send_error("An unexpected error occurred while retrieving booking status.", [], 500);
}
?>

/*
 * Example Request JSON:
 * {
 *   "booking_id": 5
 * }
 */

/*
 * Example Response JSON (Success - 200 OK - as Guest):
 * {
 *   "booking_id": 5,
 *   "property_name": "Cozy Apartment Downtown",
 *   "check_in": "2026-01-25",
 *   "check_out": "2026-01-28",
 *   "status": "approved", // e.g., 'pending', 'approved', 'rejected', 'paid'
 *   "guest_id": 101,
 *   "host_id": 5,
 *   "message": "Booking status retrieved successfully."
 * }
 */

/*
 * Example Response JSON (Success - 200 OK - as Host):
 * {
 *   "booking_id": 5,
 *   "property_name": "Cozy Apartment Downtown",
 *   "check_in": "2026-01-25",
 *   "check_out": "2026-01-28",
 *   "status": "paid",
 *   "guest_id": 101,
 *   "host_id": 5,
 *   "message": "Booking status retrieved successfully."
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
 *   "message": "Forbidden. You are not associated with this booking."
 * }
 */

/*
 * Example Response JSON (Error - Booking Not Found):
 * {
 *   "message": "Booking not found."
 * }
 */
