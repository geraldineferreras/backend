-- Fix Missing Attendance Records for Excuse Letters
-- This script creates missing attendance records for excuse letters that don't have
-- corresponding attendance records, especially for rejected excuse letters.

-- Step 1: Check which excuse letters don't have corresponding attendance records
SELECT 
    'Missing attendance records for excuse letters:' as info,
    COUNT(*) as count
FROM excuse_letters el
LEFT JOIN attendance a ON 
    el.student_id = a.student_id 
    AND el.class_id = a.class_id 
    
    AND el.date_absent = a.date
WHERE a.attendance_id IS NULL;

-- Step 2: Show the missing records
SELECT 
    el.letter_id,
    el.student_id,
    el.class_id,
    el.teacher_id,
    el.date_absent,
    el.reason,
    el.status,
    'MISSING' as attendance_status
FROM excuse_letters el
LEFT JOIN attendance a ON 
    el.student_id = a.student_id 
    AND el.class_id = a.class_id 
    AND el.date_absent = a.date
WHERE a.attendance_id IS NULL
ORDER BY el.date_absent DESC, el.student_id;

-- Step 3: Create missing attendance records for approved excuse letters
INSERT INTO attendance (
    student_id, 
    class_id, 
    subject_id, 
    section_name, 
    date, 
    time_in, 
    status, 
    notes, 
    teacher_id, 
    created_at, 
    updated_at
)
SELECT 
    el.student_id,
    el.class_id,
    COALESCE(c.subject_id, cr.subject_id, 0) as subject_id,
    COALESCE(s1.section_name, s2.section_name, 'Unknown Section') as section_name,
    el.date_absent,
    '00:00:00' as time_in,
    'excused' as status,
    'Automatically marked as excused due to approved excuse letter' as notes,
    el.teacher_id,
    NOW() as created_at,
    NOW() as updated_at
FROM excuse_letters el
LEFT JOIN attendance a ON 
    el.student_id = a.student_id 
    AND el.class_id = a.class_id 
    AND el.date_absent = a.date
LEFT JOIN classes c ON el.class_id = c.class_id
LEFT JOIN sections s1 ON c.section_id = s1.section_id
LEFT JOIN classrooms cr ON el.class_id = cr.id
LEFT JOIN sections s2 ON cr.section_id = s2.section_id
WHERE a.attendance_id IS NULL 
AND el.status = 'approved';

-- Step 4: Create missing attendance records for rejected excuse letters
INSERT INTO attendance (
    student_id, 
    class_id, 
    subject_id, 
    section_name, 
    date, 
    time_in, 
    status, 
    notes, 
    teacher_id, 
    created_at, 
    updated_at
)
SELECT 
    el.student_id,
    el.class_id,
    COALESCE(c.subject_id, cr.subject_id, 0) as subject_id,
    COALESCE(s1.section_name, s2.section_name, 'Unknown Section') as section_name,
    el.date_absent,
    '00:00:00' as time_in,
    'absent' as status,
    'Automatically marked as absent due to rejected excuse letter' as notes,
    el.teacher_id,
    NOW() as created_at,
    NOW() as updated_at
FROM excuse_letters el
LEFT JOIN attendance a ON 
    el.student_id = a.student_id 
    AND el.class_id = a.class_id 
    AND el.date_absent = a.date
LEFT JOIN classes c ON el.class_id = c.class_id
LEFT JOIN sections s1 ON c.section_id = s1.section_id
LEFT JOIN classrooms cr ON el.class_id = cr.id
LEFT JOIN sections s2 ON cr.section_id = s2.section_id
WHERE a.attendance_id IS NULL 
AND el.status = 'rejected';

-- Step 5: Create missing attendance records for pending excuse letters
INSERT INTO attendance (
    student_id, 
    class_id, 
    subject_id, 
    section_name, 
    date, 
    time_in, 
    status, 
    notes, 
    teacher_id, 
    created_at, 
    updated_at
)
SELECT 
    el.student_id,
    el.class_id,
    COALESCE(c.subject_id, cr.subject_id, 0) as subject_id,
    COALESCE(s1.section_name, s2.section_name, 'Unknown Section') as section_name,
    el.date_absent,
    '00:00:00' as time_in,
    'absent' as status,
    'Automatically marked as absent due to pending excuse letter' as notes,
    el.teacher_id,
    NOW() as created_at,
    NOW() as updated_at
FROM excuse_letters el
LEFT JOIN attendance a ON 
    el.student_id = a.student_id 
    AND el.class_id = a.class_id 
    AND el.date_absent = a.date
LEFT JOIN classes c ON el.class_id = c.class_id
LEFT JOIN sections s1 ON c.section_id = s1.section_id
LEFT JOIN classrooms cr ON el.class_id = cr.id
LEFT JOIN sections s2 ON cr.section_id = s2.section_id
WHERE a.attendance_id IS NULL 
AND el.status = 'pending';

-- Step 6: Verify all excuse letters now have attendance records
SELECT 
    'Verification - All excuse letters should have attendance records:' as info,
    COUNT(*) as total_excuse_letters,
    COUNT(a.attendance_id) as total_with_attendance,
    CASE 
        WHEN COUNT(*) = COUNT(a.attendance_id) THEN '✅ SUCCESS'
        ELSE '❌ STILL MISSING'
    END as status
FROM excuse_letters el
LEFT JOIN attendance a ON 
    el.student_id = a.student_id 
    AND el.class_id = a.class_id 
    AND el.date_absent = a.date;

-- Step 7: Show final status breakdown
SELECT 
    'Final status breakdown:' as info,
    el.status as excuse_status,
    COUNT(*) as count
FROM excuse_letters el
JOIN attendance a ON 
    el.student_id = a.student_id 
    AND el.class_id = a.class_id 
    AND el.date_absent = a.date
GROUP BY el.status
ORDER BY el.status;

-- Step 8: Check for any remaining null section_name values
SELECT 
    'Checking for null section_name values:' as info,
    COUNT(*) as null_count
FROM attendance 
WHERE section_name IS NULL OR section_name = '';

-- Step 9: Show examples of any remaining null section_name records
SELECT 
    attendance_id,
    student_id,
    class_id,
    section_name,
    date,
    status,
    notes
FROM attendance 
WHERE section_name IS NULL OR section_name = ''
LIMIT 10;

-- Step 10: Update any remaining null section_name values
UPDATE attendance a
LEFT JOIN classes c ON a.class_id = c.class_id
LEFT JOIN sections s1 ON c.section_id = s1.section_id
LEFT JOIN classrooms cr ON a.class_id = cr.id
LEFT JOIN sections s2 ON cr.section_id = s2.section_id
SET a.section_name = COALESCE(s1.section_name, s2.section_name, 'Unknown Section')
WHERE a.section_name IS NULL OR a.section_name = '';

-- Step 11: Final verification - no null section_name values
SELECT 
    'Final verification - section_name should not be null:' as info,
    COUNT(*) as total_records,
    COUNT(CASE WHEN section_name IS NULL OR section_name = '' THEN 1 END) as null_count,
    CASE 
        WHEN COUNT(CASE WHEN section_name IS NULL OR section_name = '' THEN 1 END) = 0 THEN '✅ SUCCESS'
        ELSE '❌ STILL HAS NULL VALUES'
    END as status
FROM attendance;
