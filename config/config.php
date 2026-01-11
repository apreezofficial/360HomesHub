<?php

// DB Params
define('DB_HOST', 'localhost');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
define('DB_NAME', 'your_dbname');

// App Root
define('APPROOT', dirname(dirname(__FILE__)));
// URL Root
define('URLROOT', 'http://localhost/360-homeshub');
// Site Name
define('SITENAME', '360-homeshub');

// JWT Secret Key
define('JWT_SECRET', 'your_jwt_secret');

// Google API Credentials
define('GOOGLE_CLIENT_ID', 'your_google_client_id');
define('GOOGLE_CLIENT_SECRET', 'your_google_client_secret');
define('GOOGLE_REDIRECT_URI', 'http://localhost/360-homeshub/api/auth/googleOauth');

// Resend API Key
define('RESEND_API_KEY', 'your_resend_api_key');

// Twilio Credentials
define('TWILIO_SID', 'your_twilio_sid');
define('TWILIO_TOKEN', 'your_twilio_token');
define('TWILIO_FROM', 'your_twilio_from_number');

// OTP Rate Limiting
define('OTP_RATE_LIMIT_COUNT', 3);
define('OTP_RATE_LIMIT_MINUTES', 10);
