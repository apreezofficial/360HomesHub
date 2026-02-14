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
    $typeFilter = $_GET['type'] ?? '';

    // 3. Build Query
    $whereClauses = [];
    $params = [];

    if (!empty($searchTerm)) {
        $whereClauses[] = "(p.name LIKE ? OR p.city LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ?)";
        $wildcard = "%$searchTerm%";
        $params = array_merge($params, [$wildcard, $wildcard, $wildcard, $wildcard]);
    }

    if (!empty($statusFilter)) {
        $whereClauses[] = "p.status = ?";
        $params[] = $statusFilter;
    }

    if (!empty($typeFilter)) {
        $whereClauses[] = "p.type = ?";
        $params[] = $typeFilter;
    }

    $whereSql = !empty($whereClauses) ? "WHERE " . implode(" AND ", $whereClauses) : "";

    // 4. Get Total Count
    $stmtCount = $pdo->prepare("
        SELECT COUNT(*) 
        FROM properties p
        JOIN users u ON p.host_id = u.id
        $whereSql
    ");
    $stmtCount->execute($params);
    $totalProperties = (int)$stmtCount->fetchColumn();

    // 5. Get Properties List
    $sql = "
        SELECT p.*, 
               u.first_name as host_first_name, u.last_name as host_last_name, u.email as host_email,
               (SELECT image_url FROM property_images WHERE property_id = p.id LIMIT 1) as main_image
        FROM properties p
        JOIN users u ON p.host_id = u.id
        $whereSql
        ORDER BY p.created_at DESC
        LIMIT $limit OFFSET $offset
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $properties = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 6. Stats
    $stats = [
        'total' => $pdo->query("SELECT COUNT(*) FROM properties")->fetchColumn(),
        'published' => $pdo->query("SELECT COUNT(*) FROM properties WHERE status = 'published'")->fetchColumn(),
        'pending' => $pdo->query("SELECT COUNT(*) FROM properties WHERE status = 'pending'")->fetchColumn(),
        'draft' => $pdo->query("SELECT COUNT(*) FROM properties WHERE status = 'draft'")->fetchColumn(),
    ];

    send_success('Properties list retrieved successfully.', [
        'properties' => $properties,
        'pagination' => [
            'total' => (int)$totalProperties,
            'page' => $page,
            'limit' => $limit,
            'pages' => ceil($totalProperties / $limit)
        ],
        'stats' => $stats
    ]);

} catch (Exception $e) {
    error_log("Admin properties error: " . $e->getMessage());
    send_error('Failed to retrieve properties: ' . $e->getMessage(), [], 500);
}
