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

// Check user's current onboarding step
$stmt = $pdo->prepare("SELECT onboarding_step FROM users WHERE id = ?");
$stmt->execute([$userId]);
$userOnboarding = $stmt->fetchColumn();

if ($userOnboarding !== 'kyc') {
    send_error('You are not in the KYC onboarding step. Current step: ' . $userOnboarding, ['onboarding_step' => $userOnboarding], 403);
}

try {
    $selfiePath = UploadManager::uploadFile('selfie');

    if (!$selfiePath) {
        // UploadManager::uploadFile already sends error, so just exit if it fails
        exit();
    }

    // Find the pending KYC record for this user
    $stmt = $pdo->prepare("SELECT id FROM kyc WHERE user_id = ? AND status = 'pending' ORDER BY submitted_at DESC LIMIT 1");
    $stmt->execute([$userId]);
    $kycId = $stmt->fetchColumn();

    if (!$kycId) {
        send_error('No pending KYC application found to upload selfie for.', [], 404);
    }

    $pdo->beginTransaction();

    // Update the KYC record with the selfie and set status to pending (if it wasn't already)
    $stmt = $pdo->prepare("UPDATE kyc SET selfie = ?, status = 'pending', submitted_at = CURRENT_TIMESTAMP WHERE id = ?");
    $stmt->execute([$selfiePath, $kycId]);

    // Update user's onboarding step to 'completed'
    $stmt = $pdo->prepare("UPDATE users SET onboarding_step = 'completed' WHERE id = ?");
    $stmt->execute([$userId]);

    $pdo->commit();

    // Generate new JWT token with updated onboarding step
    $userData['onboarding_step'] = 'completed';
    $newToken = JWTManager::generateToken($userData);

    send_success('Selfie uploaded and KYC application submitted for review. You will be notified of the status.', [
        'token' => $newToken,
        'onboarding_step' => 'completed',
        'selfie_url' => $selfiePath
    ]);

} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Upload selfie error for user ID $userId: " . $e->getMessage());
    send_error('Failed to upload selfie and submit KYC.', [], 500);
}
