# Attendance Module API Documentation

## Overview
This document describes the API endpoints for the Attendance Module in the School Management System (SCMS). The attendance module allows teachers to record, manage, and export student attendance data.

## Authentication
All endpoints require teacher authentication via JWT token in the Authorization header:
```
Authorization: Bearer <jwt_token>
```

## Base URL
```
http://localhost:3308/scms_new_backup/index.php/api/attendance
```

## Endpoints

### 1. Get Teacher's Classes
**Endpoint:** `GET /classes`

**Description:** Retrieve all classes assigned to the authenticated teacher for attendance management.

**Headers:**
```
Authorization: Bearer <jwt_token>
Content-Type: application/json
```

**Response:**
```json
{
  "status": true,
  "message": "Classes retrieved successfully",
  "data": [
    {
      "class_id": "1",
      "subject_id": "1",
      "section_id": "1",
      "teacher_id": "TCH123456",
      "subject_name": "Mathematics",
      "subject_code": "MATH101",
      "section_name": "A",
      "semester": "1st",
      "school_year": "2024-2025",
      "status": "active"
    }
  ]
}
```

### 2. Get Students in Class
**Endpoint:** `GET /students/{class_id}`

**Description:** Retrieve all students enrolled in a specific class.

**Parameters:**
- `class_id` (path): The ID of the class

**Headers:**
```
Authorization: Bearer <jwt_token>
Content-Type: application/json
```

**Response:**
```json
{
  "status": true,
  "message": "Students retrieved successfully",
  "data": {
    "class": {
      "class_id": "1",
      "subject_name": "Mathematics",
      "section_name": "A"
    },
    "students": [
      {
        "user_id": "STD123456",
        "full_name": "John Doe",
        "student_num": "2024-0001",
        "email": "john.doe@school.com",
        "section_id": "1"
      }
    ]
  }
}
```

### 3. Record Single Attendance
**Endpoint:** `POST /record`

**Description:** Record attendance for a single student (QR code or manual entry).

**Headers:**
```
Authorization: Bearer <jwt_token>
Content-Type: application/json
```

**Request Body:**
```json
{
  "student_id": "STD123456",
  "class_id": "1",
  "date": "2024-01-15",
  "status": "present",
  "time_in": "08:30:00",
  "notes": "Student arrived on time"
}
```

**Response:**
```json
{
  "status": true,
  "message": "Attendance recorded successfully",
  "data": {
    "attendance_id": 1,
    "student_id": "STU685651BF9DDCF988",
    "student_name": "John Doe",
    "student_num": "2024-0001",
    "student_email": "john.doe@school.com",
    "class_id": 5,
    "subject_id": 3,
    "subject_name": "Programming",
    "section_name": "BSIT 1Z",
    "date": "2024-01-15",
    "time_in": "08:30:00",
    "status": "present",
    "notes": "Student arrived on time",
    "teacher_id": "TEA6860CA834786E482",
    "created_at": "2024-01-15 08:30:00",
    "updated_at": "2024-01-15 08:30:00"
  }
}
```

### 4. Record Bulk Attendance
**Endpoint:** `POST /bulk-record`

**Description:** Record attendance for multiple students at once (manual entry).

**Headers:**
```
Authorization: Bearer <jwt_token>
Content-Type: application/json
```

**Request Body:**
```json
{
  "class_id": "1",
  "date": "2024-01-15",
  "attendance_records": [
    {
      "student_id": "STD123456",
      "status": "present",
      "time_in": "08:30:00",
      "notes": "On time"
    },
    {
      "student_id": "STD123457",
      "status": "late",
      "time_in": "08:45:00",
      "notes": "Late arrival"
    },
    {
      "student_id": "STD123458",
      "status": "absent",
      "notes": "No excuse provided"
    }
  ]
}
```

