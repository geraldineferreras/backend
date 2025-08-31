-- Add missing semester and school_year fields to classes table
-- This migration adds the fields that the code expects but are missing from the schema

-- Add semester field to classes table
ALTER TABLE `classes` 
ADD COLUMN `semester` INT NOT NULL DEFAULT 1 AFTER `teacher_id`;

-- Add school_year field to classes table  
ALTER TABLE `classes` 
ADD COLUMN `school_year` VARCHAR(10) NOT NULL DEFAULT '2024' AFTER `semester`;

-- Add indexes for better performance
ALTER TABLE `classes` 
ADD INDEX `idx_semester` (`semester`),
ADD INDEX `idx_school_year` (`school_year`);

-- Update existing records with default values
UPDATE `classes` SET `semester` = 1 WHERE `semester` IS NULL;
UPDATE `classes` SET `school_year` = '2024' WHERE `school_year` IS NULL;

-- Verify the changes
DESCRIBE `classes`;
