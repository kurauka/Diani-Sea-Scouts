<?php
require_once '../config/db.php';

class User
{
    private $conn;
    private $table = 'users';

    public $id;
    public $name;
    public $email;
    public $password;
    public $role;
    public $status;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    // Register User
    public function register()
    {
        $query = 'INSERT INTO ' . $this->table . ' (name, email, password, role) VALUES (:name, :email, :password, :role)';
        $stmt = $this->conn->prepare($query);

        // Clean data
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->password = htmlspecialchars(strip_tags($this->password));
        $this->role = htmlspecialchars(strip_tags($this->role));

        // Bind data
        $stmt->bindParam(':name', $this->name);
        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':password', password_hash($this->password, PASSWORD_BCRYPT));
        $stmt->bindParam(':role', $this->role);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Login User
    public function login()
    {
        $query = "SELECT id, name, password, role, status FROM " . $this->table . " WHERE email = :email LIMIT 0,1";
        $stmt = $this->conn->prepare($query);

        $this->email = htmlspecialchars(strip_tags($this->email));
        $stmt->bindParam(':email', $this->email);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (password_verify($this->password, $row['password'])) {
                $this->id = $row['id'];
                $this->name = $row['name'];
                $this->role = $row['role'];
                $this->status = $row['status']; // Add this
                return true;
            }
        }
        return false;
    }
}
?>