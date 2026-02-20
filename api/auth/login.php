<?php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../utils/activity_logger.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_error('Invalid request method.', [], 405);
}

$input = json_decode(file_get_contents('php://input'), true);

$email = $input['email'] ?? null;
$phone = $input['phone'] ?? null;
$password = $input['password'] ?? '';

if (empty($password) || (empty($email) && empty($phone))) {
    send_error('Email or phone, and password are required.', [], 400);
}

$pdo = Database::getInstance();
$user = null;

if (!empty($email)) {
    $stmt = $pdo->prepare("SELECT id, email, phone, password_hash, auth_provider, onboarding_step, status, message_disabled, booking_disabled, role, avatar FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
} elseif (!empty($phone)) {
    $stmt = $pdo->prepare("SELECT id, email, phone, password_hash, auth_provider, onboarding_step, status, message_disabled, booking_disabled, role, avatar FROM users WHERE phone = ?");
    $stmt->execute([$phone]);
    $user = $stmt->fetch();
}

if (!$user || !password_verify($password, $user['password_hash'])) {
    // Log failed login attempt
    ActivityLogger::log(
        null,
        'login_failed',
        "Failed login attempt",
        'user',
        null,
        ['email' => $email, 'phone' => $phone, 'reason' => 'invalid_credentials']
    );
    send_error('Invalid credentials.', [], 401);
}

// Check if onboarding is completed before allowing full login (except for admin login)
// If onboarding_step is 'otp', they need to verify OTP first.
if ($user['onboarding_step'] === 'otp') {
    send_error('Please verify your OTP first.', ['onboarding_step' => 'otp', 'user_id' => $user['id']], 403);
}

$jwtData = [
    'user_id' => $user['id'],
    'auth_provider' => $user['auth_provider'],
    'email' => $user['email'],
    'phone' => $user['phone'],
    'onboarding_step' => $user['onboarding_step'],
    'status' => $user['status'],
    'message_disabled' => (bool)$user['message_disabled'],
    'booking_disabled' => (bool)$user['booking_disabled'],
    'role' => $user['role'],
    'avatar' => $user['avatar']
];
$token = JWTManager::generateToken($jwtData);

// Stamp last_login
$pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?")->execute([$user['id']]);

// Log successful login
ActivityLogger::logUser(
    $user['id'],
    'logged_in',
    [
        'email' => $user['email'],
        'phone' => $user['phone'],
        'auth_provider' => $user['auth_provider']
    ]
);

send_success('Login successful.', [
    'token' => $token, 
    'onboarding_step' => $user['onboarding_step'], 
    'status' => $user['status'],
    'message_disabled' => (bool)$user['message_disabled'],
    'booking_disabled' => (bool)$user['booking_disabled'],
    'role' => $user['role']
]);

