# Excuse Letter Notification Testing Guide

## Overview
This guide explains how to test the excuse letter notification system. Teachers will now receive notifications (both in-app and email) when their students submit excuse letters.

## What Was Implemented

### 1. Notification Logic Added
- **File**: `application/controllers/api/ExcuseLetterController.php`
- **Method**: `submit_post()` - Added notification call after successful submission
- **New Method**: `send_excuse_letter_notification()` - Handles teacher notification creation
- **Method**: `update_put()` - Added notification call after status update
- **New Method**: `send_excuse_letter_status_notification()` - Handles student notification creation

### 2. Notification Details

#### A. Teacher Notifications (When Student Submits)
- **Type**: `excuse_letter`
- **Title**: "New Excuse Letter: [Subject Name]"
- **Message**: "[Student Name] has submitted an excuse letter for [Subject Name] ([Section Name]) - Date: [Date]"
- **Related ID**: Letter ID
- **Related Type**: `excuse_letter`

#### B. Student Notifications (When Teacher Updates Status)
- **Type**: `excuse_letter`
- **Title**: "Excuse Letter [Status]: [Subject Name]"
- **Message**: "Your excuse letter for [Subject Name] ([Section Name]) has been [status]. Date: [Date]"
- **Teacher Notes**: Included if provided by teacher
- **Related ID**: Letter ID
- **Related Type**: `excuse_letter`

## Testing Steps

### Step 1: Prepare Test Data
1. **Student Account**: Ensure you have a valid student account with JWT token
2. **Teacher Account**: Ensure you have the teacher's email to check for notifications
3. **Class**: Make sure the student is enrolled in the class
4. **Date**: Use a date that doesn't already have an excuse letter submitted

### Step 2: Submit an Excuse Letter as Student
Use Postman or the test script to submit an excuse letter:

**Endpoint**: `POST {{base_url}}/api/excuse-letters/submit`

**Headers**:
```
Content-Type: application/json
Authorization: Bearer [STUDENT_JWT_TOKEN]
```

**Body**:
```json
{
    "class_id": "5",
    "date_absent": "2025-01-07",
    "reason": "This is a test excuse letter to verify teacher notifications."
}
```

### Step 3: Verify Teacher Notification
After successful submission, check:

#### A. In-App Notifications
1. Login as the teacher
2. Go to the notifications panel
3. Look for a new notification with:
   - **Icon**: 📄 (excuse letter icon)
   - **Type**: Excuse Letter
   - **Title**: "New Excuse Letter: [Subject Name]"
   - **Message**: "[Student Name] has submitted an excuse letter for [Subject Name] ([Section Name]) - Date: [Date]"

#### B. Email Notifications
1. Check the teacher's email inbox
2. Look for an email with:
   - **Subject**: "New Excuse Letter: [Subject Name]"
   - **Content**: Should include the student name, subject name, section name, and date
   - **From**: Your configured Gmail account

#### C. Database Verification
Check the `notifications` table:
```sql
SELECT * FROM notifications 
WHERE type = 'excuse_letter' 
AND related_type = 'excuse_letter' 
ORDER BY created_at DESC 
LIMIT 1;
```

### Step 4: Test Status Update Notifications
After teacher approves/rejects the excuse letter:

#### A. Update Excuse Letter Status (Teacher)
Use Postman or the test script to update an excuse letter:

**Endpoint**: `PUT {{base_url}}/api/excuse-letters/update/{letter_id}`

**Headers**:
```
Content-Type: application/json
Authorization: Bearer [TEACHER_JWT_TOKEN]
```

**Body**:
```json
{
    "status": "approved",
    "teacher_notes": "This is a test approval with teacher notes."
}
```

#### B. Verify Student Notification
After successful status update, check:

1. **Student In-App Notifications**:
   - Login as the student
   - Go to the notifications panel
   - Look for a new notification with:
     - **Icon**: 📄 (excuse letter icon)
     - **Type**: Excuse Letter
     - **Title**: "Excuse Letter Approved: [Subject Name]" or "Excuse Letter Rejected: [Subject Name]"
     - **Message**: "Your excuse letter for [Subject Name] ([Section Name]) has been approved/rejected. Date: [Date]"

2. **Student Email Notifications**:
   - Check the student's email inbox
   - Look for an email with:
     - **Subject**: "Excuse Letter Approved: [Subject Name]" or "Excuse Letter Rejected: [Subject Name]"
     - **Content**: Should include the status, subject name, section name, date, and teacher notes
     - **From**: Your configured Gmail account

## Postman Testing

### 1. Submit Excuse Letter (Student)
```
POST {{base_url}}/api/excuse-letters/submit
Headers:
  Content-Type: application/json
  Authorization: Bearer [STUDENT_JWT_TOKEN]

Body:
{
    "class_id": "5",
    "date_absent": "2025-01-07",
    "reason": "This is a test excuse letter to verify teacher notifications."
}
```

