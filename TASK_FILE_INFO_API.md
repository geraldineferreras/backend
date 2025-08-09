# Task File Information API Documentation

This document describes the new endpoints for getting information about task files, including original filenames for files stored with hashed names in the `uploads/tasks` directory.

## Overview

The task file information endpoints help resolve the issue where task attachments are stored with encrypted/hashed filenames (like `55531ede80d7ff9617a3e073f55597c4.pdf`) but you need to display the original filename in your frontend.

## Authentication

All endpoints require JWT authentication.

**Header:** `Authorization: Bearer <jwt_token>`

## Endpoints

### 1. Get File Information by Filename

**Endpoint:** `GET /api/tasks/files/info/{filename}`

**Description:** Retrieves detailed information about a specific task file, including its original name based on the associated task.

**URL Parameters:**
- `filename` (required): The hashed filename (e.g., `55531ede80d7ff9617a3e073f55597c4.pdf`)

**Headers:**
- `Authorization: Bearer <jwt_token>` (required)
- `Content-Type: application/json`

**Response:**

**Success (200):**
```json
{
    "status": true,
    "message": "File information retrieved successfully",
    "data": {
        "filename": "55531ede80d7ff9617a3e073f55597c4.pdf",
        "original_name": "Research Paper Assignment.pdf",
        "file_size": 2048576,
        "file_size_formatted": "2.00 MB",
        "mime_type": "application/pdf",
        "extension": "pdf",
        "file_path": "uploads/tasks/55531ede80d7ff9617a3e073f55597c4.pdf",
        "download_url": "http://localhost/scms_new/api/tasks/files/55531ede80d7ff9617a3e073f55597c4.pdf",
        "task_info": {
            "task_id": "55",
            "title": "Research Paper Assignment",
            "type": "assignment"
        }
    }
}
```

**Error Responses:**

**404 Not Found:**
```json
{
    "status": false,
    "message": "File not found: filename.pdf"
}
```

**401 Unauthorized:**
```json
{
    "status": false,
    "message": "Authentication required. Please login."
}
```

### 2. List All Task Files

**Endpoint:** `GET /api/tasks/files/list`

**Description:** Retrieves information about all task files in the uploads/tasks directory.

**Headers:**
- `Authorization: Bearer <jwt_token>` (required)
- `Content-Type: application/json`

**Response:**

**Success (200):**
```json
{
    "status": true,
    "message": "Task files list retrieved successfully",
    "data": [
        {
            "filename": "55531ede80d7ff9617a3e073f55597c4.pdf",
            "original_name": "Research Paper Assignment.pdf",
            "file_size": 2048576,
            "file_size_formatted": "2.00 MB",
            "mime_type": "application/pdf",
            "extension": "pdf",
            "file_path": "uploads/tasks/55531ede80d7ff9617a3e073f55597c4.pdf",
            "download_url": "http://localhost/scms_new/api/tasks/files/55531ede80d7ff9617a3e073f55597c4.pdf",
            "task_info": {
                "task_id": "55",
                "title": "Research Paper Assignment",
                "type": "assignment"
            }
        },
        {
            "filename": "0ec255c90d87e46efdbc476d59b51b95.pdf",
            "original_name": "Task Testing due date.pdf",
            "file_size": 1048576,
            "file_size_formatted": "1.00 MB",
            "mime_type": "application/pdf",
            "extension": "pdf",
            "file_path": "uploads/tasks/0ec255c90d87e46efdbc476d59b51b95.pdf",
            "download_url": "http://localhost/scms_new/api/tasks/files/0ec255c90d87e46efdbc476d59b51b95.pdf",
            "task_info": {
                "task_id": "58",
                "title": "Task Testing due date",
                "type": "exam"
            }
        }
    ]
}
```

## Usage Examples

### JavaScript/Fetch API

#### Get File Information
```javascript
const filename = '55531ede80d7ff9617a3e073f55597c4.pdf';
const response = await fetch(`/api/tasks/files/info/${encodeURIComponent(filename)}`, {
    method: 'GET',
    headers: {
        'Authorization': `Bearer ${jwtToken}`,
        'Content-Type': 'application/json'
    }
});

const data = await response.json();

if (data.status) {
    console.log('Original Name:', data.data.original_name);
    console.log('File Size:', data.data.file_size_formatted);
    console.log('Download URL:', data.data.download_url);
    
    // Use original name in your frontend
    const displayName = data.data.original_name || data.data.filename;
    console.log('Display Name:', displayName);
}
```

