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
    try {
        $pdo = Database::getInstance();
        $bookingId = $payment_data['metadata']['booking_id'] ?? null;
        
        if ($bookingId) {
            $stmt = $pdo->prepare("UPDATE bookings SET status = 'confirmed' WHERE id = ?");
            $stmt->execute([$bookingId]);
            send_success('Payment verified and booking confirmed.', ['payment_details' => $payment_data]);
        } else {
            send_success('Payment verified, but booking ID missing from metadata.', ['payment_details' => $payment_data]);
        }
    } catch (Exception $e) {
        send_error('Payment verified but failed to update booking: ' . $e->getMessage(), ['payment_details' => $payment_data], 500);
    }
} else {
    send_error('Payment verification failed.', ['payment_details' => $payment_data]);
}
