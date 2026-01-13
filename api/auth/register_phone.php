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
$password = $input['password'] ?? '';

// Basic phone number validation (can be more robust)
if (!preg_match('/^\+[1-9]\d{1,14}$/', $phone)) {
    send_error('Invalid phone number format. Please include country code (e.g., +1234567890).', [], 400);
}

if (empty($password) || strlen($password) < 8) {
    send_error('Password must be at least 8 characters long.', [], 400);
}

$pdo = Database::getInstance();

// Check if phone already exists
$stmt = $pdo->prepare("SELECT id FROM users WHERE phone = ?");
$stmt->execute([$phone]);
if ($stmt->fetch()) {
    send_error('Phone number already registered.', [], 409);
}

$passwordHash = password_hash($password, PASSWORD_DEFAULT);

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("INSERT INTO users (phone, password_hash, auth_provider, onboarding_step) VALUES (?, ?, ?, ?)");
    $stmt->execute([$phone, $passwordHash, 'phone', 'otp']);
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
