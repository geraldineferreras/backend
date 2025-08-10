# Student Stream Comments API Documentation

## Overview
This document describes the student stream comment functionality, allowing students to add, view, edit, and delete comments on stream posts in classrooms they are enrolled in.

## Endpoints

### 1. Add Comment
**POST** `/api/student/classroom/{class_code}/stream/{stream_id}/comment`

Adds a new comment to a stream post.

#### Parameters
- `class_code` (path): The class code of the classroom
- `stream_id` (path): The ID of the stream post

#### Request Headers
```
Authorization: Bearer <jwt_token>
Content-Type: application/json
```

#### Request Body
```json
{
  "comment": "This is my comment on the post"
}
```

#### Response
**Success (200)**
```json
{
  "status": true,
  "message": "Comment added successfully",
  "data": [
    {
      "id": 1,
      "comment": "This is my comment on the post",
      "created_at": "2024-01-15 10:30:00",
      "user_id": "2021302596",
      "user_name": "John Doe",
      "user_avatar": "uploads/profile/profile_123.jpg"
    }
  ]
}
```

**Error (400)**
```json
{
  "status": false,
  "message": "Comment is required",
  "data": null
}
```

**Error (403)**
```json
{
  "status": false,
  "message": "Access denied. You must be enrolled in this class to comment.",
  "data": null
}
```

### 2. Get All Comments
**GET** `/api/student/classroom/{class_code}/stream/{stream_id}/comments`

Retrieves all comments for a specific stream post.

#### Parameters
- `class_code` (path): The class code of the classroom
- `stream_id` (path): The ID of the stream post

#### Request Headers
```
Authorization: Bearer <jwt_token>
Content-Type: application/json
```

#### Response
**Success (200)**
```json
{
  "status": true,
  "message": "Comments retrieved successfully",
  "data": [
    {
      "id": 1,
      "comment": "Great post!",
      "created_at": "2024-01-15 10:30:00",
      "user_id": "2021302596",
      "user_name": "John Doe",
      "user_avatar": "uploads/profile/profile_123.jpg"
    },
    {
      "id": 2,
      "comment": "I agree with this",
      "created_at": "2024-01-15 11:00:00",
      "user_id": "2021305889",
      "user_name": "Jane Smith",
      "user_avatar": null
    }
  ]
}
```

### 3. Edit Comment
**PUT** `/api/student/classroom/{class_code}/stream/{stream_id}/comment/{comment_id}`

Updates an existing comment (only if it belongs to the current user).

#### Parameters
- `class_code` (path): The class code of the classroom
- `stream_id` (path): The ID of the stream post
- `comment_id` (path): The ID of the comment to edit

#### Request Headers
```
Authorization: Bearer <jwt_token>
Content-Type: application/json
```

#### Request Body
```json
{
  "comment": "Updated comment text"
}
```

#### Response
**Success (200)**
```json
{
  "status": true,
  "message": "Comment updated successfully",
  "data": [
    {
      "id": 1,
      "comment": "Updated comment text",
      "created_at": "2024-01-15 10:30:00",
      "user_id": "2021302596",
      "user_name": "John Doe",
      "user_avatar": "uploads/profile/profile_123.jpg"
    }
  ]
}
```

**Error (403)**
```json
{
  "status": false,
  "message": "Failed to update comment (maybe not your comment)",
  "data": null
}
```

### 4. Delete Comment
**DELETE** `/api/student/classroom/{class_code}/stream/{stream_id}/comment/{comment_id}`

Deletes a comment (only if it belongs to the current user).

#### Parameters
- `class_code` (path): The class code of the classroom
- `stream_id` (path): The ID of the stream post
- `comment_id` (path): The ID of the comment to delete

#### Request Headers
```
Authorization: Bearer <jwt_token>
Content-Type: application/json
```

#### Response
**Success (200)**
```json
{
  "status": true,
  "message": "Comment deleted successfully",
  "data": []
}
```

**Error (403)**
```json
{
  "status": false,
  "message": "Failed to delete comment (maybe not your comment)",
  "data": null
}
```

## Security Features

1. **Authentication Required**: All endpoints require valid student JWT token
2. **Enrollment Verification**: Students can only comment on posts in classes they're enrolled in
3. **Active Enrollment**: Only students with active enrollment status can comment
4. **Ownership Validation**: Students can only edit/delete their own comments
5. **Post Validation**: Comments can only be added to existing stream posts
6. **Comment Permission**: Comments can only be added to posts that allow comments

## Database Schema

### Table: `classroom_stream_comments`
```sql
CREATE TABLE IF NOT EXISTS `classroom_stream_comments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `stream_id` int(11) NOT NULL,
  `user_id` varchar(50) NOT NULL,
  `comment` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_stream_id` (`stream_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `fk_stream_comments_stream` FOREIGN KEY (`stream_id`) REFERENCES `classroom_stream` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_stream_comments_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

