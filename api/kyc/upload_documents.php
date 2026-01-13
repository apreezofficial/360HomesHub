<?php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../utils/db.php';
require_once __DIR__ . '/../utils/response.php';
require_once __DIR__ . '/../utils/jwt.php';
require_once __DIR__ . '/../utils/upload.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_error('Invalid request method.', [], 405);
}

// Authenticate user via JWT
$userData = JWTManager::authenticate();
$userId = $userData['user_id'] ?? null;

if (!$userId) {
    send_error('Authentication failed.', [], 401);
}

$pdo = Database::getInstance();

// Check user's current onboarding step
$stmt = $pdo->prepare("SELECT onboarding_step FROM users WHERE id = ?");
$stmt->execute([$userId]);
$userOnboarding = $stmt->fetchColumn();

if ($userOnboarding !== 'kyc') {
    send_error('You are not in the KYC onboarding step. Current step: ' . $userOnboarding, ['onboarding_step' => $userOnboarding], 403);
}

// Expect country and identity_type from previous step (or re-sent with this request)
// For simplicity, let's assume it's sent again or stored temporarily by frontend
$country = $_POST['country'] ?? null;
$identityType = $_POST['identity_type'] ?? null;

if (empty($country) || empty($identityType)) {
    send_error('Country and identity_type are required fields that should be sent with the documents.', [], 400);
}

$allowedIdentityTypes = ['passport', 'national_id', 'drivers_license'];
if (!in_array(strtolower($identityType), $allowedIdentityTypes)) {
    send_error('Invalid identity type. Must be one of: ' . implode(', ', $allowedIdentityTypes), [], 400);
}

try {
    $idFrontPath = UploadManager::uploadFile('id_front');
    $idBackPath = UploadManager::uploadFile('id_back');

    if (!$idFrontPath || !$idBackPath) {
        // UploadManager::uploadFile already sends error, so just exit if it fails
        exit();
    }

    // Check if there's an existing pending or rejected KYC application for this user
    $stmt = $pdo->prepare("SELECT id FROM kyc WHERE user_id = ? AND status IN ('pending', 'rejected')");
    $stmt->execute([$userId]);
    $existingKycId = $stmt->fetchColumn();

    if ($existingKycId) {
        // Update existing KYC record
        $stmt = $pdo->prepare("UPDATE kyc SET country = ?, identity_type = ?, id_front = ?, id_back = ?, selfie = NULL, status = 'pending', submitted_at = CURRENT_TIMESTAMP, admin_note = NULL WHERE id = ?");
        $stmt->execute([$country, $identityType, $idFrontPath, $idBackPath, $existingKycId]);
    } else {
        // Create a new KYC record (selfie will be added in next step)
        $stmt = $pdo->prepare("INSERT INTO kyc (user_id, country, identity_type, id_front, id_back, status) VALUES (?, ?, ?, ?, ?, 'pending')");
        $stmt->execute([$userId, $country, $identityType, $idFrontPath, $idBackPath]);
    }


    send_success('Identity documents uploaded successfully. Please proceed to upload your selfie.', [
        'id_front_url' => $idFrontPath,
        'id_back_url' => $idBackPath
    ]);

} catch (Exception $e) {
    error_log("Upload documents error for user ID $userId: " . $e->getMessage());
    send_error('Failed to upload identity documents.', [], 500);
}
