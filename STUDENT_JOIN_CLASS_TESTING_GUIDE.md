# Student Join Class Endpoint Testing Guide

## Overview
This guide provides instructions for testing the new student join class functionality using Postman.

## Prerequisites
1. **Database Setup**: Run the SQL script `create_classroom_enrollments_table.sql` to create the required table
2. **Student Account**: You need a valid student account with JWT token
3. **Class Code**: You need a valid class code created by a teacher

## Database Setup
First, execute the SQL script to create the `classroom_enrollments` table:

```sql
-- Run this in your MySQL database (scms_db)
CREATE TABLE IF NOT EXISTS `classroom_enrollments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `classroom_id` int(11) NOT NULL,
  `student_id` varchar(50) NOT NULL,
  `enrolled_at` datetime NOT NULL,
  `status` enum('active','inactive','dropped') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_enrollment` (`classroom_id`,`student_id`),
  KEY `fk_classroom_enrollments_classroom` (`classroom_id`),
  KEY `fk_classroom_enrollments_student` (`student_id`),
  CONSTRAINT `fk_classroom_enrollments_classroom` FOREIGN KEY (`classroom_id`) REFERENCES `classrooms` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_classroom_enrollments_student` FOREIGN KEY (`student_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

## API Endpoints

### 1. Join Class
**POST** `/api/student/join-class`

**Headers:**
```
Content-Type: application/json
Authorization: Bearer <JWT_TOKEN>
```

**Request Body:**
```json
{
    "class_code": "ABC123"
}
```

**Success Response (201):**
```json
{
    "status": true,
    "message": "Successfully joined the class!",
    "data": {
        "class_code": "ABC123",
        "subject_name": "Mathematics",
        "section_name": "BSIT-1A",
        "semester": "First Semester",
        "school_year": "2024-2025",
        "teacher_name": "John Doe",
        "enrolled_at": "2024-01-15 10:30:00"
    }
}
```

**Error Responses:**
- **400**: Invalid JSON format or missing class_code
- **401**: Authentication required
- **403**: Student not in correct section for this class
- **404**: Class not found
- **409**: Already enrolled in this class
- **500**: Server error

### 2. Get My Classes
**GET** `/api/student/my-classes`

**Headers:**
```
Authorization: Bearer <JWT_TOKEN>
```

**Success Response (200):**
```json
{
    "status": true,
    "message": "Enrolled classes retrieved successfully",
    "data": [
        {
            "class_code": "ABC123",
            "subject_name": "Mathematics",
            "section_name": "BSIT-1A",
            "semester": "First Semester",
            "school_year": "2024-2025",
            "teacher_name": "John Doe",
            "enrolled_at": "2024-01-15 10:30:00"
        }
    ]
}
```

### 3. Leave Class
**DELETE** `/api/student/leave-class`

**Headers:**
```
Content-Type: application/json
Authorization: Bearer <JWT_TOKEN>
```

**Request Body:**
```json
{
    "class_code": "ABC123"
}
```

**Success Response (200):**
```json
{
    "status": true,
    "message": "Successfully left the class.",
    "data": null
}
```

## Postman Testing Steps

### Step 1: Get Student JWT Token
1. **Login as Student**
   - Method: `POST`
   - URL: `http://localhost:3308/scms_new/api/auth/login`
   - Headers: `Content-Type: application/json`
   - Body:
   ```json
   {
       "email": "student@example.com",
       "password": "password123"
   }
   ```
   - Copy the JWT token from the response

### Step 2: Get a Class Code
1. **Login as Teacher**
   - Method: `POST`
   - URL: `http://localhost:3308/scms_new/api/auth/login`
   - Headers: `Content-Type: application/json`
   - Body:
   ```json
   {
       "email": "teacher@example.com",
       "password": "password123"
   }
   ```

2. **Create a Classroom**
   - Method: `POST`
   - URL: `http://localhost:3308/scms_new/api/teacher/classrooms`
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

### Step 3: Test Join Class
1. **Join Class Request**
   - Method: `POST`
   - URL: `http://localhost:3308/scms_new/api/student/join-class`
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

### Step 4: Test Get My Classes
1. **Get Enrolled Classes**
   - Method: `GET`
   - URL: `http://localhost:3308/scms_new/api/student/my-classes`
   - Headers: `Authorization: Bearer <STUDENT_JWT_TOKEN>`

### Step 5: Test Leave Class
1. **Leave Class Request**
   - Method: `DELETE`
   - URL: `http://localhost:3308/scms_new/api/student/leave-class`
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

## Test Cases

### Test Case 1: Successful Join
- **Prerequisites**: Valid student token, valid class code, student in correct section
- **Expected**: 201 status with class details

### Test Case 2: Invalid Class Code
- **Request**: Use non-existent class code
- **Expected**: 404 status with "Class not found" message

### Test Case 3: Already Enrolled
- **Steps**: Join class twice with same student
- **Expected**: 409 status with "Already enrolled" message

### Test Case 4: Wrong Section
- **Prerequisites**: Student not in the section assigned to the class
- **Expected**: 403 status with "You can only join classes for your assigned section" message

### Test Case 5: Missing Authentication
- **Request**: Join class without JWT token
- **Expected**: 401 status with "Authentication required" message

### Test Case 6: Invalid JSON
- **Request**: Send malformed JSON
- **Expected**: 400 status with "Invalid JSON format" message

## Troubleshooting

### Common Issues:
1. **404 Not Found**: Check if the class code exists and is correct
2. **403 Forbidden**: Ensure student is assigned to the correct section
3. **401 Unauthorized**: Verify JWT token is valid and not expired
4. **500 Server Error**: Check database connection and table existence

### Database Verification:
```sql
-- Check if table exists
SHOW TABLES LIKE 'classroom_enrollments';

-- Check enrollments
SELECT * FROM classroom_enrollments;

-- Check classrooms
SELECT * FROM classrooms;

-- Check users (students)
SELECT user_id, full_name, role, section_id FROM users WHERE role = 'student';
```

## Notes
- Students can only join classes for their assigned section
- Each student can only be enrolled once per class
- Enrollment status can be 'active', 'inactive', or 'dropped'
- The system automatically validates section assignments
- All timestamps are in UTC format 