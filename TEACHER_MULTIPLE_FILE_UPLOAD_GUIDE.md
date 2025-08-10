# Teacher Multiple File Upload Guide

## Overview
This guide explains how teachers can now upload multiple files when creating class tasks using the enhanced SCMS API. The system supports three different methods for uploading multiple files while maintaining backward compatibility.

## Database Schema Changes

### New Table: `task_attachments`
```sql
CREATE TABLE IF NOT EXISTS `task_attachments` (
  `attachment_id` int(11) NOT NULL AUTO_INCREMENT,
  `task_id` int(11) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `file_path` text NOT NULL,
  `file_size` int(11) DEFAULT NULL,
  `mime_type` varchar(100) DEFAULT NULL,
  `attachment_type` enum('file','link','youtube','google_drive') DEFAULT 'file',
  `attachment_url` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`attachment_id`),
  KEY `idx_task_id` (`task_id`),
  KEY `idx_attachment_type` (`attachment_type`),
  CONSTRAINT `fk_task_attachments_task` FOREIGN KEY (`task_id`) REFERENCES `class_tasks` (`task_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

## API Endpoints

### 1. Create Task with Multiple Files
```
POST /api/tasks/create
```

### 2. Get Task with Attachments
```
GET /api/tasks/{task_id}
```

### 3. Get Task Attachments
```
GET /api/tasks/{task_id}/attachments
```

### 4. Delete Task Attachment
```
DELETE /api/tasks/{task_id}/attachments/{attachment_id}
```

### 5. Serve Task Attachment File
```
GET /api/tasks/attachment/{filename}
```

## Authentication
- Requires teacher authentication
- Include JWT token in Authorization header: `Authorization: Bearer <token>`

## Multiple File Upload Methods

### Method 1: Multiple Files with Same Field Name (Recommended)

#### HTML Form
```html
<form enctype="multipart/form-data" method="POST" action="/api/tasks/create">
    <input type="text" name="title" placeholder="Task Title" required>
    <input type="text" name="type" value="assignment" required>
    <input type="number" name="points" placeholder="Points" required>
    <textarea name="instructions" placeholder="Instructions" required></textarea>
    <input type="text" name="class_codes" value='["CLASS001"]' required>
    <input type="file" name="attachment[]" multiple accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.gif,.webp,.ppt,.pptx,.xls,.xlsx,.txt,.zip,.rar,.mp4,.mp3">
    <button type="submit">Create Task</button>
</form>
```

#### JavaScript (FormData)
```javascript
const formData = new FormData();
formData.append('title', 'Multiple Files Assignment');
formData.append('type', 'assignment');
formData.append('points', '100');
formData.append('instructions', 'Complete this assignment with multiple files');
formData.append('class_codes', JSON.stringify(['CLASS001']));
formData.append('assignment_type', 'classroom');
formData.append('allow_comments', '1');
formData.append('is_draft', '0');
formData.append('due_date', '2025-01-25 23:59:00');

// Add multiple files
const fileInput = document.querySelector('input[type="file"]');
for (let i = 0; i < fileInput.files.length; i++) {
    formData.append('attachment[]', fileInput.files[i]);
}

fetch('/api/tasks/create', {
    method: 'POST',
    headers: {
        'Authorization': 'Bearer ' + token
    },
    body: formData
})
.then(response => response.json())
.then(data => {
    if (data.success) {
        console.log('Task created successfully!');
        console.log('Task ID:', data.data.task_id);
        console.log('Attachments:', data.data.attachments);
    } else {
        console.error('Error:', data.message);
    }
});
```

### Method 2: Multiple Files with Different Field Names

#### HTML Form
```html
<form enctype="multipart/form-data" method="POST" action="/api/tasks/create">
    <input type="text" name="title" placeholder="Task Title" required>
    <input type="text" name="type" value="assignment" required>
    <input type="number" name="points" placeholder="Points" required>
    <textarea name="instructions" placeholder="Instructions" required></textarea>
    <input type="text" name="class_codes" value='["CLASS001"]' required>
    
    <input type="file" name="attachment1" accept=".pdf,.doc,.docx">
    <input type="file" name="attachment2" accept=".jpg,.jpeg,.png">
    <input type="file" name="attachment3" accept=".zip,.rar">
    
    <button type="submit">Create Task</button>
</form>
```

#### JavaScript (FormData)
```javascript
const formData = new FormData();
formData.append('title', 'Multiple Files Assignment');
formData.append('type', 'assignment');
formData.append('points', '100');
formData.append('instructions', 'Complete this assignment with multiple files');
formData.append('class_codes', JSON.stringify(['CLASS001']));
formData.append('assignment_type', 'classroom');
formData.append('allow_comments', '1');
formData.append('is_draft', '0');
formData.append('due_date', '2025-01-25 23:59:00');

// Add files with different field names
formData.append('attachment1', document.getElementById('file1').files[0]);
formData.append('attachment2', document.getElementById('file2').files[0]);
formData.append('attachment3', document.getElementById('file3').files[0]);

fetch('/api/tasks/create', {
    method: 'POST',
    headers: {
        'Authorization': 'Bearer ' + token
    },
    body: formData
})
.then(response => response.json())
.then(data => {
    if (data.success) {
        console.log('Task created successfully!');
        console.log('Task ID:', data.data.task_id);
        console.log('Attachments:', data.data.attachments);
    } else {
        console.error('Error:', data.message);
    }
});
```

