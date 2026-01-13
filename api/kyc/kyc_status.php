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
    $stmt = $pdo->prepare("SELECT id, country, identity_type, id_front, id_back, selfie, status, admin_note, submitted_at FROM kyc WHERE user_id = ? ORDER BY submitted_at DESC LIMIT 1");
    $stmt->execute([$userId]);
    $kycStatus = $stmt->fetch();

    if (!$kycStatus) {
        send_success('No KYC application found for this user.', ['status' => 'not_submitted']);
    } else {
        // Obfuscate image paths or provide full URLs if publicly accessible
        $kycStatus['id_front_url'] = '/public/uploads/' . basename($kycStatus['id_front']);
        $kycStatus['id_back_url'] = '/public/uploads/' . basename($kycStatus['id_back']);
        $kycStatus['selfie_url'] = '/public/uploads/' . basename($kycStatus['selfie']);
        unset($kycStatus['id_front']);
        unset($kycStatus['id_back']);
        unset($kycStatus['selfie']);

        send_success('KYC application status retrieved successfully.', ['kyc' => $kycStatus]);
    }

} catch (Exception $e) {
    error_log("KYC status error for user ID $userId: " . $e->getMessage());
    send_error('Failed to retrieve KYC status.', [], 500);
}
