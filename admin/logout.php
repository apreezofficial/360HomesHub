<?php
session_start();
session_unset();
session_destroy();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Logging out...</title>
</head>
<body>
    <script>
        // Clear JWT token from localStorage
        localStorage.removeItem('jwt_token');
        // Redirect to login page
        window.location.href = 'login.php';
    </script>
</body>
</html>
