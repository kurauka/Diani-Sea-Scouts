<?php
class ActivityReport
{
    private $conn;
    private $table = 'outdoor_activities';

    public $id;
    public $student_id;
    public $title;
    public $activity_type;
    public $description;
    public $activity_date;
    public $hours;
    public $evidence_link;
    public $status;
    public $instructor_id;
    public $instructor_notes;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function create()
    {
        $query = "INSERT INTO " . $this->table . " 
                  (student_id, title, activity_type, description, activity_date, hours, evidence_link) 
                  VALUES (:student_id, :title, :activity_type, :description, :activity_date, :hours, :evidence_link)";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':student_id', $this->student_id);
        $stmt->bindParam(':title', $this->title);
        $stmt->bindParam(':activity_type', $this->activity_type);
        $stmt->bindParam(':description', $this->description);
        $stmt->bindParam(':activity_date', $this->activity_date);
        $stmt->bindParam(':hours', $this->hours);
        $stmt->bindParam(':evidence_link', $this->evidence_link);

        return $stmt->execute();
    }

    public function readByStudent($student_id)
    {
        $query = "SELECT a.*, u.name as instructor_name 
                  FROM " . $this->table . " a
                  LEFT JOIN users u ON a.instructor_id = u.id
                  WHERE a.student_id = :sid
                  ORDER BY a.activity_date DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':sid', $student_id);
        $stmt->execute();
        return $stmt;
    }

    public function readPending()
    {
        $query = "SELECT a.*, u.name as student_name 
                  FROM " . $this->table . " a
                  JOIN users u ON a.student_id = u.id
                  WHERE a.status = 'Pending'
                  ORDER BY a.created_at ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function updateStatus($status, $instructor_id, $notes)
    {
        $query = "UPDATE " . $this->table . " 
                  SET status = :status, 
                      instructor_id = :instructor_id, 
                      instructor_notes = :notes 
                  WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':instructor_id', $instructor_id);
        $stmt->bindParam(':notes', $notes);
        $stmt->bindParam(':id', $this->id);
        return $stmt->execute();
    }
}
