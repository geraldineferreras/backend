-- Add created_by column to subjects table (Safe Version)
-- This version checks if the column exists before adding it
-- This allows tracking which user (Program Chairperson) created each subject

-- Method 1: Simple approach - Run each statement separately
-- If you get "Duplicate column name" error, the column already exists and you can skip that step

-- Step 1: Add created_by column
-- IMPORTANT: Select and run BOTH lines 11-12 together (the ALTER TABLE and ADD COLUMN)
ALTER TABLE `subjects` 
ADD COLUMN `created_by` VARCHAR(50) DEFAULT NULL AFTER `status`;

-- Step 2: Add index (only if column was successfully added)
-- If you get "Duplicate key name" error, the index already exists
ALTER TABLE `subjects` 
ADD INDEX `idx_created_by` (`created_by`);

-- Step 3: Verify the changes
DESCRIBE `subjects`;

-- Alternative Method 2: Using a stored procedure to check first (more advanced)
-- Uncomment below if you prefer this approach:
/*
DELIMITER $$

CREATE PROCEDURE AddCreatedByColumnIfNotExists()
BEGIN
    DECLARE column_exists INT DEFAULT 0;
    
    SELECT COUNT(*) INTO column_exists
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'subjects'
    AND COLUMN_NAME = 'created_by';
    
    IF column_exists = 0 THEN
        ALTER TABLE `subjects` 
        ADD COLUMN `created_by` VARCHAR(50) DEFAULT NULL AFTER `status`;
        
        ALTER TABLE `subjects` 
        ADD INDEX `idx_created_by` (`created_by`);
        
        SELECT 'Column created_by added successfully' AS result;
    ELSE
        SELECT 'Column created_by already exists' AS result;
    END IF;
END$$

DELIMITER ;

-- Run the procedure
CALL AddCreatedByColumnIfNotExists();

-- Clean up (optional - remove the procedure after use)
-- DROP PROCEDURE IF EXISTS AddCreatedByColumnIfNotExists;
*/

