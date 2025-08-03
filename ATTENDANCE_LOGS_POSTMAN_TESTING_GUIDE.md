# Attendance Logs API - Postman Testing Guide

This guide provides step-by-step instructions for testing the new Attendance Logs API endpoints using Postman.

## Prerequisites

1. **Postman installed** on your system
2. **XAMPP running** with your SCMS application
3. **Valid JWT token** for authentication
4. **Sample attendance data** in your database

## Base URL Setup

1. Open Postman
2. Create a new collection called "SCMS Attendance Logs API"
3. Set the base URL: `http://localhost/scms_new_backup/index.php/api`

## Authentication Setup

### 1. Get JWT Token

**Request:**
```
POST http://localhost/scms_new_backup/index.php/api/auth/login
```

**Headers:**
```
Content-Type: application/json
```

**Body (raw JSON):**
```json
{
    "username": "admin",
    "password": "your_admin_password"
}
```

**Expected Response:**
```json
{
    "status": "success",
    "message": "Login successful",
    "data": {
        "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
        "user": {
            "user_id": "admin_id",
            "username": "admin",
            "role": "admin"
        }
    }
}
```

### 2. Set Authorization Header

For all subsequent requests, add this header:
```
Authorization: Bearer YOUR_JWT_TOKEN_HERE
```

## Testing Individual Endpoints

### 1. Get Attendance Logs (with filtering)

**Request:**
```
GET http://localhost/scms_new_backup/index.php/api/attendance-logs/logs
```

**Headers:**
```
Authorization: Bearer YOUR_JWT_TOKEN_HERE
Content-Type: application/json
```

**Query Parameters (optional):**
```
?page=1&limit=10&date_from=2024-01-01&date_to=2024-12-31&status=present&student_id=STUDENT_ID&section_id=SECTION_ID&subject_id=SUBJECT_ID&teacher_id=TEACHER_ID&program=BSIT
```

**Example Request:**
```
GET http://localhost/scms_new_backup/index.php/api/attendance-logs/logs?page=1&limit=5&date_from=2024-01-01&status=present&program=BSIT
```

**Expected Response:**
```json
{
    "status": "success",
    "message": "Attendance logs retrieved successfully",
    "data": {
        "logs": [
            {
                "log_id": 1,
                "attendance_id": 123,
                "student_id": "STUDENT001",
                "student_name": "John Doe",
                "student_id_number": "2024-001",
                "section_name": "BSIT-1A",
                "program": "Bachelor of Science in Information Technology",
                "subject_name": "Programming 1",
                "subject_code": "PROG101",
                "teacher_name": "Jane Smith",
                "date": "2024-01-15",
                "time_in": "08:30:00",
                "attendance_status": "present",
                "excuse_status": "N/A",
                "late_minutes": null,
                "notes": "On time",
                "remarks": "Attendance recorded by teacher",
                "recorded_by_name": "Admin User",
                "created_at": "2024-01-15 08:35:00"
            }
        ],
        "pagination": {
            "current_page": 1,
            "total_pages": 5,
            "total_records": 50,
            "per_page": 10
        }
    }
}
```

### 2. Get Specific Attendance Log

**Request:**
```
GET http://localhost/scms_new_backup/index.php/api/attendance-logs/log/1
```

**Headers:**
```
Authorization: Bearer YOUR_JWT_TOKEN_HERE
Content-Type: application/json
```

**Expected Response:**
```json
{
    "status": "success",
    "message": "Attendance log retrieved successfully",
    "data": {
        "log_id": 1,
        "attendance_id": 123,
        "student_id": "STUDENT001",
        "student_name": "John Doe",
        "student_id_number": "2024-001",
        "section_id": 1,
        "section_name": "BSIT-1A",
        "program": "Bachelor of Science in Information Technology",
        "subject_id": 1,
        "subject_name": "Programming 1",
        "subject_code": "PROG101",
        "teacher_id": "TEACHER001",
        "teacher_name": "Jane Smith",
        "date": "2024-01-15",
        "time_in": "08:30:00",
        "time_out": null,
        "attendance_status": "present",
        "excuse_status": "N/A",
        "late_minutes": null,
        "notes": "On time",
        "remarks": "Attendance recorded by teacher",
        "recorded_by": "ADMIN001",
        "recorded_by_name": "Admin User",
        "ip_address": "192.168.1.100",
        "device_info": "Mozilla/5.0...",
        "created_at": "2024-01-15 08:35:00",
        "updated_at": "2024-01-15 08:35:00"
    }
}
```

