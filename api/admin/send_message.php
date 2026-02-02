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
$receiverId = $input['receiver_id'] ?? null;
$message = $input['message'] ?? null;

if (!$receiverId || !$message) {
    send_error('Receiver ID and message are required.', [], 400);
}

try {
    $pdo = Database::getInstance();
    
    $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, message) VALUES (?, ?, ?)");
    $stmt->execute([$userData['user_id'], $receiverId, $message]);

    send_success('Message sent to user successfully.');

} catch (Exception $e) {
    error_log("Admin send message error: " . $e->getMessage());
    send_error('Failed to send message.', [], 500);
}
