<?php
require_once dirname(__DIR__) . '/config/db.php';

class Maintenance
{
    private $conn;
    private $table = 'maintenance_records';

    public $id;
    public $equipment_id;
    public $reported_by;
    public $maintained_by;
    public $issue_description;
    public $repair_details;
    public $cost;
    public $status;
    public $reported_at;
    public $completed_at;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    // Report maintenance issue
    public function create()
    {
        $query = "INSERT INTO " . $this->table . " 
                  (equipment_id, reported_by, issue_description, status) 
                  VALUES (:equipment_id, :reported_by, :issue_description, 'Reported')";

        $stmt = $this->conn->prepare($query);

        $this->issue_description = htmlspecialchars(strip_tags($this->issue_description));

        $stmt->bindParam(':equipment_id', $this->equipment_id);
        $stmt->bindParam(':reported_by', $this->reported_by);
        $stmt->bindParam(':issue_description', $this->issue_description);

        if ($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }

    // Update maintenance (In Progress / Repaired)
    public function update()
    {
        $query = "UPDATE " . $this->table . " 
                  SET maintained_by = :maintained_by, 
                      repair_details = :repair_details, 
                      cost = :cost, 
                      status = :status, 
                      completed_at = " . ($this->status == 'Repaired' ? 'CURRENT_TIMESTAMP' : 'NULL') . "
                  WHERE id = :id";

        $stmt = $this->conn->prepare($query);

        $this->repair_details = htmlspecialchars(strip_tags($this->repair_details));

        $stmt->bindParam(':maintained_by', $this->maintained_by);
        $stmt->bindParam(':repair_details', $this->repair_details);
        $stmt->bindParam(':cost', $this->cost);
        $stmt->bindParam(':status', $this->status);
        $stmt->bindParam(':id', $this->id);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Get all maintenance logs
    public function readAll()
    {
        $query = "SELECT m.*, e.name as equipment_name, u.name as reporter_name, o.name as officer_name 
                  FROM " . $this->table . " m
                  JOIN equipment e ON m.equipment_id = e.id
                  LEFT JOIN users u ON m.reported_by = u.id
                  LEFT JOIN users o ON m.maintained_by = o.id
                  ORDER BY m.reported_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }
}
