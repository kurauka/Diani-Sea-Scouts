<?php
require_once dirname(__DIR__) . '/config/db.php';

class Borrowing
{
    private $conn;
    private $table = 'borrowing_records';

    public $id;
    public $equipment_id;
    public $user_id;
    public $instructor_id;
    public $borrow_date;
    public $due_date;
    public $return_date;
    public $status;
    public $condition_on_borrow;
    public $condition_on_return;
    public $notes;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    // Create borrowing record (Check-out)
    public function create()
    {
        $query = "INSERT INTO " . $this->table . " 
                  (equipment_id, user_id, instructor_id, due_date, status, condition_on_borrow, notes) 
                  VALUES (:equipment_id, :user_id, :instructor_id, :due_date, :status, :condition_on_borrow, :notes)";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':equipment_id', $this->equipment_id);
        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->bindParam(':instructor_id', $this->instructor_id);
        $stmt->bindParam(':due_date', $this->due_date);
        $stmt->bindParam(':status', $this->status);
        $stmt->bindParam(':condition_on_borrow', $this->condition_on_borrow);
        $stmt->bindParam(':notes', $this->notes);

        if ($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }

    // Update borrowing record (Check-in/Return)
    public function returnEquipment($return_condition)
    {
        $query = "UPDATE " . $this->table . " 
                  SET return_date = CURRENT_TIMESTAMP, 
                      status = 'Returned', 
                      condition_on_return = :condition_on_return 
                  WHERE id = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':condition_on_return', $return_condition);
        $stmt->bindParam(':id', $this->id);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Get personal borrowing history
    public function readByUser($user_id)
    {
        $query = "SELECT b.*, e.name as equipment_name, u.name as instructor_name 
                  FROM " . $this->table . " b
                  JOIN equipment e ON b.equipment_id = e.id
                  LEFT JOIN users u ON b.instructor_id = u.id
                  WHERE b.user_id = ?
                  ORDER BY b.borrow_date DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $user_id);
        $stmt->execute();
        return $stmt;
    }

    // Get all active borrowings (for Admin/Instructor)
    public function readAllActive()
    {
        $query = "SELECT b.*, e.name as equipment_name, u.name as borrower_name, i.name as instructor_name 
                  FROM " . $this->table . " b
                  JOIN equipment e ON b.equipment_id = e.id
                  JOIN users u ON b.user_id = u.id
                  LEFT JOIN users i ON b.instructor_id = i.id
                  WHERE b.status IN ('Issued', 'Overdue')
                  ORDER BY b.due_date ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }
}
