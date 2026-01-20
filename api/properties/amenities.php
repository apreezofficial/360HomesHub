<?php
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../utils/db.php'; // Assuming db.php handles DB connection
require_once __DIR__ . '/../../utils/response.php'; // Assuming response.php handles JSON responses
require_once __DIR__ . '/../../utils/jwt.php'; // Assuming jwt.php handles JWT validation

header("Content-Type: application/json");

// JWT Authentication check (assuming a function verify_jwt exists)
// if (!verify_jwt()) {
//     send_json_response(401, ["message" => "Unauthorized."]);
//     exit;
// }

try {
    $db = get_db_connection(); // Get database connection

    // Fetch all amenities from the database
    $stmt = $db->query("SELECT id, name FROM amenities ORDER BY name ASC");
    $amenities = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Return the list of amenities
    send_json_response(200, ["amenities" => $amenities]);

} catch (PDOException $e) {
    // Log the error securely
    error_log("Database error fetching amenities: " . $e->getMessage());
    send_json_response(500, ["message" => "Internal server error. Could not retrieve amenities."]);
} catch (Exception $e) {
    // Log other general errors
    error_log("General error fetching amenities: " . $e->getMessage());
    send_json_response(500, ["message" => "An unexpected error occurred."]);
}
?>

/*
 * Example Request JSON:
 * (No input needed for this endpoint as it fetches all amenities)
 */

/*
 * Example Response JSON (Success):
 * {
 *   "amenities": [
 *     {"id": 1, "name": "WiFi"},
 *     {"id": 2, "name": "Air Conditioning"},
 *     {"id": 3, "name": "Parking"}
 *   ]
 * }
 */

/*
 * Example Response JSON (Error):
 * {
 *   "message": "Internal server error. Could not retrieve amenities."
 * }
 */