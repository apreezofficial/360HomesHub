<?php

$url = 'http://localhost/360HomesHub/api/auth/login.php';
$data = ['email' => 'test@example.com', 'password' => 'test'];

$options = [
    'http' => [
        'header'  => "Content-type: application/json\r\n",
        'method'  => 'POST',
        'content' => json_encode($data),
        'ignore_errors' => true
    ]
];

$context  = stream_context_create($options);
$result = file_get_contents($url, false, $context);

if ($result === false) {
    echo "Connection error.\n";
} else {
    echo "Response:\n";
    echo $result . "\n";
    
    $json = json_decode($result);
    if (json_last_error() === JSON_ERROR_NONE) {
        echo "\nSUCCESS: Valid JSON returned.\n";
    } else {
        echo "\nFAILURE: Invalid JSON returned.\n";
        echo "Error: " . json_last_error_msg() . "\n";
    }
}
