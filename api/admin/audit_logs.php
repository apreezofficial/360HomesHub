<?php
header("Content-Type: application/json");
require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../utils/db.php';
require_once __DIR__ . '/../../utils/jwt.php';

try {
    $user = JWTManager::authenticate();
    // Allow admins/super_admins
    if (!$user || !in_array($user['role'], ['admin', 'super_admin'])) {
        // http_response_code(403);
        // echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        // exit;
    }

    $pdo = Database::getInstance();

    // Fetch Logs
    $query = "SELECT al.*, u.first_name, u.last_name, u.role as user_role 
              FROM audit_logs al 
              LEFT JOIN users u ON al.admin_id = u.id 
              ORDER BY al.created_at DESC 
              LIMIT 100";
    
    $stmt = $pdo->query($query);
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'data' => $logs]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
