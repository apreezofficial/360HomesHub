<?php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../utils/db.php';
require_once __DIR__ . '/../../utils/response.php';
require_once __DIR__ . '/../../utils/jwt.php';

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
$reason = trim($input['reason'] ?? '');

if (!$bookingId) {
    send_error('Booking ID is required.', [], 400);
}

$pdo = Database::getInstance();

try {
    $pdo->beginTransaction();

    // Get booking and verify it belongs to user
    $stmt = $pdo->prepare("
        SELECT b.*, p.name as property_name
        FROM bookings b
        INNER JOIN properties p ON b.property_id = p.id
        WHERE b.id = ? AND b.user_id = ?
    ");
    $stmt->execute([$bookingId, $userId]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$booking) {
        $pdo->rollBack();
        send_error('Booking not found.', [], 404);
    }

    // Only pending or confirmed bookings can be cancelled
    if (!in_array($booking['status'], ['pending', 'confirmed'])) {
        $pdo->rollBack();
        send_error('Only pending or confirmed bookings can be cancelled.', [], 400);
    }

    // Check if booking is within cancellation window (e.g., 24 hours before check-in)
    $checkIn = new DateTime($booking['check_in']);
    $now = new DateTime();
    $hoursUntilCheckIn = ($checkIn->getTimestamp() - $now->getTimestamp()) / 3600;

    $refundPercentage = 100;
    if ($hoursUntilCheckIn < 24) {
        $refundPercentage = 50; // 50% refund if cancelled within 24 hours
    }

    // Update booking status
    $stmt = $pdo->prepare("UPDATE bookings SET status = 'cancelled', cancellation_reason = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$reason, $bookingId]);

    // Process refund if payment was made
    $refundAmount = 0;
    if ($booking['payment_status'] === 'paid') {
        $refundAmount = $booking['total_price'] * ($refundPercentage / 100);

        // Get user wallet
        $stmt = $pdo->prepare("SELECT id, balance FROM wallets WHERE user_id = ?");
        $stmt->execute([$userId]);
        $wallet = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$wallet) {
            // Create wallet if doesn't exist
            $stmt = $pdo->prepare("INSERT INTO wallets (user_id, balance, currency) VALUES (?, 0.00, 'NGN')");
            $stmt->execute([$userId]);
            $walletId = $pdo->lastInsertId();
            $currentBalance = 0;
        } else {
            $walletId = $wallet['id'];
            $currentBalance = $wallet['balance'];
        }

        // Add refund to wallet
        $newBalance = $currentBalance + $refundAmount;
        $stmt = $pdo->prepare("UPDATE wallets SET balance = ? WHERE id = ?");
        $stmt->execute([$newBalance, $walletId]);

        // Create refund transaction
        $reference = 'CANCEL-' . strtoupper(uniqid()) . '-' . time();
        $stmt = $pdo->prepare("
            INSERT INTO transactions 
            (user_id, wallet_id, type, category, amount, balance_before, balance_after, reference, description, status, related_booking_id, metadata) 
            VALUES (?, ?, 'credit', 'booking_refund', ?, ?, ?, ?, ?, 'completed', ?, ?)
        ");
        $stmt->execute([
            $userId,
            $walletId,
            $refundAmount,
            $currentBalance,
            $newBalance,
            $reference,
            'Booking cancellation - ' . $refundPercentage . '% refund',
            $bookingId,
            json_encode([
                'booking_id' => $bookingId,
                'property_name' => $booking['property_name'],
                'original_amount' => $booking['total_price'],
                'refund_percentage' => $refundPercentage,
                'cancellation_reason' => $reason
            ])
        ]);

        // Update payment status
        $stmt = $pdo->prepare("UPDATE bookings SET payment_status = 'refunded' WHERE id = ?");
        $stmt->execute([$bookingId]);
    }

    $pdo->commit();

    send_success('Booking cancelled successfully.', [
        'booking_id' => $bookingId,
        'status' => 'cancelled',
        'refund_amount' => $refundAmount,
        'refund_percentage' => $refundPercentage,
        'formatted_refund' => '₦' . number_format($refundAmount, 2),
        'message' => $refundPercentage === 100 
            ? 'Full refund processed to your wallet.' 
            : 'Partial refund (' . $refundPercentage . '%) processed due to late cancellation.'
    ]);

} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Cancel booking error for user ID $userId: " . $e->getMessage());
    send_error('Failed to cancel booking.', [], 500);
}
