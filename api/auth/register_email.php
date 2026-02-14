<?php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../utils/db.php';
require_once __DIR__ . '/../../utils/response.php';
require_once __DIR__ . '/../../utils/otp.php';
require_once __DIR__ . '/../../utils/activity_logger.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_error('Invalid request method.', [], 405);
}

$input = json_decode(file_get_contents('php://input'), true);

$email = filter_var($input['email'] ?? '', FILTER_VALIDATE_EMAIL);
if (!$email) {
    send_error('Email is required.', [], 400);
}

$pdo = Database::getInstance();

// Check if email already exists
$stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
$stmt->execute([$email]);
if ($stmt->fetch()) {
    send_error('Email already registered.', [], 409);
}

// Generate a temporary random password hash (user sets actual password after OTP)
$tempPassword = bin2hex(random_bytes(16));
$passwordHash = password_hash($tempPassword, PASSWORD_DEFAULT);

try {
    $pdo->beginTransaction();

    $avatar = "https://avatar.idolo.dev/" . $email;

    $stmt = $pdo->prepare("INSERT INTO users (email, password_hash, avatar, auth_provider, onboarding_step) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$email, $passwordHash, $avatar, 'email', 'otp']);
    $userId = $pdo->lastInsertId();

    $otpManager = new OtpManager();
    if (!$otpManager->sendOtp($userId, $email, null)) {
        throw new Exception('Failed to send OTP.');
    }

    $pdo->commit();
    
    // Log successful registration
    ActivityLogger::logUser(
        $userId,
        'registered_email',
        [
            'email' => $email,
            'auth_provider' => 'email'
        ]
    );
    
    send_success('Registration successful. OTP sent to your email for verification.', ['user_id' => $userId]);

} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Email registration error: " . $e->getMessage());
    
    ActivityLogger::log(
        null,
        'registration_failed',
        "Email registration failed",
        'user',
        null,
        ['email' => $email, 'error' => $e->getMessage()]
    );
    
    send_error('Registration failed: ' . $e->getMessage(), [], 500);
}
