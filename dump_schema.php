<?php
require_once __DIR__ . '/utils/db.php';
$pdo = Database::getInstance();
$stmt = $pdo->query('DESCRIBE users');
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
file_put_contents('users_schema.json', json_encode($columns, JSON_PRETTY_PRINT));
echo "Schema dumped to users_schema.json\n";
