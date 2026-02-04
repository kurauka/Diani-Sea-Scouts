<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    exit('Unauthorized');
}

include_once '../config/db.php';
$database = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);

$type = $_GET['type'] ?? 'users';

if ($type === 'users') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="users_export_' . date('Y-m-d') . '.csv"');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID', 'Name', 'Email', 'Role', 'Created At']);

    $stmt = $database->query("SELECT id, name, email, role, created_at FROM users");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, $row);
    }
    fclose($output);
    exit;
}

if ($type === 'results') {
    $exam_id = $_GET['exam_id'] ?? null;
    if (!$exam_id)
        exit('Exam ID required');

    // Fetch Exam Title for filename
    $stmt = $database->prepare("SELECT title FROM exams WHERE id = ?");
    $stmt->execute([$exam_id]);
    $title = $stmt->fetchColumn() ?: 'Exam';
    $clean_title = preg_replace('/[^A-Za-z0-9\-]/', '_', $title);

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="results_' . $clean_title . '_' . date('Y-m-d') . '.csv"');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['Rank', 'Student Name', 'Email', 'Score', 'Date Taken']);

    $query = "SELECT u.name, u.email, se.score, se.completed_at 
              FROM student_exams se 
              JOIN users u ON se.student_id = u.id 
              WHERE se.exam_id = ? AND se.status = 'completed' 
              ORDER BY se.score DESC";

    $stmt = $database->prepare($query);
    $stmt->execute([$exam_id]);

    $rank = 1;
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, array_merge([$rank++], $row));
    }
    fclose($output);
    exit;
}

if ($type === 'attendance') {
    $start = $_GET['start'] ?? date('Y-m-d');
    $end = $_GET['end'] ?? date('Y-m-d');

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="attendance_report_' . $start . '_to_' . $end . '.csv"');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['Date', 'Time', 'Student Name', 'Email', 'Verified By', 'Event', 'Status']);

    $query = "SELECT a.attendance_date, a.created_at, s.name as student_name, s.email as student_email, i.name as instructor_name, e.title as event_title, a.status
              FROM attendance a 
              JOIN users s ON a.student_id = s.id 
              JOIN users i ON a.instructor_id = i.id 
              LEFT JOIN calendar_events e ON a.event_id = e.id
              WHERE a.attendance_date BETWEEN ? AND ?
              ORDER BY a.attendance_date DESC, a.created_at DESC";

    $stmt = $database->prepare($query);
    $stmt->execute([$start, $end]);

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Format time
        $row['created_at'] = date('H:i:s A', strtotime($row['created_at']));
        fputcsv($output, $row);
    }
    fclose($output);
    exit;
}
?>