<?php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../utils/db.php';
require_once __DIR__ . '/../../utils/response.php';
require_once __DIR__ . '/../../utils/jwt.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
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
    send_error('Only hosts can access dashboard stats.', [], 403);
}

$pdo = Database::getInstance();

try {
    // Get total properties
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM properties WHERE host_id = ? AND status != 'draft'");
    $stmt->execute([$userId]);
    $totalProperties = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Get active properties
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM properties WHERE host_id = ? AND status = 'active'");
    $stmt->execute([$userId]);
    $activeProperties = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Get total bookings
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total 
        FROM bookings b
        INNER JOIN properties p ON b.property_id = p.id
        WHERE p.host_id = ?
    ");
    $stmt->execute([$userId]);
    $totalBookings = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Get pending bookings
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total 
        FROM bookings b
        INNER JOIN properties p ON b.property_id = p.id
        WHERE p.host_id = ? AND b.status = 'pending'
    ");
    $stmt->execute([$userId]);
    $pendingBookings = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Get confirmed bookings
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total 
        FROM bookings b
        INNER JOIN properties p ON b.property_id = p.id
        WHERE p.host_id = ? AND b.status = 'confirmed'
    ");
    $stmt->execute([$userId]);
    $confirmedBookings = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Get total earnings (from wallet transactions)
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(amount), 0) as total 
        FROM transactions 
        WHERE user_id = ? AND type = 'credit' AND category = 'host_earning' AND status = 'completed'
    ");
    $stmt->execute([$userId]);
    $totalEarnings = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Get this month's earnings
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(amount), 0) as total 
        FROM transactions 
        WHERE user_id = ? 
        AND type = 'credit' 
        AND category = 'host_earning' 
        AND status = 'completed'
        AND MONTH(created_at) = MONTH(CURRENT_DATE())
        AND YEAR(created_at) = YEAR(CURRENT_DATE())
    ");
    $stmt->execute([$userId]);
    $monthlyEarnings = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Get wallet balance
    $stmt = $pdo->prepare("SELECT balance FROM wallets WHERE user_id = ?");
    $stmt->execute([$userId]);
    $wallet = $stmt->fetch(PDO::FETCH_ASSOC);
    $walletBalance = $wallet ? $wallet['balance'] : 0;

    // Get recent bookings (last 5)
    $stmt = $pdo->prepare("
        SELECT 
            b.id, b.check_in, b.check_out, b.total_price, b.status,
            b.created_at,
            p.name as property_name,
            u.first_name, u.last_name, u.avatar
        FROM bookings b
        INNER JOIN properties p ON b.property_id = p.id
        INNER JOIN users u ON b.user_id = u.id
        WHERE p.host_id = ?
        ORDER BY b.created_at DESC
        LIMIT 5
    ");
    $stmt->execute([$userId]);
    $recentBookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format recent bookings
    foreach ($recentBookings as &$booking) {
        $booking['total_price'] = (float)$booking['total_price'];
        $booking['formatted_price'] = '₦' . number_format($booking['total_price'], 2);
        $booking['guest_name'] = trim($booking['first_name'] . ' ' . $booking['last_name']);
        unset($booking['first_name'], $booking['last_name']);
    }

    send_success('Host dashboard stats retrieved successfully.', [
        'stats' => [
            'total_properties' => (int)$totalProperties,
            'active_properties' => (int)$activeProperties,
            'total_bookings' => (int)$totalBookings,
            'pending_bookings' => (int)$pendingBookings,
            'confirmed_bookings' => (int)$confirmedBookings,
            'total_earnings' => (float)$totalEarnings,
            'monthly_earnings' => (float)$monthlyEarnings,
            'wallet_balance' => (float)$walletBalance,
            'formatted_total_earnings' => '₦' . number_format($totalEarnings, 2),
            'formatted_monthly_earnings' => '₦' . number_format($monthlyEarnings, 2),
            'formatted_wallet_balance' => '₦' . number_format($walletBalance, 2)
        ],
        'recent_bookings' => $recentBookings
    ]);

} catch (Exception $e) {
    error_log("Get host dashboard error for user ID $userId: " . $e->getMessage());
    send_error('Failed to retrieve dashboard stats.', [], 500);
}
