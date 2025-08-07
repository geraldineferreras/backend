# Student Stream Posting API Documentation

## Overview
This endpoint allows students to create stream posts in classrooms they are enrolled in. Students can post announcements, share content, and interact with their class stream.

## Endpoint
```
POST /api/student/classroom/{class_code}/stream
```

## Authentication
- Requires student authentication
- Include JWT token in Authorization header: `Authorization: Bearer <token>`

## Parameters
- `class_code` (path parameter): The class code of the classroom

## Request Headers
```
Authorization: Bearer <jwt_token>
Content-Type: application/json
```

## Request Body
```json
{
  "title": "Announcement",
  "content": "Please pass your assignment 2 on time!",
  "is_draft": 0,
  "is_scheduled": 0,
  "scheduled_at": "",
  "allow_comments": 1,
  "attachment_type": "file",
  "attachment_url": "uploads/announcements/exam.pdf",
  "student_ids": ["2021302596", "2021305889", "2021305973"]
}
```

### Request Body Fields

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `content` | string | **Yes** | The main content of the post |
| `title` | string | No | Optional title for the post |
| `allow_comments` | boolean | No | Whether to allow comments (default: true) |
| `is_draft` | boolean | No | Whether to save as draft (default: false) |
| `is_scheduled` | boolean | No | Whether to schedule the post (default: false) |
| `scheduled_at` | string | No | Scheduled date/time (ISO format) |
| `attachment_type` | string | No | Type of attachment (link, file, etc.) |
| `attachment_url` | string | No | URL or path to attachment |
| `student_ids` | array | No | Array of user_ids to target specific students (if not provided, targets all students) |

## Response Format

### Success Response (201)
```json
{
  "status": true,
  "message": "Post created successfully",
  "data": {
    "id": 123,
    "class_code": "J56NHD",
    "user_id": "STU001",
    "title": "Optional Post Title",
    "content": "This is the main content of the post",
    "is_draft": 0,
    "is_scheduled": 0,
    "scheduled_at": null,
    "allow_comments": 1,
    "attachment_type": "link",
    "attachment_url": "https://example.com/file.pdf",
    "created_at": "2024-01-15 10:30:00",
    "updated_at": "2024-01-15 10:30:00"
  }
}
```

### Error Responses

#### 400 Bad Request
```json
{
  "status": false,
  "message": "Content is required",
  "data": null,
  "status_code": 400
}
```

#### 401 Unauthorized
```json
{
  "status": false,
  "message": "Authentication required",
  "data": null,
  "status_code": 401
}
```

#### 403 Forbidden
```json
{
  "status": false,
  "message": "Access denied. You must be enrolled in this class to post to its stream.",
  "data": null,
  "status_code": 403
}
```

#### 404 Not Found
```json
{
  "status": false,
  "message": "Classroom not found",
  "data": null,
  "status_code": 404
}
```

#### 500 Internal Server Error
```json
{
  "status": false,
  "message": "Error creating post: [error details]",
  "data": null,
  "status_code": 500
}
```

## Features

### Access Control
1. **Authentication Required**: Only authenticated students can post
2. **Enrollment Verification**: Students can only post to classes they're enrolled in
3. **Active Enrollment**: Only students with active enrollment status can post

### Post Types
- **Regular Posts**: Immediate publication
- **Draft Posts**: Saved for later editing
- **Scheduled Posts**: Published at a specific time

### Notifications
- **Teacher Notification**: Teacher always receives notification when student posts
- **Targeted Student Notifications**: If `student_ids` provided, only those students receive notifications
- **All Student Notifications**: If no `student_ids` provided, all other students in class receive notifications
- **Draft Posts**: No notifications sent for draft posts

### Content Support
- **Text Content**: Rich text content
- **Optional Title**: Can include a title
- **Attachments**: Support for file attachments and links
- **Comments**: Can enable/disable comments

## Usage Examples

### JavaScript/Fetch
```javascript
const token = 'your_jwt_token_here';
const classCode = 'J56NHD';

const postData = {
  title: 'Announcement',
  content: 'Please pass your assignment 2 on time!',
  is_draft: 0,
  is_scheduled: 0,
  scheduled_at: '',
  allow_comments: 1,
  attachment_type: 'file',
  attachment_url: 'uploads/announcements/exam.pdf',
  student_ids: ['2021302596', '2021305889', '2021305973']
};

fetch(`/api/student/classroom/${classCode}/stream`, {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json'
  },
  body: JSON.stringify(postData)
})
.then(response => response.json())
.then(data => {
  if (data.status) {
    console.log('Post created successfully:', data.data);
  } else {
    console.error('Error:', data.message);
  }
})
.catch(error => {
  console.error('Network error:', error);
});
```

