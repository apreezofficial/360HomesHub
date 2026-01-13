<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/response.php';
require_once __DIR__ . '/email.php'; // New: Include our custom email sending function

use Twilio\Rest\Client as TwilioClient;

class OtpManager {
    private PDO $pdo;
    private TwilioClient $twilio;
    // Removed: private Resend $resend;

    public function __construct() {
        $this->pdo = Database::getInstance();

        // Initialize Twilio client
        $sid = TWILIO_ACCOUNT_SID;
        $token = TWILIO_AUTH_TOKEN;
        $this->twilio = new TwilioClient($sid, $token);

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
        try {
            // Updated: Use custom send_email function
            return send_email(
                $email,
                RESEND_FROM_EMAIL,
                'Your OTP Code',
                "Your One-Time Password (OTP) is: <strong>$code</strong>. It expires in " . OTP_EXPIRATION_MINUTES . " minutes."
            );
        } catch (Exception $e) {
            error_log("Email OTP Error: " . $e->getMessage()); // Updated error message
            return false;
        }
    }

    private function sendSmsOtp(string $phone, string $code): bool {
        try {
            $this->twilio->messages->create(
                $phone,
                [
                    'from' => TWILIO_PHONE_NUMBER,
                    'body' => "Your One-Time Password (OTP) is: $code. It expires in " . OTP_EXPIRATION_MINUTES . " minutes."
                ]
            );
            return true;
        } catch (Exception $e) {
            error_log("Twilio SMS OTP Error: " . $e->getMessage());
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
