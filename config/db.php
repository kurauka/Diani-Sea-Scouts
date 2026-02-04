<?php
// Use environment variables for production (Render), fallback to local defaults
$host = getenv('DB_HOST') ?: 'localhost';
$db_name = getenv('DB_NAME') ?: 'diani_scouts_exam';
$username = getenv('DB_USER') ?: 'diani_user';
$password = getenv('DB_PASSWORD') ?: 'password123';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // In production, don't leak full connection strings in error messages
    die("Database connection failed. Please check environment configuration.");
}
?>