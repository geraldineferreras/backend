# Postman Quick Reference - SCMS Notification System

## Quick Setup

### 1. Environment Variables
```
base_url: http://localhost/scms_new_backup/index.php
jwt_token: [your_jwt_token_here]
```

### 2. Get JWT Token
```
POST {{base_url}}/api/login
Content-Type: application/json

{
  "username": "your_username",
  "password": "your_password"
}
```

## API Endpoints Quick Reference

### Authentication Header
```
Authorization: Bearer {{jwt_token}}
Content-Type: application/json
```

### 1. Get Notifications
```
GET {{base_url}}/api/notifications
GET {{base_url}}/api/notifications?limit=20&offset=0&unread_only=true
```

### 2. Mark as Read
```
PUT {{base_url}}/api/notifications/1/read
```

### 3. Mark All as Read
```
PUT {{base_url}}/api/notifications/mark-all-read
```

### 4. Delete Notification
```
DELETE {{base_url}}/api/notifications/1
```

### 5. Get Settings
```
GET {{base_url}}/api/notifications/settings
```

### 6. Update Settings
```
PUT {{base_url}}/api/notifications/settings
Content-Type: application/json

{
  "email_notifications": false,
  "task_notifications": false
}
```

### 7. Get Unread Count
```
GET {{base_url}}/api/notifications/unread-count
```

### 8. Get Recent
```
GET {{base_url}}/api/notifications/recent?limit=5
```

### 9. Get Urgent
```
GET {{base_url}}/api/notifications/urgent
```

## Expected Responses

### Success Response
```json
{
  "success": true,
  "data": { ... }
}
```

### Error Response
```json
{
  "error": "Error message"
}
```

## Status Codes

- `200` - Success
- `401` - Unauthorized
- `404` - Not Found
- `500` - Server Error

## Test Users

### Student
```
username: student1
password: password123
```

### Teacher
```
username: teacher1
password: password123
```

### Admin
```
username: admin
password: password123
```

## Quick Test Scripts

### Basic Response Test
```javascript
pm.test("Status code is 200", function () {
    pm.response.to.have.status(200);
});

pm.test("Response has success field", function () {
    var jsonData = pm.response.json();
    pm.expect(jsonData).to.have.property('success');
    pm.expect(jsonData.success).to.eql(true);
});
```

### Authentication Test
```javascript
pm.test("Unauthorized access", function () {
    pm.response.to.have.status(401);
});

pm.test("Error response", function () {
    var jsonData = pm.response.json();
    pm.expect(jsonData).to.have.property('error');
});
```

## Common Issues & Solutions

| Issue | Solution |
|-------|----------|
| 401 Unauthorized | Check JWT token validity |
| 404 Not Found | Verify notification ID exists |
| CORS Error | Check server configuration |
| Database Error | Verify tables exist |

## Notification Types

| Type | Icon | Description |
|------|------|-------------|
| announcement | 📢 | Class announcements |
| task | 📝 | Task assignments |
| submission | 📤 | Task submissions |
| excuse_letter | 📄 | Excuse letters |
| grade | 📊 | Grade updates |
| enrollment | 👥 | Class enrollment |
| system | ⚙️ | System notifications |

## Email Test

### Test Email Configuration
```
GET {{base_url}}/test_gmail_email.php
```

Replace `test@example.com` with your email address.

## Quick Commands

### Create Test Notification (via Helper)
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

### Send Test Email
```php
// In your controller
send_email_notification(
    'STU001',
    'announcement',
    'Test Email',
    'This is a test email notification',
    123,
    'announcement',
    'CS101'
);
```

## Performance Tips

1. **Use Environment Variables** - Store tokens and URLs
2. **Batch Requests** - Test multiple endpoints together
3. **Clean Up** - Delete test data after testing
4. **Monitor Logs** - Check server error logs

## Security Checklist

- [ ] JWT token is valid
- [ ] Token not expired
- [ ] User has proper permissions
- [ ] Input validation working
- [ ] SQL injection prevention
- [ ] XSS prevention

## Debug Commands

### Check Database
```sql
SELECT * FROM notifications WHERE user_id = 'STU001';
SELECT * FROM notification_settings WHERE user_id = 'STU001';
```

### Check Email Logs
```php
// In test_gmail_email.php
echo $CI->email->print_debugger();
```

### Check Routes
```php
// In CodeIgniter
var_dump($this->router->routes);
```

## Quick Troubleshooting

1. **Can't Login**: Check username/password
2. **Token Invalid**: Re-login to get new token
3. **No Notifications**: Check if notifications exist in database
4. **Email Not Sending**: Check SMTP configuration
5. **API Not Working**: Verify routes are configured

## Environment Setup

### XAMPP Configuration
- Apache: Running on port 80
- MySQL: Running on port 3306
- PHP: Version 7.4 or higher

### Database Connection
```php
// In application/config/database.php
$db['default'] = array(
    'hostname' => 'localhost',
    'username' => 'root',
    'password' => '',
    'database' => 'scms_db'
);
```

## Quick Start Checklist

- [ ] XAMPP server running
- [ ] Database tables created
- [ ] JWT token obtained
- [ ] Environment variables set
- [ ] First API call successful
- [ ] Email test working

## Support Resources

- **Installation Guide**: `NOTIFICATION_INSTALLATION_GUIDE.md`
- **API Documentation**: `NOTIFICATION_API.md`
- **Testing Guide**: `POSTMAN_TESTING_GUIDE.md`
- **Email Test**: `test_gmail_email.php`

## Version Info

- **Framework**: CodeIgniter 3.x
- **Database**: MySQL/MariaDB
- **Email**: Gmail SMTP
- **Auth**: JWT
- **Base URL**: `http://localhost/scms_new_backup/index.php` 