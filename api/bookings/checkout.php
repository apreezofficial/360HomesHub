<?php

require_once __DIR__ . '/../config.php'; // CORS and common API setup
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../utils/db.php'; // Database connection
require_once __DIR__ . '/../../utils/response.php'; // JSON response handler
require_once __DIR__ . '/../../utils/jwt.php'; // JWT authentication
require_once __DIR__ . '/../../api/notifications/notify.php'; // Notification helper
require_once __DIR__ . '/../../utils/activity_logger.php'; // Activity logger

// --- JWT Authentication ---
$userData = JWTManager::authenticate();
$guest_id = $userData['user_id'] ?? null;

if (!$guest_id) {
    send_error("Unauthorized. Invalid or missing token.", [], 401);
}

// --- Input Validation ---
$input = json_decode(file_get_contents('php://input'), true);
$booking_id = $input['booking_id'] ?? null;
$payment_gateway = $input['payment_gateway'] ?? 'paystack'; // Default to paystack

if (!$booking_id) {
    send_error("Missing required field: booking_id.");
}

// --- Database Operations ---
try {
    $pdo = get_db_connection(); // Get database connection

    // Fetch booking details to verify status and ownership
    $stmt = $pdo->prepare("
        SELECT b.id, b.status, b.guest_id, b.host_id, b.total_amount, b.property_id, 
               b.rent_amount, b.caution_fee, b.service_fee, b.tax_amount,
               p.name as property_name, p.price as property_price
        FROM bookings b
        JOIN properties p ON b.property_id = p.id
        WHERE b.id = :booking_id
    ");
    $stmt->bindParam(':booking_id', $booking_id, PDO::PARAM_INT);
    $stmt->execute();
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$booking) {
        // Log failed checkout attempt
        ActivityLogger::log(
            $guest_id,
            'checkout_failed',
            "Failed checkout attempt - Booking not found",
            'booking',
            $booking_id,
            ['reason' => 'booking_not_found']
        );
        send_error("Booking not found.", [], 404);
    }

    // --- Authorization Check ---
    if ((int)$booking['guest_id'] !== (int)$guest_id) {
        // Log unauthorized access attempt
        ActivityLogger::log(
            $guest_id,
            'checkout_unauthorized',
            "Unauthorized checkout attempt for booking ID {$booking_id}",
            'booking',
            $booking_id,
            ['actual_guest_id' => $booking['guest_id']]
        );
        send_error("Forbidden. You are not the guest of this booking.", [], 403);
    }

    // --- Status Check ---
    if ($booking['status'] !== 'approved') {
        // Log invalid status checkout attempt
        ActivityLogger::log(
            $guest_id,
            'checkout_invalid_status',
            "Checkout attempt on non-approved booking ID {$booking_id}",
            'booking',
            $booking_id,
            ['current_status' => $booking['status']]
        );
        send_error("Booking is not in an approved state. Current status: " . $booking['status'], [], 409);
    }

    // --- Calculate Total Amount on Backend (SECURITY: Never trust frontend prices) ---
    $total_amount = round((float)$booking['total_amount'], 2);
    
    // Log checkout initiation
    ActivityLogger::logBooking(
        $guest_id,
        'checkout_initiated',
        $booking_id,
        [
            'payment_gateway' => $payment_gateway,
            'total_amount' => $total_amount,
            'property_id' => $booking['property_id'],
            'host_id' => $booking['host_id']
        ]
    );

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

        // Initialize transaction with backend-calculated amount
        $data = $paystack->initializeTransaction($email, $total_amount, $metadata);

        if ($data) {
            $checkout_url = $data['authorization_url'];
            $reference = $data['reference'] ?? null;
            
            // Log successful Paystack initialization
            ActivityLogger::logPayment(
                $guest_id,
                'paystack_initialized',
                $booking_id,
                [
                    'reference' => $reference,
                    'amount' => $total_amount,
                    'email' => $email
                ]
            );
        } else {
            // Log failed Paystack initialization
            ActivityLogger::logPayment(
                $guest_id,
                'paystack_init_failed',
                $booking_id,
                ['amount' => $total_amount]
            );
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

        // Initialize transaction with backend-calculated amount
        $data = $flutterwave->initializeTransaction($email, $total_amount, $tx_ref, $metadata);

        if ($data) {
            $checkout_url = $data['link'];
            
            // Log successful Flutterwave initialization
            ActivityLogger::logPayment(
                $guest_id,
                'flutterwave_initialized',
                $booking_id,
                [
                    'tx_ref' => $tx_ref,
                    'amount' => $total_amount,
                    'email' => $email
                ]
            );
        } else {
            // Log failed Flutterwave initialization
            ActivityLogger::logPayment(
                $guest_id,
                'flutterwave_init_failed',
                $booking_id,
                ['amount' => $total_amount]
            );
             send_error("Failed to initialize Flutterwave transaction.", [], 502);
        }

    } else {
        // Log invalid gateway attempt
        ActivityLogger::log(
            $guest_id,
            'checkout_invalid_gateway',
            "Invalid payment gateway selected: {$payment_gateway}",
            'booking',
            $booking_id,
            ['gateway_attempted' => $payment_gateway]
        );
        send_error("Invalid payment gateway selected.", [], 400);
    }

    // --- Send Notifications ---
    sendNotification($guest_id, "Proceed to Payment", "Your booking for '" . $booking['property_name'] . "' has been approved. Please complete the payment to confirm your reservation.", 'important');

    $admin_user_id = 1;
    sendNotification($admin_user_id, "Booking Checkout Initiated", "Guest " . $guest_id . " has initiated checkout for booking ID " . $booking_id . ".", 'important');

    // Prepare response data
    $response_data = [
        'booking_id' => (int)$booking_id,
        'total_amount' => $total_amount,
        'currency' => 'NGN',
        'payment_gateway' => $payment_gateway,
        'checkout_url' => $checkout_url,
        'message' => 'Checkout initiated. Please proceed to payment.'
    ];

    send_success("Checkout initiated. Please proceed to payment.", $response_data);

} catch (PDOException $e) {
    error_log("Database error during checkout for booking " . $booking_id . ": " . $e->getMessage());
    
    // Log database error
    ActivityLogger::log(
        $guest_id ?? null,
        'checkout_db_error',
        "Database error during checkout",
        'booking',
        $booking_id ?? null,
        ['error' => $e->getMessage()]
    );
    
    send_error("Database error. Could not initiate checkout.", [], 500);
} catch (Exception $e) {
    error_log("General error during checkout for booking " . $booking_id . ": " . $e->getMessage());
    
    // Log general error
    ActivityLogger::log(
        $guest_id ?? null,
        'checkout_error',
        "Error during checkout",
        'booking',
        $booking_id ?? null,
        ['error' => $e->getMessage()]
    );
    
    send_error("An unexpected error occurred during checkout initiation.", [], 500);
}
