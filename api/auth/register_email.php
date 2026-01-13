<?php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../utils/db.php';
require_once __DIR__ . '/../utils/response.php';
require_once __DIR__ . '/../utils/otp.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_error('Invalid request method.', [], 405);
}

$input = json_decode(file_get_contents('php://input'), true);

$email = filter_var($input['email'] ?? '', FILTER_VALIDATE_EMAIL);
$password = $input['password'] ?? '';

if (!$email || empty($password)) {
    send_error('Email and password are required.', [], 400);
}

if (strlen($password) < 8) {
    send_error('Password must be at least 8 characters long.', [], 400);
}

$pdo = Database::getInstance();

// Check if email already exists
$stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
$stmt->execute([$email]);
if ($stmt->fetch()) {
    send_error('Email already registered.', [], 409);
}

$passwordHash = password_hash($password, PASSWORD_DEFAULT);

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("INSERT INTO users (email, password_hash, auth_provider, onboarding_step) VALUES (?, ?, ?, ?)");
    $stmt->execute([$email, $passwordHash, 'email', 'otp']);
    $userId = $pdo->lastInsertId();

    $otpManager = new OtpManager();
    if (!$otpManager->sendOtp($userId, $email, null)) {
        throw new Exception('Failed to send OTP.');
    }

    $pdo->commit();
    send_success('Registration successful. OTP sent to your email for verification.', ['user_id' => $userId]);

} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Email registration error: " . $e->getMessage());
    send_error('Registration failed: ' . $e->getMessage(), [], 500);
}
