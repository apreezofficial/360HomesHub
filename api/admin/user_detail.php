<?php
header("Content-Type: application/json");
require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../utils/db.php';
require_once __DIR__ . '/../../utils/jwt.php';

try {
    $admin = JWTManager::authenticate();
    if ($admin['role'] !== 'admin' && $admin['role'] !== 'super_admin') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }

    $userId = $_GET['id'] ?? null;
    if (!$userId) {
        throw new Exception("User ID required");
    }

    $pdo = Database::getInstance();

    // 1. User Info
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        throw new Exception("User not found");
    }

    // Remove sensitive data
    unset($user['password_hash']);
    unset($user['google_id']);

    // 2. Stats
    // Listings count
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM properties WHERE host_id = ?");
    $stmt->execute([$userId]);
    $listingsCount = (int)$stmt->fetchColumn();

    // Bookings count (as guest)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE guest_id = ?");
    $stmt->execute([$userId]);
    $bookingsGuestCount = (int)$stmt->fetchColumn();

    // Bookings received (as host)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE host_id = ?");
    $stmt->execute([$userId]);
    $bookingsHostCount = (int)$stmt->fetchColumn();

    // Total spent (as guest)
    $stmt = $pdo->prepare("SELECT SUM(total_amount) FROM bookings WHERE guest_id = ? AND status = 'confirmed'");
    $stmt->execute([$userId]);
    $totalSpent = (float)($stmt->fetchColumn() ?: 0);

    // Total earnings (as host)
    $stmt = $pdo->prepare("SELECT SUM(total_amount) FROM bookings WHERE host_id = ? AND status = 'confirmed'");
    $stmt->execute([$userId]);
    $totalEarnings = (float)($stmt->fetchColumn() ?: 0);

    // Last booking date
    $stmt = $pdo->prepare("SELECT created_at FROM bookings WHERE guest_id = ? OR host_id = ? ORDER BY created_at DESC LIMIT 1");
    $stmt->execute([$userId, $userId]);
    $lastBookingDate = $stmt->fetchColumn();

    // 3. KYC
    $stmt = $pdo->prepare("SELECT * FROM kyc WHERE user_id = ?");
    $stmt->execute([$userId]);
    $kycRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 4. Booking History
    $stmt = $pdo->prepare("SELECT b.*, p.name as property_name, p.image_url as property_image,
                                  u.first_name as host_first_name, u.last_name as host_last_name
                           FROM bookings b 
                           LEFT JOIN properties p ON b.property_id = p.id 
                           LEFT JOIN users u ON b.host_id = u.id
                           WHERE b.guest_id = ? OR b.host_id = ?
                           ORDER BY b.created_at DESC LIMIT 12");
    $stmt->execute([$userId, $userId]);
    $bookingHistory = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => [
            'user' => $user,
            'stats' => [
                'listings' => $listingsCount,
                'bookings_guest' => $bookingsGuestCount,
                'bookings_host' => $bookingsHostCount,
                'total_spent' => $totalSpent,
                'total_earnings' => $totalEarnings,
                'last_booking_date' => $lastBookingDate
            ],
            'kyc' => $kycRecords,
            'booking_history' => $bookingHistory
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
