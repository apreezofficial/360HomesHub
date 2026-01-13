<?php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../utils/db.php';
require_once __DIR__ . '/../../utils/response.php';
require_once __DIR__ . '/../../utils/jwt.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_error('Invalid request method.', [], 405);
}

// Authenticate user via JWT and check for admin role
$userData = JWTManager::authenticate();
if (!isset($userData['is_admin']) || !$userData['is_admin']) {
    send_error('Access denied. Admin privileges required.', [], 403);
}

$input = json_decode(file_get_contents('php://input'), true);

$kycId = $input['kyc_id'] ?? null;

if (!is_numeric($kycId)) {
    send_error('KYC ID is required and must be numeric.', [], 400);
}

$pdo = Database::getInstance();

try {
    $pdo->beginTransaction();

    // Get user_id from kyc record
    $stmt = $pdo->prepare("SELECT user_id FROM kyc WHERE id = ? FOR UPDATE"); // Lock row for update
    $stmt->execute([$kycId]);
    $userId = $stmt->fetchColumn();

    if (!$userId) {
        send_error('KYC application not found.', [], 404);
    }

    // Update KYC status
    $stmt = $pdo->prepare("UPDATE kyc SET status = 'approved' WHERE id = ?");
    $stmt->execute([$kycId]);

    // Update user's is_verified status
    $stmt = $pdo->prepare("UPDATE users SET is_verified = 1 WHERE id = ?");
    $stmt->execute([$userId]);

    $pdo->commit();
    send_success('KYC application approved and user verified.', ['kyc_id' => $kycId, 'user_id' => $userId]);

} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Admin approve KYC error for KYC ID $kycId: " . $e->getMessage());
    send_error('Failed to approve KYC application.', [], 500);
}
