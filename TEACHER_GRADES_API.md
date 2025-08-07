# Teacher Grades API Documentation

## Overview
This endpoint allows teachers to view all student grades for their classes in a comprehensive table format, perfect for grade management and analysis.

## Endpoint
```
GET /api/teacher/classroom/{class_code}/grades
```

## Authentication
- Requires teacher authentication
- Include JWT token in Authorization header: `Authorization: Bearer <token>`
- Teacher must own the classroom to access grades

## Parameters
- `class_code` (path parameter): The class code of the classroom
- `task_id` (query parameter, optional): Filter by specific task ID
- `student_id` (query parameter, optional): Filter by specific student ID

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
  "message": "Class grades retrieved successfully",
  "data": {
    "classroom": {
      "class_code": "S4X86T",
      "title": "System Analysis and Design (BSIT 1Z)",
      "semester": "1st Semester",
      "school_year": "2024-2025"
    },
    "tasks": [
      {
        "task_id": "24",
        "title": "ASSIGNMENT 1",
        "type": "assignment",
        "points": "50",
        "due_date": "2025-08-06 15:57:00"
      }
    ],
    "students": [
      {
        "student_id": "STU123",
        "student_name": "John Doe",
        "student_num": "2021-0001",
        "email": "john.doe@example.com",
        "profile_pic": "profile/john.jpg",
        "assignments": [
          {
            "task_id": "24",
            "task_title": "ASSIGNMENT 1",
            "task_type": "assignment",
            "points": "50",
            "due_date": "2025-08-06 15:57:00",
            "submission_id": "4",
            "grade": "45.00",
            "grade_percentage": 90.0,
            "feedback": "Excellent work!",
            "status": "graded",
            "submitted_at": "2025-08-05 23:58:26",
            "attachment_url": "uploads/submissions/file.pdf"
          }
        ],
        "total_points": 50,
        "total_earned": 45,
        "average_grade": 90.0
      }
    ],
    "statistics": {
      "total_students": 5,
      "total_assignments": 3,
      "total_submissions": 12,
      "graded_submissions": 8,
      "average_class_grade": 85.5
    },
    "filters": {
      "current_task_filter": "all",
      "current_student_filter": "all"
    }
  }
}
```

### Error Responses

#### 404 - Classroom Not Found
```json
{
  "status": false,
  "message": "Classroom not found or access denied",
  "data": null
}
```

#### 403 - Access Denied
```json
{
  "status": false,
  "message": "Access denied. You can only view grades for your own classes.",
  "data": null
}
```

## Features

### 1. **Complete Grade Overview**
- All students in the class
- All assignments/tasks for the class
- Individual grades and percentages
- Submission status tracking

### 2. **Student Information**
- Student name and ID
- Student number and email
- Profile picture
- Individual assignment grades
- Personal averages and totals

### 3. **Assignment Details**
- Task title and type
- Points possible
- Due dates
- Grade percentages
- Submission timestamps
- Feedback (if provided)

### 4. **Class Statistics**
- Total students enrolled
- Total assignments created
- Total submissions received
- Number of graded submissions
- Class average grade

### 5. **Filtering Options**
- Filter by specific task
- Filter by specific student
- View all or filtered results

## Usage Examples

### Get All Grades for a Class
```
GET /api/teacher/classroom/S4X86T/grades
```

### Get Grades for a Specific Task
```
GET /api/teacher/classroom/S4X86T/grades?task_id=24
```

### Get Grades for a Specific Student
```
GET /api/teacher/classroom/S4X86T/grades?student_id=STU123
```

### Get Grades with Multiple Filters
```
GET /api/teacher/classroom/S4X86T/grades?task_id=24&student_id=STU123
```

## Frontend Integration

This endpoint is perfect for creating grade tables like the one shown in your frontend image. The response structure provides:

1. **Table Headers**: Use `tasks` array for column headers
2. **Student Rows**: Use `students` array for each row
3. **Grade Cells**: Use `assignments` array for individual grades
4. **Averages**: Use `average_grade` for student averages
5. **Class Stats**: Use `statistics` for summary information

## Grade Status Values

- `graded` - Assignment has been graded
- `submitted` - Assignment submitted but not graded
- `not_submitted` - Assignment not submitted

## Related Endpoints

- `POST /api/tasks/submissions/{submission_id}/grade` - Grade individual submission
- `POST /api/tasks/{task_id}/bulk-grade` - Bulk grade submissions
- `GET /api/teacher/classroom/{class_code}/students` - Get class students

## Testing

Use the provided test file `test_teacher_grades.html` to test this endpoint with your teacher JWT token and class codes. 