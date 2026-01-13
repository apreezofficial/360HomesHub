<?php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../utils/db.php';
require_once __DIR__ . '/../utils/response.php';
require_once __DIR__ . '/../utils/jwt.php';
require_once __DIR__ . '/../utils/upload.php';

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
$stmt = $pdo->prepare("SELECT onboarding_step FROM users WHERE id = ?");
$stmt->execute([$userId]);
$userOnboarding = $stmt->fetchColumn();

if ($userOnboarding !== 'avatar' && $userOnboarding !== 'role' && $userOnboarding !== 'kyc' && $userOnboarding !== 'completed') {
    send_error('Please complete previous onboarding steps. Current step: ' . $userOnboarding, ['onboarding_step' => $userOnboarding], 403);
}

try {
    $avatarPath = UploadManager::uploadFile('avatar'); // 'avatar' is the name of the file input field

    if (!$avatarPath) {
        // UploadManager::uploadFile already sends error, so just exit if it fails
        exit();
    }

    // Update user's avatar and onboarding step
    $stmt = $pdo->prepare("UPDATE users SET avatar = ?, onboarding_step = 'role' WHERE id = ?");
    $stmt->execute([$avatarPath, $userId]);

    // Generate new JWT token with updated onboarding step and avatar
    $userData['avatar'] = $avatarPath;
    $userData['onboarding_step'] = 'role';
    $newToken = JWTManager::generateToken($userData);

    send_success('Avatar uploaded successfully. Please select your role.', ['token' => $newToken, 'onboarding_step' => 'role', 'avatar_url' => $avatarPath]);

} catch (Exception $e) {
    error_log("Upload avatar error for user ID $userId: " . $e->getMessage());
    send_error('Failed to upload avatar.', [], 500);
}
