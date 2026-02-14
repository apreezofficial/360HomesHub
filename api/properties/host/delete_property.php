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

// Check if user is a host
if (($userData['role'] ?? '') !== 'host') {
    send_error('Only hosts can delete properties.', [], 403);
}

$propertyId = isset($_GET['id']) ? intval($_GET['id']) : null;

if (!$propertyId) {
    send_error('Property ID is required.', [], 400);
}

$pdo = Database::getInstance();

try {
    $pdo->beginTransaction();

    // Verify property belongs to host
    $stmt = $pdo->prepare("SELECT id, status FROM properties WHERE id = ? AND host_id = ?");
    $stmt->execute([$propertyId, $userId]);
    $property = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$property) {
        $pdo->rollBack();
        send_error('Property not found or you do not have permission to delete it.', [], 404);
    }

    // Check for active or confirmed bookings
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM bookings 
        WHERE property_id = ? AND status IN ('pending', 'confirmed')
    ");
    $stmt->execute([$propertyId]);
    $activeBookings = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

    if ($activeBookings > 0) {
        $pdo->rollBack();
        send_error('Cannot delete property with active or pending bookings.', [], 400);
    }

    // Delete property images
    $stmt = $pdo->prepare("DELETE FROM property_images WHERE property_id = ?");
    $stmt->execute([$propertyId]);

    // Delete property amenities
    $stmt = $pdo->prepare("DELETE FROM property_amenities WHERE property_id = ?");
    $stmt->execute([$propertyId]);

    // Delete the property
    $stmt = $pdo->prepare("DELETE FROM properties WHERE id = ?");
    $stmt->execute([$propertyId]);

    $pdo->commit();

    send_success('Property deleted successfully.', []);

} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Delete property error for user ID $userId: " . $e->getMessage());
    send_error('Failed to delete property.', [], 500);
}
