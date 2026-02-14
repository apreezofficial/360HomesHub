<?php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../../utils/db.php';
require_once __DIR__ . '/../../utils/response.php';
require_once __DIR__ . '/../../utils/jwt.php';

// --- JWT Authentication ---
$userData = JWTManager::authenticate();
$user_id = $userData['user_id'] ?? null;

if (!$user_id) {
    send_error("Unauthorized. Invalid or missing token.", [], 401);
}

// Check if user is admin (you may need to adjust this based on your user roles)
// For now, assuming admin has role 'admin' or user_id = 1
$pdo = get_db_connection();
$stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user_role = $stmt->fetchColumn();

if ($user_role !== 'admin' && $user_id != 1) {
    send_error("Forbidden. Admin access required.", [], 403);
}

// --- Query Parameters ---
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
$user_filter = isset($_GET['user_id']) ? (int)$_GET['user_id'] : null;
$action_type_filter = isset($_GET['action_type']) ? $_GET['action_type'] : null;
$entity_type_filter = isset($_GET['entity_type']) ? $_GET['entity_type'] : null;
$entity_id_filter = isset($_GET['entity_id']) ? (int)$_GET['entity_id'] : null;
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : null;
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : null;

// Validate limit
if ($limit > 500) {
    $limit = 500; // Max 500 records per request
}

try {
    // Build query
    $where_clauses = [];
    $params = [];

    if ($user_filter) {
        $where_clauses[] = "user_id = :user_id";
        $params[':user_id'] = $user_filter;
    }

    if ($action_type_filter) {
        $where_clauses[] = "action_type LIKE :action_type";
        $params[':action_type'] = "%{$action_type_filter}%";
    }

    if ($entity_type_filter) {
        $where_clauses[] = "entity_type = :entity_type";
        $params[':entity_type'] = $entity_type_filter;
    }

    if ($entity_id_filter) {
        $where_clauses[] = "entity_id = :entity_id";
        $params[':entity_id'] = $entity_id_filter;
    }

    if ($date_from) {
        $where_clauses[] = "created_at >= :date_from";
        $params[':date_from'] = $date_from;
    }

    if ($date_to) {
        $where_clauses[] = "created_at <= :date_to";
        $params[':date_to'] = $date_to . ' 23:59:59';
    }

    $where_sql = count($where_clauses) > 0 ? "WHERE " . implode(" AND ", $where_clauses) : "";

    // Get total count
    $count_sql = "SELECT COUNT(*) FROM activity_logs {$where_sql}";
    $count_stmt = $pdo->prepare($count_sql);
    $count_stmt->execute($params);
    $total_count = $count_stmt->fetchColumn();

    // Get logs
    $sql = "
        SELECT 
            al.id,
            al.user_id,
            al.action_type,
            al.action_description,
            al.entity_type,
            al.entity_id,
            al.ip_address,
            al.user_agent,
            al.metadata,
            al.created_at,
            u.email as user_email,
            u.first_name,
            u.last_name
        FROM activity_logs al
        LEFT JOIN users u ON al.user_id = u.id
        {$where_sql}
        ORDER BY al.created_at DESC
        LIMIT :limit OFFSET :offset
    ";

    $stmt = $pdo->prepare($sql);
    
    // Bind filter parameters
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    
    // Bind limit and offset
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    
    $stmt->execute();
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Parse metadata JSON
    foreach ($logs as &$log) {
        if ($log['metadata']) {
            $log['metadata'] = json_decode($log['metadata'], true);
        }
    }

    // Get statistics
    $stats_sql = "
        SELECT 
            action_type,
            COUNT(*) as count
        FROM activity_logs
        {$where_sql}
        GROUP BY action_type
        ORDER BY count DESC
        LIMIT 20
    ";
    $stats_stmt = $pdo->prepare($stats_sql);
    $stats_stmt->execute($params);
    $stats = $stats_stmt->fetchAll(PDO::FETCH_ASSOC);

    $response_data = [
        'logs' => $logs,
        'pagination' => [
            'total' => (int)$total_count,
            'limit' => $limit,
            'offset' => $offset,
            'has_more' => ($offset + $limit) < $total_count
        ],
        'statistics' => $stats,
        'filters_applied' => [
            'user_id' => $user_filter,
            'action_type' => $action_type_filter,
            'entity_type' => $entity_type_filter,
            'entity_id' => $entity_id_filter,
            'date_from' => $date_from,
            'date_to' => $date_to
        ]
    ];

    send_success("Activity logs retrieved successfully.", $response_data);

} catch (PDOException $e) {
    error_log("Database error fetching activity logs: " . $e->getMessage());
    send_error("Database error. Could not fetch activity logs.", [], 500);
} catch (Exception $e) {
    error_log("Error fetching activity logs: " . $e->getMessage());
    send_error("An unexpected error occurred.", [], 500);
}
