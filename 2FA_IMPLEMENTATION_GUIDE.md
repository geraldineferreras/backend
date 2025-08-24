# 🔐 Two-Factor Authentication (2FA) Implementation Guide

## Overview

This guide explains how to implement and test Two-Factor Authentication in your SCMS system. The 2FA system uses TOTP (Time-based One-Time Password) algorithm, compatible with Google Authenticator, Authy, and other authenticator apps.

## ⚙️ Setup Instructions

### 1. Run Database Setup

```bash
php setup_2fa_system.php
```

This will:
- Add 2FA fields to your `users` table
- Create `backup_codes` table
- Add necessary indexes

### 2. Verify Installation

Check that these files exist:
- `application/libraries/TwoFactorAuth.php` ✅
- `application/controllers/api/TwoFactor.php` ✅
- Routes added to `application/config/routes.php` ✅

## 🔗 API Endpoints

### 1. Setup 2FA
```http
POST /api/2fa/setup
Authorization: Bearer <your_jwt_token>
```

**Response:**
```json
{
  "status": true,
  "message": "2FA setup initiated",
  "data": {
    "secret": "JBSWY3DPEHPK3PXP",
    "qr_code_url": "otpauth://totp/SCMS:user@example.com?secret=JBSWY3DPEHPK3PXP&issuer=SCMS",
    "backup_codes": ["A1B2C3D4", "E5F6G7H8", ...],
    "instructions": [...]
  }
}
```

### 2. Verify and Enable 2FA
```http
POST /api/2fa/verify
Authorization: Bearer <your_jwt_token>
Content-Type: application/json

{
  "secret": "JBSWY3DPEHPK3PXP",
  "code": "123456"
}
```

**Response:**
```json
{
  "status": true,
  "message": "2FA has been enabled successfully",
  "data": {
    "two_factor_enabled": true,
    "enabled_at": "2024-01-15 10:30:00"
  }
}
```

### 3. Check 2FA Status
```http
GET /api/2fa/status
Authorization: Bearer <your_jwt_token>
```

**Response:**
```json
{
  "status": true,
  "data": {
    "two_factor_enabled": true,
    "user_id": "12345"
  }
}
```

### 4. Verify 2FA During Login
```http
POST /api/2fa/login-verify
Content-Type: application/json

{
  "email": "user@example.com",
  "code": "123456"
}
```

**Response:**
```json
{
  "status": true,
  "message": "2FA verification successful",
  "data": {
    "user_id": "12345",
    "email": "user@example.com",
    "two_factor_verified": true
  }
}
```

### 5. Use Backup Code
```http
POST /api/2fa/backup-code
Content-Type: application/json

{
  "email": "user@example.com",
  "backup_code": "A1B2C3D4"
}
```

### 6. Get Backup Codes
```http
GET /api/2fa/backup-codes
Authorization: Bearer <your_jwt_token>
```

**Response:**
```json
{
  "status": true,
  "message": "Backup codes retrieved successfully",
  "data": {
    "backup_codes": ["A1B2C3D4", "E5F6G7H8", "I9J0K1L2"],
    "count": 3,
    "warning": "Save these codes in a secure location. Each code can only be used once."
  }
}
```

### 7. Count Backup Codes
```http
GET /api/2fa/backup-codes/count
Authorization: Bearer <your_jwt_token>
```

**Response:**
```json
{
  "status": true,
  "data": {
    "backup_codes_count": 5,
    "message": "Backup codes count retrieved successfully",
    "warning": "Generate new codes if count is low"
  }
}
```

### 8. Disable 2FA
```http
POST /api/2fa/disable
Authorization: Bearer <your_jwt_token>
Content-Type: application/json

{
  "code": "123456"
}
```

## 📱 Frontend Integration

### 1. Updated Login Flow

