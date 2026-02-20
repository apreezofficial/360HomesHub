<?php

class FlutterwaveService {
    private string $secretKey = FLUTTERWAVE_SECRET_KEY;

    public function initializeTransaction(string $email, float $amount, string $tx_ref, array $metadata = []): ?array {
        $url = "https://api.flutterwave.com/v3/payments";
        $fields = [
            'tx_ref' => $tx_ref,
            'amount' => $amount,
            'currency' => "NGN",
            'payment_options' => "card,banktransfer,ussd",
            'redirect_url' => FLUTTERWAVE_CALLBACK_URL,
            'customer' => [
                'email' => $email
            ],
            'meta' => $metadata,
            'customizations' => [
                'title' => "360HomesHub Payment",
                'description' => "Property Booking Payment"
            ]
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer " . $this->secretKey,
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
        return ($result['status'] === 'success') ? $result['data'] : null;
    }

    public function verifyTransaction(string $transactionId): ?array {
        $url = "https://api.flutterwave.com/v3/transactions/" . rawurlencode($transactionId) . "/verify";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer " . $this->secretKey,
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
        return ($result['status'] === 'success') ? $result['data'] : null;
    }

    public function verifyWebhook(): bool {
        // Retrieve the signature sent by Flutterwave
        $signature = $_SERVER['HTTP_VERIF_HASH'] ?? '';

        // Retrieve the secret hash from config
        $secretHash = defined('FLUTTERWAVE_SECRET_HASH') ? FLUTTERWAVE_SECRET_HASH : '';

        if (!$signature || !$secretHash || $signature !== $secretHash) {
            return false;
        }

        return true;
    }
}