**Response:**
```json
{
  "status": true,
  "message": "Bulk attendance recorded: 3 successful, 0 failed",
  "data": {
    "success_count": 3,
    "error_count": 0,
    "errors": []
  }
}
```

### 5. Get Attendance Records
**Endpoint:** `GET /records/{class_id}/{date}`

**Description:** Retrieve attendance records for a specific class and date.

**Parameters:**
- `class_id` (path): The ID of the class
- `date` (path): The date in YYYY-MM-DD format

**Headers:**
```
Authorization: Bearer <jwt_token>
Content-Type: application/json
```

**Response:**
```json
{
  "status": true,
  "message": "Attendance records retrieved successfully",
  "data": {
    "class": {
      "class_id": "1",
      "subject_name": "Mathematics",
      "section_name": "A"
    },
    "date": "2024-01-15",
    "records": [
      {
        "attendance_id": "1",
        "student_id": "STD123456",
        "student_name": "John Doe",
        "student_num": "2024-0001",
        "student_email": "john.doe@school.com",
        "status": "present",
        "time_in": "08:30:00",
        "notes": "On time",
        "created_at": "2024-01-15 08:30:00"
      },
      {
        "student_id": "STD123457",
        "student_name": "Jane Smith",
        "student_num": "2024-0002",
        "student_email": "jane.smith@school.com",
        "status": null,
        "time_in": null,
        "notes": null
      }
    ],
    "summary": {
      "present": 1,
      "late": 0,
      "absent": 0,
      "excused": 0,
      "total": 2
    }
  }
}
```

### 6. Update Attendance
**Endpoint:** `PUT /update/{attendance_id}`

**Description:** Update an existing attendance record.

**Parameters:**
- `attendance_id` (path): The ID of the attendance record

**Headers:**
```
Authorization: Bearer <jwt_token>
Content-Type: application/json
```

**Request Body:**
```json
{
  "status": "late",
  "time_in": "08:45:00",
  "notes": "Updated: Student arrived late"
}
```

**Response:**
```json
{
  "status": true,
  "message": "Attendance updated successfully"
}
```

### 7. Get Attendance Summary
**Endpoint:** `GET /summary/{class_id}`

**Description:** Get attendance summary statistics for a class over a date range.

**Parameters:**
- `class_id` (path): The ID of the class
- `date_from` (query): Start date (YYYY-MM-DD, default: 30 days ago)
- `date_to` (query): End date (YYYY-MM-DD, default: today)

**Headers:**
```
Authorization: Bearer <jwt_token>
Content-Type: application/json
```

**Response:**
```json
{
  "status": true,
  "message": "Attendance summary retrieved successfully",
  "data": {
    "class": {
      "class_id": "1",
      "subject_name": "Mathematics",
      "section_name": "A"
    },
    "date_range": {
      "from": "2023-12-15",
      "to": "2024-01-15"
    },
    "summary": [
      {
        "status": "present",
        "count": "15",
        "date": "2024-01-15"
      },
      {
        "status": "late",
        "count": "3",
        "date": "2024-01-15"
      }
    ],
    "total_students": 20
  }
}
```

### 8. Export Attendance Report
**Endpoint:** `GET /export/{class_id}`

**Description:** Export attendance records as JSON or CSV.

**Parameters:**
- `class_id` (path): The ID of the class
- `date_from` (query): Start date (YYYY-MM-DD, default: 30 days ago)
- `date_to` (query): End date (YYYY-MM-DD, default: today)
- `format` (query): Export format (json/csv, default: json)

**Headers:**
```
Authorization: Bearer <jwt_token>
Content-Type: application/json
```

**JSON Response:**
```json
{
  "status": true,
  "message": "Attendance export data retrieved successfully",
  "data": {
    "class": {
      "class_id": "1",
      "subject_name": "Mathematics",
      "section_name": "A"
    },
    "date_range": {
      "from": "2023-12-15",
      "to": "2024-01-15"
    },
    "records": [
      {
        "attendance_id": "1",
        "student_name": "John Doe",
        "student_num": "2024-0001",
        "status": "present",
        "time_in": "08:30:00",
        "notes": "On time",
        "date": "2024-01-15"
      }
    ],
    "total_records": 1
  }
}
```

