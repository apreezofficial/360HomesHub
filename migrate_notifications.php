<?php
require_once __DIR__ . '/config/env.php';
require_once __DIR__ . '/utils/db.php';

try {
    $pdo = Database::getInstance();

    // Create notifications table
    $pdo->exec("CREATE TABLE IF NOT EXISTS notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NULL, -- NULL for system-wide admin notifications, or specific user ID
        title VARCHAR(255) NOT NULL,
        message TEXT,
        type VARCHAR(50) DEFAULT 'info', -- info, success, warning, error
        is_read TINYINT(1) DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX (user_id)
    )");
    echo "Created notifications table.\n";

    // Seed dummy notifications
    $stmt = $pdo->query("SELECT COUNT(*) FROM notifications");
    if ($stmt->fetchColumn() == 0) {
        $notifications = [
            ['New User Registration', 'John Doe has registered as a Host.', 'info'],
            ['Booking Confirmed', 'Booking #10234 confirmed by Host.', 'success'],
            ['Payment Received', 'Payment of $450.00 received for Booking #10234.', 'success'],
            ['New Property Listing', 'Property "Luxury Villa" is pending approval.', 'warning'],
            ['System Alert', 'Database backup completed successfully.', 'info']
        ];

        $ins = $pdo->prepare("INSERT INTO notifications (title, message, type, created_at) VALUES (?, ?, ?, DATE_SUB(NOW(), INTERVAL ? HOUR))");
        
        foreach ($notifications as $i => $n) {
            $ins->execute([$n[0], $n[1], $n[2], $i * 2]); // Spread out over hours
        }
        echo "Seeded notifications.\n";
    }

} catch (PDOException $e) {
    die("DB Error: " . $e->getMessage());
}
?>
