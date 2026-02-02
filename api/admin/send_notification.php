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
$target = $input['target'] ?? 'all'; // 'all' or user_id
$message = $input['message'] ?? null;

if (!$message) {
    send_error('Notification message is required.', [], 400);
}

try {
    $pdo = Database::getInstance();
    
    if ($target === 'all') {
        $users = $pdo->query("SELECT id FROM users")->fetchAll(PDO::FETCH_COLUMN);
        $stmt = $pdo->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
        foreach ($users as $userId) {
            $stmt->execute([$userId, $message]);
        }
        send_success('Global notification sent to all users.');
    } else {
        $stmt = $pdo->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
        $stmt->execute([$target, $message]);
        send_success('Notification sent to user successfully.');
    }

} catch (Exception $e) {
    error_log("Admin send notification error: " . $e->getMessage());
    send_error('Failed to send notification.', [], 500);
}
