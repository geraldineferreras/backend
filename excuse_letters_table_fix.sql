-- Add missing fields to excuse_letters table
-- This script adds teacher_id and teacher_notes fields

-- Add teacher_id column to link excuse letters to specific teachers
ALTER TABLE `excuse_letters` ADD COLUMN `teacher_id` VARCHAR(50) NOT NULL AFTER `class_id`;

-- Add teacher_notes column for teacher feedback
ALTER TABLE `excuse_letters` ADD COLUMN `teacher_notes` TEXT NULL AFTER `status`;

-- Add created_at and updated_at columns
ALTER TABLE `excuse_letters` ADD COLUMN `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER `teacher_notes`;
ALTER TABLE `excuse_letters` ADD COLUMN `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`;

-- Add index for better performance on teacher queries
ALTER TABLE `excuse_letters` ADD INDEX `idx_teacher_id` (`teacher_id`);

-- Add index for status queries
ALTER TABLE `excuse_letters` ADD INDEX `idx_status` (`status`);

-- Add unique constraint to prevent duplicate submissions
ALTER TABLE `excuse_letters` ADD UNIQUE KEY `unique_excuse` (`student_id`, `class_id`, `date_absent`);

-- Update existing records to set teacher_id based on class assignment
-- This assumes existing records need to be updated with the teacher from the classes table
UPDATE `excuse_letters` 
SET `teacher_id` = (
    SELECT `teacher_id` 
    FROM `classes` 
    WHERE `classes`.`class_id` = `excuse_letters`.`class_id`
)
WHERE `teacher_id` IS NULL OR `teacher_id` = ''; 