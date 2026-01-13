<?php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../utils/db.php';
require_once __DIR__ . '/../../utils/response.php';
require_once __DIR__ . '/../../utils/jwt.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    send_error('Invalid request method.', [], 405);
}

// Authenticate user via JWT and check for admin role
$userData = JWTManager::authenticate();
if (!isset($userData['is_admin']) || !$userData['is_admin']) {
    send_error('Access denied. Admin privileges required.', [], 403);
}

$pdo = Database::getInstance();

$statusFilter = $_GET['status'] ?? null;
$allowedStatuses = ['pending', 'approved', 'rejected'];

$sql = "SELECT kyc.id, kyc.user_id, users.email, users.phone, users.first_name, users.last_name, users.avatar, kyc.country, kyc.identity_type, kyc.id_front, kyc.id_back, kyc.selfie, kyc.status, kyc.admin_note, kyc.submitted_at 
        FROM kyc 
        JOIN users ON kyc.user_id = users.id";
$params = [];

if ($statusFilter && in_array($statusFilter, $allowedStatuses)) {
    $sql .= " WHERE kyc.status = ?";
    $params[] = $statusFilter;
}

$sql .= " ORDER BY kyc.submitted_at DESC";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $kycApplications = $stmt->fetchAll();

    // Process results to include full URLs and remove sensitive paths
    foreach ($kycApplications as &$app) {
        $app['id_front_url'] = '/public/uploads/' . basename($app['id_front']);
        $app['id_back_url'] = '/public/uploads/' . basename($app['id_back']);
        $app['selfie_url'] = '/public/uploads/' . basename($app['selfie']);
        unset($app['id_front']);
        unset($app['id_back']);
        unset($app['selfie']);
    }

    send_success('KYC applications retrieved successfully.', ['applications' => $kycApplications]);

} catch (Exception $e) {
    error_log("Admin KYC list error: " . $e->getMessage());
    send_error('Failed to retrieve KYC applications.', [], 500);
}
