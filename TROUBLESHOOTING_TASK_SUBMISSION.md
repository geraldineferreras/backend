# Troubleshooting Task Submission Issues

## Problem: "At least one attachment is required" Error

This error occurs when the API doesn't receive a file attachment or text content properly.

## Debugging Steps

### 1. Use the Debug Tool

First, use the debug tool I created:

1. Open `debug_task_submission.html` in your browser
2. Go to the "Debug Upload" tab
3. Enter your JWT token
4. Select a file and fill in the form
5. Click "Debug Upload"
6. Check the response to see what the server receives

### 2. Check Your Frontend Code

Make sure your React code follows this pattern:

```javascript
const handleSubmitFiles = async () => {
  try {
    const formData = new FormData();
    
    // Add text content if provided
    if (submissionContent.trim()) {
      formData.append('submission_content', submissionContent);
    }
    
    // Add class code
    formData.append('class_code', classCode);
    
    // Add files - IMPORTANT: field name must be 'attachment'
    files.forEach((file) => {
      formData.append('attachment', file);
    });

    const response = await api.post(`/tasks/${taskId}/submit`, formData, {
      headers: {
        'Authorization': `Bearer ${token}`,
        // DON'T set Content-Type - let browser set it for FormData
      }
    });

    console.log('Success:', response.data);
  } catch (error) {
    console.error('Error:', error.response?.data || error.message);
  }
};
```

### 3. Common Issues and Solutions

#### Issue 1: Wrong Field Name
**Problem:** Using wrong field name for file upload
**Solution:** Use `attachment` as the field name

```javascript
// ❌ Wrong
formData.append('file', file);
formData.append('files', file);

// ✅ Correct
formData.append('attachment', file);
```

#### Issue 2: Setting Content-Type Header
**Problem:** Setting Content-Type header manually
**Solution:** Let the browser set it automatically for FormData

```javascript
// ❌ Wrong
headers: {
  'Authorization': `Bearer ${token}`,
  'Content-Type': 'multipart/form-data' // Don't set this!
}

// ✅ Correct
headers: {
  'Authorization': `Bearer ${token}`
  // Let browser set Content-Type automatically
}
```

#### Issue 3: Not Using FormData
**Problem:** Sending JSON instead of FormData
**Solution:** Use FormData for file uploads

```javascript
// ❌ Wrong - JSON for file uploads
const data = {
  submission_content: 'text',
  class_code: 'MATH101',
  file: file // This won't work!
};

// ✅ Correct - FormData for file uploads
const formData = new FormData();
formData.append('submission_content', 'text');
formData.append('class_code', 'MATH101');
formData.append('attachment', file);
```

#### Issue 4: File Not Selected
**Problem:** No file is actually selected
**Solution:** Add validation

```javascript
if (files.length === 0) {
  alert('Please select at least one file');
  return;
}
```

#### Issue 5: File Size Too Large
**Problem:** File exceeds 10MB limit
**Solution:** Check file size before upload

```javascript
const maxSize = 10 * 1024 * 1024; // 10MB
if (file.size > maxSize) {
  alert('File size exceeds 10MB limit');
  return;
}
```

### 4. Check Server Logs

The API now includes debug logging. Check your server logs for these messages:

```
Task submission debug - Content-Type: multipart/form-data
Task submission debug - POST data: Array(...)
Task submission debug - FILES data: Array(...)
Task submission debug - submission_content: your text
Task submission debug - attachment_url: uploads/submissions/filename.pdf
```

### 5. Test with Different Files

Try uploading different file types to see if the issue is file-specific:

- Small text file (.txt)
- PDF file
- Image file (.jpg, .png)
- Document file (.doc, .docx)

### 6. Check File Permissions

Make sure the upload directory exists and is writable:

```bash
# Check if directory exists
ls -la uploads/submissions/

# Create directory if it doesn't exist
mkdir -p uploads/submissions/

# Set proper permissions
chmod 755 uploads/submissions/
```

### 7. Browser Developer Tools

1. Open Developer Tools (F12)
2. Go to Network tab
3. Submit a file
4. Check the request:
   - **Request URL:** Should be `/api/tasks/23/submit`
   - **Request Method:** Should be `POST`
   - **Content-Type:** Should be `multipart/form-data; boundary=...`
   - **Request Payload:** Should show FormData with your file

### 8. Axios Configuration

If using Axios, make sure you're not overriding the Content-Type:

```javascript
// ❌ Wrong - This will break file uploads
const api = axios.create({
  headers: {
    'Content-Type': 'application/json' // This breaks FormData
  }
});

// ✅ Correct - Let Axios handle Content-Type automatically
const api = axios.create({
  // Don't set Content-Type globally
});
```

### 9. React State Management

Make sure your file state is properly managed:

```javascript
const [files, setFiles] = useState([]);

const handleFileChange = (event) => {
  const selectedFiles = Array.from(event.target.files);
  setFiles(selectedFiles);
  console.log('Files selected:', selectedFiles); // Debug
};
```

### 10. Complete Working Example

Here's a complete working example:

```javascript
import React, { useState } from 'react';
import axios from 'axios';

const TaskSubmission = ({ taskId }) => {
  const [files, setFiles] = useState([]);
  const [submissionContent, setSubmissionContent] = useState('');
  const [classCode, setClassCode] = useState('');
  const [isSubmitting, setIsSubmitting] = useState(false);

  const handleSubmit = async (e) => {
    e.preventDefault();
    
    if (files.length === 0 && !submissionContent.trim()) {
      alert('Please provide either a file or text content');
      return;
    }

    setIsSubmitting(true);

    try {
      const formData = new FormData();
      
      if (submissionContent.trim()) {
        formData.append('submission_content', submissionContent);
      }
      
      formData.append('class_code', classCode);
      
      files.forEach((file) => {
        formData.append('attachment', file);
      });

      const token = localStorage.getItem('token');
      
      const response = await axios.post(`/api/tasks/${taskId}/submit`, formData, {
        headers: {
          'Authorization': `Bearer ${token}`
        }
      });

      console.log('Success:', response.data);
      alert('Task submitted successfully!');
      
    } catch (error) {
      console.error('Error:', error.response?.data || error.message);
      alert('Failed to submit task: ' + (error.response?.data?.message || error.message));
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <form onSubmit={handleSubmit}>
      <div>
        <label>Submission Content:</label>
        <textarea
          value={submissionContent}
          onChange={(e) => setSubmissionContent(e.target.value)}
          placeholder="Enter your submission content..."
        />
      </div>
      
      <div>
        <label>Class Code:</label>
        <input
          type="text"
          value={classCode}
          onChange={(e) => setClassCode(e.target.value)}
          required
        />
      </div>
      
      <div>
        <label>Files:</label>
        <input
          type="file"
          onChange={(e) => setFiles(Array.from(e.target.files))}
          multiple
          accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.gif,.webp,.ppt,.pptx,.xls,.xlsx,.txt,.zip,.rar,.mp4,.mp3"
        />
      </div>
      
      <button type="submit" disabled={isSubmitting}>
        {isSubmitting ? 'Submitting...' : 'Submit Task'}
      </button>
    </form>
  );
};

export default TaskSubmission;
```

## Next Steps

1. Use the debug tool to see what's being sent
2. Check your frontend code against the examples above
3. Look at server logs for debug information
4. Test with the provided React example
5. If still having issues, share the debug output and your frontend code 