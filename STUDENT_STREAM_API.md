# Student Classroom Stream API Documentation

## Overview
This endpoint allows students to view stream posts from teachers in classrooms they are enrolled in. Students can only view published posts (not drafts or scheduled posts) and have limited visibility compared to teachers.

## Endpoint
```
GET /api/student/classroom/{class_code}/stream
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

## Response Format

### Success Response (200)
```json
{
  "status": true,
  "message": "Stream posts retrieved successfully",
  "data": [
    {
      "id": 1,
      "user_name": "Christian S. Mallari",
      "user_avatar": "uploads/profile/teacher.jpg",
      "created_at": "2024-01-15 10:30:00",
      "is_pinned": true,
      "title": "Important Announcement",
      "content": "Please submit your assignments by Friday.",
      "like_count": 5,
      "attachment_url": "uploads/announcement/document.pdf",
      "attachment_type": "pdf",
      "attachment_serving_url": "http://localhost:3308/scms_new_backup/index.php/api/files/announcement/document.pdf",
      "attachment_file_type": "application/pdf"
    },
    {
      "id": 2,
      "user_name": "Christian S. Mallari",
      "user_avatar": "uploads/profile/teacher.jpg",
      "created_at": "2024-01-14 15:45:00",
      "is_pinned": false,
      "title": "Class Schedule Update",
      "content": "Next week's schedule has been updated.",
      "like_count": 3,
      "attachment_url": null,
      "attachment_type": null,
      "attachment_serving_url": null,
      "attachment_file_type": null
    }
  ]
}
```

### Error Responses

#### 401 Unauthorized
```json
{
  "status": false,
  "message": "Authentication required",
  "data": null
}
```

#### 403 Forbidden
```json
{
  "status": false,
  "message": "Access denied. You must be enrolled in this class to view its stream.",
  "data": null
}
```

#### 404 Not Found
```json
{
  "status": false,
  "message": "Classroom not found",
  "data": null
}
```

#### 500 Internal Server Error
```json
{
  "status": false,
  "message": "Error retrieving stream posts: [error details]",
  "data": null
}
```

## Features

### Access Control
1. **Authentication Required**: Only authenticated students can access
2. **Enrollment Verification**: Students can only view streams from classes they're enrolled in
3. **Post Filtering**: Students only see published posts (not drafts or scheduled posts)

### Post Visibility
- **Published Posts**: Students can see all published stream posts
- **Drafts**: Hidden from students
- **Scheduled Posts**: Hidden from students until published
- **Private Posts**: Students only see posts where `visible_to_student_ids` is null/empty or contains their student ID

### Data Privacy
- **Like Count**: Students can see how many likes a post has, but not who liked it
- **Author Information**: Students can see the teacher's name and avatar
- **Attachments**: Students can access attachments through serving URLs

### Sorting
- **Pinned Posts**: Pinned posts appear first
- **Chronological**: Posts are sorted by creation date (newest first)

## Usage Examples

### JavaScript/Fetch
```javascript
const token = 'your_jwt_token_here';
const classCode = 'ABC123';

fetch(`/api/student/classroom/${classCode}/stream`, {
  method: 'GET',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json'
  }
})
.then(response => response.json())
.then(data => {
  if (data.status) {
    console.log('Stream posts:', data.data);
    data.data.forEach(post => {
      console.log(`Post by ${post.user_name}: ${post.title}`);
      console.log(`Likes: ${post.like_count}`);
    });
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
const classCode = 'ABC123';

axios.get(`/api/student/classroom/${classCode}/stream`, {
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json'
  }
})
.then(response => {
  const data = response.data;
  if (data.status) {
    console.log('Stream posts:', data.data);
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
curl -X GET \
  "http://localhost:3308/scms_new_backup/index.php/api/student/classroom/ABC123/stream" \
  -H "Authorization: Bearer your_jwt_token_here" \
  -H "Content-Type: application/json"
```

## Data Structure

### Stream Post Object
- `id`: Post database ID
- `user_name`: Author's full name
- `user_avatar`: Author's profile picture URL (can be null)
- `created_at`: Post creation date and time
- `is_pinned`: Whether the post is pinned (true/false)
- `title`: Post title
- `content`: Post content
- `like_count`: Number of likes (students can't see who liked)
- `attachment_url`: Original attachment URL (can be null)
- `attachment_type`: File type of attachment (can be null)
- `attachment_serving_url`: URL to access the attachment (can be null)
- `attachment_file_type`: MIME type of attachment (can be null)

## Security Features

1. **Authentication Required**: Only authenticated students can access
2. **Enrollment Verification**: Students can only view classes they're enrolled in
3. **Post Filtering**: Students only see appropriate posts (published, not drafts/scheduled)
4. **Privacy Protection**: Students can't see who liked posts, only the count
5. **Error Handling**: Proper error messages without exposing sensitive data

## Related Endpoints

- `GET /api/teacher/classroom/{class_code}/stream` - Teacher view of streams (full access)
- `GET /api/student/my-classes` - Get student's enrolled classes
- `GET /api/student/classroom/{class_code}/people` - Get class members
- `POST /api/student/join-class` - Join a class
- `DELETE /api/student/leave-class` - Leave a class

## Testing

To test this endpoint:

1. Ensure you have a valid student JWT token
2. Make sure you're enrolled in a class
3. Use the class code to access the stream
4. Verify that you can see published posts but not drafts or scheduled posts

Example test request:
```bash
curl -X GET \
  "http://localhost:3308/scms_new_backup/index.php/api/student/classroom/YOUR_CLASS_CODE/stream" \
  -H "Authorization: Bearer YOUR_STUDENT_TOKEN" \
  -H "Content-Type: application/json"
```

## Notes

- Students cannot create, edit, or delete stream posts
- Students cannot like, comment, or interact with posts
- Students only see posts that are published and visible to them
- The endpoint respects the `visible_to_student_ids` field for private posts
- Attachments are served through a secure file serving system 