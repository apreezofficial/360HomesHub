<?php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../utils/db.php'; // Database connection
require_once __DIR__ . '/../../utils/response.php'; // JSON response handler
require_once __DIR__ . '/../../utils/jwt.php'; // JWT authentication
require_once __DIR__ . '/../../api/notifications/notify.php'; // Notification helper

header("Content-Type: application/json");

// --- JWT Authentication ---
$userData = JWTManager::authenticate();
$guest_id = $userData['user_id'] ?? null;

if (!$guest_id) {
    send_json_response(401, ["message" => "Unauthorized. Invalid or missing token."]);
    exit;
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
    send_json_response(400, ["message" => "Missing required fields. Please provide property_id, check_in, check_out, adults, children, and rooms."]);
    exit;
}

// Date validation
$check_in_date = DateTime::createFromFormat('Y-m-d', $check_in_str);
$check_out_date = DateTime::createFromFormat('Y-m-d', $check_out_str);
$today = new DateTime();

if (!$check_in_date || !$check_out_date) {
    send_json_response(400, ["message" => "Invalid date format. Please use YYYY-MM-DD."]);
    exit;
}

if ($check_in_date < $today->setTime(0,0,0)) {
     send_json_response(400, ["message" => "Check-in date must be today or in the future."]);
    exit;
}

if ($check_out_date <= $check_in_date) {
    send_json_response(400, ["message" => "Check-out date must be after check-in date."]);
    exit;
}

// Numeric validation
if ($adults < 0 || $children < 0 || $rooms < 0) {
    send_json_response(400, ["message" => "Number of adults, children, and rooms cannot be negative."]);
    exit;
}

