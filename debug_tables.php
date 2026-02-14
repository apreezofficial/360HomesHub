<?php
require_once __DIR__ . '/config/env.php';
require_once __DIR__ . '/utils/db.php';

try {
    $pdo = Database::getInstance();
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo implode("\n", $tables);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
