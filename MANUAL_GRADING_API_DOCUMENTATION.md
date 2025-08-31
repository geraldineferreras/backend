# Manual Grading API Documentation

## Overview

The Manual Grading API is designed specifically for **face-to-face classroom activities** where teachers want to grade students without requiring file submissions or attachments. This is perfect for:

- **In-class activities** where students complete work on paper
- **Oral presentations** or **recitations**
- **Lab activities** or **practical demonstrations**
- **Group work assessments**
- **Any activity where you want to grade without file uploads**

## Key Features

### 1. **No Submission Required**
- Students don't need to submit files or content
- Teachers can grade directly from the classroom
- Perfect for face-to-face activities

### 2. **Automatic Record Creation**
- System automatically creates submission records
- No need to manually create submissions
- Maintains data consistency

### 3. **Instant Grading**
- Grades are saved immediately
- Students receive instant notifications
- Real-time grade updates

## API Endpoint

### Manual Grade Student
**POST** `/api/tasks/{task_id}/manual-grade`

**Purpose**: Grade a student manually without requiring file submissions

**Headers**:
```
Content-Type: application/json
Authorization: Bearer <teacher_jwt_token>
```

**Request Body**:
```json
{
    "student_id": "STU123456",
    "class_code": "MATH101",
    "grade": 85,
    "feedback": "Excellent work on the presentation! Great communication skills."
}
```

**Required Fields**:
- `student_id`: The student's unique identifier
- `class_code`: The class code where the task is posted
- `grade`: The numerical grade (must be between 0 and task points)

**Optional Fields**:
- `feedback`: Additional feedback for the student

## Response Format

### Success Response (200)
```json
{
    "success": true,
    "message": "Student graded successfully via manual grading",
    "data": {
        "submission_id": 123,
        "student_name": "John Doe",
        "student_num": "2021305973",
        "grade": 85,
        "max_points": 100,
        "percentage": 85.0,
        "feedback": "Excellent work on the presentation! Great communication skills.",
        "task_title": "Oral Presentation",
        "graded_at": "2024-01-15 14:30:00"
    },
    "status": 200
}
```

### Error Responses

#### 400 - Bad Request
```json
{
    "success": false,
    "message": "Grade must be between 0 and 100",
    "data": null,
    "status": 400
}
```

#### 404 - Task Not Found
```json
{
    "success": false,
    "message": "Task not found or access denied",
    "data": null,
    "status": 404
}
```

#### 400 - Student Not Enrolled
```json
{
    "success": false,
    "message": "Student not enrolled in this class",
    "data": null,
    "status": 400
}
```

## Use Cases & Workflows

### 1. **In-Class Activity Grading**
1. Create a task in your system (e.g., "Week 5 Activity")
2. Students complete the activity in class
3. Teacher grades each student using the manual grading API
4. Grades are recorded instantly
5. Students receive notifications about their grades

### 2. **Oral Presentation Assessment**
1. Create a task for "Oral Presentation"
2. Students present their work in class
3. Teacher grades presentation skills using the API
4. Add feedback about delivery, content, etc.
5. Grade is recorded immediately

### 3. **Lab Activity Evaluation**
1. Create a task for "Lab Experiment #3"
2. Students complete lab work
3. Teacher observes and grades each student
4. Use the API to record grades and feedback
5. Grades are saved instantly

## Frontend Integration

### Manual Grading Form
```javascript
// Example frontend implementation
async function manualGradeStudent(taskId, studentId, classCode, grade, feedback) {
    try {
        const response = await fetch(`/api/tasks/${taskId}/manual-grade`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${teacherToken}`
            },
            body: JSON.stringify({
                student_id: studentId,
                class_code: classCode,
                grade: grade,
                feedback: feedback || null
            })
        });

        const result = await response.json();
        
        if (result.success) {
            showSuccess(`Graded ${result.data.student_name}: ${result.data.grade}/${result.data.max_points}`);
            // Update UI to reflect the new grade
            updateGradeDisplay(result.data);
        } else {
            showError(result.message);
        }
    } catch (error) {
        showError('Failed to submit grade');
        console.error('Manual grading error:', error);
    }
}
```

### Manual Grading Modal
```html
<!-- Example HTML structure for manual grading modal -->
<div class="manual-grading-modal">
    <h3>Manual Grading</h3>
    <p>Grade student: <span id="student-name">Student Name</span></p>
    <p class="description">Perfect for face-to-face activities and manual assessment</p>
    
    <div class="important-note">
        <i class="icon-hand"></i>
        <span>Face-to-Face Activity: Student assigned but no submission yet. Grading will create a manual submission record.</span>
    </div>
    
    <div class="form-group">
        <label for="grade">Score (out of <span id="max-points">10</span>)</label>
        <input type="number" id="grade" min="0" max="10" placeholder="Enter score">
    </div>
    
    <div class="form-group">
        <label for="feedback">Feedback (Optional)</label>
        <textarea id="feedback" rows="3" placeholder="Enter feedback for the student..."></textarea>
    </div>
    
    <div class="modal-actions">
        <button class="btn-secondary" onclick="closeModal()">Cancel</button>
        <button class="btn-primary" onclick="submitManualGrade()">Save Grade</button>
    </div>