#### List All Files
```javascript
const response = await fetch('/api/tasks/files/list', {
    method: 'GET',
    headers: {
        'Authorization': `Bearer ${jwtToken}`,
        'Content-Type': 'application/json'
    }
});

const data = await response.json();

if (data.status) {
    data.data.forEach(file => {
        console.log(`File: ${file.filename}`);
        console.log(`Original Name: ${file.original_name || 'Not available'}`);
        console.log(`Size: ${file.file_size_formatted}`);
        console.log(`Task: ${file.task_info ? file.task_info.title : 'Unknown'}`);
    });
}
```

### cURL

#### Get File Information
```bash
curl -X GET "http://localhost/scms_new/api/tasks/files/info/55531ede80d7ff9617a3e073f55597c4.pdf" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json"
```

#### List All Files
```bash
curl -X GET "http://localhost/scms_new/api/tasks/files/list" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json"
```

### Postman

#### Get File Information
1. **Method:** GET
2. **URL:** `{{base_url}}/api/tasks/files/info/55531ede80d7ff9617a3e073f55597c4.pdf`
3. **Headers:**
   - `Authorization: Bearer {{jwt_token}}`
   - `Content-Type: application/json`

#### List All Files
1. **Method:** GET
2. **URL:** `{{base_url}}/api/tasks/files/list`
3. **Headers:**
   - `Authorization: Bearer {{jwt_token}}`
   - `Content-Type: application/json`

## How It Works

### Original Name Resolution

The system resolves original filenames by:

1. **Database Lookup:** Searches the `class_tasks` table for tasks that use the given filename as their `attachment_url`
2. **Actual Original Filename:** Returns the stored `original_filename` if available
3. **Fallback to Task Title:** If no original filename is stored, constructs the name using the task title and file extension
4. **Final Fallback:** If no task is found, returns `null` for `original_name`

### Example Resolution

- **Hashed Filename:** `55531ede80d7ff9617a3e073f55597c4.pdf`
- **Database Lookup:** Finds task with `attachment_url = '55531ede80d7ff9617a3e073f55597c4.pdf'`
- **Stored Original Filename:** "SCMS_sections_2025-08-02 (1).pdf"
- **Original Name:** "SCMS_sections_2025-08-02 (1).pdf"

## Frontend Integration

### Display Original Names

```javascript
// When displaying task attachments in your frontend
function displayTaskAttachment(attachmentUrl) {
    // Get file info to resolve original name
    fetch(`/api/tasks/files/info/${encodeURIComponent(attachmentUrl)}`, {
        headers: { 'Authorization': `Bearer ${jwtToken}` }
    })
    .then(response => response.json())
    .then(data => {
        if (data.status) {
            const displayName = data.data.original_name || data.data.filename;
            const downloadUrl = data.data.download_url;
            
            // Use displayName in your UI
            document.getElementById('attachment-name').textContent = displayName;
            document.getElementById('download-link').href = downloadUrl;
        }
    });
}
```

### Batch Processing

```javascript
// For multiple files, you can use the list endpoint
fetch('/api/tasks/files/list', {
    headers: { 'Authorization': `Bearer ${jwtToken}` }
})
.then(response => response.json())
.then(data => {
    if (data.status) {
        // Create a mapping of hashed names to original names
        const filenameMap = {};
        data.data.forEach(file => {
            filenameMap[file.filename] = file.original_name || file.filename;
        });
        
        // Use this mapping in your frontend
        console.log('Filename mapping:', filenameMap);
    }
});
```

## Security Features

1. **Authentication Required:** Only authenticated users can access file information
2. **File Validation:** Verifies that files exist before returning information
3. **Path Security:** Only allows access to files in the `uploads/tasks` directory
4. **Task Association:** Only returns information for files that are actually used by tasks

## Error Handling

The endpoints include comprehensive error handling for:
- Missing or invalid JWT tokens
- Files that don't exist
- Database connection issues
- Server errors

## Testing

You can test these endpoints using the provided test file:
- **Test File:** `test_task_file_info.html`
- **Access:** `http://localhost/scms_new/test_task_file_info.html`

## Benefits

1. **Frontend Display:** Display meaningful filenames instead of hashed names
2. **User Experience:** Better user experience with readable file names
3. **File Management:** Easier file management and identification
4. **Backward Compatibility:** Works with existing hashed filenames
5. **Performance:** Efficient database lookups for filename resolution

## Limitations

1. **Task Association Required:** Original names are only available for files that are associated with tasks in the database
2. **Legacy Data:** Tasks created before this update will not have stored original filenames and will fallback to task title + extension
3. **Extension Preservation:** Only the extension is preserved from the original upload for legacy data

## Future Enhancements

Consider these improvements for future versions:
1. Store actual original filenames during upload
2. Add support for multiple attachments per task
3. Implement file metadata storage
4. Add file preview capabilities
