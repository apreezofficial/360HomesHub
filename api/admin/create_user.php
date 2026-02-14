<?php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../utils/db.php';
require_once __DIR__ . '/../../utils/response.php';
require_once __DIR__ . '/../../utils/jwt.php';

// Authenticate admin
$userData = JWTManager::authenticate();
if ($userData['role'] !== 'admin') {
    send_error('Access denied. Admin only.', [], 403);
}

$input = json_decode(file_get_contents('php://input'), true);

$email = $input['email'] ?? null;
$phone = $input['phone'] ?? null;
$password = $input['password'] ?? null;
$firstName = $input['first_name'] ?? '';
$lastName = $input['last_name'] ?? '';
$role = $input['role'] ?? 'guest'; // guest or host

if ((empty($email) && empty($phone)) || empty($password)) {
    send_error('Email/Phone and password are required.', [], 400);
}

$pdo = Database::getInstance();

// Check existence
if ($email) {
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) send_error('Email already exists.', [], 409);
}

if ($phone) {
    $stmt = $pdo->prepare("SELECT id FROM users WHERE phone = ?");
    $stmt->execute([$phone]);
    if ($stmt->fetch()) send_error('Phone already exists.', [], 409);
}

$passwordHash = password_hash($password, PASSWORD_DEFAULT);
$authProvider = $email ? 'email' : 'phone';

try {
    $stmt = $pdo->prepare("
        INSERT INTO users (email, phone, password_hash, first_name, last_name, role, auth_provider, status, onboarding_step) 
        VALUES (?, ?, ?, ?, ?, ?, ?, 'verified', 'completed')
    ");
    $stmt->execute([$email, $phone, $passwordHash, $firstName, $lastName, $role, $authProvider]);
    $userId = $pdo->lastInsertId();

    send_success('User created successfully by admin.', ['user_id' => $userId]);

} catch (Exception $e) {
    error_log("Admin create user error: " . $e->getMessage());
    send_error('Failed to create user.', [], 500);
}
