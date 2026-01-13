<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/response.php'; // For error handling

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JWTManager {

    public static function generateToken(array $data): string {
        $issuedAt = time();
        $expirationTime = $issuedAt + JWT_EXPIRATION_TIME; // Expiration time
        $payload = [
            'iat'  => $issuedAt,
            'exp'  => $expirationTime,
            'data' => $data // Contains user information
        ];
        return JWT::encode($payload, JWT_SECRET, JWT_ALGORITHM);
    }

    public static function validateToken(string $token): ?array {
        try {
            $decoded = JWT::decode($token, new Key(JWT_SECRET, JWT_ALGORITHM));
            return (array) $decoded->data;
        } catch (Exception $e) {
            // Log the error for debugging, but don't expose too much info to the client
            // error_log("JWT Validation Error: " . $e->getMessage());
            return null;
        }
    }

    public static function authenticate(): ?array {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? '';

        if (empty($authHeader) || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            send_error('Unauthorized: Bearer token not provided.', [], 401);
            return null; // This line will not be reached due to exit() in send_error
        }

        $token = $matches[1];
        $userData = self::validateToken($token);

        if ($userData === null) {
            send_error('Unauthorized: Invalid or expired token.', [], 401);
            return null; // This line will not be reached
        }

        return $userData;
    }
}
