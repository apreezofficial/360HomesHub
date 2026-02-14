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

// Get filter parameter
$status = $_GET['status'] ?? null;

$pdo = Database::getInstance();

try {
    // Build query with filters
    $where = ["user_id = ?"];
    $params = [$userId];

    if ($status && in_array($status, ['pending', 'processing', 'completed', 'failed', 'cancelled'])) {
        $where[] = "status = ?";
        $params[] = $status;
    }

    $whereClause = implode(' AND ', $where);

    // Get total count
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM withdrawals WHERE $whereClause");
    $stmt->execute($params);
    $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Get withdrawals
    $params[] = $limit;
    $params[] = $offset;
    
    $stmt = $pdo->prepare("
        SELECT 
            id, amount, status, reference, bank_name, 
            account_number, account_holder_name, admin_note,
            created_at, processed_at
        FROM withdrawals 
        WHERE $whereClause
        ORDER BY created_at DESC 
        LIMIT ? OFFSET ?
    ");
    $stmt->execute($params);
    $withdrawals = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format withdrawals
    foreach ($withdrawals as &$withdrawal) {
        $withdrawal['amount'] = (float)$withdrawal['amount'];
        $withdrawal['formatted_amount'] = '₦' . number_format($withdrawal['amount'], 2);
        $withdrawal['masked_account_number'] = '****' . substr($withdrawal['account_number'], -4);
        unset($withdrawal['account_number']); // Remove full account number for security
    }

    $totalPages = ceil($total / $limit);

    send_success('Withdrawals retrieved successfully.', [
        'withdrawals' => $withdrawals,
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
    error_log("Get withdrawals error for user ID $userId: " . $e->getMessage());
    send_error('Failed to retrieve withdrawals.', [], 500);
}
