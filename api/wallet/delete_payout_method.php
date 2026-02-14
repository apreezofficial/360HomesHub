<?php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../utils/db.php';
require_once __DIR__ . '/../../utils/response.php';
require_once __DIR__ . '/../../utils/jwt.php';

if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    send_error('Invalid request method.', [], 405);
}

// Authenticate user via JWT
$userData = JWTManager::authenticate();
$userId = $userData['user_id'] ?? null;

if (!$userId) {
    send_error('Authentication failed.', [], 401);
}

$payoutMethodId = isset($_GET['id']) ? intval($_GET['id']) : null;

if (!$payoutMethodId) {
    send_error('Payout method ID is required.', [], 400);
}

$pdo = Database::getInstance();

try {
    // Check if payout method exists and belongs to user
    $stmt = $pdo->prepare("SELECT is_default FROM payout_methods WHERE id = ? AND user_id = ?");
    $stmt->execute([$payoutMethodId, $userId]);
    $method = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$method) {
        send_error('Payout method not found.', [], 404);
    }

    // Check if there are pending withdrawals using this method
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM withdrawals WHERE payout_method_id = ? AND status IN ('pending', 'processing')");
    $stmt->execute([$payoutMethodId]);
    $pendingCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

    if ($pendingCount > 0) {
        send_error('Cannot delete payout method with pending withdrawals.', [], 400);
    }

    // Delete payout method
    $stmt = $pdo->prepare("DELETE FROM payout_methods WHERE id = ? AND user_id = ?");
    $stmt->execute([$payoutMethodId, $userId]);

    // If this was the default, set another as default if available
    if ($method['is_default']) {
        $stmt = $pdo->prepare("UPDATE payout_methods SET is_default = 1 WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
        $stmt->execute([$userId]);
    }

    send_success('Payout method deleted successfully.', []);

} catch (Exception $e) {
    error_log("Delete payout method error for user ID $userId: " . $e->getMessage());
    send_error('Failed to delete payout method.', [], 500);
}
