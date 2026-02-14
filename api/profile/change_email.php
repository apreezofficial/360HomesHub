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

$newEmail = filter_var($input['new_email'] ?? '', FILTER_VALIDATE_EMAIL);

if (!$newEmail) {
    send_error('Valid email address is required.', [], 400);
}

$pdo = Database::getInstance();

try {
    // Check if new email already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $stmt->execute([$newEmail, $userId]);
    if ($stmt->fetch()) {
        send_error('Email address already in use.', [], 409);
    }

    // Get current user data
    $stmt = $pdo->prepare("SELECT email FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        send_error('User not found.', [], 404);
    }

    // Send OTP to new email for verification
    $otpManager = new OtpManager();
    if (!$otpManager->sendOtp($userId, $newEmail, null)) {
        send_error('Failed to send verification code to new email.', [], 500);
    }

    // Store pending email change in session or temporary table
    // For now, we'll use a simple approach - store in a temp column or use OTP metadata
    
    send_success('Verification code sent to new email address. Please verify to complete the change.', [
        'new_email' => $newEmail,
        'message' => 'Check your new email for the verification code'
    ]);

} catch (Exception $e) {
    error_log("Request email change error for user ID $userId: " . $e->getMessage());
    send_error('Failed to process email change request.', [], 500);
}
