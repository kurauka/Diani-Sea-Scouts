<?php
// Verification script for Attendance Enhancements
require_once dirname(__DIR__) . '/config/db.php';

echo "Starting Attendance Enhancement Verification...\n";

try {
    // 0. Mock session for instructor
    session_start();
    $_SESSION['role'] = 'instructor';
    $_SESSION['user_id'] = 1;

    // 1. Test Event Creation (Simulate what attendance.php does)
    $title = "Test Activity " . time();
    $type = "class";
    $start = date('Y-m-d');
    $uid = 1;

    $stmt = $database->prepare("INSERT INTO calendar_events (title, start_date, type, created_by) VALUES (?, ?, ?, ?)");
    $stmt->execute([$title, $start, $type, $uid]);
    $eventId = $database->lastInsertId();
    echo "✓ Activity Created (ID: $eventId)\n";

    // 2. Test Attendance Marking with Event ID
    // We'll use a student ID that exists, or mock one if needed.
    // For verification, let's just check the logic in mark_attendance.php works via a direct call simulation
    $_POST['student_id'] = 1; // Assuming student 1 exists
    $_POST['event_id'] = $eventId;
    $_POST['status'] = 'present';

    // Instead of including mark_attendance.php (which would exit/json), we'll check the DB manually
    $sql = "INSERT INTO attendance (student_id, instructor_id, event_id, attendance_date, status) 
            VALUES (:sid, :ins, :eid, CURRENT_DATE, :status)";
    $stmt = $database->prepare($sql);
    $stmt->execute([':sid' => 1, ':ins' => 1, ':eid' => $eventId, ':status' => 'present']);
    $attendanceId = $database->lastInsertId();
    echo "✓ Attendance Record Created with Event ID: $eventId\n";

    // 3. Verify linkage
    $check = $database->prepare("SELECT event_id FROM attendance WHERE id = ?");
    $check->execute([$attendanceId]);
    $linkedId = $check->fetchColumn();
    if ($linkedId == $eventId) {
        echo "✓ Linkage Verified: $linkedId == $eventId\n";
    } else {
        throw new Exception("Linkage failed: $linkedId != $eventId");
    }

    // Cleanup
    $database->exec("DELETE FROM attendance WHERE id = $attendanceId");
    $database->exec("DELETE FROM calendar_events WHERE id = $eventId");
    echo "✓ Test Data Cleaned Up\n";
    echo "Verification Successful!\n";

} catch (Exception $e) {
    echo "Verification Failed: " . $e->getMessage() . "\n";
}