## Usage Examples

### JavaScript/Fetch

#### Add Comment
```javascript
const token = 'your_jwt_token_here';
const classCode = 'ABC123';
const streamId = 456;

const commentData = {
  comment: 'This is a great post!'
};

fetch(`/api/student/classroom/${classCode}/stream/${streamId}/comment`, {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json'
  },
  body: JSON.stringify(commentData)
})
.then(response => response.json())
.then(data => {
  if (data.status) {
    console.log('Comment added:', data.data);
  } else {
    console.error('Error:', data.message);
  }
});
```

#### Get Comments
```javascript
fetch(`/api/student/classroom/${classCode}/stream/${streamId}/comments`, {
  method: 'GET',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json'
  }
})
.then(response => response.json())
.then(data => {
  if (data.status) {
    console.log('Comments:', data.data);
  } else {
    console.error('Error:', data.message);
  }
});
```

#### Edit Comment
```javascript
const commentId = 789;
const updatedComment = {
  comment: 'Updated comment text'
};

fetch(`/api/student/classroom/${classCode}/stream/${streamId}/comment/${commentId}`, {
  method: 'PUT',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json'
  },
  body: JSON.stringify(updatedComment)
})
.then(response => response.json())
.then(data => {
  if (data.status) {
    console.log('Comment updated:', data.data);
  } else {
    console.error('Error:', data.message);
  }
});
```

#### Delete Comment
```javascript
fetch(`/api/student/classroom/${classCode}/stream/${streamId}/comment/${commentId}`, {
  method: 'DELETE',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json'
  }
})
.then(response => response.json())
.then(data => {
  if (data.status) {
    console.log('Comment deleted successfully');
  } else {
    console.error('Error:', data.message);
  }
});
```

### Axios

#### Add Comment
```javascript
const axios = require('axios');

const token = 'your_jwt_token_here';
const classCode = 'ABC123';
const streamId = 456;

const commentData = {
  comment: 'This is a great post!'
};

axios.post(`/api/student/classroom/${classCode}/stream/${streamId}/comment`, commentData, {
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json'
  }
})
.then(response => {
  if (response.data.status) {
    console.log('Comment added:', response.data.data);
  }
})
.catch(error => {
  console.error('Error:', error.response.data.message);
});
```

## Testing in Postman

### Prerequisites
1. Valid student JWT token
2. Class code where student is enrolled
3. Stream post ID that allows comments

### Test Cases

#### 1. Add Comment
- **Method**: POST
- **URL**: `{{base_url}}/api/student/classroom/{{class_code}}/stream/{{stream_id}}/comment`
- **Headers**: 
  - `Authorization: Bearer {{student_token}}`
  - `Content-Type: application/json`
- **Body**:
```json
{
  "comment": "Test comment from student"
}
```

#### 2. Get Comments
- **Method**: GET
- **URL**: `{{base_url}}/api/student/classroom/{{class_code}}/stream/{{stream_id}}/comments`
- **Headers**: 
  - `Authorization: Bearer {{student_token}}`

#### 3. Edit Comment
- **Method**: PUT
- **URL**: `{{base_url}}/api/student/classroom/{{class_code}}/stream/{{stream_id}}/comment/{{comment_id}}`
- **Headers**: 
  - `Authorization: Bearer {{student_token}}`
  - `Content-Type: application/json`
- **Body**:
```json
{
  "comment": "Updated comment text"
}
```

#### 4. Delete Comment
- **Method**: DELETE
- **URL**: `{{base_url}}/api/student/classroom/{{class_code}}/stream/{{stream_id}}/comment/{{comment_id}}`
- **Headers**: 
  - `Authorization: Bearer {{student_token}}`

## Error Handling

### Common Error Codes
- **400**: Bad Request (missing comment text, invalid JSON)
- **401**: Unauthorized (invalid or missing JWT token)
- **403**: Forbidden (not enrolled, not owner of comment, comments not allowed)
- **404**: Not Found (classroom or stream post not found)
- **500**: Internal Server Error (database or server issues)

### Error Response Format
```json
{
  "status": false,
  "message": "Error description",
  "data": null
}
```

## Related Endpoints

- `GET /api/student/classroom/{class_code}/stream` - View stream posts
- `POST /api/student/classroom/{class_code}/stream` - Create stream posts
- `GET /api/student/my-classes` - Get enrolled classes
- `GET /api/student/classroom/{class_code}/people` - Get class members

## Notes

- Students can only comment on posts in classes they're enrolled in
- Students can only edit/delete their own comments
- Comments are automatically ordered by creation time (oldest first)
- All comment operations return the updated list of comments for the post
- The system respects the `allow_comments` setting on stream posts
- Comments include user information (name, avatar) for display purposes
