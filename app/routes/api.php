<?php
// app/routes/api.php

// This is where you will define your API routes.
// You can use a simple routing mechanism or a more advanced one like FastRoute.

// For now, let's just have a simple check.
if (isset($_GET['url'])) {
    $url = explode('/', filter_var(rtrim($_GET['url'], '/'), FILTER_SANITIZE_URL));
    
    // Example route: /api/users
    if ($url[0] == 'api' && $url[1] == 'users') {
        // This is where you would call your UserController to get all users.
        // For now, just return a simple JSON response.
        header('Content-Type: application/json');
        echo json_encode(['message' => 'API route for users works!']);
        exit;
    }
}
