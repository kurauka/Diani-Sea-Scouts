<?php
session_start();
// Security check: Only admins can run migrations
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    die("Unauthorized access. Please login as admin first.");
}

require_once '../config/db.php';

try {
    $database = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);
    $database->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "<div style='font-family: sans-serif; padding: 20px;'>";
    echo "<h1>Database Migration Assistant</h1>";

    $queries = [
        // 1. Users table update
        "ALTER TABLE `users` MODIFY `role` ENUM('student', 'instructor', 'admin', 'maintenance_officer') DEFAULT 'student'",

        // 2. Equipment Categories
        "CREATE TABLE IF NOT EXISTS `equipment_categories` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `name` VARCHAR(100) NOT NULL UNIQUE,
            `description` TEXT,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        // 3. Equipment
        "CREATE TABLE IF NOT EXISTS `equipment` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `category_id` INT,
            `name` VARCHAR(255) NOT NULL,
            `description` TEXT,
            `serial_number` VARCHAR(100) UNIQUE,
            `total_quantity` INT DEFAULT 1,
            `available_quantity` INT DEFAULT 1,
            `status` ENUM('Available', 'In Use', 'Under Repair', 'Lost', 'Decommissioned') DEFAULT 'Available',
            `condition` ENUM('New', 'Good', 'Worn', 'Damaged') DEFAULT 'Good',
            `qr_code` VARCHAR(255),
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            CONSTRAINT `fk_equipment_category` FOREIGN KEY (`category_id`) REFERENCES `equipment_categories`(`id`) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        // 4. Borrowing Records
        "CREATE TABLE IF NOT EXISTS `borrowing_records` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `equipment_id` INT NOT NULL,
            `user_id` INT NOT NULL,
            `instructor_id` INT,
            `borrow_date` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `due_date` DATETIME,
            `return_date` DATETIME,
            `status` ENUM('Pending', 'Issued', 'Returned', 'Overdue', 'Lost') DEFAULT 'Pending',
            `condition_on_borrow` ENUM('New', 'Good', 'Worn', 'Damaged') DEFAULT 'Good',
            `condition_on_return` ENUM('New', 'Good', 'Worn', 'Damaged'),
            `notes` TEXT,
            CONSTRAINT `fk_borrowing_equipment` FOREIGN KEY (`equipment_id`) REFERENCES `equipment`(`id`) ON DELETE CASCADE,
            CONSTRAINT `fk_borrowing_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        // 5. Maintenance Records
        "CREATE TABLE IF NOT EXISTS `maintenance_records` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `equipment_id` INT NOT NULL,
            `reported_by` INT,
            `maintained_by` INT,
            `issue_description` TEXT NOT NULL,
            `repair_details` TEXT,
            `cost` DECIMAL(10, 2) DEFAULT 0.00,
            `status` ENUM('Reported', 'In Progress', 'Repaired', 'Decommissioned') DEFAULT 'Reported',
            `reported_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `completed_at` TIMESTAMP NULL,
            CONSTRAINT `fk_maintenance_equipment` FOREIGN KEY (`equipment_id`) REFERENCES `equipment`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        // 6. Outdoor Activities
        "CREATE TABLE IF NOT EXISTS `outdoor_activities` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `student_id` INT NOT NULL,
            `title` VARCHAR(255) NOT NULL,
            `activity_type` ENUM('Hiking', 'Camping', 'Scout Craft', 'Community Service', 'Water Activity', 'Other') DEFAULT 'Other',
            `description` TEXT,
            `activity_date` DATE NOT NULL,
            `hours` INT DEFAULT 1,
            `evidence_link` VARCHAR(255),
            `status` ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Pending',
            `instructor_id` INT,
            `instructor_notes` TEXT,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT `fk_activity_student` FOREIGN KEY (`student_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

        // 7. Initial Categories
        "INSERT IGNORE INTO `equipment_categories` (`name`, `description`) VALUES 
        ('Marine Tools', 'Kayaks, paddles, life jackets, etc.'),
        ('Camping Gear', 'Tents, sleeping bags, stoves, etc.'),
        ('Uniforms', 'Scout uniforms, badges, hats, etc.'),
        ('Training Kits', 'First aid kits, ropes, navigation tools, etc.')"
    ];

    foreach ($queries as $sql) {
        try {
            $database->exec($sql);
            echo "<p style='color: green;'>✅ Success: " . substr($sql, 0, 50) . "...</p>";
        } catch (PDOException $e) {
            // Ignore Duplicate column or table errors if they already exist
            if ($e->getCode() == '42S21' || $e->getCode() == '42S01') {
                echo "<p style='color: orange;'>ℹ️ Skipped (Already exists): " . substr($sql, 0, 50) . "...</p>";
            } else {
                echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
            }
        }
    }

    echo "<h3>Migration Finished.</h3>";
    echo "<p>Please <strong style='color: red;'>delete this file (admin/migrate.php)</strong> immediately for security.</p>";
    echo "<a href='dashboard.php' style='display: inline-block; padding: 10px 20px; background: #20A4F3; color: white; text-decoration: none; border-radius: 8px;'>Go to Dashboard</a>";
    echo "</div>";

} catch (PDOException $e) {
    die("Database Connection Failed: " . $e->getMessage());
}
?>