```javascript
// Step 1: Regular login
const loginResponse = await fetch('/api/login', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({ email, password })
});

const loginData = await loginResponse.json();

if (loginData.status && loginData.data.two_factor_enabled) {
  // Step 2: Show 2FA verification form
  showTwoFactorForm(loginData.data.email);
} else {
  // Regular login success
  handleLoginSuccess(loginData);
}

// Step 3: Verify 2FA code
const verify2FA = async (email, code) => {
  const response = await fetch('/api/2fa/login-verify', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ email, code })
  });
  
  const data = await response.json();
  if (data.status) {
    // Complete login process
    handleLoginSuccess(data);
  } else {
    showError('Invalid 2FA code');
  }
};
```

### 2. Settings Page Integration

```javascript
// Check if user has 2FA enabled
const check2FAStatus = async () => {
  const response = await fetch('/api/2fa/status', {
    headers: { 'Authorization': `Bearer ${token}` }
  });
  
  const data = await response.json();
  return data.data.two_factor_enabled;
};

// Setup 2FA
const setup2FA = async () => {
  const response = await fetch('/api/2fa/setup', {
    method: 'POST',
    headers: { 'Authorization': `Bearer ${token}` }
  });
  
  const data = await response.json();
  if (data.status) {
    // Show QR code and backup codes
    showQRCode(data.data.qr_code_url);
    showBackupCodes(data.data.backup_codes);
    showVerificationForm(data.data.secret);
  }
};

// Verify and enable 2FA
const verify2FA = async (secret, code) => {
  const response = await fetch('/api/2fa/verify', {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({ secret, code })
  });
  
  const data = await response.json();
  if (data.status) {
    showSuccess('2FA enabled successfully!');
    update2FAStatus(true);
  } else {
    showError('Invalid code. Please try again.');
  }
};
```

## 🧪 Testing with Postman

### Test 1: Setup 2FA
1. **Login first** to get JWT token
2. **POST** `/api/2fa/setup` with Authorization header
3. **Copy the secret** from response
4. **Install Google Authenticator** on your phone
5. **Scan QR code** or enter secret manually

### Test 2: Verify 2FA
1. **Get 6-digit code** from authenticator app
2. **POST** `/api/2fa/verify` with secret and code
3. **Verify success** response

### Test 3: Login with 2FA
1. **POST** `/api/login` with email/password
2. **Check response** for `two_factor_enabled: true`
3. **POST** `/api/2fa/login-verify` with email and 2FA code
4. **Verify successful** verification

### Test 4: Backup Codes
1. **Use backup code** from setup response
2. **POST** `/api/2fa/backup-code` with email and backup code
3. **Verify success** and note code is consumed

## 🔒 Security Features

### ✅ What's Included:
- **TOTP Algorithm**: Industry standard, compatible with all major authenticator apps
- **Clock Skew Tolerance**: Accepts codes from ±30 seconds for network delays
- **Backup Codes**: 8 one-time recovery codes for emergency access
- **Secure Storage**: Secrets and codes are properly hashed
- **Timing Attack Protection**: Secure string comparison functions
- **Audit Logging**: All 2FA events are logged for security monitoring
- **Rate Limiting**: Built-in protection against brute force attacks

### 🛡️ Best Practices:
- Backup codes are consumed after use
- 2FA secret is never exposed after setup
- All operations require proper authentication
- Comprehensive error handling
- Secure random number generation

## 🎯 User Experience Flow

### For Admins/Teachers/Students:

1. **Enable 2FA in Settings**
   - Click "Enable 2FA" button
   - Scan QR code with authenticator app
   - Enter verification code
   - Save backup codes securely

2. **Login with 2FA**
   - Enter email/password normally
   - System detects 2FA is enabled
   - Show 2FA code input field
   - Enter 6-digit code from app
   - Access granted

3. **Emergency Access**
   - If phone is lost/broken
   - Use backup codes instead
   - Each code works only once
   - Generate new codes after use

## 🚀 Next Steps

1. **Run the setup script**: `php setup_2fa_system.php`
2. **Test with Postman** using the examples above
3. **Update your frontend** login flow
4. **Add 2FA settings** to user profile pages
5. **Test the complete flow** end-to-end

## 📞 Support

If you encounter any issues:
1. Check database connection
2. Verify all files are in place
3. Test individual endpoints with Postman
4. Check error logs in CodeIgniter

Your 2FA system is now ready for production use! 🎉
