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

$firstName = trim($input['first_name'] ?? '');
$lastName = trim($input['last_name'] ?? '');
$bio = trim($input['bio'] ?? '');

if (empty($firstName) || empty($lastName)) {
    send_error('First name and last name are required.', [], 400);
}

$pdo = Database::getInstance();

try {
    // Fetch current user data
    $stmt = $pdo->prepare("SELECT email, phone, auth_provider, role, status, message_disabled, booking_disabled, onboarding_step, avatar, address, city, state, country FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        send_error('User not found.', [], 404);
    }

    // Update profile
    $stmt = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, bio = ? WHERE id = ?");
    $stmt->execute([$firstName, $lastName, $bio, $userId]);

    // Generate new JWT with updated info
    $jwtData = [
        'user_id' => $userId,
        'email' => $user['email'],
        'phone' => $user['phone'],
        'auth_provider' => $user['auth_provider'],
        'role' => $user['role'],
        'status' => $user['status'],
        'message_disabled' => (bool)$user['message_disabled'],
        'booking_disabled' => (bool)$user['booking_disabled'],
        'onboarding_step' => $user['onboarding_step'],
        'first_name' => $firstName,
        'last_name' => $lastName,
        'avatar' => $user['avatar']
    ];
    $newToken = JWTManager::generateToken($jwtData);

    send_success('Profile updated successfully.', [
        'token' => $newToken,
        'profile' => [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'bio' => $bio
        ]
    ]);

} catch (Exception $e) {
    error_log("Update profile error for user ID $userId: " . $e->getMessage());
    send_error('Failed to update profile.', [], 500);
}
