# Task Grading Notification Testing Guide

## Overview
This guide explains how to test the task grading notification system. Students will now receive notifications (both in-app and email) when teachers grade their task submissions.

## What Was Implemented

### 1. Notification Logic Added
- **File**: `application/controllers/api/TaskController.php`
- **Method**: `grade_submission_post()` - Added notification call after successful grading
- **New Method**: `send_grade_notification()` - Handles student notification creation

### 2. Notification Details
- **Type**: `grade`
- **Title**: "Task Graded: [Task Title]"
- **Message**: "Your submission for '[Task Title]' has been graded by [Teacher Name]. Grade: [Grade] (Class: [Class Name])"
- **Feedback**: Included if provided by teacher
- **Related ID**: Submission ID
- **Related Type**: `grade`
- **Class Code**: The class where the task was submitted

## Testing Steps

### Step 1: Prepare Test Data
1. **Student Account**: Ensure you have a valid student account with JWT token
2. **Teacher Account**: Ensure you have a valid teacher account with JWT token
3. **Task**: Use an existing task or create a new one
4. **Submission**: Make sure a student has submitted the task
5. **Submission ID**: Note the submission ID to grade

### Step 2: Grade a Task Submission as Teacher
Use Postman or the test script to grade a task submission:

**Endpoint**: `POST {{base_url}}/api/tasks/submissions/{submission_id}/grade`

**Headers**:
```
Content-Type: application/json
Authorization: Bearer [TEACHER_JWT_TOKEN]
```

**Body**:
```json
{
    "grade": 85,
    "feedback": "This is a test grade with feedback to verify student notifications."
}
```

### Step 3: Verify Student Notification
After successful grading, check:

#### A. In-App Notifications
1. Login as the student
2. Go to the notifications panel
3. Look for a new notification with:
   - **Icon**: 📊 (grade icon)
   - **Type**: Grade
   - **Title**: "Task Graded: [Task Title]"
   - **Message**: "Your submission for '[Task Title]' has been graded by [Teacher Name]. Grade: [Grade] (Class: [Class Name])"
   - **Feedback**: Should include teacher's feedback if provided

#### B. Email Notifications
1. Check the student's email inbox
2. Look for an email with:
   - **Subject**: "Task Graded: [Task Title]"
   - **Content**: Should include the grade, teacher name, task title, class name, and feedback
   - **From**: Your configured Gmail account

#### C. Database Verification
Check the `notifications` table:
```sql
SELECT * FROM notifications 
WHERE type = 'grade' 
AND related_type = 'grade' 
ORDER BY created_at DESC 
LIMIT 1;
```

## Postman Testing

### 1. Submit Task (Student)
```
POST {{base_url}}/api/tasks/{task_id}/submit
Headers:
  Content-Type: application/json
  Authorization: Bearer [STUDENT_JWT_TOKEN]

Body:
{
    "submission_content": "This is my test submission to verify grading notifications.",
    "class_code": "J56NHD",
    "attachment_type": "link",
    "attachment_url": "https://drive.google.com/file/d/test-submission/view"
}
```

### 2. Grade Task Submission (Teacher)
```
POST {{base_url}}/api/tasks/submissions/{submission_id}/grade
Headers:
  Content-Type: application/json
  Authorization: Bearer [TEACHER_JWT_TOKEN]

Body:
{
    "grade": 85,
    "feedback": "This is a test grade with feedback to verify student notifications."
}
```

### 3. Check Student Notifications
```
GET {{base_url}}/api/notifications
Headers:
  Content-Type: application/json
  Authorization: Bearer [STUDENT_JWT_TOKEN]
```

## Expected Results

### Successful Grading Response
```json
{
    "status": true,
    "message": "Submission graded successfully"
}
```

### Student Notification (In-App)
```json
{
    "id": 126,
    "type": "grade",
    "type_display": "Grade",
    "icon": "📊",
    "title": "Task Graded: Research Paper Assignment",
    "message": "Your submission for 'Research Paper Assignment' has been graded by Joel Quiambao. Grade: 85 (Class: Advanced OOP)\n\nFeedback: This is a test grade with feedback to verify student notifications.",
    "related_id": 5,
    "related_type": "grade",
    "class_code": "J56NHD",
    "is_read": false,
    "is_urgent": false,
    "created_at": "2025-01-07 12:30:00",
    "updated_at": "2025-01-07 12:30:00"
}
```

### Student Email Notification
**Subject**: Task Graded: Research Paper Assignment

**Body**: HTML email with grade, teacher name, task title, class name, and feedback

## Troubleshooting

### Issue 1: No Student Notification
**Possible Causes**:
- Student ID not found in submission data
- Notification helper not loaded
- Database error in notification creation

**Solution**:
1. Check submission data has valid `student_id`
2. Verify notification helper is loaded in TaskController
3. Check application logs for errors

### Issue 2: No Email Notification
**Possible Causes**:
- Email configuration issues
- Student's email notification settings disabled
- SMTP connection problems

**Solution**:
1. Test email configuration with `test_gmail_email.php`
2. Check student's notification settings
3. Verify SMTP credentials

### Issue 3: Missing Task Information
**Possible Causes**:
- Task not found in database
- Teacher information not available

**Solution**:
1. Verify task exists in database
2. Check teacher user record exists
3. Ensure proper database relationships

## Code Changes Summary

### TaskController.php Changes
1. **Added notification call** in `grade_submission_post()` method
2. **Added `send_grade_notification()`** method
3. **Uses existing notification helpers** for consistency

### Notification Details
- **Helper Function**: `create_grade_notification()`
- **Email Template**: Uses grade-specific template
- **Database**: Stores in `notifications` table with `type = 'grade'`

## Testing Checklist

- [ ] Teacher can grade task submission successfully
- [ ] Student receives in-app notification
- [ ] Student receives email notification
- [ ] Notification contains correct task title
- [ ] Notification contains correct teacher name
- [ ] Notification contains correct grade
- [ ] Notification contains correct class name
- [ ] Notification includes feedback if provided
- [ ] Notification appears in database
- [ ] No duplicate notifications created
- [ ] Error handling works properly

## Next Steps

1. **Test with real data** using student and teacher accounts
2. **Verify email delivery** to student's inbox
3. **Check notification settings** for students
4. **Monitor application logs** for any errors
5. **Test edge cases** (invalid grades, missing feedback, etc.)

## Test Script

Use the provided `test_task_grade_notification.php` script to test the functionality:

```bash
php test_task_grade_notification.php
```

Make sure to:
1. Replace `YOUR_TEACHER_TOKEN_HERE` with a valid teacher JWT token
2. Update the `submission_id` to match an actual submission in your database
3. Ensure the submission is in 'submitted' status
4. Check that the notification system is properly configured

## Bulk Grading Notifications

The system also supports bulk grading notifications. When using the bulk grading endpoint:

**Endpoint**: `POST {{base_url}}/api/tasks/{task_id}/bulk-grade`

**Body**:
```json
{
    "grades": [
        {
            "submission_id": 5,
            "grade": 85,
            "feedback": "Good work"
        },
        {
            "submission_id": 6,
            "grade": 90,
            "feedback": "Excellent submission"
        }
    ]
}
```

Each student will receive individual notifications for their graded submissions.
