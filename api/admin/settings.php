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
    
    // Fetch admin user details
    $stmt = $pdo->prepare("SELECT first_name, last_name, email, phone FROM users WHERE id = ?");
    $stmt->execute([$userData['id']]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$admin) {
        send_error('Admin user not found.', [], 404);
    }

    // Mock generic settings for now
    $settings = [
        'site_name' => '360HomesHub',
        'support_email' => 'support@360houmeshub.com',
        'admin_profile' => $admin
    ];

    send_success('Settings retrieved successfully.', ['settings' => $settings]);

} catch (Exception $e) {
    error_log("Admin settings error: " . $e->getMessage());
    send_error('Failed to retrieve settings.', [], 500);
}
