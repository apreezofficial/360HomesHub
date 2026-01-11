<?php

// Show all errors for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../app/bootstrap.php';

// Basic routing
if (isset($_GET['url']) && strpos($_GET['url'], 'api') === 0) {
    // Load API routes
    $init = new ApiCore();
} else {
    // Load web routes
    $init = new Core();
}
