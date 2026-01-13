<?php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../utils/db.php';
require_once __DIR__ . '/../utils/response.php';
require_once __DIR__ . '/../utils/jwt.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_error('Invalid request method.', [], 405);
}

$input = json_decode(file_get_contents('php://input'), true);

$idToken = $input['id_token'] ?? '';

if (empty($idToken)) {
    send_error('Google ID token is required.', [], 400);
}

$client = new Google_Client(['client_id' => GOOGLE_CLIENT_ID]);
$payload = null;

try {
    $payload = $client->verifyIdToken($idToken);
} catch (Exception $e) {
    send_error('Invalid Google ID token: ' . $e->getMessage(), [], 401);
}

if (!$payload) {
    send_error('Invalid Google ID token.', [], 401);
}

$googleId = $payload['sub'];
$email = $payload['email'];
$firstName = $payload['given_name'] ?? null;
$lastName = $payload['family_name'] ?? null;
$avatar = $payload['picture'] ?? null; // Google profile picture

$pdo = Database::getInstance();
$user = null;

try {
    $pdo->beginTransaction();

    // Check if user exists by google_id
    $stmt = $pdo->prepare("SELECT id, email, phone, password_hash, auth_provider, onboarding_step, is_verified, role FROM users WHERE google_id = ?");
    $stmt->execute([$googleId]);
    $user = $stmt->fetch();

    if (!$user) {
        // Check if user exists by email (to link Google to existing email account if applicable)
        $stmt = $pdo->prepare("SELECT id, email, phone, password_hash, auth_provider, onboarding_step, is_verified, role FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            // Link existing email user to Google account
            $stmt = $pdo->prepare("UPDATE users SET google_id = ?, auth_provider = 'google' WHERE id = ?");
            $stmt->execute([$googleId, $user['id']]);
            // Re-fetch user to get updated info
            $stmt = $pdo->prepare("SELECT id, email, phone, password_hash, auth_provider, onboarding_step, is_verified, role FROM users WHERE id = ?");
            $stmt->execute([$user['id']]);
            $user = $stmt->fetch();
        } else {
            // New user registration via Google
            // All users must have passwords. Set a random password hash for now, user will be prompted to set a real one.
            $temporaryPasswordHash = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT); // Random 32-char hex string as temp password
            $onboardingStep = 'password'; // Prompt user to set a real password

            $stmt = $pdo->prepare("INSERT INTO users (email, google_id, password_hash, first_name, last_name, avatar, auth_provider, onboarding_step) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$email, $googleId, $temporaryPasswordHash, $firstName, $lastName, $avatar, 'google', $onboardingStep]);
            $userId = $pdo->lastInsertId();

            // Fetch newly created user data to include in JWT
            $stmt = $pdo->prepare("SELECT id, email, phone, password_hash, auth_provider, onboarding_step, is_verified, role FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
        }
    }

    $pdo->commit();

    // Generate JWT token
    $jwtData = [
        'user_id' => $user['id'],
        'auth_provider' => $user['auth_provider'],
        'email' => $user['email'],
        'phone' => $user['phone'],
        'onboarding_step' => $user['onboarding_step'],
        'is_verified' => (bool)$user['is_verified'],
        'role' => $user['role']
    ];
    $token = JWTManager::generateToken($jwtData);

    send_success('Google authentication successful.', [
        'token' => $token,
        'onboarding_step' => $user['onboarding_step'],
        'is_verified' => (bool)$user['is_verified'],
        'role' => $user['role']
    ]);

} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Google auth error: " . $e->getMessage());
    send_error('Google authentication failed.', [], 500);
}
