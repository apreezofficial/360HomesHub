<?php
require_once __DIR__ . '/config/env.php';
require_once __DIR__ . '/utils/db.php';

try {
    $pdo = Database::getInstance();
    $columns = $pdo->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_COLUMN);

    if (!in_array('profile_pic', $columns)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN profile_pic VARCHAR(255) DEFAULT NULL AFTER email");
        echo "Added profile_pic column to users table.\n";
    } else {
        echo "profile_pic column already exists.\n";
    }

} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>
