<?php

require_once __DIR__ . '/../config.php'; // CORS and common API setup
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../utils/db.php'; // Database connection
require_once __DIR__ . '/../../utils/response.php'; // JSON response handler
require_once __DIR__ . '/../../utils/jwt.php'; // JWT authentication
require_once __DIR__ . '/../../api/notifications/notify.php'; // Notification helper

// --- JWT Authentication ---
$userData = JWTManager::authenticate();
$guest_id = $userData['user_id'] ?? null;

if (!$guest_id) {
    send_error("Unauthorized. Invalid or missing token.", [], 401);
}

// --- Input Validation ---
$input = json_decode(file_get_contents('php://input'), true);
$booking_id = $input['booking_id'] ?? null;
$payment_gateway = $input['payment_gateway'] ?? null;

if (!$booking_id) {
    send_error("Missing required field: booking_id.");
}

// --- Database Operations ---
try {
    $pdo = get_db_connection(); // Get database connection

    // Fetch booking details to verify status and ownership
    $stmt = $pdo->prepare("
        SELECT b.id, b.status, b.guest_id, b.host_id, b.total_amount, b.property_id, p.name as property_name
        FROM bookings b
        JOIN properties p ON b.property_id = p.id
        WHERE b.id = :booking_id
    ");
    $stmt->bindParam(':booking_id', $booking_id, PDO::PARAM_INT);
    $stmt->execute();
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$booking) {
        send_error("Booking not found.", [], 404);
    }

    // --- Authorization Check ---
    if ((int)$booking['guest_id'] !== (int)$guest_id) {
        send_error("Forbidden. You are not the guest of this booking.", [], 403);
    }

    // --- Status Check ---
    if ($booking['status'] !== 'approved') {
        send_error("Booking is not in an approved state. Current status: " . $booking['status'], [], 409);
    }

    // --- Payment Gateway Integration ---
    if ($payment_gateway === 'paystack') {
        require_once __DIR__ . '/../../utils/paystack.php';
        $paystack = new PaystackService();
        
        $email = $userData['email'] ?? 'guest@example.com'; 
        if (!isset($userData['email'])) {
             $uStmt = $pdo->prepare("SELECT email FROM users WHERE id = ?");
             $uStmt->execute([$guest_id]);
             $email = $uStmt->fetchColumn();
        }

        $metadata = [
            'booking_id' => (int)$booking_id,
            'guest_id' => (int)$guest_id,
            'host_id' => (int)$booking['host_id'],
            'property_id' => (int)$booking['property_id']
        ];

        $data = $paystack->initializeTransaction($email, $booking['total_amount'], $metadata);

        if ($data) {
            $checkout_url = $data['authorization_url'];
        } else {
            send_error("Failed to initialize Paystack transaction.", [], 502);
        }

    } elseif ($payment_gateway === 'flutterwave') {
        require_once __DIR__ . '/../../utils/flutterwave.php';
        $flutterwave = new FlutterwaveService();

        $email = $userData['email'] ?? 'guest@example.com';
         if (!isset($userData['email'])) {
             $uStmt = $pdo->prepare("SELECT email FROM users WHERE id = ?");
             $uStmt->execute([$guest_id]);
             $email = $uStmt->fetchColumn();
        }

        $tx_ref = "360HB-" . time() . "-" . $booking_id;
        $metadata = [
            'booking_id' => (int)$booking_id,
            'user_id' => (int)$guest_id
        ];

        $data = $flutterwave->initializeTransaction($email, $booking['total_amount'], $tx_ref, $metadata);

        if ($data) {
            $checkout_url = $data['link'];
        } else {
             send_error("Failed to initialize Flutterwave transaction.", [], 502);
        }

    } else {
        send_error("Invalid payment gateway selected.", [], 400);
    }

    // --- Send Notifications ---
    sendNotification($guest_id, "Proceed to Payment", "Your booking for '" . $booking['property_name'] . "' has been approved. Please complete the payment to confirm your reservation.", 'important');

    $admin_user_id = 1;
    sendNotification($admin_user_id, "Booking Checkout Initiated", "Guest " . $guest_id . " has initiated checkout for booking ID " . $booking_id . ".", 'important');

    // Prepare response data
    $response_data = [
        'booking_id' => (int)$booking_id,
        'total_amount' => round((float)$booking['total_amount'], 2),
        'currency' => 'NGN',
        'payment_gateway' => $payment_gateway,
        'checkout_url' => $checkout_url,
        'message' => 'Checkout initiated. Please proceed to payment.'
    ];

    send_success("Checkout initiated. Please proceed to payment.", $response_data);

} catch (PDOException $e) {
    error_log("Database error during checkout for booking " . $booking_id . ": " . $e->getMessage());
    send_error("Database error. Could not initiate checkout.", [], 500);
} catch (Exception $e) {
    error_log("General error during checkout for booking " . $booking_id . ": " . $e->getMessage());
    send_error("An unexpected error occurred during checkout initiation.", [], 500);
}
