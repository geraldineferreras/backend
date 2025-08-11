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
