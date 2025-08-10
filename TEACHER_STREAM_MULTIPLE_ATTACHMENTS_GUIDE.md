# Teacher Stream Multiple Attachments Guide

## Overview

The teacher stream API now supports multiple file attachments, similar to the class tasks functionality. Teachers can upload one or multiple files when creating stream posts (announcements).

## Database Changes

### New Table: `stream_attachments`

```sql
CREATE TABLE IF NOT EXISTS `stream_attachments` (
  `attachment_id` int(11) NOT NULL AUTO_INCREMENT,
  `stream_id` int(11) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `file_path` text NOT NULL,
  `file_size` int(11) DEFAULT NULL,
  `mime_type` varchar(100) DEFAULT NULL,
  `attachment_type` enum('file','link','youtube','google_drive') DEFAULT 'file',
  `attachment_url` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`attachment_id`),
  KEY `idx_stream_id` (`stream_id`),
  KEY `idx_attachment_type` (`attachment_type`),
  CONSTRAINT `fk_stream_attachments_stream` FOREIGN KEY (`stream_id`) REFERENCES `classroom_stream` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

## API Usage

### Endpoint
```
POST /api/teacher/classroom/{class_code}/stream
```

### Request Format

#### Option 1: Multipart Form-Data (Recommended for file uploads)
```http
Content-Type: multipart/form-data

title: "Announcement with Multiple Files"
content: "Please review the attached documents"
is_draft: 0
is_scheduled: 0
scheduled_at: ""
allow_comments: 1
student_ids: ["2021302596", "2021305889", "2021305973"]
attachment_0: [file1.pdf]
attachment_1: [file2.jpg]
attachment_2: [file3.docx]
```

#### Option 2: JSON Body (for text-only posts or when using existing file URLs)
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

### File Upload Details

- **Supported Formats**: PDF, DOC, DOCX, JPG, JPEG, PNG, GIF, TXT, ZIP, RAR, MP4, MP3
- **Maximum File Size**: 10MB per file
- **Upload Directory**: `uploads/announcement/`
- **File Naming**: Original filenames are preserved (sanitized for security)

### Response Format

#### Success Response
```json
{
  "success": true,
  "message": "Announcement posted successfully",
  "data": {
    "id": 123,
    "class_code": "A4V9TE",
    "user_id": "TEA123",
    "title": "Announcement with Multiple Files",
    "content": "Please review the attached documents",
    "is_draft": 0,
    "is_scheduled": 0,
    "scheduled_at": null,
    "allow_comments": 1,
    "attachment_type": "multiple",
    "attachment_url": null,
    "created_at": "2025-01-15 10:30:00"
  }
}
```

#### Error Response
```json
{
  "success": false,
  "message": "Upload failed for attachment_0: File size exceeds limit",
  "data": null,
  "status_code": 400
}
```

## Retrieving Stream Posts

### Single Attachment (Backward Compatible)
When a stream post has a single attachment, the response includes:
```json
{
  "attachment_type": "file",
  "attachment_url": "uploads/announcements/document.pdf",
  "attachment_serving_url": "http://localhost/scms_new_backup/uploads/announcements/document.pdf",
  "attachment_file_type": "pdf"
}
```

### Multiple Attachments
When a stream post has multiple attachments, the response includes:
```json
{
  "attachment_type": "multiple",
  "attachment_url": null,
  "attachments": [
    {
      "attachment_id": 1,
      "file_name": "document1.pdf",
      "original_name": "Assignment Guidelines.pdf",
      "file_path": "uploads/announcements/document1.pdf",
      "file_size": 1024000,
      "mime_type": "application/pdf",
      "attachment_type": "file",
      "attachment_url": "uploads/announcements/document1.pdf",
      "serving_url": "http://localhost/scms_new_backup/uploads/announcements/document1.pdf",
      "file_type": "pdf"
    },
    {
      "attachment_id": 2,
      "file_name": "image1.jpg",
      "original_name": "Sample Image.jpg",
      "file_path": "uploads/announcements/image1.jpg",
      "file_size": 512000,
      "mime_type": "image/jpeg",
      "attachment_type": "file",
      "attachment_url": "uploads/announcements/image1.jpg",
      "serving_url": "http://localhost/scms_new_backup/uploads/announcements/image1.jpg",
      "file_type": "jpg"
    }
  ],
  "attachment_serving_url": "http://localhost/scms_new_backup/uploads/announcements/document1.pdf",
  "attachment_file_type": "pdf"
}
```

## Frontend Implementation

### HTML Form Example
```html
<form id="streamForm" enctype="multipart/form-data">
  <input type="text" name="title" placeholder="Title" required>
  <textarea name="content" placeholder="Content" required></textarea>
  
  <div id="fileInputs">
    <div class="file-input">
      <input type="file" name="attachment_0" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.gif,.txt,.zip,.rar,.mp4,.mp3">
      <button type="button" onclick="removeFile(this)">Remove</button>
    </div>
  </div>
  
  <button type="button" onclick="addFileInput()">Add Another File</button>
  <button type="submit">Post to Stream</button>
