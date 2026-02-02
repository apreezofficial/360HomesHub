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

$input = json_decode(file_get_contents('php://input'), true);
$propertyId = $input['property_id'] ?? null;
$status = $input['status'] ?? null; // published, archived, etc.

if (!$propertyId || !$status) {
    send_error('Property ID and Status are required.', [], 400);
}

// Map allowed statuses
$allowedStatuses = ['draft', 'published', 'archived'];
if (!in_array($status, $allowedStatuses)) {
    send_error('Invalid status provided.', [], 400);
}

try {
    $pdo = Database::getInstance();
    
    $stmt = $pdo->prepare("UPDATE properties SET status = ? WHERE id = ?");
    $stmt->execute([$status, $propertyId]);

    send_success("Property status updated to $status.", ['property_id' => $propertyId, 'status' => $status]);

} catch (Exception $e) {
    error_log("Admin update property error: " . $e->getMessage());
    send_error('Failed to update property status.', [], 500);
}
