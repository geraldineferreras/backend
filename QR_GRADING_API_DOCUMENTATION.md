# QR Grading API Documentation

## Overview
The QR Grading API is designed specifically for **face-to-face classroom activities** where teachers want to quickly grade students using QR codes. This is perfect for:

- **In-class quizzes** where students complete work on paper
- **Oral presentations** or **recitations**
- **Lab activities** or **practical demonstrations**
- **Group work assessments**
- **Any activity where you want instant grading and feedback**

## How It Works

### 1. **QR Code Generation**
- Each student gets a unique QR code containing their student ID and class code
- Teachers can generate QR codes for individual students or entire classes
- QR codes are in format: `student_id:class_code` (e.g., `123:S4X86T`)

### 2. **Quick Grading Process**
- Teacher scans student's QR code using the QR scanner
- System automatically identifies the student and class
- Teacher enters the grade and optional feedback
- Grade is immediately recorded and student gets notified

### 3. **Real-time Updates**
- Grades are saved instantly to the database
- Students receive immediate notifications about their grades
- All existing grade management features work seamlessly

## API Endpoints

### 1. Quick Grade Individual Student
**POST** `/api/qr-grading/quick-grade`

**Purpose**: Grade a single student quickly via QR code scan

**Headers**:
```
Content-Type: application/json
Authorization: Bearer <teacher_jwt_token>
```

**Request Body**:
```json
{
    "qr_data": "IDNo: 2021305973\nFull Name: ANJELA SOFIA G. SARMIENTO\nProgram: Bachelor of Science in Information Technology",
    "grade": 85,
    "task_id": 24,
    "feedback": "Excellent work! Great presentation skills."
}
```

**Response**:
```json
{
    "success": true,
    "message": "Student graded successfully via QR code",
    "data": {
        "submission_id": 156,
        "student_name": "John Doe",
        "student_id": "123",
        "grade": 85,
        "feedback": "Excellent work! Great presentation skills.",
        "graded_at": "2024-01-15T10:30:00+00:00"
    }
}
```

### 2. Bulk Quick Grade Multiple Students
**POST** `/api/qr-grading/bulk-quick-grade`

**Purpose**: Grade multiple students quickly in sequence

**Headers**:
```
Content-Type: application/json
Authorization: Bearer <teacher_jwt_token>
```

**Request Body**:
```json
{
    "grades": [
        {
            "qr_data": "IDNo: 2021305973\nFull Name: ANJELA SOFIA G. SARMIENTO\nProgram: Bachelor of Science in Information Technology",
            "grade": 85,
            "task_id": 24,
            "feedback": "Great work!"
        },
        {
            "qr_data": "IDNo: 2022001234\nFull Name: JOHN DOE\nProgram: Bachelor of Science in Computer Science",
            "grade": 92,
            "task_id": 24,
            "feedback": "Excellent presentation!"
        },
        {
            "qr_data": "IDNo: 2022005678\nFull Name: JANE SMITH\nProgram: Bachelor of Science in Information Technology",
            "grade": 78,
            "task_id": 24,
            "feedback": "Good effort, room for improvement"
        }
    ]
}
```

**Response**:
```json
{
    "success": true,
    "message": "Successfully graded 3 students via QR codes",
    "data": {
        "graded_count": 3,
        "results": [
            {
                "submission_id": 156,
                "student_name": "John Doe",
                "student_id": "123",
                "grade": 85,
                "feedback": "Great work!",
                "graded_at": "2024-01-15T10:30:00+00:00"
            },
            {
                "submission_id": 157,
                "student_name": "Jane Smith",
                "student_id": "124",
                "grade": 92,
                "feedback": "Excellent presentation!",
                "graded_at": "2024-01-15T10:31:00+00:00"
            },
            {
                "submission_id": 158,
                "student_name": "Bob Johnson",
                "student_id": "125",
                "grade": 78,
                "feedback": "Good effort, room for improvement",
                "graded_at": "2024-01-15T10:32:00+00:00"
            }
        ],
        "errors": []
    }
}
```

### 3. Generate Student QR Code
**GET** `/api/qr-grading/student-qr/{student_id}?class_code={class_code}`

**Purpose**: Generate QR code data for a specific student

**Headers**:
```
Authorization: Bearer <teacher_jwt_token>
```

