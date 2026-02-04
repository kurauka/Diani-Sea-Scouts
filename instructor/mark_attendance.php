<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'instructor') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

include_once '../config/db.php';
$database = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = $_POST['student_id'] ?? null;
    $instructor_id = $_SESSION['user_id'];

    if (!$student_id) {
        echo json_encode(['success' => false, 'error' => 'Invalid ID']);
        exit;
    }

    // Check if student exists
    $stmt = $database->prepare("SELECT name FROM users WHERE id = ? AND role = 'student'");
    $stmt->execute([$student_id]);
    $student_name = $stmt->fetchColumn();

    if (!$student_name) {
        echo json_encode(['success' => false, 'error' => 'Student not found']);
        exit;
    }

    try {
        $event_id = $_POST['event_id'] ?? null;
        if (empty($event_id))
            $event_id = null;

        // Insert attendance
        $sql = "INSERT INTO attendance (student_id, instructor_id, event_id, attendance_date) VALUES (:sid, :ins, :eid, CURRENT_DATE)";
        $stmt = $database->prepare($sql);
        $stmt->execute([':sid' => $student_id, ':ins' => $instructor_id, ':eid' => $event_id]);

        $eventName = "";
        if ($event_id) {
            $eStmt = $database->prepare("SELECT title FROM calendar_events WHERE id = ?");
            $eStmt->execute([$event_id]);
            $eventName = " for " . $eStmt->fetchColumn();
        }

        echo json_encode([
            'success' => true,
            'name' => $student_name . $eventName,
            'time' => date('H:i A')
        ]);
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) { // Unique constraint violation
            echo json_encode(['success' => false, 'error' => 'Already Marked' . ($event_id ? ' for this event' : ' Today')]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Database Error: ' . $e->getMessage()]);
        }
    }
}
?>