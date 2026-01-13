<?php

require_once __DIR__ . '/../vendor/autoload.php';

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;

$client = new Client(['base_uri' => 'http://localhost/360%20homeshub/']);

echo "Running tests for Admin API...\n";

// Test for api/admin/login.php
try {
    $client->get('api/admin/login.php');
} catch (ClientException $e) {
    $response = $e->getResponse();
    $body = json_decode($response->getBody()->getContents(), true);
    assert($response->getStatusCode() === 405);
    assert($body['message'] === 'Invalid request method.');
    echo "Test Passed: [Admin Login] Invalid request method.\n";
}

try {
    $client->post('api/admin/login.php');
} catch (ClientException $e) {
    $response = $e->getResponse();
    $body = json_decode($response->getBody()->getContents(), true);
    assert($response->getStatusCode() === 400);
    assert($body['message'] === 'Email and password are required.');
    echo "Test Passed: [Admin Login] Missing credentials.\n";
}

// Test for api/admin/kyc_list.php
try {
    $client->post('api/admin/kyc_list.php');
} catch (ClientException $e) {
    $response = $e->getResponse();
    $body = json_decode($response->getBody()->getContents(), true);
    assert($response->getStatusCode() === 405);
    assert($body['message'] === 'Invalid request method.');
    echo "Test Passed: [KYC List] Invalid request method.\n";
}

// Test for api/admin/approve_kyc.php
try {
    $client->get('api/admin/approve_kyc.php');
} catch (ClientException $e) {
    $response = $e->getResponse();
    $body = json_decode($response->getBody()->getContents(), true);
    assert($response->getStatusCode() === 405);
    assert($body['message'] === 'Invalid request method.');
    echo "Test Passed: [Approve KYC] Invalid request method.\n";
}

try {
    $client->post('api/admin/approve_kyc.php');
} catch (ClientException $e) {
    $response = $e->getResponse();
    $body = json_decode($response->getBody()->getContents(), true);
    assert($response->getStatusCode() === 401);
    assert($body['message'] === 'Unauthorized');
    echo "Test Passed: [Approve KYC] Unauthorized.\n";
}

// Test for api/admin/reject_kyc.php
try {
    $client->get('api/admin/reject_kyc.php');
} catch (ClientException $e) {
    $response = $e->getResponse();
    $body = json_decode($response->getBody()->getContents(), true);
    assert($response->getStatusCode() === 405);
    assert($body['message'] === 'Invalid request method.');
    echo "Test Passed: [Reject KYC] Invalid request method.\n";
}

try {
    $client->post('api/admin/reject_kyc.php');
} catch (ClientException $e) {
    $response = $e->getResponse();
    $body = json_decode($response->getBody()->getContents(), true);
    assert($response->getStatusCode() === 401);
    assert($body['message'] === 'Unauthorized');
    echo "Test Passed: [Reject KYC] Unauthorized.\n";
}

echo "All Admin API tests passed!\n";

