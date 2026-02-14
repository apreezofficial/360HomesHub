<?php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../utils/db.php';
require_once __DIR__ . '/../../utils/paystack.php';
require_once __DIR__ . '/../../utils/flutterwave.php';
require_once __DIR__ . '/../notifications/notify.php';
require_once __DIR__ . '/../../utils/activity_logger.php';

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit();
}

$input = @file_get_contents("php://input");
$event = json_decode($input, true);

// Identify Gateway
$gateway = null;
if (isset($_SERVER['HTTP_X_PAYSTACK_SIGNATURE'])) {
    $gateway = 'paystack';
} elseif (isset($_SERVER['HTTP_VERIF_HASH'])) {
    $gateway = 'flutterwave';
}

if (!$gateway) {
    // Log as warning but don't fail, could be a test or manual hit
    error_log("Webhook request from unknown source or missing headers");
    ActivityLogger::log(null, 'webhook_unknown_gateway', "Webhook request from unknown source", 'webhook', null, []);
    http_response_code(400);
    exit();
}

// Verify Signature
$isVerified = false;
$paystackService = new PaystackService();
$flutterwaveService = new FlutterwaveService();

if ($gateway === 'paystack') {
    if ($paystackService->verifyWebhook()) {
        $isVerified = true;
    }
} elseif ($gateway === 'flutterwave') {
    if ($flutterwaveService->verifyWebhook()) {
        $isVerified = true;
    }
}

if (!$isVerified) {
    error_log("Webhook signature verification failed for {$gateway}");
    ActivityLogger::log(null, 'webhook_signature_failed', "Webhook signature verification failed", 'webhook', null, ['gateway' => $gateway]);
    http_response_code(401);
    exit();
}

// Process Event
try {
    if ($gateway === 'paystack') {
        if (($event['event'] ?? '') === 'charge.success') {
            $data = $event['data'];
            $reference = $data['reference'];
            $metadata = $data['metadata'] ?? [];
            $booking_id = $metadata['booking_id'] ?? null;
            $amount = ($data['amount'] ?? 0) / 100; // Paystack sends kobo
            
            if ($booking_id) {
                processPaymentSuccess($booking_id, $reference, $amount, 'paystack', $data);
            }
        }
    } elseif ($gateway === 'flutterwave') {
        // Flutterwave sends diff structures. We verified signature, so we trust it.
        // Usually event is 'charge.completed' or we check status 'successful'
        if (($event['status'] ?? '') === 'successful' || ($event['event'] ?? '') === 'charge.completed') {
            $data = $event['data'] ?? $event;
            $reference = $data['tx_ref'] ?? $data['reference'] ?? null;
            
            // For Flutterwave, reliable metadata extraction often requires verifying the transaction ID
            // because the webhook payload might be slim or nested differently.
            // However, to be fast, we check if metadata is present.
            $metadata = $data['meta'] ?? $event['meta'] ?? [];
            
            // If we don't have booking_id, we MUST verify transaction to get it
            if (empty($metadata['booking_id']) && !empty($data['id'])) {
                 $verifiedFn = $flutterwaveService->verifyTransaction($data['id']);
                 if ($verifiedFn && $verifiedFn['status'] === 'successful') {
                     $metadata = $verifiedFn['meta']; // Metadata from verify endpoint
                     $amount = $verifiedFn['amount'];
                     $reference = $verifiedFn['tx_ref'];
                 }
            }
            
            $booking_id = $metadata['booking_id'] ?? null;
            $amount = $data['amount'] ?? 0;

            if ($booking_id) {
                 processPaymentSuccess($booking_id, $reference, $amount, 'flutterwave', $data);
            }
        }
    }
    
    http_response_code(200);

} catch (Exception $e) {
    error_log("Webhook Error: " . $e->getMessage());
    ActivityLogger::log(null, 'webhook_error', "Error processing webhook", 'webhook', null, ['error' => $e->getMessage()]);
    http_response_code(500); 
}

function processPaymentSuccess($bookingId, $reference, $amount, $gateway, $paymentData) {
    $pdo = get_db_connection();

    // Check if booking exists
    $stmt = $pdo->prepare("SELECT guest_id, host_id, total_amount, status, property_id FROM bookings WHERE id = ?");
    $stmt->execute([$bookingId]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$booking) {
         ActivityLogger::logPayment(null, 'webhook_failed_booking_not_found', $bookingId, ['reference' => $reference, 'gateway' => $gateway]);
         return;
    }

    // Check if already paid
    if ($booking['status'] === 'paid' || $booking['status'] === 'confirmed') {
         // It's a duplicate success event, which is common. Log info but don't error.
         ActivityLogger::logPayment($booking['guest_id'], 'webhook_duplicate_event', $bookingId, ['reference' => $reference, 'status' => $booking['status']]);
         return;
    }
    
    // Verify amount (optional but recommended)
    if ((float)$amount < (float)$booking['total_amount']) {
         ActivityLogger::logPayment($booking['guest_id'], 'webhook_amount_mismatch', $bookingId, ['expected' => $booking['total_amount'], 'paid' => $amount]);
         return; 
    }

    // Update Transaction
    try {
        $pdo->beginTransaction();

        $updateStmt = $pdo->prepare("UPDATE bookings SET status = 'paid' WHERE id = ?");
        $updateStmt->execute([$bookingId]);

        // Insert Transaction Record if not exists
        $checkTx = $pdo->prepare("SELECT id FROM transactions WHERE reference = ? AND gateway = ?");
        $checkTx->execute([$reference, $gateway]);
        if (!$checkTx->fetch()) {
             $txStmt = $pdo->prepare("INSERT INTO transactions (user_id, booking_id, amount, reference, gateway, status, type, created_at) VALUES (?, ?, ?, ?, ?, 'success', 'booking', NOW())");
             $txStmt->execute([
                $booking['guest_id'],
                $bookingId,
                $amount,
                $reference,
                $gateway
            ]);
        }

        $pdo->commit();

        // Log Success
        ActivityLogger::logPayment(
            $booking['guest_id'],
            'webhook_payment_verified',
            $bookingId,
            [
                'reference' => $reference,
                'gateway' => $gateway,
                'amount' => $amount
            ]
        );

        // Notifications
        // Guest
        sendNotification($booking['guest_id'], "Payment Received", "Your payment for booking #{$bookingId} has been confirmed!", "important");
        
        // Host
        sendNotification($booking['host_id'], "Booking Confirmed", "Payment received for booking #{$bookingId}. It is now confirmed.", "important");

        // Admin
        sendNotification(1, "Payment Confirmed via Webhook", "Booking #{$bookingId} payment confirmed via {$gateway}.", "normal");
        
        // --- SEND & LOG EMAILS (Guest, Host, Admin) ---
        require_once __DIR__ . '/../../utils/payment_email_helper.php';
        PaymentEmailHelper::sendPaymentEmails(
            $pdo, 
            $bookingId, 
            $amount, 
            $reference, 
            $gateway
        );

    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Webhook DB Transaction Error: " . $e->getMessage());
        ActivityLogger::logPayment($booking['guest_id'] ?? null, 'webhook_db_error', $bookingId, ['error' => $e->getMessage()]);
        throw $e;
    }
}
