# Student Classroom People API Documentation

## Overview
This endpoint allows students to view all people (teacher and students) in a specific class they are enrolled in.

## Endpoint
```
GET /api/student/classroom/{class_code}/people
```

## Authentication
- Requires student authentication
- Include JWT token in Authorization header: `Authorization: Bearer <token>`

## Parameters
- `class_code` (path parameter): The class code of the classroom

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
  "message": "Classroom members retrieved successfully",
  "data": {
    "classroom": {
      "id": 1,
      "class_code": "ABC123",
      "title": "Introduction to Programming",
      "semester": "1st",
      "school_year": "2024-2025",
      "section_name": "BSIT-1A"
    },
    "teacher": {
      "user_id": "TCH123456",
      "full_name": "Christian S. Mallari",
      "email": "christian.mallari@school.com",
      "profile_pic": "uploads/profile/teacher.jpg",
      "role": "Primary Instructor",
      "status": "Active"
    },
    "students": [
      {
        "user_id": "STD123456",
        "full_name": "Dabu, Justine Roman T.",
        "email": "justine.dabu@student.com",
        "student_num": "2021305973",
        "contact_num": "09355846456",
        "program": "Bachelor of Science in Information Technology",
        "profile_pic": "uploads/profile/student1.jpg",
        "role": "Class Member",
        "status": "Enrolled",
        "enrolled_at": "2024-01-15 10:30:00",
        "enrollment_status": "active"
      },
      {
        "user_id": "STD123457",
        "full_name": "Dela Rosa, Lorenz Andre G.",
        "email": "lorenz.delarosa@student.com",
        "student_num": "2021305974",
        "contact_num": "09123456789",
        "program": "Bachelor of Science in Information Technology",
        "profile_pic": null,
        "role": "Class Member",
        "status": "Enrolled",
        "enrolled_at": "2024-01-15 11:15:00",
        "enrollment_status": "active"
      }
    ],
    "statistics": {
      "total_members": 3,
      "total_teachers": 1,
      "total_students": 2
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

**403 Forbidden**
```json
{
  "status": false,
  "message": "Access denied. You must be enrolled in this class to view its members.",
  "data": null,
  "status_code": 403
}
```

**404 Not Found**
```json
{
  "status": false,
  "message": "Classroom not found",
  "data": null,
  "status_code": 404
}
```

**500 Internal Server Error**
```json
{
  "status": false,
  "message": "Failed to retrieve classroom members: <error_message>",
  "data": null,
  "status_code": 500
}
```

## Validation Rules

1. **Authentication**: Must be a logged-in student
2. **Enrollment**: Student must be enrolled in the specified class
3. **Classroom Existence**: Classroom must exist and be active
4. **Access Control**: Only enrolled students can view class members

## Usage Examples

### JavaScript/Fetch
```javascript
const token = 'your_jwt_token_here';
const classCode = 'ABC123';

fetch(`/api/student/classroom/${classCode}/people`, {
  method: 'GET',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json'
  }
})
.then(response => response.json())
.then(data => {
  if (data.status) {
    console.log('Classroom members:', data.data);
    console.log('Total members:', data.data.statistics.total_members);
    console.log('Teacher:', data.data.teacher);
    console.log('Students:', data.data.students);
  } else {
    console.error('Error:', data.message);
  }
})
.catch(error => {
  console.error('Network error:', error);
});
```

### Axios
```javascript
const axios = require('axios');

const token = 'your_jwt_token_here';
const classCode = 'ABC123';

axios.get(`/api/student/classroom/${classCode}/people`, {
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json'
  }
})
.then(response => {
  const data = response.data;
  if (data.status) {
    console.log('Classroom members:', data.data);
  } else {
    console.error('Error:', data.message);
  }
})
.catch(error => {
  console.error('Error:', error.response?.data || error.message);
});
```

### cURL
```bash
curl -X GET \
  "http://localhost:3308/scms_new_backup/index.php/api/student/classroom/ABC123/people" \
  -H "Authorization: Bearer your_jwt_token_here" \
  -H "Content-Type: application/json"
```

## Data Structure

### Classroom Object
- `id`: Classroom database ID
- `class_code`: Unique class code
- `title`: Classroom title/name
- `semester`: Academic semester
- `school_year`: Academic year
- `section_name`: Section name

### Teacher Object
- `user_id`: Teacher's user ID
- `full_name`: Teacher's full name
- `email`: Teacher's email address
- `profile_pic`: Profile picture URL (can be null)
- `role`: Always "Primary Instructor"
- `status`: Always "Active"

### Student Object
- `user_id`: Student's user ID
- `full_name`: Student's full name
- `email`: Student's email address
- `student_num`: Student number
- `contact_num`: Contact number
- `program`: Academic program
- `profile_pic`: Profile picture URL (can be null)
- `role`: Always "Class Member"
- `status`: Always "Enrolled"
- `enrolled_at`: Enrollment date and time
- `enrollment_status`: Enrollment status (usually "active")

### Statistics Object
- `total_members`: Total number of people in the class
- `total_teachers`: Number of teachers (usually 1)
- `total_students`: Number of enrolled students

## Testing

Use the provided test file `test_student_classroom_people.html` to test this endpoint:

1. Open the test file in your browser
2. Enter your JWT token
3. Enter a class code you're enrolled in
4. Click "Get Classroom People"
5. View the results

## Security Features

1. **Authentication Required**: Only authenticated students can access
2. **Enrollment Verification**: Students can only view classes they're enrolled in
3. **Data Privacy**: Only shows basic information needed for class collaboration
4. **Error Handling**: Proper error messages without exposing sensitive data

## Related Endpoints

- `GET /api/teacher/classroom/{class_code}/students` - Teacher view of students
- `GET /api/student/my-classes` - Get student's enrolled classes
- `POST /api/student/join-class` - Join a class
- `DELETE /api/student/leave-class` - Leave a class 