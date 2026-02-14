<?php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../utils/db.php';
require_once __DIR__ . '/../../utils/response.php';
require_once __DIR__ . '/../../utils/jwt.php';
require_once __DIR__ . '/../../utils/upload.php';

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

try {
    $avatarPath = UploadManager::uploadFile('avatar');

    if (!$avatarPath) {
        exit();
    }

    // Update user's avatar
    $stmt = $pdo->prepare("UPDATE users SET avatar = ? WHERE id = ?");
    $stmt->execute([$avatarPath, $userId]);

    // Fetch updated user data for JWT
    $stmt = $pdo->prepare("SELECT email, phone, auth_provider, role, status, message_disabled, booking_disabled, onboarding_step, first_name, last_name FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Generate new JWT with updated avatar
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
        'first_name' => $user['first_name'],
        'last_name' => $user['last_name'],
        'avatar' => $avatarPath
    ];
    $newToken = JWTManager::generateToken($jwtData);

    send_success('Avatar updated successfully.', [
        'token' => $newToken,
        'avatar_url' => $avatarPath
    ]);

} catch (Exception $e) {
    error_log("Update avatar error for user ID $userId: " . $e->getMessage());
    send_error('Failed to update avatar.', [], 500);
}
