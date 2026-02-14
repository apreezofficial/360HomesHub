<?php

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config/env.php';

echo "<h1>Environment Check</h1>";

echo "<h2>Payment Gateways</h2>";
echo "<ul>";
echo "<li><strong>PAYSTACK_SECRET_KEY:</strong> " . (defined('PAYSTACK_SECRET_KEY') && !empty(PAYSTACK_SECRET_KEY) ? "✅ Set" : "❌ Missing") . "</li>";
echo "<li><strong>FLUTTERWAVE_SECRET_KEY:</strong> " . (defined('FLUTTERWAVE_SECRET_KEY') && !empty(FLUTTERWAVE_SECRET_KEY) ? "✅ Set" : "❌ Missing") . "</li>";
echo "<li><strong>FLUTTERWAVE_SECRET_HASH:</strong> " . (defined('FLUTTERWAVE_SECRET_HASH') && !empty(FLUTTERWAVE_SECRET_HASH) ? "✅ Set (Required for Webhooks)" : "❌ Missing (Webhooks will fail)") . "</li>";
echo "</ul>";

echo "<h2>Email (Resend)</h2>";
echo "<ul>";
echo "<li><strong>RESEND_API_KEY:</strong> " . (defined('RESEND_API_KEY') && !empty(RESEND_API_KEY) ? "✅ Set" : "❌ Missing") . "</li>";
echo "<li><strong>RESEND_FROM_EMAIL:</strong> " . (defined('RESEND_FROM_EMAIL') && !empty(RESEND_FROM_EMAIL) ? "✅ Set" : "❌ Missing") . "</li>";
echo "</ul>";

echo "<h2>App URL</h2>";
echo "<ul>";
echo "<li><strong>APP_URL:</strong> " . (defined('APP_URL') ? "✅ Set (" . APP_URL . ")" : "❌ Not Set (Redirects might fail, using localhost default)") . "</li>";
echo "</ul>";

echo "<hr>";
echo "<p>If any keep keys are missing, please add them to <code>config/env.php</code>.</p>";
