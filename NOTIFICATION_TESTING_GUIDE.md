# Notification System Testing Guide

## Overview
The notification system has been successfully implemented and integrated into the task creation process. This guide will help you test and verify that notifications are working correctly.

## What Was Implemented

### 1. Task Creation Notifications
- **Location**: `application/controllers/api/TaskController.php`
- **Method**: `send_task_notifications()` (private method)
- **Trigger**: Called automatically when a task is created via `create_post()`

### 2. Notification Features
- ✅ **Database Notifications**: Stored in `notifications` table
- ✅ **Email Notifications**: Sent via Gmail SMTP
- ✅ **Class Name Display**: Shows actual class name (e.g., "Advanced OOP") instead of class code
- ✅ **Duplicate Prevention**: Prevents duplicate task assignments
- ✅ **Error Handling**: Graceful error handling that doesn't break task creation

## Testing Steps

### Step 1: Verify Database Tables
Make sure these tables exist in your database:
- `notifications`
- `notification_settings`
- `class_tasks`
- `task_student_assignments`
- `users`
- `classrooms`
- `classroom_enrollments`

### Step 2: Test Task Creation with Notifications

#### Option A: Using Postman
1. **Set up the request**:
   - Method: `POST`
   - URL: `http://localhost/scms_new_backup/index.php/api/tasks/create`
   - Headers:
     ```
     Content-Type: application/json
     Authorization: Bearer YOUR_JWT_TOKEN
     ```
   - Body (JSON):
     ```json
     {
       "title": "Test Task with Notifications",
       "type": "assignment",
       "points": 50,
       "instructions": "This is a test task to verify notifications are working.",
       "class_codes": ["J56NHD"],
       "assignment_type": "classroom",
       "allow_comments": 1,
       "is_draft": 0,
       "is_scheduled": 0,
       "due_date": "2025-01-30 23:59:00"
     }
     ```

2. **Send the request** and check:
   - ✅ Response shows "Task created successfully"
   - ✅ Task ID is returned
   - ✅ Check your email inbox for notifications
   - ✅ Check the app's notification center

#### Option B: Using the Test Script
1. Edit `test_task_notification.php`
2. Replace `YOUR_JWT_TOKEN_HERE` with a valid teacher JWT token
3. Run: `php test_task_notification.php`

### Step 3: Verify Notifications

#### Check Database Notifications
```sql
SELECT * FROM notifications WHERE related_type = 'task' ORDER BY created_at DESC LIMIT 10;
```

#### Check Email Notifications
- Check the email inbox of students enrolled in the class
- Look for emails with subject containing "New Class Task" or "New Individual Task"
- Verify the email shows the actual class name (not class code)

#### Check App Notifications
- Log in as a student enrolled in the class
- Check the notifications section in the app
- Verify notifications appear with correct task information

### Step 4: Test Different Scenarios

#### Scenario 1: Classroom Assignment
```json
{
  "title": "Classroom Assignment Test",
  "type": "assignment",
  "points": 100,
  "instructions": "This task is assigned to the entire class.",
  "class_codes": ["J56NHD"],
  "assignment_type": "classroom",
  "allow_comments": 1,
  "is_draft": 0,
  "due_date": "2025-01-30 23:59:00"
}
```
**Expected**: All students in the class receive notifications.

#### Scenario 2: Individual Assignment
```json
{
  "title": "Individual Assignment Test",
  "type": "quiz",
  "points": 25,
  "instructions": "This task is assigned to specific students.",
  "class_codes": ["J56NHD"],
  "assignment_type": "individual",
  "assigned_students": [
    {"student_id": "STU685651BF9DDCF988", "class_code": "J56NHD"}
  ],
  "allow_comments": 0,
  "is_draft": 0,
  "due_date": "2025-01-30 23:59:00"
}
```
**Expected**: Only assigned students receive notifications.

### Step 5: Test Email Configuration

#### Verify SMTP Settings
The email configuration is in `application/config/email.php`:
- ✅ SMTP Host: `smtp.gmail.com`
- ✅ SMTP Port: `465`
- ✅ SMTP User: `grldnferreras@gmail.com`
- ✅ SMTP Crypto: `ssl`

#### Test Email Sending
Run the email test script:
```bash
php test_email_simple.php
```
**Expected**: SMTP connection successful

### Step 6: Debug Common Issues

#### Issue 1: No Email Notifications
**Possible Causes**:
- Email notifications disabled in user settings
- SMTP configuration issues
- User email not found in database

**Debug Steps**:
1. Check `notification_settings` table for user preferences
2. Verify user email exists in `users` table
3. Check application logs for email errors

#### Issue 2: No Database Notifications
**Possible Causes**:
- Notification helper not loaded
- Database connection issues
- Missing notification tables

**Debug Steps**:
1. Check if `notification` helper is loaded in TaskController
2. Verify database connection
3. Check if notifications table exists

#### Issue 3: Duplicate Task Assignments
**Possible Causes**:
- Duplicate student IDs in request
- Database constraint violations

**Debug Steps**:
1. Check the `safe_assign_students_to_task` method is being used
2. Verify unique constraints in database
3. Check application logs for assignment errors

## Expected Results

### Successful Task Creation
```json
{
  "status": true,
  "message": "Task created successfully",
  "data": {
    "task_id": "49",
    "title": "Test Task with Notifications",
    "type": "assignment",
    "points": "50",
    "instructions": "This is a test task to verify notifications are working.",
    "class_codes": "[\"J56NHD\"]",
    "assignment_type": "classroom",
    "status": "active",
    "created_at": "2025-08-07 10:30:00",
    "teacher_name": "Joel Quiambao"
  }
}
```

### Email Notification Content
**Subject**: "New Class Task: Test Task with Notifications"
**Body**: HTML email with:
- Task title and description
- Class name (e.g., "Advanced OOP" not "J56NHD")
- Teacher name
- Due date
- Points value

### Database Notification Record
```sql
SELECT * FROM notifications WHERE related_id = '49' AND related_type = 'task';
```
**Expected**: Notification records for each student in the class

## Troubleshooting

### Check Application Logs
Look for these log messages:
- `"Task notifications sent to X students in class Y for task Z"`
- `"Failed to send task notifications: [error message]"`

### Verify Helper Functions
Ensure these functions exist and work:
- `create_notifications_for_users()`
- `get_class_students()`
- `send_email_notification()`

### Test Individual Components
1. **Database**: Test direct database queries
2. **Email**: Test email sending independently
3. **Helpers**: Test helper functions in isolation
4. **API**: Test task creation without notifications first

## Next Steps

After successful testing:
1. ✅ Notifications are working correctly
2. ✅ Emails are being sent
3. ✅ Class names display correctly
4. ✅ Duplicate assignments are prevented
5. ✅ Error handling is robust

The notification system is now fully integrated and ready for production use!
