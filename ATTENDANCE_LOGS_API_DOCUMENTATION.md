# Attendance Logs API Documentation

## Overview
The Attendance Logs API provides access to the dedicated `attendance_logs` table, which contains detailed attendance tracking information separate from the general audit logs. This API is designed for administrators to view, filter, and export attendance logs with comprehensive filtering options.

## Base URL
```
/api/attendance-logs
```

## Authentication
All endpoints require a valid admin JWT token in the Authorization header:
```
Authorization: Bearer <your_jwt_token>
```

## Endpoints

### 1. Get Attendance Logs
**GET** `/api/attendance-logs/logs`

Retrieves attendance logs with comprehensive filtering and pagination support.

#### Query Parameters
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `student_id` | string | No | Filter by student ID |
| `teacher_id` | string | No | Filter by teacher ID |
| `section_id` | integer | No | Filter by section ID |
| `subject_id` | integer | No | Filter by subject ID |
| `attendance_status` | string | No | Filter by attendance status (present, absent, late, excused) |
| `excuse_status` | string | No | Filter by excuse status (pending, approved, rejected, N/A) |
| `date_from` | string | No | Filter by start date (YYYY-MM-DD format) |
| `date_to` | string | No | Filter by end date (YYYY-MM-DD format) |
| `recorded_by` | string | No | Filter by user who recorded the attendance |
| `limit` | integer | No | Number of records per page (default: 50, max: 100) |
| `offset` | integer | No | Offset for pagination (default: 0) |

#### Example Request
```bash
GET /api/attendance-logs/logs?attendance_status=present&date_from=2024-01-01&limit=20
```

#### Example Response
```json
{
  "success": true,
  "message": "Attendance logs retrieved successfully",
  "data": {
    "logs": [
      {
        "log_id": 1,
        "attendance_id": 123,
        "student_id": "student001",
        "student_name": "John Smith",
        "student_id_number": "2021-0001",
        "section_id": 1,
        "section_name": "BSIT-1A",
        "subject_id": 5,
        "subject_name": "Mathematics",
        "subject_code": "MATH101",
        "teacher_id": "teacher001",
        "teacher_name": "Dr. Sarah Johnson",
        "date": "2024-01-15",
        "time_in": "08:30:00",
        "time_out": null,
        "attendance_status": "present",
        "excuse_status": "N/A",
        "late_minutes": null,
        "notes": "Student arrived on time",
        "remarks": "Attendance recorded by teacher",
        "recorded_by": "teacher001",
        "recorded_by_name": "Dr. Sarah Johnson",
        "ip_address": "192.168.1.100",
        "device_info": "Mozilla/5.0...",
        "created_at": "2024-01-15 08:30:00",
        "updated_at": "2024-01-15 08:30:00"
      }
    ],
    "pagination": {
      "total": 150,
      "limit": 20,
      "offset": 0,
      "has_more": true
    },
    "filters": {
      "attendance_status": "present",
      "date_from": "2024-01-01"
    }
  }
}
```

### 2. Get Specific Attendance Log
**GET** `/api/attendance-logs/log/{log_id}`

Retrieves a specific attendance log entry by ID.

#### Path Parameters
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `log_id` | integer | Yes | The ID of the attendance log entry |

#### Example Request
```bash
GET /api/attendance-logs/log/123
```

#### Example Response
```json
{
  "success": true,
  "message": "Attendance log retrieved successfully",
  "data": {
    "log_id": 123,
    "attendance_id": 456,
    "student_id": "student001",
    "student_name": "John Smith",
    "student_id_number": "2021-0001",
    "section_id": 1,
    "section_name": "BSIT-1A",
    "subject_id": 5,
    "subject_name": "Mathematics",
    "subject_code": "MATH101",
    "teacher_id": "teacher001",
    "teacher_name": "Dr. Sarah Johnson",
    "date": "2024-01-15",
    "time_in": "08:30:00",
    "time_out": null,
    "attendance_status": "present",
    "excuse_status": "N/A",
    "late_minutes": null,
    "notes": "Student arrived on time",
    "remarks": "Attendance recorded by teacher",
    "recorded_by": "teacher001",
    "recorded_by_name": "Dr. Sarah Johnson",
    "ip_address": "192.168.1.100",
    "device_info": "Mozilla/5.0...",
    "created_at": "2024-01-15 08:30:00",
    "updated_at": "2024-01-15 08:30:00"
  }
}
```

### 3. Export Attendance Logs
**GET** `/api/attendance-logs/export`

Exports attendance logs to CSV format with the same filtering options as the main endpoint.

#### Query Parameters
Same as the main attendance logs endpoint.

#### Example Request
```bash
GET /api/attendance-logs/export?attendance_status=absent&date_from=2024-01-01
```

#### Response
Returns a CSV file download with the following columns:
- Log ID
- Student Name
- Student ID
- Section
- Subject
- Teacher
- Date
- Time In
- Time Out
- Attendance Status
- Excuse Status
- Late Minutes
- Notes
- Recorded By
- IP Address
- Created At

### 4. Get Attendance Statistics
**GET** `/api/attendance-logs/statistics`

Retrieves comprehensive statistics about attendance logs.

#### Query Parameters
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `date_from` | string | No | Filter by start date (YYYY-MM-DD format) |
| `date_to` | string | No | Filter by end date (YYYY-MM-DD format) |

