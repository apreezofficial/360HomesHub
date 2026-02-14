<?php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../utils/db.php';
require_once __DIR__ . '/../../utils/response.php';
require_once __DIR__ . '/../../utils/jwt.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_error('Invalid request method.', [], 405);
}

// Authenticate user via JWT
$userData = JWTManager::authenticate();
$userId = $userData['user_id'] ?? null;

if (!$userId) {
    send_error('Authentication failed.', [], 401);
}

$input = json_decode(file_get_contents('php://input'), true);

$payoutMethodId = isset($input['payout_method_id']) ? intval($input['payout_method_id']) : null;

if (!$payoutMethodId) {
    send_error('Payout method ID is required.', [], 400);
}

$pdo = Database::getInstance();

try {
    $pdo->beginTransaction();

    // Verify payout method belongs to user
    $stmt = $pdo->prepare("SELECT id FROM payout_methods WHERE id = ? AND user_id = ?");
    $stmt->execute([$payoutMethodId, $userId]);
    
    if (!$stmt->fetch()) {
        $pdo->rollBack();
        send_error('Payout method not found.', [], 404);
    }

    // Unset all default methods for this user
    $stmt = $pdo->prepare("UPDATE payout_methods SET is_default = 0 WHERE user_id = ?");
    $stmt->execute([$userId]);

    // Set the selected method as default
    $stmt = $pdo->prepare("UPDATE payout_methods SET is_default = 1 WHERE id = ? AND user_id = ?");
    $stmt->execute([$payoutMethodId, $userId]);

    $pdo->commit();

    send_success('Default payout method updated successfully.', []);

} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Set default payout method error for user ID $userId: " . $e->getMessage());
    send_error('Failed to update default payout method.', [], 500);
}