**Response**:
```json
{
    "success": true,
    "message": "QR code data generated successfully",
    "data": {
        "student_id": "123",
        "student_name": "ANJELA SOFIA G. SARMIENTO",
        "student_number": "2021305973",
        "program": "Bachelor of Science in Information Technology",
        "class_code": "S4X86T",
        "qr_data": "IDNo: 2021305973\nFull Name: ANJELA SOFIA G. SARMIENTO\nProgram: Bachelor of Science in Information Technology",
        "qr_text": "IDNo: 2021305973\nFull Name: ANJELA SOFIA G. SARMIENTO\nProgram: Bachelor of Science in Information Technology"
    }
}
```

### 4. Generate Class QR Codes
**GET** `/api/qr-grading/class-qr/{class_code}`

**Purpose**: Generate QR codes for all students in a class

**Headers**:
```
Authorization: Bearer <teacher_jwt_token>
```

**Response**:
```json
{
    "success": true,
    "message": "QR codes generated for all students in class",
    "data": {
        "class_code": "S4X86T",
        "class_title": "Advanced Mathematics",
        "student_count": 25,
        "qr_codes": [
            {
                "student_id": "123",
                "student_name": "John Doe",
                "student_num": "2024001",
                "class_code": "S4X86T",
                "qr_data": "IDNo: 2021305973\nFull Name: ANJELA SOFIA G. SARMIENTO\nProgram: Bachelor of Science in Information Technology",
                "qr_text": "IDNo: 2021305973\nFull Name: ANJELA SOFIA G. SARMIENTO\nProgram: Bachelor of Science in Information Technology"
            },
            {
                "student_id": "124",
                "student_name": "JANE SMITH",
                "student_num": "2022001234",
                "program": "Bachelor of Science in Computer Science",
                "class_code": "S4X86T",
                "qr_data": "IDNo: 2022001234\nFull Name: JANE SMITH\nProgram: Bachelor of Science in Computer Science",
                "qr_text": "IDNo: 2022001234\nFull Name: JANE SMITH\nProgram: Bachelor of Science in Computer Science"
            }
        ]
    }
}
```

## QR Code Format

### Standard Format
```
IDNo: 2021305973
Full Name: ANJELA SOFIA G. SARMIENTO
Program: Bachelor of Science in Information Technology
```

**Examples**:
- Student with IDNo 2021305973, Full Name ANJELA SOFIA G. SARMIENTO, Program BSIT
- Student with IDNo 2022001234, Full Name JOHN DOE, Program BSCS

### QR Code Content
The QR code contains human-readable text with student information:
```
IDNo: 2021305973
Full Name: ANJELA SOFIA G. SARMIENTO
Program: Bachelor of Science in Information Technology
```

**Note**: The system automatically finds the student in your class using the IDNo (student number) and validates their enrollment.

## Use Cases & Workflows

### 1. **In-Class Quiz Grading**
1. Create a task in your system (e.g., "Week 5 Quiz")
2. Generate QR codes for all students in the class
3. Print QR codes and distribute to students
4. Students complete the quiz on paper
5. Teacher scans each student's QR code and grades immediately
6. Grades are recorded and students get instant feedback

### 2. **Oral Presentation Assessment**
1. Create a task for "Oral Presentation"
2. Generate individual student QR codes
3. Students present their work
4. Teacher scans QR code and grades presentation skills
5. Add feedback about delivery, content, etc.
6. Grade is recorded instantly

### 3. **Lab Activity Evaluation**
1. Create a task for "Lab Experiment #3"
2. Students complete lab work
3. Teacher observes and grades each student
4. Scan QR code, enter grade, add lab-specific feedback
5. Grades are saved immediately

### 4. **Group Work Assessment**
1. Create a task for "Group Project Presentation"
2. Generate QR codes for each group member
3. After group presentation, grade each member individually
4. Scan each student's QR code and assign individual grades
5. Add feedback about contribution, teamwork, etc.

## Frontend Integration

### QR Scanner Setup
```javascript
// Initialize QR scanner
const qrScanner = new QrScanner(
    document.getElementById('qr-video'),
    result => {
        // Handle QR scan result
        const qrData = result.data; // e.g., "IDNo: 2021305973\nFull Name: ANJELA SOFIA G. SARMIENTO\nProgram: Bachelor of Science in Information Technology"
        handleQRScan(qrData);
    }
);

// Start scanner
qrScanner.start();
```

