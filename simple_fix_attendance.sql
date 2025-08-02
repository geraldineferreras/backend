-- Simple fix for attendance table
-- Run this in your database to add the missing teacher_id column

-- Add teacher_id column to attendance table
ALTER TABLE `attendance` ADD COLUMN `teacher_id` VARCHAR(50) NOT NULL AFTER `notes`;

-- Add created_at and updated_at columns if they don't exist
ALTER TABLE `attendance` ADD COLUMN `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER `teacher_id`;
ALTER TABLE `attendance` ADD COLUMN `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`;

-- Update status enum to include 'excused'
ALTER TABLE `attendance` MODIFY COLUMN `status` ENUM('present', 'late', 'absent', 'excused') NOT NULL;

-- Add unique constraint
ALTER TABLE `attendance` ADD UNIQUE KEY `unique_attendance` (`student_id`, `class_id`, `date`); 