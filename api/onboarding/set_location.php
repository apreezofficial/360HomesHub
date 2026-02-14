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

$pdo = Database::getInstance();

// Check user's current onboarding step
$stmt = $pdo->prepare("SELECT onboarding_step, email, phone, auth_provider, role, status, message_disabled, booking_disabled, first_name, last_name, avatar FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$userOnboarding = $user['onboarding_step'];

if ($userOnboarding !== 'location' && $userOnboarding !== 'avatar' && $userOnboarding !== 'role' && $userOnboarding !== 'kyc' && $userOnboarding !== 'completed') {
    send_error('Please complete previous onboarding steps. Current step: ' . $userOnboarding, ['onboarding_step' => $userOnboarding], 403);
}

$input = json_decode(file_get_contents('php://input'), true);

$address = trim($input['address'] ?? '');
$city = trim($input['city'] ?? '');
$state = trim($input['state'] ?? '');
$country = trim($input['country'] ?? '');

if (empty($address) || empty($city) || empty($state) || empty($country)) {
    send_error('Address, city, state, and country are required.', [], 400);
}

try {
    // Update user's location information
    $stmt = $pdo->prepare("UPDATE users SET address = ?, city = ?, state = ?, country = ?, onboarding_step = 'avatar' WHERE id = ?");
    $stmt->execute([$address, $city, $state, $country, $userId]);

    // Generate new JWT token with updated onboarding step
    // Generate new JWT token with updated onboarding step and location info
    $jwtData = [
        'user_id' => $userId,
        'email' => $user['email'],
        'phone' => $user['phone'],
        'auth_provider' => $user['auth_provider'],
        'role' => $user['role'],
        'status' => $user['status'],
        'message_disabled' => (bool)$user['message_disabled'],
        'booking_disabled' => (bool)$user['booking_disabled'],
        'onboarding_step' => 'avatar',
        'first_name' => $user['first_name'],
        'last_name' => $user['last_name'],
        'address' => $address,
        'city' => $city,
        'state' => $state,
        'country' => $country,
        'avatar' => $user['avatar']
    ];
    $newToken = JWTManager::generateToken($jwtData);

    send_success('Location updated successfully. Please upload your avatar.', ['token' => $newToken, 'onboarding_step' => 'avatar']);

} catch (Exception $e) {
    error_log("Set location error for user ID $userId: " . $e->getMessage());
    send_error('Failed to update location.', [], 500);
}
