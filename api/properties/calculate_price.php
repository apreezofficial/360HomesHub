<?php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../utils/db.php';
require_once __DIR__ . '/../../utils/response.php';
require_once __DIR__ . '/../../utils/jwt.php';

// Load fees configuration
if (file_exists(__DIR__ . '/../../config/fees.php')) {
    require_once __DIR__ . '/../../config/fees.php';
} else {
    $config['fees'] = [
        'caution_fee' => 0.00,
        'service_fee_percentage' => 0.0,
        'tax_percentage' => 0.0
    ];
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_error('Invalid request method.', [], 405);
}

// Authenticate user via JWT
$userData = JWTManager::authenticate();
$userId = $userData['user_id'] ?? null;

if (!$userId) {
    send_error('Authentication failed.', [], 401);
}

// Get input data
$data = json_decode(file_get_contents('php://input'), true);

$propertyId = $data['property_id'] ?? null;
$checkInStr = $data['check_in'] ?? $data['start_date'] ?? null;
$checkOutStr = $data['check_out'] ?? $data['end_date'] ?? null;
$guests = $data['guests'] ?? 1;

if (!$propertyId || !$checkInStr || !$checkOutStr) {
    send_error('Missing required fields: property_id, check_in/start_date, check_out/end_date.', [], 400);
}

try {
    $pdo = Database::getInstance();

    // Fetch property details
    $stmt = $pdo->prepare("SELECT id, name, price, price_type FROM properties WHERE id = ?");
    $stmt->execute([$propertyId]);
    $property = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$property) {
        send_error('Property not found.', [], 404);
    }

    // Date calculations
    $checkIn = new DateTime($checkInStr);
    $checkOut = new DateTime($checkOutStr);
    $interval = $checkIn->diff($checkOut);
    $nights = $interval->days;

    if ($nights <= 0) {
        send_error('Invalid date range. Check-out must be after check-in.', [], 400);
    }

    // Base price calculation
    $basePrice = (float)$property['price'];
    $rentAmount = 0.0;

    if ($property['price_type'] === 'per_night') {
        $rentAmount = $basePrice * $nights;
    } elseif ($property['price_type'] === 'per_week') {
        $rentAmount = ($basePrice / 7) * $nights;
    } elseif ($property['price_type'] === 'per_month') {
        $rentAmount = ($basePrice / 30) * $nights;
    } else {
        // Default to per_night if not specified or unknown
        $rentAmount = $basePrice * $nights;
    }

    // Fees calculation
    $cautionFee = (float)($config['fees']['caution_fee'] ?? 0);
    $serviceFeeRate = (float)($config['fees']['service_fee_percentage'] ?? 0) / 100;
    $taxRate = (float)($config['fees']['tax_percentage'] ?? 0) / 100;

    $serviceFee = $rentAmount * $serviceFeeRate;
    $taxAmount = $rentAmount * $taxRate;
    $totalAmount = $rentAmount + $cautionFee + $serviceFee + $taxAmount;

    send_success('Price calculated successfully.', [
        'property_id' => (int)$propertyId,
        'property_name' => $property['name'],
        'nights' => $nights,
        'rent_amount' => round($rentAmount, 2),
        'caution_fee' => round($cautionFee, 2),
        'service_fee' => round($serviceFee, 2),
        'tax_amount' => round($taxAmount, 2),
        'total_amount' => round($totalAmount, 2),
        'currency' => 'NGN' // Assuming NGN based on previous context
    ]);

} catch (Exception $e) {
    error_log("Calculate price error: " . $e->getMessage());
    send_error('An error occurred while calculating the price: ' . $e->getMessage(), [], 500);
}