### Quick Grade Form
```javascript
async function handleQRScan(qrData) {
    // Show grading form
    document.getElementById('qr-data').value = qrData;
    document.getElementById('grading-form').style.display = 'block';
}

async function submitGrade() {
    const gradeData = {
        qr_data: document.getElementById('qr-data').value,
        grade: parseInt(document.getElementById('grade').value),
        task_id: currentTaskId,
        feedback: document.getElementById('feedback').value
    };
    
    try {
        const response = await fetch('/api/qr-grading/quick-grade', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${teacherToken}`
            },
            body: JSON.stringify(gradeData)
        });
        
        const result = await response.json();
        if (result.success) {
            showSuccess(`Graded ${result.data.student_name}: ${result.data.grade}/100`);
            clearForm();
        }
    } catch (error) {
        showError('Failed to submit grade');
    }
}
```

## Benefits

### 1. **Speed & Efficiency**
- Grade students in seconds, not minutes
- No need to manually look up student IDs
- Instant grade recording and feedback

### 2. **Accuracy**
- Eliminates manual data entry errors
- Automatic student identification
- Consistent grading process

### 3. **Real-time Updates**
- Grades appear immediately in the system
- Students get instant notifications
- Teachers can see progress in real-time

### 4. **Flexibility**
- Works with any type of task or activity
- Supports individual and bulk grading
- Easy to integrate with existing workflows

## Security Features

### 1. **Teacher Authentication**
- All endpoints require valid teacher JWT token
- Teachers can only grade students in their own classes

### 2. **Student Validation**
- System verifies student enrollment in teacher's class
- Prevents unauthorized access to student records

### 3. **Data Integrity**
- All grades are properly linked to submissions
- Audit trail maintained for all grading activities

## Error Handling

### Common Error Responses

**400 - Bad Request**
```json
{
    "success": false,
    "message": "Field 'grade' is required",
    "code": 400
}
```

**404 - Not Found**
```json
{
    "success": false,
    "message": "Student not found or not enrolled in your class",
    "code": 404
}
```

**500 - Internal Server Error**
```json
{
    "success": false,
    "message": "QR grading failed: Database connection error",
    "code": 500
}
```

## Testing

### Test with Postman

1. **Generate QR Codes**
   ```
   GET /api/qr-grading/class-qr/S4X86T
   Authorization: Bearer <teacher_token>
   ```

2. **Quick Grade Test**
   ```
   POST /api/qr-grading/quick-grade
   Authorization: Bearer <teacher_token>
   Body: {
       "qr_data": "IDNo: 2021305973\nFull Name: ANJELA SOFIA G. SARMIENTO\nProgram: Bachelor of Science in Information Technology",
       "grade": 85,
       "task_id": 24,
       "feedback": "Test grade via QR"
   }
   ```

3. **Bulk Grade Test**
   ```
   POST /api/qr-grading/bulk-quick-grade
   Authorization: Bearer <teacher_token>
   Body: {
       "grades": [
           {"qr_data": "IDNo: 2021305973\nFull Name: ANJELA SOFIA G. SARMIENTO\nProgram: Bachelor of Science in Information Technology", "grade": 85, "task_id": 24},
           {"qr_data": "IDNo: 2022001234\nFull Name: JOHN DOE\nProgram: Bachelor of Science in Computer Science", "grade": 92, "task_id": 24}
       ]
   }
   ```

## Integration with Existing Systems

The QR Grading API integrates seamlessly with your existing:

- **Task Management System** - All grades are properly linked to tasks
- **Grade Management** - Grades appear in all existing grade reports
- **Notification System** - Students get instant grade notifications
- **Export Features** - QR grades are included in all grade exports
- **Comprehensive Grading** - QR grades contribute to final grade calculations

## Support

For questions or issues with the QR Grading API:

1. Check the error messages for specific issues
2. Verify teacher authentication and permissions
3. Ensure students are properly enrolled in your class
4. Confirm task IDs exist and are accessible

---

**Perfect for**: Face-to-face classrooms, in-person assessments, quick grading workflows, and any situation where you want instant grade recording!
