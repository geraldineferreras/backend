-- Add original_filename field to class_tasks table
-- This will store the actual original filename of uploaded task attachments

ALTER TABLE `class_tasks` 
ADD COLUMN `original_filename` VARCHAR(255) NULL AFTER `attachment_url`;

-- Add index for better performance when searching by original filename
CREATE INDEX `idx_original_filename` ON `class_tasks` (`original_filename`);

-- Update existing records to have a placeholder for original_filename
-- For existing tasks, we'll set it to NULL since we don't have the original filenames
UPDATE `class_tasks` 
SET `original_filename` = NULL 
WHERE `original_filename` IS NULL;

-- Show the updated table structure
DESCRIBE `class_tasks`;
