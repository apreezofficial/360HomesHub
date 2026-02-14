<?php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../utils/db.php';
require_once __DIR__ . '/../../utils/response.php';
require_once __DIR__ . '/../../utils/jwt.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
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
    $stmt = $pdo->prepare("
        SELECT 
            id, email, phone, first_name, last_name, bio, 
            address, city, state, country, latitude, longitude,
            avatar, role, auth_provider, status, 
            message_disabled, booking_disabled, onboarding_step,
            created_at, last_login
        FROM users 
        WHERE id = ?
    ");
    $stmt->execute([$userId]);
    $profile = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$profile) {
        send_error('Profile not found.', [], 404);
    }

    // Convert boolean fields
    $profile['message_disabled'] = (bool)$profile['message_disabled'];
    $profile['booking_disabled'] = (bool)$profile['booking_disabled'];

    // Get KYC status
    $kycStmt = $pdo->prepare("SELECT status, identity_type, submitted_at FROM kyc WHERE user_id = ? ORDER BY submitted_at DESC LIMIT 1");
    $kycStmt->execute([$userId]);
    $kycData = $kycStmt->fetch(PDO::FETCH_ASSOC);
    
    $profile['kyc_status'] = $kycData ? $kycData['status'] : 'not_submitted';
    $profile['kyc_identity_type'] = $kycData['identity_type'] ?? null;
    $profile['kyc_submitted_at'] = $kycData['submitted_at'] ?? null;

    send_success('Profile retrieved successfully.', ['profile' => $profile]);

} catch (Exception $e) {
    error_log("Get profile error for user ID $userId: " . $e->getMessage());
    send_error('Failed to retrieve profile.', [], 500);
}
