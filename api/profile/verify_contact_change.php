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

$otpCode = $input['otp_code'] ?? '';
$newEmail = filter_var($input['new_email'] ?? '', FILTER_VALIDATE_EMAIL);
$newPhone = $input['new_phone'] ?? '';

if (empty($otpCode)) {
    send_error('OTP code is required.', [], 400);
}

if (!$newEmail && !$newPhone) {
    send_error('Either new email or new phone must be provided.', [], 400);
}

$pdo = Database::getInstance();
$otpManager = new OtpManager();

try {
    // Verify OTP
    if (!$otpManager->verifyOtp($userId, $otpCode)) {
        send_error('Invalid or expired OTP.', [], 400);
    }

    // Fetch current user data
    $stmt = $pdo->prepare("SELECT email, phone, auth_provider, role, status, message_disabled, booking_disabled, onboarding_step, first_name, last_name, avatar FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        send_error('User not found.', [], 404);
    }

    // Update email or phone
    if ($newEmail) {
        $stmt = $pdo->prepare("UPDATE users SET email = ? WHERE id = ?");
        $stmt->execute([$newEmail, $userId]);
        $user['email'] = $newEmail;
        $message = 'Email address updated successfully.';
    } elseif ($newPhone) {
        $stmt = $pdo->prepare("UPDATE users SET phone = ? WHERE id = ?");
        $stmt->execute([$newPhone, $userId]);
        $user['phone'] = $newPhone;
        $message = 'Phone number updated successfully.';
    }

    // Generate new JWT with updated info
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

    send_success($message, [
        'token' => $newToken,
        'email' => $user['email'],
        'phone' => $user['phone']
    ]);

} catch (Exception $e) {
    error_log("Verify contact change error for user ID $userId: " . $e->getMessage());
    send_error('Failed to verify and update contact information.', [], 500);
}
