<?php
require_once __DIR__ . '/config/env.php';
require_once __DIR__ . '/utils/db.php';

try {
    $pdo = Database::getInstance();

    // 1. Add columns to users table if they don't exist
    $columns = $pdo->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_COLUMN);
    
    if (!in_array('last_login', $columns)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN last_login DATETIME NULL");
        echo "Added last_login to users.\n";
    }
    
    if (!in_array('last_ip', $columns)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN last_ip VARCHAR(45) NULL");
        echo "Added last_ip to users.\n";
    }

    // 2. Create audit_logs table
    $pdo->exec("CREATE TABLE IF NOT EXISTS audit_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        admin_id INT NOT NULL,
        action VARCHAR(255) NOT NULL,
        details TEXT,
        ip_address VARCHAR(45),
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX (admin_id)
    )");
    echo "Created audit_logs table.\n";

    // 3. Seed Audit Logs for the current user (admin)
    // First get an admin ID (assuming ID 1 is admin, or current session)
    // We'll insert for the first found admin
    $stmt = $pdo->query("SELECT id FROM users WHERE role IN ('admin', 'super_admin') LIMIT 1");
    $adminId = $stmt->fetchColumn();

    if ($adminId) {
        // Clear existing demo logs to avoid duplicates if re-run (optional, but good for "Seeding")
        // $pdo->exec("DELETE FROM audit_logs WHERE admin_id = $adminId");

        $logs = [
            [
                'action' => 'Modified role - Sarah Jenkins',
                'details' => 'Changed Sarah Jenkins role from Operations to Safety and Trust.',
                'minutes_ago' => 120 // 2 hours
            ],
            [
                'action' => 'Admin login',
                'details' => 'Successfully login via Macbook pro intel i7',
                'minutes_ago' => 180 // 3 hours
            ],
            [
                'action' => 'Security alert',
                'details' => 'Flagged suspicious trade ID: #TR-92837',
                'minutes_ago' => 1440 + 300 // Yesterday (24h + some)
            ],
            [
                'action' => 'Report exported',
                'details' => 'Financial summary (Nov 23, 2024 to Jan 21, 2025) exported.',
                'minutes_ago' => 4320 // 3 days ago (Feb 4 is past)
            ]
        ];

        $stmt = $pdo->prepare("INSERT INTO audit_logs (admin_id, action, details, created_at, ip_address) VALUES (?, ?, ?, DATE_SUB(NOW(), INTERVAL ? MINUTE), '192.168.1.1')");

        foreach ($logs as $log) {
            $stmt->execute([$adminId, $log['action'], $log['details'], $log['minutes_ago']]);
        }
        echo "Seeded audit logs.\n";
        
        // Update user last login
        $pdo->exec("UPDATE users SET last_login = NOW() - INTERVAL 2 HOUR, last_ip = '192.168.1.1' WHERE id = $adminId");
    } else {
        echo "No admin user found to seed logs.\n";
    }

} catch (PDOException $e) {
    die("DB Error: " . $e->getMessage());
}
?>
