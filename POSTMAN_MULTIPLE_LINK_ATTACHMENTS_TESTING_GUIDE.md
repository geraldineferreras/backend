# Postman Testing Guide for Multiple Link Attachments

## Overview
This guide explains how to test the enhanced teacher stream post API that now supports multiple link attachments (regular links, YouTube, and Google Drive) using Postman.

## API Endpoint
```
POST /api/teacher/classroom/{class_code}/stream
```

**Full URL**: `http://localhost/scms_new_backup/index.php/api/teacher/classroom/{CLASS_CODE}/stream`

## Integration Status ✅
Your backend is **properly integrated** with CodeIgniter routing:
- **Route**: `$route['api/teacher/classroom/(:any)/stream']['post'] = 'api/TeacherController/classroom_stream_post/$1';`
- **Controller**: `application/controllers/api/TeacherController.php`
- **Method**: `classroom_stream_post($class_code)`
- **CORS**: Enabled for frontend access

## Authentication
- **Type**: Bearer Token
- **Header**: `Authorization: Bearer {JWT_TOKEN}`
- **How to get**: Login as a teacher and extract the JWT token from the response

## Testing Scenarios

### 1. Testing with Multipart/Form-Data

#### Setup in Postman:
1. **Method**: POST
2. **URL**: `http://localhost/scms_new_backup/index.php/api/teacher/classroom/{CLASS_CODE}/stream`
3. **Headers**: 
   - `Authorization: Bearer {JWT_TOKEN}`
   - Remove `Content-Type` header (Postman will set it automatically)
4. **Body**: Select `form-data`

#### Test Case 1: Multiple Link Types
```
Key: title
Value: Test Post with Multiple Links

Key: content  
Value: This post contains various types of links for testing

Key: link_0
Value: https://www.example.com

Key: youtube_0
Value: https://www.youtube.com/watch?v=dQw4w9WgXcQ

Key: gdrive_0
Value: https://drive.google.com/file/d/1BxiMVs0XRA5nFMdKvBdBZjgmUUqptlbs74OgvE2upms/view

Key: link_1
Value: https://github.com

Key: youtube_1
Value: https://youtu.be/dQw4w9WgXcQ
```

#### Test Case 2: Mixed File and Links
```
Key: title
Value: Test Post with Files and Links

Key: content
Value: This post contains both files and links

Key: file_0
Type: File
Value: [Select a PDF or image file]

Key: link_0
Value: https://www.example.com

Key: youtube_0
Value: https://www.youtube.com/watch?v=dQw4w9WgXcQ
```

### 2. Testing with JSON Body

#### Setup in Postman:
1. **Method**: POST
2. **URL**: `http://localhost/scms_new_backup/index.php/api/teacher/classroom/{CLASS_CODE}/stream`
3. **Headers**: 
   - `Authorization: Bearer {JWT_TOKEN}`
   - `Content-Type: application/json`
4. **Body**: Select `raw` and choose `JSON`

#### Test Case 1: Multiple Link Types
```json
{
  "title": "Test JSON Post with Multiple Links",
  "content": "This post contains various types of links for testing",
  "attachments": [
    {
      "type": "link",
      "url": "https://www.example.com",
      "title": "Example Website"
    },
    {
      "type": "youtube",
      "url": "https://www.youtube.com/watch?v=dQw4w9WgXcQ",
      "title": "Rick Roll Video"
    },
    {
      "type": "google_drive",
      "url": "https://drive.google.com/file/d/1BxiMVs0XRA5nFMdKvBdBZjgmUUqptlbs74OgvE2upms/view",
      "title": "Google Drive Document"
    }
  ]
}
```

#### Test Case 2: Mixed Types with Metadata
```json
{
  "title": "Test JSON Post with Rich Metadata",
  "content": "This post contains links with additional information",
  "attachments": [
    {
      "type": "link",
      "url": "https://github.com",
      "title": "GitHub Repository",
      "description": "Source code repository"
    },
    {
      "type": "youtube",
      "url": "https://youtu.be/dQw4w9WgXcQ",
      "title": "Never Gonna Give You Up",
      "description": "Classic Rick Astley song"
    }
  ]
}
```

### 3. Testing Edge Cases

#### Test Case 1: Invalid URLs
```json
{
  "title": "Test Invalid URLs",
  "content": "Testing URL validation",
  "attachments": [
    {
      "type": "youtube",
      "url": "https://invalid-youtube.com/watch?v=123",
      "title": "Invalid YouTube URL"
    },
    {
      "type": "google_drive",
      "url": "https://invalid-drive.com/file/d/123",
      "title": "Invalid Google Drive URL"
    }
  ]
}
```

