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

try {
    $pdo = Database::getInstance();
    $period = $_GET['period'] ?? '30_days';

    // Date Logic
    $interval = '30 DAY';
    if ($period === '7_days') $interval = '7 DAY';
    if ($period === '1_year') $interval = '1 YEAR';

    // Stats Queries
    // Total Users
    $stmtUsers = $pdo->query("SELECT COUNT(*) FROM users");
    if (!$stmtUsers) {
        throw new Exception("Users query failed: " . print_r($pdo->errorInfo(), true));
    }
    $totalUsers = $stmtUsers->fetchColumn();

    // New Users (with error check)
    $stmtNewUsers = $pdo->prepare("SELECT COUNT(*) FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL :interval)");
    // Since we can't bind INTERVAL directly in some SQL modes easily without concat, let use the previous logic but safer
    // Actually safe way:
    $intervalStr = "-30 DAY";
    if ($period === '7_days') $intervalStr = "-7 DAY";
    if ($period === '1_year') $intervalStr = "-1 YEAR";
    
    // Use strtotime for safer date calculation in PHP
    $dateThreshold = date('Y-m-d H:i:s', strtotime($intervalStr));
    
    // Re-run safely
    $newUsers = $pdo->query("SELECT COUNT(*) FROM users WHERE created_at >= '$dateThreshold'")->fetchColumn();

    // Listings
    $totalProperties = $pdo->query("SELECT COUNT(*) FROM properties WHERE status = 'published'")->fetchColumn();
    $newProperties = $pdo->query("SELECT COUNT(*) FROM properties WHERE status = 'published' AND created_at >= DATE_SUB(NOW(), INTERVAL $interval)")->fetchColumn();

    // Bookings
    $activeBookings = $pdo->query("SELECT COUNT(*) FROM bookings WHERE status IN ('confirmed', 'active')")->fetchColumn();
    $newBookings = $pdo->query("SELECT COUNT(*) FROM bookings WHERE status IN ('confirmed', 'active') AND created_at >= DATE_SUB(NOW(), INTERVAL $interval)")->fetchColumn();

    // Revenue
    $totalRevenue = $pdo->query("SELECT SUM(amount) FROM transactions WHERE status = 'success'")->fetchColumn() ?: 0;
    $newRevenue = $pdo->query("SELECT SUM(amount) FROM transactions WHERE status = 'success' AND created_at >= DATE_SUB(NOW(), INTERVAL $interval)")->fetchColumn() ?: 0;

    // Action Queue logic (Mock concepts with real queries)
    $actionQueue = [];

    // 1. Listings pending verify > 24h
    $pendingProps = $pdo->query("
        SELECT id, name, created_at 
        FROM properties 
        WHERE status = 'pending' AND created_at < DATE_SUB(NOW(), INTERVAL 24 HOUR)
        LIMIT 3
    ")->fetchAll(PDO::FETCH_ASSOC);

    foreach($pendingProps as $p) {
        $actionQueue[] = [
            'entity' => 'Listing Review Delay',
            'desc' => "Property: {$p['name']}",
            'severity' => 'High',
            'time' => $p['created_at'], // In real app use relative time formatter
            'action_link' => "property_view.php?id={$p['id']}",
            'action_text' => 'Review listing'
        ];
    }

    // 2. Pending KYC
    $pendingKYC = $pdo->query("SELECT id, user_id, created_at FROM kyc WHERE status = 'pending' LIMIT 2")->fetchAll(PDO::FETCH_ASSOC);
    foreach($pendingKYC as $k) {
        $actionQueue[] = [
            'entity' => 'KYC Verification',
            'desc' => "User ID: {$k['user_id']} pending verification",
            'severity' => 'Medium',
            'time' => $k['created_at'],
            'action_link' => "kyc.php",
            'action_text' => 'Verify user'
        ];
    }

    // Activities (Combined Stream)
    $activities = [];
    
    // New Users
    $recentUsers = $pdo->query("SELECT first_name, last_name, created_at FROM users ORDER BY created_at DESC LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);
    foreach($recentUsers as $u) {
        $activities[] = [
            'title' => 'New user registration',
            'time' => $u['created_at'],
            'desc' => "{$u['first_name']} {$u['last_name']} joined the platform.",
            'type' => 'user'
        ];
    }

    // New Properties
    $recentProps = $pdo->query("SELECT p.name, u.first_name, u.last_name, p.created_at FROM properties p JOIN users u ON p.host_id = u.id ORDER BY p.created_at DESC LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);
    foreach($recentProps as $p) {
        $activities[] = [
            'title' => 'New listing added',
            'time' => $p['created_at'],
            'desc' => "The host {$p['first_name']}, has submitted '{$p['name']}' for verification.",
            'type' => 'property'
        ];
    }

    // Sort activities by time
    usort($activities, function($a, $b) {
        return strtotime($b['time']) - strtotime($a['time']);
    });
    $activities = array_slice($activities, 0, 5); // key 5 most recent

    // Recently Added Listings Table
    $recentListingsTable = $pdo->query("
        SELECT p.*, u.first_name, u.last_name 
        FROM properties p 
        JOIN users u ON p.host_id = u.id 
        ORDER BY p.created_at DESC 
        LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC);


    send_success('Dashboard stats retrieved.', [
        'period' => $period,
        'stats' => [
            'users' => [
                'total' => (int)$totalUsers,
                'growth' => $totalUsers > 0 ? round(($newUsers / $totalUsers) * 100) : 0
            ],
            'listings' => [
                'total' => (int)$totalProperties,
                'growth' => $totalProperties > 0 ? round(($newProperties / $totalProperties) * 100) : 0
            ],
            'bookings' => [
                'total' => (int)$activeBookings,
                'growth' => $activeBookings > 0 ? round(($newBookings / $activeBookings) * 100) : 0
            ],
            'revenue' => [
                'total' => (float)$totalRevenue,
                'growth' => $totalRevenue > 0 ? round(($newRevenue / $totalRevenue) * 100) : 0
            ]
        ],
        'action_queue' => $actionQueue,
        'activities' => $activities,
        'recent_listings' => $recentListingsTable
    ]);

} catch (Exception $e) {
    error_log("Admin stats error: " . $e->getMessage());
    // In production, hide detailed errors. In dev, showing it helps.
    send_error('Failed to retrieve stats: ' . $e->getMessage(), [], 500);
}
