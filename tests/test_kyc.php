<?php

require_once __DIR__ . '/../vendor/autoload.php';

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;

$client = new Client(['base_uri' => 'http://localhost/360%20homeshub/']);

echo "Running tests for KYC API...\n";

// Test for api/kyc/kyc_status.php
try {
    $client->post('api/kyc/kyc_status.php');
} catch (ClientException $e) {
    $response = $e->getResponse();
    $body = json_decode($response->getBody()->getContents(), true);
    assert($response->getStatusCode() === 405);
    assert($body['message'] === 'Invalid request method');
    echo "Test Passed: [KYC Status] Invalid request method.\n";
}

try {
    $client->get('api/kyc/kyc_status.php');
} catch (ClientException $e) {
    $response = $e->getResponse();
    $body = json_decode($response->getBody()->getContents(), true);
    assert($response->getStatusCode() === 401);
    assert($body['message'] === 'Unauthorized');
    echo "Test Passed: [KYC Status] Unauthorized.\n";
}

// Test for api/kyc/start_kyc.php
try {
    $client->get('api/kyc/start_kyc.php');
} catch (ClientException $e) {
    $response = $e->getResponse();
    $body = json_decode($response->getBody()->getContents(), true);
    assert($response->getStatusCode() === 405);
    assert($body['message'] === 'Invalid request method');
    echo "Test Passed: [Start KYC] Invalid request method.\n";
}

try {
    $client->post('api/kyc/start_kyc.php');
} catch (ClientException $e) {
    $response = $e->getResponse();
    $body = json_decode($response->getBody()->getContents(), true);
    assert($response->getStatusCode() === 401);
    assert($body['message'] === 'Unauthorized');
    echo "Test Passed: [Start KYC] Unauthorized.\n";
}

// Test for api/kyc/upload_documents.php
try {
    $client->get('api/kyc/upload_documents.php');
} catch (ClientException $e) {
    $response = $e->getResponse();
    $body = json_decode($response->getBody()->getContents(), true);
    assert($response->getStatusCode() === 405);
    assert($body['message'] === 'Invalid request method.');
    echo "Test Passed: [Upload Documents] Invalid request method.\n";
}

try {
    $client->post('api/kyc/upload_documents.php');
} catch (ClientException $e) {
    $response = $e->getResponse();
    $body = json_decode($response->getBody()->getContents(), true);
    assert($response->getStatusCode() === 401);
    assert($body['message'] === 'Unauthorized');
    echo "Test Passed: [Upload Documents] Unauthorized.\n";
}

// Test for api/kyc/upload_selfie.php
try {
    $client->get('api/kyc/upload_selfie.php');
} catch (ClientException $e) {
    $response = $e->getResponse();
    $body = json_decode($response->getBody()->getContents(), true);
    assert($response->getStatusCode() === 405);
    assert($body['message'] === 'Invalid request method.');
    echo "Test Passed: [Upload Selfie] Invalid request method.\n";
}

try {
    $client->post('api/kyc/upload_selfie.php');
} catch (ClientException $e) {
    $response = $e->getResponse();
    $body = json_decode($response->getBody()->getContents(), true);
    assert($response->getStatusCode() === 401);
    assert($body['message'] === 'Unauthorized');
    echo "Test Passed: [Upload Selfie] Unauthorized.\n";
}

echo "All KYC API tests passed!\n";

