<?php
// Use environment variables for production (Render), fallback to local defaults
$host = getenv('DB_HOST') ?: 'localhost';
$db_name = getenv('DB_NAME') ?: 'diani_scouts_exam';
$username = getenv('DB_USER') ?: 'diani_user';
$password = getenv('DB_PASSWORD') ?: 'password123';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Determine if we are on Render
    $is_render = getenv('RENDER');

    if ($is_render) {
        // Obfuscate sensitive info but show the error message type
        $msg = $e->getMessage();
        // Hide password if it somehow leaks in the message (unlikely for PDO but safe)
        if ($password)
            $msg = str_replace($password, '********', $msg);

        die("Database Connection Error on Render: " . htmlspecialchars($msg) . "<br><br>" .
            "Check your Render Environment Variables:<br>" .
            "- DB_HOST: $host<br>" .
            "- DB_NAME: $db_name<br>" .
            "- DB_USER: $username");
    } else {
        die("Database connection failed: " . $e->getMessage());
    }
}
?>