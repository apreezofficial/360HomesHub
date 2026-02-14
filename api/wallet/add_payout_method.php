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

$bankName = trim($input['bank_name'] ?? '');
$accountHolderName = trim($input['account_holder_name'] ?? '');
$accountNumber = trim($input['account_number'] ?? '');
$swiftBicCode = trim($input['swift_bic_code'] ?? '');
$isDefault = isset($input['is_default']) ? (bool)$input['is_default'] : true;

if (empty($bankName) || empty($accountHolderName) || empty($accountNumber)) {
    send_error('Bank name, account holder name, and account number are required.', [], 400);
}

// Validate account number (basic validation)
if (!preg_match('/^[0-9]{10,20}$/', $accountNumber)) {
    send_error('Invalid account number format.', [], 400);
}

$pdo = Database::getInstance();

try {
    $pdo->beginTransaction();

    // If setting as default, unset other default methods
    if ($isDefault) {
        $stmt = $pdo->prepare("UPDATE payout_methods SET is_default = 0 WHERE user_id = ?");
        $stmt->execute([$userId]);
    }

    // Insert new payout method
    $stmt = $pdo->prepare("
        INSERT INTO payout_methods 
        (user_id, bank_name, account_holder_name, account_number, swift_bic_code, is_default) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$userId, $bankName, $accountHolderName, $accountNumber, $swiftBicCode, $isDefault ? 1 : 0]);
    
    $payoutMethodId = $pdo->lastInsertId();

    $pdo->commit();

    send_success('Payout method added successfully.', [
        'payout_method' => [
            'id' => $payoutMethodId,
            'bank_name' => $bankName,
            'account_holder_name' => $accountHolderName,
            'account_number' => $accountNumber,
            'is_default' => $isDefault
        ]
    ]);

} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Add payout method error for user ID $userId: " . $e->getMessage());
    send_error('Failed to add payout method.', [], 500);
}
