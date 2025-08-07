# Complete Notification Testing Guide

## Overview

This comprehensive guide covers testing all aspects of the SCMS notification system, including in-app notifications, email notifications, and integration with existing features.

**Base URL**: `http://localhost/scms_new_backup/index.php`

## Prerequisites

1. **Database Setup**: Run `notifications_table.sql`
2. **JWT Token**: Obtain valid token from login
3. **Postman**: For API testing
4. **Email Access**: To test email notifications

## Part 1: Database Testing

### 1.1 Verify Table Creation

**SQL Commands**:
```sql
-- Check if tables exist
SHOW TABLES LIKE 'notifications';
SHOW TABLES LIKE 'notification_settings';

-- Check table structure
DESCRIBE notifications;
DESCRIBE notification_settings;

-- Check foreign key constraints
SELECT 
    CONSTRAINT_NAME,
    COLUMN_NAME,
    REFERENCED_TABLE_NAME,
    REFERENCED_COLUMN_NAME
FROM information_schema.KEY_COLUMN_USAGE 
WHERE TABLE_NAME = 'notifications';
```

**Expected Results**:
- Both tables should exist
- Foreign key to `users` table should be present
- All required columns should exist

### 1.2 Test Data Insertion

**SQL Commands**:
```sql
-- Insert test notification
INSERT INTO notifications (
    user_id, type, title, message, 
    related_id, related_type, class_code, 
    is_read, is_urgent
) VALUES (
    'STU001', 'announcement', 'Test Announcement', 
    'This is a test announcement', 123, 'announcement', 
    'CS101', 0, 0
);

-- Insert test settings
INSERT INTO notification_settings (
    user_id, email_notifications, push_notifications,
    announcement_notifications, task_notifications,
    submission_notifications, grade_notifications,
    enrollment_notifications
) VALUES (
    'STU001', 1, 1, 1, 1, 1, 1, 1
);
```

**Expected Results**:
- No errors during insertion
- Data appears in tables

## Part 2: API Testing

### 2.1 Authentication Testing

#### Test 1: Valid JWT Token
```bash
# Get token
POST {{base_url}}/api/login
Content-Type: application/json

{
  "username": "student1",
  "password": "password123"
}
```

**Expected**: 200 OK with token

#### Test 2: Invalid Token
```bash
GET {{base_url}}/api/notifications
Authorization: Bearer invalid_token
```

**Expected**: 401 Unauthorized

#### Test 3: No Token
```bash
GET {{base_url}}/api/notifications
```

**Expected**: 401 Unauthorized

### 2.2 Notification CRUD Testing

#### Test 1: Get Notifications
```bash
GET {{base_url}}/api/notifications
Authorization: Bearer {{jwt_token}}
```

**Expected Response**:
```json
{
  "success": true,
  "data": {
    "notifications": [...],
    "unread_count": 0
  }
}
```

#### Test 2: Get Notifications with Parameters
```bash
GET {{base_url}}/api/notifications?limit=10&offset=0&unread_only=true
Authorization: Bearer {{jwt_token}}
```

**Expected**: Paginated results

#### Test 3: Mark as Read
```bash
PUT {{base_url}}/api/notifications/1/read
Authorization: Bearer {{jwt_token}}
```

**Expected**: Success message

#### Test 4: Mark All as Read
```bash
PUT {{base_url}}/api/notifications/mark-all-read
Authorization: Bearer {{jwt_token}}
```

**Expected**: Success message

#### Test 5: Delete Notification
```bash
DELETE {{base_url}}/api/notifications/1
Authorization: Bearer {{jwt_token}}
```

**Expected**: Success message

### 2.3 Settings Testing

#### Test 1: Get Settings
```bash
GET {{base_url}}/api/notifications/settings
Authorization: Bearer {{jwt_token}}
```

**Expected Response**:
```json
{
  "success": true,
  "data": {
    "email_notifications": true,
    "push_notifications": true,
    "announcement_notifications": true,
    "task_notifications": true,
    "submission_notifications": true,
    "grade_notifications": true,
    "enrollment_notifications": true
  }
}
```

#### Test 2: Update Settings
```bash
PUT {{base_url}}/api/notifications/settings
Authorization: Bearer {{jwt_token}}
Content-Type: application/json

{
  "email_notifications": false,
  "task_notifications": false
}
```

