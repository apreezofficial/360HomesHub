<?php
// Suppress PHP errors/warnings in output
error_reporting(0);
ini_set('display_errors', 0);

// Always return JSON
header('Content-Type: application/json');

// ...require your dependencies...

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        "success" => false,
        "message" => "Invalid request method."
    ]);
    exit;
}

// Authenticate user via JWT if needed
// $userData = JWTManager::authenticate();
// $userId = $userData['user_id'] ?? null;

// Get raw POST data
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "Invalid JSON input"
    ]);
    exit;
}

// Validate required fields
if (!isset($data['property_id']) || !isset($data['start_date']) || !isset($data['end_date'])) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "Missing required fields"
    ]);
    exit;
}

// ...existing code for price calculation...

// Example response
echo json_encode([
    "success" => true,
    "price" => 12345 // replace with actual calculation
]);
exit;
