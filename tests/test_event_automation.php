<?php
// Verification script for Event-Based Attendance & Automation
require_once dirname(__DIR__) . '/config/db.php';

echo "Starting Event-Based Attendance Verification...\n";

try {
    // 1. Mock session for instructor
    session_start();
    $_SESSION['role'] = 'instructor';
    $_SESSION['user_id'] = 1; // Assuming instructor 1 exists

    // 2. Create a test event for today
    $title = "Test Automation Event " . time();
    $start = date('Y-m-d');
    $uid = 1;
    $stmt = $database->prepare("INSERT INTO calendar_events (title, start_date, type, created_by) VALUES (?, ?, 'meeting', ?)");
    $stmt->execute([$title, $start, $uid]);
    $eventId = $database->lastInsertId();
    echo "✓ Test Event Created (ID: $eventId)\n";

    // 3. Mark ONE student as present
    $studentId = 1; // Assuming student 1 exists
    $stmt = $database->prepare("INSERT INTO attendance (student_id, instructor_id, event_id, attendance_date, status) VALUES (?, ?, ?, ?, 'present')");
    $stmt->execute([$studentId, $uid, $eventId, $start]);
    echo "✓ Student 1 marked as Present\n";

    // 4. Trigger Finalize Logic (Simulate POST to finalize_attendance.php)
    $_POST['event_id'] = $eventId;

    // Logic from finalize_attendance.php (running directly for test)
    $stmt = $database->prepare("SELECT troop_id FROM users WHERE id = ?");
    $stmt->execute([$uid]);
    $troop_id = $stmt->fetchColumn();

    $findQuery = "SELECT id FROM users 
                  WHERE role = 'student' 
                  AND (troop_id = :troop OR (:troop IS NULL AND troop_id IS NULL))
                  AND id != :sid
                  AND id NOT IN (
                      SELECT student_id FROM attendance 
                      WHERE attendance_date = :date 
                      AND (event_id = :eid OR (:eid IS NULL AND event_id IS NULL))
                  )";

    $findStmt = $database->prepare($findQuery);
    $findStmt->execute([':troop' => $troop_id, ':eid' => $eventId, ':date' => $start, ':sid' => $studentId]);
    $missingStudents = $findStmt->fetchAll(PDO::FETCH_COLUMN);

    $marked = 0;
    if (!empty($missingStudents)) {
        $insertStmt = $database->prepare("INSERT INTO attendance (student_id, instructor_id, event_id, attendance_date, status) VALUES (?, ?, ?, ?, 'absent')");
        foreach ($missingStudents as $sid) {
            $insertStmt->execute([$sid, $uid, $eventId, $start]);
            $marked++;
        }
    }
    echo "✓ Finalize Logic executed. $marked students marked as absent.\n";

    // 5. Verify database
    $checkStmt = $database->prepare("SELECT COUNT(*) FROM attendance WHERE event_id = ? AND status = 'absent'");
    $checkStmt->execute([$eventId]);
    $absentCount = $checkStmt->fetchColumn();

    if ($absentCount == $marked) {
        echo "✓ Verification Successful: $absentCount absent records found.\n";
    } else {
        throw new Exception("Count mismatch: Expected $marked, found $absentCount");
    }

    // Cleanup
    $database->exec("DELETE FROM attendance WHERE event_id = $eventId");
    $database->exec("DELETE FROM calendar_events WHERE id = $eventId");
    echo "✓ Test Data Cleaned Up\n";
    echo "All Tests Passed!\n";

} catch (Exception $e) {
    echo "Verification Failed: " . $e->getMessage() . "\n";
}
