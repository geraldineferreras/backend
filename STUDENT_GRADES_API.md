# Student Grades API Documentation

## Overview
This endpoint allows students to view their grades for all tasks across their enrolled classes, with filtering options and academic performance statistics.

## Endpoint
```
GET /api/student/grades
```

## Authentication
- Requires student authentication
- Include JWT token in Authorization header: `Authorization: Bearer <token>`

## Query Parameters
- `class_code` (optional): Filter grades by specific class code
- `status` (optional): Filter by submission status
  - `all` - Show all tasks (default)
  - `graded` - Show only graded tasks
  - `submitted` - Show only submitted but not graded tasks
  - `not_submitted` - Show only tasks not submitted

## Request Headers
```
Authorization: Bearer <jwt_token>
Content-Type: application/json
```

## Response Format

### Success Response (200)
```json
{
  "status": true,
  "message": "Student grades retrieved successfully",
  "data": {
    "academic_performance": {
      "student_name": "Ferreras, Geraldine P.",
      "average_grade": 85.2,
      "total_assignments": 8,
      "graded_assignments": 6
    },
    "grades": [
      {
        "task_id": 23,
        "title": "Text Encryption Model Analysis",
        "type": "assignment",
        "points": 50,
        "due_date": "2024-11-29 17:00:00",
        "submission_id": 45,
        "grade": 45,
        "grade_percentage": 90.0,
        "feedback": "Excellent work on the encryption model!",
        "status": "graded",
        "submitted_at": "2024-11-28 14:30:00",
        "attachment_count": 3,
        "attachment_url": "uploads/submissions/abc123.pdf",
        "attachment_type": "file",
        "class_code": "ABC123"
      },
      {
        "task_id": 24,
        "title": "Database Design Project",
        "type": "project",
        "points": 50,
        "due_date": "2024-11-29 17:00:00",
        "submission_id": 46,
        "grade": 48,
        "grade_percentage": 96.0,
        "feedback": "Great database design!",
        "status": "graded",
        "submitted_at": "2024-11-27 16:45:00",
        "attachment_count": 1,
        "attachment_url": "uploads/submissions/def456.pdf",
        "attachment_type": "file",
        "class_code": "ABC123"
      },
      {
        "task_id": 25,
        "title": "Final Capstone Project",
        "type": "project",
        "points": 100,
        "due_date": "2024-12-13 17:00:00",
        "submission_id": 47,
        "grade": null,
        "grade_percentage": null,
        "feedback": null,
        "status": "submitted",
        "submitted_at": "2024-12-10 09:15:00",
        "attachment_count": 1,
        "attachment_url": "uploads/submissions/ghi789.pdf",
        "attachment_type": "file",
        "class_code": "ABC123"
      }
    ],
    "filters": {
      "available_statuses": ["all", "graded", "submitted", "not_submitted"],
      "available_classes": [
        {
          "class_code": "ABC123",
          "title": "Introduction to Programming",
          "semester": "1st",
          "school_year": "2024-2025"
        }
      ],
      "current_status_filter": "all",
      "current_class_filter": "all"
    }
  }
}
```

### Error Responses

**401 Unauthorized**
```json
{
  "status": false,
  "message": "Authentication required",
  "data": null,
  "status_code": 401
}
```

**404 Not Found**
```json
{
  "status": false,
  "message": "Class not found or you are not enrolled",
  "data": null,
  "status_code": 404
}
```

**500 Internal Server Error**
```json
{
  "status": false,
  "message": "Failed to retrieve student grades: <error_message>",
  "data": null,
  "status_code": 500
}
```

## Validation Rules

1. **Authentication**: Must be a logged-in student
2. **Enrollment**: Student must be enrolled in classes to view grades
3. **Access Control**: Students can only view their own grades
4. **Filtering**: Optional filters for class and status

## Usage Examples

