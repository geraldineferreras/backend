# Student Stream Draft and Schedule Management API Documentation

## Overview
This document describes the new student stream draft and schedule management endpoints that allow students to have full control over their draft posts and scheduled posts, similar to teacher capabilities.

## New Endpoints

### 1. Get Student's Draft Posts
**GET** `/api/student/classroom/{class_code}/stream/drafts`

Retrieves all draft posts created by the authenticated student in a specific classroom.

#### Authentication
- Requires student authentication
- Include JWT token in Authorization header: `Authorization: Bearer <token>`

#### Parameters
- `class_code` (path parameter): The class code of the classroom

#### Request Headers
```
Authorization: Bearer <jwt_token>
Content-Type: application/json
```

#### Response Format

**Success Response (200)**
```json
{
  "status": true,
  "message": "Draft posts retrieved successfully",
  "data": [
    {
      "id": 123,
      "class_code": "ABC123",
      "user_id": "2021302596",
      "user_name": "John Doe",
      "user_avatar": "uploads/profile/profile_123.jpg",
      "title": "Draft Announcement",
      "content": "This is a draft post content...",
      "is_draft": 1,
      "is_scheduled": 0,
      "scheduled_at": null,
      "allow_comments": 1,
      "attachment_type": "multiple",
      "attachment_url": null,
      "created_at": "2024-01-15 10:30:00",
      "updated_at": "2024-01-15 10:30:00",
      "attachments": [
        {
          "attachment_id": 456,
          "file_name": "abc123def456.pdf",
          "original_name": "document.pdf",
          "file_path": "uploads/submissions/abc123def456.pdf",
          "file_size": 1024000,
          "mime_type": "application/pdf",
          "attachment_type": "file",
          "attachment_url": null,
          "serving_url": "/file/submissions/abc123def456.pdf",
          "file_type": "application/pdf"
        }
      ]
    }
  ]
}
```

**Error Response (403)**
```json
{
  "status": false,
  "message": "Access denied. You must be enrolled in this class to view drafts.",
  "data": null
}
```

### 2. Get Student's Scheduled Posts
**GET** `/api/student/classroom/{class_code}/stream/scheduled`

Retrieves all scheduled posts created by the authenticated student in a specific classroom that are scheduled for future publication.

#### Authentication
- Requires student authentication
- Include JWT token in Authorization header: `Authorization: Bearer <token>`

#### Parameters
- `class_code` (path parameter): The class code of the classroom

#### Request Headers
```
Authorization: Bearer <jwt_token>
Content-Type: application/json
```

#### Response Format

**Success Response (200)**
```json
{
  "status": true,
  "message": "Scheduled posts retrieved successfully",
  "data": [
    {
      "id": 124,
      "class_code": "ABC123",
      "user_id": "2021302596",
      "user_name": "John Doe",
      "user_avatar": "uploads/profile/profile_123.jpg",
      "title": "Scheduled Announcement",
      "content": "This post will be published later...",
      "is_draft": 0,
      "is_scheduled": 1,
      "scheduled_at": "2024-01-20 09:00:00",
      "allow_comments": 1,
      "attachment_type": "file",
      "attachment_url": "uploads/announcements/schedule.pdf",
      "created_at": "2024-01-15 10:30:00",
      "updated_at": "2024-01-15 10:30:00"
    }
  ]
}
```

**Error Response (403)**
```json
{
  "status": false,
  "message": "Access denied. You must be enrolled in this class to view scheduled posts.",
  "data": null
}
```

### 3. Update Student's Draft Post
**PUT** `/api/student/classroom/{class_code}/stream/draft/{draft_id}`

Updates an existing draft post. Can also be used to publish the draft by setting `is_draft` to 0.

#### Authentication
- Requires student authentication
- Include JWT token in Authorization header: `Authorization: Bearer <token>`

#### Parameters
- `class_code` (path parameter): The class code of the classroom
- `draft_id` (path parameter): The ID of the draft post to update

#### Request Headers
```
Authorization: Bearer <jwt_token>
Content-Type: application/json
```

#### Request Body
```json
{
  "title": "Updated Draft Title",
  "content": "Updated content for the draft post...",
  "is_draft": 0,
  "is_scheduled": 1,
  "scheduled_at": "2024-01-25 14:00:00",
  "allow_comments": 1,
  "student_ids": ["2021302596", "2021305889"]
}
```

#### Request Body Fields

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `content` | string | **Yes** | The updated content of the post |
| `title` | string | No | Optional title for the post |
| `is_draft` | boolean | No | Whether to keep as draft (1) or publish (0) |
| `is_scheduled` | boolean | No | Whether to schedule the post (1) or publish immediately (0) |
| `scheduled_at` | string | No | Scheduled date/time (ISO format) if scheduling |
| `allow_comments` | boolean | No | Whether to allow comments |
| `student_ids` | array | No | Array of user_ids to target specific students |

#### Response Format

