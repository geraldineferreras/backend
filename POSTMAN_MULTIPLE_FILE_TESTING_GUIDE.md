# Postman Testing Guide for Multiple File Submission

This guide provides step-by-step instructions for testing the multiple file submission functionality using Postman.

## Prerequisites

1. **Authentication**: You need a valid student token
2. **Task ID**: A valid task ID from your system
3. **Class Code**: A valid class code
4. **Files**: Test files to upload

## Getting Authentication Token

First, you need to get a student authentication token:

### 1. Login Request
```
POST {{base_url}}/api/auth/login
Content-Type: application/json

{
  "email": "student@example.com",
  "password": "password123"
}
```

### 2. Extract Token
From the response, copy the `token` value for use in subsequent requests.

## Method 1: Multiple Files with Same Field Name (attachment[])

### Setup in Postman

1. **Request Type**: `POST`
2. **URL**: `{{base_url}}/api/tasks/1/submit`
3. **Headers**:
   - `Authorization: Bearer YOUR_TOKEN_HERE`
   - `Content-Type: multipart/form-data` (Postman will set this automatically)

### Body Configuration

1. **Body Type**: Select `form-data`
2. **Add Fields**:
   - `submission_content` (Text): "This is my submission with multiple files"
   - `class_code` (Text): "J56NHD"
   - `attachment` (File): Select your first file
   - `attachment` (File): Select your second file
   - `attachment` (File): Select your third file

**Note**: In Postman, you can add multiple fields with the same name (`attachment`) and Postman will handle them as an array.

### Example Request Structure
```
POST {{base_url}}/api/tasks/1/submit
Authorization: Bearer YOUR_TOKEN_HERE
Content-Type: multipart/form-data

Form Data:
- submission_content: "This is my submission with multiple files"
- class_code: "J56NHD"
- attachment: [File 1: document.pdf]
- attachment: [File 2: image.png]
- attachment: [File 3: spreadsheet.xlsx]
```

## Method 2: Multiple Files with Different Field Names (attachment1, attachment2, etc.)

### Setup in Postman

1. **Request Type**: `POST`
2. **URL**: `{{base_url}}/api/tasks/1/submit`
3. **Headers**:
   - `Authorization: Bearer YOUR_TOKEN_HERE`
   - `Content-Type: multipart/form-data`

### Body Configuration

1. **Body Type**: Select `form-data`
2. **Add Fields**:
   - `submission_content` (Text): "This is my submission with multiple files"
   - `class_code` (Text): "J56NHD"
   - `attachment1` (File): Select your first file
   - `attachment2` (File): Select your second file
   - `attachment3` (File): Select your third file

### Example Request Structure
```
POST {{base_url}}/api/tasks/1/submit
Authorization: Bearer YOUR_TOKEN_HERE
Content-Type: multipart/form-data

Form Data:
- submission_content: "This is my submission with multiple files"
- class_code: "J56NHD"
- attachment1: [File 1: document.pdf]
- attachment2: [File 2: image.png]
- attachment3: [File 3: spreadsheet.xlsx]
```

## Method 3: JSON Array of External Files/URLs

### Setup in Postman

1. **Request Type**: `POST`
2. **URL**: `{{base_url}}/api/tasks/1/submit`
3. **Headers**:
   - `Authorization: Bearer YOUR_TOKEN_HERE`
   - `Content-Type: application/json`

### Body Configuration

1. **Body Type**: Select `raw`
2. **Format**: Select `JSON`
3. **Body Content**:

```json
{
  "submission_content": "This is my submission with external file links",
  "class_code": "J56NHD",
  "attachments": [
    {
      "file_name": "research_paper.pdf",
      "original_name": "research_paper.pdf",
      "attachment_type": "link",
      "attachment_url": "https://drive.google.com/file/d/1234567890/view",
      "file_size": 2048576,
      "mime_type": "application/pdf"
    },
    {
      "file_name": "presentation.pptx",
      "original_name": "presentation.pptx",
      "attachment_type": "google_drive",
      "attachment_url": "https://drive.google.com/file/d/0987654321/view",
      "file_size": 1048576,
      "mime_type": "application/vnd.openxmlformats-officedocument.presentationml.presentation"
    },
    {
      "file_name": "video_demo.mp4",
      "original_name": "video_demo.mp4",
      "attachment_type": "youtube",
      "attachment_url": "https://www.youtube.com/watch?v=dQw4w9WgXcQ",
      "file_size": 0,
      "mime_type": "video/mp4"
    }
  ]
}
```

## Testing Different Attachment Types

### File Uploads
- **Supported Types**: PDF, DOC, DOCX, PPT, PPTX, XLS, XLSX, TXT, PNG, JPG, JPEG, GIF, MP4, MP3, ZIP, RAR
- **Size Limit**: 10MB per file
- **Max Files**: No limit (practical limit based on server configuration)

### External Links
- **Google Drive**: `attachment_type: "google_drive"`
- **YouTube**: `attachment_type: "youtube"`
- **General Links**: `attachment_type: "link"`

## Expected Responses