#### Example Request
```bash
GET /api/attendance-logs/statistics?date_from=2024-01-01&date_to=2024-01-31
```

#### Example Response
```json
{
  "success": true,
  "message": "Attendance statistics retrieved successfully",
  "data": {
    "overall": {
      "total_logs": 1250,
      "unique_students": 45,
      "unique_teachers": 8,
      "unique_sections": 12,
      "unique_subjects": 15
    },
    "status_breakdown": [
      {
        "attendance_status": "present",
        "count": 980
      },
      {
        "attendance_status": "absent",
        "count": 180
      },
      {
        "attendance_status": "late",
        "count": 70
      },
      {
        "attendance_status": "excused",
        "count": 20
      }
    ],
    "excuse_breakdown": [
      {
        "excuse_status": "N/A",
        "count": 1230
      },
      {
        "excuse_status": "approved",
        "count": 15
      },
      {
        "excuse_status": "pending",
        "count": 3
      },
      {
        "excuse_status": "rejected",
        "count": 2
      }
    ],
    "top_sections": [
      {
        "section_name": "BSIT-1A",
        "count": 150
      },
      {
        "section_name": "BSIT-1B",
        "count": 145
      }
    ],
    "top_subjects": [
      {
        "subject_name": "Mathematics",
        "count": 200
      },
      {
        "subject_name": "Programming",
        "count": 180
      }
    ]
  }
}
```

### 5. Get Filter Options
**GET** `/api/attendance-logs/filters`

Retrieves available filter options for the attendance logs.

#### Example Request
```bash
GET /api/attendance-logs/filters
```

#### Example Response
```json
{
  "success": true,
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
        "subject_id": 5,
        "subject_name": "Mathematics",
        "subject_code": "MATH101"
      },
      {
        "subject_id": 6,
        "subject_name": "Programming",
        "subject_code": "PROG101"
      }
    ],
    "teachers": [
      {
        "teacher_id": "teacher001",
        "teacher_name": "Dr. Sarah Johnson"
      },
      {
        "teacher_id": "teacher002",
        "teacher_name": "Mr. John Doe"
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
    ]
  }
}
```

## Usage Examples

### JavaScript/Fetch API

```javascript
// Get attendance logs with filters
async function getAttendanceLogs() {
    const params = new URLSearchParams({
        attendance_status: 'present',
        date_from: '2024-01-01',
        limit: '20'
    });
    
    const response = await fetch(`/api/attendance-logs/logs?${params}`, {
        headers: {
            'Authorization': `Bearer ${token}`,
            'Content-Type': 'application/json'
        }
    });
    const data = await response.json();
    return data;
}

// Export attendance logs
async function exportAttendanceLogs() {
    const params = new URLSearchParams({
        attendance_status: 'absent',
        date_from: '2024-01-01'
    });
    
    const response = await fetch(`/api/attendance-logs/export?${params}`, {
        headers: {
            'Authorization': `Bearer ${token}`
        }
    });
    
    const blob = await response.blob();
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'attendance_logs.csv';
    a.click();
}

// Get statistics
async function getAttendanceStatistics() {
    const response = await fetch('/api/attendance-logs/statistics?date_from=2024-01-01', {
        headers: {
            'Authorization': `Bearer ${token}`,
            'Content-Type': 'application/json'
        }
    });
    const data = await response.json();
    return data;
}
```

### cURL Examples

```bash
# Get attendance logs
curl -X GET "http://localhost/api/attendance-logs/logs?attendance_status=present&limit=20" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"

# Export attendance logs
curl -X GET "http://localhost/api/attendance-logs/export?attendance_status=absent" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  --output attendance_logs.csv

# Get statistics
curl -X GET "http://localhost/api/attendance-logs/statistics?date_from=2024-01-01" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"
```

## Integration with Frontend

The API is designed to work seamlessly with your existing frontend interface. The response format matches your frontend expectations:

- **Filtering**: Supports all filter options shown in your UI
- **Pagination**: Provides pagination metadata for infinite scrolling or pagination controls
- **Export**: Direct CSV download functionality
- **Statistics**: Comprehensive statistics for dashboard displays
- **Real-time Data**: Fresh data retrieval for your attendance log table

## Benefits of Dedicated Attendance Logs Table

1. **Performance**: Faster queries for attendance-specific data
2. **Detailed Information**: More comprehensive attendance tracking
3. **Better Organization**: Separate from general audit logs
4. **Easier Reporting**: Direct access to attendance data
5. **Comprehensive Tracking**: Includes student, teacher, section, and subject information

## Error Responses

### 401 Unauthorized
```json
{
  "success": false,
  "message": "Unauthorized access",
  "data": null,
  "status_code": 401
}
```

### 404 Not Found
```json
{
  "success": false,
  "message": "Attendance log not found",
  "data": null,
  "status_code": 404
}
```

### 500 Internal Server Error
```json
{
  "success": false,
  "message": "Failed to retrieve attendance logs: Database error",
  "data": null,
  "status_code": 500
}
```

## Security Considerations

1. **Admin Only Access**: All endpoints require admin authentication
2. **IP Tracking**: All logs include IP addresses for security monitoring
3. **Comprehensive Logging**: Tracks detailed attendance information
4. **Data Integrity**: Logs are immutable and cannot be modified once created
5. **Export Control**: Export functionality is restricted to admin users only 