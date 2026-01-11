<?php
// app/routes/api.php

header('Content-Type: application/json');

// Simple router
$method = $_SERVER['REQUEST_METHOD'];
$url = isset($_GET['url']) ? rtrim($_GET['url'], '/') : '';
$url = filter_var($url, FILTER_SANITIZE_URL);
$url = explode('/', $url);
$path = $url; // Keep the full path for more complex routing

// Define routes
$routes = [
    'GET' => [
        'api/test' => function() {
            sendJsonResponse(['message' => 'API GET request is working!']);
        },
        'api/users' => 'ApiController@getAllUsers', // Example of controller usage
    ],
    'POST' => [
        'api/test' => function() {
            $data = json_decode(file_get_contents('php://input'), true);
            sendJsonResponse(['message' => 'API POST request is working!', 'data' => $data]);
        },
        'api/auth/forgotpassword' => 'AuthController@forgotPassword',
        'api/auth/resetpassword' => 'AuthController@resetPassword'
    ]
];

// Match route
$route_path = implode('/', array_slice($path, 0, 2));
if (isset($routes[$method][$route_path])) {
    $handler = $routes[$method][$route_path];
    
    if (is_callable($handler)) {
        $handler();
    } else if (is_string($handler)) {
        list($controller, $methodName) = explode('@', $handler);
        $controllerFile = __DIR__ . '/../controllers/' . $controller . '.php';
        if (file_exists($controllerFile)) {
            require_once $controllerFile;
            $controllerInstance = new $controller();
            if (method_exists($controllerInstance, $methodName)) {
                $controllerInstance->$methodName();
            } else {
                sendJsonResponse(['error' => "Method {$methodName} not found in controller {$controller}"], 500);
            }
        } else {
            sendJsonResponse(['error' => "Controller {$controller} not found"], 500);
        }
    }
} else {
    // Handle 404
    sendJsonResponse(['error' => 'API endpoint not found'], 404);
}

// Helper function for JSON responses
function sendJsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data);
    exit;
}
