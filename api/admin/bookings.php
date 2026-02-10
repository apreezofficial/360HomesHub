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
    
    // Fetch all bookings with user and property details
    $stmt = $pdo->prepare("
        SELECT b.*, 
               u.first_name as guest_first_name, u.last_name as guest_last_name, u.email as guest_email,
               p.name as property_name, p.city as property_city, p.state as property_state
        FROM bookings b
        JOIN users u ON b.guest_id = u.id
        JOIN properties p ON b.property_id = p.id
        ORDER BY b.created_at DESC
    ");
    $stmt->execute();
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

    send_success('Bookings retrieved successfully.', ['bookings' => $bookings]);

} catch (Exception $e) {
    error_log("Admin bookings error: " . $e->getMessage());
    send_error('Failed to retrieve bookings.', [], 500);
}
