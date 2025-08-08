-- Add assigned_students field to class_tasks table
-- This field will store JSON array of assigned students for individual assignments

ALTER TABLE `class_tasks` 
ADD COLUMN `assigned_students` JSON NULL COMMENT 'Array of assigned students for individual assignments' 
AFTER `class_codes`;

-- Add assignment_type field if it doesn't exist
ALTER TABLE `class_tasks` 
ADD COLUMN `assignment_type` ENUM('classroom', 'individual') NOT NULL DEFAULT 'classroom' 
AFTER `assigned_students`;

-- Add due_date field if it doesn't exist
ALTER TABLE `class_tasks` 
ADD COLUMN `due_date` DATETIME NULL 
AFTER `assignment_type`;

-- Add indexes for better performance
CREATE INDEX `idx_assignment_type` ON `class_tasks` (`assignment_type`);
CREATE INDEX `idx_due_date` ON `class_tasks` (`due_date`);

-- Add composite index for assigned students queries
CREATE INDEX `idx_assignment_type_assigned_students` ON `class_tasks` (`assignment_type`, `assigned_students`(100));
