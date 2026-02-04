-- Migration script for Equipment Inventory System

-- 1. Update users table to include 'maintenance_officer' role
ALTER TABLE `users` MODIFY `role` ENUM('student', 'instructor', 'admin', 'maintenance_officer') DEFAULT 'student';

-- 2. Create equipment_categories table
CREATE TABLE IF NOT EXISTS `equipment_categories` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL UNIQUE,
    `description` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Create equipment table
CREATE TABLE IF NOT EXISTS `equipment` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Create borrowing_records table
CREATE TABLE IF NOT EXISTS `borrowing_records` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `equipment_id` INT NOT NULL,
    `user_id` INT NOT NULL,
    `instructor_id` INT, -- The one who approved/issued it
    `borrow_date` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `due_date` DATETIME,
    `return_date` DATETIME,
    `status` ENUM('Pending', 'Issued', 'Returned', 'Overdue', 'Lost') DEFAULT 'Pending',
    `condition_on_borrow` ENUM('New', 'Good', 'Worn', 'Damaged') DEFAULT 'Good',
    `condition_on_return` ENUM('New', 'Good', 'Worn', 'Damaged'),
    `notes` TEXT,
    CONSTRAINT `fk_borrowing_equipment` FOREIGN KEY (`equipment_id`) REFERENCES `equipment`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_borrowing_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_borrowing_instructor` FOREIGN KEY (`instructor_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Create maintenance_records table
CREATE TABLE IF NOT EXISTS `maintenance_records` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `equipment_id` INT NOT NULL,
    `reported_by` INT,
    `maintained_by` INT, -- Maintenance officer
    `issue_description` TEXT NOT NULL,
    `repair_details` TEXT,
    `cost` DECIMAL(10, 2) DEFAULT 0.00,
    `status` ENUM('Reported', 'In Progress', 'Repaired', 'Decommissioned') DEFAULT 'Reported',
    `reported_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `completed_at` TIMESTAMP NULL,
    CONSTRAINT `fk_maintenance_equipment` FOREIGN KEY (`equipment_id`) REFERENCES `equipment`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_maintenance_reporter` FOREIGN KEY (`reported_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_maintenance_officer` FOREIGN KEY (`maintained_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default categories
INSERT INTO `equipment_categories` (`name`, `description`) VALUES 
('Marine Tools', 'Kayaks, paddles, life jackets, etc.'),
('Camping Gear', 'Tents, sleeping bags, stoves, etc.'),
('Uniforms', 'Scout uniforms, badges, hats, etc.'),
('Training Kits', 'First aid kits, ropes, navigation tools, etc.');
