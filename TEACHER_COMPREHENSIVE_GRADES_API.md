# Teacher Comprehensive Grades API Documentation

## Overview
This API provides comprehensive grade management for teachers, including attendance, activities, performance tasks, and quizzes with customizable percentage weights for export to XLSX format.

## Endpoints

### 1. Get Comprehensive Grades
```
GET /api/teacher/classroom/{class_code}/comprehensive-grades
```

### 2. Export Grades to XLSX
```
GET /api/teacher/classroom/{class_code}/export-grades
```

## Authentication
- Requires teacher authentication
- Include JWT token in Authorization header: `Authorization: Bearer <token>`
- Teacher must own the classroom to access grades

## Query Parameters

### Customizable Percentage Weights
- `attendance_weight` (optional): Weight for attendance (default: 10)
- `activity_weight` (optional): Weight for activities (default: 30)
- `assignment_quiz_weight` (optional): Weight for assignments/quizzes (default: 30)
- `major_exam_weight` (optional): Weight for major exams (default: 30)

**Note**: All weights must total 100%. If not provided, defaults to 10% attendance, 30% activity, 30% assignment/quiz, 30% major exam.

## Request Headers
```
Authorization: Bearer <jwt_token>
Content-Type: application/json
```

## Response Format

### Success Response (200) - Comprehensive Grades
```json
{
  "status": true,
  "message": "Comprehensive grades retrieved successfully",
  "data": {
    "classroom": {
      "class_code": "A4V9TE",
      "title": "Database Management System (BSIT 4C)",
      "semester": "1ST SEMESTER",
      "school_year": "2024-2025"
    },
    "tasks_summary": {
      "activities": [
        {
          "task_id": "1",
          "title": "Activity 1",
          "type": "activity",
          "points": 100
        }
      ],
      "assignments_quizzes": [
        {
          "task_id": "2",
          "title": "Assignment 1",
          "type": "assignment",
          "points": 50
        }
      ],
      "major_exams": [
        {
          "task_id": "3",
          "title": "Midterm Exam",
          "type": "exam",
          "points": 100
        }
      ]
    },
    "students": [
      {
        "student_id": "STU123",
        "student_name": "John Doe",
        "student_num": "2021-0001",
        "email": "john.doe@example.com",
        "profile_pic": "profile/john.jpg",
        "attendance": {
          "total_sessions": 15,
          "present_sessions": 12,
          "late_sessions": 2,
          "absent_sessions": 1,
          "excused_sessions": 0,
          "attendance_percentage": 93.33,
          "weighted_score": 9.33
        },
        "activities": [
          {
            "task_id": "1",
            "title": "Activity 1",
            "type": "activity",
            "points": 100,
            "grade": 85,
            "grade_percentage": 85.00,
            "status": "graded",
            "submitted_at": "2025-08-05 10:30:00",
            "feedback": "Good work!"
          }
        ],
        "assignments_quizzes": [
          {
            "task_id": "2",
            "title": "Assignment 1",
            "type": "assignment",
            "points": 50,
            "grade": 45,
            "grade_percentage": 90.00,
            "status": "graded",
            "submitted_at": "2025-08-06 14:20:00",
            "feedback": "Excellent!"
          }
        ],
        "major_exams": [
          {
            "task_id": "3",
            "title": "Midterm Exam",
            "type": "exam",
            "points": 100,
            "grade": 88,
            "grade_percentage": 88.00,
            "status": "graded",
            "submitted_at": "2025-08-07 09:00:00",
            "feedback": "Well done!"
          }
        ],
        "category_scores": {
          "attendance": 9.33,
          "activity": 25.50,
          "assignment_quiz": 27.00,
          "major_exam": 26.40
        },
        "final_grade": 88.23
      }
    ],
    "statistics": {
      "total_students": 25,
      "total_activities": 3,
      "total_assignments_quizzes": 5,
      "total_major_exams": 2,
      "total_submissions": 150,
      "graded_submissions": 120,
      "average_final_grade": 85.50,
      "weights": {
        "attendance": 10,
        "activity": 30,
        "assignment_quiz": 30,
        "major_exam": 30
      }
    }
  }
}
```

## Usage Examples

### Get Comprehensive Grades with Default Weights
```
GET /api/teacher/classroom/A4V9TE/comprehensive-grades
```

### Get Comprehensive Grades with Custom Weights
```
GET /api/teacher/classroom/A4V9TE/comprehensive-grades?attendance_weight=15&activity_weight=25&assignment_quiz_weight=30&major_exam_weight=30
```

