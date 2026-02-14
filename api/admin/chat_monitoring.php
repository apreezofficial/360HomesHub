<?php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config/env.php';
require_once __DIR__ . '/../../utils/db.php';
require_once __DIR__ . '/../../utils/response.php';
require_once __DIR__ . '/../../utils/jwt.php';

// Authenticate admin
$userData = JWTManager::authenticate();
if ($userData['role'] !== 'admin') {
    send_error('Access denied. Admin only.', [], 403);
}

$pdo = Database::getInstance();

try {
    // This would normally query the messages table for red flags
    // (e.g., phone numbers, emails, external links)
    
    // For now, return a list of flagged conversations (Mocked logic)
    $flaggedChats = [
        [
            'id' => 123,
            'participants' => ['Sarah M.', 'Host John'],
            'reason' => 'Phone number detected',
            'severity' => 'Medium',
            'time' => date('Y-m-d H:i:s', strtotime('-1 hour')),
            'status' => 'pending_review'
        ],
        [
            'id' => 124,
            'participants' => ['Mike K.', 'Host Alex'],
            'reason' => 'External link shared',
            'severity' => 'Low',
            'time' => date('Y-m-d H:i:s', strtotime('-5 mins')),
            'status' => 'pending_review'
        ]
    ];

    send_success('Flagged chats retrieved successfully.', [
        'flagged_chats' => $flaggedChats
    ]);

} catch (Exception $e) {
    error_log("Chat monitoring error: " . $e->getMessage());
    send_error('Failed to retrieve flagged chats.', [], 500);
}
