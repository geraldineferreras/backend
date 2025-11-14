-- Add created_by column to subjects table
-- This allows tracking which user (Program Chairperson) created each subject
-- 
-- IMPORTANT: Make sure to run the entire ALTER TABLE statement, not just the ADD COLUMN line

-- Step 1: Add created_by column (run this entire statement)
-- Column will be added at the end of the table
ALTER TABLE `subjects` 
ADD COLUMN `created_by` VARCHAR(50) DEFAULT NULL;

-- Step 2: Add index for better query performance when filtering by creator
ALTER TABLE `subjects` 
ADD INDEX `idx_created_by` (`created_by`);

-- Optional: Add foreign key constraint to users table for referential integrity
-- Uncomment the following lines if you want to enforce referential integrity:
-- ALTER TABLE `subjects` 
-- ADD CONSTRAINT `fk_subjects_created_by` 
-- FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

-- Step 3: Verify the changes
DESCRIBE `subjects`;

