<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'instructor') {
    header("Location: ../auth/login.php");
    exit;
}

include_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $message = trim($_POST['message']);
    $type = $_POST['type'];
    $instructor_id = $_SESSION['user_id'];

    if (empty($title) || empty($message)) {
        header("Location: communications.php?error=empty_fields");
        exit;
    }

    $database = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);

    $query = "INSERT INTO announcements (title, message, type, created_by) VALUES (:title, :message, :type, :created_by)";
    $stmt = $database->prepare($query);

    if ($stmt->execute([':title' => $title, ':message' => $message, ':type' => $type, ':created_by' => $instructor_id])) {
        header("Location: communications.php?msg=success");
    } else {
        header("Location: communications.php?error=db_error");
    }
} else {
    header("Location: communications.php");
}