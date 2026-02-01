<?php

require_once __DIR__ . '/../config/env.php';

class Database {
    private static ?PDO $instance = null;
    private string $host = DB_HOST;
    private string $db_name = DB_NAME;
    private string $username = DB_USER;
    private string $password = DB_PASS;
    private string $charset = 'utf8mb4';

    private function __construct() {
        // Private constructor to prevent direct instantiation
    }

    public static function getInstance(): PDO {
        if (self::$instance === null) {
            self::$instance = self::connect();
        }
        return self::$instance;
    }

    private static function connect(): PDO {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
            return $pdo;
        } catch (PDOException $e) {
            // In a production environment, you would log this error instead of echoing it
            // For development, echoing is acceptable.
            http_response_code(500);
            echo json_encode(['error' => 'Database connection error: ' . $e->getMessage()]);
            exit();
        }
    }
}

/**
 * Global helper for retrieving the PDO database connection.
 */
function get_db_connection(): PDO {
    return Database::getInstance();
}
