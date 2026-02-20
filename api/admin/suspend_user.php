<?php
header("Content-Type: application/json");
require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../utils/db.php';
require_once __DIR__ . '/../../utils/jwt.php';

try {
    $admin = JWTManager::authenticate();
    if (!$admin || !in_array($admin['role'], ['admin', 'super_admin'])) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }

    $pdo = Database::getInstance();
    $data = json_decode(file_get_contents("php://input"), true);

    if (!isset($data['user_id'])) {
        throw new Exception("user_id is required");
    }

    $userId    = (int)$data['user_id'];
    $action    = $data['action'] ?? 'suspend'; // 'suspend' | 'unsuspend'

    // Fetch current user
    $stmt = $pdo->prepare("SELECT id, first_name, last_name, status FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $target = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$target) throw new Exception("User not found");

    // Prevent admins suspending themselves
    if ($userId === (int)$admin['user_id']) {
        throw new Exception("You cannot suspend your own account");
    }

    if ($action === 'suspend') {
        // Preserve current status in a temp column so we can restore on unsuspend
        $newStatus = 'suspended';
        // Store pre-suspend status in a JSON note or just use booking_disabled as flag
        $update = $pdo->prepare("UPDATE users SET status = ?, booking_disabled = 1, message_disabled = 1 WHERE id = ?");
        $update->execute([$newStatus, $userId]);
    } else {
        // Restore to verified if they had it, else no_kyc
        $kycCheck = $pdo->prepare("SELECT COUNT(*) FROM kyc WHERE user_id = ? AND status = 'approved'");
        $kycCheck->execute([$userId]);
        $isVerified = (int)$kycCheck->fetchColumn() > 0;
        $restoreStatus = $isVerified ? 'verified' : 'no_kyc';

        $update = $pdo->prepare("UPDATE users SET status = ?, booking_disabled = 0, message_disabled = 0 WHERE id = ?");
        $update->execute([$restoreStatus, $userId]);
        $newStatus = $restoreStatus;
    }


    // Audit log
    $targetName = "{$target['first_name']} {$target['last_name']}";
    $log = $pdo->prepare("INSERT INTO audit_logs (admin_id, action, details) VALUES (?, ?, ?)");
    $log->execute([
        $admin['user_id'],
        ($action === 'unsuspend' ? "Unsuspended" : "Suspended") . " user - $targetName",
        "User #$userId status changed to $newStatus"
    ]);

    echo json_encode([
        'success' => true,
        'message' => "User has been " . ($action === 'unsuspend' ? 'unsuspended' : 'suspended') . " successfully.",
        'new_status' => $newStatus
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
