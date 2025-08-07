# System Notifications Testing Guide

## Overview
This guide explains how to test the system notification functionality that has been implemented in your SCMS. System notifications are sent for administrative and system-level events.

## What Was Implemented

### 1. User Registration Welcome Notifications
- **Location**: `application/controllers/api/Auth.php`
- **Method**: `send_welcome_notification()` (private method)
- **Trigger**: Called automatically when a user successfully registers
- **Recipient**: The newly registered user

### 2. Account Status Change Notifications
- **Location**: `application/controllers/api/Auth.php`
- **Method**: `send_account_status_notification()` (private method)
- **Trigger**: Called when admin changes user status (active/inactive)
- **Recipient**: The user whose status was changed

### 3. Section Assignment Notifications
- **Location**: `application/controllers/api/AdminController.php`
- **Methods**: 
  - `send_section_assignment_notification()` (for advisers)
  - `send_student_section_assignment_notifications()` (for students)
- **Trigger**: Called when admin creates a new section with assignments
- **Recipients**: Adviser and assigned students

### 4. Security Alert Notifications
- **Location**: `application/controllers/api/Auth.php`
- **Method**: `send_security_alert_notification()` (private method)
- **Trigger**: Can be called for suspicious login activity
- **Recipient**: User whose account had suspicious activity

## System Notification Types

| Scenario | Type | Title | Message | Urgent |
|----------|------|-------|---------|--------|
| **User Registration** | `system` | "Welcome to SCMS!" | Welcome message with account details | No |
| **Account Activation** | `system` | "Account Status Updated" | Account activated message | No |
| **Account Deactivation** | `system` | "Account Status Updated" | Account suspended message | Yes |
| **Section Assignment (Adviser)** | `system` | "New Section Assignment" | Adviser assigned to section | No |
| **Section Assignment (Student)** | `system` | "Section Assignment" | Student assigned to section | No |
| **Security Alert** | `system` | "Security Alert - New Login" | Suspicious login activity | Yes |

## Testing Steps

### Step 1: Verify Database Setup
Run the test script to verify everything is set up correctly:
```bash
php test_system_notifications.php
```

### Step 2: Test User Registration (Welcome Notifications)

#### Using Postman:
1. **Set up the request**:
   - Method: `POST`
   - URL: `http://localhost/scms_new_backup/index.php/api/auth/register`
   - Headers:
     ```
     Content-Type: application/json
     ```
   - Body (JSON):
     ```json
     {
       "role": "student",
       "full_name": "Test Student",
       "email": "teststudent@example.com",
       "password": "password123",
       "contact_num": "09123456789",
       "address": "Test Address",
       "program": "BSIT",
       "student_num": "2021-0001",
       "qr_code": "TEST123"
     }
     ```

2. **Check the response**:
   ```json
   {
     "status": true,
     "message": "Student registered successfully!",
     "data": {
       "user_id": "STU123456789",
       "role": "student",
       "full_name": "Test Student",
       "email": "teststudent@example.com"
     }
   }
   ```

3. **Verify notification was created**:
   - Check the `notifications` table in database
   - Look for a record with `type = 'system'` and `title = 'Welcome to SCMS!'`

### Step 3: Test Account Status Changes

#### Using Postman:
1. **Set up the request** (requires admin token):
   - Method: `PUT`
   - URL: `http://localhost/scms_new_backup/index.php/api/auth/change_user_status`
   - Headers:
     ```
     Content-Type: application/json
     Authorization: Bearer [ADMIN_JWT_TOKEN]
     ```
   - Body (JSON):
     ```json
     {
       "target_role": "student",
       "user_id": "STU123456789",
       "status": "inactive"
     }
     ```

2. **Check the response**:
   ```json
   {
     "status": true,
     "message": "Student deactivated successfully"
   }
   ```

3. **Verify notification was created**:
   - Check the `notifications` table
   - Look for a record with `type = 'system'`, `title = 'Account Status Updated'`, and `is_urgent = 1`

### Step 4: Test Section Assignment Notifications

