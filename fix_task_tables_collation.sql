-- Fix collation mismatch for task tables
-- This script will alter existing tables to use utf8mb4_general_ci collation

-- Alter class_tasks table collation
ALTER TABLE `class_tasks` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

-- Alter task_comments table collation  
ALTER TABLE `task_comments` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

-- Alter task_submissions table collation
ALTER TABLE `task_submissions` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

-- Verify the collation changes
SELECT 
    TABLE_NAME,
    TABLE_COLLATION
FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME IN ('class_tasks', 'task_comments', 'task_submissions', 'users'); 