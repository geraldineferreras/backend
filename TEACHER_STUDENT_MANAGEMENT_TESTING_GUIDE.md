# Teacher Student Management Testing Guide

## Overview
This guide provides instructions for testing the new teacher endpoints to view enrolled students and enrollment statistics for their classes.

## New Endpoints

### 1. Get Enrolled Students
**GET** `/api/teacher/classroom/{class_code}/students`

**Headers:**
```
Authorization: Bearer <JWT_TOKEN>
```

**Success Response (200):**
```json
{
    "status": true,
    "message": "Enrolled students retrieved successfully",
    "data": {
        "class_code": "ABC123",
        "total_students": 2,
        "students": [
            {
                "user_id": "2021305973",
                "full_name": "ANJELA SOFIA G. SARMIENTO",
                "email": "chriselyn@example.com",
                "student_num": "2021305973",
                "contact_num": "09355846456",
                "program": "Bachelor of Science in Information Technology",
                "section_name": "BSIT-1A",
                "enrolled_at": "2024-01-15 10:30:00",
                "enrollment_status": "active"
            },
            {
                "user_id": "2021305974",
                "full_name": "JOHN DOE",
                "email": "john@example.com",
                "student_num": "2021305974",
                "contact_num": "09123456789",
                "program": "Bachelor of Science in Information Technology",
                "section_name": "BSIT-1A",
                "enrolled_at": "2024-01-15 11:15:00",
                "enrollment_status": "active"
            }
        ]
    }
}
```

**Error Responses:**
- **401**: Authentication required
- **403**: Access denied (not your class)
- **404**: Classroom not found

### 2. Get Enrollment Statistics
**GET** `/api/teacher/classroom/{class_code}/enrollment-stats`

**Headers:**
```
Authorization: Bearer <JWT_TOKEN>
```

**Success Response (200):**
```json
{
    "status": true,
    "message": "Enrollment statistics retrieved successfully",
    "data": {
        "class_code": "ABC123",
        "total_enrolled": 2,
        "total_inactive": 0,
        "total_dropped": 0,
        "recent_enrollments": 2,
        "total_enrollments": 2
    }
}
```

## Postman Testing Steps

### Step 1: Get Teacher JWT Token
1. **Login as Teacher**
   - Method: `POST`
   - URL: `http://localhost/scms_new/api/login`
   - Headers: `Content-Type: application/json`
   - Body:
   ```json
   {
       "email": "teacher@example.com",
       "password": "password123"
   }
   ```
   - Copy the JWT token from the response

### Step 2: Create a Classroom (if needed)
1. **Create Classroom**
   - Method: `POST`
   - URL: `http://localhost/scms_new/api/teacher/classrooms`
   - Headers: 
     ```
     Content-Type: application/json
     Authorization: Bearer <TEACHER_JWT_TOKEN>
     ```
   - Body:
   ```json
   {
       "subject_id": 1,
       "section_id": 1,
       "semester": "First Semester",
       "school_year": "2024-2025"
   }
   ```
   - Copy the `class_code` from the response

### Step 3: Have Students Join the Class
1. **Student Login**
   - Method: `POST`
   - URL: `http://localhost/scms_new/api/login`
   - Headers: `Content-Type: application/json`
   - Body:
   ```json
   {
       "email": "student@example.com",
       "password": "password123"
   }
   ```

2. **Student Join Class**
   - Method: `POST`
   - URL: `http://localhost/scms_new/api/student/join-class`
   - Headers:
     ```
     Content-Type: application/json
     Authorization: Bearer <STUDENT_JWT_TOKEN>
     ```
   - Body:
   ```json
   {
       "class_code": "ABC123"
   }
   ```

### Step 4: Test Teacher Endpoints

#### 4.1 Get Enrolled Students
- Method: `GET`
- URL: `http://localhost/scms_new/api/teacher/classroom/ABC123/students`
- Headers: `Authorization: Bearer <TEACHER_JWT_TOKEN>`

#### 4.2 Get Enrollment Statistics
- Method: `GET`
- URL: `http://localhost/scms_new/api/teacher/classroom/ABC123/enrollment-stats`
- Headers: `Authorization: Bearer <TEACHER_JWT_TOKEN>`

## Test Cases

### Test Case 1: Successful Student List Retrieval
- **Prerequisites**: Teacher owns the class, students have joined
- **Expected**: 200 status with list of enrolled students

### Test Case 2: Empty Class
- **Prerequisites**: Teacher owns the class, no students have joined
- **Expected**: 200 status with empty students array

### Test Case 3: Wrong Teacher
- **Prerequisites**: Teacher doesn't own the class
- **Expected**: 403 status with "Access denied" message

### Test Case 4: Invalid Class Code
- **Request**: Use non-existent class code
- **Expected**: 404 status with "Classroom not found" message

### Test Case 5: Missing Authentication
- **Request**: Get students without JWT token
- **Expected**: 401 status with "Authentication required" message

## Complete Testing Workflow

### 1. Setup Phase
```bash
# 1. Teacher creates class
POST /api/teacher/classrooms
{
    "subject_id": 1,
    "section_id": 1,
    "semester": "First Semester",
    "school_year": "2024-2025"
}

# 2. Multiple students join the class
POST /api/student/join-class
{
    "class_code": "ABC123"
}
```

### 2. Testing Phase
```bash
# 3. Teacher views enrolled students
GET /api/teacher/classroom/ABC123/students

# 4. Teacher views enrollment statistics
GET /api/teacher/classroom/ABC123/enrollment-stats
```

### 3. Verification Phase
- Check that all joined students appear in the list
- Verify enrollment statistics match the actual enrollments
- Confirm only the class owner can view the data

## Features

### Student List Features:
- **Complete Student Information**: Full name, email, student number, contact, program
- **Section Information**: Shows which section the student belongs to
- **Enrollment Details**: When they joined and current status
- **Sorted by Name**: Students listed alphabetically
- **Active Only**: Only shows currently active enrollments

### Statistics Features:
- **Total Enrolled**: Count of active enrollments
- **Total Inactive**: Count of inactive enrollments
- **Total Dropped**: Count of dropped enrollments
- **Recent Enrollments**: Enrollments in the last 7 days
- **Total Enrollments**: Sum of all enrollment statuses

## Security Features:
- **Teacher Ownership**: Only class owner can view students
- **Authentication Required**: Valid JWT token needed
- **Role Verification**: Only teachers can access these endpoints
- **Data Privacy**: Student data only visible to class teacher

## Notes
- Students must be in the correct section to join classes
- Enrollment status can be 'active', 'inactive', or 'dropped'
- Statistics are real-time and reflect current database state
- All timestamps are in UTC format 