</form>
```

### JavaScript Example
```javascript
document.getElementById('streamForm').addEventListener('submit', async function(e) {
  e.preventDefault();
  
  const formData = new FormData();
  
  // Add form fields
  formData.append('title', document.querySelector('[name="title"]').value);
  formData.append('content', document.querySelector('[name="content"]').value);
  
  // Add files
  const fileInputs = document.querySelectorAll('input[type="file"]');
  fileInputs.forEach((input, index) => {
    if (input.files[0]) {
      formData.append(`attachment_${index}`, input.files[0]);
    }
  });
  
  try {
    const response = await fetch('/api/teacher/classroom/A4V9TE/stream', {
      method: 'POST',
      body: formData
    });
    
    const result = await response.json();
    if (result.success) {
      console.log('Stream post created successfully:', result.data);
    } else {
      console.error('Error:', result.message);
    }
  } catch (error) {
    console.error('Network error:', error);
  }
});

function addFileInput() {
  const fileInputs = document.getElementById('fileInputs');
  const index = fileInputs.children.length;
  const newInput = document.createElement('div');
  newInput.className = 'file-input';
  newInput.innerHTML = `
    <input type="file" name="attachment_${index}" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.gif,.txt,.zip,.rar,.mp4,.mp3">
    <button type="button" onclick="removeFile(this)">Remove</button>
  `;
  fileInputs.appendChild(newInput);
}

function removeFile(button) {
  const fileInputs = document.getElementById('fileInputs');
  if (fileInputs.children.length > 1) {
    button.parentElement.remove();
  }
}
```

## Postman Testing

### Test with Multiple Files
1. Set method to `POST`
2. Set URL to `{{base_url}}/api/teacher/classroom/A4V9TE/stream`
3. Select `Body` tab
4. Choose `form-data`
5. Add fields:
   - `title`: "Test Multiple Attachments"
   - `content`: "Testing the new multiple attachment feature"
   - `is_draft`: 0
   - `allow_comments`: 1
   - `student_ids`: ["2021302596", "2021305889"]
6. Add files:
   - `attachment_0`: Select first file
   - `attachment_1`: Select second file
   - `attachment_2`: Select third file
7. Send request

### Test with Single File
1. Follow same steps as above
2. Add only one file field: `attachment_0`
3. The system will automatically set `attachment_type` to "file"

## Backward Compatibility

- Existing single-attachment stream posts continue to work
- The API maintains the same response structure for single attachments
- New `attachments` array is only present when `attachment_type` is "multiple"

## Error Handling

Common error scenarios and solutions:

1. **File Size Exceeds Limit**
   - Error: "File size exceeds limit"
   - Solution: Ensure files are under 10MB

2. **Invalid File Type**
   - Error: "File type not allowed"
   - Solution: Use supported file formats

3. **Upload Directory Issues**
   - Error: "Failed to create upload directory"
   - Solution: Ensure `uploads/announcement/` directory exists and is writable

4. **Database Constraint Violation**
   - Error: "Foreign key constraint failed"
   - Solution: Ensure the stream post exists before adding attachments

## Security Features

- File names are sanitized to prevent path traversal attacks
- File types are validated against allowed extensions
- File size limits prevent abuse
- Original filenames are preserved for user convenience
- Files are stored outside the web root for security

## Performance Considerations

- Multiple attachments are loaded efficiently using batch queries
- File serving URLs are generated on-demand
- Database indexes optimize attachment retrieval
- File metadata is cached in the response to reduce database calls
