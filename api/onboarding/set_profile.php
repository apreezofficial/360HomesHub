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
$stmt = $pdo->prepare("SELECT onboarding_step FROM users WHERE id = ?");
$stmt->execute([$userId]);
$userOnboarding = $stmt->fetchColumn();

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
    $userData['first_name'] = $firstName;
    $userData['last_name'] = $lastName;
    $userData['bio'] = $bio;
    $userData['onboarding_step'] = 'location';
    $newToken = JWTManager::generateToken($userData);

    send_success('Profile updated successfully. Please set your location.', ['token' => $newToken, 'onboarding_step' => 'location']);

} catch (Exception $e) {
    error_log("Set profile error for user ID $userId: " . $e->getMessage());
    send_error('Failed to update profile.', [], 500);
}
