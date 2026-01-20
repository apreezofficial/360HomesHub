<?php
require_once __DIR__ . '/../../utils/response.php'; // Assuming response.php handles JSON responses
require_once __DIR__ . '/../../utils/jwt.php'; // Assuming jwt.php handles JWT validation

header("Content-Type: application/json");

// JWT Authentication check (assuming a function verify_jwt exists)
// if (!verify_jwt()) {
//     send_json_response(401, ["message" => "Unauthorized."]);
//     exit;
// }

// Static list of house rules as no database table was defined for rules.
// This can be extended or moved to a configuration file/database later if needed.
$house_rules = [
    "No smoking inside the property.",
    "Pets are not allowed.",
    "Please keep noise levels down after 10 PM.",
    "Guests are responsible for any damages caused.",
    "Check-out time is 11 AM."
];

// Return the list of house rules
send_json_response(200, ["house_rules" => $house_rules]);

?>

/*
 * Example Request JSON:
 * (No input needed for this endpoint as it returns a static list of rules)
 */

/*
 * Example Response JSON (Success):
 * {
 *   "house_rules": [
 *     "No smoking inside the property.",
 *     "Pets are not allowed.",
 *     "Please keep noise levels down after 10 PM.",
 *     "Guests are responsible for any damages caused.",
 *     "Check-out time is 11 AM."
 *   ]
 * }
 */

/*
 * Example Response JSON (Error):
 * (This endpoint is unlikely to error unless response.php is missing, but for completeness)
 * {
 *   "message": "An unexpected error occurred."
 * }
 */