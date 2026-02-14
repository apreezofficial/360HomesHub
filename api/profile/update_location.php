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

$input = json_decode(file_get_contents('php://input'), true);

$address = trim($input['address'] ?? '');
$city = trim($input['city'] ?? '');
$state = trim($input['state'] ?? '');
$country = trim($input['country'] ?? '');
$latitude = $input['latitude'] ?? null;
$longitude = $input['longitude'] ?? null;

if (empty($address) || empty($city) || empty($state) || empty($country)) {
    send_error('Address, city, state, and country are required.', [], 400);
}

$pdo = Database::getInstance();

try {
    // Update location
    $stmt = $pdo->prepare("UPDATE users SET address = ?, city = ?, state = ?, country = ?, latitude = ?, longitude = ? WHERE id = ?");
    $stmt->execute([$address, $city, $state, $country, $latitude, $longitude, $userId]);

    send_success('Location updated successfully.', [
        'location' => [
            'address' => $address,
            'city' => $city,
            'state' => $state,
            'country' => $country,
            'latitude' => $latitude,
            'longitude' => $longitude
        ]
    ]);

} catch (Exception $e) {
    error_log("Update location error for user ID $userId: " . $e->getMessage());
    send_error('Failed to update location.', [], 500);
}
