<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'instructor') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

include_once '../config/db.php';

// Get JSON Input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit;
}

$title = $input['title'];
$description = $input['description'];
$subject = isset($input['subject']) ? $input['subject'] : 'General';
$duration = $input['duration'];
$questions = $input['questions'];
$created_by = $_SESSION['user_id'];

try {
    $database = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);

    // Start Transaction
    $database->beginTransaction();

    // 1. Insert Exam
    // 1. Insert Exam
    $queryExam = "INSERT INTO exams (title, description, subject, created_by, duration_minutes) VALUES (:title, :desc, :subj, :uid, :dur)";
    $stmt = $database->prepare($queryExam);
    $stmt->execute([
        ':title' => $title,
        ':desc' => $description,
        ':subj' => $subject,
        ':uid' => $created_by,
        ':dur' => $duration
    ]);

    $exam_id = $database->lastInsertId();

    // 2. Insert Questions
    $queryQ = "INSERT INTO questions (exam_id, question_text, option_a, option_b, option_c, option_d, correct_option) VALUES (:eid, :txt, :a, :b, :c, :d, :corr)";
    $stmtQ = $database->prepare($queryQ);

    foreach ($questions as $q) {
        $stmtQ->execute([
            ':eid' => $exam_id,
            ':txt' => $q['text'],
            ':a' => $q['options']['A'],
            ':b' => $q['options']['B'],
            ':c' => $q['options']['C'],
            ':d' => $q['options']['D'],
            ':corr' => $q['correct']
        ]);
    }

    // Commit
    $database->commit();

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    $database->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>