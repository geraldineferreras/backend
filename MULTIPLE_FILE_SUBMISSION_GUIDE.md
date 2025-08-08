# Multiple File Submission Guide

## Overview
This guide explains how to submit tasks with multiple files using the enhanced SCMS API. The system now supports three different methods for uploading multiple files.

## Database Schema Changes

### New Table: `task_submission_attachments`
```sql
CREATE TABLE IF NOT EXISTS `task_submission_attachments` (
  `attachment_id` int(11) NOT NULL AUTO_INCREMENT,
  `submission_id` int(11) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `file_path` text NOT NULL,
  `file_size` int(11) DEFAULT NULL,
  `mime_type` varchar(100) DEFAULT NULL,
  `attachment_type` enum('file','link','youtube','google_drive') DEFAULT 'file',
  `attachment_url` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`attachment_id`),
  KEY `idx_submission_id` (`submission_id`),
  KEY `idx_attachment_type` (`attachment_type`),
  CONSTRAINT `fk_task_submission_attachments_submission` FOREIGN KEY (`submission_id`) REFERENCES `task_submissions` (`submission_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

## API Endpoint
```
POST /api/tasks/{task_id}/submit
```

## Authentication
- Requires student authentication
- Include JWT token in Authorization header: `Authorization: Bearer <token>`

## Method 1: Multiple Files with Same Field Name (Recommended)

### HTML Form
```html
<form enctype="multipart/form-data" method="POST">
    <input type="text" name="submission_content" placeholder="Submission content">
    <input type="text" name="class_code" placeholder="Class code" required>
    <input type="file" name="attachment[]" multiple accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.gif,.webp,.ppt,.pptx,.xls,.xlsx,.txt,.zip,.rar,.mp4,.mp3">
    <button type="submit">Submit Task</button>
</form>
```

### JavaScript (FormData)
```javascript
const formData = new FormData();
formData.append('submission_content', 'My assignment submission');
formData.append('class_code', 'MATH101');

// Add multiple files with same field name
const fileInput = document.querySelector('input[type="file"]');
Array.from(fileInput.files).forEach(file => {
    formData.append('attachment[]', file);
});

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
        console.log('Attachments count:', data.data.attachments_count);
    }
});
```

### React Example
```javascript
const handleSubmit = async (e) => {
    e.preventDefault();
    
    const formData = new FormData();
    formData.append('submission_content', submissionContent);
    formData.append('class_code', classCode);
    
    // Add multiple files
    files.forEach(file => {
        formData.append('attachment[]', file);
    });
    
    try {
        const response = await fetch('/api/tasks/123/submit', {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${token}`
            },
            body: formData
        });
        
        const data = await response.json();
        if (data.success) {
            console.log('Submitted with', data.data.attachments_count, 'files');
        }
    } catch (error) {
        console.error('Error:', error);
    }
};
```

## Method 2: Multiple Files with Different Field Names

### HTML Form
```html
<form enctype="multipart/form-data" method="POST">
    <input type="text" name="submission_content" placeholder="Submission content">
    <input type="text" name="class_code" placeholder="Class code" required>
    <input type="file" name="attachment1" accept=".pdf,.doc,.docx">
    <input type="file" name="attachment2" accept=".jpg,.jpeg,.png">
    <input type="file" name="attachment3" accept=".zip,.rar">
    <button type="submit">Submit Task</button>
</form>
```

### JavaScript (FormData)
```javascript
const formData = new FormData();
formData.append('submission_content', 'My assignment submission');
formData.append('class_code', 'MATH101');

// Add files with different field names
formData.append('attachment1', file1);
formData.append('attachment2', file2);
formData.append('attachment3', file3);

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

## Method 3: JSON Array of Attachment URLs

### JSON Request
```json
{
    "submission_content": "My assignment submission with external links",
    "class_code": "MATH101",
    "attachments": [
        {
            "file_name": "research_paper.pdf",
            "original_name": "research_paper.pdf",
            "attachment_type": "link",
            "attachment_url": "https://drive.google.com/file/d/123456/view"
        },
        {
            "file_name": "presentation.pptx",
            "original_name": "presentation.pptx",
            "attachment_type": "google_drive",
            "attachment_url": "https://drive.google.com/file/d/789012/view"
        },
        {
            "file_name": "video_demo.mp4",
            "original_name": "video_demo.mp4",
            "attachment_type": "youtube",
            "attachment_url": "https://youtube.com/watch?v=abcdef"
        }
    ]
}
```

### JavaScript (JSON)
```javascript
const submissionData = {
    submission_content: 'My assignment submission with external links',
    class_code: 'MATH101',
    attachments: [
        {
            file_name: 'research_paper.pdf',
            original_name: 'research_paper.pdf',
            attachment_type: 'link',
            attachment_url: 'https://drive.google.com/file/d/123456/view'
        },
        {
            file_name: 'presentation.pptx',
            original_name: 'presentation.pptx',
            attachment_type: 'google_drive',
            attachment_url: 'https://drive.google.com/file/d/789012/view'
        }
    ]
};

fetch('/api/tasks/123/submit', {
    method: 'POST',
    headers: {
        'Authorization': 'Bearer ' + token,
        'Content-Type': 'application/json'
    },
    body: JSON.stringify(submissionData)
})
.then(response => response.json())
.then(data => console.log(data));
```

