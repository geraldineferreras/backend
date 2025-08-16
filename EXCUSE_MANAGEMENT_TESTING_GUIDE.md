# Excuse Management Testing Guide

## Overview
This guide provides comprehensive testing steps to verify that the excuse management system properly integrates with the attendance system. When teachers approve or reject excuse letters, the system should automatically update attendance records in the `attendance` table.

## What Was Fixed

### 1. Section Name Null Issue
- **Problem**: Some attendance records had `NULL` values in the `section_name` field
- **Solution**: Enhanced the excuse letter controller to ensure `section_name` is always populated
- **Implementation**: Added fallback logic to retrieve section names from multiple sources

### 2. Automatic Attendance Updates
- **Problem**: Excuse letter approval/rejection didn't automatically update attendance
- **Solution**: Integrated excuse letter processing with attendance record creation/updates
- **Implementation**: Modified `ExcuseLetterController` to call attendance update methods

## Testing Scenarios

### Scenario 1: Teacher Approves Excuse Letter

#### Prerequisites
- Student has submitted an excuse letter
- Excuse letter status is "pending"
- Teacher is logged in and has access to the class

#### Test Steps
1. **Login as Teacher**
   ```
   POST /api/auth/login
   {
     "email": "teacher@example.com",
     "password": "password123"
   }
   ```

2. **View Pending Excuse Letters**
   ```
   GET /api/excuse-letters/teacher
   Authorization: Bearer {teacher_token}
   ```

3. **Approve Excuse Letter**
   ```
   PUT /api/excuse-letters/update/{letter_id}
   Authorization: Bearer {teacher_token}
   {
     "status": "approved",
     "teacher_notes": "Documentation verified"
   }
   ```

4. **Verify Attendance Record Created/Updated**
   ```
   GET /api/attendance/records/{class_id}/{date}
   Authorization: Bearer {teacher_token}
   ```

#### Expected Results
- Excuse letter status changes to "approved"
- New attendance record is created (or existing one updated)
- Attendance status is "excused"
- `section_name` field is populated (not null)
- Notes contain: "Automatically marked as excused due to approved excuse letter"

### Scenario 2: Teacher Rejects Excuse Letter

#### Test Steps
1. **Reject Excuse Letter**
   ```
   PUT /api/excuse-letters/update/{letter_id}
   Authorization: Bearer {teacher_token}
   {
     "status": "rejected",
     "teacher_notes": "Insufficient documentation"
   }
   ```

2. **Verify Attendance Record**
   ```
   GET /api/attendance/records/{class_id}/{date}
   Authorization: Bearer {teacher_token}
   ```

#### Expected Results
- Excuse letter status changes to "rejected"
- New attendance record is created (or existing one updated)
- Attendance status is "absent"
- `section_name` field is populated (not null)
- Notes contain: "Automatically marked as absent due to rejected excuse letter"

### Scenario 3: Bulk Sync Excuse Letters

#### Test Steps
1. **Sync All Excuse Letters for a Date**
   ```
   POST /api/attendance/sync-excuse-letters
   Authorization: Bearer {teacher_token}
   {
     "classroom_id": "123",
     "date": "2025-08-12"
   }
   ```

2. **Verify All Records Updated**
   ```
   GET /api/attendance/records/{class_id}/{date}
   Authorization: Bearer {teacher_token}
   ```

#### Expected Results
- All pending excuse letters are processed
- Attendance records are created/updated accordingly
- No `section_name` fields are null

## Database Verification

### Check Attendance Table Structure
```sql
DESCRIBE attendance;
```

**Expected Columns:**
- `attendance_id` (INT, AUTO_INCREMENT, PRIMARY KEY)
- `student_id` (VARCHAR(50), NOT NULL)
- `class_id` (VARCHAR(50), NOT NULL)
- `subject_id` (INT, NOT NULL)
- `section_name` (VARCHAR(100), NOT NULL) ← **This should NOT be null**
- `date` (DATE, NOT NULL)
- `time_in` (TIME)
- `status` (ENUM: 'present', 'late', 'absent', 'excused')
- `notes` (TEXT)
- `teacher_id` (VARCHAR(50), NOT NULL)
- `created_at` (TIMESTAMP)
- `updated_at` (TIMESTAMP)

### Verify No Null Section Names
```sql
SELECT COUNT(*) as null_section_count 
FROM attendance 
WHERE section_name IS NULL OR section_name = '';
```

**Expected Result:** `null_section_count = 0`

