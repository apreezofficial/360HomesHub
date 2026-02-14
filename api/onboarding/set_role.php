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
$stmt = $pdo->prepare("SELECT onboarding_step, email, phone, auth_provider, status, message_disabled, booking_disabled, first_name, last_name, bio, address, city, state, country, avatar FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$userOnboarding = $user['onboarding_step'];

if ($userOnboarding !== 'role' && $userOnboarding !== 'kyc' && $userOnboarding !== 'completed') {
    send_error('Please complete previous onboarding steps. Current step: ' . $userOnboarding, ['onboarding_step' => $userOnboarding], 403);
}

$input = json_decode(file_get_contents('php://input'), true);

$role = strtolower(trim($input['role'] ?? ''));

if (!in_array($role, ['guest', 'host'])) {
    send_error('Invalid role. Role must be either "guest" or "host".', [], 400);
}

try {
    // Update user's role and onboarding step
    $stmt = $pdo->prepare("UPDATE users SET role = ?, onboarding_step = 'kyc' WHERE id = ?");
    $stmt->execute([$role, $userId]);

    // Generate new JWT token with updated onboarding step and role
    $jwtData = [
        'user_id' => $userId,
        'email' => $user['email'],
        'phone' => $user['phone'],
        'auth_provider' => $user['auth_provider'],
        'role' => $role,
        'status' => $user['status'],
        'message_disabled' => (bool)$user['message_disabled'],
        'booking_disabled' => (bool)$user['booking_disabled'],
        'onboarding_step' => 'kyc',
        'first_name' => $user['first_name'],
        'last_name' => $user['last_name'],
        'avatar' => $user['avatar']
    ];
    $newToken = JWTManager::generateToken($jwtData);

    send_success('Role selected successfully. Please proceed to KYC verification.', ['token' => $newToken, 'onboarding_step' => 'kyc', 'role' => $role]);

} catch (Exception $e) {
    error_log("Set role error for user ID $userId: " . $e->getMessage());
    send_error('Failed to set role.', [], 500);
}
