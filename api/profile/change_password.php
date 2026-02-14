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

$currentPassword = $input['current_password'] ?? '';
$newPassword = $input['new_password'] ?? '';

if (empty($currentPassword) || empty($newPassword)) {
    send_error('Current password and new password are required.', [], 400);
}

if (strlen($newPassword) < 8) {
    send_error('New password must be at least 8 characters long.', [], 400);
}

$pdo = Database::getInstance();

try {
    // Get current password hash
    $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        send_error('User not found.', [], 404);
    }

    // Verify current password
    if (!password_verify($currentPassword, $user['password_hash'])) {
        send_error('Current password is incorrect.', [], 401);
    }

    // Update password
    $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
    $stmt->execute([$newPasswordHash, $userId]);

    send_success('Password changed successfully.', []);

} catch (Exception $e) {
    error_log("Change password error for user ID $userId: " . $e->getMessage());
    send_error('Failed to change password.', [], 500);
}
