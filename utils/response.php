<?php

function send_json_response(int $status_code, bool $success, string $message, array $data = []): void {
    http_response_code($status_code);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit();
}

function send_success(string $message = 'Success', array $data = [], int $status_code = 200): void {
    send_json_response($status_code, true, $message, $data);
}

function send_error(string $message = 'Error', array $data = [], int $status_code = 400): void {
    send_json_response($status_code, false, $message, $data);
}
