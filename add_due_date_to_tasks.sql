-- Add due_date column to class_tasks table
ALTER TABLE `class_tasks` 
ADD COLUMN `due_date` DATETIME NULL AFTER `scheduled_at`;

-- Update existing tasks to have a default due date (7 days from creation)
UPDATE `class_tasks` 
SET `due_date` = DATE_ADD(`created_at`, INTERVAL 7 DAY) 
WHERE `due_date` IS NULL; 