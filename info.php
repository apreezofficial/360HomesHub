<?php
$curl = curl_init();

curl_setopt_array($curl, [
    CURLOPT_URL => "https://api.paystack.co/transaction/initialize",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CUSTOMREQUEST => "POST",
    CURLOPT_POSTFIELDS => json_encode([
        'email' => 'customer@example.com',
        'amount' => 50000, // in kobo (50000 = ₦500)
        'callback_url' => 'http://localhost/callback.php'
    ]),
    CURLOPT_HTTPHEADER => [
        "Authorization: Bearer YOUR_SECRET_KEY", // Replace with your Paystack secret key
        "Content-Type: application/json",
        "Cache-Control: no-cache"
    ],
]);

$response = curl_exec($curl);
$err = curl_error($curl);

if ($err) {
    echo "cURL Error: " . $err;
} else {
    $result = json_decode($response, true);
    if ($result['status']) {
        // Redirect to Paystack payment page
        header('Location: ' . $result['data']['authorization_url']);
    } else {
        echo "Error: " . $result['message'];
    }
}

curl_close($curl);
?>   