### JavaScript/Fetch
```javascript
const token = 'your_jwt_token_here';

// Get all grades
fetch('/api/student/grades', {
  method: 'GET',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json'
  }
})
.then(response => response.json())
.then(data => {
  if (data.status) {
    console.log('Academic Performance:', data.data.academic_performance);
    console.log('Grades:', data.data.grades);
    console.log('Available Filters:', data.data.filters);
  } else {
    console.error('Error:', data.message);
  }
});

// Get grades for specific class
fetch('/api/student/grades?class_code=ABC123', {
  method: 'GET',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json'
  }
});

// Get only graded assignments
fetch('/api/student/grades?status=graded', {
  method: 'GET',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json'
  }
});
```

### Axios
```javascript
const axios = require('axios');

const token = 'your_jwt_token_here';

// Get all grades
axios.get('/api/student/grades', {
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json'
  }
})
.then(response => {
  const data = response.data;
  if (data.status) {
    console.log('Grades:', data.data.grades);
  } else {
    console.error('Error:', data.message);
  }
})
.catch(error => {
  console.error('Error:', error.response?.data || error.message);
});

// Get grades with filters
axios.get('/api/student/grades', {
  params: {
    class_code: 'ABC123',
    status: 'graded'
  },
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json'
  }
});
```

### cURL
```bash
# Get all grades
curl -X GET \
  "http://localhost:3308/scms_new_backup/index.php/api/student/grades" \
  -H "Authorization: Bearer your_jwt_token_here" \
  -H "Content-Type: application/json"

# Get grades for specific class
curl -X GET \
  "http://localhost:3308/scms_new_backup/index.php/api/student/grades?class_code=ABC123" \
  -H "Authorization: Bearer your_jwt_token_here" \
  -H "Content-Type: application/json"

# Get only graded assignments
curl -X GET \
  "http://localhost:3308/scms_new_backup/index.php/api/student/grades?status=graded" \
  -H "Authorization: Bearer your_jwt_token_here" \
  -H "Content-Type: application/json"
```

## Data Structure

### Academic Performance Object
- `student_name`: Student's full name
- `average_grade`: Average grade percentage across all graded assignments
- `total_assignments`: Total number of assignments
- `graded_assignments`: Number of assignments that have been graded

### Grade Object
- `task_id`: Task database ID
- `title`: Task title
- `type`: Task type (assignment, project, quiz, etc.)
- `points`: Maximum points for the task
- `due_date`: Task due date
- `submission_id`: Student's submission ID (null if not submitted)
- `grade`: Actual grade received (null if not graded)
- `grade_percentage`: Grade as percentage (null if not graded)
- `feedback`: Teacher feedback (null if not provided)
- `status`: Submission status (graded, submitted, not_submitted)
- `submitted_at`: Submission date (null if not submitted)
- `attachment_count`: Number of attached files
- `attachment_url`: URL to submitted file (null if no file)
- `attachment_type`: Type of attachment (file, link, etc.)
- `class_code`: Class code for the task

### Filters Object
- `available_statuses`: Array of available status filter options
- `available_classes`: Array of classes the student is enrolled in
- `current_status_filter`: Currently applied status filter
- `current_class_filter`: Currently applied class filter

## Status Values

- **graded**: Task has been submitted and graded
- **submitted**: Task has been submitted but not yet graded
- **not_submitted**: Task has not been submitted

## Testing

Use the provided test file `test_student_grades.html` to test this endpoint:

1. Open the test file in your browser
2. Enter your JWT token
3. Optionally enter a class code to filter
4. Optionally select a status filter
5. Click "Get My Grades"
6. View the results

## Security Features

1. **Authentication Required**: Only authenticated students can access
2. **Data Privacy**: Students can only view their own grades
3. **Enrollment Verification**: Only shows grades for enrolled classes
4. **Error Handling**: Proper error messages without exposing sensitive data

## Related Endpoints

- `GET /api/tasks/student/{task_id}` - Get specific task details for student
- `POST /api/tasks/{task_id}/submit` - Submit a task
- `GET /api/student/my-classes` - Get student's enrolled classes
- `GET /api/student/classroom/{class_code}/people` - Get class members 