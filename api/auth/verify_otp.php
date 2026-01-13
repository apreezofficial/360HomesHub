<?php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../utils/db.php';
require_once __DIR__ . '/../../utils/response.php';
require_once __DIR__ . '/../../utils/otp.php';
require_once __DIR__ . '/../../utils/jwt.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_error('Invalid request method.', [], 405);
}

$input = json_decode(file_get_contents('php://input'), true);

$userId = $input['user_id'] ?? null;
$otpCode = $input['otp_code'] ?? '';

if (!is_numeric($userId) || empty($otpCode)) {
    send_error('User ID and OTP code are required.', [], 400);
}

$pdo = Database::getInstance();
$otpManager = new OtpManager();

try {
    if (!$otpManager->verifyOtp($userId, $otpCode)) {
        send_error('Invalid or expired OTP.', [], 400);
    }

    // Get user details
    $stmt = $pdo->prepare("SELECT id, email, phone, auth_provider, onboarding_step FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    if (!$user) {
        send_error('User not found.', [], 404);
    }

    $currentOnboardingStep = $user['onboarding_step'];
    $newOnboardingStep = $currentOnboardingStep; // Default to current

    // Determine next onboarding step based on auth provider and current step
    if ($user['auth_provider'] === 'email' || $user['auth_provider'] === 'phone') {
        if ($currentOnboardingStep === 'otp') {
            $newOnboardingStep = 'password';
        }
    } elseif ($user['auth_provider'] === 'google') {
        if ($currentOnboardingStep === 'otp' || $currentOnboardingStep === 'password') { // For Google, password might be set in google_auth.php
            $newOnboardingStep = 'profile'; // Directly to profile after Google login/password set
        }
    }

    // Update onboarding step if changed
    if ($newOnboardingStep !== $currentOnboardingStep) {
        $stmt = $pdo->prepare("UPDATE users SET onboarding_step = ? WHERE id = ?");
        $stmt->execute([$newOnboardingStep, $userId]);
    }

    // Generate JWT token
    $jwtData = [
        'user_id' => $user['id'],
        'auth_provider' => $user['auth_provider'],
        'email' => $user['email'],
        'phone' => $user['phone'],
        'onboarding_step' => $newOnboardingStep
    ];
    $token = JWTManager::generateToken($jwtData);

    send_success('OTP verified successfully.', ['token' => $token, 'onboarding_step' => $newOnboardingStep]);

} catch (Exception $e) {
    error_log("OTP verification error: " . $e->getMessage());
    send_error('OTP verification failed.', [], 500);
}
