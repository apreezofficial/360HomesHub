<?php
// /api/messages/unread_count.php

// --- Example Request ---
// No body required, uses JWT for authentication.
// --- Example Response ---
// {
//   "status": "success",
//   "unread_count": 5
// }

header('Content-Type: application/json');

require_once '../../vendor/autoload.php';
require_once '../../utils/db.php';
require_once '../../utils/jwt.php';
require_once '../../utils/response.php';

try {
    // Authenticate user
    $jwt = get_jwt_from_header();
    if (!$jwt) {
        send_response('error', 'Authentication token not provided.');
    }

    $decoded = validate_jwt($jwt);
    if (!$decoded) {
        send_response('error', 'Invalid or expired token.');
    }
    $user_id = $decoded->data->user_id;

    // Get DB connection
    $pdo = get_db_connection();

    // Query for unread messages count
    $stmt = $pdo->prepare("SELECT COUNT(*) as unread_count FROM messages WHERE receiver_id = ? AND is_read = 0");
    $stmt->execute([$user_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    $unread_count = $result ? (int) $result['unread_count'] : 0;

    send_response('success', null, ['unread_count' => $unread_count]);

} catch (Exception $e) {
    send_response('error', $e->getMessage());
}

?>
