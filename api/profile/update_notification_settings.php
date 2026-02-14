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

$input = json_decode(file_get_contents('php://input'), true);

// Get settings (frontend sends enabled state, we store disabled state)
$emailNotifications = isset($input['email_notifications']) ? (bool)$input['email_notifications'] : null;
$pushNotifications = isset($input['push_notifications']) ? (bool)$input['push_notifications'] : null;
$bookingNotifications = isset($input['booking_notifications']) ? (bool)$input['booking_notifications'] : null;

$pdo = Database::getInstance();

try {
    // Build update query dynamically based on provided settings
    $updates = [];
    $params = [];

    // Invert the logic (enabled -> not disabled)
    if ($emailNotifications !== null || $pushNotifications !== null) {
        $messageDisabled = !($emailNotifications || $pushNotifications);
        $updates[] = "message_disabled = ?";
        $params[] = $messageDisabled ? 1 : 0;
    }

    if ($bookingNotifications !== null) {
        $bookingDisabled = !$bookingNotifications;
        $updates[] = "booking_disabled = ?";
        $params[] = $bookingDisabled ? 1 : 0;
    }

    if (empty($updates)) {
        send_error('No settings provided to update.', [], 400);
    }

    $params[] = $userId;
    $sql = "UPDATE users SET " . implode(", ", $updates) . " WHERE id = ?";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    // Fetch updated user data for JWT
    $stmt = $pdo->prepare("SELECT email, phone, auth_provider, role, status, message_disabled, booking_disabled, onboarding_step, first_name, last_name, avatar FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Generate new JWT with updated settings
    $jwtData = [
        'user_id' => $userId,
        'email' => $user['email'],
        'phone' => $user['phone'],
        'auth_provider' => $user['auth_provider'],
        'role' => $user['role'],
        'status' => $user['status'],
        'message_disabled' => (bool)$user['message_disabled'],
        'booking_disabled' => (bool)$user['booking_disabled'],
        'onboarding_step' => $user['onboarding_step'],
        'first_name' => $user['first_name'],
        'last_name' => $user['last_name'],
        'avatar' => $user['avatar']
    ];
    $newToken = JWTManager::generateToken($jwtData);

    send_success('Notification settings updated successfully.', [
        'token' => $newToken,
        'settings' => [
            'message_disabled' => (bool)$user['message_disabled'],
            'booking_disabled' => (bool)$user['booking_disabled']
        ]
    ]);

} catch (Exception $e) {
    error_log("Update notification settings error for user ID $userId: " . $e->getMessage());
    send_error('Failed to update notification settings.', [], 500);
}
