<?php

require_once __DIR__ . '/../vendor/autoload.php';

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;

$client = new Client(['base_uri' => 'http://localhost/360%20homeshub/']);

echo "Running tests for Auth API...\n";

// Test for api/auth/login.php
try {
    $client->get('api/auth/login.php');
} catch (ClientException $e) {
    $response = $e->getResponse();
    $body = json_decode($response->getBody()->getContents(), true);
    assert($response->getStatusCode() === 405);
    assert($body['message'] === 'Invalid request method.');
    echo "Test Passed: [Login] Invalid request method.\n";
}

try {
    $client->post('api/auth/login.php');
} catch (ClientException $e) {
    $response = $e->getResponse();
    $body = json_decode($response->getBody()->getContents(), true);
    assert($response->getStatusCode() === 400);
    assert($body['message'] === 'Email or phone, and password are required.');
    echo "Test Passed: [Login] Missing credentials.\n";
}

try {
    $client->post('api/auth/login.php', [
        'json' => [
            'email' => 'test@example.com',
            'password' => 'wrongpassword'
        ]
    ]);
} catch (ClientException $e) {
    $response = $e->getResponse();
    $body = json_decode($response->getBody()->getContents(), true);
    assert($response->getStatusCode() === 401);
    assert($body['message'] === 'Invalid credentials.');
    echo "Test Passed: [Login] Invalid credentials.\n";
}


// Test for api/auth/register_email.php
try {
    $client->get('api/auth/register_email.php');
} catch (ClientException $e) {
    $response = $e->getResponse();
    $body = json_decode($response->getBody()->getContents(), true);
    assert($response->getStatusCode() === 405);
    assert($body['message'] === 'Invalid request method.');
    echo "Test Passed: [Register Email] Invalid request method.\n";
}

try {
    $client->post('api/auth/register_email.php');
} catch (ClientException $e) {
    $response = $e->getResponse();
    $body = json_decode($response->getBody()->getContents(), true);
    assert($response->getStatusCode() === 400);
    assert($body['message'] === 'Email and password are required.');
    echo "Test Passed: [Register Email] Missing credentials.\n";
}

try {
    $client->post('api/auth/register_email.php', [
        'json' => [
            'email' => 'test@example.com',
            'password' => 'short'
        ]
    ]);
} catch (ClientException $e) {
    $response = $e->getResponse();
    $body = json_decode($response->getBody()->getContents(), true);
    assert($response->getStatusCode() === 400);
    assert($body['message'] === 'Password must be at least 8 characters long.');
    echo "Test Passed: [Register Email] Password too short.\n";
}

// Test for api/auth/register_phone.php
try {
    $client->get('api/auth/register_phone.php');
} catch (ClientException $e) {
    $response = $e->getResponse();
    $body = json_decode($response->getBody()->getContents(), true);
    assert($response->getStatusCode() === 405);
    assert($body['message'] === 'Invalid request method.');
    echo "Test Passed: [Register Phone] Invalid request method.\n";
}

try {
    $client->post('api/auth/register_phone.php');
} catch (ClientException $e) {
    $response = $e->getResponse();
    $body = json_decode($response->getBody()->getContents(), true);
    assert($response->getStatusCode() === 400);
    assert($body['message'] === 'Phone number and password are required.');
    echo "Test Passed: [Register Phone] Missing credentials.\n";
}

// Test for api/auth/verify_otp.php
try {
    $client->get('api/auth/verify_otp.php');
} catch (ClientException $e) {
    $response = $e->getResponse();
    $body = json_decode($response->getBody()->getContents(), true);
    assert($response->getStatusCode() === 405);
    assert($body['message'] === 'Invalid request method.');
    echo "Test Passed: [Verify OTP] Invalid request method.\n";
}

try {
    $client->post('api/auth/verify_otp.php');
} catch (ClientException $e) {
    $response = $e->getResponse();
    $body = json_decode($response->getBody()->getContents(), true);
    assert($response->getStatusCode() === 400);
    assert($body['message'] === 'User ID and OTP are required.');
    echo "Test Passed: [Verify OTP] Missing user ID and OTP.\n";
}

echo "All Auth API tests passed!\n";

