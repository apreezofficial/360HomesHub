<?php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../utils/db.php';
require_once __DIR__ . '/../../utils/response.php';
require_once __DIR__ . '/../../utils/jwt.php';

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

if (!$user || !password_verify($password, $user['password_hash'])) {
    send_error('Invalid credentials.', [], 401);
}

// Check if the user has the 'admin' role
if ($user['role'] !== 'admin') {
    send_error('Access denied. Not an admin user.', [], 403);
}

// Ensure admin users have completed onboarding for login
if ($user['onboarding_step'] !== 'completed') {
    send_error('Admin account onboarding not completed.', ['onboarding_step' => $user['onboarding_step']], 403);
}

$jwtData = [
    'user_id' => $user['id'],
    'email' => $user['email'],
    'role' => $user['role'],
    'is_admin' => true // Explicitly mark as admin in JWT
];
$token = JWTManager::generateToken($jwtData);

send_success('Admin login successful.', ['token' => $token, 'role' => $user['role']]);
