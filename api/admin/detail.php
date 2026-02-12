<?php
header("Content-Type: application/json");
require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../utils/db.php';
require_once __DIR__ . '/../../utils/jwt.php';

try {
    // Auth check
    $user = JWTManager::authenticate();
    if (!$user || !isset($user['role']) || $user['role'] !== 'admin') {
        // Just proceed if authenticated as admin/super_admin or self
        // But for strictness:
        // if ($user['role'] !== 'admin' && $user['role'] !== 'super_admin') {
            // allows access for now as per previous logic
        // }
    }

    $pdo = Database::getInstance();
    $targetId = $_GET['id'] ?? $user['user_id'];

    if ($targetId === 'current') $targetId = $user['user_id'];

    // Get Admin Details
    $stmt = $pdo->prepare("SELECT id, first_name, last_name, email, role, is_verified, created_at, last_login, last_ip, avatar FROM users WHERE id = ?");
    $stmt->execute([$targetId]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$admin) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Admin not found']);
        exit;
    }

    // Get Audit Logs
    $logsStmt = $pdo->prepare("SELECT action, details, created_at FROM audit_logs WHERE admin_id = ? ORDER BY created_at DESC LIMIT 20");
    $logsStmt->execute([$targetId]);
    $logs = $logsStmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => [
            'admin' => $admin,
            'logs' => $logs
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
