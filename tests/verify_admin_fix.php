<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../utils/db.php';

use GuzzleHttp\Client;

$pdo = Database::getInstance();
$client = new Client(['base_uri' => 'http://localhost/360HomesHub/', 'http_errors' => false]);

echo "Running verification for Admin Onboarding Fix...\n";

// 1. Prepare test user: Ensure admin@example.com exists and set onboarding_step to 'otp'
$email = 'admin@example.com';
$password = 'password123'; // From seed.sql

$stmt = $pdo->prepare("SELECT id, role FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user || $user['role'] !== 'admin') {
    die("Error: Admin user admin@example.com not found. Please run seed.sql first.\n");
}

$userId = $user['id'];

$stmt = $pdo->prepare("UPDATE users SET onboarding_step = 'otp' WHERE id = ?");
$stmt->execute([$userId]);

echo "Test admin account reset to 'otp'.\n";

// 2. Attempt login
$response = $client->post('api/admin/login.php', [
    'json' => [
        'email' => $email,
        'password' => $password
    ]
]);

$statusCode = $response->getStatusCode();
$body = json_decode($response->getBody()->getContents(), true);

if ($statusCode === 200 && $body['success']) {
    echo "Login successful!\n";
    
    // 3. Verify onboarding_step in DB
    $stmt = $pdo->prepare("SELECT onboarding_step FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $newStep = $stmt->fetchColumn();
    
    if ($newStep === 'completed') {
        echo "SUCCESS: Onboarding step automatically updated to 'completed'.\n";
    } else {
        echo "FAILURE: Onboarding step is still '$newStep'.\n";
    }
} else {
    echo "FAILURE: Login failed with status $statusCode. Message: " . ($body['message'] ?? 'No message') . "\n";
}
