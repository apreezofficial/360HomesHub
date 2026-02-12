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
    
    // Fetch all users with basic info and activity counts
    $stmt = $pdo->prepare("
        SELECT id, first_name, last_name, email, phone, role, is_verified, onboarding_step, created_at, avatar,
               (SELECT COUNT(*) FROM bookings WHERE guest_id = users.id) as booking_count,
               (SELECT COUNT(*) FROM properties WHERE host_id = users.id) as listing_count
        FROM users 
        ORDER BY created_at DESC
    ");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch summary stats
    $stats = [
        'total_users' => $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
        'host_count' => $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'host'")->fetchColumn(),
        'guest_count' => $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'guest'")->fetchColumn(),
    ];

    send_success('Users retrieved successfully.', [
        'users' => $users,
        'stats' => $stats
    ]);

} catch (Exception $e) {
    error_log("Admin users error: " . $e->getMessage());
    send_error('Failed to retrieve users.', [], 500);
}
