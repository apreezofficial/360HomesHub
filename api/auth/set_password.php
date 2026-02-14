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
    // JWTManager::authenticate() already handles error responses, but this is a safeguard
    send_error('Authentication failed.', [], 401);
}

$input = json_decode(file_get_contents('php://input'), true);

$password = $input['password'] ?? '';

if (empty($password) || strlen($password) < 8) {
    send_error('Password must be at least 8 characters long.', [], 400);
}

$pdo = Database::getInstance();

// Check user's current onboarding step
$stmt = $pdo->prepare("SELECT onboarding_step, email, phone, auth_provider, role, status, message_disabled, booking_disabled, avatar, first_name, last_name FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$userOnboarding = $user['onboarding_step'];

if ($userOnboarding !== 'password' && $userOnboarding !== 'otp') {
    // If user is not in 'password' step, they might be resetting it or it's an error. 
    // Assuming this endpoint is strictly for initial setup or reset.
    // If strict onboarding:
    // send_error('Invalid onboarding step.', [], 403);
}

try {
    // Hash the new password
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    // Update user's password and onboarding step
    $stmt = $pdo->prepare("UPDATE users SET password_hash = ?, onboarding_step = 'profile' WHERE id = ?");
    $stmt->execute([$passwordHash, $userId]);

    // Generate new JWT token with updated onboarding step and user info
    $jwtData = [
        'user_id' => $userId,
        'email' => $user['email'],
        'phone' => $user['phone'],
        'auth_provider' => $user['auth_provider'],
        'role' => $user['role'],
        'status' => $user['status'],
        'message_disabled' => (bool)$user['message_disabled'],
        'booking_disabled' => (bool)$user['booking_disabled'],
        'onboarding_step' => 'profile',
        'avatar' => $user['avatar'],
        'first_name' => $user['first_name'],
        'last_name' => $user['last_name']
    ];
    $newToken = JWTManager::generateToken($jwtData);

    send_success('Password set successfully. Please complete your profile.', ['token' => $newToken, 'onboarding_step' => 'profile']);

} catch (Exception $e) {
    error_log("Set password error for user ID $userId: " . $e->getMessage());
    send_error('Failed to set password.', [], 500);
}
