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
    // 1. Pagination Params
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $limit = isset($_GET['limit']) ? max(1, min(100, (int)$_GET['limit'])) : 20;
    $offset = ($page - 1) * $limit;

    // 2. Filter Params
    $searchTerm = $_GET['search'] ?? '';
    $roleFilter = $_GET['role'] ?? '';
    $statusFilter = $_GET['status'] ?? '';

    // 3. Build Query
    $whereClauses = [];
    $params = [];

    if (!empty($searchTerm)) {
        $whereClauses[] = "(first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR phone LIKE ?)";
        $wildcard = "%$searchTerm%";
        $params = array_merge($params, [$wildcard, $wildcard, $wildcard, $wildcard]);
    }

    if (!empty($roleFilter)) {
        $whereClauses[] = "role = ?";
        $params[] = $roleFilter;
    }

    if (!empty($statusFilter)) {
        $whereClauses[] = "status = ?";
        $params[] = $statusFilter;
    }

    $whereSql = !empty($whereClauses) ? "WHERE " . implode(" AND ", $whereClauses) : "";

    // 4. Get Total Count for Pagination
    $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM users $whereSql");
    $stmtCount->execute($params);
    $totalUsers = (int)$stmtCount->fetchColumn();

    // 5. Get Users List
    $sql = "
        SELECT id, first_name, last_name, email, phone, role, status, created_at, avatar, onboarding_step,
               (SELECT COUNT(*) FROM bookings WHERE guest_id = users.id) as booking_count,
               (SELECT COUNT(*) FROM properties WHERE host_id = users.id) as listing_count
        FROM users 
        $whereSql
        ORDER BY created_at DESC
        LIMIT $limit OFFSET $offset
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 6. Summary Stats (Total, Active, Pending KYC etc.)
    $stats = [
        'total' => $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
        'hosts' => $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'host'")->fetchColumn(),
        'guests' => $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'guest'")->fetchColumn(),
        'pending_kyc' => $pdo->query("SELECT COUNT(*) FROM users WHERE status = 'no_kyc'")->fetchColumn(),
    ];

    send_success('Users list retrieved successfully.', [
        'users' => $users,
        'pagination' => [
            'total' => $totalUsers,
            'page' => $page,
            'limit' => $limit,
            'pages' => ceil($totalUsers / $limit)
        ],
        'stats' => $stats
    ]);

} catch (Exception $e) {
    error_log("Admin users error: " . $e->getMessage());
    send_error('Failed to retrieve users: ' . $e->getMessage(), [], 500);
}
