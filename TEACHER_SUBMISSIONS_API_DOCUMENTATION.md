# Teacher Submissions API Documentation

## Overview
This API endpoint allows teachers to retrieve all student submissions for a specific task, including their attachments and detailed information. This is perfect for the teacher's view shown in your frontend screenshot.

## Endpoint

### Get All Student Submissions for a Task
```
GET {{base_url}}/api/tasks/{task_id}/submissions
```

**Description:** Retrieves all student submissions with attachments for a specific task (Teacher only)

**Authentication:** Required (Teacher token)

**Parameters:**
- `task_id` (path parameter): The ID of the task

**Headers:**
```
Authorization: Bearer {teacher_token}
Content-Type: application/json
```

## Example Request

```bash
curl -X GET "http://localhost/scms_new_backup/api/tasks/58/submissions" \
  -H "Authorization: Bearer your_teacher_token" \
  -H "Content-Type: application/json"
```

## Response Format

### Success Response (200)
```json
{
  "status": "success",
  "message": "Task submissions retrieved successfully",
  "data": {
    "task": {
      "task_id": 58,
      "title": "Task Testing due date",
      "type": "EXAM",
      "points": 50,
      "due_date": "2025-08-11",
      "instructions": "Complete the assignment as specified...",
      "teacher_id": "T001",
      "class_codes": ["J56NHD"],
      "assignment_type": "classroom",
      "assigned_students": null,
      "allow_comments": true,
      "is_draft": false,
      "is_scheduled": false,
      "created_at": "2024-01-10 09:00:00",
      "updated_at": "2024-01-10 09:00:00"
    },
    "submissions": [
      {
        "submission_id": 1,
        "task_id": 58,
        "student_id": "S001",
        "class_code": "J56NHD",
        "student_name": "CHRISTINE NOAH G. SINGIAN",
        "student_num": "2022311852",
        "email": "christine.singian@example.com",
        "profile_pic": "profile/christine.jpg",
        "submission_content": "Here is my research paper submission...",
        "submitted_at": "2024-01-15 10:30:00",
        "grade": null,
        "feedback": null,
        "status": "submitted",
        "attachments": [
          {
            "attachment_id": 1,
            "submission_id": 1,
            "file_name": "abc123.pdf",
            "original_name": "research_paper.pdf",
            "file_path": "uploads/submissions/abc123.pdf",
            "file_size": 1024000,
            "mime_type": "application/pdf",
            "attachment_type": "file",
            "attachment_url": null,
            "created_at": "2024-01-15 10:30:00"
          },
          {
            "attachment_id": 2,
            "submission_id": 1,
            "file_name": "def456.zip",
            "original_name": "supporting_documents.zip",
            "file_path": "uploads/submissions/def456.zip",
            "file_size": 2048000,
            "mime_type": "application/zip",
            "attachment_type": "file",
            "attachment_url": null,
            "created_at": "2024-01-15 10:30:00"
          }
        ],
        "attachment_count": 2
      },
      {
        "submission_id": 2,
        "task_id": 58,
        "student_id": "S002",
        "class_code": "J56NHD",
        "student_name": "Carll Morales",
        "student_num": "2022548988",
        "email": "carll.morales@example.com",
        "profile_pic": "profile/carll.jpg",
        "submission_content": "My assignment submission...",
        "submitted_at": "2024-01-16 14:20:00",
        "grade": null,
        "feedback": null,
        "status": "submitted",
        "attachments": [],
        "attachment_count": 0
      }
    ],
    "total_submissions": 3,
    "submitted_count": 2,
    "graded_count": 0
  }
}
```

### Error Responses

#### 404 - Task Not Found
```json
{
  "status": "error",
  "message": "Task not found or access denied"
}
```

#### 401 - Unauthorized
```json
{
  "status": "error",
  "message": "Unauthorized access"
}
```

#### 500 - Server Error
```json
{
  "status": "error",
  "message": "Failed to retrieve task submissions: {error_message}"
}
```

## Response Fields

### Task Object
- `task_id`: Unique identifier for the task
- `title`: Task title
- `type`: Task type (EXAM, ASSIGNMENT, QUIZ, etc.)
- `points`: Maximum points for the task
- `due_date`: Task due date
- `instructions`: Task instructions
- `teacher_id`: ID of the teacher who created the task
- `class_codes`: Array of class codes this task is assigned to
- `assignment_type`: Type of assignment (classroom/individual)
- `assigned_students`: Array of specifically assigned students (for individual assignments)
- `allow_comments`: Whether comments are allowed
- `is_draft`: Whether the task is a draft
- `is_scheduled`: Whether the task is scheduled for future publication

