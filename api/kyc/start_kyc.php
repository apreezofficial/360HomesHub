<?php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../utils/db.php';
require_once __DIR__ . '/../utils/response.php';
require_once __DIR__ . '/../utils/jwt.php';

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
$stmt = $pdo->prepare("SELECT onboarding_step, country FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user || $user['onboarding_step'] !== 'kyc') {
    send_error('You are not in the KYC onboarding step or user not found. Current step: ' . ($user['onboarding_step'] ?? 'N/A'), ['onboarding_step' => $user['onboarding_step'] ?? 'N/A'], 403);
}

$input = json_decode(file_get_contents('php://input'), true);

$country = trim($input['country'] ?? '');
$identityType = strtolower(trim($input['identity_type'] ?? ''));

if (empty($country) || empty($identityType)) {
    send_error('Country and identity_type are required.', [], 400);
}

$allowedIdentityTypes = ['passport', 'national_id', 'drivers_license'];
if (!in_array($identityType, $allowedIdentityTypes)) {
    send_error('Invalid identity type. Must be one of: ' . implode(', ', $allowedIdentityTypes), [], 400);
}

// Check if there's an existing pending or approved KYC
$stmt = $pdo->prepare("SELECT status FROM kyc WHERE user_id = ? AND (status = 'pending' OR status = 'approved')");
$stmt->execute([$userId]);
if ($stmt->fetch()) {
    send_error('You already have a pending or approved KYC application.', [], 409);
}

// Store KYC initial data temporarily in session or user table (for this vanilla setup, we'll assume the frontend holds this and sends with uploads)
// Or, we could update the user table with kyc_country and kyc_identity_type to be used in next steps.
// For now, just confirm and proceed to next step.

send_success('KYC initiated. Please proceed to upload your identity documents.', [
    'country' => $country,
    'identity_type' => $identityType
]);

