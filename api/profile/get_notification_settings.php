<?php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../utils/db.php';
require_once __DIR__ . '/../../utils/response.php';
require_once __DIR__ . '/../../utils/jwt.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    send_error('Invalid request method.', [], 405);
}

// Authenticate user via JWT
$userData = JWTManager::authenticate();
$userId = $userData['user_id'] ?? null;

if (!$userId) {
    send_error('Authentication failed.', [], 401);
}

$pdo = Database::getInstance();

try {
    // Get notification preferences
    $stmt = $pdo->prepare("
        SELECT 
            message_disabled,
            booking_disabled
        FROM users 
        WHERE id = ?
    ");
    $stmt->execute([$userId]);
    $preferences = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$preferences) {
        send_error('User not found.', [], 404);
    }

    // Convert to boolean
    $preferences['message_disabled'] = (bool)$preferences['message_disabled'];
    $preferences['booking_disabled'] = (bool)$preferences['booking_disabled'];

    // Invert for frontend (disabled -> enabled)
    $settings = [
        'email_notifications' => !$preferences['message_disabled'],
        'push_notifications' => !$preferences['message_disabled'],
        'booking_notifications' => !$preferences['booking_disabled'],
        'message_notifications' => !$preferences['message_disabled']
    ];

    send_success('Notification settings retrieved successfully.', ['settings' => $settings]);

} catch (Exception $e) {
    error_log("Get notification settings error for user ID $userId: " . $e->getMessage());
    send_error('Failed to retrieve notification settings.', [], 500);
}
