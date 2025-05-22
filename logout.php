<?php
session_start();
session_unset();
session_destroy();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logged Out - NEXTGEN</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <meta http-equiv="refresh" content="2;url=login.php">
</head>
<body>
    <div class="container py-5">
        <div class="alert alert-success text-center">
            <h4 class="mb-3">You have been logged out.</h4>
            <p>Redirecting to <a href="login.php">login page</a>...</p>
        </div>
    </div>
</body>
</html> 