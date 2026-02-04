<?php
class Result
{
    private $conn;
    private $table_student_exams = 'student_exams';
    private $table_answers = 'answers';

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function startExam($student_id, $exam_id)
    {
        // limit 1 active exam or check if already taken
        $query = "INSERT INTO " . $this->table_student_exams . " (student_id, exam_id, status, started_at) VALUES (:student_id, :exam_id, 'in_progress', NOW())";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':student_id', $student_id);
        $stmt->bindParam(':exam_id', $exam_id);
        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    public function submitExam($student_exam_id, $score)
    {
        $query = "UPDATE " . $this->table_student_exams . " SET score = :score, status = 'completed', completed_at = NOW() WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':score', $score);
        $stmt->bindParam(':id', $student_exam_id);
        return $stmt->execute();
    }

    public function saveAnswer($student_exam_id, $question_id, $selected_option, $is_correct)
    {
        $query = "INSERT INTO " . $this->table_answers . " (student_exam_id, question_id, selected_option, is_correct) VALUES (:student_exam_id, :question_id, :selected_option, :is_correct)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':student_exam_id', $student_exam_id);
        $stmt->bindParam(':question_id', $question_id);
        $stmt->bindParam(':selected_option', $selected_option);
        $stmt->bindParam(':is_correct', $is_correct);
        return $stmt->execute();
    }

    public function getStudentResults($student_id)
    {
        $query = "SELECT se.*, e.title, e.duration_minutes FROM " . $this->table_student_exams . " se 
                  JOIN exams e ON se.exam_id = e.id 
                  WHERE se.student_id = :student_id ORDER BY se.completed_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':student_id', $student_id);
        $stmt->execute();
        return $stmt;
    }
}
?>