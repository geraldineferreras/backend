# Change Password Implementation

## Overview
This document describes the implementation of the change password functionality for the SCMS (Student Class Management System) backend. The feature allows authenticated users (teachers, students, and admins) to change their passwords securely.

## API Endpoint

### Change Password
- **URL:** `/api/auth/change-password`
- **Method:** `POST`
- **Authentication:** Required (JWT token in Authorization header)

### Request Body
```json
{
    "current_password": "string",
    "new_password": "string", 
    "confirm_password": "string"
}
```

### Response Format
**Success (200):**
```json
{
    "status": true,
    "message": "Password changed successfully",
    "user_role": "teacher|student|admin"
}
```

**Error (400/401/404/500):**
```json
{
    "status": false,
    "message": "Error description"
}
```

## Implementation Details

### 1. Route Configuration
Added to `application/config/routes.php`:
```php
$route['api/auth/change-password']['post'] = 'api/auth/change_password';
$route['api/auth/change-password']['options'] = 'api/auth/options';
```

### 2. Controller Method
Added `change_password()` method to `application/controllers/api/Auth.php`:

#### Features:
- **Authentication Check:** Verifies user is logged in via session
- **Input Validation:** Validates all required fields
- **Password Requirements:** Enforces minimum 8 characters with letters + numbers
- **Current Password Verification:** Confirms user knows their current password
- **Password Confirmation:** Ensures new password is typed correctly
- **Duplicate Prevention:** Prevents setting the same password
- **Secure Hashing:** Uses BCRYPT for password hashing
- **Audit Logging:** Logs password change events for security

#### Validation Rules:
1. **Required Fields:** All three password fields must be provided
2. **Password Length:** New password must be at least 8 characters
3. **Password Complexity:** Must contain both letters and numbers
4. **Password Match:** New password and confirm password must match
5. **Current Password:** Must be correct
6. **Different Password:** New password must be different from current

### 3. Security Features

#### Password Hashing
- Uses PHP's `password_hash()` with BCRYPT algorithm
- Automatically generates secure salt
- Industry-standard hashing method

#### Token Validation
- Requires valid JWT token in Authorization header
- Supports Bearer token format: `Authorization: Bearer {token}`
- Prevents unauthorized password changes
- Maintains user context throughout the process

#### Input Sanitization
- Trims whitespace from all inputs
- Validates JSON format
- Prevents injection attacks

#### Audit Logging
- Logs all password change attempts
- Records IP address and user agent
- Tracks user role and ID for security monitoring

## Usage Examples

### Frontend Integration (JavaScript)
```javascript
async function changePassword(currentPassword, newPassword, confirmPassword) {
    try {
        const response = await fetch('/api/auth/change-password', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                current_password: currentPassword,
                new_password: newPassword,
                confirm_password: confirmPassword
            })
        });
        
        const data = await response.json();
        
        if (response.ok) {
            console.log('Password changed successfully:', data.message);
            // Handle success (e.g., show success message, close modal)
        } else {
            console.error('Password change failed:', data.message);
            // Handle error (e.g., show error message)
        }
    } catch (error) {
        console.error('Network error:', error);
        // Handle network errors
    }
}
```

### cURL Example
```bash
curl -X POST http://localhost/api/auth/change-password \
  -H "Content-Type: application/json" \
  -d '{
    "current_password": "oldpassword123",
    "new_password": "newpassword456",
    "confirm_password": "newpassword456"
  }'
```

## Error Handling

### Common Error Responses

#### 400 Bad Request
- Missing required fields
- Password validation failures
- Current password incorrect
- New password same as current

#### 401 Unauthorized
- User not authenticated
- Session expired

#### 404 Not Found
- User record not found

#### 500 Internal Server Error
- Database update failures
- System errors

### Error Messages
- Clear, user-friendly error descriptions
- Specific validation failure details
- Security-conscious error information

## Database Changes
No new database tables or columns are required. The existing `users` table is used to store the updated password hash.

## Testing

### Test File
Created `test_change_password.php` for testing the endpoint:
- HTML form interface
- Client-side validation
- API response display
- Error handling demonstration

### Test Scenarios
1. **Valid Password Change:** Correct current password, valid new password
2. **Invalid Current Password:** Wrong current password
3. **Password Mismatch:** New password and confirm don't match
4. **Weak Password:** Password doesn't meet requirements
5. **Duplicate Password:** New password same as current
6. **Unauthenticated:** No valid JWT token
7. **Invalid Token:** Expired or malformed token

## Security Considerations

### Password Requirements
- Minimum 8 characters
- Must contain letters and numbers
- Consider special characters for enhanced security

### Token Management
- Requires valid JWT token in Authorization header
- Supports Bearer token format
- Prevents unauthorized access
- Maintains user context

### Audit Trail
- All password changes are logged
- IP address and user agent tracking
- User role and ID recording

### Input Validation
- Server-side validation (primary)
- Client-side validation (secondary)
- JSON format validation
- SQL injection prevention

## Integration with Frontend

### Modal Integration
The API is designed to work with your existing "Change Password" modal:
- Handles all validation requirements
- Provides clear error messages
- Supports the password requirements display
- Works with the existing form structure

### Error Display
Frontend can display specific error messages:
- Field-specific validation errors
- Authentication errors
- System errors

### Success Handling
On successful password change:
- Show success message
- Close modal
- Optionally redirect or refresh

## Future Enhancements

### Potential Improvements
1. **Password History:** Prevent reuse of recent passwords
2. **Rate Limiting:** Prevent brute force attempts
3. **Email Notification:** Notify user of password change
4. **Password Strength Meter:** Real-time password strength feedback
5. **Multi-factor Verification:** Require 2FA code for password changes

### API Extensions
1. **Bulk Password Reset:** Admin functionality for multiple users
2. **Password Expiry:** Force password changes after time period
3. **Password Policy:** Configurable password requirements

## Troubleshooting

### Common Issues

#### 404 Not Found
- Check if route is properly configured
- Verify controller method exists
- Check for typos in URL

#### 401 Unauthorized
- Ensure user is logged in and has valid JWT token
- Check Authorization header format: `Bearer {token}`
- Verify token is not expired
- Check token_lib configuration

#### 500 Internal Server Error
- Check database connection
- Verify User_model exists
- Check error logs for details

### Debug Steps
1. Verify route configuration
2. Check controller method
3. Test with valid session
4. Review error logs
5. Validate input data

## Conclusion
The change password functionality provides a secure, user-friendly way for all user types (teachers, students, admins) to update their passwords. The implementation follows security best practices and integrates seamlessly with the existing authentication system.
