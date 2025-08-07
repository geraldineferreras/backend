# Teacher Submission Notification Testing Guide

## Overview
This guide explains how to test the teacher notification system when students submit tasks. Teachers will now receive notifications (both in-app and email) when their students submit tasks.

## What Was Implemented

### 1. Notification Logic Added
- **File**: `application/controllers/api/TaskController.php`
- **Method**: `submit_post()` - Added notification call after successful submission
- **New Method**: `send_submission_notification()` - Handles teacher notification creation

### 2. Notification Details
- **Type**: `submission`
- **Title**: "New Task Submission: [Task Title]"
- **Message**: "[Student Name] has submitted the task '[Task Title]' for class [Class Name]"
- **Related ID**: Submission ID
- **Related Type**: `submission`
- **Class Code**: The class where the submission was made

## Testing Steps

### Step 1: Prepare Test Data
1. **Student Account**: Ensure you have a valid student account with JWT token
2. **Teacher Account**: Ensure you have the teacher's email to check for notifications
3. **Task**: Use an existing task or create a new one
4. **Class**: Make sure the student is enrolled in the class

### Step 2: Submit a Task as Student
Use Postman or the test script to submit a task:

**Endpoint**: `POST {{base_url}}/api/tasks/{task_id}/submit`

**Headers**:
```
Content-Type: application/json
Authorization: Bearer [STUDENT_JWT_TOKEN]
```

**Body**:
```json
{
    "submission_content": "This is my test submission to verify teacher notifications.",
    "class_code": "J56NHD",
    "attachment_type": "link",
    "attachment_url": "https://drive.google.com/file/d/test-submission/view"
}
```

### Step 3: Verify Teacher Notification
After successful submission, check:

#### A. In-App Notifications
1. Login as the teacher
2. Go to the notifications panel
3. Look for a new notification with:
   - **Icon**: 📤 (submission icon)
   - **Type**: Submission
   - **Title**: "New Task Submission: [Task Title]"
   - **Message**: "[Student Name] has submitted the task '[Task Title]' for class [Class Name]"

#### B. Email Notifications
1. Check the teacher's email inbox
2. Look for an email with:
   - **Subject**: "New Task Submission: [Task Title]"
   - **Content**: Should include the student name, task title, and class name
   - **From**: Your configured Gmail account

#### C. Database Verification
Check the `notifications` table:
```sql
SELECT * FROM notifications 
WHERE type = 'submission' 
AND related_type = 'submission' 
ORDER BY created_at DESC 
LIMIT 1;
```

## Postman Testing

### 1. Create Task (Teacher)
```
POST {{base_url}}/api/tasks/create
Headers:
  Content-Type: application/json
  Authorization: Bearer [TEACHER_JWT_TOKEN]

Body:
{
    "title": "Test Task for Submission Notifications",
    "type": "assignment",
    "points": 50,
    "instructions": "Submit this task to test teacher notifications",
    "class_codes": ["J56NHD"],
    "assignment_type": "classroom",
    "allow_comments": 1,
    "is_draft": 0,
    "is_scheduled": 0,
    "due_date": "2025-01-30 23:59:00"
}
```

### 2. Submit Task (Student)
```
POST {{base_url}}/api/tasks/{task_id}/submit
Headers:
  Content-Type: application/json
  Authorization: Bearer [STUDENT_JWT_TOKEN]

Body:
{
    "submission_content": "This is my submission to test teacher notifications.",
    "class_code": "J56NHD",
    "attachment_type": "link",
    "attachment_url": "https://drive.google.com/file/d/test-submission/view"
}
```

### 3. Check Teacher Notifications
```
GET {{base_url}}/api/notifications
Headers:
  Content-Type: application/json
  Authorization: Bearer [TEACHER_JWT_TOKEN]
```

## Expected Results

### Successful Submission Response
```json
{
    "status": true,
    "message": "Task submitted successfully",
    "data": {
        "submission_id": 6
    }
}
```

### Teacher Notification (In-App)
```json
{
    "id": 123,
    "type": "submission",
    "type_display": "Submission",
    "icon": "📤",
    "title": "New Task Submission: Test Task for Submission Notifications",
    "message": "John Doe has submitted the task 'Test Task for Submission Notifications' for class Advanced OOP",
    "related_id": 6,
    "related_type": "submission",
    "class_code": "J56NHD",
    "is_read": false,
    "is_urgent": false,
    "created_at": "2025-01-07 10:30:00",
    "updated_at": "2025-01-07 10:30:00"
}
```

### Teacher Email Notification
**Subject**: New Task Submission: Test Task for Submission Notifications

**Body**: HTML email with student name, task title, and class name

## Troubleshooting

### Issue 1: No Teacher Notification
**Possible Causes**:
- Teacher ID not found in task data
- Notification helper not loaded
- Database error in notification creation

**Solution**:
1. Check task data has valid `teacher_id`
2. Verify notification helper is loaded in TaskController
3. Check application logs for errors

### Issue 2: No Email Notification
**Possible Causes**:
- Email configuration issues
- Teacher's email notification settings disabled
- SMTP connection problems

**Solution**:
1. Test email configuration with `test_gmail_email.php`
2. Check teacher's notification settings
3. Verify SMTP credentials

### Issue 3: Duplicate Notifications
**Possible Causes**:
- Multiple submission attempts
- Race conditions

**Solution**:
1. Check for existing submissions before creating new ones
2. Implement proper error handling

## Code Changes Summary

### TaskController.php Changes
1. **Added notification call** in `submit_post()` method
2. **Added `send_submission_notification()`** method
3. **Uses existing notification helpers** for consistency

### Notification Details
- **Helper Function**: `create_submission_notification()`
- **Email Template**: Uses submission-specific template
- **Database**: Stores in `notifications` table with `type = 'submission'`

## Testing Checklist

- [ ] Student can submit task successfully
- [ ] Teacher receives in-app notification
- [ ] Teacher receives email notification
- [ ] Notification contains correct student name
- [ ] Notification contains correct task title
- [ ] Notification contains correct class name
- [ ] Notification appears in database
- [ ] No duplicate notifications created
- [ ] Error handling works properly

## Next Steps

1. **Test with real data** using student and teacher accounts
2. **Verify email delivery** to teacher's inbox
3. **Check notification settings** for teachers
4. **Monitor application logs** for any errors
5. **Test edge cases** (multiple submissions, invalid data, etc.)
