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

// Check if user is a host
if (($userData['role'] ?? '') !== 'host') {
    send_error('Only hosts can update property status.', [], 403);
}

$input = json_decode(file_get_contents('php://input'), true);

$propertyId = isset($input['property_id']) ? intval($input['property_id']) : null;
$status = $input['status'] ?? null;

if (!$propertyId || !$status) {
    send_error('Property ID and status are required.', [], 400);
}

if (!in_array($status, ['active', 'inactive'])) {
    send_error('Invalid status. Must be "active" or "inactive".', [], 400);
}

$pdo = Database::getInstance();

try {
    // Verify property belongs to host
    $stmt = $pdo->prepare("SELECT id, status FROM properties WHERE id = ? AND host_id = ?");
    $stmt->execute([$propertyId, $userId]);
    $property = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$property) {
        send_error('Property not found or you do not have permission to update it.', [], 404);
    }

    // Don't allow activating draft properties
    if ($property['status'] === 'draft' && $status === 'active') {
        send_error('Cannot activate a draft property. Please complete the listing first.', [], 400);
    }

    // Update status
    $stmt = $pdo->prepare("UPDATE properties SET status = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$status, $propertyId]);

    send_success('Property status updated successfully.', [
        'property_id' => $propertyId,
        'status' => $status
    ]);

} catch (Exception $e) {
    error_log("Update property status error for user ID $userId: " . $e->getMessage());
    send_error('Failed to update property status.', [], 500);
}
