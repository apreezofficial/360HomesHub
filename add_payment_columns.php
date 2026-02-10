<?php
require_once __DIR__ . '/utils/db.php';

try {
    $pdo = Database::getInstance();
    $pdo->exec("ALTER TABLE bookings ADD COLUMN payment_link VARCHAR(255) DEFAULT NULL");
    $pdo->exec("ALTER TABLE bookings ADD COLUMN payment_ref VARCHAR(100) DEFAULT NULL");
    echo "Columns added successfully.";
} catch (PDOException $e) {
    echo "Error (might already exist): " . $e->getMessage();
}
