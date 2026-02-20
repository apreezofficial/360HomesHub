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
    
    // Fetch all transactions
    $stmt = $pdo->prepare("
        SELECT t.*, u.first_name, u.last_name, u.email 
        FROM transactions t 
        JOIN users u ON t.user_id = u.id 
        ORDER BY t.created_at DESC
    ");
    $stmt->execute();
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    send_success('Transactions retrieved successfully.', ['transactions' => $transactions]);

} catch (Exception $e) {
    error_log("Admin transactions error: " . $e->getMessage());
    send_error('Failed to retrieve transactions.', [], 500);
}
