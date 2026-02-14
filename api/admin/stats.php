<?php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../utils/db.php';
require_once __DIR__ . '/../../utils/response.php';
require_once __DIR__ . '/../../utils/jwt.php';

// Authenticate admin
$userData = JWTManager::authenticate();
if ($userData['role'] !== 'admin') {
    send_error('Access denied. Admin only.', [], 403);
}

$period = $_GET['period'] ?? 'last_30_days'; // today, this_week, last_30_days, last_3_months

$pdo = Database::getInstance();

try {
    // 1. Determine Date Ranges
    $currentStart = '';
    $previousStart = '';
    $now = date('Y-m-d H:i:s');

    switch ($period) {
        case 'today':
            $currentStart = date('Y-m-d 00:00:00');
            $previousStart = date('Y-m-d 00:00:00', strtotime('-1 day'));
            $previousEnd = date('Y-m-d 23:59:59', strtotime('-1 day'));
            break;
        case 'this_week':
            $currentStart = date('Y-m-d H:i:s', strtotime('monday this week'));
            $previousStart = date('Y-m-d H:i:s', strtotime('monday last week'));
            $previousEnd = date('Y-m-d H:i:s', strtotime('sunday last week 23:59:59'));
            break;
        case 'last_3_months':
            $currentStart = date('Y-m-d H:i:s', strtotime('-3 months'));
            $previousStart = date('Y-m-d H:i:s', strtotime('-6 months'));
            $previousEnd = $currentStart;
            break;
        case 'last_30_days':
        default:
            $currentStart = date('Y-m-d H:i:s', strtotime('-30 days'));
            $previousStart = date('Y-m-d H:i:s', strtotime('-60 days'));
            $previousEnd = $currentStart;
            break;
    }

    // Helper function for growth
    function calculateGrowth($current, $previous) {
        if ($previous == 0) return $current > 0 ? 100 : 0;
        return round((($current - $previous) / $previous) * 100);
    }

    // 2. Fetch Stats
    // Total Users
    $totalUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $verifiedUsers = $pdo->query("SELECT COUNT(*) FROM users WHERE status = 'verified'")->fetchColumn();
    $hostUsers = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'host'")->fetchColumn();
    $guestUsers = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'guest'")->fetchColumn();
    $currentUsers = $pdo->query("SELECT COUNT(*) FROM users WHERE created_at >= '$currentStart'")->fetchColumn();
    $prevUsers = $pdo->query("SELECT COUNT(*) FROM users WHERE created_at >= '$previousStart' AND created_at < '$currentStart'")->fetchColumn();
    $usersGrowth = calculateGrowth($currentUsers, $prevUsers);

    // Active Listings (published)
    $activeListings = $pdo->query("SELECT COUNT(*) FROM properties WHERE status = 'active'")->fetchColumn();
    $currentListings = $pdo->query("SELECT COUNT(*) FROM properties WHERE status = 'active' AND created_at >= '$currentStart'")->fetchColumn();
    $prevListings = $pdo->query("SELECT COUNT(*) FROM properties WHERE status = 'active' AND created_at >= '$previousStart' AND created_at < '$currentStart'")->fetchColumn();
    $listingsGrowth = calculateGrowth($currentListings, $prevListings);

    // Active Bookings (confirmed/active)
    $activeBookings = $pdo->query("SELECT COUNT(*) FROM bookings WHERE status IN ('confirmed', 'active')")->fetchColumn();
    $currentBookings = $pdo->query("SELECT COUNT(*) FROM bookings WHERE status IN ('confirmed', 'active') AND created_at >= '$currentStart'")->fetchColumn();
    $prevBookings = $pdo->query("SELECT COUNT(*) FROM bookings WHERE status IN ('confirmed', 'active') AND created_at >= '$previousStart' AND created_at < '$currentStart'")->fetchColumn();
    $bookingsGrowth = calculateGrowth($currentBookings, $prevBookings);

    // Total Earnings
    $totalEarnings = $pdo->query("SELECT SUM(amount) FROM transactions WHERE type = 'credit' AND category = 'host_earning' AND status = 'completed'")->fetchColumn() ?: 0;
    $currentEarnings = $pdo->query("SELECT SUM(amount) FROM transactions WHERE type = 'credit' AND category = 'host_earning' AND status = 'completed' AND created_at >= '$currentStart'")->fetchColumn() ?: 0;
    $prevEarnings = $pdo->query("SELECT SUM(amount) FROM transactions WHERE type = 'credit' AND category = 'host_earning' AND status = 'completed' AND created_at >= '$previousStart' AND created_at < '$currentStart'")->fetchColumn() ?: 0;
    $earningsGrowth = calculateGrowth($currentEarnings, $prevEarnings);

    // 3. Action Queue
    $actionQueue = [];
    
    // Listing Review Delay
    $stmtReview = $pdo->query("SELECT id, name, created_at FROM properties WHERE status = 'pending' ORDER BY created_at ASC LIMIT 2");
    while ($row = $stmtReview->fetch()) {
        $actionQueue[] = [
            'entity' => 'Listing Review Delay',
            'description' => "Listing: " . $row['name'] . " pending approval for over 2 days.",
            'severity' => 'High',
            'time' => $row['created_at'],
            'action' => 'Review listing',
            'action_link' => "/admin/listings/" . $row['id']
        ];
    }

    // Booking Dispute (Mocked or from disputes table if exists)
    $actionQueue[] = [
        'entity' => 'Booking Dispute',
        'description' => "Guest reported property mismatch (Booking #HB-1002).",
        'severity' => 'Critical',
        'time' => date('Y-m-d H:i:s', strtotime('-15 mins')),
        'action' => 'View report',
        'action_link' => "/admin/bookings/disputes/1"
    ];

    // Chat Violation (Mocked)
    $actionQueue[] = [
        'entity' => 'Chat Violation',
        'description' => "Phone number detected in chat (Sarah M. & Host J.).",
        'severity' => 'Medium',
        'time' => date('Y-m-d H:i:s', strtotime('-1 hour')),
        'action' => 'Open chat',
        'action_link' => "/admin/chats/123"
    ];

    // 4. Recently Added Listings
    $recentListings = [];
    $stmtRecent = $pdo->query("
        SELECT p.id, p.name, p.address, p.city, p.status, p.price, u.first_name, u.last_name, p.created_at
        FROM properties p
        JOIN users u ON p.host_id = u.id
        ORDER BY p.created_at DESC
        LIMIT 5
    ");
    while ($row = $stmtRecent->fetch()) {
        $recentListings[] = [
            'id' => $row['id'],
            'name' => $row['name'],
            'location' => $row['city'] . ", " . $row['address'],
            'host' => $row['first_name'] . " " . $row['last_name'],
            'status' => ucfirst($row['status']),
            'price' => "₦" . number_format($row['price'], 2),
            'time' => $row['created_at']
        ];
    }

    // 5. Activities (Timeline)
    $activities = [];
    
    // Mix of New Listings and Admin Actions
    $stmtActs = $pdo->query("
        SELECT 'listing' as type, p.name, u.first_name, p.created_at 
        FROM properties p 
        JOIN users u ON p.host_id = u.id 
        ORDER BY p.created_at DESC 
        LIMIT 5
    ");
    while ($row = $stmtActs->fetch()) {
        $activities[] = [
            'title' => 'New listing added',
            'description' => "The host " . $row['first_name'] . ", has submitted a new property for verification. Review it now.",
            'time' => $row['created_at'],
            'date_group' => (date('Y-m-d', strtotime($row['created_at'])) === date('Y-m-d')) ? 'Today' : 'Yesterday'
        ];
    }

    // Add a mocked recurring activity if list is short
    if (count($activities) < 3) {
        $activities[] = [
            'title' => 'Admin released payout',
            'description' => "The balance for host Alexander has been cleared and sent to bank.",
            'time' => date('Y-m-d H:i:s', strtotime('-2 hours')),
            'date_group' => 'Today'
        ];
    }

    send_success('Admin dashboard loaded successfully.', [
        'overview' => [
            'total_users' => [
                'value' => (int)$totalUsers,
                'verified_value' => (int)$verifiedUsers,
                'host_value' => (int)$hostUsers,
                'guest_value' => (int)$guestUsers,
                'growth' => (int)$usersGrowth,
                'label' => 'Total users'
            ],
            'active_listings' => [
                'value' => (int)$activeListings,
                'growth' => (int)$listingsGrowth,
                'label' => 'Active listings'
            ],
            'active_bookings' => [
                'value' => (int)$activeBookings,
                'growth' => (int)$bookingsGrowth,
                'label' => 'Active bookings'
            ],
            'total_earnings' => [
                'value' => (float)$totalEarnings,
                'growth' => (int)$earningsGrowth,
                'label' => 'Total earnings',
                'formatted' => "₦" . number_format($totalEarnings, 2)
            ]
        ],
        'action_queue' => $actionQueue,
        'recent_listings' => $recentListings,
        'activities' => $activities
    ]);

} catch (Exception $e) {
    error_log("Dashboard error: " . $e->getMessage());
    send_error('Failed to load dashboard data.', [], 500);
}
