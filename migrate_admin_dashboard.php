<?php
require_once __DIR__ . '/config/env.php';
require_once __DIR__ . '/utils/db.php';

try {
    $pdo = Database::getInstance();
    
    // 1. Create system_activities table
    $pdo->exec("CREATE TABLE IF NOT EXISTS system_activities (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        user_id BIGINT NULL,
        type VARCHAR(50) NOT NULL,
        description TEXT NOT NULL,
        severity ENUM('low', 'medium', 'high', 'critical') DEFAULT 'low',
        related_id BIGINT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_type (type),
        INDEX idx_created (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // 2. Create disputes table (for Action Queue)
    $pdo->exec("CREATE TABLE IF NOT EXISTS disputes (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        booking_id BIGINT NOT NULL,
        reporter_id BIGINT NOT NULL,
        reason TEXT NOT NULL,
        status ENUM('pending', 'investigating', 'resolved', 'dismissed') DEFAULT 'pending',
        severity ENUM('medium', 'high', 'critical') DEFAULT 'medium',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
        FOREIGN KEY (reporter_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    echo "Admin tables created successfully.";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
