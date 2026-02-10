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
    $listingsCount = $stmt->fetchColumn();

    // Bookings count (as guest)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE guest_id = ?");
    $stmt->execute([$userId]);
    $bookingsCount = $stmt->fetchColumn();

    // Recent Activity (Last 5 bookings)
    $stmt = $pdo->prepare("SELECT b.id, b.status, b.created_at, b.total_amount, p.name as property_name 
                           FROM bookings b 
                           LEFT JOIN properties p ON b.property_id = p.id 
                           WHERE b.guest_id = ? 
                           ORDER BY b.created_at DESC LIMIT 5");
    $stmt->execute([$userId]);
    $recentActivity = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'data' => [
            'user' => $user,
            'stats' => [
                'listings' => $listingsCount,
                'bookings' => $bookingsCount,
                'total_spent' => 0 // Todo: Calculate from transactions
            ],
            'recent_activity' => $recentActivity
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
