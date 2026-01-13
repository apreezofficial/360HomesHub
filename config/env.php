<?php

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', '360homesub'); // Change to your database name
define('DB_USER', 'root'); // Change to your database user
define('DB_PASS', ''); // Change to your database password

// JWT Configuration
define('JWT_SECRET', 'your_super_secret_jwt_key_here'); // !!! IMPORTANT: Change this to a strong, unique secret key !!!
define('JWT_ALGORITHM', 'HS256');
define('JWT_EXPIRATION_TIME', 3600); // 1 hour

// Twilio Configuration (for SMS OTP)
define('TWILIO_ACCOUNT_SID', 'ACxxxxxxxxxxxxxxxxxxxxxxxxxxxxx'); // Your Twilio Account SID
define('TWILIO_AUTH_TOKEN', 'your_twilio_auth_token'); // Your Twilio Auth Token
define('TWILIO_PHONE_NUMBER', '+1234567890'); // Your Twilio Phone Number (e.g., +15017122661)

// Resend Configuration (for Email OTP)
define('RESEND_API_KEY', 're_xxxxxxxxxxxxxxxxxxxxxxxxxxxxx'); // Your Resend API Key
define('RESEND_FROM_EMAIL', 'onboarding@yourdomain.com'); // Your verified Resend email sender

// Google OAuth 2.0 Configuration
define('GOOGLE_CLIENT_ID', 'YOUR_GOOGLE_CLIENT_ID'); // Your Google Client ID
define('GOOGLE_CLIENT_SECRET', 'YOUR_GOOGLE_CLIENT_SECRET'); // Your Google Client Secret
define('GOOGLE_REDIRECT_URI', 'http://localhost/api/auth/google_auth.php'); // Your redirect URI

// File Upload Configuration
define('UPLOAD_DIR', __DIR__ . '/../public/uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5 MB
define('ALLOWED_FILE_TYPES', ['jpg', 'jpeg', 'png']);

// OTP Configuration
define('OTP_EXPIRATION_MINUTES', 5);
define('OTP_LENGTH', 6);
