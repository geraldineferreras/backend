-- Fix Attendance Table Structure
-- Add missing columns to the attendance table

-- First, check if the attendance table exists
-- If it doesn't exist, create it with the complete structure
CREATE TABLE IF NOT EXISTS `attendance` (
  `attendance_id` INT PRIMARY KEY AUTO_INCREMENT,
  `student_id` VARCHAR(50) NOT NULL,
  `class_id` INT NOT NULL,
  `subject_id` INT NOT NULL,
  `section_name` VARCHAR(50) NOT NULL,
  `date` DATE NOT NULL,
  `time_in` TIME,
  `status` ENUM('present', 'late', 'absent', 'excused') NOT NULL,
  `notes` TEXT,
  `teacher_id` VARCHAR(50) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `unique_attendance` (`student_id`, `class_id`, `date`)
);

-- If the table already exists, add the missing columns
-- Add teacher_id column if it doesn't exist
ALTER TABLE `attendance` 
ADD COLUMN IF NOT EXISTS `teacher_id` VARCHAR(50) NOT NULL AFTER `notes`;

-- Add created_at column if it doesn't exist
ALTER TABLE `attendance` 
ADD COLUMN IF NOT EXISTS `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER `teacher_id`;

-- Add updated_at column if it doesn't exist
ALTER TABLE `attendance` 
ADD COLUMN IF NOT EXISTS `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`;

-- Add unique constraint if it doesn't exist
ALTER TABLE `attendance` 
ADD UNIQUE KEY IF NOT EXISTS `unique_attendance` (`student_id`, `class_id`, `date`);

-- Update the status enum to include 'excused' if it doesn't exist
ALTER TABLE `attendance` 
MODIFY COLUMN `status` ENUM('present', 'late', 'absent', 'excused') NOT NULL;

-- Add indexes for better performance
ALTER TABLE `attendance` 
ADD INDEX IF NOT EXISTS `idx_class_date` (`class_id`, `date`),
ADD INDEX IF NOT EXISTS `idx_student_date` (`student_id`, `date`),
ADD INDEX IF NOT EXISTS `idx_teacher_date` (`teacher_id`, `date`);

-- Verify the table structure
DESCRIBE `attendance`; 