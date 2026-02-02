<?php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../utils/db.php'; // Database connection
require_once __DIR__ . '/../../utils/response.php'; // JSON response handler
require_once __DIR__ . '/../../utils/jwt.php'; // JWT authentication
require_once __DIR__ . '/../../api/notifications/notify.php'; // Notification helper
// Placeholder for payment gateway integration
// In a real scenario, you would include SDKs or specific payment logic here.
// For now, we simulate the process.

header("Content-Type: application/json");

// --- JWT Authentication ---
// Authenticate user to ensure they are the guest initiating checkout.
$userData = JWTManager::authenticate();
$guest_id = $userData['user_id'] ?? null;

if (!$guest_id) {
    send_error("Unauthorized. Invalid or missing token.", [], 401);
}

// --- Input Validation ---
$input = json_decode(file_get_contents('php://input'), true);
$booking_id = $input['booking_id'] ?? null;

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
    // Ensure the logged-in user is the guest who made the booking
    if ((int)$booking['guest_id'] !== (int)$guest_id) {
        send_error("Forbidden. You are not the guest of this booking.", [], 403);
    }

    // --- Status and Payment Check ---
    // Booking must be approved and not yet paid
    if ($booking['status'] !== 'approved') {
        send_error("Booking is not in an approved state. Current status: {$booking['status']}.", [], 409);
    }
    // Check if already paid, though status 'paid' should prevent this branch if db has FKs or app logic is strict.
    // For simplicity, we rely on 'approved' state implying 'not paid' here.

    // --- Payment Gateway Integration Simulation ---
    // In a real application, you would:
    // 1. Retrieve API keys from environment or config.
    // 2. Initialize the chosen payment gateway SDK (e.g., Paystack, Flutterwave).
    // 3. Create a payment instance with booking details (amount, currency, metadata).
    // 4. Obtain a checkout URL from the gateway.
    // 5. Update the booking status to 'awaiting_payment' or similar if needed, and store transaction reference.

    $payment_gateway = 'paystack'; // Default or determined by configuration
    $checkout_url = '#'; // Placeholder

    // Example simulation for Paystack
    if ($payment_gateway === 'paystack') {
        // Generate a dummy checkout URL. Replace with actual SDK call.
        // For simulation, let's create a URL with booking details encoded.
        // In reality, this would be a secure redirect to Paystack's servers.
        $amount_in_kobo = (int)($booking['total_amount'] * 100); // Paystack uses kobo or cents
        $metadata = json_encode([
            'booking_id' => (int)$booking['id'],
            'guest_id' => (int)$guest_id,
            'host_id' => (int)$booking['host_id'],
            'property_id' => (int)$booking['property_id']
        ]);
        // NOTE: This is a SIMULATED URL and not a real payment link.
        $checkout_url = "https://paystack.example.com/pay?id={$booking_id}&amount={$amount_in_kobo}&ref=" . bin2hex(random_bytes(16)) . "&meta=" . urlencode($metadata);

        // In a real scenario, you would save a transaction reference here.
        // Example: Update booking table or a separate transactions table.
        // $pdo->prepare("UPDATE bookings SET payment_ref = :ref WHERE id = :booking_id")->execute([...]);

    } else { // Example for Flutterwave (if needed)
        // $checkout_url = "https://flutterwave.example.com/pay?booking_id={$booking_id}&amount={$booking['total_amount']}";
        send_error("Payment gateway not supported yet.", [], 501);
    }

    // --- Send Notifications ---
    // Notify Guest (Important)
    sendNotification($guest_id, "Proceed to Payment", "Your booking for '{$booking['property_name']}' has been approved. Please complete the payment to confirm your reservation. Click the link to proceed.", 'important');

    // Notify Admin (Important)
    $admin_user_id = 1; // Assuming admin user ID is 1
    sendNotification($admin_user_id, "Booking Checkout Initiated", "Guest {$guest_id} has initiated checkout for booking ID {$booking_id} ({$booking['property_name']}).", 'important');


    // Prepare response data
    $response_data = [
        'booking_id' => (int)$booking_id,
        'total_amount' => round((float)$booking['total_amount'], 2),
        'currency' => 'NGN', // Assuming NGN, should be configurable
        'payment_gateway' => $payment_gateway,
        'checkout_url' => $checkout_url,
        'message' => 'Checkout initiated. Please proceed to payment.'
    ];

    send_success("Checkout initiated. Please proceed to payment.", $response_data);

} catch (PDOException $e) {
    error_log("Database error during checkout for booking {$booking_id}: " . $e->getMessage());
    send_error("Database error. Could not initiate checkout.", [], 500);
} catch (Exception $e) {
    error_log("General error during checkout for booking {$booking_id}: " . $e->getMessage());
    send_error("An unexpected error occurred during checkout initiation.", [], 500);
}
?>

/*
 * Example Request JSON:
 * {
 *   "booking_id": 5 // Must be an 'approved' booking
 * }
 */

/*
 * Example Response JSON (Success - 200 OK):
 * {
 *   "booking_id": 5,
 *   "total_amount": 395.00,
 *   "currency": "NGN",
 *   "payment_gateway": "paystack",
 *   "checkout_url": "https://paystack.example.com/pay?id=5&amount=39500&ref=a1b2c3d4e5f6a7b8&meta=%7B%22booking_id%22%3A5%2C%22guest_id%22%3A101%2C%22host_id%22%3A5%2C%22property_id%22%3A1%7D",
 *   "message": "Checkout initiated. Please proceed to payment."
 * }
 */

/*
 * Example Response JSON (Error - Unauthorized):
 * {
 *   "message": "Unauthorized. Invalid or missing token."
 * }
 */

/*
 * Example Response JSON (Error - Forbidden):
 * {
 *   "message": "Forbidden. You are not the guest of this booking."
 * }
 */

/*
 * Example Response JSON (Error - Booking Not Found):
 * {
 *   "message": "Booking not found."
 * }
 */

/*
 * Example Response JSON (Error - Not Approved):
 * {
 *   "message": "Booking is not in an approved state. Current status: pending."
 * }
 */