**Expected**: Success message

### 2.4 Special Endpoints Testing

#### Test 1: Get Unread Count
```bash
GET {{base_url}}/api/notifications/unread-count
Authorization: Bearer {{jwt_token}}
```

**Expected**: Count of unread notifications

#### Test 2: Get Recent Notifications
```bash
GET {{base_url}}/api/notifications/recent?limit=5
Authorization: Bearer {{jwt_token}}
```

**Expected**: Recent notifications array

#### Test 3: Get Urgent Notifications
```bash
GET {{base_url}}/api/notifications/urgent
Authorization: Bearer {{jwt_token}}
```

**Expected**: Urgent notifications array

## Part 3: Helper Function Testing

### 3.1 Create Notifications via Helper

#### Test 1: Simple Notification
```php
// In your controller
create_notification(
    'STU001',
    'announcement',
    'Test Notification',
    'This is a test notification',
    123,
    'announcement',
    'CS101',
    false
);
```

**Expected**: Notification created in database

#### Test 2: Multiple Users
```php
$user_ids = ['STU001', 'STU002', 'STU003'];
create_notifications_for_users(
    $user_ids,
    'announcement',
    'Class Announcement',
    'Important class announcement',
    123,
    'announcement',
    'CS101',
    false
);
```

**Expected**: Notifications created for all users

#### Test 3: Type-Specific Helpers
```php
// Announcement
create_announcement_notification('STU001', 123, 'Announcement', 'Message', 'CS101');

// Task
create_task_notification('STU001', 456, 'Task', 'Description', 'CS101', true);

// Grade
create_grade_notification('STU001', 789, 'Grade Update', 'Your grade has been updated', 'CS101');
```

**Expected**: Type-specific notifications created

### 3.2 Email Notification Testing

#### Test 1: Send Email
```php
send_email_notification(
    'STU001',
    'announcement',
    'Test Email',
    'This is a test email',
    123,
    'announcement',
    'CS101'
);
```

**Expected**: Email sent successfully

#### Test 2: Test Email Configuration
```bash
GET {{base_url}}/test_gmail_email.php
```

**Expected**: Email test results displayed

#### Test 3: Type-Specific Emails
```php
send_announcement_email('STU001', 123, 'Announcement', 'Message', 'CS101');
send_task_email('STU001', 456, 'Task', 'Description', 'CS101');
send_grade_email('STU001', 789, 'Grade Update', 'Your grade has been updated', 'CS101');
```

**Expected**: Type-specific emails sent

## Part 4: Integration Testing

### 4.1 Teacher Controller Integration

#### Test 1: Create Announcement
```bash
# Create announcement (assuming endpoint exists)
POST {{base_url}}/api/teacher/announcements
Authorization: Bearer {{teacher_token}}
Content-Type: application/json

{
  "title": "Test Announcement",
  "message": "This is a test announcement",
  "class_code": "CS101"
}
```

**Expected**: 
- Announcement created
- Notifications sent to all students in class
- Email notifications sent (if enabled)

#### Test 2: Create Task
```bash
# Create task (assuming endpoint exists)
POST {{base_url}}/api/teacher/tasks
Authorization: Bearer {{teacher_token}}
Content-Type: application/json

{
  "title": "Test Task",
  "description": "This is a test task",
  "class_code": "CS101",
  "due_date": "2024-01-20"
}
```

**Expected**:
- Task created
- Notifications sent to assigned students
- Email notifications sent (if enabled)

### 4.2 Task Controller Integration

#### Test 1: Task Submission
```bash
# Submit task (assuming endpoint exists)
POST {{base_url}}/api/tasks/1/submit
Authorization: Bearer {{student_token}}
Content-Type: application/json

{
  "submission_text": "My submission"
}
```

**Expected**:
- Submission created
- Notification sent to teacher
- Email notification sent (if enabled)

#### Test 2: Grade Assignment
```bash
# Grade submission (assuming endpoint exists)
POST {{base_url}}/api/tasks/submissions/1/grade
Authorization: Bearer {{teacher_token}}
Content-Type: application/json

{
  "grade": 85,
  "feedback": "Good work"
}
```

**Expected**:
- Grade assigned
- Notification sent to student
- Email notification sent (if enabled)

### 4.3 Student Controller Integration

