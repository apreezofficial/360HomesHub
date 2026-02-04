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

$input = json_decode(file_get_contents('php://input'), true);
$message_id = $input['message_id'] ?? $_GET['message_id'] ?? null;
// Alternatively, view conversation with a specific user
$other_user_id = $input['other_user_id'] ?? $_GET['other_user_id'] ?? null;


try {
    $pdo = Database::getInstance();
    
    if ($message_id) {
        $stmt = $pdo->prepare("SELECT * FROM messages WHERE id = :mid AND (sender_id = :uid1 OR receiver_id = :uid2)");
        $stmt->bindValue(':mid', $message_id, PDO::PARAM_INT);
        $stmt->bindValue(':uid1', $user_id, PDO::PARAM_INT);
        $stmt->bindValue(':uid2', $user_id, PDO::PARAM_INT);
        $stmt->execute();
        $msg = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$msg) {
            send_error("Message not found or access denied.", [], 404);
        }
        send_success("Message retrieved.", $msg);
        
    } elseif ($other_user_id) {
        $stmt = $pdo->prepare("
            SELECT * FROM messages 
            WHERE (sender_id = :uid1 AND receiver_id = :other1) 
               OR (sender_id = :other2 AND receiver_id = :uid2)
            ORDER BY id ASC
        ");
        $stmt->bindValue(':uid1', $user_id, PDO::PARAM_INT);
        $stmt->bindValue(':other1', $other_user_id, PDO::PARAM_INT);
        $stmt->bindValue(':other2', $other_user_id, PDO::PARAM_INT);
        $stmt->bindValue(':uid2', $user_id, PDO::PARAM_INT);
        $stmt->execute();
        $msgs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        send_success("Conversation retrieved.", $msgs);
    } else {
        send_error("Missing message_id or other_user_id.", [], 400);
    }

} catch (Exception $e) {
    send_error("Error.", [], 500);
}
