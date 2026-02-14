<?php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../utils/db.php';
require_once __DIR__ . '/../../utils/response.php';
require_once __DIR__ . '/../../utils/jwt.php';

// Authenticate admin
$userData = JWTManager::authenticate();
if ($userData['role'] !== 'admin') {
    send_error('Access denied. Admin only.', [], 403);
}

$pdo = Database::getInstance();

try {
    // 1. Transaction List
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $limit = isset($_GET['limit']) ? max(1, min(100, (int)$_GET['limit'])) : 20;
    $offset = ($page - 1) * $limit;

    $stmtTxns = $pdo->prepare("
        SELECT t.*, u.first_name, u.last_name, u.email
        FROM transactions t
        JOIN users u ON t.user_id = u.id
        ORDER BY t.created_at DESC
        LIMIT $limit OFFSET $offset
    ");
    $stmtTxns->execute();
    $transactions = $stmtTxns->fetchAll(PDO::FETCH_ASSOC);

    // 2. Revenue over time (Last 7 days)
    $revenueHistory = [];
    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $stmt = $pdo->prepare("SELECT SUM(amount) FROM transactions WHERE DATE(created_at) = ? AND type = 'credit' AND status = 'completed'");
        $stmt->execute([$date]);
        $amount = (float)$stmt->fetchColumn() ?: 0;
        $revenueHistory[] = ['date' => $date, 'revenue' => $amount];
    }

    // 3. Stats Summary
    $stats = [
        'total_revenue' => $pdo->query("SELECT SUM(amount) FROM transactions WHERE type = 'credit' AND status = 'completed'")->fetchColumn() ?: 0,
        'total_payouts' => $pdo->query("SELECT SUM(amount) FROM transactions WHERE category = 'withdrawal' AND status = 'completed'")->fetchColumn() ?: 0,
        'pending_payouts' => $pdo->query("SELECT SUM(amount) FROM transactions WHERE category = 'withdrawal' AND status = 'pending'")->fetchColumn() ?: 0,
        'platform_commission' => $pdo->query("SELECT SUM(total_price * 0.15) FROM bookings WHERE status = 'confirmed'")->fetchColumn() ?: 0,
    ];

    send_success('Finance stats retrieved successfully.', [
        'transactions' => $transactions,
        'revenue_history' => $revenueHistory,
        'stats' => $stats
    ]);

} catch (Exception $e) {
    error_log("Admin finance error: " . $e->getMessage());
    send_error('Failed to retrieve finance data.', [], 500);
}
