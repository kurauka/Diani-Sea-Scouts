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

        $status = $_POST['status'] ?? 'present';
        $valid_statuses = ['present', 'absent', 'late', 'excused'];
        if (!in_array($status, $valid_statuses))
            $status = 'present';

        // Check if already exists for today/event
        $checkSql = "SELECT id FROM attendance WHERE student_id = :sid AND attendance_date = CURRENT_DATE";
        if ($event_id) {
            $checkSql .= " AND event_id = :eid";
        } else {
            $checkSql .= " AND event_id IS NULL";
        }

        $cStmt = $database->prepare($checkSql);
        $params = [':sid' => $student_id];
        if ($event_id)
            $params[':eid'] = $event_id;
        $cStmt->execute($params);
        $existing_id = $cStmt->fetchColumn();

        if ($existing_id) {
            // Update existing
            $sql = "UPDATE attendance SET status = :status, instructor_id = :ins WHERE id = :id";
            $stmt = $database->prepare($sql);
            $stmt->execute([':status' => $status, ':ins' => $instructor_id, ':id' => $existing_id]);
        } else {
            // Insert new
            $sql = "INSERT INTO attendance (student_id, instructor_id, event_id, attendance_date, status) 
                    VALUES (:sid, :ins, :eid, CURRENT_DATE, :status)";
            $stmt = $database->prepare($sql);
            $stmt->execute([':sid' => $student_id, ':ins' => $instructor_id, ':eid' => $event_id, ':status' => $status]);
        }

        $eventName = "";
        if ($event_id) {
            $eStmt = $database->prepare("SELECT title FROM calendar_events WHERE id = ?");
            $eStmt->execute([$event_id]);
            $eventName = " for " . $eStmt->fetchColumn();
        }

        echo json_encode([
            'success' => true,
            'name' => $student_name . $eventName,
            'status' => $status,
            'time' => date('H:i A')
        ]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => 'Database Error: ' . $e->getMessage()]);
    }
}
?>