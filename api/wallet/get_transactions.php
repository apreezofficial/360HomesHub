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

// Get pagination parameters
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = isset($_GET['limit']) ? min(50, max(1, intval($_GET['limit']))) : 20;
$offset = ($page - 1) * $limit;

// Get filter parameters
$type = $_GET['type'] ?? null; // 'credit' or 'debit'
$category = $_GET['category'] ?? null;
$status = $_GET['status'] ?? null;

$pdo = Database::getInstance();

try {
    // Build query with filters
    $where = ["user_id = ?"];
    $params = [$userId];

    if ($type && in_array($type, ['credit', 'debit'])) {
        $where[] = "type = ?";
        $params[] = $type;
    }

    if ($category) {
        $where[] = "category = ?";
        $params[] = $category;
    }

    if ($status) {
        $where[] = "status = ?";
        $params[] = $status;
    }

    $whereClause = implode(' AND ', $where);

    // Get total count
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM transactions WHERE $whereClause");
    $stmt->execute($params);
    $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Get transactions
    $params[] = $limit;
    $params[] = $offset;
    
    $stmt = $pdo->prepare("
        SELECT 
            id, type, category, amount, description, 
            status, reference, balance_before, balance_after,
            created_at, metadata
        FROM transactions 
        WHERE $whereClause
        ORDER BY created_at DESC 
        LIMIT ? OFFSET ?
    ");
    $stmt->execute($params);
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format transactions
    foreach ($transactions as &$transaction) {
        $transaction['amount'] = (float)$transaction['amount'];
        $transaction['balance_before'] = (float)$transaction['balance_before'];
        $transaction['balance_after'] = (float)$transaction['balance_after'];
        $transaction['formatted_amount'] = '₦' . number_format($transaction['amount'], 2);
        
        if ($transaction['metadata']) {
            $transaction['metadata'] = json_decode($transaction['metadata'], true);
        }
    }

    $totalPages = ceil($total / $limit);

    send_success('Transactions retrieved successfully.', [
        'transactions' => $transactions,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total_items' => (int)$total,
            'items_per_page' => $limit,
            'has_next' => $page < $totalPages,
            'has_prev' => $page > 1
        ]
    ]);

} catch (Exception $e) {
    error_log("Get transactions error for user ID $userId: " . $e->getMessage());
    send_error('Failed to retrieve transactions.', [], 500);
}
