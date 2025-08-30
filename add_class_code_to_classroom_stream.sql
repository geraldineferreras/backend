-- Add class_code column to classroom_stream table
ALTER TABLE `classroom_stream`
  ADD COLUMN `class_code` VARCHAR(20) NOT NULL AFTER `id`,
  ADD INDEX `idx_class_code` (`class_code`);

-- Update existing records to have a class_code (if any exist)
-- This assumes existing posts should be linked to a default classroom
-- You may need to adjust this based on your existing data
UPDATE `classroom_stream` cs
JOIN `classrooms` c ON cs.classroom_id = c.id
SET cs.class_code = c.class_code
WHERE cs.class_code IS NULL OR cs.class_code = '';
