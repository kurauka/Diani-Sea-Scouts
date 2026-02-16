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
    $instructor_id = $_SESSION['user_id'];
    $event_id = $_POST['event_id'] ?? null;
    if (empty($event_id))
        $event_id = null;

    $date = $_POST['date'] ?? date('Y-m-d');

    try {
        // 1. Get instructor's troop
        $stmt = $database->prepare("SELECT troop_id FROM users WHERE id = ?");
        $stmt->execute([$instructor_id]);
        $troop_id = $stmt->fetchColumn();

        // 2. Find all students who DON'T have a record for date/event
        $findQuery = "SELECT id FROM users 
                      WHERE role = 'student' 
                      AND id NOT IN (
                          SELECT student_id FROM attendance 
                          WHERE attendance_date = :att_date
                          AND (event_id = :eid OR (:eid IS NULL AND event_id IS NULL))
                      )";

        $findStmt = $database->prepare($findQuery);
        $findStmt->execute([':eid' => $event_id, ':att_date' => $date]);
        $missingStudents = $findStmt->fetchAll(PDO::FETCH_COLUMN);

        $marked = 0;
        if (!empty($missingStudents)) {
            $insertQuery = "INSERT INTO attendance (student_id, instructor_id, event_id, attendance_date, status) 
                            VALUES (:sid, :ins, :eid, :att_date, 'absent')";
            $insertStmt = $database->prepare($insertQuery);

            foreach ($missingStudents as $sid) {
                $insertStmt->execute([
                    ':sid' => $sid,
                    ':ins' => $instructor_id,
                    ':eid' => $event_id,
                    ':att_date' => $date
                ]);
                $marked++;
            }
        }

        echo json_encode([
            'success' => true,
            'message' => "Rollcall finalized. $marked students marked as absent.",
            'count' => $marked
        ]);

    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}
?>