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

// Check if user is a host
if (($userData['role'] ?? '') !== 'host') {
    send_error('Only hosts can reject bookings.', [], 403);
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

    // Get booking and verify it belongs to host's property
    $stmt = $pdo->prepare("
        SELECT b.*, p.host_id, p.name as property_name, u.first_name, u.last_name
        FROM bookings b
        INNER JOIN properties p ON b.property_id = p.id
        INNER JOIN users u ON b.user_id = u.id
        WHERE b.id = ? AND p.host_id = ?
    ");
    $stmt->execute([$bookingId, $userId]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$booking) {
        $pdo->rollBack();
        send_error('Booking not found or you do not have permission to reject it.', [], 404);
    }

    if ($booking['status'] !== 'pending') {
        $pdo->rollBack();
        send_error('Only pending bookings can be rejected.', [], 400);
    }

    // Update booking status
    $stmt = $pdo->prepare("UPDATE bookings SET status = 'rejected', rejection_reason = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$reason, $bookingId]);

    // If guest has already paid, process refund
    if ($booking['payment_status'] === 'paid') {
        // Get guest wallet
        $stmt = $pdo->prepare("SELECT id, balance FROM wallets WHERE user_id = ?");
        $stmt->execute([$booking['user_id']]);
        $guestWallet = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$guestWallet) {
            // Create wallet if doesn't exist
            $stmt = $pdo->prepare("INSERT INTO wallets (user_id, balance, currency) VALUES (?, 0.00, 'NGN')");
            $stmt->execute([$booking['user_id']]);
            $guestWalletId = $pdo->lastInsertId();
            $guestBalance = 0;
        } else {
            $guestWalletId = $guestWallet['id'];
            $guestBalance = $guestWallet['balance'];
        }

        // Refund to guest wallet
        $newGuestBalance = $guestBalance + $booking['total_price'];
        $stmt = $pdo->prepare("UPDATE wallets SET balance = ? WHERE id = ?");
        $stmt->execute([$newGuestBalance, $guestWalletId]);

        // Create refund transaction
        $reference = 'REFUND-' . strtoupper(uniqid()) . '-' . time();
        $stmt = $pdo->prepare("
            INSERT INTO transactions 
            (user_id, wallet_id, type, category, amount, balance_before, balance_after, reference, description, status, related_booking_id, metadata) 
            VALUES (?, ?, 'credit', 'booking_refund', ?, ?, ?, ?, ?, 'completed', ?, ?)
        ");
        $stmt->execute([
            $booking['user_id'],
            $guestWalletId,
            $booking['total_price'],
            $guestBalance,
            $newGuestBalance,
            $reference,
            'Booking payment - refunded',
            $bookingId,
            json_encode([
                'booking_id' => $bookingId,
                'property_name' => $booking['property_name'],
                'rejection_reason' => $reason
            ])
        ]);

        // Update booking payment status
        $stmt = $pdo->prepare("UPDATE bookings SET payment_status = 'refunded' WHERE id = ?");
        $stmt->execute([$bookingId]);
    }

    $pdo->commit();

    send_success('Booking rejected successfully.', [
        'booking_id' => $bookingId,
        'status' => 'rejected',
        'refund_processed' => isset($newGuestBalance)
    ]);

} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Reject booking error for user ID $userId: " . $e->getMessage());
    send_error('Failed to reject booking.', [], 500);
}
