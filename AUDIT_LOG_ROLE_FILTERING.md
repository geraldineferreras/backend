# Audit Log Role Filtering Endpoints

## Overview
This document describes the new role-specific audit log filtering endpoints that allow administrators to filter audit logs by user roles (admin, teacher, student).

## Base URL
```
/api/admin/audit-logs
```

## Authentication
All endpoints require a valid admin JWT token in the Authorization header:
```
Authorization: Bearer <your_jwt_token>
```

## Available Endpoints

### 1. Role-Specific Endpoints

#### Get Admin Audit Logs
**GET** `/api/admin/audit-logs/admin`

Retrieves audit logs for admin users only.

**Query Parameters:**
- `page` (optional): Page number (default: 1)
- `limit` (optional): Number of records per page (default: 50, max: 100)
- `module` (optional): Filter by module
- `action` (optional): Filter by action type
- `date_from` (optional): Filter by start date (YYYY-MM-DD format)
- `date_to` (optional): Filter by end date (YYYY-MM-DD format)

**Example Request:**
```bash
GET /api/admin/audit-logs/admin?limit=20&module=USER%20MANAGEMENT
```

**Example Response:**
```json
{
  "success": true,
  "message": "Admin audit logs retrieved successfully",
  "data": {
    "logs": [
      {
        "log_id": 1,
        "user_id": "admin123",
        "user_name": "Dr. Sarah Johnson",
        "user_role": "admin",
        "action_type": "CREATED",
        "module": "USER MANAGEMENT",
        "table_name": "users",
        "record_id": 456,
        "details": "Created new student account for John Smith",
        "ip_address": "192.168.1.100",
        "created_at": "2024-01-15 09:30:00"
      }
    ],
    "pagination": {
      "current_page": 1,
      "per_page": 20,
      "total_records": 45,
      "total_pages": 3
    },
    "filter": "admin"
  }
}
```

#### Get Teacher Audit Logs
**GET** `/api/admin/audit-logs/teacher`

Retrieves audit logs for teacher users only.

**Query Parameters:** Same as admin endpoint

**Example Request:**
```bash
GET /api/admin/audit-logs/teacher?action=UPDATED%20ATTENDANCE
```

#### Get Student Audit Logs
**GET** `/api/admin/audit-logs/student`

Retrieves audit logs for student users only.

**Query Parameters:** Same as admin endpoint

**Example Request:**
```bash
GET /api/admin/audit-logs/student?date_from=2024-01-01&date_to=2024-01-31
```

### 2. General Endpoint with Role Filter

#### Get All Audit Logs (with role filter)
**GET** `/api/admin/audit-logs`

The existing general endpoint that supports role filtering via the `user_role` parameter.

**Query Parameters:**
- `user_role` (optional): Filter by role (admin, teacher, student)
- All other parameters same as above

**Example Request:**
```bash
GET /api/admin/audit-logs?user_role=teacher&module=ATTENDANCE%20MANAGEMENT
```

## Usage Examples

### JavaScript/Fetch API

```javascript
// Get admin logs
async function getAdminLogs() {
    const response = await fetch('/api/admin/audit-logs/admin?limit=20', {
        headers: {
            'Authorization': `Bearer ${token}`,
            'Content-Type': 'application/json'
        }
    });
    const data = await response.json();
    return data;
}

// Get teacher logs with additional filters
async function getTeacherLogs() {
    const params = new URLSearchParams({
        limit: '20',
        module: 'ATTENDANCE MANAGEMENT',
        date_from: '2024-01-01'
    });
    
    const response = await fetch(`/api/admin/audit-logs/teacher?${params}`, {
        headers: {
            'Authorization': `Bearer ${token}`,
            'Content-Type': 'application/json'
        }
    });
    const data = await response.json();
    return data;
}

// Get student logs
async function getStudentLogs() {
    const response = await fetch('/api/admin/audit-logs/student', {
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
# Get admin logs
curl -X GET "http://localhost/api/admin/audit-logs/admin?limit=20" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"

# Get teacher logs with filters
curl -X GET "http://localhost/api/admin/audit-logs/teacher?module=ATTENDANCE%20MANAGEMENT&action=UPDATED%20ATTENDANCE" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"

# Get student logs for specific date range
curl -X GET "http://localhost/api/admin/audit-logs/student?date_from=2024-01-01&date_to=2024-01-31" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"
```

## Response Format

All endpoints return the same response format:

```json
{
  "success": true,
  "message": "Role audit logs retrieved successfully",
  "data": {
    "logs": [
      {
        "log_id": 1,
        "user_id": "user123",
        "user_name": "User Name",
        "user_role": "role",
        "action_type": "ACTION_TYPE",
        "module": "MODULE_NAME",
        "table_name": "table_name",
        "record_id": 123,
        "details": "Simple string description of the action",
        "ip_address": "192.168.1.100",
        "created_at": "2024-01-15 09:30:00"
      }
    ],
    "pagination": {
      "current_page": 1,
      "per_page": 50,
      "total_records": 100,
      "total_pages": 2
    },
    "filter": "role_name"
  }
}
```

## Error Responses

```json
{
  "success": false,
  "message": "Error message",
  "data": null,
  "status_code": 400
}
```

## Common Error Codes

- `401`: Unauthorized (invalid or missing JWT token)
- `403`: Forbidden (user is not an admin)
- `400`: Bad Request (invalid parameters)
- `500`: Internal Server Error

## Testing

Use the provided test file `test_audit_role_filtering.html` to test all the new endpoints:

1. Open the test file in your browser
2. Enter your JWT token
3. Click the role-specific buttons to test each endpoint
4. Use additional filters to test combined filtering

## Benefits

1. **Simplified Filtering**: Direct endpoints for each role make it easier to filter logs
2. **Better Performance**: Role-specific queries are more efficient
3. **Cleaner URLs**: More intuitive endpoint names
4. **Backward Compatibility**: Existing general endpoint still works
5. **Flexible**: Supports additional filters (module, action, date range)

## Notes

- All endpoints require admin authentication
- The `details` field now contains simple strings instead of JSON objects
- Pagination is supported on all endpoints
- Date filters use YYYY-MM-DD format
- Role names are case-sensitive: `admin`, `teacher`, `student` 