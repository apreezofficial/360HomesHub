<?php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../utils/db.php';
require_once __DIR__ . '/../../utils/response.php';
require_once __DIR__ . '/../../utils/paystack.php';
require_once __DIR__ . '/../../utils/flutterwave.php';
require_once __DIR__ . '/../notifications/notify.php';
require_once __DIR__ . '/../../utils/activity_logger.php';

// This is usually a GET request from the redirect, or can be a POST for manual verification
$gateway = $_GET['gateway'] ?? null;
$reference = $_GET['reference'] ?? $_GET['transaction_id'] ?? $_GET['trxref'] ?? null;

if (!$gateway || !$reference) {
    // Redirect to error page instead of showing JSON
    $app_url = defined('APP_URL') ? APP_URL : 'http://localhost/360HomesHub';
    header("Location: {$app_url}/payment_result.php?status=error&message=" . urlencode("Gateway and reference are required"));
    exit;
}

$status = 'failed';
$payment_data = null;
$booking_details = null; // Initialize to avoid undefined variable error

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
        $pdo = get_db_connection();
        $bookingId = $payment_data['metadata']['booking_id'] ?? null;
        
        if ($bookingId) {
            // Fetch booking details FIRST
            $stmt = $pdo->prepare("SELECT guest_id, host_id, total_amount, property_id FROM bookings WHERE id = ?");
            $stmt->execute([$bookingId]);
            $booking_details = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$booking_details) {
                ActivityLogger::logPayment(
                    null,
                    'verification_failed',
                    $bookingId,
                    ['reason' => 'booking_not_found', 'reference' => $reference, 'gateway' => $gateway]
                );
                
                // Redirect to error page
                $app_url = defined('APP_URL') ? APP_URL : 'http://localhost/360HomesHub';
                header("Location: {$app_url}/payment_result.php?status=error&message=" . urlencode("Booking not found"));
                exit;
            }
            
            // Update booking status to confirmed/paid
            $updateStmt = $pdo->prepare("UPDATE bookings SET status = 'paid' WHERE id = ?");
            $updateStmt->execute([$bookingId]);

            // Log successful payment verification
            ActivityLogger::logPayment(
                $booking_details['guest_id'],
                'verified',
                $bookingId,
                [
                    'reference' => $reference,
                    'gateway' => $gateway,
                    'amount' => $booking_details['total_amount'],
                    'host_id' => $booking_details['host_id'],
                    'property_id' => $booking_details['property_id']
                ]
            );

            // Insert Transaction Record
            try {
                $txStmt = $pdo->prepare("INSERT INTO transactions (user_id, booking_id, amount, reference, gateway, status, type, created_at) VALUES (?, ?, ?, ?, ?, 'success', 'booking', NOW())");
                $txStmt->execute([
                    $booking_details['guest_id'],
                    $bookingId,
                    $booking_details['total_amount'],
                    $reference,
                    $gateway
                ]);
            } catch (Exception $e) {
                // Log but don't fail the verification if transaction log fails
                error_log("Failed to log transaction: " . $e->getMessage());
                ActivityLogger::log(
                    $booking_details['guest_id'],
                    'transaction_log_failed',
                    "Failed to log transaction record",
                    'transaction',
                    null,
                    ['error' => $e->getMessage(), 'booking_id' => $bookingId]
                );
            }

            // Notify Guest
            sendNotification($booking_details['guest_id'], "Payment Successful", "Your payment for booking #$bookingId was successful. Your stay is now confirmed!", "important");
            
            // Notify Host
            sendNotification($booking_details['host_id'], "New Confirmed Booking", "Booking #$bookingId has been paid for and is now confirmed.", "important");
            
            // Notify Admin
            $admin_id = 1;
            sendNotification($admin_id, "Payment Confirmed", "Payment for booking #$bookingId has been confirmed via " . ucfirst($gateway) . ". Amount: ₦" . number_format($booking_details['total_amount'], 2), "important");

            // Redirect to success page instead of showing JSON
            $app_url = defined('APP_URL') ? APP_URL : 'http://localhost/360HomesHub';
            header("Location: {$app_url}/payment_result.php?status=success&booking_id={$bookingId}&amount=" . $booking_details['total_amount']);
            exit;
        } else {
            ActivityLogger::log(
                null,
                'payment_verification_no_booking_id',
                "Payment verified but no booking ID in metadata",
                'payment',
                null,
                ['reference' => $reference, 'gateway' => $gateway]
            );
            
            // Redirect to error page
            $app_url = defined('APP_URL') ? APP_URL : 'http://localhost/360HomesHub';
            header("Location: {$app_url}/payment_result.php?status=error&message=" . urlencode("Booking ID missing"));
            exit;
        }
    } catch (Exception $e) {
        error_log("Payment verification error: " . $e->getMessage());
        ActivityLogger::log(
            $booking_details['guest_id'] ?? null,
            'payment_verification_error',
            "Error during payment verification",
            'payment',
            $bookingId ?? null,
            ['error' => $e->getMessage(), 'reference' => $reference]
        );
        
        // Redirect to error page
        $app_url = defined('APP_URL') ? APP_URL : 'http://localhost/360HomesHub';
        header("Location: {$app_url}/payment_result.php?status=error&message=" . urlencode("Verification failed"));
        exit;
    }
} else {
    ActivityLogger::log(
        null,
        'payment_verification_failed',
        "Payment verification failed",
        'payment',
        null,
        ['reference' => $reference, 'gateway' => $gateway, 'status' => $status]
    );
    
    // Redirect to error page
    $app_url = defined('APP_URL') ? APP_URL : 'http://localhost/360HomesHub';
    header("Location: {$app_url}/payment_result.php?status=failed&message=" . urlencode("Payment verification failed"));
    exit;
}
