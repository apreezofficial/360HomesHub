<?php

require_once __DIR__ . '/../../utils/db.php'; // Database connection
require_once __DIR__ . '/../../utils/response.php'; // JSON response handler

/**
 * Centralized notification helper function.
 * Inserts a notification into the database.
 *
 * @param int $user_id The ID of the user to notify.
 * @param string $title The title of the notification.
 * @param string $message The message content of the notification.
 * @param string $level The notification level ('important', 'normal', 'low').
 * @return bool True on success, false on failure.
 */
function sendNotification(int $user_id, string $title, string $message, string $level): bool
{
    // Define valid notification levels
    $valid_levels = ['important', 'normal', 'low'];

    // Validate level
    if (!in_array($level, $valid_levels)) {
        error_log("Invalid notification level provided: {$level}");
        return false;
    }

    // Basic sanitization for title and message to prevent XSS if displayed directly elsewhere,
    // though generally database input should be handled with prepared statements.
    $sanitized_title = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
    $sanitized_message = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');

    try {
        $pdo = get_db_connection(); // Get database connection

        $sql = "
            INSERT INTO notifications (user_id, title, message, level, created_at)
            VALUES (:user_id, :title, :message, :level, CURRENT_TIMESTAMP)
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindParam(':title', $sanitized_title, PDO::PARAM_STR);
        $stmt->bindParam(':message', $sanitized_message, PDO::PARAM_STR);
        $stmt->bindParam(':level', $level, PDO::PARAM_STR);

        return $stmt->execute();

    } catch (PDOException $e) {
        error_log("Database error sending notification to user {$user_id}: " . $e->getMessage());
        return false;
    } catch (Exception $e) {
        error_log("General error sending notification to user {$user_id}: " . $e->getMessage());
        return false;
    }
}

// --- Example usage as an API endpoint (though primarily intended as a helper function) ---
// This part would typically be called from other API scripts, not directly accessed by users.
// If accessed directly, it would need its own authentication and input handling.
// For now, we'll include a basic structure but note it's mainly for internal use.

// Assume this script is included by an API endpoint that handles its own request/response.
// If this script were to be the direct entry point, it would need:
/*
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // This is a simplified example for demonstration; actual API endpoints
    // will handle their own input parsing and authentication.
    $input = json_decode(file_get_contents('php://input'), true);

    $user_id = $input['user_id'] ?? null;
    $title = $input['title'] ?? null;
    $message = $input['message'] ?? null;
    $level = $input['level'] ?? null;

    if (!$user_id || !$title || !$message || !$level) {
        send_json_response(400, ["message" => "Missing required fields: user_id, title, message, level."]);
    } else {
        if (sendNotification($user_id, $title, $message, $level)) {
            send_json_response(200, ["message" => "Notification sent successfully."]);
        } else {
            send_json_response(500, ["message" => "Failed to send notification."]);
        }
    }
} else {
    send_json_response(405, ["message" => "Invalid request method."]);
}
*/

// Placeholder for actual function definition. The actual API endpoints will call `sendNotification()` function.

?>

/*
 * This file provides a helper function `sendNotification` to abstract notification logic.
 * It is intended to be included by other API endpoints.
 *
 * Function Signature:
 * sendNotification(int $user_id, string $title, string $message, string $level): bool
 *
 * Parameters:
 * - $user_id: The ID of the recipient user.
 * - $title: The title of the notification.
 * - $message: The body of the notification.
 * - $level: The notification level ('important', 'normal', 'low').
 *
 * Returns:
 * - true if the notification was successfully sent (inserted into DB), false otherwise.
 */

/*
 * Example of how another API endpoint would use this function:
 *
 * require_once __DIR__ . '/../../api/notifications/notify.php'; // Include this file
 *
 * $recipient_user_id = 123;
 * $notification_title = "Booking Approved!";
 * $notification_message = "Your booking for property XYZ has been approved by the host.";
 * $notification_level = "important";
 *
 * if (sendNotification($recipient_user_id, $notification_title, $notification_message, $notification_level)) {
 *     // Notification sent successfully
 * } else {
 *     // Log error or handle failure
 * }
 */

/*
 * Example Request JSON (if this file were an API endpoint directly):
 * {
 *   "user_id": 101,
 *   "title": "New Message Received",
 *   "message": "You have a new message from the host.",
 *   "level": "normal" // 'important', 'normal', or 'low'
 * }
 */

/*
 * Example Response JSON (if this file were an API endpoint directly - Success):
 * {
 *   "message": "Notification sent successfully."
 * }
 */

/*
 * Example Response JSON (if this file were an API endpoint directly - Error):
 * {
 *   "message": "Missing required fields: user_id, title, message, level."
 * }
 */