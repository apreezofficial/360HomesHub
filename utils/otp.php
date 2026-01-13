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
        $send_result = send_email(
            $email,
            RESEND_FROM_EMAIL,
            'Your OTP Code',
            "Your One-Time Password (OTP) is: <strong>$code</strong>. It expires in " . OTP_EXPIRATION_MINUTES . " minutes."
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