### Submission Object
- `submission_id`: Unique identifier for the submission
- `task_id`: ID of the task
- `student_id`: ID of the student
- `class_code`: Class code the student submitted for
- `student_name`: Full name of the student
- `student_num`: Student number/ID
- `email`: Student's email address
- `profile_pic`: Path to student's profile picture
- `submission_content`: Text content of the submission
- `submitted_at`: Timestamp when the submission was made
- `grade`: Grade given to the submission (null if not graded)
- `feedback`: Feedback given to the submission (null if not provided)
- `status`: Submission status (submitted, graded, etc.)
- `attachments`: Array of attachment objects
- `attachment_count`: Number of attachments

### Attachment Object
- `attachment_id`: Unique identifier for the attachment
- `submission_id`: ID of the submission this attachment belongs to
- `file_name`: Encrypted filename stored on server
- `original_name`: Original filename uploaded by student
- `file_path`: Path to the file on server
- `file_size`: Size of the file in bytes
- `mime_type`: MIME type of the file
- `attachment_type`: Type of attachment (file, link, youtube, google_drive)
- `attachment_url`: URL for external attachments (null for files)
- `created_at`: Timestamp when attachment was created

### Summary Statistics
- `total_submissions`: Total number of submissions for the task
- `submitted_count`: Number of students who have submitted
- `graded_count`: Number of submissions that have been graded

## Frontend Integration

### React/JavaScript Example
```javascript
// Fetch teacher submissions
async function getTeacherSubmissions(taskId, token) {
  try {
    const response = await fetch(`/api/tasks/${taskId}/submissions`, {
      method: 'GET',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json'
      }
    });
    
    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }
    
    const data = await response.json();
    return data.data;
  } catch (error) {
    console.error('Error fetching submissions:', error);
    throw error;
  }
}

// Usage example
const submissionsData = await getTeacherSubmissions(58, teacherToken);
console.log('Task:', submissionsData.task);
console.log('Submissions:', submissionsData.submissions);
console.log('Statistics:', {
  total: submissionsData.total_submissions,
  submitted: submissionsData.submitted_count,
  graded: submissionsData.graded_count
});
```

### Displaying Student Submissions
```javascript
// Render student submissions list
function renderSubmissions(submissions) {
  return submissions.map(submission => (
    <div key={submission.submission_id} className="submission-card">
      <div className="student-info">
        <img src={submission.profile_pic} alt={submission.student_name} />
        <div>
          <h4>{submission.student_name}</h4>
          <p>ID: {submission.student_num}</p>
          <p>Status: {submission.status}</p>
          <p>Grade: {submission.grade || '--'}/{submission.task?.points || '--'}</p>
        </div>
      </div>
      
      {submission.submission_content && (
        <div className="submission-content">
          <p>{submission.submission_content}</p>
        </div>
      )}
      
      {submission.attachments.length > 0 && (
        <div className="attachments">
          <h5>Attachments ({submission.attachment_count}):</h5>
          {submission.attachments.map(attachment => (
            <div key={attachment.attachment_id} className="attachment">
              <span>📎 {attachment.original_name}</span>
              <span>({formatFileSize(attachment.file_size)})</span>
              <button onClick={() => downloadFile(attachment)}>Download</button>
            </div>
          ))}
        </div>
      )}
    </div>
  ));
}
```

## Testing

### Using the Test File
1. Open `test_teacher_submissions_endpoint.html` in your browser
2. Configure the base URL, task ID, and teacher token
3. Click "Test Teacher Submissions Endpoint" to test the API
4. View the formatted results showing student submissions with attachments

### Postman Testing
1. Create a new GET request
2. URL: `{{base_url}}/api/tasks/58/submissions`
3. Headers:
   - `Authorization: Bearer your_teacher_token`
   - `Content-Type: application/json`
4. Send the request and view the response

## Security Notes

- Only teachers can access this endpoint
- Teachers can only view submissions for tasks they created
- Student information is included but protected by teacher authentication
- File paths are returned but actual file access requires additional authentication

## Related Endpoints

- `GET /api/tasks/{task_id}` - Get task details
- `GET /api/tasks/submissions/{submission_id}` - Get specific submission
- `POST /api/tasks/submissions/{submission_id}/grade` - Grade a submission
- `GET /api/tasks/{task_id}/stats` - Get task statistics

## File Access

To access the actual files, use the file serving endpoint:
```
GET {{base_url}}/api/tasks/submissions/files/{filename}
```

This endpoint will serve the actual file content for download or preview.