#### Test 1: Join Class
```bash
# Join class (assuming endpoint exists)
POST {{base_url}}/api/student/join-class
Authorization: Bearer {{student_token}}
Content-Type: application/json

{
  "class_code": "CS101"
}
```

**Expected**:
- Student enrolled in class
- Notification sent to teacher
- Email notification sent (if enabled)

### 4.4 Excuse Letter Integration

#### Test 1: Submit Excuse Letter
```bash
# Submit excuse letter (assuming endpoint exists)
POST {{base_url}}/api/excuse-letters/submit
Authorization: Bearer {{student_token}}
Content-Type: application/json

{
  "reason": "Medical appointment",
  "date": "2024-01-15"
}
```

**Expected**:
- Excuse letter submitted
- Notification sent to teacher
- Email notification sent (if enabled)

## Part 5: Email Testing

### 5.1 Email Configuration Test

#### Test 1: Basic Email Test
```bash
GET {{base_url}}/test_gmail_email.php
```

**Expected**: Email test results with success/failure status

#### Test 2: HTML Email Test
```php
// Test HTML email formatting
send_email_notification(
    'STU001',
    'announcement',
    'HTML Test',
    'This is a <strong>bold</strong> test message with <em>formatting</em>.',
    123,
    'announcement',
    'CS101'
);
```

**Expected**: HTML formatted email received

### 5.2 Email Settings Test

#### Test 1: Disable Email Notifications
```bash
PUT {{base_url}}/api/notifications/settings
Authorization: Bearer {{jwt_token}}
Content-Type: application/json

{
  "email_notifications": false
}
```

**Then create notification**:
```php
create_notification('STU001', 'announcement', 'Test', 'Message', 123, 'announcement', 'CS101');
```

**Expected**: No email sent

#### Test 2: Enable Email Notifications
```bash
PUT {{base_url}}/api/notifications/settings
Authorization: Bearer {{jwt_token}}
Content-Type: application/json

{
  "email_notifications": true
}
```

**Then create notification**:
```php
create_notification('STU001', 'announcement', 'Test', 'Message', 123, 'announcement', 'CS101');
```

**Expected**: Email sent

## Part 6: Performance Testing

### 6.1 Load Testing

#### Test 1: Multiple Notifications
```php
// Create 100 notifications
for ($i = 1; $i <= 100; $i++) {
    create_notification(
        'STU001',
        'announcement',
        "Notification $i",
        "Message $i",
        $i,
        'announcement',
        'CS101'
    );
}
```

**Then test API**:
```bash
GET {{base_url}}/api/notifications?limit=50
Authorization: Bearer {{jwt_token}}
```

**Expected**: Response within 1 second

#### Test 2: Pagination Test
```bash
GET {{base_url}}/api/notifications?limit=10&offset=0
GET {{base_url}}/api/notifications?limit=10&offset=10
GET {{base_url}}/api/notifications?limit=10&offset=20
```

**Expected**: Different results for each page

### 6.2 Concurrent Testing

#### Test 1: Multiple Users
```bash
# Test with multiple user tokens simultaneously
GET {{base_url}}/api/notifications
Authorization: Bearer {{token1}}

GET {{base_url}}/api/notifications
Authorization: Bearer {{token2}}

GET {{base_url}}/api/notifications
Authorization: Bearer {{token3}}
```

**Expected**: All requests successful

## Part 7: Error Testing

### 7.1 Invalid Data Testing

#### Test 1: Invalid Notification ID
```bash
PUT {{base_url}}/api/notifications/999/read
Authorization: Bearer {{jwt_token}}
```

**Expected**: 404 Not Found

#### Test 2: Invalid Settings
```bash
PUT {{base_url}}/api/notifications/settings
Authorization: Bearer {{jwt_token}}
Content-Type: application/json

{
  "invalid_field": true
}
```

**Expected**: 400 Bad Request or 500 Internal Server Error

#### Test 3: Invalid User ID
```php
create_notification(
    'INVALID_USER',
    'announcement',
    'Test',
    'Message',
    123,
    'announcement',
    'CS101'
);
```

**Expected**: Database error or foreign key constraint violation

### 7.2 Edge Cases

#### Test 1: Empty Message
```php
create_notification(
    'STU001',
    'announcement',
    'Test',
    '',
    123,
    'announcement',
    'CS101'
);
```

**Expected**: Notification created with empty message

