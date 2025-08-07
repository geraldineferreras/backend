# SCMS Notification System Installation Guide

## Overview

This guide provides step-by-step instructions for installing and configuring the notification system in your SCMS (Student Class Management System). The notification system includes both in-app notifications and email notifications.

## Prerequisites

- CodeIgniter 3.x
- MySQL/MariaDB database
- PHP with email extensions enabled
- Gmail account for SMTP (or other SMTP provider)

## Installation Steps

### 1. Database Setup

#### Step 1.1: Create Notification Tables

Run the SQL script to create the required database tables:

```sql
-- Execute the contents of notifications_table.sql
```

You can run this in phpMyAdmin or your preferred MySQL client.

#### Step 1.2: Verify Table Creation

Check that the following tables were created successfully:
- `notifications` - Stores all notification data
- `notification_settings` - Stores user notification preferences

### 2. File Installation

#### Step 2.1: Copy Model Files

Ensure the following file is in place:
- `application/models/Notification_model.php`

#### Step 2.2: Copy Controller Files

Ensure the following file is in place:
- `application/controllers/api/NotificationController.php`

#### Step 2.3: Copy Helper Files

Ensure the following files are in place:
- `application/helpers/notification_helper.php`
- `application/helpers/email_notification_helper.php`

#### Step 2.4: Copy Configuration Files

Ensure the following file is in place:
- `application/config/email.php`

### 3. Email Configuration

#### Step 3.1: Gmail SMTP Setup

The system is configured to use Gmail SMTP. The configuration is already set in `application/config/email.php`:

```php
$config['protocol'] = 'smtp';
$config['smtp_host'] = 'smtp.gmail.com';
$config['smtp_port'] = 465;
$config['smtp_user'] = 'grldnferreras@gmail.com';
$config['smtp_pass'] = 'ucek fffw ccfe siny';
$config['smtp_crypto'] = 'ssl';
```

#### Step 3.2: Test Email Configuration

Run the test script to verify email configuration:

1. Open `test_gmail_email.php` in your browser
2. Replace `test@example.com` with your actual email address
3. Run the script to test email sending

### 4. Route Configuration

#### Step 4.1: Verify Routes

The notification routes have been added to `application/config/routes.php`. Verify these routes are present:

```php
// Notification Management
$route['api/notifications']['get'] = 'api/NotificationController/get_notifications';
$route['api/notifications/(:num)/read']['put'] = 'api/NotificationController/mark_as_read/$1';
$route['api/notifications/mark-all-read']['put'] = 'api/NotificationController/mark_all_as_read';
$route['api/notifications/(:num)']['delete'] = 'api/NotificationController/delete_notification/$1';
$route['api/notifications/settings']['get'] = 'api/NotificationController/get_settings';
$route['api/notifications/settings']['put'] = 'api/NotificationController/update_settings';
$route['api/notifications/unread-count']['get'] = 'api/NotificationController/get_unread_count';
$route['api/notifications/recent']['get'] = 'api/NotificationController/get_recent';
$route['api/notifications/urgent']['get'] = 'api/NotificationController/get_urgent';
```

### 5. Integration with Existing Controllers

#### Step 5.1: Teacher Controller Integration

The notification system will be integrated into the following existing controllers:
- `TeacherController.php` - For announcements, tasks, and grades
- `TaskController.php` - For task-related notifications
- `ExcuseLetterController.php` - For excuse letter notifications
- `StudentController.php` - For enrollment notifications

#### Step 5.2: Helper Loading

Ensure the notification helpers are loaded in your controllers:

```php
$this->load->helper('notification');
$this->load->helper('email_notification');
```

### 6. Testing the Installation

#### Step 6.1: Test Database Connection

1. Access your application
2. Try to create a test notification
3. Check if notifications are stored in the database

#### Step 6.2: Test Email Sending

1. Run the email test script
2. Check your email inbox for test messages
3. Verify HTML formatting is correct

#### Step 6.3: Test API Endpoints

Use Postman or similar tool to test the notification API endpoints:

- `GET /api/notifications` - Get user notifications
- `PUT /api/notifications/{id}/read` - Mark notification as read
- `GET /api/notifications/settings` - Get notification settings

## Configuration Options

### Email Settings

You can modify the email configuration in `application/config/email.php`:

```php
$config['smtp_user'] = 'your-email@gmail.com';
$config['smtp_pass'] = 'your-app-password';
```

### Notification Types

The system supports the following notification types:
- `announcement` - Class announcements
- `task` - Task assignments and updates
- `submission` - Task submissions
- `excuse_letter` - Excuse letter requests
- `grade` - Grade updates
- `enrollment` - Class enrollment
- `system` - System notifications

### User Settings

Users can configure their notification preferences through the API:
- Email notifications (on/off)
- Push notifications (on/off)
- Type-specific notifications (announcements, tasks, etc.)

## Troubleshooting

### Common Issues

#### Issue 1: Email Not Sending
**Solution:**
1. Check Gmail app password is correct
2. Verify SMTP settings
3. Check PHP email extensions are enabled
4. Test with the email test script

#### Issue 2: Database Errors
**Solution:**
1. Verify database tables exist
2. Check foreign key constraints
3. Ensure user_id exists in users table

#### Issue 3: API Endpoints Not Working
**Solution:**
1. Check routes are properly configured
2. Verify controller file exists
3. Check JWT authentication is working

#### Issue 4: Notifications Not Creating
**Solution:**
1. Check helper files are loaded
2. Verify model methods are working
3. Check user permissions

### Debug Information

Enable CodeIgniter debugging to see detailed error messages:

```php
// In application/config/config.php
$config['log_threshold'] = 4;
```

## Security Considerations

### JWT Authentication

All notification endpoints require valid JWT tokens. Ensure your authentication system is properly configured.

### Email Security

- Use app passwords for Gmail
- Enable 2-factor authentication
- Regularly rotate passwords

### Database Security

- Use prepared statements (already implemented)
- Validate all input data
- Implement proper access controls

## Performance Optimization

### Database Indexing

The notification tables include proper indexes for optimal performance:
- `idx_user_id` - For user-specific queries
- `idx_type` - For type-based filtering
- `idx_is_read` - For unread notifications
- `idx_created_at` - For chronological ordering

### Email Batching

For bulk notifications, consider implementing email batching to avoid overwhelming the SMTP server.

## Maintenance

### Regular Tasks

1. **Clean Old Notifications**: Implement a cron job to delete old notifications
2. **Monitor Email Logs**: Check for failed email deliveries
3. **Update Gmail Settings**: Keep app passwords current
4. **Database Optimization**: Regular table optimization

### Backup Strategy

Include notification tables in your regular database backup routine.

## Support

For issues or questions:
1. Check the troubleshooting section
2. Review the API documentation
3. Test with Postman collection
4. Check server error logs

## Version Information

- **Version**: 1.0
- **Framework**: CodeIgniter 3.x
- **Database**: MySQL/MariaDB
- **Email**: Gmail SMTP
- **Authentication**: JWT

---

**Note**: This installation guide assumes you have basic knowledge of CodeIgniter and MySQL. If you encounter issues, refer to the CodeIgniter documentation or seek assistance from your development team. 