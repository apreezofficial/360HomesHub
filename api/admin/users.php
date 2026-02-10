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

try {
    $pdo = Database::getInstance();
    
    // Fetch all users with basic info
    $stmt = $pdo->prepare("SELECT id, first_name, last_name, email, phone, role, is_verified, onboarding_step, created_at, profile_pic FROM users ORDER BY created_at DESC");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    send_success('Users retrieved successfully.', ['users' => $users]);

} catch (Exception $e) {
    error_log("Admin users error: " . $e->getMessage());
    send_error('Failed to retrieve users.', [], 500);
}
