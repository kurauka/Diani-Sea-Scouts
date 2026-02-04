-- Database setup for Outdoor Activity Reporting

CREATE TABLE IF NOT EXISTS `outdoor_activities` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `student_id` INT NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `activity_type` ENUM('Hiking', 'Camping', 'Scout Craft', 'Community Service', 'Water Activity', 'Other') DEFAULT 'Other',
    `description` TEXT,
    `activity_date` DATE NOT NULL,
    `hours` INT DEFAULT 1,
    `evidence_link` VARCHAR(255),
    `status` ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Pending',
    `instructor_id` INT, -- Assigned when approved/rejected
    `instructor_notes` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_activity_student` FOREIGN KEY (`student_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_activity_instructor` FOREIGN KEY (`instructor_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
