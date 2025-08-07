# Postman Testing Guide for SCMS Notification System

## Overview

This guide provides step-by-step instructions for testing the SCMS notification system using Postman. All tests require a valid JWT token for authentication.

**Base URL**: `http://localhost/scms_new_backup/index.php`

## Prerequisites

1. **Postman Installed**: Download and install Postman from [postman.com](https://www.postman.com/)
2. **JWT Token**: You need a valid JWT token from the login endpoint
3. **Database Setup**: Ensure notification tables are created
4. **Server Running**: Make sure your XAMPP server is running

## Getting Started

### Step 1: Get JWT Token

First, you need to obtain a JWT token by logging in:

**Request**:
```
POST http://localhost/scms_new_backup/index.php/api/login
```

**Headers**:
```
Content-Type: application/json
```

**Body**:
```json
{
  "username": "your_username",
  "password": "your_password"
}
```

**Expected Response**:
```json
{
  "success": true,
  "data": {
    "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
    "user": {
      "user_id": "STU001",
      "username": "student1",
      "role": "student"
    }
  }
}
```

**Save the token** for use in subsequent requests.

### Step 2: Set Up Environment Variables

In Postman, create an environment with these variables:

| Variable | Value |
|----------|-------|
| `base_url` | `http://localhost/scms_new_backup/index.php` |
| `jwt_token` | `your_jwt_token_here` |

## Test Collection

### 1. Get User Notifications

**Request**:
```
GET {{base_url}}/api/notifications
```

**Headers**:
```
Authorization: Bearer {{jwt_token}}
Content-Type: application/json
```

**Query Parameters** (optional):
- `limit`: 20
- `offset`: 0
- `unread_only`: true

**Expected Response**:
```json
{
  "success": true,
  "data": {
    "notifications": [
      {
        "id": 1,
        "user_id": "STU001",
        "type": "announcement",
        "title": "New Class Announcement",
        "message": "There will be a class meeting tomorrow at 2 PM.",
        "related_id": 123,
        "related_type": "announcement",
        "class_code": "CS101",
        "is_read": false,
        "is_urgent": false,
        "created_at": "2024-01-15 10:30:00",
        "updated_at": "2024-01-15 10:30:00"
      }
    ],
    "unread_count": 5
  }
}
```

### 2. Mark Notification as Read

**Request**:
```
PUT {{base_url}}/api/notifications/1/read
```

**Headers**:
```
Authorization: Bearer {{jwt_token}}
Content-Type: application/json
```

**Expected Response**:
```json
{
  "success": true,
  "message": "Notification marked as read"
}
```

### 3. Mark All Notifications as Read

**Request**:
```
PUT {{base_url}}/api/notifications/mark-all-read
```

**Headers**:
```
Authorization: Bearer {{jwt_token}}
Content-Type: application/json
```

**Expected Response**:
```json
{
  "success": true,
  "message": "All notifications marked as read"
}
```

### 4. Delete Notification

**Request**:
```
DELETE {{base_url}}/api/notifications/1
```

**Headers**:
```
Authorization: Bearer {{jwt_token}}
Content-Type: application/json
```

**Expected Response**:
```json
{
  "success": true,
  "message": "Notification deleted"
}
```

### 5. Get Notification Settings

**Request**:
```
GET {{base_url}}/api/notifications/settings
```

**Headers**:
```
Authorization: Bearer {{jwt_token}}
Content-Type: application/json
```

**Expected Response**:
```json
{
  "success": true,
  "data": {
    "id": 1,
    "user_id": "STU001",
    "email_notifications": true,
    "push_notifications": true,
    "announcement_notifications": true,
    "task_notifications": true,
    "submission_notifications": true,
    "grade_notifications": true,
    "enrollment_notifications": true,
    "created_at": "2024-01-15 10:30:00",
    "updated_at": "2024-01-15 10:30:00"
  }
}
```

### 6. Update Notification Settings

**Request**:
```
PUT {{base_url}}/api/notifications/settings
```

**Headers**:
```
Authorization: Bearer {{jwt_token}}
Content-Type: application/json
```

**Body**:
```json
{
  "email_notifications": false,
  "task_notifications": false,
  "announcement_notifications": true,
  "grade_notifications": true
}
```

**Expected Response**:
```json
{
  "success": true,
  "message": "Settings updated successfully"
}
```

### 7. Get Unread Count

**Request**:
```
GET {{base_url}}/api/notifications/unread-count
```

**Headers**:
```
Authorization: Bearer {{jwt_token}}
Content-Type: application/json
```

**Expected Response**:
```json
{
  "success": true,
  "data": {
    "unread_count": 3
  }
}
```

### 8. Get Recent Notifications

**Request**:
```
GET {{base_url}}/api/notifications/recent?limit=5
```

**Headers**:
```
Authorization: Bearer {{jwt_token}}
Content-Type: application/json
```

**Expected Response**:
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "user_id": "STU001",
      "type": "announcement",
      "title": "New Class Announcement",
      "message": "There will be a class meeting tomorrow at 2 PM.",
      "related_id": 123,
      "related_type": "announcement",
      "class_code": "CS101",
      "is_read": false,
      "is_urgent": false,
      "created_at": "2024-01-15 10:30:00",
      "updated_at": "2024-01-15 10:30:00"
    }
  ]
}
```

### 9. Get Urgent Notifications

**Request**:
```
GET {{base_url}}/api/notifications/urgent
```

**Headers**:
```
Authorization: Bearer {{jwt_token}}
Content-Type: application/json
```

**Expected Response**:
```json
{
  "success": true,
  "data": [
    {
      "id": 2,
      "user_id": "STU001",
      "type": "task",
      "title": "Urgent: Assignment Due Tomorrow",
      "message": "Your assignment is due tomorrow. Please submit it on time.",
      "related_id": 456,
      "related_type": "task",
      "class_code": "CS101",
      "is_read": false,
      "is_urgent": true,
      "created_at": "2024-01-15 11:00:00",
      "updated_at": "2024-01-15 11:00:00"
    }
  ]
}
```

## Error Testing

### Test 1: Unauthorized Access

**Request**:
```
GET {{base_url}}/api/notifications
```

**Headers**:
```
Content-Type: application/json
```
(No Authorization header)

**Expected Response**:
```json
{
  "error": "Unauthorized"
}
```

**Status Code**: 401

### Test 2: Invalid Token

**Request**:
```
GET {{base_url}}/api/notifications
```

**Headers**:
```
Authorization: Bearer invalid_token_here
Content-Type: application/json
```

**Expected Response**:
```json
{
  "error": "Unauthorized"
}
```

**Status Code**: 401

### Test 3: Notification Not Found

**Request**:
```
PUT {{base_url}}/api/notifications/999/read
```

**Headers**:
```
Authorization: Bearer {{jwt_token}}
Content-Type: application/json
```

**Expected Response**:
```json
{
  "error": "Notification not found"
}
```

**Status Code**: 404

## Testing Different User Roles

### Student Testing

1. **Login as Student**:
   ```json
   {
     "username": "student1",
     "password": "password123"
   }
   ```

2. **Test Student-Specific Features**:
   - Get notifications
   - Update settings
   - Mark notifications as read

### Teacher Testing

1. **Login as Teacher**:
   ```json
   {
     "username": "teacher1",
     "password": "password123"
   }
   ```

2. **Test Teacher-Specific Features**:
   - Get notifications
   - Update settings
   - Create test notifications

### Admin Testing

1. **Login as Admin**:
   ```json
   {
     "username": "admin",
     "password": "password123"
   }
   ```

2. **Test Admin-Specific Features**:
   - Get all notifications
   - Manage system notifications

## Performance Testing

### Test 1: Large Dataset

1. Create multiple notifications in the database
2. Test pagination with different limit/offset values
3. Verify response times

**Request**:
```
GET {{base_url}}/api/notifications?limit=100&offset=0
```

### Test 2: Concurrent Requests

1. Send multiple requests simultaneously
2. Verify rate limiting is working
3. Check for any race conditions

## Integration Testing

### Test 1: Create Notification via Helper

1. Use a teacher account to create an announcement
2. Verify notifications are created for all students
3. Check email notifications are sent

### Test 2: Task Assignment

1. Create a task assignment
2. Verify notifications are sent to assigned students
3. Check urgent flag for due dates

### Test 3: Grade Updates

1. Update a student's grade
2. Verify notification is sent to the student
3. Check email notification content

## Automated Testing

### Postman Collection

Create a Postman collection with all the above requests:

1. **Pre-request Scripts**: Set up authentication
2. **Tests**: Verify response status and data
3. **Environment Variables**: Store tokens and base URL

### Example Test Script

```javascript
// Test for successful response
pm.test("Status code is 200", function () {
    pm.response.to.have.status(200);
});