### Axios
```javascript
const axios = require('axios');

const token = 'your_jwt_token_here';
const classCode = 'J56NHD';

const postData = {
  title: 'Announcement',
  content: 'Please pass your assignment 2 on time!',
  is_draft: 0,
  is_scheduled: 0,
  scheduled_at: '',
  allow_comments: 1,
  attachment_type: 'file',
  attachment_url: 'uploads/announcements/exam.pdf',
  student_ids: ['2021302596', '2021305889', '2021305973']
};

axios.post(`/api/student/classroom/${classCode}/stream`, postData, {
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json'
  }
})
.then(response => {
  const data = response.data;
  if (data.status) {
    console.log('Post created successfully:', data.data);
  } else {
    console.error('Error:', data.message);
  }
})
.catch(error => {
  console.error('Error:', error.response?.data || error.message);
});
```

### cURL
```bash
curl -X POST \
  "http://localhost:3308/scms_new_backup/index.php/api/student/classroom/J56NHD/stream" \
  -H "Authorization: Bearer your_jwt_token_here" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Announcement",
    "content": "Please pass your assignment 2 on time!",
    "is_draft": 0,
    "is_scheduled": 0,
    "scheduled_at": "",
    "allow_comments": 1,
    "attachment_type": "file",
    "attachment_url": "uploads/announcements/exam.pdf",
    "student_ids": ["2021302596", "2021305889", "2021305973"]
  }'
```

## Data Structure

### Request Body Object
- `content`: The main content of the post (required)
- `title`: Optional title for the post
- `allow_comments`: Whether to allow comments (default: true)
- `is_draft`: Whether to save as draft (default: false)
- `is_scheduled`: Whether to schedule the post (default: false)
- `scheduled_at`: Scheduled date/time (ISO format)
- `attachment_type`: Type of attachment
- `attachment_url`: URL or path to attachment
- `student_ids`: Array of user_ids to target specific students (if not provided, targets all students)

### Response Data Object
- `id`: Post database ID
- `class_code`: Class code where post was created
- `user_id`: Student's user ID
- `title`: Post title
- `content`: Post content
- `is_draft`: Whether post is a draft
- `is_scheduled`: Whether post is scheduled
- `scheduled_at`: Scheduled date/time
- `allow_comments`: Whether comments are allowed
- `attachment_type`: Type of attachment
- `attachment_url`: URL or path to attachment
- `created_at`: Post creation date and time
- `updated_at`: Post last update date and time

## Security Features

1. **Authentication Required**: Only authenticated students can post
2. **Enrollment Verification**: Students can only post to classes they're enrolled in
3. **Active Enrollment**: Only students with active enrollment status can post
4. **Input Validation**: Required fields are validated
5. **Error Handling**: Proper error messages without exposing sensitive data

## Related Endpoints

- `GET /api/student/classroom/{class_code}/stream` - View stream posts
- `GET /api/student/my-classes` - Get student's enrolled classes
- `GET /api/student/classroom/{class_code}/people` - Get class members
- `POST /api/student/join-class` - Join a class
- `DELETE /api/student/leave-class` - Leave a class

## Testing

To test this endpoint:

1. Ensure you have a valid student JWT token
2. Make sure you're enrolled in a class
3. Use the class code to post to the stream
4. Verify that the post appears in the stream
5. Check that notifications are sent to teacher and other students

Example test request:
```bash
curl -X POST \
  "http://localhost:3308/scms_new_backup/index.php/api/student/classroom/YOUR_CLASS_CODE/stream" \
  -H "Authorization: Bearer YOUR_STUDENT_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Test Student Post",
    "content": "This is a test post from a student.",
    "is_draft": 0,
    "is_scheduled": 0,
    "scheduled_at": "",
    "allow_comments": 1,
    "attachment_type": "file",
    "attachment_url": "uploads/announcements/exam.pdf",
    "student_ids": ["2021302596", "2021305889", "2021305973"]
  }'
```

## Notes

- Students can create posts in any class they're enrolled in
- Draft posts are not visible to other users
- Scheduled posts are published at the specified time
- Notifications are sent to teacher and other students for published posts
- Students cannot edit or delete posts after creation
- Students cannot pin or like posts
- The endpoint respects the student's enrollment status
