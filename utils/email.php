<?php

function send_email($to, $from, $subject, $html) {
    $api_key = getenv('RESEND_API_KEY');
    if (!$api_key) {
        return 'Resend API Error: API key not found in environment variables.';
    }

    $url = 'https://api.resend.com/emails';
    $data = [
        'from' => $from,
        'to' => $to,
        'subject' => $subject,
        'html' => $html,
    ];

    $headers = [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $api_key,
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if (curl_errno($ch)) {
        $curl_error = curl_error($ch);
        curl_close($ch);
        return 'Resend API cURL Error: ' . $curl_error;
    }
    curl_close($ch);

    if ($http_code >= 200 && $http_code < 300) {
        return true;
    } else {
        $decoded_response = json_decode($response, true);
        if (isset($decoded_response['message'])) {
            return 'Resend API Error: ' . $decoded_response['message'];
        } else {
            return 'Resend API Error: ' . $response;
        }
    }
}
