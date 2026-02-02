<?php

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../config/env.php';
require_once __DIR__ . '/../../../utils/db.php';
require_once __DIR__ . '/../../../utils/response.php';
require_once __DIR__ . '/../../../utils/jwt.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_error('Invalid request method.', [], 405);
}

// Authenticate user
$userData = JWTManager::authenticate();
$userId = $userData['user_id'] ?? null;

if (!$userId) {
    send_error('Authentication failed.', [], 401);
}

// Get input data
$data = json_decode(file_get_contents('php://input'), true);

$country = trim($data['country'] ?? '');
$address = trim($data['address'] ?? '');
$apartment_suite = trim($data['apartment_suite'] ?? '');
$city = trim($data['city'] ?? '');
$state = trim($data['state'] ?? '');
$zip_code = trim($data['zip_code'] ?? '');
$latitude = $data['latitude'] ?? null;
$longitude = $data['longitude'] ?? null;

// Basic validation for Step 1
if (empty($country) || empty($address) || empty($city) || empty($state) || empty($zip_code)) {
    send_error('Country, address, city, state, and postal code are required.', [], 400);
}

try {
    $pdo = Database::getInstance();

    // Check if the user is a host
    $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $role = $stmt->fetchColumn();

    if ($role !== 'host') {
        send_error('Only hosts can create listings.', [], 403);
    }

    // Insert property initiation record
    $stmt = $pdo->prepare("
        INSERT INTO properties (
            host_id, name, address, apartment_suite, city, state, zip_code, country, 
            latitude, longitude, onboarding_step, status, 
            type, space_type, guests_max, bedrooms, bathrooms, beds, price
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, 'draft', 'apartment', 'whole', 1, 1, 1, 1, 0)
    ");

    $stmt->execute([
        $userId,
        'Draft Listing', // Placeholder name
        $address,
        $apartment_suite,
        $city,
        $state,
        $zip_code,
        $country,
        $latitude,
        $longitude
    ]);

    $propertyId = $pdo->lastInsertId();

    send_success('Property listing initiated successfully.', [
        'property_id' => (int)$propertyId,
        'next_step' => 2
    ]);

} catch (Exception $e) {
    error_log("Init listing error: " . $e->getMessage());
    send_error('An error occurred while initiating property listing.', [], 500);
}
