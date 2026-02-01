<?php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../utils/paystack.php';
require_once __DIR__ . '/../../utils/flutterwave.php';

$userData = JWTManager::authenticate();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_error('Invalid request method.', [], 405);
}

$input = json_decode(file_get_contents('php://input'), true);

$amount = $input['amount'] ?? null;
$gateway = $input['gateway'] ?? 'paystack'; // Default to paystack
$booking_id = $input['booking_id'] ?? null;

if (!$amount || !$booking_id) {
    send_error('Amount and booking ID are required.', [], 400);
}

$email = $userData['email'] ?? 'guest@example.com';
$metadata = ['booking_id' => $booking_id, 'user_id' => $userData['user_id']];

if ($gateway === 'paystack') {
    $paystack = new PaystackService();
    $data = $paystack->initializeTransaction($email, $amount, $metadata);
    
    if ($data) {
        send_success('Paystack checkout initialized.', [
            'checkout_url' => $data['authorization_url'],
            'reference' => $data['reference']
        ]);
    }
} elseif ($gateway === 'flutterwave') {
    $flutterwave = new FlutterwaveService();
    $tx_ref = "360HB-" . time() . "-" . $booking_id;
    $data = $flutterwave->initializeTransaction($email, $amount, $tx_ref, $metadata);
    
    if ($data) {
        send_success('Flutterwave checkout initialized.', [
            'checkout_url' => $data['link'],
            'tx_ref' => $tx_ref
        ]);
    }
} else {
    send_error('Invalid payment gateway selected.', [], 400);
}

send_error('Failed to initialize payment gateway.');
