<?php

/**
 * Activity Logger Utility
 * Logs important actions to the activity_logs table
 */

require_once __DIR__ . '/db.php';

class ActivityLogger {
    
    /**
     * Log an activity to the database
     * 
     * @param int|null $user_id User ID performing the action (null for system actions)
     * @param string $action_type Type of action (e.g., 'booking_created', 'payment_initiated')
     * @param string $action_description Human-readable description of the action
     * @param string|null $entity_type Type of entity affected (e.g., 'booking', 'payment')
     * @param int|null $entity_id ID of the affected entity
     * @param array|null $metadata Additional metadata as associative array
     * @return bool Success status
     */
    public static function log(
        ?int $user_id,
        string $action_type,
        string $action_description,
        ?string $entity_type = null,
        ?int $entity_id = null,
        ?array $metadata = null
    ): bool {
        try {
            $pdo = get_db_connection();
            
            // Get IP address
            $ip_address = self::getClientIP();
            
            // Get user agent
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
            
            // Encode metadata as JSON if provided
            $metadata_json = $metadata ? json_encode($metadata) : null;
            
            $stmt = $pdo->prepare("
                INSERT INTO activity_logs 
                (user_id, action_type, action_description, entity_type, entity_id, ip_address, user_agent, metadata)
                VALUES 
                (:user_id, :action_type, :action_description, :entity_type, :entity_id, :ip_address, :user_agent, :metadata)
            ");
            
            $stmt->execute([
                ':user_id' => $user_id,
                ':action_type' => $action_type,
                ':action_description' => $action_description,
                ':entity_type' => $entity_type,
                ':entity_id' => $entity_id,
                ':ip_address' => $ip_address,
                ':user_agent' => $user_agent,
                ':metadata' => $metadata_json
            ]);
            
            return true;
            
        } catch (PDOException $e) {
            error_log("Failed to log activity: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get the real client IP address
     * 
     * @return string|null
     */
    private static function getClientIP(): ?string {
        $ip_keys = [
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        ];
        
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? null;
    }
    
    /**
     * Quick log for booking actions
     */
    public static function logBooking(int $user_id, string $action, int $booking_id, array $metadata = []): bool {
        return self::log(
            $user_id,
            "booking_{$action}",
            "Booking {$action}: ID {$booking_id}",
            'booking',
            $booking_id,
            $metadata
        );
    }
    
    /**
     * Quick log for payment actions
     */
    public static function logPayment(int $user_id, string $action, int $booking_id, array $metadata = []): bool {
        return self::log(
            $user_id,
            "payment_{$action}",
            "Payment {$action} for booking ID {$booking_id}",
            'payment',
            $booking_id,
            $metadata
        );
    }
    
    /**
     * Quick log for property actions
     */
    public static function logProperty(int $user_id, string $action, int $property_id, array $metadata = []): bool {
        return self::log(
            $user_id,
            "property_{$action}",
            "Property {$action}: ID {$property_id}",
            'property',
            $property_id,
            $metadata
        );
    }
    
    /**
     * Quick log for user actions
     */
    public static function logUser(int $user_id, string $action, array $metadata = []): bool {
        return self::log(
            $user_id,
            "user_{$action}",
            "User {$action}",
            'user',
            $user_id,
            $metadata
        );
    }
}
