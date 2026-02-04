<?php
class Question
{
    private $conn;
    private $table = 'questions';

    public $id;
    public $exam_id;
    public $question_text;
    public $option_a;
    public $option_b;
    public $option_c;
    public $option_d;
    public $correct_option;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function create()
    {
        $query = 'INSERT INTO ' . $this->table . ' (exam_id, question_text, option_a, option_b, option_c, option_d, correct_option) VALUES (:exam_id, :question_text, :option_a, :option_b, :option_c, :option_d, :correct_option)';
        $stmt = $this->conn->prepare($query);

        $this->question_text = htmlspecialchars(strip_tags($this->question_text));
        $this->option_a = htmlspecialchars(strip_tags($this->option_a));
        $this->option_b = htmlspecialchars(strip_tags($this->option_b));
        $this->option_c = htmlspecialchars(strip_tags($this->option_c));
        $this->option_d = htmlspecialchars(strip_tags($this->option_d));
        $this->correct_option = htmlspecialchars(strip_tags($this->correct_option));

        $stmt->bindParam(':exam_id', $this->exam_id);
        $stmt->bindParam(':question_text', $this->question_text);
        $stmt->bindParam(':option_a', $this->option_a);
        $stmt->bindParam(':option_b', $this->option_b);
        $stmt->bindParam(':option_c', $this->option_c);
        $stmt->bindParam(':option_d', $this->option_d);
        $stmt->bindParam(':correct_option', $this->correct_option);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    public function read_by_exam($exam_id)
    {
        $query = 'SELECT * FROM ' . $this->table . ' WHERE exam_id = :exam_id ORDER BY created_at ASC';
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':exam_id', $exam_id);
        $stmt->execute();
        return $stmt;
    }
}
?>