#### Using Postman:
1. **Set up the request** (requires admin token):
   - Method: `POST`
   - URL: `http://localhost/scms_new_backup/index.php/api/admin/sections`
   - Headers:
     ```
     Content-Type: application/json
     Authorization: Bearer [ADMIN_JWT_TOKEN]
     ```
   - Body (JSON):
     ```json
     {
       "section_name": "BSIT 1A",
       "program": "BSIT",
       "year_level": "1st Year",
       "adviser_id": "TEA123456789",
       "semester": "1st",
       "academic_year": "2024-2025",
       "student_ids": ["STU123456789", "STU987654321"]
     }
     ```

2. **Check the response**:
   ```json
   {
     "status": true,
     "message": "Section created successfully",
     "data": {
       "section_id": 1,
       "assigned_students_count": 2,
       "assigned_students": [...]
     }
   }
   ```

3. **Verify notifications were created**:
   - Check for adviser notification: `type = 'system'`, `title = 'New Section Assignment'`
   - Check for student notifications: `type = 'system'`, `title = 'Section Assignment'`

### Step 5: Test Email Notifications

If email notifications are enabled, users should also receive emails for system notifications. Check:
1. **Email inbox** of the test users
2. **Email logs** in the application
3. **SMTP configuration** in `application/config/email.php`

## Database Verification

### Check System Notifications in Database:
```sql
SELECT 
    n.id,
    n.user_id,
    n.type,
    n.title,
    n.message,
    n.is_urgent,
    n.created_at,
    u.full_name,
    u.email
FROM notifications n
JOIN users u ON n.user_id = u.user_id
WHERE n.type = 'system'
ORDER BY n.created_at DESC
LIMIT 10;
```

### Check Notification Settings:
```sql
SELECT 
    ns.user_id,
    ns.email_notifications,
    ns.system_notifications,
    u.full_name,
    u.email
FROM notification_settings ns
JOIN users u ON ns.user_id = u.user_id
ORDER BY ns.created_at DESC;
```

## API Endpoints for System Notifications

### 1. Get User Notifications
```
GET {{base_url}}/api/notifications
Authorization: Bearer [JWT_TOKEN]
```

### 2. Mark Notification as Read
```
PUT {{base_url}}/api/notifications/{id}/read
Authorization: Bearer [JWT_TOKEN]
```

### 3. Get Unread Count
```
GET {{base_url}}/api/notifications?unread_only=true
Authorization: Bearer [JWT_TOKEN]
```

## Expected Behavior

### For Welcome Notifications:
- ✅ Sent immediately after successful registration
- ✅ Contains personalized welcome message
- ✅ Includes user's name and role
- ✅ Not marked as urgent

### For Account Status Changes:
- ✅ Sent when admin changes user status
- ✅ Different messages for activation vs deactivation
- ✅ Deactivation notifications marked as urgent
- ✅ Includes instructions for next steps

### For Section Assignments:
- ✅ Adviser notified when assigned to new section
- ✅ Students notified when assigned to section
- ✅ Includes section details (name, program, year)
- ✅ Not marked as urgent

### For Security Alerts:
- ✅ Marked as urgent
- ✅ Include login details
- ✅ Provide security instructions

## Troubleshooting

### Common Issues:

1. **No notifications created**:
   - Check if notification helper is loaded
   - Verify database connection
   - Check for PHP errors in logs

2. **Email notifications not sent**:
   - Verify SMTP configuration
   - Check email settings in database
   - Test SMTP connection

3. **Notifications not appearing in UI**:
   - Check API authentication
   - Verify notification retrieval endpoint
   - Check for JavaScript errors

### Debug Commands:
```bash
# Check PHP error logs
tail -f /path/to/php/error.log

# Check application logs
tail -f application/logs/log-*.php

# Test database connection
php test_system_notifications.php
```

## Summary

System notifications are now fully implemented and will automatically send notifications for:
- ✅ User registration (welcome messages)
- ✅ Account status changes (activation/deactivation)
- ✅ Section assignments (adviser and student notifications)
- ✅ Security alerts (suspicious activity)

All notifications are stored in the database and can be sent via email if configured. The system is ready for production use!

