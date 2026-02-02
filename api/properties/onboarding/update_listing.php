<?php

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../config/env.php';
require_once __DIR__ . '/../../../utils/db.php';
require_once __DIR__ . '/../../../utils/response.php';
require_once __DIR__ . '/../../../utils/jwt.php';
require_once __DIR__ . '/../../../utils/notify_admin.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_error('Invalid request method.', [], 405);
}

// Authenticate user
$userData = JWTManager::authenticate();
$userId = $userData['user_id'] ?? null;

if (!$userId) {
    send_error('Authentication failed.', [], 401);
}

// Get input data
$data = json_decode(file_get_contents('php://input'), true);

$propertyId = $data['property_id'] ?? null;
$step = $data['onboarding_step'] ?? null;

if (!$propertyId) {
    send_error('Property ID is required.', [], 400);
}

try {
    $pdo = Database::getInstance();

    // Verify ownership
    $stmt = $pdo->prepare("SELECT host_id FROM properties WHERE id = ?");
    $stmt->execute([$propertyId]);
    $propertyHostId = $stmt->fetchColumn();

    if (!$propertyHostId || $propertyHostId != $userId) {
        send_error('Unauthorized: You do not own this listing.', [], 403);
    }

    // List of allowed fields to update
    $allowedFields = [
        'name', 'description', 'type', 'space_type', 'guests_max', 
        'bedrooms', 'bathrooms', 'beds', 'amenities', 'price', 
        'price_type', 'booking_type', 'free_cancellation', 
        'check_in_time', 'check_out_time', 'pets_allowed', 
        'age_restriction', 'smoking_allowed', 'extra_rules', 
        'cancellation_policy', 'availability_type', 'instant_booking',
        'status', 'onboarding_step'
    ];

    $updateFields = [];
    $params = [];

    foreach ($data as $key => $value) {
        if (in_array($key, $allowedFields)) {
            $updateFields[] = "`$key` = ?";
            // Handle JSON fields
            if (in_array($key, ['amenities', 'extra_rules'])) {
                $params[] = is_array($value) ? json_encode($value) : $value;
            } else {
                $params[] = $value;
            }
        }
    }

    if (empty($updateFields)) {
        send_error('No valid fields provided for update.', [], 400);
    }

    $params[] = $propertyId;
    $sql = "UPDATE properties SET " . implode(', ', $updateFields) . " WHERE id = ?";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    // If property is published, notify admin
    if (isset($data['status']) && $data['status'] === 'published') {
        $stmt = $pdo->prepare("SELECT name FROM properties WHERE id = ?");
        $stmt->execute([$propertyId]);
        $propertyName = $stmt->fetchColumn();
        AdminNotification::notify("New Property Published", "A new property '$propertyName' has been published and requires review.", $propertyId);
    }

    send_success('Property updated successfully.', [
        'property_id' => (int)$propertyId,
        'updated_fields' => array_intersect(array_keys($data), $allowedFields)
    ]);

} catch (Exception $e) {
    error_log("Update listing error: " . $e->getMessage());
    send_error('An error occurred while updating property: ' . $e->getMessage(), [], 500);
}
