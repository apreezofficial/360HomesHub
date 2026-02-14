<?php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../utils/db.php';
require_once __DIR__ . '/../../utils/response.php';
require_once __DIR__ . '/../../utils/jwt.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    send_error('Invalid request method.', [], 405);
}

// Authenticate user
$userData = JWTManager::authenticate();
$userId = $userData['user_id'] ?? null;

if (!$userId) {
    send_error('Authentication failed.', [], 401);
}

$pdo = Database::getInstance();

try {
    // Counts for guest
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total,
            COUNT(CASE WHEN status = 'confirmed' AND check_out >= CURRENT_DATE THEN 1 END) as active,
            COUNT(CASE WHEN status = 'completed' OR (status = 'confirmed' AND check_out < CURRENT_DATE) THEN 1 END) as completed,
            COUNT(CASE WHEN status = 'pending' OR status = 'approved' THEN 1 END) as pending
        FROM bookings 
        WHERE user_id = ?
    ");
    $stmt->execute([$userId]);
    $guestStats = $stmt->fetch(PDO::FETCH_ASSOC);

    // Counts for host (if user is host)
    $hostStats = null;
    if ($userData['role'] === 'host') {
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total,
                COUNT(CASE WHEN b.status = 'confirmed' AND b.check_out >= CURRENT_DATE THEN 1 END) as active,
                COUNT(CASE WHEN b.status = 'completed' OR (b.status = 'confirmed' AND b.check_out < CURRENT_DATE) THEN 1 END) as completed,
                COUNT(CASE WHEN b.status = 'pending' THEN 1 END) as request_pending
            FROM bookings b
            JOIN properties p ON b.property_id = p.id
            WHERE p.host_id = ?
        ");
        $stmt->execute([$userId]);
        $hostStats = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    send_success('Booking counts retrieved successfully.', [
        'guest' => [
            'total' => (int)$guestStats['total'],
            'active' => (int)$guestStats['active'],
            'completed' => (int)$guestStats['completed'],
            'pending' => (int)$guestStats['pending']
        ],
        'host' => $hostStats ? [
            'total' => (int)$hostStats['total'],
            'active' => (int)$hostStats['active'],
            'completed' => (int)$hostStats['completed'],
            'request_pending' => (int)$hostStats['request_pending']
        ] : null
    ]);

} catch (Exception $e) {
    error_log("Get booking counts error: " . $e->getMessage());
    send_error('Failed to retrieve booking counts.', [], 500);
}