#### Test Case 2: Empty Attachments
```json
{
  "title": "Test Empty Attachments",
  "content": "Testing with no attachments",
  "attachments": []
}
```

#### Test Case 3: Single Attachment
```json
{
  "title": "Test Single Attachment",
  "content": "Testing with one attachment",
  "attachments": [
    {
      "type": "link",
      "url": "https://www.example.com",
      "title": "Single Link"
    }
  ]
}
```

## Expected Responses

### Success Response (200 OK)
```json
{
  "status": "success",
  "message": "Stream post created successfully",
  "data": {
    "stream_id": 123,
    "title": "Test Post with Multiple Links",
    "content": "This post contains various types of links for testing",
    "attachment_type": "multiple",
    "attachment_url": null,
    "created_at": "2024-01-15 10:30:00"
  }
}
```

### Error Response (400 Bad Request)
```json
{
  "status": "error",
  "message": "Invalid YouTube URL format",
  "data": null
}
```

### Error Response (401 Unauthorized)
```json
{
  "status": "error",
  "message": "Unauthorized access",
  "data": null
}
```

## Validation Rules

### YouTube URL Formats Accepted:
- `https://www.youtube.com/watch?v=VIDEO_ID`
- `https://youtu.be/VIDEO_ID`
- `https://www.youtube.com/embed/VIDEO_ID`

### Google Drive URL Formats Accepted:
- `https://drive.google.com/file/d/FILE_ID/view`
- `https://drive.google.com/open?id=FILE_ID`
- `https://docs.google.com/document/d/DOC_ID/edit`

### Regular Link Validation:
- Must start with `http://` or `https://`
- Basic URL format validation

## Frontend Integration

### React/Frontend Usage:
```javascript
const createStreamPost = async (classCode, postData) => {
  try {
    const response = await fetch(
      `http://localhost/scms_new_backup/index.php/api/teacher/classroom/${classCode}/stream`,
      {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${jwtToken}`,
          'Content-Type': 'application/json'
        },
        body: JSON.stringify(postData)
      }
    );
    
    const result = await response.json();
    return result;
  } catch (error) {
    console.error('Error creating stream post:', error);
    throw error;
  }
};

// Example usage with multiple links
const postData = {
  title: "My Stream Post",
  content: "This post has multiple links",
  attachments: [
    {
      type: "link",
      url: "https://www.example.com"
    },
    {
      type: "youtube",
      url: "https://www.youtube.com/watch?v=dQw4w9WgXcQ"
    }
  ]
};

createStreamPost('CLASS_CODE', postData);
```

## Testing Checklist

- [ ] Test multipart/form-data with multiple link types
- [ ] Test JSON body with multiple link types
- [ ] Test mixed file and link uploads
- [ ] Test invalid URL formats
- [ ] Test empty attachments array
- [ ] Test single attachment
- [ ] Verify JWT authentication
- [ ] Check database entries in `stream_attachments` table
- [ ] Verify `attachment_type` is set to 'multiple' for multiple attachments
- [ ] Test with different classroom codes
- [ ] Test frontend integration with React

## Troubleshooting

### Common Issues:
1. **401 Unauthorized**: Check JWT token validity and expiration
2. **400 Bad Request**: Verify URL formats and required fields
3. **500 Internal Server Error**: Check server logs for detailed error information
4. **File Upload Issues**: Ensure file size is within limits and format is supported
5. **CORS Issues**: Verify CORS hook is loaded and frontend origin is allowed

### Debug Steps:
1. Check the `application/logs/` directory for error logs
2. Verify database connection and table structure
3. Test with simple URLs first, then complex ones
4. Ensure all required fields are provided in the request
5. Verify the API endpoint URL includes `/index.php/` in the path

## Database Verification

After successful creation, verify in the database:

```sql
-- Check the main stream post
SELECT * FROM classroom_stream WHERE id = {STREAM_ID};

-- Check attachments
SELECT * FROM stream_attachments WHERE stream_id = {STREAM_ID};
```

The `stream_attachments` table should contain entries with:
- `attachment_type`: 'link', 'youtube', or 'google_drive'
- `attachment_url`: The validated URL
- `file_name`: Generated filename or URL identifier
- `mime_type`: Appropriate MIME type for the attachment type
