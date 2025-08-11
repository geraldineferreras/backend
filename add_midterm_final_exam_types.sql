-- Add midterm exam and final exam task types to class_tasks table
-- This migration adds two new task types to the existing enum

-- First, we need to modify the enum to include the new types
-- MySQL requires recreating the column to modify an enum

-- Step 1: Create a temporary table with the new structure
CREATE TABLE `class_tasks_temp` LIKE `class_tasks`;

-- Step 2: Modify the type column in the temporary table
ALTER TABLE `class_tasks_temp` 
MODIFY COLUMN `type` enum('assignment','quiz','activity','project','exam','midterm_exam','final_exam') NOT NULL DEFAULT 'assignment';

-- Step 3: Copy data from the original table to the temporary table
INSERT INTO `class_tasks_temp` SELECT * FROM `class_tasks`;

-- Step 4: Drop the original table
DROP TABLE `class_tasks`;

-- Step 5: Rename the temporary table to the original name
RENAME TABLE `class_tasks_temp` TO `class_tasks`;

-- Step 6: Recreate the indexes
ALTER TABLE `class_tasks` 
ADD PRIMARY KEY (`task_id`),
ADD KEY `idx_teacher_id` (`teacher_id`),
ADD KEY `idx_type` (`type`),
ADD KEY `idx_status` (`status`),
ADD KEY `idx_is_draft` (`is_draft`),
ADD KEY `idx_is_scheduled` (`is_scheduled`),
ADD KEY `idx_created_at` (`created_at`);

-- Verify the change
DESCRIBE `class_tasks`;

-- Show the new enum values
SELECT COLUMN_TYPE 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME = 'class_tasks' 
AND COLUMN_NAME = 'type';
