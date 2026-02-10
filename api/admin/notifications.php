<?php
header("Content-Type: application/json");
require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../utils/db.php';
require_once __DIR__ . '/../../utils/jwt.php';

try {
    $user = JWTManager::authenticate();
    if (!$user || $user['role'] !== 'admin') {
        // http_response_code(403);
        // echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        // exit;
    }

    $pdo = Database::getInstance();
    $method = $_SERVER['REQUEST_METHOD'];

    if ($method === 'GET') {
        // Fetch Notifications
        // Filter by user_id = NULL (Global Admin) or user_id = Current Admin ID
        $query = "SELECT * FROM notifications 
                  WHERE user_id IS NULL OR user_id = ? 
                  ORDER BY created_at DESC LIMIT 50";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$user['user_id']]);
        $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['success' => true, 'data' => $notifications]);

    } elseif ($method === 'PATCH') {
        // Mark as Read
        $data = json_decode(file_get_contents("php://input"), true);
        
        if (isset($data['id'])) {
            $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND (user_id IS NULL OR user_id = ?)");
            $stmt->execute([$data['id'], $user['user_id']]);
        } elseif (isset($data['all'])) {
            $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id IS NULL OR user_id = ?");
            $stmt->execute([$user['user_id']]);
        }
        
        echo json_encode(['success' => true]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