### Check Excuse Letter Integration
```sql
SELECT 
    a.attendance_id,
    a.student_id,
    a.section_name,
    a.status,
    a.notes,
    el.status as excuse_status,
    el.reason
FROM attendance a
LEFT JOIN excuse_letters el ON 
    a.student_id = el.student_id 
    AND a.class_id = el.class_id 
    AND a.date = el.date_absent
WHERE a.notes LIKE '%excuse letter%'
ORDER BY a.date DESC, a.student_id;
```

## API Endpoints to Test

### Excuse Letter Management
- `POST /api/excuse-letters/submit` - Student submits excuse letter
- `GET /api/excuse-letters/teacher` - Teacher views excuse letters
- `PUT /api/excuse-letters/update/{letter_id}` - Teacher approves/rejects
- `DELETE /api/excuse-letters/delete/{letter_id}` - Student deletes pending letter

### Attendance Management
- `GET /api/attendance/records/{class_id}/{date}` - View attendance records
- `POST /api/attendance/sync-excuse-letters` - Sync excuse letters with attendance
- `GET /api/attendance/excuse-letter-status/{student_id}/{date}` - Check excuse status

## Error Handling Tests

### Test 1: Invalid Excuse Letter ID
```
PUT /api/excuse-letters/update/999999
Authorization: Bearer {teacher_token}
{
  "status": "approved"
}
```

**Expected Result:** 404 error with message "Excuse letter not found or access denied"

### Test 2: Invalid Status
```
PUT /api/excuse-letters/update/{letter_id}
Authorization: Bearer {teacher_token}
{
  "status": "invalid_status"
}
```

**Expected Result:** 400 error with message "Invalid status. Must be: pending, approved, or rejected"

### Test 3: Unauthorized Access
```
PUT /api/excuse-letters/update/{letter_id}
Authorization: Bearer {student_token}
{
  "status": "approved"
}
```

**Expected Result:** 403 error with message "Access denied"

## Performance Testing

### Test 1: Bulk Excuse Letter Processing
1. Create 100+ excuse letters for the same date
2. Approve/reject them in batches
3. Monitor database performance and response times

### Test 2: Concurrent Updates
1. Have multiple teachers process excuse letters simultaneously
2. Verify no data corruption or duplicate records
3. Check database locks and transaction handling

## Monitoring and Logging

### Check Application Logs
```bash
tail -f application/logs/log-*.php
```

**Look for:**
- "Attendance marked as excused for student..."
- "Attendance marked as absent for student..."
- "Using default section name for student..." (warnings)
- Any error messages related to attendance updates

### Database Query Monitoring
Enable slow query log to identify any performance issues:
```sql
SET GLOBAL slow_query_log = 'ON';
SET GLOBAL long_query_time = 1;
```

## Troubleshooting

### Common Issues

#### Issue 1: Section Name Still Null
**Symptoms:** Attendance records created with null section_name
**Solution:** Check if sections table has data and verify the JOIN logic

#### Issue 2: Attendance Not Created
**Symptoms:** Excuse letter approved but no attendance record
**Solution:** Check database permissions and verify the class lookup logic

#### Issue 3: Performance Issues
**Symptoms:** Slow response when processing multiple excuse letters
**Solution:** Add database indexes and optimize JOIN queries

### Debug Commands

#### Check Excuse Letter Status
```sql
SELECT * FROM excuse_letters WHERE letter_id = {letter_id};
```

#### Check Attendance Record
```sql
SELECT * FROM attendance WHERE student_id = '{student_id}' AND date = '{date}';
```

#### Check Class Information
```sql
SELECT c.*, s.section_name, sub.subject_name 
FROM classes c 
JOIN sections s ON c.section_id = s.section_id 
JOIN subjects sub ON c.subject_id = sub.id 
WHERE c.class_id = '{class_id}';
```

## Success Criteria

✅ **All attendance records have non-null section_name values**
✅ **Excuse letter approval automatically creates "excused" attendance**
✅ **Excuse letter rejection automatically creates "absent" attendance**
✅ **Existing attendance records are properly updated**
✅ **No duplicate attendance records are created**
✅ **Performance is acceptable under normal load**
✅ **Error handling works correctly for edge cases**

## Next Steps

After successful testing:
1. Monitor the system in production for any issues
2. Collect feedback from teachers and students
3. Consider adding additional features like:
   - Bulk excuse letter processing
   - Email notifications for status changes
   - Attendance report generation
   - Historical excuse letter tracking
