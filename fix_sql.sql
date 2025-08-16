-- Fix student ID mismatch for Face To Face Task (task_id = 91)
-- This will update the assigned_students field to use the actual student IDs from submissions

-- First, let's see what's currently in the assigned_students field
SELECT task_id, title, assigned_students 
FROM class_tasks 
WHERE task_id = 91;

-- Now let's see what student IDs are actually in the task_submissions table
SELECT student_id, grade, status 
FROM task_submissions 
WHERE task_id = 91;

-- Update the assigned_students field with the correct student IDs
UPDATE class_tasks 
SET assigned_students = '[{"student_id":"STU689436CBB","class_code":"A4V9TE"},{"student_id":"STU689436695C","class_code":"A4V9TE"},{"student_id":"STU689439A217","class_code":"A4V9TE"}]'
WHERE task_id = 91;

-- Verify the update
SELECT task_id, title, assigned_students 
FROM class_tasks 
WHERE task_id = 91;

-- Fix attendance table section_name null values
-- Update attendance records with null section_name by joining with classes and sections tables
UPDATE attendance a
JOIN classes c ON a.class_id = c.class_id
JOIN sections s ON c.section_id = s.section_id
SET a.section_name = s.section_name
WHERE a.section_name IS NULL OR a.section_name = '';

-- Update attendance records with null section_name by joining with classrooms and sections tables
-- (for cases where class_id refers to classroom.id instead of classes.class_id)
UPDATE attendance a
JOIN classrooms cr ON a.class_id = cr.id
JOIN sections s ON cr.section_id = s.section_id
SET a.section_name = s.section_name
WHERE a.section_name IS NULL OR a.section_name = ''
AND NOT EXISTS (
    SELECT 1 FROM classes c WHERE c.class_id = a.class_id
);

-- Update attendance records with null section_name by joining with users and sections tables
-- (for cases where we can get section from student's section_id)
UPDATE attendance a
JOIN users u ON a.student_id = u.user_id
JOIN sections s ON u.section_id = s.section_id
SET a.section_name = s.section_name
WHERE a.section_name IS NULL OR a.section_name = ''
AND u.section_id IS NOT NULL;

-- Set default section name for any remaining null values
UPDATE attendance 
SET section_name = 'Unknown Section' 
WHERE section_name IS NULL OR section_name = '';

-- Ensure section_name column is NOT NULL
ALTER TABLE attendance MODIFY COLUMN section_name VARCHAR(100) NOT NULL;

-- Add index on section_name for better performance
ALTER TABLE attendance ADD INDEX idx_section_name (section_name);
