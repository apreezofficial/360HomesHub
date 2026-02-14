<?php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../utils/db.php';
require_once __DIR__ . '/../../utils/response.php';
require_once __DIR__ . '/../../utils/jwt.php';
require_once __DIR__ . '/../../utils/otp.php';

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

$newPhone = $input['new_phone'] ?? '';

// Basic phone number validation
if (!preg_match('/^\+[1-9]\d{1,14}$/', $newPhone)) {
    send_error('Invalid phone number format. Please include country code (e.g., +1234567890).', [], 400);
}

$pdo = Database::getInstance();

try {
    // Check if new phone already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE phone = ? AND id != ?");
    $stmt->execute([$newPhone, $userId]);
    if ($stmt->fetch()) {
        send_error('Phone number already in use.', [], 409);
    }

    // Get current user data
    $stmt = $pdo->prepare("SELECT phone FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        send_error('User not found.', [], 404);
    }

    // Send OTP to new phone for verification
    $otpManager = new OtpManager();
    if (!$otpManager->sendOtp($userId, null, $newPhone)) {
        send_error('Failed to send verification code to new phone number.', [], 500);
    }

    send_success('Verification code sent to new phone number. Please verify to complete the change.', [
        'new_phone' => $newPhone,
        'message' => 'Check your new phone for the verification code'
    ]);

} catch (Exception $e) {
    error_log("Request phone change error for user ID $userId: " . $e->getMessage());
    send_error('Failed to process phone change request.', [], 500);
}
