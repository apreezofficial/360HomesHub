<?php
header("Content-Type: application/json");
require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../utils/db.php';
require_once __DIR__ . '/../../utils/jwt.php';

try {
    // Auth Check
    $user = JWTManager::authenticate();
    
    if (!$user || !isset($user['role']) || ($user['role'] !== 'admin' && $user['role'] !== 'super_admin')) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }

    $pdo = Database::getInstance();
    $data = json_decode(file_get_contents("php://input"), true);
    
    // Validate inputs
    if (!isset($data['id']) || !isset($data['role'])) {
        throw new Exception("Missing parameters");
    }

    $targetId = $data['id'];
    $newRole = $data['role'];

    // Verify allowed roles
    if (!in_array($newRole, ['super_admin', 'operations', 'finance', 'trust'])) {
        throw new Exception("Invalid role");
    }

    // Get Target User Name for Logging
    $stmt = $pdo->prepare("SELECT first_name, last_name FROM users WHERE id = ?");
    $stmt->execute([$targetId]);
    $targetUser = $stmt->fetch(PDO::FETCH_ASSOC);
    $targetName = $targetUser ? ($targetUser['first_name'] . ' ' . $targetUser['last_name']) : "User #$targetId";

    // Update Role
    $updateStmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
    $updateStmt->execute([$newRole, $targetId]);

    // Log Action (by Current Admin)
    $logStmt = $pdo->prepare("INSERT INTO audit_logs (admin_id, action, details) VALUES (?, ?, ?)");
    $logStmt->execute([
        $user['user_id'], // Current admin ID
        "Modified role - $targetName",
        "Changed role to $newRole"
    ]);

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