</div>
```

### JavaScript Implementation
```javascript
// Handle manual grade submission
function submitManualGrade() {
    const grade = document.getElementById('grade').value;
    const feedback = document.getElementById('feedback').value;
    
    if (!grade || grade < 0 || grade > maxPoints) {
        showError('Please enter a valid grade');
        return;
    }
    
    manualGradeStudent(
        currentTaskId,
        currentStudentId,
        currentClassCode,
        parseInt(grade),
        feedback
    ).then(() => {
        closeModal();
        refreshGradesList();
    });
}

// Update grade display after successful grading
function updateGradeDisplay(gradeData) {
    const gradeElement = document.querySelector(`[data-student-id="${gradeData.student_num}"] .grade`);
    if (gradeElement) {
        gradeElement.textContent = `${gradeData.grade}/${gradeData.max_points}`;
        gradeElement.classList.add('graded');
    }
}
```

## Testing

### Postman Testing
1. Set method to `POST`
2. URL: `{{base_url}}/api/tasks/105/manual-grade`
3. Headers:
   - `Authorization: Bearer {{teacher_token}}`
   - `Content-Type: application/json`
4. Body (raw JSON):
```json
{
    "student_id": "STU123456",
    "class_code": "A4V9TE",
    "grade": 8,
    "feedback": "Great participation in the group activity!"
}
```

### Test Cases
1. **Valid Grade**: Test with grade within range (0 to max points)
2. **Invalid Grade**: Test with grade below 0 or above max points
3. **Missing Fields**: Test with missing required fields
4. **Unauthorized Access**: Test with invalid teacher token
5. **Student Not Enrolled**: Test with student not in the class

## Database Changes

### Automatic Submission Creation
When using manual grading, the system automatically:

1. **Creates submission records** in `task_submissions` table
2. **Sets default values**:
   - `submission_content`: "Manual grading - Face-to-face activity"
   - `attachment_type`: `null`
   - `attachment_url`: `null`
   - `status`: "submitted"
3. **Maintains referential integrity** with existing grade tracking

### Table Structure
```sql
-- The system uses the existing task_submissions table
-- No additional tables or schema changes required
```

## Security Features

### 1. **Teacher Authentication**
- All endpoints require valid teacher JWT token
- Teachers can only grade students in their own classes

### 2. **Student Validation**
- System verifies student enrollment in teacher's class
- Prevents unauthorized access to student records

### 3. **Grade Validation**
- Grades must be within valid range (0 to task points)
- Prevents invalid grade entries

## Benefits

### 1. **Simplicity**
- No need for complex file upload handling
- Streamlined grading process for face-to-face activities

### 2. **Efficiency**
- Grade students quickly without waiting for submissions
- Perfect for in-class assessments

### 3. **Flexibility**
- Works with any type of task or activity
- Supports both individual and bulk grading workflows

### 4. **Data Consistency**
- Maintains consistent grade tracking
- Integrates seamlessly with existing grade management

## Related Endpoints

- `POST /api/tasks/submissions/{submission_id}/grade` - Grade existing submissions
- `POST /api/qr-grading/quick-grade` - QR code-based grading
- `GET /api/tasks/{task_id}/submissions` - View all submissions for a task
- `GET /api/teacher/classroom/{class_code}/grades` - View class grades

## Notes

1. **No File Handling**: Manual grading doesn't process or store files
2. **Automatic Records**: Submission records are created automatically
3. **Notification System**: Students receive immediate grade notifications
4. **Grade Validation**: All grades are validated against task point limits
5. **Audit Trail**: All manual grades are logged with timestamps
