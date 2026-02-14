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
$stmt = $pdo->prepare("SELECT onboarding_step, email, phone, auth_provider, role, status, message_disabled, booking_disabled, avatar, address, city, state, country FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$userOnboarding = $user['onboarding_step'];

if ($userOnboarding !== 'profile' && $userOnboarding !== 'location' && $userOnboarding !== 'avatar' && $userOnboarding !== 'role' && $userOnboarding !== 'kyc' && $userOnboarding !== 'completed') {
    send_error('Please complete previous onboarding steps. Current step: ' . $userOnboarding, ['onboarding_step' => $userOnboarding], 403);
}

$input = json_decode(file_get_contents('php://input'), true);

$firstName = trim($input['first_name'] ?? '');
$lastName = trim($input['last_name'] ?? '');
$bio = trim($input['bio'] ?? '');

if (empty($firstName) || empty($lastName)) {
    send_error('First name and last name are required.', [], 400);
}

try {
    // Update user's profile information
    $stmt = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, bio = ?, onboarding_step = 'location' WHERE id = ?");
    $stmt->execute([$firstName, $lastName, $bio, $userId]);

    // Generate new JWT token with updated onboarding step
    // Generate new JWT token with updated onboarding step and profile info
    $jwtData = [
        'user_id' => $userId,
        'email' => $user['email'],
        'phone' => $user['phone'],
        'auth_provider' => $user['auth_provider'],
        'role' => $user['role'],
        'status' => $user['status'],
        'message_disabled' => (bool)$user['message_disabled'],
        'booking_disabled' => (bool)$user['booking_disabled'],
        'onboarding_step' => 'location',
        'avatar' => $user['avatar'],
        'first_name' => $firstName,
        'last_name' => $lastName,
        'bio' => $bio
    ];
    $newToken = JWTManager::generateToken($jwtData);

    send_success('Profile updated successfully. Please set your location.', ['token' => $newToken, 'onboarding_step' => 'location']);

} catch (Exception $e) {
    error_log("Set profile error for user ID $userId: " . $e->getMessage());
    send_error('Failed to update profile.', [], 500);
}
