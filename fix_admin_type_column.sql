-- Fix admin_type column size to accommodate longer values
-- Run this script in your MySQL database interface

-- First, let's check the current column definition
DESCRIBE users;

-- Update the admin_type column to be larger
ALTER TABLE users MODIFY COLUMN admin_type VARCHAR(50);

-- Verify the change
DESCRIBE users;

-- Now run the admin type assignment
-- Step 1: If there's no main_admin and we have a BSIT admin, make them main_admin
UPDATE users 
SET admin_type = 'main_admin' 
WHERE role = 'admin' 
AND (admin_type IS NULL OR admin_type = '') 
AND program = 'BSIT'
AND user_id = (
    SELECT user_id FROM (
        SELECT user_id FROM users 
        WHERE role = 'admin' 
        AND (admin_type IS NULL OR admin_type = '') 
        AND program = 'BSIT'
        LIMIT 1
    ) as temp
);

-- Step 2: Set all other admins without admin_type as program_chairperson
UPDATE users 
SET admin_type = 'program_chairperson' 
WHERE role = 'admin' 
AND (admin_type IS NULL OR admin_type = '');

-- Verify the results
SELECT user_id, full_name, program, admin_type 
FROM users 
WHERE role = 'admin'
ORDER BY admin_type, program;

-- Show final distribution
SELECT admin_type, COUNT(*) as count 
FROM users 
WHERE role = 'admin' 
GROUP BY admin_type;
