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
    send_error('Only hosts can access property listings.', [], 403);
}

$pdo = Database::getInstance();

// Get filter parameters
$status = $_GET['status'] ?? null; // 'draft', 'pending', 'active', 'inactive'
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = isset($_GET['limit']) ? min(50, max(1, intval($_GET['limit']))) : 20;
$offset = ($page - 1) * $limit;

try {
    // Build query with filters
    $where = ["host_id = ?"];
    $params = [$userId];

    if ($status && in_array($status, ['draft', 'pending', 'active', 'inactive'])) {
        $where[] = "status = ?";
        $params[] = $status;
    }

    $whereClause = implode(' AND ', $where);

    // Get total count
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM properties WHERE $whereClause");
    $stmt->execute($params);
    $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Get properties
    $params[] = $limit;
    $params[] = $offset;
    
    $stmt = $pdo->prepare("
        SELECT 
            id, name, address, city, state, country,
            type, space_type, guests_max, bedrooms, bathrooms, beds,
            price, status, onboarding_step, created_at,
            latitude, longitude
        FROM properties 
        WHERE $whereClause
        ORDER BY created_at DESC 
        LIMIT ? OFFSET ?
    ");
    $stmt->execute($params);
    $properties = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get images for each property
    foreach ($properties as &$property) {
        $stmt = $pdo->prepare("SELECT image_url FROM property_images WHERE property_id = ? ORDER BY display_order ASC LIMIT 1");
        $stmt->execute([$property['id']]);
        $image = $stmt->fetch(PDO::FETCH_ASSOC);
        $property['main_image'] = $image['image_url'] ?? null;
        
        $property['price'] = (float)$property['price'];
        $property['formatted_price'] = '₦' . number_format($property['price'], 2);
    }

    $totalPages = ceil($total / $limit);

    send_success('Properties retrieved successfully.', [
        'properties' => $properties,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total_items' => (int)$total,
            'items_per_page' => $limit
        ]
    ]);

} catch (Exception $e) {
    error_log("Get host properties error for user ID $userId: " . $e->getMessage());
    send_error('Failed to retrieve properties.', [], 500);
}
