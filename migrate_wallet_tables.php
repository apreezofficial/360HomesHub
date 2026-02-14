<?php

require_once __DIR__ . '/config/env.php';
require_once __DIR__ . '/utils/db.php';

$pdo = Database::getInstance();

try {
    echo "Creating wallet and transaction tables...\n";

    // Create wallets table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS wallets (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT NOT NULL,
            balance DECIMAL(15, 2) DEFAULT 0.00,
            currency VARCHAR(3) DEFAULT 'NGN',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            UNIQUE KEY unique_user_wallet (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    echo "✓ Wallets table created\n";

    // Create payout_methods table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS payout_methods (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT NOT NULL,
            bank_name VARCHAR(255) NOT NULL,
            account_holder_name VARCHAR(255) NOT NULL,
            account_number VARCHAR(50) NOT NULL,
            swift_bic_code VARCHAR(20),
            is_default TINYINT(1) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    echo "✓ Payout methods table created\n";

    // Create transactions table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS transactions (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT NOT NULL,
            wallet_id BIGINT NOT NULL,
            type ENUM('credit', 'debit') NOT NULL,
            category ENUM('booking_payment', 'booking_refund', 'host_earning', 'withdrawal', 'deposit', 'fee', 'other') NOT NULL,
            amount DECIMAL(15, 2) NOT NULL,
            balance_before DECIMAL(15, 2) NOT NULL,
            balance_after DECIMAL(15, 2) NOT NULL,
            reference VARCHAR(255) UNIQUE NOT NULL,
            description TEXT,
            metadata JSON,
            status ENUM('pending', 'processing', 'completed', 'failed', 'cancelled') DEFAULT 'pending',
            related_booking_id BIGINT,
            related_withdrawal_id BIGINT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (wallet_id) REFERENCES wallets(id) ON DELETE CASCADE,
            INDEX idx_user_created (user_id, created_at DESC),
            INDEX idx_reference (reference),
            INDEX idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    echo "✓ Transactions table created\n";

    // Create withdrawals table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS withdrawals (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT NOT NULL,
            wallet_id BIGINT NOT NULL,
            payout_method_id BIGINT NOT NULL,
            amount DECIMAL(15, 2) NOT NULL,
            status ENUM('pending', 'processing', 'completed', 'failed', 'cancelled') DEFAULT 'pending',
            reference VARCHAR(255) UNIQUE NOT NULL,
            bank_name VARCHAR(255),
            account_number VARCHAR(50),
            account_holder_name VARCHAR(255),
            admin_note TEXT,
            processed_at TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (wallet_id) REFERENCES wallets(id) ON DELETE CASCADE,
            FOREIGN KEY (payout_method_id) REFERENCES payout_methods(id),
            INDEX idx_user_status (user_id, status),
            INDEX idx_reference (reference)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    echo "✓ Withdrawals table created\n";

    echo "\n✅ All wallet and transaction tables created successfully!\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
