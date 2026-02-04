<?php
// Use environment variables for production (Render), fallback to local defaults
$db_host = getenv('DB_HOST') ?: 'localhost';
$db_port = getenv('DB_PORT') ?: '3306';
$db_name = getenv('DB_NAME') ?: 'diani_scouts_exam';
$db_user = getenv('DB_USER') ?: 'diani_user';
$db_pass = getenv('DB_PASSWORD') ?: 'password123';

try {
    $dsn = "mysql:host=$db_host;port=$db_port;dbname=$db_name;charset=utf8mb4";
    $pdo = new PDO($dsn, $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Determine if we are on Render
    $is_render = getenv('RENDER');

    if ($is_render) {
        // Obfuscate sensitive info but show the error message type
        $msg = $e->getMessage();
        // Hide password if it somehow leaks in the message (unlikely for PDO but safe)
        if ($db_pass)
            $msg = str_replace($db_pass, '********', $msg);

        die("Database Connection Error on Render: " . htmlspecialchars($msg) . "<br><br>" .
            "Check your Render Environment Variables:<br>" .
            "- DB_HOST: $db_host<br>" .
            "- DB_PORT: $db_port<br>" .
            "- DB_NAME: $db_name<br>" .
            "- DB_USER: $db_user");
    } else {
        die("Database connection failed: " . $e->getMessage());
    }
}
?>