<?php

require_once __DIR__ . '/../vendor/autoload.php';

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;

$client = new Client(['base_uri' => 'http://localhost/360%20homeshub/']);

echo "Running tests for Onboarding API...\n";

// Test for api/onboarding/set_location.php
try {
    $client->get('api/onboarding/set_location.php');
} catch (ClientException $e) {
    $response = $e->getResponse();
    $body = json_decode($response->getBody()->getContents(), true);
    assert($response->getStatusCode() === 405);
    assert($body['message'] === 'Invalid request method');
    echo "Test Passed: [Set Location] Invalid request method.\n";
}

try {
    $client->post('api/onboarding/set_location.php');
} catch (ClientException $e) {
    $response = $e->getResponse();
    $body = json_decode($response->getBody()->getContents(), true);
    assert($response->getStatusCode() === 401);
    assert($body['message'] === 'Unauthorized');
    echo "Test Passed: [Set Location] Unauthorized.\n";
}

// Test for api/onboarding/set_profile.php
try {
    $client->get('api/onboarding/set_profile.php');
} catch (ClientException $e) {
    $response = $e->getResponse();
    $body = json_decode($response->getBody()->getContents(), true);
    assert($response->getStatusCode() === 405);
    assert($body['message'] === 'Invalid request method');
    echo "Test Passed: [Set Profile] Invalid request method.\n";
}

try {
    $client->post('api/onboarding/set_profile.php');
} catch (ClientException $e) {
    $response = $e->getResponse();
    $body = json_decode($response->getBody()->getContents(), true);
    assert($response->getStatusCode() === 401);
    assert($body['message'] === 'Unauthorized');
    echo "Test Passed: [Set Profile] Unauthorized.\n";
}

// Test for api/onboarding/set_role.php
try {
    $client->get('api/onboarding/set_role.php');
} catch (ClientException $e) {
    $response = $e->getResponse();
    $body = json_decode($response->getBody()->getContents(), true);
    assert($response->getStatusCode() === 405);
    assert($body['message'] === 'Invalid request method');
    echo "Test Passed: [Set Role] Invalid request method.\n";
}

try {
    $client->post('api/onboarding/set_role.php');
} catch (ClientException $e) {
    $response = $e->getResponse();
    $body = json_decode($response->getBody()->getContents(), true);
    assert($response->getStatusCode() === 401);
    assert($body['message'] === 'Unauthorized');
    echo "Test Passed: [Set Role] Unauthorized.\n";
}

// Test for api/onboarding/upload_avatar.php
try {
    $client->get('api/onboarding/upload_avatar.php');
} catch (ClientException $e) {
    $response = $e->getResponse();
    $body = json_decode($response->getBody()->getContents(), true);
    assert($response->getStatusCode() === 405);
    assert($body['message'] === 'Invalid request method');
    echo "Test Passed: [Upload Avatar] Invalid request method.\n";
}

try {
    $client->post('api/onboarding/upload_avatar.php');
} catch (ClientException $e) {
    $response = $e->getResponse();
    $body = json_decode($response->getBody()->getContents(), true);
    assert($response->getStatusCode() === 401);
    assert($body['message'] === 'Unauthorized');
    echo "Test Passed: [Upload Avatar] Unauthorized.\n";
}

echo "All Onboarding API tests passed!\n";

