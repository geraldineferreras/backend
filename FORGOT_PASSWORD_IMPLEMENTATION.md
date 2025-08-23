# 🔐 Forgot Password Implementation - SCMS

## Overview
This document describes the complete implementation of forgot password functionality for the SCMS (Student Classroom Management System) backend.

## ✅ What Has Been Implemented

### 1. **Database Table**
- **Table**: `password_reset_tokens`
- **Purpose**: Stores password reset tokens with expiration and usage tracking
- **File**: `create_password_reset_tokens_table.sql`

### 2. **API Endpoints**
- **POST** `/api/auth/forgot-password` - Request password reset
- **POST** `/api/auth/reset-password` - Reset password using token

### 3. **Backend Controller Methods**
- **`forgot_password()`** - Handles forgot password requests
- **`reset_password()`** - Handles password reset using tokens
- **`send_password_reset_email()`** - Sends reset emails

### 4. **Security Features**
- Secure token generation using `random_bytes(32)`
- 1-hour token expiration
- Single-use tokens (marked as used after reset)
- Audit logging for all password reset activities
- No user enumeration (same response for existing/non-existing emails)

## 🚀 Setup Instructions

### Step 1: Create Database Table
Run the setup script:
```bash
php setup_password_reset_table.php
```

Or manually execute the SQL:
```sql
-- Run the contents of create_password_reset_tokens_table.sql
```

### Step 2: Configure Email Settings
In your CodeIgniter configuration (`application/config/email.php`):

```php
$config['protocol'] = 'smtp';
$config['smtp_host'] = 'your-smtp-host.com';
$config['smtp_port'] = 587;
$config['smtp_user'] = 'your-email@domain.com';
$config['smtp_pass'] = 'your-password';
$config['smtp_crypto'] = 'tls';
$config['mailtype'] = 'html';
```

### Step 3: Update Routes
Routes are already added to `application/config/routes.php`:
```php
$route['api/auth/forgot-password']['post'] = 'api/auth/forgot_password';
$route['api/auth/reset-password']['post'] = 'api/auth/reset_password';
```

## 📡 API Usage

### 1. Request Password Reset
**Endpoint**: `POST /api/auth/forgot-password`

**Request Body**:
```json
{
    "email": "user@example.com"
}
```

**Response**:
```json
{
    "status": true,
    "message": "Password reset link has been sent to your email"
}
```

### 2. Reset Password
**Endpoint**: `POST /api/auth/reset-password`

**Request Body**:
```json
{
    "token": "abc123...",
    "new_password": "newpassword123"
}
```

**Response**:
```json
{
    "status": true,
    "message": "Password has been reset successfully"
}
```

## 🔧 Testing

### Test File
Use `test_forgot_password.php` to test the functionality:

1. **Open** `test_forgot_password.php` in your browser
2. **Create** the database table first
3. **Test** forgot password request
4. **Check** email for reset link
5. **Test** password reset with token
6. **Verify** login with new password

### Manual Testing with Postman

#### Test Forgot Password:
```
POST http://localhost/scms_new_backup/index.php/api/auth/forgot-password
Content-Type: application/json

{
    "email": "admin@example.com"
}
```

#### Test Reset Password:
```
POST http://localhost/scms_new_backup/index.php/api/auth/reset-password
Content-Type: application/json

{
    "token": "token_from_email",
    "new_password": "newpassword123"
}
```

## 🛡️ Security Features

### Token Security
- **Length**: 64 characters (32 bytes hex)
- **Entropy**: Cryptographically secure random generation
- **Expiration**: 1 hour from creation
- **Usage**: Single-use only

### Email Security
- **No user enumeration**: Same response for all emails
- **Secure links**: HTTPS reset links
- **Clear expiration**: Users know when links expire
- **Professional template**: Reduces phishing risk

### Audit Logging
- **Request logging**: All password reset requests
- **Completion logging**: Successful password resets
- **IP tracking**: Source IP addresses
- **User agent**: Browser/device information

## 📧 Email Configuration

### SMTP Services
The system works with any SMTP service:

- **Gmail**: Use App Passwords for security
- **SendGrid**: Professional email delivery
- **Mailgun**: Reliable email service
- **Amazon SES**: Cost-effective for high volume

### Email Template
The system sends professional HTML emails with:
- Clear subject line
- User's full name
- Secure reset button
- Manual link option
- Expiration warning
- Security notice

## 🐛 Troubleshooting

### Common Issues

#### 1. **Email Not Sending**
- Check SMTP configuration
- Verify email credentials
- Check server firewall settings
- Review CodeIgniter email logs

#### 2. **Token Not Working**
- Verify token hasn't expired (1 hour limit)
- Check if token was already used
- Ensure database table exists
- Check database connection

#### 3. **Database Errors**
- Run `setup_password_reset_table.php`
- Check database permissions
- Verify table structure
- Review error logs

### Debug Mode
Enable CodeIgniter debug mode to see detailed error messages:
```php
// In application/config/config.php
$config['log_threshold'] = 4;
```

## 🔄 Integration with Frontend

### Frontend Requirements
Your frontend should:

1. **Call** `/api/auth/forgot-password` with user's email
2. **Display** success message after request
3. **Handle** the reset link from email
4. **Call** `/api/auth/reset-password` with token and new password
5. **Redirect** to login after successful reset

### Example Frontend Flow
```javascript
// 1. User enters email
const email = document.getElementById('email').value;

// 2. Send forgot password request
const response = await fetch('/api/auth/forgot-password', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ email })
});

// 3. Show success message
if (response.ok) {
    showMessage('Check your email for reset instructions');
}
```

## 📊 Database Schema

### password_reset_tokens Table
```sql
CREATE TABLE `password_reset_tokens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `expires_at` timestamp NOT NULL,
  `used` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `token` (`token`),
  KEY `expires_at` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## 🎯 Next Steps

### Immediate Actions
1. ✅ Run `setup_password_reset_table.php`
2. ✅ Configure email settings
3. ✅ Test with `test_forgot_password.php`
4. ✅ Integrate with your frontend

### Future Enhancements
- **Rate limiting**: Prevent abuse
- **Multiple email providers**: Fallback options
- **SMS verification**: Two-factor reset
- **Password strength validation**: Enhanced security
- **Account lockout**: After failed attempts

## 📞 Support

If you encounter issues:
1. Check the troubleshooting section above
2. Review CodeIgniter error logs
3. Verify database connectivity
4. Test email configuration separately

---

**🎉 Your forgot password functionality is now fully implemented and ready to use!**
