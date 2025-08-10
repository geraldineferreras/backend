# Student Classroom Stream API Documentation

## Overview
This endpoint allows students to view stream posts from teachers in classrooms they are enrolled in. Students can only view published posts (not drafts or scheduled posts) and have limited visibility compared to teachers. **The API now supports multiple attachments for stream posts.**

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
      "attachment_type": "file",
      "attachment_serving_url": "http://localhost:3308/scms_new_backup/index.php/api/files/announcement/document.pdf",
      "attachment_file_type": "application/pdf"
    },
    {
      "id": 2,
      "user_name": "Christian S. Mallari",
      "user_avatar": "uploads/profile/teacher.jpg",
      "created_at": "2024-01-14 15:45:00",
      "is_pinned": false,
      "title": "Class Materials",
      "content": "Here are the materials for next week's class.",
      "like_count": 3,
      "attachment_type": "multiple",
      "attachments": [
        {
          "attachment_id": 1,
          "file_name": "a1b2c3d4e5f6.pdf",
          "original_name": "lecture_notes.pdf",
          "file_path": "uploads/announcement/a1b2c3d4e5f6.pdf",
          "file_size": 2048576,
          "mime_type": "application/pdf",
          "attachment_type": "file",
          "attachment_url": null,
          "serving_url": "http://localhost:3308/scms_new_backup/index.php/api/files/announcement/a1b2c3d4e5f6.pdf",
          "file_type": "application/pdf"
        },
        {
          "attachment_id": 2,
          "file_name": "f6e5d4c3b2a1.png",
          "original_name": "diagram.png",
          "file_path": "uploads/announcement/f6e5d4c3b2a1.png",
          "file_size": 512000,
          "mime_type": "image/png",
          "attachment_type": "file",
          "attachment_url": null,
          "serving_url": "http://localhost:3308/scms_new_backup/index.php/api/files/announcement/f6e5d4c3b2a1.png",
          "file_type": "image/png"
        }
      ],
      "attachment_serving_url": "http://localhost:3308/scms_new_backup/index.php/api/files/announcement/a1b2c3d4e5f6.pdf",
      "attachment_file_type": "application/pdf"
    },
    {
      "id": 3,
      "user_name": "Christian S. Mallari",
      "user_avatar": "uploads/profile/teacher.jpg",
      "created_at": "2024-01-13 09:15:00",
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

### Multiple Attachments Support

When a stream post has multiple attachments, the response includes:

- **`attachment_type`**: Set to `"multiple"`
- **`attachments`**: Array containing all attachment details
- **`attachment_serving_url`**: URL to the first attachment (for backward compatibility)
- **`attachment_file_type`**: MIME type of the first attachment (for backward compatibility)

Each attachment in the `attachments` array includes:
- `attachment_id`: Unique identifier for the attachment
- `file_name`: System-generated filename
- `original_name`: Original filename as uploaded by the teacher
- `file_path`: Internal file path
- `file_size`: File size in bytes
- `mime_type`: MIME type of the file
- `attachment_type`: Type of attachment (usually "file")
- `attachment_url`: Additional URL if applicable
- `serving_url`: Public URL to access/download the file
- `file_type`: MIME type (duplicate of mime_type for compatibility)

### Single Attachment (Backward Compatibility)

For posts with single attachments, the response maintains the original format:
- **`attachment_type`**: Set to the file type (e.g., `"file"`)
- **`attachment_url`**: Path to the single file
- **`attachment_serving_url`**: Public URL to access the file
- **`attachment_file_type`**: MIME type of the file

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

### Multiple Attachments Support
- **Multiple Files**: Students can view posts with multiple file attachments
- **File Details**: Each attachment shows filename, size, type, and download URL
- **Backward Compatibility**: Single attachment posts continue to work as before
- **File Access**: Students can download/view all attachments through serving URLs

### Data Privacy
- **Like Count**: Students can see how many likes a post has, but not who liked it
- **Author Information**: Students can see the teacher's name and avatar
- **Attachments**: Students can access all attachments through serving URLs

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
      
      // Handle multiple attachments
      if (post.attachment_type === 'multiple' && post.attachments) {
        console.log(`Multiple attachments (${post.attachments.length}):`);
        post.attachments.forEach(attachment => {
          console.log(`  - ${attachment.original_name} (${attachment.file_size} bytes)`);
          console.log(`    Download: ${attachment.serving_url}`);
        });
      } else if (post.attachment_url) {
        console.log(`Single attachment: ${post.attachment_serving_url}`);
      }
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
    
    // Process attachments
    data.data.forEach(post => {
      if (post.attachment_type === 'multiple' && post.attachments) {
        console.log(`Post has ${post.attachments.length} attachments`);
        post.attachments.forEach(attachment => {
          console.log(`File: ${attachment.original_name}`);
        });
      }
    });
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
- `attachment_url`: Original attachment URL (can be null, for single attachments)
- `attachment_type`: File type of attachment or "multiple" for multiple files
- `attachment_serving_url`: URL to access the attachment (can be null, for multiple attachments shows first file)
- `attachment_file_type`: MIME type of attachment (can be null, for multiple attachments shows first file type)

### Multiple Attachments Object
When `attachment_type` is "multiple", the post includes an `attachments` array:

- `attachment_id`: Unique identifier for the attachment
- `file_name`: System-generated filename
- `original_name`: Original filename as uploaded
- `file_path`: Internal file path
- `file_size`: File size in bytes
- `mime_type`: MIME type of the file
- `attachment_type`: Type of attachment
- `attachment_url`: Additional URL if applicable
- `serving_url`: Public URL to access/download the file
- `file_type`: MIME type (for compatibility)

## Security Features

1. **Authentication Required**: Only authenticated students can access
2. **Enrollment Verification**: Students can only view classes they're enrolled in
3. **Post Filtering**: Students only see appropriate posts (published, not drafts/scheduled)
4. **Privacy Protection**: Students can't see who liked posts, only the count
5. **File Access Control**: Students can only access files from posts they can view
6. **Error Handling**: Proper error messages without exposing sensitive data

## Related Endpoints

- `GET /api/teacher/classroom/{class_code}/stream` - Teacher view of streams (full access)
- `POST /api/teacher/classroom/{class_code}/stream` - Teacher create stream posts (supports multiple attachments)
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
5. Check that posts with multiple attachments show the `attachments` array
6. Verify that single attachment posts maintain backward compatibility

### Testing Multiple Attachments

1. **Create a teacher post with multiple files** using the teacher API
2. **View the post as a student** using this endpoint
3. **Verify the response includes**:
   - `attachment_type: "multiple"`
   - `attachments` array with file details
   - `attachment_serving_url` pointing to first file
   - All individual attachment serving URLs are accessible

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
- Multiple attachments are fully supported and backward compatible
- Single attachment posts continue to work exactly as before
- Attachments are served through a secure file serving system
- File access is controlled by the same permissions as post visibility 