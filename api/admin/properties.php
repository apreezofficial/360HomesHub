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

try {
    $pdo = Database::getInstance();
    $stmt = $pdo->prepare("
        SELECT p.*, u.email as host_email, u.first_name, u.last_name 
        FROM properties p 
        JOIN users u ON p.host_id = u.id 
        ORDER BY p.created_at DESC
    ");
    $stmt->execute();
    $properties = $stmt->fetchAll(PDO::FETCH_ASSOC);

    send_success('Properties retrieved successfully.', ['properties' => $properties]);

} catch (Exception $e) {
    error_log("Admin properties error: " . $e->getMessage());
    send_error('Failed to retrieve properties.', [], 500);
}
