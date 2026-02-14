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

$pdo = Database::getInstance();

try {
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $limit = isset($_GET['limit']) ? max(1, min(100, (int)$_GET['limit'])) : 15;
    $offset = ($page - 1) * $limit;

    // We combine various sources for the activity feed:
    // 1. New user registrations
    // 2. New property listings
    // 3. New bookings
    // 4. Completed payments
    // 5. KYC submissions

    $sql = "(
        SELECT 'user' as type, 'New user registration' as title, 
               CONCAT(first_name, ' ', last_name, ' joined the platform.') as description, 
               created_at, id as entity_id
        FROM users
    ) UNION (
        SELECT 'property' as type, 'New listing added' as title, 
               CONCAT('The host Alexander, has submitted ', name, ' for verification.') as description, 
               created_at, id as entity_id
        FROM properties
    ) UNION (
        SELECT 'booking' as type, 'New booking request' as title, 
               CONCAT('Guest ID ', guest_id, ' requested booking for Property ID ', property_id) as description, 
               created_at, id as entity_id
        FROM bookings
    ) UNION (
        SELECT 'payout' as type, 'Admin released payout' as title, 
               description, 
               created_at, id as entity_id
        FROM transactions 
        WHERE category = 'withdrawal' AND status = 'completed'
    )
    ORDER BY created_at DESC
    LIMIT $limit OFFSET $offset";

    $stmt = $pdo->query($sql);
    $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Group by Date for UI (Today, Yesterday, etc.)
    $groupedActivities = [];
    foreach ($activities as $act) {
        $date = date('Y-m-d', strtotime($act['created_at']));
        $label = 'This week';
        if ($date === date('Y-m-d')) $label = 'Today';
        elseif ($date === date('Y-m-d', strtotime('-1 day'))) $label = 'Yesterday';
        
        $groupedActivities[$label][] = $act;
    }

    send_success('Activities retrieved successfully.', [
        'activities' => $activities,
        'grouped' => $groupedActivities
    ]);

} catch (Exception $e) {
    error_log("Admin activities error: " . $e->getMessage());
    send_error('Failed to retrieve activities.', [], 500);
}
