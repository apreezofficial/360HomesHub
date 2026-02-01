<?php

function send_email($to, $from = null, $subject = '', $html = '') {
    $api_key = defined('RESEND_API_KEY') ? RESEND_API_KEY : null;
    $from_email = $from ?: (defined('RESEND_FROM_EMAIL') ? RESEND_FROM_EMAIL : null);

    if (!$api_key) {
        $err = 'Resend API Error: API key constant (RESEND_API_KEY) not defined.';
        error_log($err);
        return $err;
    }

    if (!$from_email) {
        $err = 'Resend API Error: From email not provided or defined in env.php.';
        error_log($err);
        return $err;
    }

    $url = 'https://api.resend.com/emails';
    $data = [
        'from' => $from_email,
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
        $err = 'Resend API cURL Error: ' . $curl_error;
        error_log($err);
        return $err;
    }
    curl_close($ch);

    if ($http_code >= 200 && $http_code < 300) {
        return true;
    } else {
        $decoded_response = json_decode($response, true);
        $err_msg = isset($decoded_response['message']) ? $decoded_response['message'] : $response;
        $final_err = 'Resend API Error (HTTP ' . $http_code . '): ' . $err_msg;
        error_log($final_err);
        return $final_err;
    }
}
