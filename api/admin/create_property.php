<?php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../utils/db.php';
require_once __DIR__ . '/../../utils/response.php';
require_once __DIR__ . '/../../utils/jwt.php';

// Authenticate admin
$userData = JWTManager::authenticate();
if ($userData['role'] !== 'admin') {
    send_error('Access denied. Admin only.', [], 403);
}

$input = json_decode(file_get_contents('php://input'), true);

$hostId = $input['host_id'] ?? null;
$name = $input['name'] ?? 'Untitled Property';
$type = $input['type'] ?? 'apartment';
$address = $input['address'] ?? '';
$city = $input['city'] ?? '';
$state = $input['state'] ?? '';
$country = $input['country'] ?? 'Nigeria';
$price = $input['price'] ?? 0;
$guestsMax = $input['guests_max'] ?? 1;
$bedrooms = $input['bedrooms'] ?? 1;
$description = $input['description'] ?? '';

if (!$hostId || empty($address)) {
    send_error('Host ID and Address are required.', [], 400);
}

try {
    $pdo = Database::getInstance();
    
    // Check if host exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ? AND role = 'host'");
    $stmt->execute([$hostId]);
    if (!$stmt->fetch()) {
        send_error('Valid host not found for the given ID.', [], 404);
    }

    $stmt = $pdo->prepare("
        INSERT INTO properties (
            host_id, name, description, type, price, address, city, state, country, 
            guests_max, bedrooms, status, onboarding_step, latitude, longitude
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'published', 6, 6.5244, 3.3792)
    ");
    
    $stmt->execute([
        $hostId, $name, $description, $type, $price, $address, $city, $state, $country, 
        $guestsMax, $bedrooms
    ]);
    
    $propertyId = $pdo->lastInsertId();

    send_success('Property created and published for host.', ['property_id' => $propertyId]);

} catch (Exception $e) {
    error_log("Admin create property error: " . $e->getMessage());
    send_error('Failed to create property: ' . $e->getMessage(), [], 500);
}
