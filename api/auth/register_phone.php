<?php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../utils/db.php';
require_once __DIR__ . '/../../utils/response.php';
require_once __DIR__ . '/../../utils/otp.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_error('Invalid request method.', [], 405);
}

$input = json_decode(file_get_contents('php://input'), true);

$phone = $input['phone'] ?? '';
if (!$phone) {
    send_error('Phone number is required.', [], 400);
}

$pdo = Database::getInstance();

// Check if phone already exists
$stmt = $pdo->prepare("SELECT id FROM users WHERE phone = ?");
$stmt->execute([$phone]);
if ($stmt->fetch()) {
    send_error('Phone number already registered.', [], 409);
}

// Generate a temporary random password hash (user sets actual password after OTP)
$tempPassword = bin2hex(random_bytes(16));
$passwordHash = password_hash($tempPassword, PASSWORD_DEFAULT);

try {
    $pdo->beginTransaction();

    $avatar = "https://avatar.idolo.dev/" . $phone;

    $stmt = $pdo->prepare("INSERT INTO users (phone, password_hash, avatar, auth_provider, onboarding_step) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$phone, $passwordHash, $avatar, 'phone', 'otp']);
    $userId = $pdo->lastInsertId();

    $otpManager = new OtpManager();
    if (!$otpManager->sendOtp($userId, null, $phone)) {
        throw new Exception('Failed to send OTP.');
    }

    $pdo->commit();
    send_success('Registration successful. OTP sent to your phone for verification.', ['user_id' => $userId]);

} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Phone registration error: " . $e->getMessage());
    send_error('Registration failed: ' . $e->getMessage(), [], 500);
}