**CSV Response:**
- Content-Type: `text/csv`
- Content-Disposition: `attachment; filename="attendance_report_MATH101_A_2023-12-15_to_2024-01-15.csv"`
- CSV data with headers: Date, Student Name, Student Number, Status, Time In, Notes, Subject, Section

### 9. Delete Attendance Record
**Endpoint:** `DELETE /delete/{attendance_id}`

**Description:** Delete an attendance record.

**Parameters:**
- `attendance_id` (path): The ID of the attendance record

**Headers:**
```
Authorization: Bearer <jwt_token>
Content-Type: application/json
```

**Response:**
```json
{
  "status": true,
  "message": "Attendance record deleted successfully"
}
```

## Status Values
The attendance status can be one of the following:
- `present`: Student is present
- `late`: Student arrived late
- `absent`: Student is absent
- `excused`: Student is absent with excuse

## Error Responses

### Authentication Error (401)
```json
{
  "status": false,
  "message": "Authentication required. Please login."
}
```

### Access Denied (403)
```json
{
  "status": false,
  "message": "Access denied. Insufficient permissions."
}
```

### Validation Error (400)
```json
{
  "status": false,
  "message": "Missing required fields: student_id, class_id, date, status"
}
```

### Not Found (404)
```json
{
  "status": false,
  "message": "Class not found or access denied"
}
```

### Server Error (500)
```json
{
  "status": false,
  "message": "Failed to retrieve classes: Database connection error"
}
```

## Usage Examples

### QR Code Attendance Flow
1. Teacher opens attendance page
2. Teacher selects class and date
3. Teacher clicks "Start QR Scan"
4. Student shows QR code to camera
5. Frontend extracts student ID from QR code
6. Frontend calls `POST /record` with attendance data
7. Backend validates and records attendance
8. Frontend shows success message and updates display

### Manual Attendance Flow
1. Teacher opens attendance page
2. Teacher selects class and date
3. Teacher clicks "View Manual Attendance"
4. Frontend calls `GET /records/{class_id}/{date}`
5. Frontend displays attendance table with all students
6. Teacher marks each student's status
7. Teacher clicks save
8. Frontend calls `POST /bulk-record` with all attendance data
9. Backend processes bulk attendance
10. Frontend shows success message and updates display

### Export Flow
1. Teacher clicks "Export Attendance"
2. Teacher selects date range and format
3. Frontend calls `GET /export/{class_id}?date_from=...&date_to=...&format=csv`
4. Backend generates CSV file
5. Frontend triggers file download

## Database Schema

### Attendance Table
```sql
CREATE TABLE attendance (
  attendance_id INT PRIMARY KEY AUTO_INCREMENT,
  student_id VARCHAR(50) NOT NULL,
  class_id INT NOT NULL,
  subject_id INT NOT NULL,
  section_name VARCHAR(50) NOT NULL,
  date DATE NOT NULL,
  time_in TIME,
  status ENUM('present', 'late', 'absent', 'excused') NOT NULL,
  notes TEXT,
  teacher_id VARCHAR(50) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY unique_attendance (student_id, class_id, date)
);
```

## Security Considerations
1. All endpoints require teacher authentication
2. Teachers can only access their own classes
3. Input validation prevents SQL injection
4. CORS headers are properly configured
5. Error messages don't expose sensitive information
6. Database transactions ensure data consistency

## Performance Considerations
1. Database indexes on frequently queried columns
2. Efficient JOIN queries with proper indexing
3. Pagination for large datasets (future enhancement)
4. Caching for frequently accessed data (future enhancement)
5. Bulk operations for better performance 