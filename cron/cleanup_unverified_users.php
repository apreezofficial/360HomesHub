<?php

require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../utils/db.php';

$pdo = Database::getInstance();

try {
    echo "Running cleanup of unverified users...\n";

    // Delete users who are still in 'otp' step and created more than 1 hour ago
    $stmt = $pdo->prepare("DELETE FROM users WHERE onboarding_step = 'otp' AND created_at < (NOW() - INTERVAL 1 HOUR)");
    $stmt->execute();

    $deletedCount = $stmt->rowCount();

    echo "Cleanup completed. Deleted $deletedCount unverified users.\n";

} catch (Exception $e) {
    echo "Error during cleanup: " . $e->getMessage() . "\n";
    exit(1);
}
