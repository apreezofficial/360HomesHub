<?php

class PaystackService {
    private string $secretKey = PAYSTACK_SECRET_KEY;

    public function initializeTransaction(string $email, float $amount, array $metadata = []): ?array {
        $url = "https://api.paystack.co/transaction/initialize";
        $fields = [
            'email' => $email,
            'amount' => (int)($amount * 100), // Paystack expects amount in kobo/cents
            'metadata' => $metadata,
            'callback_url' => PAYSTACK_CALLBACK_URL
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer " . $this->secretKey,
            "Cache-Control: no-cache",
            "Content-Type: application/json"
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        $err = curl_error($ch);
        // curl_close($ch);

        if ($err) {
            return null;
        }

        $result = json_decode($response, true);
        return ($result['status'] ?? false) ? $result['data'] : null;
    }

    public function verifyTransaction(string $reference): ?array {
        $url = "https://api.paystack.co/transaction/verify/" . rawurlencode($reference);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer " . $this->secretKey,
            "Cache-Control: no-cache",
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        $err = curl_error($ch);
        // curl_close($ch);

        if ($err) {
            return null;
        }

        $result = json_decode($response, true);
        return ($result['status'] ?? false) ? $result['data'] : null;
    }

    public function verifyWebhook(): bool {
        // Retrieve the request's body
        $input = @file_get_contents("php://input");
        
        // Retrieve the signature sent by Paystack
        $signature = $_SERVER['HTTP_X_PAYSTACK_SIGNATURE'] ?? '';

        // Confirm the signature is valid
        if (!$signature || $signature !== hash_hmac('sha512', $input, $this->secretKey)) {
            return false;
        }

        return true;
    }
}
