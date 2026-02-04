<?php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../utils/db.php';
require_once __DIR__ . '/../../utils/response.php';
require_once __DIR__ . '/../../utils/jwt.php';

header("Content-Type: application/json");

$userData = JWTManager::authenticate();
$user_id = $userData['user_id'] ?? null;

if (!$user_id) {
    send_error("Unauthorized.", [], 401);
}

try {
    $pdo = Database::getInstance();
    
    // Fetch last 50 messages where user is sender or receiver
    $stmt = $pdo->prepare("
        SELECT * FROM messages 
        WHERE sender_id = :uid1 OR receiver_id = :uid2 
        ORDER BY id DESC 
        LIMIT 50
    ");
    $stmt->bindValue(':uid1', $user_id, PDO::PARAM_INT);
    $stmt->bindValue(':uid2', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    send_success("Messages retrieved successfully.", $messages);

} catch (Exception $e) {
    error_log("Messages error: " . $e->getMessage());
    send_error("Failed to retrieve messages.", [], 500);
}
