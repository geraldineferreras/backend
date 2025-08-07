# Task Submission Guide

## Overview
This guide explains how to submit tasks to teachers using form data with file uploads in the SCMS API.

## API Endpoint
```
POST /api/tasks/{task_id}/submit
```

## Authentication
- Requires student authentication
- Include JWT token in Authorization header: `Authorization: Bearer <token>`

## Request Methods

### 1. Multipart Form Data (Recommended for File Uploads)

**Content-Type:** `multipart/form-data`

**Form Fields:**
- `submission_content` (text) - Optional text content
- `class_code` (text) - Required class code
- `attachment` (file) - Optional file upload

**Supported File Types:**
- Images: gif, jpg, jpeg, png, webp
- Documents: pdf, doc, docx, ppt, pptx, xls, xlsx, txt
- Archives: zip, rar
- Media: mp4, mp3

**File Size Limit:** 10MB

### 2. JSON Request (Alternative)

**Content-Type:** `application/json`

**Request Body:**
```json
{
  "submission_content": "Optional text content",
  "class_code": "CLASS001",
  "attachment_url": "https://example.com/file.pdf",
  "attachment_type": "link"
}
```

## Examples

### Example 1: Submit with File Upload (Multipart)

```javascript
// Using FormData
const formData = new FormData();
formData.append('submission_content', 'This is my assignment submission');
formData.append('class_code', 'MATH101');
formData.append('attachment', fileInput.files[0]); // File from input

fetch('/api/tasks/123/submit', {
  method: 'POST',
  headers: {
    'Authorization': 'Bearer ' + token
  },
  body: formData
})
.then(response => response.json())
.then(data => {
  if (data.success) {
    console.log('Task submitted successfully!');
    console.log('Submission ID:', data.data.submission_id);
  } else {
    console.error('Error:', data.message);
  }
});
```

### Example 2: Submit with Text Only (Multipart)

```javascript
const formData = new FormData();
formData.append('submission_content', 'My written assignment answer');
formData.append('class_code', 'ENG101');

fetch('/api/tasks/123/submit', {
  method: 'POST',
  headers: {
    'Authorization': 'Bearer ' + token
  },
  body: formData
})
.then(response => response.json())
.then(data => console.log(data));
```

### Example 3: Submit with External Link (JSON)

```javascript
fetch('/api/tasks/123/submit', {
  method: 'POST',
  headers: {
    'Authorization': 'Bearer ' + token,
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    submission_content: 'Check my Google Drive document',
    class_code: 'SCI101',
    attachment_url: 'https://drive.google.com/file/d/123456/view',
    attachment_type: 'google_drive'
  })
})
.then(response => response.json())
.then(data => console.log(data));
```

## HTML Form Example

```html
<!DOCTYPE html>
<html>
<head>
    <title>Task Submission</title>
</head>
<body>
    <form id="taskForm" enctype="multipart/form-data">
        <div>
            <label for="submission_content">Submission Content:</label>
            <textarea id="submission_content" name="submission_content" rows="4" cols="50"></textarea>
        </div>
        
        <div>
            <label for="class_code">Class Code:</label>
            <input type="text" id="class_code" name="class_code" required>
        </div>
        
        <div>
            <label for="attachment">Attachment (Optional):</label>
            <input type="file" id="attachment" name="attachment" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.gif,.webp,.ppt,.pptx,.xls,.xlsx,.txt,.zip,.rar,.mp4,.mp3">
        </div>
        
        <button type="submit">Submit Task</button>
    </form>

    <script>
        document.getElementById('taskForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const token = localStorage.getItem('token'); // Get your JWT token
            
            try {
                const response = await fetch('/api/tasks/123/submit', {
                    method: 'POST',
                    headers: {
                        'Authorization': 'Bearer ' + token
                    },
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    alert('Task submitted successfully!');
                    console.log('Submission ID:', data.data.submission_id);
                } else {
                    alert('Error: ' + data.message);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Failed to submit task');
            }
        });
    </script>
</body>
</html>
```

## Response Format

### Success Response (201 Created)
```json
{
  "success": true,
  "message": "Task submitted successfully",
  "data": {
    "submission_id": 456
  },
  "status_code": 201
}
```

### Error Responses

**400 Bad Request**
```json
{
  "success": false,
  "message": "At least one attachment is required",
  "data": null,
  "status_code": 400
}
```

**403 Forbidden**
```json
{
  "success": false,
  "message": "You are not enrolled in this class",
  "data": null,
  "status_code": 403
}
```

**404 Not Found**
```json
{
  "success": false,
  "message": "Task not found",
  "data": null,
  "status_code": 404
}
```

**409 Conflict**
```json
{
  "success": false,
  "message": "You have already submitted this task",
  "data": null,
  "status_code": 409
}
```

## Validation Rules

1. **Authentication**: Must be a logged-in student
2. **Task Existence**: Task must exist and be active
3. **Enrollment**: Student must be enrolled in the class
4. **No Duplicate**: Student can only submit once per task per class
5. **Content Required**: At least submission_content OR attachment must be provided
6. **Class Code**: Required field
7. **File Size**: Maximum 10MB for file uploads
8. **File Types**: Only allowed file types are accepted

## File Upload Details

- **Upload Directory**: `./uploads/submissions/`
- **File Naming**: Encrypted names for security
- **Storage**: Files are stored locally on server
- **Access**: Files can be accessed via `/api/tasks/serve-submission-file/{filename}`

## Error Handling

Common errors and solutions:

1. **"File upload failed"** - Check file size and type
2. **"You are not enrolled in this class"** - Verify class code and enrollment
3. **"You have already submitted this task"** - Check for existing submission
4. **"Task not found"** - Verify task ID exists and is active

## Testing

Use the provided test files:
- `test_task_submission.html` - For testing file uploads
- `test_task_submission_json.html` - For testing JSON requests 