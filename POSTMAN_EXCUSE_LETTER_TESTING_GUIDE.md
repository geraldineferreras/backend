# Postman Testing Guide for Excuse Letter Fix

This guide will help you test the implemented fix for the excuse letter submission error using Postman.

## Prerequisites

1. **Postman installed** on your system
2. **XAMPP running** with Apache and MySQL services
3. **Database populated** with test data (students, classes, classrooms, enrollments)
4. **JWT token** for authentication

## Step 1: Get Authentication Token

### Request: Login to get JWT token
- **Method**: POST
- **URL**: `http://localhost/scms_new_backup/index.php/api/auth/login`
- **Headers**:
  ```
  Content-Type: application/json
  ```
- **Body** (raw JSON):
  ```json
  {
    "email": "student@example.com",
    "password": "student123"
  }
  ```

### Expected Response:
```json
{
  "status": "success",
  "message": "Login successful",
  "data": {
    "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
    "user": {
      "user_id": 123,
      "email": "student@example.com",
      "role": "student"
    }
  }
}
```

**Save the token** - you'll need it for all subsequent requests.

## Step 2: Test Student Classes Endpoint

### Request: Get student's enrolled classes
- **Method**: GET
- **URL**: `http://localhost/scms_new_backup/index.php/api/student/my-classes`
- **Headers**:
  ```
  Authorization: Bearer YOUR_JWT_TOKEN_HERE
  Content-Type: application/json
  ```

### Expected Response:
```json
{
  "status": "success",
  "message": "Classes retrieved successfully",
  "data": [
    {
      "class_id": 1,
      "subject_id": 1,
      "teacher_id": 5,
      "subject_name": "Mathematics",
      "section_name": "Section A",
      "teacher_name": "John Doe"
    }
  ]
}
```

**Note**: The `class_id` returned should now be from the `classes` table, not the `classrooms` table.

## Step 3: Test Excuse Letter Submission

### Request: Submit excuse letter
- **Method**: POST
- **URL**: `http://localhost/scms_new_backup/index.php/api/excuse-letters/submit`
- **Headers**:
  ```
  Authorization: Bearer YOUR_JWT_TOKEN_HERE
  Content-Type: application/json
  ```
- **Body** (raw JSON):
  ```json
  {
    "class_id": 1,
    "absence_date": "2024-01-15",
    "reason": "Medical appointment",
    "description": "I had a doctor's appointment and couldn't attend class"
  }
  ```

### Expected Response (Success):
```json
{
  "status": "success",
  "message": "Excuse letter submitted successfully",
  "data": {
    "excuse_id": 123,
    "student_id": 123,
    "class_id": 1,
    "absence_date": "2024-01-15",
    "reason": "Medical appointment",
    "description": "I had a doctor's appointment and couldn't attend class",
    "status": "pending",
    "submitted_at": "2024-01-16 10:30:00"
  }
}
```

## Step 4: Test Error Scenarios

### Test 1: Invalid class_id
- **Method**: POST
- **URL**: `http://localhost/scms_new_backup/index.php/api/excuse-letters/submit`
- **Headers**: Same as above
- **Body**:
  ```json
  {
    "class_id": 999,
    "absence_date": "2024-01-15",
    "reason": "Test reason",
    "description": "Test description"
  }
  ```

**Expected Response**:
```json
{
  "status": "error",
  "message": "Class not found",
  "code": 404
}
```

### Test 2: Student not enrolled in class
- **Method**: POST
- **URL**: `http://localhost/scms_new_backup/index.php/api/excuse-letters/submit`
- **Headers**: Same as above
- **Body**:
  ```json
  {
    "class_id": 2,
    "absence_date": "2024-01-15",
    "reason": "Test reason",
    "description": "Test description"
  }
  ```
  *(Use a class_id that the student is not enrolled in)*

**Expected Response**:
```json
{
  "status": "error",
  "message": "You are not enrolled in this class",
  "code": 400
}
```

## Step 5: Test with Classroom ID (Backward Compatibility)

### Test: Submit with classroom_id instead of class_id
- **Method**: POST
- **URL**: `http://localhost/scms_new_backup/index.php/api/excuse-letters/submit`
- **Headers**: Same as above
- **Body**:
  ```json
  {
    "class_id": 4,
    "absence_date": "2024-01-15",
    "reason": "Test with classroom ID",
    "description": "Testing backward compatibility"
  }
  ```
  *(Use a classroom_id that corresponds to a class the student is enrolled in)*

**Expected Response**: Should work successfully if the classroom has a corresponding class.

## Testing Checklist

- [ ] Login and get JWT token
- [ ] Retrieve student's enrolled classes
- [ ] Submit excuse letter with valid class_id
- [ ] Test with invalid class_id (should fail)
- [ ] Test with unenrolled class_id (should fail)
- [ ] Test with classroom_id (should work if corresponding class exists)
- [ ] Verify all responses match expected format

## Troubleshooting

### Common Issues:

1. **401 Unauthorized**: Check if JWT token is valid and properly formatted
2. **404 Not Found**: Verify the API endpoints are correct
3. **500 Internal Server Error**: Check XAMPP logs and database connection
4. **400 Bad Request**: Verify JSON format and required fields

### Database Verification:

Run these SQL queries to verify your test data:

```sql
-- Check student enrollments
SELECT ce.*, c.subject_id, c.section_id, c.teacher_id
FROM classroom_enrollments ce
JOIN classrooms c ON ce.classroom_id = c.id
WHERE ce.student_id = YOUR_STUDENT_ID;

-- Check corresponding classes
SELECT cl.*, s.subject_name, sec.section_name
FROM classes cl
JOIN subjects s ON cl.subject_id = s.id
JOIN sections sec ON cl.section_id = sec.section_id
WHERE cl.subject_id = YOUR_SUBJECT_ID
AND cl.section_id = YOUR_SECTION_ID;
```

## Success Criteria

The fix is working correctly if:
1. ✅ Student can retrieve their enrolled classes
2. ✅ Student can submit excuse letters for enrolled classes
3. ✅ Proper error messages for invalid/unenrolled classes
4. ✅ Backward compatibility with classroom_id works
5. ✅ No more "You are not enrolled in this class" errors for valid submissions
