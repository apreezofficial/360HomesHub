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

$amount = isset($input['amount']) ? floatval($input['amount']) : 0;
$payoutMethodId = isset($input['payout_method_id']) ? intval($input['payout_method_id']) : null;

if ($amount <= 0) {
    send_error('Invalid withdrawal amount.', [], 400);
}

if ($amount < 1000) {
    send_error('Minimum withdrawal amount is ₦1,000.', [], 400);
}

if (!$payoutMethodId) {
    send_error('Payout method is required.', [], 400);
}

$pdo = Database::getInstance();

try {
    $pdo->beginTransaction();

    // Get wallet
    $stmt = $pdo->prepare("SELECT id, balance FROM wallets WHERE user_id = ? FOR UPDATE");
    $stmt->execute([$userId]);
    $wallet = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$wallet) {
        $pdo->rollBack();
        send_error('Wallet not found.', [], 404);
    }

    // Check sufficient balance
    if ($wallet['balance'] < $amount) {
        $pdo->rollBack();
        send_error('Insufficient balance.', [], 400);
    }

    // Verify payout method belongs to user
    $stmt = $pdo->prepare("SELECT bank_name, account_number, account_holder_name FROM payout_methods WHERE id = ? AND user_id = ?");
    $stmt->execute([$payoutMethodId, $userId]);
    $payoutMethod = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$payoutMethod) {
        $pdo->rollBack();
        send_error('Invalid payout method.', [], 404);
    }

    // Generate unique reference
    $reference = 'WD-' . strtoupper(uniqid()) . '-' . time();

    // Create withdrawal record
    $stmt = $pdo->prepare("
        INSERT INTO withdrawals 
        (user_id, wallet_id, payout_method_id, amount, status, reference, bank_name, account_number, account_holder_name) 
        VALUES (?, ?, ?, ?, 'pending', ?, ?, ?, ?)
    ");
    $stmt->execute([
        $userId, 
        $wallet['id'], 
        $payoutMethodId, 
        $amount, 
        $reference,
        $payoutMethod['bank_name'],
        $payoutMethod['account_number'],
        $payoutMethod['account_holder_name']
    ]);
    $withdrawalId = $pdo->lastInsertId();

    // Deduct from wallet
    $newBalance = $wallet['balance'] - $amount;
    $stmt = $pdo->prepare("UPDATE wallets SET balance = ? WHERE id = ?");
    $stmt->execute([$newBalance, $wallet['id']]);

    // Create transaction record
    $stmt = $pdo->prepare("
        INSERT INTO transactions 
        (user_id, wallet_id, type, category, amount, balance_before, balance_after, reference, description, status, related_withdrawal_id, metadata) 
        VALUES (?, ?, 'debit', 'withdrawal', ?, ?, ?, ?, ?, 'processing', ?, ?)
    ");
    $stmt->execute([
        $userId,
        $wallet['id'],
        $amount,
        $wallet['balance'],
        $newBalance,
        $reference,
        'Money sent to ' . $payoutMethod['bank_name'] . ' ****' . substr($payoutMethod['account_number'], -4),
        $withdrawalId,
        json_encode([
            'bank_name' => $payoutMethod['bank_name'],
            'account_number' => '****' . substr($payoutMethod['account_number'], -4)
        ])
    ]);

    $pdo->commit();

    send_success('Withdrawal initiated successfully.', [
        'withdrawal' => [
            'id' => $withdrawalId,
            'reference' => $reference,
            'amount' => $amount,
            'formatted_amount' => '₦' . number_format($amount, 2),
            'status' => 'processing',
            'bank_name' => $payoutMethod['bank_name'],
            'account_number' => '****' . substr($payoutMethod['account_number'], -4),
            'estimated_time' => '24 hours',
            'new_balance' => $newBalance
        ]
    ]);

} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Withdraw funds error for user ID $userId: " . $e->getMessage());
    send_error('Failed to process withdrawal.', [], 500);
}
