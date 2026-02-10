<?php
require_once __DIR__ . '/config/env.php';
require_once __DIR__ . '/utils/db.php';

try {
    $pdo = Database::getInstance();
    
    // Check columns
    $columns = $pdo->query("SHOW COLUMNS FROM notifications")->fetchAll(PDO::FETCH_COLUMN);
    
    // Add 'title' if missing
    if (!in_array('title', $columns)) {
        // Add title column with default value
        $pdo->exec("ALTER TABLE notifications ADD COLUMN title VARCHAR(255) DEFAULT 'System Notification' AFTER user_id");
        echo "Added 'title' column.\n";
        
        // Update existing records to have a meaningful title based on message?
        // Or just leave default 'System Notification'
    }

    // Add 'type' if missing
    if (!in_array('type', $columns)) {
        $pdo->exec("ALTER TABLE notifications ADD COLUMN type VARCHAR(50) DEFAULT 'info' AFTER message");
        echo "Added 'type' column.\n";
    }

    // Optional: User mentioned "adding body". 'message' exists and serves as body. 
    // If we want to be explicit, we could add 'body' or just alias it in API.
    // I'll stick to 'message' as the content since the table already has it and contains data.

    echo "Notifications table updated successfully.\n";

} catch (PDOException $e) {
    die("DB Error: " . $e->getMessage());
}
?>