### 3. Export Attendance Logs to CSV

**Request:**
```
GET http://localhost/scms_new_backup/index.php/api/attendance-logs/export
```

**Headers:**
```
Authorization: Bearer YOUR_JWT_TOKEN_HERE
Content-Type: application/json
```

**Query Parameters (optional):**
```
?date_from=2024-01-01&date_to=2024-12-31&status=present&student_id=STUDENT_ID&section_id=SECTION_ID&subject_id=SUBJECT_ID&teacher_id=TEACHER_ID&program=BSIT
```

**Expected Response:**
- A CSV file download with attendance log data
- Headers will include: `Content-Type: text/csv` and `Content-Disposition: attachment; filename="attendance_logs_YYYY-MM-DD.csv"`

### 4. Get Attendance Statistics

**Request:**
```
GET http://localhost/scms_new_backup/index.php/api/attendance-logs/statistics
```

**Headers:**
```
Authorization: Bearer YOUR_JWT_TOKEN_HERE
Content-Type: application/json
```

**Query Parameters (optional):**
```
?date_from=2024-01-01&date_to=2024-12-31&section_id=SECTION_ID&subject_id=SUBJECT_ID&program=BSIT
```

**Expected Response:**
```json
{
    "status": "success",
    "message": "Statistics retrieved successfully",
    "data": {
        "total_records": 150,
        "attendance_breakdown": {
            "present": 120,
            "absent": 15,
            "late": 10,
            "excused": 5
        },
        "daily_averages": {
            "present_rate": 80.0,
            "absent_rate": 10.0,
            "late_rate": 6.67,
            "excused_rate": 3.33
        },
        "top_late_students": [
            {
                "student_id": "STUDENT002",
                "student_name": "Jane Wilson",
                "late_count": 5,
                "total_records": 20
            }
        ],
        "section_breakdown": [
            {
                "section_name": "BSIT-1A",
                "total_records": 50,
                "present_count": 45,
                "absent_count": 3,
                "late_count": 2
            }
        ],
        "subject_breakdown": [
            {
                "subject_name": "Programming 1",
                "total_records": 75,
                "present_count": 60,
                "absent_count": 10,
                "late_count": 5
            }
        ]
    }
}
```

### 5. Get Available Filter Options

**Request:**
```
GET http://localhost/scms_new_backup/index.php/api/attendance-logs/filters
```

**Headers:**
```
Authorization: Bearer YOUR_JWT_TOKEN_HERE
Content-Type: application/json
```

**Expected Response:**
```json
{
    "status": "success",
    "message": "Filter options retrieved successfully",
    "data": {
        "sections": [
            {
                "section_id": 1,
                "section_name": "BSIT-1A"
            },
            {
                "section_id": 2,
                "section_name": "BSIT-1B"
            }
        ],
        "subjects": [
            {
                "subject_id": 1,
                "subject_name": "Programming 1",
                "subject_code": "PROG101"
            },
            {
                "subject_id": 2,
                "subject_name": "Mathematics",
                "subject_code": "MATH101"
            }
        ],
        "teachers": [
            {
                "teacher_id": "TEACHER001",
                "teacher_name": "Jane Smith"
            },
            {
                "teacher_id": "TEACHER002",
                "teacher_name": "John Doe"
            }
        ],
        "students": [
            {
                "student_id": "STUDENT001",
                "student_name": "Alice Johnson",
                "student_id_number": "2024-001"
            },
            {
                "student_id": "STUDENT002",
                "student_name": "Bob Wilson",
                "student_id_number": "2024-002"
            }
        ],
        "attendance_statuses": [
            "present",
            "absent",
            "late",
            "excused"
        ],
        "excuse_statuses": [
            "N/A",
            "pending",
            "approved",
            "rejected"
        ],
        "programs": [
            {
                "program_id": "all",
                "program_name": "All Programs"
            },
            {
                "program_id": "BSIT",
                "program_name": "Bachelor of Science in Information Technology"
            },
            {
                "program_id": "BSIS",
                "program_name": "Bachelor of Science in Information Systems"
            },
            {
                "program_id": "BSCS",
                "program_name": "Bachelor of Science in Computer Science"
            },
            {
                "program_id": "ACT",
                "program_name": "Associate in Computer Technology"
            }
        ]
    }
}
```

