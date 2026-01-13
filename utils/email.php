<?php

function send_email($to, $from, $subject, $html) {
    $api_key = getenv('RESEND_API_KEY');
    if (!$api_key) {
        // Handle error: API key not found
        return false;
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
    curl_close($ch);

    if ($http_code >= 200 && $http_code < 300) {
        return true;
    } else {
        // Handle error: log the response
        error_log('Resend API error: ' . $response);
        return false;
    }
}
