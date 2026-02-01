<?php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../utils/paystack.php';
require_once __DIR__ . '/../../utils/flutterwave.php';

// This is usually a GET request from the redirect, or can be a POST for manual verification
$gateway = $_GET['gateway'] ?? null;
$reference = $_GET['reference'] ?? $_GET['transaction_id'] ?? null;

if (!$gateway || !$reference) {
    send_error('Gateway and reference are required for verification.', [], 400);
}

$status = 'failed';
$payment_data = null;

if ($gateway === 'paystack') {
    $paystack = new PaystackService();
    $payment_data = $paystack->verifyTransaction($reference);
    if ($payment_data && $payment_data['status'] === 'success') {
        $status = 'success';
    }
} elseif ($gateway === 'flutterwave') {
    $flutterwave = new FlutterwaveService();
    $payment_data = $flutterwave->verifyTransaction($reference);
    if ($payment_data && $payment_data['status'] === 'successful') {
        $status = 'success';
    }
}

if ($status === 'success') {
    // Here you would typically update the booking status in the database
    send_success('Payment verified successfully.', ['payment_details' => $payment_data]);
} else {
    send_error('Payment verification failed.', ['payment_details' => $payment_data]);
}
