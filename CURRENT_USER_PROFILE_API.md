# Current User Profile API Documentation

This document describes the new endpoint for getting the current logged-in user's profile information, including their profile picture.

## Overview

The `/api/user/me` endpoint allows authenticated users to retrieve their own profile information based on their JWT token, without needing to know their user_id or role.

## Authentication

This endpoint requires JWT authentication.

**Header:** `Authorization: Bearer <jwt_token>`

## Endpoint

### Get Current User Profile

**Endpoint:** `GET /api/user/me`

**Description:** Retrieves the profile information of the currently logged-in user based on their JWT token.

**Headers:**
- `Authorization: Bearer <jwt_token>` (required)
- `Content-Type: application/json`

**Query Parameters:** None

**Response:**

**Success (200):**
```json
{
    "status": true,
    "message": "Current user profile retrieved successfully",
    "data": {
        "user_id": "STU689439A21701A102",
        "full_name": "John Doe",
        "email": "john.doe@example.com",
        "role": "student",
        "contact_num": "+1234567890",
        "address": "123 Main St, City, Country",
        "program": "Computer Science",
        "profile_pic": "uploads/profile/abc123.jpg",
        "cover_pic": "uploads/cover/def456.jpg",
        "status": "active",
        "last_login": "2025-08-09 10:30:00",
        "created_at": "2025-01-15 09:00:00",
        "updated_at": "2025-08-09 10:30:00"
    }
}
```

**Error Responses:**

**401 Unauthorized:**
```json
{
    "status": false,
    "message": "Authentication required. Please login."
}
```

**404 Not Found:**
```json
{
    "status": false,
    "message": "User not found"
}
```

**500 Internal Server Error:**
```json
{
    "status": false,
    "message": "Failed to retrieve user profile: [error details]"
}
```

## Usage Examples

### JavaScript/Fetch API
```javascript
const response = await fetch('/api/user/me', {
    method: 'GET',
    headers: {
        'Authorization': `Bearer ${jwtToken}`,
        'Content-Type': 'application/json'
    }
});

const data = await response.json();

if (data.status) {
    console.log('User Profile:', data.data);
    console.log('Profile Picture:', data.data.profile_pic);
    
    // Construct image URL
    if (data.data.profile_pic) {
        const imageUrl = `/image/profile/${data.data.profile_pic.replace('uploads/profile/', '')}`;
        console.log('Profile Image URL:', imageUrl);
    }
}
```

### cURL
```bash
curl -X GET "http://localhost/scms_new/api/user/me" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json"
```

### Postman
1. **Method:** GET
2. **URL:** `{{base_url}}/api/user/me`
3. **Headers:**
   - `Authorization: Bearer {{jwt_token}}`
   - `Content-Type: application/json`

## Profile Picture Access

Once you have the profile picture path from the response, you can access the image using the image serving endpoint:

**Image URL Format:** `{{base_url}}/image/profile/{filename}`

**Example:**
- Profile pic path: `uploads/profile/abc123.jpg`
- Image URL: `{{base_url}}/image/profile/abc123.jpg`

## Security Features

1. **Authentication Required:** Only authenticated users can access their profile
2. **Token-based:** Uses JWT token to identify the current user
3. **Password Excluded:** The password field is automatically removed from the response
4. **User Isolation:** Users can only access their own profile data

## Testing

You can test this endpoint using the provided test file:
- **Test File:** `test_current_user_profile.html`
- **Access:** `http://localhost/scms_new/test_current_user_profile.html`

## Comparison with Other Endpoints

| Endpoint | Purpose | Authentication | Parameters Required |
|----------|---------|----------------|-------------------|
| `/api/user/me` | Get current user profile | JWT Token | None |
| `/api/user` | Get specific user profile | JWT Token | `role`, `user_id` |
| `/api/validate-token` | Validate token + basic info | JWT Token | None |

## Benefits

1. **Convenience:** No need to know user_id or role
2. **Security:** Users can only access their own data
3. **Complete Profile:** Returns all user information including profile pictures
4. **Token-based:** Works seamlessly with existing JWT authentication

## Error Handling

The endpoint includes comprehensive error handling for:
- Missing or invalid JWT tokens
- User not found in database
- Database connection issues
- Server errors

## Integration Notes

This endpoint is perfect for:
- User profile pages
- Navigation menus showing user info
- Profile picture display
- User settings pages
- Any feature that needs current user information