// Test for success field
pm.test("Response has success field", function () {
    var jsonData = pm.response.json();
    pm.expect(jsonData).to.have.property('success');
    pm.expect(jsonData.success).to.eql(true);
});

// Test for data structure
pm.test("Response has correct data structure", function () {
    var jsonData = pm.response.json();
    pm.expect(jsonData).to.have.property('data');
    pm.expect(jsonData.data).to.have.property('notifications');
    pm.expect(jsonData.data.notifications).to.be.an('array');
});
```

## Troubleshooting

### Common Issues

1. **CORS Errors**: Ensure your server allows cross-origin requests
2. **Authentication Errors**: Check JWT token validity and expiration
3. **Database Errors**: Verify notification tables exist
4. **Email Errors**: Check SMTP configuration

### Debug Steps

1. **Check Server Logs**: Look for PHP errors in XAMPP logs
2. **Verify Database**: Check if notifications are being created
3. **Test Email**: Use the email test script
4. **Check Routes**: Verify routes are properly configured

## Best Practices

1. **Use Environment Variables**: Store tokens and URLs in environment variables
2. **Test Error Cases**: Always test error scenarios
3. **Verify Data**: Check that returned data matches expected format
4. **Clean Up**: Delete test data after testing
5. **Document Issues**: Keep track of any bugs or issues found

## Next Steps

After completing these tests:

1. **Integration with Frontend**: Test with your actual frontend application
2. **Load Testing**: Test with multiple concurrent users
3. **Security Testing**: Verify proper authentication and authorization
4. **Email Testing**: Verify email notifications are working correctly

## Support

If you encounter issues:

1. Check the troubleshooting section
2. Review server error logs
3. Verify database connectivity
4. Test with the provided test scripts 