<?php

require_once __DIR__ . '/../config.php'; // CORS and common API setup
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../utils/db.php'; // Database connection
require_once __DIR__ . '/../../utils/response.php'; // JSON response handler
require_once __DIR__ . '/../../utils/jwt.php'; // JWT authentication

// Load payment configuration fees
// This file is assumed to be available and contains $config['fees'] array
if (!file_exists(__DIR__ . '/../../config/fees.php')) {
    // Handle case where config file is missing, though it should be created earlier.
    error_log("Config file config/fees.php not found.");
    send_error("Configuration error: Fees configuration not found.", [], 500);
}
require_once __DIR__ . '/../../config/fees.php';


// --- JWT Authentication ---
$userData = JWTManager::authenticate();
$guest_id = $userData['user_id'] ?? null;

if (!$guest_id) {
    send_error("Unauthorized. Invalid or missing token.", [], 401);
}

// --- Input Validation ---
$input = json_decode(file_get_contents('php://input'), true);

$property_id = $input['property_id'] ?? null;
$check_in_str = $input['check_in'] ?? null;
$check_out_str = $input['check_out'] ?? null;
$adults = isset($input['adults']) ? (int)$input['adults'] : null;
$children = isset($input['children']) ? (int)$input['children'] : null;
$rooms = isset($input['rooms']) ? (int)$input['rooms'] : null;

// Basic validation
if (!$property_id || !$check_in_str || !$check_out_str || $adults === null || $children === null || $rooms === null) {
    send_error("Missing required fields. Please provide property_id, check_in, check_out, adults, children, and rooms.");
}

// Date validation
$check_in_date = DateTime::createFromFormat('Y-m-d', $check_in_str);
$check_out_date = DateTime::createFromFormat('Y-m-d', $check_out_str);
$today = new DateTime();

if (!$check_in_date || !$check_out_date) {
    send_error("Invalid date format. Please use YYYY-MM-DD.");
}

if ($check_in_date < $today->setTime(0,0,0)) {
     send_error("Check-in date must be today or in the future.");
}

if ($check_out_date <= $check_in_date) {
    send_error("Check-out date must be after check-in date.");
}

// Numeric validation
if ($adults < 0 || $children < 0 || $rooms < 0) {
    send_error("Number of adults, children, and rooms cannot be negative.");
}

// --- Database Operations ---
try {
    $pdo = get_db_connection(); // Get database connection

    // Fetch property details including price and host_id
    $stmt = $pdo->prepare("
        SELECT id, name, price, price_type, host_id
        FROM properties
        WHERE id = :property_id
    ");
    $stmt->bindParam(':property_id', $property_id);
    $stmt->execute();
    $property = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$property) {
    send_error("Property not found.", [], 404);
}

    // --- Fee Configuration ---
    // Ensure fees are loaded and are numeric
    $caution_fee_config = isset($config['fees']['caution_fee']) ? (float)$config['fees']['caution_fee'] : 0.00;
    $service_fee_percent_config = isset($config['fees']['service_fee_percentage']) ? (float)$config['fees']['service_fee_percentage'] : 0.00;
    $tax_percent_config = isset($config['fees']['tax_percentage']) ? (float)$config['fees']['tax_percentage'] : 0.00;

    // --- Calculations ---

    // 1. Calculate nights
    // Date interval in days
    $interval = $check_in_date->diff($check_out_date);
    $nights = $interval->days;

    // Ensure at least 1 night if check-out is the day after check-in (edge case)
    if ($nights === 0 && $check_out_date > $check_in_date) {
        $nights = 1;
    }
    // If somehow nights is still 0, it means check_out is not after check_in, already caught by validation.

    // 2. Calculate rent amount
    // Assuming 'price' in properties table is per night.
    // If price_type is different (e.g., 'per_week'), this logic needs adjustment.
    // For now, we'll assume price is daily rate if price_type is 'per_night'.
    $rent_amount = 0.00;
    if ($property['price_type'] === 'per_night' && $property['price'] !== null) {
        $rent_amount = (float)$property['price'] * $nights;
    } else {
        // Handle other price types or default to 0 if price is not set or type is unknown.
        // For example, if price_type were 'per_week', you'd adjust calculation.
        // For now, we'll log a warning if price_type is unexpected and rent is 0.
        if ($property['price'] === null) {
            error_log("Property {$property_id} has no price set.");
        } else {
             error_log("Unsupported price_type '{$property['price_type']}' for property {$property_id}. Assuming rent is 0.");
        }
        $rent_amount = 0.00; // Default to 0 if price not available or type not handled
    }


    // 3. Calculate caution fee (fixed amount from config)
    $caution_fee = $caution_fee_config;

    // 4. Calculate service fee (percentage of rent amount)
    $service_fee = ($rent_amount * $service_fee_percent_config) / 100.0;

    // 5. Calculate tax amount (percentage of rent amount)
    $tax_amount = ($rent_amount * $tax_percent_config) / 100.0;

    // 6. Calculate total amount
    $total_amount = $rent_amount + $caution_fee + $service_fee + $tax_amount;

    // Prepare response data
    $booking_details = [
        'property_id' => (int)$property_id,
        'guest_id' => (int)$guest_id,
        'host_id' => (int)$property['host_id'],
        'check_in' => $check_in_date->format('Y-m-d'),
        'check_out' => $check_out_date->format('Y-m-d'),
        'nights' => (int)$nights,
        'adults' => (int)$adults,
        'children' => (int)$children,
        'rooms' => (int)$rooms,
        'rent_amount' => round($rent_amount, 2),
        'caution_fee' => round($caution_fee, 2),
        'service_fee' => round($service_fee, 2),
        'tax_amount' => round($tax_amount, 2),
        'total_amount' => round($total_amount, 2)
    ];

    send_success("Booking calculation completed.", ["booking_calculation" => $booking_details]);

} catch (PDOException $e) {
    error_log("Database error during booking calculation: " . $e->getMessage());
    send_error("Database error. Could not calculate booking.", [], 500);
} catch (Exception $e) {
    error_log("General error during booking calculation: " . $e->getMessage());
    send_error("An unexpected error occurred during booking calculation.", [], 500);
}