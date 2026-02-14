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
    $statusFilter = $_GET['status'] ?? '';
    $paymentStatusFilter = $_GET['payment_status'] ?? '';

    // 3. Build Query
    $whereClauses = [];
    $params = [];

    if (!empty($searchTerm)) {
        $whereClauses[] = "(u.first_name LIKE ? OR u.last_name LIKE ? OR p.name LIKE ? OR b.payment_ref LIKE ?)";
        $wildcard = "%$searchTerm%";
        $params = array_merge($params, [$wildcard, $wildcard, $wildcard, $wildcard]);
    }

    if (!empty($statusFilter)) {
        $whereClauses[] = "b.status = ?";
        $params[] = $statusFilter;
    }

    if (!empty($paymentStatusFilter)) {
        $whereClauses[] = "b.payment_status = ?";
        $params[] = $paymentStatusFilter;
    }

    $whereSql = !empty($whereClauses) ? "WHERE " . implode(" AND ", $whereClauses) : "";

    // 4. Get Total Count
    $stmtCount = $pdo->prepare("
        SELECT COUNT(*) 
        FROM bookings b
        JOIN users u ON b.guest_id = u.id
        JOIN properties p ON b.property_id = p.id
        $whereSql
    ");
    $stmtCount->execute($params);
    $totalBookings = (int)$stmtCount->fetchColumn();

    // 5. Get Bookings List
    $sql = "
        SELECT b.*, 
               u.first_name as guest_first_name, u.last_name as guest_last_name, u.email as guest_email, u.avatar as guest_avatar,
               p.name as property_name, p.city as property_city, p.state as property_state,
               h.first_name as host_first_name, h.last_name as host_last_name
        FROM bookings b
        JOIN users u ON b.guest_id = u.id
        JOIN properties p ON b.property_id = p.id
        JOIN users h ON p.host_id = h.id
        $whereSql
        ORDER BY b.created_at DESC
        LIMIT $limit OFFSET $offset
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 6. Stats for Badges
    $stats = [
        'total' => $pdo->query("SELECT COUNT(*) FROM bookings")->fetchColumn(),
        'pending' => $pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'pending'")->fetchColumn(),
        'confirmed' => $pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'confirmed'")->fetchColumn(),
        'active' => $pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'active'")->fetchColumn(),
        'cancelled' => $pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'cancelled'")->fetchColumn(),
    ];

    send_success('Bookings list retrieved successfully.', [
        'bookings' => $bookings,
        'pagination' => [
            'total' => (int)$totalBookings,
            'page' => $page,
            'limit' => $limit,
            'pages' => ceil($totalBookings / $limit)
        ],
        'stats' => $stats
    ]);

} catch (Exception $e) {
    error_log("Admin bookings error: " . $e->getMessage());
    send_error('Failed to retrieve bookings: ' . $e->getMessage(), [], 500);
}
