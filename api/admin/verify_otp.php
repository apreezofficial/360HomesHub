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

$input = json_decode(file_get_contents('php://input'), true);

$email = $input['email'] ?? null;
$code = $input['otp'] ?? null;

if (empty($email) || empty($code)) {
    send_error('Email and OTP code are required.', [], 400);
}

$pdo = Database::getInstance();

// Fetch user
$stmt = $pdo->prepare("SELECT id, email, role FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user || $user['role'] !== 'admin') {
    send_error('Unauthorized access.', [], 403);
}

// Verify OTP
$otpManager = new OtpManager();
if ($otpManager->verifyOtp($user['id'], $code)) {
    // Generate JWT
    $jwtData = [
        'user_id' => $user['id'],
        'email' => $user['email'],
        'role' => $user['role'],
        'is_admin' => true
    ];
    $token = JWTManager::generateToken($jwtData);

    send_success('OTP verified. Login successful.', ['token' => $token]);
} else {
    send_error('Invalid or expired OTP code.', [], 401);
}
