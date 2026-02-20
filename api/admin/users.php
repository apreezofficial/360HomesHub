<?php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../utils/db.php';
require_once __DIR__ . '/../../utils/response.php';
require_once __DIR__ . '/../../utils/jwt.php';

// Authenticate admin
$userData = JWTManager::authenticate();
if (!in_array($userData['role'], ['admin', 'super_admin'])) {
    send_error('Access denied. Admin only.', [], 403);
}

$pdo = Database::getInstance();

try {
    // 1. Pagination Params
    $page   = isset($_GET['page'])  ? max(1, (int)$_GET['page'])               : 1;
    $limit  = isset($_GET['limit']) ? max(1, min(100, (int)$_GET['limit']))    : 25;
    $offset = ($page - 1) * $limit;

    // 2. Filter / Search Params
    $searchTerm   = trim($_GET['search'] ?? '');
    $roleFilter   = $_GET['role']   ?? '';
    $statusFilter = $_GET['status'] ?? '';
    $kycFilter    = $_GET['kyc']    ?? '';
    $sortBy       = $_GET['sort']   ?? 'newest';

    // 3. Build WHERE
    $whereClauses = [];
    $params       = [];

    if (!empty($searchTerm)) {
        $whereClauses[] = "(u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ? OR u.phone LIKE ? OR CAST(u.id AS CHAR) = ?)";
        $w = "%$searchTerm%";
        $params = array_merge($params, [$w, $w, $w, $w, $searchTerm]);
    }

    if (!empty($roleFilter)) {
        $whereClauses[] = "u.role = ?";
        $params[] = $roleFilter;
    }

    if (!empty($statusFilter)) {
        $whereClauses[] = "u.status = ?";
        $params[] = $statusFilter;
    }

    if ($kycFilter === 'verified') {
        $whereClauses[] = "u.status = 'verified'";
    } elseif ($kycFilter === 'pending') {
        $whereClauses[] = "EXISTS (SELECT 1 FROM kyc k WHERE k.user_id = u.id AND k.status = 'pending')";
    } elseif ($kycFilter === 'none') {
        $whereClauses[] = "u.status = 'no_kyc' AND NOT EXISTS (SELECT 1 FROM kyc k WHERE k.user_id = u.id)";
    }

    $whereSql = !empty($whereClauses) ? "WHERE " . implode(" AND ", $whereClauses) : "";

    // 4. Sort
    $orderSql = "u.created_at DESC";
    if ($sortBy === 'oldest')     $orderSql = "u.created_at ASC";
    if ($sortBy === 'name')       $orderSql = "u.first_name ASC, u.last_name ASC";
    if ($sortBy === 'activity')   $orderSql = "booking_count DESC";

    // 5. Total Count
    $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM users u $whereSql");
    $stmtCount->execute($params);
    $totalCount = (int)$stmtCount->fetchColumn();

    // 6. Users List with richer data
    $sql = "
        SELECT
            u.id,
            u.first_name,
            u.last_name,
            u.email,
            u.phone,
            u.role,
            u.status,
            u.avatar,
            u.created_at,
            u.last_login,
            u.booking_disabled,
            u.message_disabled,
            (SELECT COUNT(*) FROM bookings WHERE guest_id = u.id) as booking_count,
            (SELECT COUNT(*) FROM properties WHERE host_id = u.id) as listing_count,
            (SELECT COUNT(*) FROM bookings WHERE host_id = u.id) as bookings_received,
            COALESCE(
                (SELECT k.status FROM kyc k WHERE k.user_id = u.id ORDER BY k.submitted_at DESC LIMIT 1),
                'none'
            ) as kyc_status,
            COALESCE(
                (SELECT SUM(total_amount) FROM bookings WHERE guest_id = u.id AND status = 'confirmed'), 0
            ) as total_spent,
            COALESCE(
                (SELECT SUM(total_amount) FROM bookings WHERE host_id = u.id AND status = 'confirmed'), 0
            ) as total_earned
        FROM users u
        $whereSql
        ORDER BY $orderSql
        LIMIT $limit OFFSET $offset
    ";


    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 7. Summary Stats
    $stats = [
        'total_users'   => (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
        'host_count'    => (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'host'")->fetchColumn(),
        'guest_count'   => (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'guest'")->fetchColumn(),
        'admin_count'   => (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn(),
        'suspended'     => (int)$pdo->query("SELECT COUNT(*) FROM users WHERE status = 'suspended'")->fetchColumn(),
        'verified'      => (int)$pdo->query("SELECT COUNT(*) FROM users WHERE status = 'verified'")->fetchColumn(),
        'pending_kyc'   => (int)$pdo->query("SELECT COUNT(DISTINCT user_id) FROM kyc WHERE status = 'pending'")->fetchColumn(),
        'new_this_week' => (int)$pdo->query("SELECT COUNT(*) FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetchColumn(),
    ];


    send_success('Users list retrieved successfully.', [
        'users'      => $users,
        'pagination' => [
            'total' => $totalCount,
            'page'  => $page,
            'limit' => $limit,
            'pages' => max(1, (int)ceil($totalCount / $limit))
        ],
        'stats' => $stats
    ]);

} catch (Exception $e) {
    error_log("Admin users error: " . $e->getMessage());
    send_error('Failed to retrieve users: ' . $e->getMessage(), [], 500);
}
