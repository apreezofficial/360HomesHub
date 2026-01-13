<?php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../utils/db.php';
require_once __DIR__ . '/../utils/response.php';
require_once __DIR__ . '/../utils/jwt.php';

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

try {
    // Hash the new password
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    // Update user's password and onboarding step
    $stmt = $pdo->prepare("UPDATE users SET password_hash = ?, onboarding_step = 'profile' WHERE id = ?");
    $stmt->execute([$passwordHash, $userId]);

    // Generate new JWT token with updated onboarding step
    $userData['onboarding_step'] = 'profile';
    $newToken = JWTManager::generateToken($userData);

    send_success('Password set successfully. Please complete your profile.', ['token' => $newToken, 'onboarding_step' => 'profile']);

} catch (Exception $e) {
    error_log("Set password error for user ID $userId: " . $e->getMessage());
    send_error('Failed to set password.', [], 500);
}
