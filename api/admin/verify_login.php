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
$otpCode = $input['otp'] ?? null;

if (empty($email) || empty($otpCode)) {
    send_error('Email and OTP code are required.', [], 400);
}

$pdo = Database::getInstance();
$stmt = $pdo->prepare("SELECT id, role, first_name, last_name, email FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user) {
    send_error('User not found.', [], 404);
}

// Verify OTP
$otpManager = new OtpManager();
if ($otpManager->verifyOtp($user['id'], $otpCode)) {
    // Generate JWT
    $token = JWTManager::generateToken([
        'user_id' => $user['id'],
        'email' => $user['email'],
        'role' => $user['role'] // Should check role=admin too if desired, but login prompt handles it
    ]);
    
    // Store in session if needed by old PHP pages, but frontend uses token
    session_start();
    $_SESSION['jwt_token'] = $token;
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['email'] = $user['email'];

    send_success('Login successful.', ['token' => $token, 'user' => $user]);
} else {
    send_error('Invalid or expired OTP.', [], 401);
}
?>
