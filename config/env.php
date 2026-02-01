<?php

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', '360homesub');
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

// Google OAuth 2.0 Configuration
define('GOOGLE_CLIENT_ID', 'YOUR_GOOGLE_CLIENT_ID');
define('GOOGLE_CLIENT_SECRET', 'YOUR_GOOGLE_CLIENT_SECRET');
define('GOOGLE_REDIRECT_URI', 'http://localhost/api/auth/google_auth.php');

// File Upload Configuration
define('UPLOAD_DIR', __DIR__ . '/../public/uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024);
define('ALLOWED_FILE_TYPES', ['jpg', 'jpeg', 'png']);

// OTP Configuration
define('OTP_EXPIRATION_MINUTES', 5);
define('OTP_LENGTH', 6);

// Payment Gateway Configuration
define('PAYSTACK_SECRET_KEY', 'sk_test_xxxxxxxxxxxxxxxxxxxxxxxxxxxxx');
define('FLUTTERWAVE_SECRET_KEY', 'FLWSECK_TEST-xxxxxxxxxxxxxxxxxxxxxxxxxxxxx-X');
define('PAYSTACK_CALLBACK_URL', 'http://localhost/360HomesHub/api/payments/verify.php?gateway=paystack');
define('FLUTTERWAVE_CALLBACK_URL', 'http://localhost/360HomesHub/api/payments/verify.php?gateway=flutterwave');