## Testing Scenarios

### Scenario 1: Filter by Date Range

1. Set up the request to get logs for a specific date range
2. Use query parameters: `?date_from=2024-01-01&date_to=2024-01-31`
3. Verify that only logs within the specified date range are returned

### Scenario 2: Filter by Student

1. Get a student ID from the filters endpoint
2. Use query parameter: `?student_id=STUDENT001`
3. Verify that only logs for that specific student are returned

### Scenario 3: Filter by Attendance Status

1. Use query parameter: `?status=late`
2. Verify that only logs with "late" status are returned

### Scenario 4: Combine Multiple Filters

1. Use multiple query parameters: `?status=present&section_id=1&date_from=2024-01-01`
2. Verify that logs match all specified criteria

### Scenario 5: Test Pagination

1. Use query parameters: `?page=1&limit=5`
2. Verify that only 5 records are returned
3. Check the pagination information in the response
4. Test with `?page=2&limit=5` to get the next page

### Scenario 6: Filter by Program

1. Test with `?program=BSIT` to get only BSIT attendance logs
2. Test with `?program=BSIS` to get only BSIS attendance logs
3. Test with `?program=BSCS` to get only BSCS attendance logs
4. Test with `?program=ACT` to get only ACT attendance logs
5. Test without program parameter to get all programs
6. Combine with other filters: `?program=BSIT&status=present&date_from=2024-01-01`

## Error Testing

### 1. Test Invalid Token

1. Use an invalid or expired JWT token
2. Expected response: 401 Unauthorized

### 2. Test Invalid Log ID

1. Request a non-existent log ID: `/api/attendance-logs/log/999999`
2. Expected response: 404 Not Found

### 3. Test Invalid Date Format

1. Use invalid date format: `?date_from=invalid-date`
2. Expected response: 400 Bad Request

### 4. Test Unauthorized Access

1. Remove the Authorization header
2. Expected response: 401 Unauthorized

## Postman Collection Setup

### 1. Create Environment Variables

Create a Postman environment with these variables:
- `base_url`: `http://localhost/scms_new_backup/index.php/api`
- `jwt_token`: (will be set after login)
- `log_id`: (will be set after getting a log)

### 2. Create Request Templates

**Login Request:**
```
POST {{base_url}}/auth/login
```

**Get Logs Request:**
```
GET {{base_url}}/attendance-logs/logs?page=1&limit=10
```

**Get Specific Log Request:**
```
GET {{base_url}}/attendance-logs/log/{{log_id}}
```

**Export Request:**
```
GET {{base_url}}/attendance-logs/export
```

**Statistics Request:**
```
GET {{base_url}}/attendance-logs/statistics
```

**Filters Request:**
```
GET {{base_url}}/attendance-logs/filters
```

### 3. Set Up Authorization

For all requests except login, add this header:
```
Authorization: Bearer {{jwt_token}}
```

## Troubleshooting

### Common Issues:

1. **CORS Errors**: Ensure your XAMPP is running and the application is accessible
2. **404 Errors**: Check that the API routes are properly configured
3. **Authentication Errors**: Verify your JWT token is valid and not expired
4. **Empty Results**: Check that you have attendance data in your database

### Debug Steps:

1. Check XAMPP error logs
2. Verify database connection
3. Test with a simple request first
4. Check that the attendance_logs table exists and has data

## Sample Data for Testing

If you need to create sample data for testing, you can:

1. Use the regular attendance recording API to create some attendance records
2. Check that the attendance_logs table is being populated
3. Use the filters endpoint to get valid IDs for testing

This guide should help you thoroughly test all the new Attendance Logs API endpoints using Postman! 