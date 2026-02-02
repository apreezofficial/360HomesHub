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

try {
    $pdo = Database::getInstance();

    // Total Users
    $totalUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();

    // Total Properties
    $totalProperties = $pdo->query("SELECT COUNT(*) FROM properties")->fetchColumn();
    $publishedProperties = $pdo->query("SELECT COUNT(*) FROM properties WHERE status = 'published'")->fetchColumn();
    $draftProperties = $pdo->query("SELECT COUNT(*) FROM properties WHERE status = 'draft'")->fetchColumn();

    // Total Revenue (from successful transactions)
    $totalRevenue = $pdo->query("SELECT SUM(amount) FROM transactions WHERE status = 'success'")->fetchColumn() ?: 0;

    // Pending KYC
    $pendingKYC = $pdo->query("SELECT COUNT(*) FROM kyc WHERE status = 'pending'")->fetchColumn();

    // Recent Transactions (top 5)
    $stmt = $pdo->query("
        SELECT t.*, u.first_name, u.last_name 
        FROM transactions t 
        JOIN users u ON t.user_id = u.id 
        ORDER BY t.created_at DESC 
        LIMIT 5
    ");
    $recentTransactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    send_success('Admin stats retrieved.', [
        'users' => (int)$totalUsers,
        'properties' => [
            'total' => (int)$totalProperties,
            'published' => (int)$publishedProperties,
            'draft' => (int)$draftProperties
        ],
        'revenue' => (float)$totalRevenue,
        'pending_kyc' => (int)$pendingKYC,
        'recent_transactions' => $recentTransactions
    ]);

} catch (Exception $e) {
    error_log("Admin stats error: " . $e->getMessage());
    send_error('Failed to retrieve stats.', [], 500);
}
