<?php
require_once dirname(__DIR__) . '/config/db.php';

class Equipment
{
    private $conn;
    private $table = 'equipment';

    public $id;
    public $category_id;
    public $name;
    public $description;
    public $serial_number;
    public $total_quantity;
    public $available_quantity;
    public $status;
    public $condition;
    public $qr_code;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    // Get all equipment
    public function readAll()
    {
        $query = "SELECT e.*, c.name as category_name 
                  FROM " . $this->table . " e
                  LEFT JOIN equipment_categories c ON e.category_id = c.id
                  ORDER BY e.created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    // Get single equipment
    public function readOne()
    {
        $query = "SELECT e.*, c.name as category_name 
                  FROM " . $this->table . " e
                  LEFT JOIN equipment_categories c ON e.category_id = c.id
                  WHERE e.id = ? LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $this->category_id = $row['category_id'];
            $this->name = $row['name'];
            $this->description = $row['description'];
            $this->serial_number = $row['serial_number'];
            $this->total_quantity = $row['total_quantity'];
            $this->available_quantity = $row['available_quantity'];
            $this->status = $row['status'];
            $this->condition = $row['condition'];
            $this->qr_code = $row['qr_code'];
            return true;
        }
        return false;
    }

    // Create equipment
    public function create()
    {
        $query = "INSERT INTO " . $this->table . " 
                  (category_id, name, description, serial_number, total_quantity, available_quantity, status, `condition`, qr_code) 
                  VALUES (:category_id, :name, :description, :serial_number, :total_quantity, :available_quantity, :status, :condition, :qr_code)";
        
        $stmt = $this->conn->prepare($query);

        // Sanitize
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->description = htmlspecialchars(strip_tags($this->description));
        $this->serial_number = htmlspecialchars(strip_tags($this->serial_number));

        // Bind
        $stmt->bindParam(':category_id', $this->category_id);
        $stmt->bindParam(':name', $this->name);
        $stmt->bindParam(':description', $this->description);
        $stmt->bindParam(':serial_number', $this->serial_number);
        $stmt->bindParam(':total_quantity', $this->total_quantity);
        $stmt->bindParam(':available_quantity', $this->available_quantity);
        $stmt->bindParam(':status', $this->status);
        $stmt->bindParam(':condition', $this->condition);
        $stmt->bindParam(':qr_code', $this->qr_code);

        if ($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }

    // Update equipment
    public function update()
    {
        $query = "UPDATE " . $this->table . " 
                  SET category_id = :category_id, 
                      name = :name, 
                      description = :description, 
                      serial_number = :serial_number, 
                      total_quantity = :total_quantity, 
                      available_quantity = :available_quantity, 
                      status = :status, 
                      `condition` = :condition, 
                      qr_code = :qr_code
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);

        // Sanitize
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->description = htmlspecialchars(strip_tags($this->description));
        $this->serial_number = htmlspecialchars(strip_tags($this->serial_number));

        // Bind
        $stmt->bindParam(':category_id', $this->category_id);
        $stmt->bindParam(':name', $this->name);
        $stmt->bindParam(':description', $this->description);
        $stmt->bindParam(':serial_number', $this->serial_number);
        $stmt->bindParam(':total_quantity', $this->total_quantity);
        $stmt->bindParam(':available_quantity', $this->available_quantity);
        $stmt->bindParam(':status', $this->status);
        $stmt->bindParam(':condition', $this->condition);
        $stmt->bindParam(':qr_code', $this->qr_code);
        $stmt->bindParam(':id', $this->id);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Delete equipment
    public function delete()
    {
        $query = "DELETE FROM " . $this->table . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Update stock quantity
    public function updateQuantity($change)
    {
        $query = "UPDATE " . $this->table . " SET available_quantity = available_quantity + :change WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':change', $change);
        $stmt->bindParam(':id', $this->id);
        return $stmt->execute();
    }

    // Update status and condition
    public function updateStatus($status, $condition = null)
    {
        $query = "UPDATE " . $this->table . " SET status = :status" . ($condition ? ", `condition` = :condition" : "") . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':status', $status);
        if ($condition) $stmt->bindParam(':condition', $condition);
        $stmt->bindParam(':id', $this->id);
        return $stmt->execute();
    }
}
