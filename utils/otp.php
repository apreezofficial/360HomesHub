<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/response.php';
require_once __DIR__ . '/email.php'; // New: Include our custom email sending function

class OtpManager {
    private PDO $pdo;
    // Removed: private Resend $resend;

    public function __construct() {
        $this->pdo = Database::getInstance();

        // Removed: Initialize Resend client
        // $this->resend = Resend::client(RESEND_API_KEY);
    }

    private function generateOtpCode(): string {
        return str_pad(random_int(0, 999999), OTP_LENGTH, '0', STR_PAD_LEFT);
    }

    public function sendOtp(int $userId, ?string $email = null, ?string $phone = null): bool {
        $code = $this->generateOtpCode();
        $expiresAt = date('Y-m-d H:i:s', time() + (OTP_EXPIRATION_MINUTES * 60));

        // Store OTP in database
        $stmt = $this->pdo->prepare("INSERT INTO otps (user_id, code, expires_at) VALUES (?, ?, ?)");
        $stmt->execute([$userId, $code, $expiresAt]);

        if ($email) {
            return $this->sendEmailOtp($email, $code);
        } elseif ($phone) {
            return $this->sendSmsOtp($phone, $code);
        }
        return false;
    }

    private function sendEmailOtp(string $email, string $code): bool {
        // Updated: Use custom send_email function and handle its return
        
        $expiration = defined('OTP_EXPIRATION_MINUTES') ? OTP_EXPIRATION_MINUTES : 5;
        
        $html = "
<!DOCTYPE html>
<html>
<head>
<style>
/* Clean styles */
body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; background-color: #f9f9f9; padding: 20px; margin: 0; }
.container { max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.05); border: 1px solid #eee; }
.header { background: #ffffff; padding: 30px; text-align: center; border-bottom: 3px solid #005a92; }
.header h1 { color: #005a92; margin: 0; font-size: 22px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; }
.content { padding: 40px; text-align: center; }
.otp-label { font-size: 14px; color: #888; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 10px; font-weight: 600; }
.otp-code { font-size: 36px; font-weight: 800; letter-spacing: 8px; color: #005a92; margin: 10px 0 30px; background: #f8fbff; display: inline-block; padding: 20px 40px; border-radius: 12px; border: 2px dashed #dbeafe; }
.footer { padding: 25px; text-align: center; color: #9ca3af; font-size: 12px; background: #f9fafb; border-top: 1px solid #f3f4f6; }
.warning { color: #ef4444; font-size: 13px; margin-top: 20px; }
</style>
</head>
<body>
<div class='container'>
    <div class='header'>
        <h1>36HomesHub</h1>
    </div>
    <div class='content'>
        <p style='color: #4b5563; font-size: 16px; margin-bottom: 30px; font-weight: 500;'>Verify your identity to access the Admin Portal</p>
        
        <div class='otp-label'>Verification Code</div>
        <div class='otp-code'>$code</div>
        
        <p style='color: #6b7280; font-size: 14px; line-height: 1.6;'>This code is valid for <strong>$expiration minutes</strong>.<br>Do not share this code with anyone.</p>
        
        <div class='warning'>If you didn't request this, please contact support immediately.</div>
    </div>
    <div class='footer'>
        &copy; " . date('Y') . " 36HomesHub Inc. All rights reserved.<br>
        123 Innovation Drive, Tech City via Lagos.
    </div>
</div>
</body>
</html>
";

        $send_result = send_email(
            $email,
            defined('RESEND_FROM_EMAIL') ? RESEND_FROM_EMAIL : null,
            'Your 36HomesHub Verification Code',
            $html
        );

        if ($send_result === true) {
            return true;
        } else {
            error_log("Email OTP Error: " . $send_result); // Log the detailed error message
            return false;
        }
    }

    private function sendSmsOtp(string $phone, string $code): bool {
        $sid = TWILIO_ACCOUNT_SID;
        $token = TWILIO_AUTH_TOKEN;
        $from_number = TWILIO_PHONE_NUMBER;
        $body = urlencode("Your One-Time Password (OTP) is: $code. It expires in " . OTP_EXPIRATION_MINUTES . " minutes.");
        $to_number = urlencode($phone);

        $ch = curl_init();
        $url = "https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json";
        $post = "To={$to_number}&From={$from_number}&Body={$body}";

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        curl_setopt($ch, CURLOPT_USERPWD, "{$sid}:{$token}");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Consider setting to true in production with proper CA certs
        curl_setopt($ch, CURLOPT_HEADER, false);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            error_log("cURL Error: " . $curl_error);
            return false;
        }

        $response_data = json_decode($response, true);

        if ($http_code >= 200 && $http_code < 300) {
            // Check for Twilio API specific errors if any
            if (isset($response_data['status']) && $response_data['status'] === 'failed') {
                error_log("Twilio API Error: " . ($response_data['message'] ?? 'Unknown error') . " - Code: " . ($response_data['code'] ?? 'N/A'));
                return false;
            }
            return true;
        } else {
            error_log("Twilio SMS OTP HTTP Error: {$http_code} - Response: {$response}");
            return false;
        }
    }

    public function verifyOtp(int $userId, string $code): bool {
        $stmt = $this->pdo->prepare("SELECT * FROM otps WHERE user_id = ? AND code = ? AND used = 0 AND expires_at > NOW() ORDER BY expires_at DESC LIMIT 1");
        $stmt->execute([$userId, $code]);
        $otp = $stmt->fetch();

        if ($otp) {
            // Mark OTP as used
            $stmt = $this->pdo->prepare("UPDATE otps SET used = 1 WHERE id = ?");
            $stmt->execute([$otp['id']]);
            return true;
        }
        return false;
    }
}
