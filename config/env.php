<?php

if (defined('ENV_LOADED')) {
    return;
}
define('ENV_LOADED', true);

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', '36homeshub');
define('DB_USER', 'root');
define('DB_PASS', '');

// JWT Configuration
define('JWT_SECRET', 'Jfyeiotwyuqndtdghsjyg2diwhj');
define('JWT_ALGORITHM', 'HS256');
define('JWT_EXPIRATION_TIME', 3600);

// Twilio Configuration (for SMS OTP)
define('TWILIO_ACCOUNT_SID', 'ACf872320fac4757edb484dcb2642979fc');
define('TWILIO_AUTH_TOKEN', '4be2c9b183e917683dcab067968eface');
define('TWILIO_PHONE_NUMBER', '+2349064779856');

// Resend Configuration (for Email OTP)
define('RESEND_API_KEY', 're_gM1NVuXy_2jzvEXNKsDkrUkefYmd5g5X7');
define('RESEND_FROM_EMAIL', 'ap@proforms.top');

// Determine Base URL dynamically
$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http");
$host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
$base_url = $protocol . "://" . $host;

// If on localhost, include the project subdirectory if applicable
if ($host === 'localhost' || $host === '127.0.0.1') {
    $base_url .= '/36HomesHub';
}
define('BASE_URL', $base_url);
define('APP_URL', $base_url); // Alias for consistency
define('ADMIN_EMAIL', 'apreezofficial@gmail.com'); // Admin email for notifications

// Google OAuth 2.0 Configuration
define('GOOGLE_CLIENT_ID', 'YOUR_GOOGLE_CLIENT_ID');
define('GOOGLE_CLIENT_SECRET', 'YOUR_GOOGLE_CLIENT_SECRET');
define('GOOGLE_REDIRECT_URI', BASE_URL . '/api/auth/google_auth.php');

// File Upload Configuration
define('UPLOAD_DIR', __DIR__ . '/../public/uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024);
define('ALLOWED_FILE_TYPES', ['jpg', 'jpeg', 'png']);

// OTP Configuration
define('OTP_EXPIRATION_MINUTES', 5);
define('OTP_LENGTH', 6);

// Payment Gateway Configuration
define('PAYSTACK_SECRET_KEY', 'sk_test_44d01a2928eb943b5560869061c3aba8fe9af0db');
define('PAYSTACK_PUBLIC_KEY', 'pk_test_ad01fac1c9522778a16e055964d6597adbdc93ec');
define('FLUTTERWAVE_SECRET_KEY', 'FLWSECK_TEST-xxxxxxxxxxxxxxxxxxxxxxxxxxxxx-X');
define('PAYSTACK_CALLBACK_URL', BASE_URL . '/api/payments/verify.php?gateway=paystack');
define('FLUTTERWAVE_CALLBACK_URL', BASE_URL . '/api/payments/verify.php?gateway=flutterwave');

// Enable Error Reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
