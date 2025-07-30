# JWT Authentication Testing Guide

## Overview
This guide explains how to test the JWT authentication system implemented in the School Management System.

## JWT Implementation Features

### 1. **Token Generation**
- Tokens are automatically generated upon successful login
- Token expiration: 1 hour (3600 seconds)
- Algorithm: HS256
- Token type: Bearer

### 2. **Security Features**
- Token validation with signature verification
- Expiration time checking
- Role-based access control
- Token refresh capability

## API Endpoints

### Authentication Endpoints

#### 1. **Login** (POST `/api/login`)
```json
{
    "email": "admin@example.com",
    "password": "password123"
}
```

**Response:**
```json
{
    "status": true,
    "message": "Login successful",
    "data": {
        "role": "admin",
        "user_id": "ADM001",
        "full_name": "John Admin",
        "email": "admin@example.com",
        "status": "active",
        "last_login": "2024-01-01 12:00:00",
        "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
        "token_type": "Bearer",
        "expires_in": 3600
    }
}
```

#### 2. **Token Refresh** (POST `/api/refresh-token`)
**Headers:**
```
Authorization: Bearer <your_jwt_token>
```

**Response:**
```json
{
    "status": true,
    "message": "Token refreshed successfully",
    "data": {
        "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
        "token_type": "Bearer",
        "expires_in": 3600
    }
}
```

#### 3. **Validate Token** (GET `/api/validate-token`)
**Headers:**
```
Authorization: Bearer <your_jwt_token>
```

**Response:**
```json
{
    "status": true,
    "message": "Token is valid",
    "data": {
        "user_id": "ADM001",
        "role": "admin",
        "email": "admin@example.com",
        "full_name": "John Admin"
    }
}
```

#### 4. **Logout** (POST `/api/logout`)
**Headers:**
```
Authorization: Bearer <your_jwt_token>
```

**Response:**
```json
{
    "status": true,
    "message": "Logout successful. Please remove the token from client storage."
}
```

### Protected Endpoints

All user management endpoints now require authentication:

#### 1. **Get Users** (GET `/api/users?role=admin`)
**Headers:**
```
Authorization: Bearer <your_jwt_token>
```

#### 2. **Get User** (GET `/api/user?role=admin&user_id=ADM001`)
**Headers:**
```
Authorization: Bearer <your_jwt_token>
```

#### 3. **Update User** (PUT `/api/user`)
**Headers:**
```
Authorization: Bearer <your_jwt_token>
Content-Type: application/json
```

#### 4. **Delete User** (DELETE `/api/user`)
**Headers:**
```
Authorization: Bearer <your_jwt_token>
Content-Type: application/json
```

#### 5. **Change User Status** (POST `/api/admin/change-status`)
**Headers:**
```
Authorization: Bearer <your_jwt_token>
Content-Type: application/json
```
*Note: Admin role required*

## Testing with Postman

### Step 1: Login
1. Create a POST request to `http://localhost/scms_new/api/login`
2. Set Content-Type: `application/json`
3. Body:
```json
{
    "email": "admin@example.com",
    "password": "password123"
}
```
4. Copy the token from the response

### Step 2: Use Token
1. For subsequent requests, add header:
   ```
   Authorization: Bearer <your_token>
   ```
2. Test protected endpoints

### Step 3: Test Token Refresh
1. Create a POST request to `http://localhost/scms_new/api/refresh-token`
2. Add the Authorization header with your current token
3. Get a new token with extended expiration

### Step 4: Test Token Validation
1. Create a GET request to `http://localhost/scms_new/api/validate-token`
2. Add the Authorization header
3. Verify token is valid

## Error Responses

### 401 Unauthorized
```json
{
    "status": false,
    "message": "Authentication required. Please login."
}
```

### 403 Forbidden
```json
{
    "status": false,
    "message": "Access denied. Insufficient permissions."
}
```

### Token Expired
```json
{
    "status": false,
    "message": "Invalid or expired token"
}
```

## Security Features

### 1. **Role-Based Access Control**
- `require_admin()`: Only admin users
- `require_teacher()`: Only teacher users
- `require_student()`: Only student users
- `require_admin_or_teacher()`: Admin or teacher users
- `require_admin_or_student()`: Admin or student users

### 2. **Token Security**
- HMAC-SHA256 signature verification
- Expiration time validation
- Not-before time validation
- Secure token generation

### 3. **Helper Functions**
- `check_auth()`: Check if user is authenticated
- `require_auth()`: Require authentication
- `get_current_user_id()`: Get current user ID
- `get_current_user_role()`: Get current user role
- `can_access_user_data()`: Check data access permissions

## Client-Side Implementation

### JavaScript Example
```javascript
// Store token after login
localStorage.setItem('jwt_token', response.data.token);

// Add token to requests
const token = localStorage.getItem('jwt_token');
fetch('/api/users?role=admin', {
    headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json'
    }
});

// Handle token expiration
if (response.status === 401) {
    // Redirect to login
    localStorage.removeItem('jwt_token');
    window.location.href = '/login';
}
```

## Important Notes

1. **Token Storage**: Store tokens securely (localStorage, sessionStorage, or HTTP-only cookies)
2. **Token Expiration**: Implement automatic token refresh before expiration
3. **Logout**: Always remove tokens from client storage on logout
4. **HTTPS**: Use HTTPS in production for secure token transmission
5. **Secret Key**: Change the secret key in `Token_lib.php` for production

## Production Considerations

1. **Environment Variables**: Move secret key to environment variables
2. **Token Blacklisting**: Implement token blacklist for logout
3. **Rate Limiting**: Add rate limiting for login attempts
4. **Audit Logging**: Log authentication events
5. **CORS**: Configure CORS properly for your domain