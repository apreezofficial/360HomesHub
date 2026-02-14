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
    // Get or create wallet
    $stmt = $pdo->prepare("SELECT id, balance, currency FROM wallets WHERE user_id = ?");
    $stmt->execute([$userId]);
    $wallet = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$wallet) {
        // Create wallet if doesn't exist
        $stmt = $pdo->prepare("INSERT INTO wallets (user_id, balance, currency) VALUES (?, 0.00, 'NGN')");
        $stmt->execute([$userId]);
        $walletId = $pdo->lastInsertId();
        
        $wallet = [
            'id' => $walletId,
            'balance' => '0.00',
            'currency' => 'NGN'
        ];
    }

    // Get recent transactions (last 10)
    $stmt = $pdo->prepare("
        SELECT 
            id, type, category, amount, description, 
            status, created_at, reference
        FROM transactions 
        WHERE user_id = ? 
        ORDER BY created_at DESC 
        LIMIT 10
    ");
    $stmt->execute([$userId]);
    $recentTransactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate growth percentage (last 30 days vs previous 30 days)
    $stmt = $pdo->prepare("
        SELECT 
            SUM(CASE WHEN type = 'credit' AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN amount ELSE 0 END) as current_month,
            SUM(CASE WHEN type = 'credit' AND created_at >= DATE_SUB(NOW(), INTERVAL 60 DAY) AND created_at < DATE_SUB(NOW(), INTERVAL 30 DAY) THEN amount ELSE 0 END) as previous_month
        FROM transactions 
        WHERE user_id = ?
    ");
    $stmt->execute([$userId]);
    $growth = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $growthPercentage = 0;
    if ($growth['previous_month'] > 0) {
        $growthPercentage = (($growth['current_month'] - $growth['previous_month']) / $growth['previous_month']) * 100;
    } elseif ($growth['current_month'] > 0) {
        $growthPercentage = 100;
    }

    // Get payout method
    $stmt = $pdo->prepare("SELECT id, bank_name, account_number, account_holder_name FROM payout_methods WHERE user_id = ? AND is_default = 1 LIMIT 1");
    $stmt->execute([$userId]);
    $payoutMethod = $stmt->fetch(PDO::FETCH_ASSOC);

    send_success('Wallet data retrieved successfully.', [
        'wallet' => [
            'id' => $wallet['id'],
            'balance' => (float)$wallet['balance'],
            'currency' => $wallet['currency'],
            'formatted_balance' => '₦' . number_format($wallet['balance'], 2),
            'growth_percentage' => round($growthPercentage, 1)
        ],
        'recent_transactions' => $recentTransactions,
        'payout_method' => $payoutMethod
    ]);

} catch (Exception $e) {
    error_log("Get wallet error for user ID $userId: " . $e->getMessage());
    send_error('Failed to retrieve wallet data.', [], 500);
}
