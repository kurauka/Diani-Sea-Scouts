<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    exit('Unauthorized');
}

include_once '../config/db.php';
$database = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $message = $_POST['message'];
    $type = $_POST['type'];
    $target_role = $_POST['target_role'];
    $user_id = $_SESSION['user_id'];

    // Insert Announcement
    $sql = "INSERT INTO announcements (title, message, type, target_role, created_by) VALUES (:title, :message, :type, :target, :uid)";
    $stmt = $database->prepare($sql);
    $stmt->execute([':title' => $title, ':message' => $message, ':type' => $type, ':target' => $target_role, ':uid' => $user_id]);

    // Handle Calendar Event
    if (isset($_POST['add_to_calendar']) && !empty($_POST['event_date'])) {
        $event_date = $_POST['event_date'];
        $sqlCal = "INSERT INTO calendar_events (title, event_date, created_by) VALUES (:title, :date, :uid)";
        $stmtCal = $database->prepare($sqlCal);
        $stmtCal->execute([':title' => $title, ':date' => $event_date, ':uid' => $user_id]);
    }

    header("Location: communications.php?msg=success");
    exit;
}
?>