## Supported File Types
- **Images**: gif, jpg, jpeg, png, webp
- **Documents**: pdf, doc, docx, ppt, pptx, xls, xlsx, txt
- **Archives**: zip, rar
- **Media**: mp4, mp3

## File Size Limit
- Maximum file size: 10MB per file
- No limit on number of files

## Response Format

### Success Response
```json
{
  "success": true,
  "message": "Task created successfully",
  "data": {
    "task_id": 123,
    "title": "Multiple Files Assignment",
    "type": "assignment",
    "points": 100,
    "instructions": "Complete this assignment with multiple files",
    "attachments": [
      {
        "attachment_id": 1,
        "file_name": "abc123def456.pdf",
        "original_name": "assignment_instructions.pdf",
        "file_path": "uploads/tasks/abc123def456.pdf",
        "file_size": 1024000,
        "mime_type": "application/pdf",
        "attachment_type": "file",
        "attachment_url": "uploads/tasks/abc123def456.pdf"
      },
      {
        "attachment_id": 2,
        "file_name": "xyz789ghi012.jpg",
        "original_name": "example_image.jpg",
        "file_path": "uploads/tasks/xyz789ghi012.jpg",
        "file_size": 512000,
        "mime_type": "image/jpeg",
        "attachment_type": "file",
        "attachment_url": "uploads/tasks/xyz789ghi012.jpg"
      }
    ],
    "attachment_count": 2
  },
  "status_code": 201
}
```

## Student Access to Multiple File Tasks

Students can now access tasks with multiple file attachments through the following endpoints:

### 1. Get All Tasks with Attachments
```
GET /api/tasks/student?class_code=CLASS_CODE
```
**Response includes:**
- All task details
- `attachments` array with file information
- `attachment_count` showing total number of files
- Submission status for each task

### 2. Get Individually Assigned Tasks with Attachments
```
GET /api/tasks/student/assigned?class_code=CLASS_CODE
```
**Response includes:**
- Only individually assigned tasks
- `attachments` array with file information
- `attachment_count` showing total number of files
- Submission status for each task

### 3. Get Specific Task Details with Attachments
```
GET /api/tasks/student/{task_id}
```
**Response includes:**
- Complete task details
- `attachments` array with file information
- `attachment_count` showing total number of files
- Student's submission status
- Comments (if allowed)

### 4. Get Task Attachments Only
```
GET /api/tasks/student/{task_id}/attachments
```
**Response includes:**
- Array of attachment objects with:
  - `attachment_id`
  - `file_name`
  - `original_name`
  - `file_path`
  - `file_size`
  - `mime_type`
  - `attachment_type`
  - `attachment_url`
  - `created_at`

### 5. Download Task Files
```
GET /api/tasks/files/{filename}
```
**Response:**
- File content for download/viewing
- Supports all file types (PDF, images, documents, etc.)

## Example Student Response
```json
{
  "status": true,
  "message": "Tasks retrieved successfully",
  "data": [
    {
      "task_id": "70",
      "title": "Research Paper Assignment",
      "type": "assignment",
      "points": "100",
      "instructions": "Write a comprehensive research paper...",
      "due_date": "2025-01-25 23:59:00",
      "teacher_name": "Joel Quiambao",
      "submission_status": "not_submitted",
      "submission_id": null,
      "class_codes": ["J56NHD"],
      "attachments": [
        {
          "attachment_id": "7",
          "file_name": "8bfd66a8d88a3a70614d36aa3192a7bf.pdf",
          "original_name": "SCMS_sections_2025-08-02 (1).pdf",
          "file_path": "uploads/tasks/8bfd66a8d88a3a70614d36aa3192a7bf.pdf",
          "file_size": "295",
          "mime_type": "application/pdf",
          "attachment_type": "file",
          "attachment_url": "uploads/tasks/8bfd66a8d88a3a70614d36aa3192a7bf.pdf",
          "created_at": "2025-08-10 16:02:03"
        }
      ],
      "attachment_count": 1
    }
  ]
}
```

## Postman Testing

### Setup
1. **Request Type**: `POST`
2. **URL**: `{{base_url}}/api/tasks/create`
3. **Headers**:
   - `Authorization: Bearer YOUR_TOKEN_HERE`
   - `Content-Type: multipart/form-data` (Postman will set this automatically)

### Body Configuration
1. **Body Type**: Select `form-data`
2. **Add Fields**:

