<?php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../utils/db.php';
require_once __DIR__ . '/../../utils/response.php';
require_once __DIR__ . '/../../utils/jwt.php';
require_once __DIR__ . '/../../api/notifications/notify.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_error('Invalid request method.', [], 405);
}

// Authenticate user via JWT
$userData = JWTManager::authenticate();
$userId = $userData['user_id'] ?? null;

if (!$userId) {
    send_error('Authentication failed.', [], 401);
}

$input = json_decode(file_get_contents('php://input'), true);

$bookingId = isset($input['booking_id']) ? intval($input['booking_id']) : null;
$reference = $input['reference'] ?? null;

if (!$bookingId || !$reference) {
    send_error('Booking ID and payment reference are required.', [], 400);
}

$pdo = Database::getInstance();

try {
    $pdo->beginTransaction();

    // 1. Fetch booking
    $stmt = $pdo->prepare("
        SELECT b.*, p.host_id, p.name as property_name, u.email as guest_email, u.first_name as guest_first_name
        FROM bookings b
        JOIN properties p ON b.property_id = p.id
        JOIN users u ON b.user_id = u.id
        WHERE b.id = ? AND b.user_id = ?
    ");
    $stmt->execute([$bookingId, $userId]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$booking) {
        $pdo->rollBack();
        send_error('Booking not found.', [], 404);
    }

    if ($booking['status'] !== 'approved' && $booking['payment_status'] === 'paid') {
        $pdo->rollBack();
        send_error('Booking is already paid or in an invalid state for payment verification.', [], 400);
    }

    // 2. Verify payment with gateway (Simulated for now, usually calls Paystack/Flutterwave API)
    // In a real scenario, we'd call $paystack->verifyTransaction($reference)
    $paymentSuccessful = true; // Simulation

    if (!$paymentSuccessful) {
        $pdo->rollBack();
        send_error('Payment verification failed.', [], 400);
    }

    // 3. Update booking status
    $stmt = $pdo->prepare("UPDATE bookings SET status = 'confirmed', payment_status = 'paid', payment_ref = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$reference, $bookingId]);

    // 4. Credit Host Wallet
    $hostId = $booking['host_id'];
    
    // Get host wallet
    $stmt = $pdo->prepare("SELECT id, balance FROM wallets WHERE user_id = ? FOR UPDATE");
    $stmt->execute([$hostId]);
    $wallet = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$wallet) {
        // Create wallet if doesn't exist
        $stmt = $pdo->prepare("INSERT INTO wallets (user_id, balance, currency) VALUES (?, 0.00, 'NGN')");
        $stmt->execute([$hostId]);
        $walletId = $pdo->lastInsertId();
        $currentBalance = 0;
    } else {
        $walletId = $wallet['id'];
        $currentBalance = $wallet['balance'];
    }

    // Calculate host earning (85% of total, platform takes 15%)
    // Note: total_price in bookings table
    $totalPrice = (float)$booking['total_price'];
    $platformFee = $totalPrice * 0.15;
    $hostEarning = $totalPrice * 0.85;
    $newBalance = $currentBalance + $hostEarning;

    // Update wallet
    $stmt = $pdo->prepare("UPDATE wallets SET balance = ? WHERE id = ?");
    $stmt->execute([$newBalance, $walletId]);

    // 5. Create Transaction Record
    $txnRef = 'EARN-' . strtoupper(uniqid()) . '-' . time();
    $stmt = $pdo->prepare("
        INSERT INTO transactions 
        (user_id, wallet_id, type, category, amount, balance_before, balance_after, reference, description, status, related_booking_id, metadata) 
        VALUES (?, ?, 'credit', 'host_earning', ?, ?, ?, ?, ?, 'completed', ?, ?)
    ");
    $stmt->execute([
        $hostId,
        $walletId,
        $hostEarning,
        $currentBalance,
        $newBalance,
        $txnRef,
        'Booking payment for ' . $booking['property_name'],
        $bookingId,
        json_encode([
            'booking_id' => $bookingId,
            'total_price' => $totalPrice,
            'platform_fee' => $platformFee,
            'host_earning' => $hostEarning,
            'guest_name' => $booking['guest_first_name'],
            'payment_ref' => $reference
        ])
    ]);

    // 6. Send Notifications
    // Notify Guest
    sendNotification(
        $userId, 
        "Booking Confirmed!", 
        "Your payment for '{$booking['property_name']}' has been verified. Your booking is now active. Enjoy your stay!", 
        'important'
    );

    // Notify Host
    sendNotification(
        $hostId, 
        "Payment Received!", 
        "Payment for booking on '{$booking['property_name']}' has been received. ₦" . number_format($hostEarning, 2) . " has been added to your wallet.", 
        'important'
    );

    $pdo->commit();

    send_success('Payment verified and booking confirmed.', [
        'booking_id' => $bookingId,
        'status' => 'confirmed',
        'payment_status' => 'paid',
        'host_earning' => $hostEarning
    ]);

} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Verify payment error for booking ID $bookingId: " . $e->getMessage());
    send_error('Failed to verify payment.', [], 500);
}
