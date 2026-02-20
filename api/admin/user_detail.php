<?php
header("Content-Type: application/json");
require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../utils/db.php';
require_once __DIR__ . '/../../utils/jwt.php';

try {
    $admin = JWTManager::authenticate();
    if (!in_array($admin['role'], ['admin', 'super_admin'])) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }

    $userId = isset($_GET['id']) ? (int)$_GET['id'] : null;
    if (!$userId) {
        throw new Exception("User ID required");
    }

    $pdo = Database::getInstance();

    // 1. User Info (safe columns — no password_hash, no google_id)
    $stmt = $pdo->prepare("
        SELECT id, first_name, last_name, email, phone, role, status,
               booking_disabled, message_disabled, avatar, bio, address,
               city, state, country, created_at, last_login, last_ip, auth_provider
        FROM users WHERE id = ?
    ");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) throw new Exception("User not found");

    // 2. Stats
    $s = $pdo->prepare("SELECT COUNT(*) FROM properties WHERE host_id = ?");
    $s->execute([$userId]); $listingsCount = (int)$s->fetchColumn();

    $s = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE guest_id = ?");
    $s->execute([$userId]); $bookingsGuestCount = (int)$s->fetchColumn();

    $s = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE host_id = ?");
    $s->execute([$userId]); $bookingsHostCount = (int)$s->fetchColumn();

    $s = $pdo->prepare("SELECT COALESCE(SUM(total_amount),0) FROM bookings WHERE guest_id = ? AND status IN ('confirmed','paid')");
    $s->execute([$userId]); $totalSpent = (float)$s->fetchColumn();

    $s = $pdo->prepare("SELECT COALESCE(SUM(total_amount),0) FROM bookings WHERE host_id = ? AND status IN ('confirmed','paid')");
    $s->execute([$userId]); $totalEarnings = (float)$s->fetchColumn();

    $s = $pdo->prepare("SELECT created_at FROM bookings WHERE guest_id = ? OR host_id = ? ORDER BY created_at DESC LIMIT 1");
    $s->execute([$userId, $userId]); $lastBookingDate = $s->fetchColumn();

    // 3. KYC records (all of them)
    $s = $pdo->prepare("SELECT id, identity_type, status, admin_note, submitted_at, id_front, id_back, selfie FROM kyc WHERE user_id = ? ORDER BY submitted_at DESC");
    $s->execute([$userId]);
    $kycRecords = $s->fetchAll(PDO::FETCH_ASSOC);

    // 4. Booking History with property image from property_images table
    $s = $pdo->prepare("
        SELECT b.id, b.property_id, b.status, b.check_in, b.check_out,
               b.total_amount, b.nights, b.created_at,
               p.name AS property_name,
               p.city AS property_city, p.state AS property_state,
               (SELECT pi.media_url FROM property_images pi WHERE pi.property_id = p.id ORDER BY pi.uploaded_at ASC LIMIT 1) AS property_image,
               hu.first_name AS host_first_name, hu.last_name AS host_last_name
        FROM bookings b
        LEFT JOIN properties p  ON b.property_id = p.id
        LEFT JOIN users hu      ON b.host_id = hu.id
        WHERE b.guest_id = ? OR b.host_id = ?
        ORDER BY b.created_at DESC
        LIMIT 15
    ");
    $s->execute([$userId, $userId]);
    $bookingHistory = $s->fetchAll(PDO::FETCH_ASSOC);

    // 5. Properties listed (for hosts)
    $s = $pdo->prepare("
        SELECT p.id, p.name, p.city, p.state, p.price, p.price_type, p.status, p.created_at,
               (SELECT pi.media_url FROM property_images pi WHERE pi.property_id = p.id ORDER BY pi.uploaded_at ASC LIMIT 1) AS image
        FROM properties p
        WHERE p.host_id = ?
        ORDER BY p.created_at DESC
        LIMIT 6
    ");
    $s->execute([$userId]);
    $properties = $s->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data'    => [
            'user'            => $user,
            'stats'           => [
                'listings'          => $listingsCount,
                'bookings_guest'    => $bookingsGuestCount,
                'bookings_host'     => $bookingsHostCount,
                'total_spent'       => $totalSpent,
                'total_earnings'    => $totalEarnings,
                'last_booking_date' => $lastBookingDate,
            ],
            'kyc'             => $kycRecords,
            'booking_history' => $bookingHistory,
            'properties'      => $properties,
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