## New API Endpoints

### Get Submission with Attachments
```
GET /api/tasks/submissions/{submission_id}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "submission_id": 123,
        "task_id": 456,
        "student_id": "STU001",
        "class_code": "MATH101",
        "submission_content": "My assignment submission",
        "submitted_at": "2024-01-15 10:30:00",
        "grade": null,
        "feedback": null,
        "status": "submitted",
        "attachments": [
            {
                "attachment_id": 1,
                "file_name": "abc123.pdf",
                "original_name": "research_paper.pdf",
                "file_path": "uploads/submissions/abc123.pdf",
                "file_size": 1024000,
                "mime_type": "application/pdf",
                "attachment_type": "file",
                "attachment_url": "uploads/submissions/abc123.pdf",
                "created_at": "2024-01-15 10:30:00"
            },
            {
                "attachment_id": 2,
                "file_name": "def456.pptx",
                "original_name": "presentation.pptx",
                "file_path": "uploads/submissions/def456.pptx",
                "file_size": 2048000,
                "mime_type": "application/vnd.openxmlformats-officedocument.presentationml.presentation",
                "attachment_type": "file",
                "attachment_url": "uploads/submissions/def456.pptx",
                "created_at": "2024-01-15 10:30:00"
            }
        ]
    }
}
```

### Get Student Submission with Attachments
```
GET /api/tasks/{task_id}/submission?class_code=MATH101
```

### Delete Attachment
```
DELETE /api/tasks/submissions/{submission_id}/attachments/{attachment_id}
```

## Postman Testing

### Method 1: Multiple Files (Same Field Name)
1. Set method to `POST`
2. URL: `{{base_url}}/api/tasks/1/submit`
3. Headers:
   - `Authorization: Bearer {{token}}`
4. Body (form-data):
   - `submission_content`: "My assignment submission"
   - `class_code`: "MATH101"
   - `attachment[]`: [Select multiple files]

### Method 2: Multiple Files (Different Field Names)
1. Set method to `POST`
2. URL: `{{base_url}}/api/tasks/1/submit`
3. Headers:
   - `Authorization: Bearer {{token}}`
4. Body (form-data):
   - `submission_content`: "My assignment submission"
   - `class_code`: "MATH101"
   - `attachment1`: [Select file 1]
   - `attachment2`: [Select file 2]
   - `attachment3`: [Select file 3]

### Method 3: JSON Attachments
1. Set method to `POST`
2. URL: `{{base_url}}/api/tasks/1/submit`
3. Headers:
   - `Authorization: Bearer {{token}}`
   - `Content-Type: application/json`
4. Body (raw JSON):
```json
{
    "submission_content": "My assignment submission with external links",
    "class_code": "MATH101",
    "attachments": [
        {
            "file_name": "research_paper.pdf",
            "original_name": "research_paper.pdf",
            "attachment_type": "link",
            "attachment_url": "https://drive.google.com/file/d/123456/view"
        },
        {
            "file_name": "presentation.pptx",
            "original_name": "presentation.pptx",
            "attachment_type": "google_drive",
            "attachment_url": "https://drive.google.com/file/d/789012/view"
        }
    ]
}
```

## Supported File Types
- **Images**: gif, jpg, jpeg, png, webp
- **Documents**: pdf, doc, docx, ppt, pptx, xls, xlsx, txt
- **Archives**: zip, rar
- **Media**: mp4, mp3

## File Size Limit
- **Maximum**: 10MB per file
- **Total**: No limit on number of files (only individual file size)

## Response Format

### Success Response
```json
{
    "success": true,
    "message": "Task submitted successfully",
    "data": {
        "submission_id": 123,
        "attachments_count": 3
    },
    "status": 201
}
```

### Error Response
```json
{
    "success": false,
    "message": "File upload failed: File too large",
    "status": 400
}
```

## Migration Notes

### Legacy Support
The API maintains backward compatibility with single file uploads:
- Single `attachment` field still works
- Single `attachment_url` in JSON still works
- Existing submissions continue to work

### Database Migration
Run the SQL script to create the new table:
```sql
-- Run create_task_submission_attachments_table.sql
```

## Best Practices

1. **Use Method 1** for most cases (multiple files with same field name)
2. **Use Method 2** when you need different file type restrictions per field
3. **Use Method 3** for external links and URLs
4. **Always validate file types** on the frontend
5. **Check file sizes** before upload
6. **Handle upload errors** gracefully
7. **Show progress indicators** for large files

## Troubleshooting

### Common Issues

1. **"File upload failed"**
   - Check file size (max 10MB)
   - Verify file type is supported
   - Ensure upload directory is writable

2. **"At least one attachment is required"**
   - Make sure files are properly selected
   - Check field names match exactly
   - Verify form data is being sent correctly

3. **"Class code is required"**
   - Ensure class_code is included in the request
   - Verify the student is enrolled in the class

### Debug Endpoint
Use the existing debug endpoint to troubleshoot:
```
POST /api/tasks/debug-submit
```

This will show you exactly what data the server receives.