### Export Grades to XLSX with Custom Weights
```
GET /api/teacher/classroom/A4V9TE/export-grades?attendance_weight=10&activity_weight=30&assignment_quiz_weight=30&major_exam_weight=30
```

## Grade Calculation Formula

### Attendance Score
```
Attendance Percentage = ((Present + Late + Excused) / Total Sessions) × 100
Weighted Attendance Score = (Attendance Percentage × Attendance Weight) / 100
```

### Activity Score
```
Activity Average = Average of all activity grades (graded only)
Weighted Activity Score = (Activity Average × Activity Weight) / 100
```

### Assignment/Quiz Score
```
Assignment/Quiz Average = Average of all assignment/quiz grades (graded only)
Weighted Assignment/Quiz Score = (Assignment/Quiz Average × Assignment/Quiz Weight) / 100
```

### Major Exam Score
```
Major Exam Average = Average of all major exam grades (graded only)
Weighted Major Exam Score = (Major Exam Average × Major Exam Weight) / 100
```

### Final Grade
```
Final Grade = Weighted Attendance Score + Weighted Activity Score + Weighted Assignment/Quiz Score + Weighted Major Exam Score
```

## Task Categorization

### Activities
- Tasks with `type = 'activity'`

### Assignments/Quizzes
- Tasks with `type = 'assignment'` or `type = 'quiz'`

### Major Exams
- Tasks with `type = 'exam'` or `type = 'project'`

## XLSX Export Features

The export endpoint generates an XLSX file with:

### 1. **Formatted Headers**
- Bold text with background colors
- Category headers spanning multiple columns
- Maximum score indicators

### 2. **Formula Calculations**
- **RS (Raw Score)**: Sum of individual scores
- **PS (Percentage Score)**: `(Raw Score / Maximum Score) × 100`
- **WS (Weighted Score)**: `(Percentage Score × Category Weight) / 100`
- **Final Grade**: Sum of all weighted scores

### 3. **Visual Formatting**
- Alternating row colors for readability
- Color-coded category sections
- Highlighted final grades
- Proper column widths

### 4. **Customizable Weights**
- Weights displayed in header row
- Easy to modify for different grading schemes
- Validation to ensure weights total 100%

## Error Responses

### 400 - Invalid Weights
```json
{
  "status": false,
  "message": "Weights must total 100%. Current total: 95%",
  "data": null
}
```

### 404 - Classroom Not Found
```json
{
  "status": false,
  "message": "Classroom not found or access denied",
  "data": null
}
```

### 403 - Access Denied
```json
{
  "status": false,
  "message": "Access denied. You can only view grades for your own classes.",
  "data": null
}
```

## Frontend Integration

### Grade Table Structure
```javascript
// Example frontend usage
const response = await fetch('/api/teacher/classroom/A4V9TE/comprehensive-grades');
const data = await response.json();

// Display summary cards
document.getElementById('total-students').textContent = data.statistics.total_students;
document.getElementById('total-assignments').textContent = data.statistics.total_assignments_quizzes;
document.getElementById('submissions').textContent = data.statistics.total_submissions;
document.getElementById('class-average').textContent = data.statistics.average_final_grade + '%';

// Build grade table
data.students.forEach(student => {
    // Create student row
    // Add attendance data
    // Add activity grades
    // Add assignment/quiz grades
    // Add major exam grades
    // Calculate and display final grade
});
```

### Export Button
```javascript
// Export grades to XLSX
const exportGrades = async () => {
    const weights = {
        attendance_weight: 10,
        activity_weight: 30,
        assignment_quiz_weight: 30,
        major_exam_weight: 30
    };
    
    const params = new URLSearchParams(weights);
    window.open(`/api/teacher/classroom/A4V9TE/export-grades?${params}`);
};
```

## Grade Status Values

- `graded` - Assignment has been graded
- `submitted` - Assignment submitted but not graded
- `not_submitted` - Assignment not submitted

## Related Endpoints

- `GET /api/teacher/classroom/{class_code}/grades` - Basic grades view
- `POST /api/tasks/submissions/{submission_id}/grade` - Grade individual submission
- `GET /api/attendance/records/{class_id}/{date}` - View attendance records

## Notes

1. **Weight Validation**: All percentage weights must total exactly 100%
2. **Attendance Calculation**: Present, late, and excused sessions count as attended
3. **Grade Averages**: Only graded assignments are included in averages
4. **XLSX Formulas**: The exported file includes Excel formulas for automatic calculations
5. **Customizable**: Teachers can adjust weights based on their grading policies
