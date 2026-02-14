<?php

require_once __DIR__ . '/config/env.php';
require_once __DIR__ . '/utils/db.php';

$pdo = Database::getInstance();

try {
    $pdo->beginTransaction();

    echo "Updating users table structure...\n";

    // 1. Add status, message_disabled, booking_disabled
    $sql = "ALTER TABLE users 
            ADD COLUMN status ENUM('verified', 'pending', 'suspended', 'no_kyc') DEFAULT 'no_kyc' AFTER auth_provider,
            ADD COLUMN message_disabled TINYINT(1) DEFAULT 0 AFTER status,
            ADD COLUMN booking_disabled TINYINT(1) DEFAULT 0 AFTER message_disabled";
    
    $pdo->exec($sql);
    echo "Added new columns: status, message_disabled, booking_disabled.\n";

    // 2. Migrate data from is_verified to status
    // If is_verified = 1, status = 'verified', else status = 'no_kyc' (or pending)
    // Given the previous enum was just is_verified, let's map it.
    $pdo->exec("UPDATE users SET status = 'verified' WHERE is_verified = 1");
    $pdo->exec("UPDATE users SET status = 'no_kyc' WHERE is_verified = 0 OR is_verified IS NULL");
    echo "Migrated is_verified data to status.\n";

    // 3. Remove is_verified column
    $pdo->exec("ALTER TABLE users DROP COLUMN is_verified");
    echo "Removed is_verified column.\n";

    $pdo->commit();
    echo "Migration completed successfully!\n";

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
