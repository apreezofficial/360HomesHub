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

$propertyId = $_GET['id'] ?? null;
if (!$propertyId) {
    send_error('Property ID is required.', [], 400);
}

try {
    $pdo = Database::getInstance();
    
    // Fetch property details
    $stmt = $pdo->prepare("
        SELECT p.*, u.email as host_email, u.first_name, u.last_name, u.phone as host_phone
        FROM properties p 
        JOIN users u ON p.host_id = u.id 
        WHERE p.id = ?
    ");
    $stmt->execute([$propertyId]);
    $property = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$property) {
        send_error('Property not found.', [], 404);
    }

    // Fetch images
    $stmt = $pdo->prepare("SELECT * FROM property_images WHERE property_id = ?");
    $stmt->execute([$propertyId]);
    $images = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $property['images'] = $images;

    send_success('Property details retrieved successfully.', ['property' => $property]);

} catch (Exception $e) {
    error_log("Admin property details error: " . $e->getMessage());
    send_error('Failed to retrieve property details.', [], 500);
}
