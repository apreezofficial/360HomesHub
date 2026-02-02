<?php

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../config/env.php';
require_once __DIR__ . '/../../../utils/response.php';
require_once __DIR__ . '/../../../utils/jwt.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_error('Invalid request method.', [], 405);
}

// Authenticate user
$userData = JWTManager::authenticate();
$userId = $userData['user_id'] ?? null;

if (!$userId) {
    send_error('Authentication failed.', [], 401);
}

// Get input data
$data = json_decode(file_get_contents('php://input'), true);
$basePrice = isset($data['price']) ? (float)$data['price'] : 0;

if ($basePrice <= 0) {
    send_error('Price must be greater than zero.', [], 400);
}

// Fee percentages (can be moved to config/env.php)
$guestServiceFeePercent = 12; // Guest pays 12% on top of base price
$hostProcessingFeePercent = 3;   // Host pays 3% on base price

$guestServiceFee = round($basePrice * ($guestServiceFeePercent/100), 2);
$totalGuestPrice = $basePrice + $guestServiceFee;

$hostProcessingFee = round($basePrice * ($hostProcessingFeePercent/100), 2);
$hostEarning = $basePrice - $hostProcessingFee;

send_success('Pricing breakdown calculated.', [
    'base_price' => $basePrice,
    'guest_service_fee' => $guestServiceFee,
    'total_guest_price' => $totalGuestPrice,
    'processing_fee' => $hostProcessingFee,
    'host_earning' => $hostEarning,
    'currency' => 'NGN' // Default currency, could be dynamic
]);