#### Test 2: Very Long Message
```php
$long_message = str_repeat('A', 10000);
create_notification(
    'STU001',
    'announcement',
    'Test',
    $long_message,
    123,
    'announcement',
    'CS101'
);
```

**Expected**: Notification created (or error if exceeds database limit)

## Part 8: Security Testing

### 8.1 Authentication Testing

#### Test 1: Token Expiration
```bash
# Use expired token
GET {{base_url}}/api/notifications
Authorization: Bearer {{expired_token}}
```

**Expected**: 401 Unauthorized

#### Test 2: Cross-User Access
```bash
# Try to access another user's notification
PUT {{base_url}}/api/notifications/1/read
Authorization: Bearer {{other_user_token}}
```

**Expected**: 404 Not Found or 403 Forbidden

### 8.2 Input Validation

#### Test 1: SQL Injection
```bash
PUT {{base_url}}/api/notifications/settings
Authorization: Bearer {{jwt_token}}
Content-Type: application/json

{
  "email_notifications": "'; DROP TABLE notifications; --"
}
```

**Expected**: Input validation error or sanitized input

#### Test 2: XSS Prevention
```php
create_notification(
    'STU001',
    'announcement',
    '<script>alert("XSS")</script>',
    '<script>alert("XSS")</script>',
    123,
    'announcement',
    'CS101'
);
```

**Expected**: Script tags escaped or removed

## Part 9: User Role Testing

### 9.1 Student Testing

#### Test 1: Student Login
```bash
POST {{base_url}}/api/login
Content-Type: application/json

{
  "username": "student1",
  "password": "password123"
}
```

#### Test 2: Student Notifications
```bash
GET {{base_url}}/api/notifications
Authorization: Bearer {{student_token}}
```

**Expected**: Student's notifications only

### 9.2 Teacher Testing

#### Test 1: Teacher Login
```bash
POST {{base_url}}/api/login
Content-Type: application/json

{
  "username": "teacher1",
  "password": "password123"
}
```

#### Test 2: Teacher Notifications
```bash
GET {{base_url}}/api/notifications
Authorization: Bearer {{teacher_token}}
```

**Expected**: Teacher's notifications only

### 9.3 Admin Testing

#### Test 1: Admin Login
```bash
POST {{base_url}}/api/login
Content-Type: application/json

{
  "username": "admin",
  "password": "password123"
}
```

#### Test 2: Admin Notifications
```bash
GET {{base_url}}/api/notifications
Authorization: Bearer {{admin_token}}
```

**Expected**: Admin's notifications only

## Part 10: Cleanup Testing

### 10.1 Data Cleanup

#### Test 1: Delete Test Notifications
```bash
DELETE {{base_url}}/api/notifications/1
Authorization: Bearer {{jwt_token}}
```

**Expected**: Notification deleted

#### Test 2: Reset Settings
```bash
PUT {{base_url}}/api/notifications/settings
Authorization: Bearer {{jwt_token}}
Content-Type: application/json

{
  "email_notifications": true,
  "push_notifications": true,
  "announcement_notifications": true,
  "task_notifications": true,
  "submission_notifications": true,
  "grade_notifications": true,
  "enrollment_notifications": true
}
```

**Expected**: Settings reset to defaults

## Test Results Documentation

### Success Criteria

- [ ] All API endpoints return correct responses
- [ ] Email notifications are sent successfully
- [ ] Database operations work correctly
- [ ] Authentication and authorization work
- [ ] Error handling works properly
- [ ] Performance is acceptable
- [ ] Security measures are effective

### Issues Found

Document any issues discovered during testing:

1. **Issue 1**: Description and steps to reproduce
2. **Issue 2**: Description and steps to reproduce
3. **Issue 3**: Description and steps to reproduce

### Recommendations

1. **Performance**: Any performance improvements needed
2. **Security**: Any security enhancements required
3. **Usability**: Any user experience improvements
4. **Documentation**: Any documentation updates needed

## Conclusion

This comprehensive testing guide covers all aspects of the notification system. Follow each section systematically to ensure thorough testing of all features.

For additional support, refer to:
- `NOTIFICATION_INSTALLATION_GUIDE.md`
- `NOTIFICATION_API.md`
- `POSTMAN_TESTING_GUIDE.md`
- `POSTMAN_QUICK_REFERENCE.md` 