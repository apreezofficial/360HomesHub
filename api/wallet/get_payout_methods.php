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
            id, bank_name, account_holder_name, account_number, 
            swift_bic_code, is_default, created_at
        FROM payout_methods 
        WHERE user_id = ?
        ORDER BY is_default DESC, created_at DESC
    ");
    $stmt->execute([$userId]);
    $payoutMethods = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Convert is_default to boolean
    foreach ($payoutMethods as &$method) {
        $method['is_default'] = (bool)$method['is_default'];
        // Mask account number for security (show last 4 digits)
        $method['masked_account_number'] = '****' . substr($method['account_number'], -4);
    }

    send_success('Payout methods retrieved successfully.', [
        'payout_methods' => $payoutMethods
    ]);

} catch (Exception $e) {
    error_log("Get payout methods error for user ID $userId: " . $e->getMessage());
    send_error('Failed to retrieve payout methods.', [], 500);
}
