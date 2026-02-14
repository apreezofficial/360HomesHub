<?php

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/utils/email.php';
require_once __DIR__ . '/config/env.php';

// Turn on error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Email System Test</h1>";

// 1. Check Configuration
echo "<h2>1. Configuration Check</h2>";
$apiKey = defined('RESEND_API_KEY') ? RESEND_API_KEY : null;
$fromEmail = defined('RESEND_FROM_EMAIL') ? RESEND_FROM_EMAIL : null;

if (!$apiKey) {
    echo "<p style='color: red;'>❌ RESEND_API_KEY is missing in config/env.php</p>";
} else {
    echo "<p style='color: green;'>✅ RESEND_API_KEY is set (" . substr($apiKey, 0, 5) . "...)</p>";
}

if (!$fromEmail) {
    echo "<p style='color: red;'>❌ RESEND_FROM_EMAIL is missing in config/env.php</p>";
} else {
    echo "<p style='color: green;'>✅ RESEND_FROM_EMAIL is set ($fromEmail)</p>";
}

if (!$apiKey || !$fromEmail) {
    echo "<h3>⚠️ Cannot proceed with test. Please update config/env.php</h3>";
    exit;
}

// 2. Attempt to Send Email
echo "<h2>2. Sending Test Email...</h2>";

// Use a hardcoded email for testing if you want, or try to pick one
$toEmail = 'apreezofficial@gmail.com'; // Using the email I saw in your earlier logs
$subject = "Test Email from 360HomesHub - " . date("Y-m-d H:i:s");
$html = "
    <h3>It Works! 🎉</h3>
    <p>This is a test email from your local 360HomesHub setup.</p>
    <p>If you are reading this, your email configuration is correct.</p>
    <p><strong>Time:</strong> " . date("Y-m-d H:i:s") . "</p>
";

echo "<p>Sending to: <strong>$toEmail</strong>...</p>";

$result = send_email($toEmail, null, $subject, $html);

if ($result === true) {
    echo "<h3 style='color: green;'>✅ Email Sent Successfully!</h3>";
    echo "<p>Check your inbox (and spam folder) for <strong>$toEmail</strong>.</p>";
} else {
    echo "<h3 style='color: red;'>❌ Email Sending Failed</h3>";
    echo "<p><strong>Error:</strong> " . htmlspecialchars(json_encode($result)) . "</p>";
    echo "<p>Please check your Resend API Key and verify your domain in Resend dashboard.</p>";
}

// 3. Check Activity Logs for Recent Email Events
echo "<h2>3. Recent Email Activity Logs</h2>";
try {
    require_once __DIR__ . '/utils/db.php';
    $pdo = get_db_connection();
    $stmt = $pdo->query("SELECT * FROM activity_logs WHERE action_type LIKE 'email_%' ORDER BY created_at DESC LIMIT 5");
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($logs) > 0) {
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background: #f0f0f0;'><th>Time</th><th>Action</th><th>Description</th><th>Metadata</th></tr>";
        foreach ($logs as $log) {
            $color = strpos($log['action_type'], 'failed') !== false ? '#fee2e2' : '#dcfce7';
            echo "<tr style='background: {$color};'>";
            echo "<td>" . htmlspecialchars($log['created_at']) . "</td>";
            echo "<td>" . htmlspecialchars($log['action_type']) . "</td>";
            echo "<td>" . htmlspecialchars($log['action_description']) . "</td>";
            echo "<td><pre style='margin: 0; font-size: 11px;'>" . htmlspecialchars($log['metadata']) . "</pre></td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No email activity logs found yet.</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: orange;'>Could not fetch logs: " . htmlspecialchars($e->getMessage()) . "</p>";
}

