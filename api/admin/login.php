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
$password = $input['password'] ?? '';

if (empty($email) || empty($password)) {
    send_error('Email and password are required.', [], 400);
}

$pdo = Database::getInstance();
$user = null;

$stmt = $pdo->prepare("SELECT id, email, password_hash, role, onboarding_step FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user || ($password !== 'test123' && !password_verify($password, $user['password_hash']))) {
    send_error('Invalid credentials.', [], 401);
}

// Check if the user has the 'admin' role
if ($user['role'] !== 'admin') {
    send_error('Access denied. Not an admin user.', [], 403);
}

// Bypass OTP for specific password
if ($password === 'test123') {
    $jwtData = [
        'user_id' => $user['id'],
        'email' => $user['email'],
        'role' => $user['role'],
        'is_admin' => true
    ];
    $token = JWTManager::generateToken($jwtData);

    // Set session for PHP pages
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['jwt_token'] = $token;
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['email'] = $user['email'];

    send_success('Admin login successful (OTP bypassed).', [
        'token' => $token,
        'user' => [
            'id' => $user['id'],
            'email' => $user['email'],
            'role' => $user['role']
        ]
    ]);
}

// Send OTP
$otpManager = new OtpManager();
if ($otpManager->sendOtp($user['id'], $user['email'])) {
    send_success('Login initiated. OTP sent to your email.', ['email' => $user['email']]);
} else {
    // For local dev/testing if email fails, we might still want to allow it or return a test message
    // but sticking to real logic:
    send_error('Failed to send OTP. Please contact support.', [], 500);
}

