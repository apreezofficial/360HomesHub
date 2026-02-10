<?php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../utils/paystack.php';
require_once __DIR__ . '/../../utils/flutterwave.php';
require_once __DIR__ . '/../notifications/notify.php';

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

            // Notify Admin
            $admin_id = 1; // Assuming 1 is the primary admin
            sendNotification($admin_id, "Payment Confirmed", "Payment for booking #$bookingId has been confirmed via " . ucfirst($gateway) . ".", "important");

            // Insert Transaction Record
            try {
                $txStmt = $pdo->prepare("INSERT INTO transactions (user_id, booking_id, amount, reference, gateway, status, type, created_at) VALUES (?, ?, ?, ?, ?, 'success', 'booking', NOW())");
                // Need to fetch user_id (guest_id) first if not available in $booking_details yet (which is below).
                // But $booking_details is fetched BELOW. Let's move it UP.
                
                $stmt = $pdo->prepare("SELECT guest_id, host_id, total_amount FROM bookings WHERE id = ?");
                $stmt->execute([$bookingId]);
                $booking_details = $stmt->fetch();
                
                if ($booking_details) {
                    $txStmt->execute([
                        $booking_details['guest_id'],
                        $bookingId,
                        $booking_details['total_amount'], // Use amount from booking
                        $reference,
                        $gateway
                    ]);
                }
            } catch (Exception $e) {
                // Log but don't fail the verification if transaction log fails
                error_log("Failed to log transaction: " . $e->getMessage());
            }

            if ($booking_details) {
                // Notify Guest
                sendNotification($booking_details['guest_id'], "Payment Successful", "Your payment for booking #$bookingId was successful. Your stay is now confirmed!", "normal");
                
                // Notify Host
                sendNotification($booking_details['host_id'], "New Confirmed Booking", "Booking #$bookingId has been paid for and is now confirmed.", "important");
            }

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