| Key | Type | Value |
|-----|------|-------|
| `title` | Text | "Multiple Files Assignment" |
| `type` | Text | "assignment" |
| `points` | Text | "100" |
| `instructions` | Text | "Complete this assignment with multiple files" |
| `class_codes` | Text | `["CLASS001"]` |
| `assignment_type` | Text | "classroom" |
| `allow_comments` | Text | "1" |
| `is_draft` | Text | "0" |
| `due_date` | Text | "2025-01-25 23:59:00" |
| `attachment[]` | File | [Select multiple files] |

### Testing Multiple Files
1. **Method 1**: Use `attachment[]` field and select multiple files
2. **Method 2**: Use separate fields like `attachment1`, `attachment2`, etc.

## Frontend Implementation Example

### React Component
```jsx
import React, { useState } from 'react';

const CreateTaskForm = () => {
    const [formData, setFormData] = useState({
        title: '',
        type: 'assignment',
        points: '',
        instructions: '',
        class_codes: ['CLASS001'],
        assignment_type: 'classroom',
        allow_comments: true,
        is_draft: false,
        due_date: ''
    });
    const [files, setFiles] = useState([]);

    const handleFileChange = (e) => {
        setFiles(Array.from(e.target.files));
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        
        const formDataToSend = new FormData();
        
        // Add form fields
        Object.keys(formData).forEach(key => {
            if (key === 'class_codes') {
                formDataToSend.append(key, JSON.stringify(formData[key]));
            } else {
                formDataToSend.append(key, formData[key]);
            }
        });
        
        // Add files
        files.forEach(file => {
            formDataToSend.append('attachment[]', file);
        });
        
        try {
            const response = await fetch('/api/tasks/create', {
                method: 'POST',
                headers: {
                    'Authorization': 'Bearer ' + localStorage.getItem('token')
                },
                body: formDataToSend
            });
            
            const data = await response.json();
            
            if (data.success) {
                alert('Task created successfully!');
                // Reset form
                setFormData({
                    title: '',
                    type: 'assignment',
                    points: '',
                    instructions: '',
                    class_codes: ['CLASS001'],
                    assignment_type: 'classroom',
                    allow_comments: true,
                    is_draft: false,
                    due_date: ''
                });
                setFiles([]);
            } else {
                alert('Error: ' + data.message);
            }
        } catch (error) {
            console.error('Error:', error);
            alert('Failed to create task');
        }
    };

    return (
        <form onSubmit={handleSubmit} encType="multipart/form-data">
            <div>
                <label>Title:</label>
                <input
                    type="text"
                    value={formData.title}
                    onChange={(e) => setFormData({...formData, title: e.target.value})}
                    required
                />
            </div>
            
            <div>
                <label>Type:</label>
                <select
                    value={formData.type}
                    onChange={(e) => setFormData({...formData, type: e.target.value})}
                >
                    <option value="assignment">Assignment</option>
                    <option value="quiz">Quiz</option>
                    <option value="activity">Activity</option>
                    <option value="project">Project</option>
                    <option value="exam">Exam</option>
                </select>
            </div>
            
            <div>
                <label>Points:</label>
                <input
                    type="number"
                    value={formData.points}
                    onChange={(e) => setFormData({...formData, points: e.target.value})}
                    required
                />
            </div>
            
            <div>
                <label>Instructions:</label>
                <textarea
                    value={formData.instructions}
                    onChange={(e) => setFormData({...formData, instructions: e.target.value})}
                    required
                />
            </div>
            
            <div>
                <label>Files:</label>
                <input
                    type="file"
                    multiple
                    onChange={handleFileChange}
                    accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.gif,.webp,.ppt,.pptx,.xls,.xlsx,.txt,.zip,.rar,.mp4,.mp3"
                />
            </div>
            
            <div>
                <label>Due Date:</label>
                <input
                    type="datetime-local"
                    value={formData.due_date}
                    onChange={(e) => setFormData({...formData, due_date: e.target.value})}
                />
            </div>
            
            <button type="submit">Create Task</button>
        </form>
    );
};

export default CreateTaskForm;
```

## Error Handling

### Common Errors
1. **File too large**: File exceeds 10MB limit
2. **Invalid file type**: File type not supported
3. **Upload directory not writable**: Server configuration issue
4. **Missing required fields**: Title, type, points, instructions, or class_codes

### Error Response Format
```json
{
  "success": false,
  "message": "File upload failed: File too large",
  "data": null,
  "status_code": 400
}
```

## Backward Compatibility
- Existing single file uploads continue to work
- The system automatically detects single vs. multiple files
- Single files are stored in the original `attachment_url` field
- Multiple files are stored in the new `task_attachments` table

## Security Features
- File type validation
- File size limits
- Encrypted file names
- Teacher-only access to task management
- Proper file path validation

## Performance Considerations
- Files are uploaded in parallel when possible
- Database transactions ensure data consistency
- Efficient file serving with proper headers
- Indexed database queries for fast retrieval
