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
$adminNote = trim($input['admin_note'] ?? '');

if (!is_numeric($kycId)) {
    send_error('KYC ID is required and must be numeric.', [], 400);
}

$pdo = Database::getInstance();

try {
    // Update KYC status to rejected and add admin note
    $stmt = $pdo->prepare("UPDATE kyc SET status = 'rejected', admin_note = ? WHERE id = ?");
    $stmt->execute([$adminNote, $kycId]);

    send_success('KYC application rejected.', ['kyc_id' => $kycId, 'admin_note' => $adminNote]);

} catch (Exception $e) {
    error_log("Admin reject KYC error for KYC ID $kycId: " . $e->getMessage());
    send_error('Failed to reject KYC application.', [], 500);
}