### 2. Check Teacher Notifications
```
GET {{base_url}}/api/notifications
Headers:
  Content-Type: application/json
  Authorization: Bearer [TEACHER_JWT_TOKEN]
```

### 3. Update Excuse Letter Status (Teacher)
```
PUT {{base_url}}/api/excuse-letters/update/{letter_id}
Headers:
  Content-Type: application/json
  Authorization: Bearer [TEACHER_JWT_TOKEN]

Body:
{
    "status": "approved",
    "teacher_notes": "This is a test approval with teacher notes."
}
```

### 4. Check Student Notifications
```
GET {{base_url}}/api/notifications
Headers:
  Content-Type: application/json
  Authorization: Bearer [STUDENT_JWT_TOKEN]
```

## Expected Results

### Successful Submission Response
```json
{
    "status": true,
    "message": "Excuse letter submitted successfully",
    "data": {
        "letter_id": "15",
        "student_id": "STU685651BF9DDCF988",
        "class_id": "5",
        "teacher_id": "TEA6860CA834786E482",
        "date_absent": "2025-01-07",
        "reason": "This is a test excuse letter to verify teacher notifications.",
        "image_path": null,
        "status": "pending",
        "teacher_notes": null,
        "created_at": "2025-01-07 10:30:00",
        "updated_at": "2025-01-07 10:30:00",
        "subject_name": "Database Management System",
        "subject_code": "DBMS312",
        "section_name": "BSIT 1Z",
        "student_name": "CHRISELYN GREFAL"
    }
}
```

### Teacher Notification (In-App)
```json
{
    "id": 124,
    "type": "excuse_letter",
    "type_display": "Excuse Letter",
    "icon": "📄",
    "title": "New Excuse Letter: Database Management System",
    "message": "CHRISELYN GREFAL has submitted an excuse letter for Database Management System (BSIT 1Z) - Date: 2025-01-07",
    "related_id": 15,
    "related_type": "excuse_letter",
    "class_code": "J56NHD",
    "is_read": false,
    "is_urgent": false,
    "created_at": "2025-01-07 10:30:00",
    "updated_at": "2025-01-07 10:30:00"
}
```

### Teacher Email Notification
**Subject**: New Excuse Letter: Database Management System

**Body**: HTML email with student name, subject name, section name, and date

### Successful Status Update Response
```json
{
    "status": true,
    "message": "Excuse letter status updated successfully"
}
```

### Student Notification (In-App) - After Status Update
```json
{
    "id": 125,
    "type": "excuse_letter",
    "type_display": "Excuse Letter",
    "icon": "📄",
    "title": "Excuse Letter Approved: Database Management System",
    "message": "Your excuse letter for Database Management System (BSIT 1Z) has been approved. Date: 2025-01-07\n\nTeacher Notes: This is a test approval with teacher notes.",
    "related_id": 16,
    "related_type": "excuse_letter",
    "class_code": null,
    "is_read": false,
    "is_urgent": false,
    "created_at": "2025-01-07 11:30:00",
    "updated_at": "2025-01-07 11:30:00"
}
```

### Student Email Notification - After Status Update
**Subject**: Excuse Letter Approved: Database Management System

**Body**: HTML email with status, subject name, section name, date, and teacher notes

## Troubleshooting

### Issue 1: No Teacher Notification
**Possible Causes**:
- Teacher ID not found in class data
- Notification helper not loaded
- Database error in notification creation

**Solution**:
1. Check class data has valid `teacher_id`
2. Verify notification helper is loaded in ExcuseLetterController
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
1. Check for existing excuse letters before creating new ones
2. Implement proper error handling

## Code Changes Summary

### ExcuseLetterController.php Changes
1. **Added notification helper** in constructor
2. **Added notification call** in `submit_post()` method
3. **Added `send_excuse_letter_notification()`** method
4. **Uses existing notification helpers** for consistency

### Notification Details
- **Helper Function**: `create_excuse_letter_notification()`
- **Email Template**: Uses excuse letter-specific template
- **Database**: Stores in `notifications` table with `type = 'excuse_letter'`

## Testing Checklist

- [ ] Student can submit excuse letter successfully
- [ ] Teacher receives in-app notification
- [ ] Teacher receives email notification
- [ ] Notification contains correct student name
- [ ] Notification contains correct subject name
- [ ] Notification contains correct section name
- [ ] Notification contains correct date
- [ ] Notification appears in database
- [ ] No duplicate notifications created
- [ ] Error handling works properly

## Next Steps

1. **Test with real data** using student and teacher accounts
2. **Verify email delivery** to teacher's inbox
3. **Check notification settings** for teachers
4. **Monitor application logs** for any errors
5. **Test edge cases** (multiple submissions, invalid data, etc.)

## Test Script

Use the provided `test_excuse_letter_notification.php` script to test the functionality:

```bash
php test_excuse_letter_notification.php
```

Make sure to:
1. Replace `YOUR_STUDENT_TOKEN_HERE` with a valid student JWT token
2. Update the `class_id` to match an actual class in your database
3. Use a date that doesn't already have an excuse letter submitted