### Successful Submission (Method 1 & 2)
```json
{
  "status": "success",
  "message": "Task submitted successfully",
  "data": {
    "submission_id": 123,
    "task_id": 1,
    "student_id": 456,
    "submission_content": "This is my submission with multiple files",
    "submitted_at": "2024-01-15 10:30:00",
    "attachments_count": 3,
    "attachments": [
      {
        "attachment_id": 1,
        "file_name": "encrypted_name_1.pdf",
        "original_name": "document.pdf",
        "file_path": "uploads/submissions/encrypted_name_1.pdf",
        "file_size": 1048576,
        "mime_type": "application/pdf",
        "attachment_type": "file",
        "attachment_url": null
      },
      {
        "attachment_id": 2,
        "file_name": "encrypted_name_2.png",
        "original_name": "image.png",
        "file_path": "uploads/submissions/encrypted_name_2.png",
        "file_size": 512000,
        "mime_type": "image/png",
        "attachment_type": "file",
        "attachment_url": null
      }
    ]
  }
}
```

### Successful Submission (Method 3)
```json
{
  "status": "success",
  "message": "Task submitted successfully",
  "data": {
    "submission_id": 124,
    "task_id": 1,
    "student_id": 456,
    "submission_content": "This is my submission with external file links",
    "submitted_at": "2024-01-15 10:35:00",
    "attachments_count": 3,
    "attachments": [
      {
        "attachment_id": 3,
        "file_name": "research_paper.pdf",
        "original_name": "research_paper.pdf",
        "file_path": "",
        "file_size": 2048576,
        "mime_type": "application/pdf",
        "attachment_type": "link",
        "attachment_url": "https://drive.google.com/file/d/1234567890/view"
      },
      {
        "attachment_id": 4,
        "file_name": "presentation.pptx",
        "original_name": "presentation.pptx",
        "file_path": "",
        "file_size": 1048576,
        "mime_type": "application/vnd.openxmlformats-officedocument.presentationml.presentation",
        "attachment_type": "google_drive",
        "attachment_url": "https://drive.google.com/file/d/0987654321/view"
      }
    ]
  }
}
```

## Testing New API Endpoints

### 1. Get Submission with Attachments
```
GET {{base_url}}/api/tasks/submissions/123
Authorization: Bearer YOUR_TOKEN_HERE
```

### 2. Get Student's Submission for a Task
```
GET {{base_url}}/api/tasks/1/submission?class_code=J56NHD
Authorization: Bearer YOUR_TOKEN_HERE
```

### 3. Delete Specific Attachment
```
DELETE {{base_url}}/api/tasks/submissions/123/attachments/1
Authorization: Bearer YOUR_TOKEN_HERE
```

## Error Responses

### Invalid File Type
```json
{
  "status": "error",
  "message": "Invalid file type. Allowed types: pdf, doc, docx, ppt, pptx, xls, xlsx, txt, png, jpg, jpeg, gif, mp4, mp3, zip, rar",
  "data": null
}
```

### File Too Large
```json
{
  "status": "error",
  "message": "File size exceeds maximum limit of 10MB",
  "data": null
}
```

### No Files Provided
```json
{
  "status": "error",
  "message": "No valid files provided for submission",
  "data": null
}
```

## Postman Collection Setup

### 1. Create Environment Variables
- `base_url`: Your API base URL (e.g., `http://localhost/scms_new_backup`)
- `student_token`: Your student authentication token
- `task_id`: A valid task ID for testing
- `class_code`: A valid class code

### 2. Create Request Templates

#### Template for Method 1 & 2 (File Upload)
```
POST {{base_url}}/api/tasks/{{task_id}}/submit
Authorization: Bearer {{student_token}}
Content-Type: multipart/form-data

Body (form-data):
- submission_content: "Test submission with multiple files"
- class_code: "{{class_code}}"
- attachment: [File upload]
```

#### Template for Method 3 (JSON)
```
POST {{base_url}}/api/tasks/{{task_id}}/submit
Authorization: Bearer {{student_token}}
Content-Type: application/json

Body (raw JSON):
{
  "submission_content": "Test submission with external links",
  "class_code": "{{class_code}}",
  "attachments": [
    {
      "file_name": "test_file.pdf",
      "original_name": "test_file.pdf",
      "attachment_type": "link",
      "attachment_url": "https://example.com/file.pdf",
      "file_size": 1024,
      "mime_type": "application/pdf"
    }
  ]
}
```

## Testing Checklist

- [ ] Method 1: Multiple files with same field name
- [ ] Method 2: Multiple files with different field names
- [ ] Method 3: JSON array of external files
- [ ] Single file upload (backward compatibility)
- [ ] Invalid file type handling
- [ ] File size limit handling
- [ ] Get submission with attachments
- [ ] Delete specific attachment
- [ ] Get student's submission for a task
- [ ] Authentication error handling
- [ ] Missing required fields handling

## Tips for Testing

1. **File Preparation**: Prepare test files of different types and sizes
2. **Token Management**: Keep your authentication token updated
3. **Error Testing**: Test with invalid file types and oversized files
4. **Response Validation**: Verify that all attachment metadata is correctly returned
5. **Cross-Method Testing**: Test the same submission using different methods to ensure consistency

## Troubleshooting

### Common Issues

1. **"No valid files provided"**: Check that files are actually selected in Postman
2. **"Invalid file type"**: Ensure file extensions match the allowed list
3. **"File size exceeds limit"**: Use smaller test files
4. **Authentication errors**: Verify your token is valid and not expired
5. **CORS errors**: Ensure your API server has proper CORS configuration

### Debug Steps

1. Check the request headers in Postman's console
2. Verify file uploads are properly configured
3. Test with a single file first before trying multiple files
4. Check server logs for detailed error messages
5. Verify database table structure matches the expected schema