// --- Database Operations ---
try {
    $pdo = get_db_connection(); // Get database connection

    // Fetch property details to get host_id and validate property existence
    $stmt = $pdo->prepare("
        SELECT id, host_id, price, price_type
        FROM properties
        WHERE id = :property_id
    ");
    $stmt->bindParam(':property_id', $property_id);
    $stmt->execute();
    $property = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$property) {
        send_json_response(404, ["message" => "Property not found."]);
        exit;
    }

    $host_id = $property['host_id'];

    // --- Calculations (Re-calculating details for consistency, could be improved by passing calculation result) ---
    $interval = $check_in_date->diff($check_out_date);
    $nights = $interval->days;
     if ($nights === 0 && $check_out_date > $check_in_date) { // Handle case where check-out is next day
        $nights = 1;
    }

    // Fetch fees from config
    if (!isset($config['fees']['caution_fee']) || !isset($config['fees']['service_fee_percentage']) || !isset($config['fees']['tax_percentage'])) {
         error_log("Payment fees configuration is incomplete.");
         send_json_response(500, ["message" => "Configuration error: Missing fee details."]);
         exit;
    }
    $caution_fee_config = (float)$config['fees']['caution_fee'];
    $service_fee_percent_config = (float)$config['fees']['service_fee_percentage'];
    $tax_percent_config = (float)$config['fees']['tax_percentage'];

    // Calculate rent amount (assuming price is per night if price_type is 'per_night')
    $rent_amount = 0.00;
    if ($property['price_type'] === 'per_night' && $property['price'] !== null) {
        $rent_amount = (float)$property['price'] * $nights;
    } else {
        error_log("Unsupported price_type '{$property['price_type']}' for property {$property_id}. Rent calculation may be inaccurate.");
        // Potentially fallback or throw error depending on business logic
        // For now, assume rent is 0 if price type is not 'per_night' or price is null
        $rent_amount = 0.00;
    }

    $caution_fee = $caution_fee_config;
    $service_fee = ($rent_amount * $service_fee_percent_config) / 100.0;
    $tax_amount = ($rent_amount * $tax_percent_config) / 100.0;
    $total_amount = $rent_amount + $caution_fee + $service_fee + $tax_amount;

    // --- Create Booking ---
    $sql = "
        INSERT INTO bookings (property_id, guest_id, host_id, check_in, check_out, nights, adults, children, rooms, rent_amount, caution_fee, service_fee, tax_amount, total_amount, status, created_at)
        VALUES (:property_id, :guest_id, :host_id, :check_in, :check_out, :nights, :adults, :children, :rooms, :rent_amount, :caution_fee, :service_fee, :tax_amount, :total_amount, 'pending', CURRENT_TIMESTAMP)
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':property_id', $property_id, PDO::PARAM_INT);
    $stmt->bindParam(':guest_id', $guest_id, PDO::PARAM_INT);
    $stmt->bindParam(':host_id', $host_id, PDO::PARAM_INT);
    $stmt->bindValue(':check_in', $check_in_date->format('Y-m-d'), PDO::PARAM_STR);
    $stmt->bindValue(':check_out', $check_out_date->format('Y-m-d'), PDO::PARAM_STR);
    $stmt->bindParam(':nights', $nights, PDO::PARAM_INT);
    $stmt->bindParam(':adults', $adults, PDO::PARAM_INT);
    $stmt->bindParam(':children', $children, PDO::PARAM_INT);
    $stmt->bindParam(':rooms', $rooms, PDO::PARAM_INT);
    $stmt->bindValue(':rent_amount', round($rent_amount, 2), PDO::PARAM_STR);
    $stmt->bindValue(':caution_fee', round($caution_fee, 2), PDO::PARAM_STR);
    $stmt->bindValue(':service_fee', round($service_fee, 2), PDO::PARAM_STR);
    $stmt->bindValue(':tax_amount', round($tax_amount, 2), PDO::PARAM_STR);
    $stmt->bindValue(':total_amount', round($total_amount, 2), PDO::PARAM_STR);

    if ($stmt->execute()) {
        $booking_id = $pdo->lastInsertId();

        // --- Send Notifications ---
        $property_name_stmt = $pdo->prepare("SELECT name FROM properties WHERE id = :property_id");
        $property_name_stmt->bindParam(':property_id', $property_id);
        $property_name_stmt->execute();
        $property_name = $property_name_stmt->fetchColumn() ?: 'a property';

        // Notify Host (Important)
        sendNotification($host_id, "New Booking Request", "A new booking request has been made for your property: {$property_name} from {$check_in_date->format('Y-m-d')} to {$check_out_date->format('Y-m-d')}.", 'important');

        // Notify Guest (Normal)
        sendNotification($guest_id, "Booking Request Sent", "Your booking request for {$property_name} has been submitted. It is pending host approval.", 'normal');

        // Notify Admin (Important)
        // Assuming an admin user ID or a way to identify admins. For now, hardcoding admin_id = 1 or looking up an admin role if available.
        // If no specific admin user ID is known, you might need to query for users with 'admin' role.
        // For simplicity, let's assume a primary admin user with ID 1.
        $admin_user_id = 1; // This might need to be dynamic or configurable
        sendNotification($admin_user_id, "New Property Booking", "A new booking request has been submitted for property {$property_id} by guest {$guest_id}.", 'important');


        // Prepare response data
        $created_booking_data = [
            'id' => (int)$booking_id,
            'property_id' => (int)$property_id,
            'guest_id' => (int)$guest_id,
            'host_id' => (int)$host_id,
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
            'total_amount' => round($total_amount, 2),
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s') // Get current timestamp
        ];

        send_json_response(201, ["message" => "Booking request created successfully.", "booking" => $created_booking_data]);
    } else {
        send_json_response(500, ["message" => "Failed to create booking request."]);
    }

} catch (PDOException $e) {
    error_log("Database error during booking creation: " . $e->getMessage());
    send_json_response(500, ["message" => "Database error. Could not create booking."]);
} catch (Exception $e) {
    error_log("General error during booking creation: " . $e->getMessage());
    send_json_response(500, ["message" => "An unexpected error occurred during booking creation."]);
}
?>

/*
 * Example Request JSON:
 * {
 *   "property_id": 1,
 *   "check_in": "2026-01-25",
 *   "check_out": "2026-01-28",
 *   "adults": 2,
 *   "children": 1,
 *   "rooms": 1
 * }
 */

/*
 * Example Response JSON (Success - 201 Created):
 * {
 *   "message": "Booking request created successfully.",
 *   "booking": {
 *     "id": 5, // Newly created booking ID
 *     "property_id": 1,
 *     "guest_id": 101, // Authenticated user's ID
 *     "host_id": 5,
 *     "check_in": "2026-01-25",
 *     "check_out": "2026-01-28",
 *     "nights": 3,
 *     "adults": 2,
 *     "children": 1,
 *     "rooms": 1,
 *     "rent_amount": 300.00,
 *     "caution_fee": 50.00,
 *     "service_fee": 30.00,
 *     "tax_amount": 15.00,
 *     "total_amount": 395.00,
 *     "status": "pending",
 *     "created_at": "2026-01-20 15:45:00" // Example timestamp
 *   }
 * }
 */

/*
 * Example Response JSON (Error - Missing Fields):
 * {
 *   "message": "Missing required fields. Please provide property_id, check_in, check_out, adults, children, and rooms."
 * }
 */

/*
 * Example Response JSON (Error - Property Not Found):
 * {
 *   "message": "Property not found."
 * }
 */

/*
 * Example Response JSON (Error - Invalid Dates):
 * {
 *   "message": "Check-out date must be after check-in date."
 * }
 */