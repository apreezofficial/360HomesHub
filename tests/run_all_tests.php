<?php

echo "Starting all tests...\n\n";

require_once __DIR__ . '/test_auth.php';
echo "\n";

require_once __DIR__ . '/test_admin.php';
echo "\n";

require_once __DIR__ . '/test_kyc.php';
echo "\n";

require_once __DIR__ . '/test_onboarding.php';
echo "\n";

echo "All tests completed.\n";

