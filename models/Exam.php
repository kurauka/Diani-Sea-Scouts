<?php
class Exam
{
    private $conn;
    private $table = 'exams';

    public $id;
    public $title;
    public $description;
    public $created_by;
    public $duration_minutes;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function create()
    {
        $query = 'INSERT INTO ' . $this->table . ' (title, description, created_by, duration_minutes) VALUES (:title, :description, :created_by, :duration_minutes)';
        $stmt = $this->conn->prepare($query);

        $this->title = htmlspecialchars(strip_tags($this->title));
        $this->description = htmlspecialchars(strip_tags($this->description));
        $this->created_by = htmlspecialchars(strip_tags($this->created_by));
        $this->duration_minutes = htmlspecialchars(strip_tags($this->duration_minutes));

        $stmt->bindParam(':title', $this->title);
        $stmt->bindParam(':description', $this->description);
        $stmt->bindParam(':created_by', $this->created_by);
        $stmt->bindParam(':duration_minutes', $this->duration_minutes);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    public function read_by_instructor($instructor_id)
    {
        $query = 'SELECT * FROM ' . $this->table . ' WHERE created_by = :instructor_id ORDER BY created_at DESC';
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':instructor_id', $instructor_id);
        $stmt->execute();
        return $stmt;
    }

    public function read_all()
    {
        $query = 'SELECT * FROM ' . $this->table . ' ORDER BY created_at DESC';
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }
}
?>