-- Create table for task-student assignments
CREATE TABLE IF NOT EXISTS `task_student_assignments` (
  `assignment_id` int(11) NOT NULL AUTO_INCREMENT,
  `task_id` int(11) NOT NULL,
  `student_id` varchar(50) NOT NULL,
  `class_code` varchar(20) NOT NULL,
  `assigned_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `status` enum('assigned','completed','overdue') DEFAULT 'assigned',
  PRIMARY KEY (`assignment_id`),
  UNIQUE KEY `unique_task_student` (`task_id`, `student_id`, `class_code`),
  KEY `task_id` (`task_id`),
  KEY `student_id` (`student_id`),
  KEY `class_code` (`class_code`),
  CONSTRAINT `fk_task_assignments_task` FOREIGN KEY (`task_id`) REFERENCES `class_tasks` (`task_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_task_assignments_student` FOREIGN KEY (`student_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Add new columns to class_tasks table
ALTER TABLE `class_tasks` 
ADD COLUMN `assignment_type` enum('classroom','individual') DEFAULT 'classroom' AFTER `class_codes`,
ADD COLUMN `assigned_students` json NULL AFTER `assignment_type`;

-- Add index for better performance
ALTER TABLE `class_tasks` ADD INDEX `idx_assignment_type` (`assignment_type`); 