**Success Response (200)**
```json
{
  "status": true,
  "message": "Draft updated successfully",
  "data": {
    "id": 123,
    "class_code": "ABC123",
    "user_id": "2021302596",
    "title": "Updated Draft Title",
    "content": "Updated content for the draft post...",
    "is_draft": 0,
    "is_scheduled": 1,
    "scheduled_at": "2024-01-25 14:00:00",
    "allow_comments": 1,
    "visible_to_student_ids": "[\"2021302596\",\"2021305889\"]",
    "created_at": "2024-01-15 10:30:00",
    "updated_at": "2024-01-15 11:45:00"
  }
}
```

**Error Response (403)**
```json
{
  "status": false,
  "message": "Access denied. You can only update your own drafts.",
  "data": null
}
```

**Error Response (400)**
```json
{
  "status": false,
  "message": "This post is not a draft.",
  "data": null
}
```

## Security Features

1. **Authentication Required**: All endpoints require valid student JWT token
2. **Enrollment Verification**: Students can only access classes they're enrolled in
3. **Active Enrollment**: Only students with active enrollment status can access
4. **Ownership Validation**: Students can only view and edit their own drafts/scheduled posts
5. **Draft Validation**: Draft update endpoint only works on actual draft posts
6. **Notification Integration**: Publishing drafts automatically creates notifications

## Usage Examples

### JavaScript/Fetch

#### Get Draft Posts
```javascript
const token = 'your_jwt_token_here';
const classCode = 'ABC123';

fetch(`/api/student/classroom/${classCode}/stream/drafts`, {
  method: 'GET',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json'
  }
})
.then(response => response.json())
.then(data => {
  if (data.status) {
    console.log('Drafts:', data.data);
  } else {
    console.error('Error:', data.message);
  }
})
.catch(error => console.error('Error:', error));
```

#### Get Scheduled Posts
```javascript
const token = 'your_jwt_token_here';
const classCode = 'ABC123';

fetch(`/api/student/classroom/${classCode}/stream/scheduled`, {
  method: 'GET',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json'
  }
})
.then(response => response.json())
.then(data => {
  if (data.status) {
    console.log('Scheduled posts:', data.data);
  } else {
    console.error('Error:', data.message);
  }
})
.catch(error => console.error('Error:', error));
```

#### Update Draft Post
```javascript
const token = 'your_jwt_token_here';
const classCode = 'ABC123';
const draftId = 123;

const updateData = {
  title: 'Updated Title',
  content: 'Updated content...',
  is_draft: 0, // Publish the draft
  is_scheduled: 0,
  allow_comments: 1
};

fetch(`/api/student/classroom/${classCode}/stream/draft/${draftId}`, {
  method: 'PUT',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json'
  },
  body: JSON.stringify(updateData)
})
.then(response => response.json())
.then(data => {
  if (data.status) {
    console.log('Draft updated:', data.data);
  } else {
    console.error('Error:', data.message);
  }
})
.catch(error => console.error('Error:', error));
```

### cURL

#### Get Draft Posts
```bash
curl -X GET \
  "http://localhost/scms_new_backup/index.php/api/student/classroom/ABC123/stream/drafts" \
  -H "Authorization: Bearer your_jwt_token_here" \
  -H "Content-Type: application/json"
```

#### Get Scheduled Posts
```bash
curl -X GET \
  "http://localhost/scms_new_backup/index.php/api/student/classroom/ABC123/stream/scheduled" \
  -H "Authorization: Bearer your_jwt_token_here" \
  -H "Content-Type: application/json"
```

#### Update Draft Post
```bash
curl -X PUT \
  "http://localhost/scms_new_backup/index.php/api/student/classroom/ABC123/stream/draft/123" \
  -H "Authorization: Bearer your_jwt_token_here" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Updated Title",
    "content": "Updated content...",
    "is_draft": 0,
    "is_scheduled": 0,
    "allow_comments": 1
  }'
```

## Related Endpoints

- `POST /api/student/classroom/{class_code}/stream` - Create stream posts (supports drafts and scheduling)
- `GET /api/student/classroom/{class_code}/stream` - View published stream posts
- `GET /api/student/classroom/{class_code}/stream/{stream_id}/comments` - View post comments
- `POST /api/student/classroom/{class_code}/stream/{stream_id}/comment` - Add comments to posts

## Testing

To test these new endpoints:

1. **Ensure you have a valid student JWT token**
2. **Make sure you're enrolled in a class**
3. **Create some draft and scheduled posts first** using the existing POST endpoint
4. **Test each endpoint** with the class code and appropriate parameters
5. **Verify responses** match the expected format
6. **Test error cases** like unauthorized access or invalid parameters

## Notes

- **Draft posts are private**: Only the creating student can see and edit them
- **Scheduled posts show future dates**: Only posts scheduled for future publication are returned
- **Publishing drafts**: Setting `is_draft = 0` automatically publishes the post and sends notifications
- **Multiple attachments**: All endpoints support posts with multiple file attachments
- **Backward compatibility**: Existing student stream functionality remains unchanged
- **Teacher parity**: Students now have similar draft/schedule management capabilities as teachers
