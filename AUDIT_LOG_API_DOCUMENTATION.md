# Audit Log API Documentation

## Overview
The Audit Log API provides comprehensive logging and retrieval capabilities for tracking user activities across the SCMS system. All endpoints require admin authentication.

## Base URL
```
/api/admin/audit-logs
```

## Authentication
All endpoints require a valid admin JWT token in the Authorization header:
```
Authorization: Bearer <your_jwt_token>
```

## Endpoints

### 1. Get Audit Logs
**GET** `/api/admin/audit-logs`

Retrieves audit logs with filtering and pagination support.

#### Query Parameters
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `module` | string | No | Filter by module (e.g., "USER MANAGEMENT", "SECTION MANAGEMENT") |
| `user_role` | string | No | Filter by user role (e.g., "admin", "teacher", "student") |
| `date_from` | string | No | Filter by start date (YYYY-MM-DD format) |
| `date_to` | string | No | Filter by end date (YYYY-MM-DD format) |
| `search` | string | No | Search in user name, action, module, or details |
| `limit` | integer | No | Number of records per page (default: 50, max: 100) |
| `offset` | integer | No | Offset for pagination (default: 0) |

#### Example Request
```bash
GET /api/admin/audit-logs?module=USER%20MANAGEMENT&date_from=2024-01-01&limit=20
```

#### Example Response
```json
{
  "success": true,
  "message": "Audit logs retrieved successfully",
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
      "total": 150,
      "limit": 20,
      "offset": 0,
      "has_more": true
    },
    "filters": {
      "module": "USER MANAGEMENT",
      "date_from": "2024-01-01"
    }
  }
}
```

### 2. Get Specific Audit Log
**GET** `/api/admin/audit-logs/{log_id}`

Retrieves a specific audit log entry by ID.

#### Path Parameters
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `log_id` | integer | Yes | The ID of the audit log entry |

#### Example Request
```bash
GET /api/admin/audit-logs/123
```

#### Example Response
```json
{
  "success": true,
  "message": "Audit log retrieved successfully",
  "data": {
    "log_id": 123,
    "user_id": "admin123",
    "user_name": "Dr. Sarah Johnson",
    "user_role": "admin",
    "action_type": "UPDATED",
    "module": "SECTION MANAGEMENT",
    "table_name": "sections",
    "record_id": 789,
    "details": "Updated section BSIT-1A details",
    "ip_address": "192.168.1.100",
    "created_at": "2024-01-15 10:15:00"
  }
}
```

### 3. Get Available Modules
**GET** `/api/admin/audit-logs/modules`

Retrieves a list of available modules for filtering.

#### Example Request
```bash
GET /api/admin/audit-logs/modules
```

#### Example Response
```json
{
  "success": true,
  "message": "Modules retrieved successfully",
  "data": [
    {"module": "AUTHENTICATION"},
    {"module": "GRADES MANAGEMENT"},
    {"module": "REPORTS & LOGS"},
    {"module": "SECTION MANAGEMENT"},
    {"module": "USER MANAGEMENT"}
  ]
}
```

### 4. Get Available Roles
**GET** `/api/admin/audit-logs/roles`

Retrieves a list of available user roles for filtering.

#### Example Request
```bash
GET /api/admin/audit-logs/roles
```

#### Example Response
```json
{
  "success": true,
  "message": "Roles retrieved successfully",
  "data": [
    {"user_role": "admin"},
    {"user_role": "student"},
    {"user_role": "teacher"}
  ]
}
```

### 5. Export Audit Logs
**GET** `/api/admin/audit-logs/export`

Exports audit logs to CSV format with the same filtering options as the main endpoint.

#### Query Parameters
Same as the main audit logs endpoint.

#### Example Request
```bash
GET /api/admin/audit-logs/export?module=USER%20MANAGEMENT&date_from=2024-01-01
```

#### Response
Returns a CSV file download with the following columns:
- Log ID
- User ID
- User Name
- User Role
- Action Type
- Module
- Table Name
- Record ID
- Details
- IP Address
- Created At

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
  "message": "Audit log not found",
  "data": null,
  "status_code": 404
}
```

### 500 Internal Server Error
```json
{
  "success": false,
  "message": "Failed to retrieve audit logs: Database error",
  "data": null,
  "status_code": 500
}
```

## Usage Examples

### Filtering by Module and Date Range
```bash
curl -X GET "https://your-domain.com/api/admin/audit-logs?module=USER%20MANAGEMENT&date_from=2024-01-01&date_to=2024-01-31" \
  -H "Authorization: Bearer your_jwt_token"
```

### Searching for Specific Actions
```bash
curl -X GET "https://your-domain.com/api/admin/audit-logs?search=login&user_role=admin" \
  -H "Authorization: Bearer your_jwt_token"
```

### Pagination
```bash
curl -X GET "https://your-domain.com/api/admin/audit-logs?limit=10&offset=20" \
  -H "Authorization: Bearer your_jwt_token"
```

### Export to CSV
```bash
curl -X GET "https://your-domain.com/api/admin/audit-logs/export?module=USER%20MANAGEMENT" \
  -H "Authorization: Bearer your_jwt_token" \
  --output audit_logs.csv
```

## Integration with Frontend

The API is designed to work seamlessly with your existing frontend interface. The response format matches your frontend expectations:

- **Filtering**: Supports all filter options shown in your UI
- **Pagination**: Provides pagination metadata for infinite scrolling or pagination controls
- **Search**: Full-text search across multiple fields
- **Export**: Direct CSV download functionality
- **Real-time Data**: Fresh data retrieval for your audit log table

## Helper Functions

The system includes helper functions for easy audit logging throughout your application:

```php
// Log a general audit event
log_audit_event('CREATED', 'USER MANAGEMENT', 'Created new user account');

// Log user login
log_user_login($user_data, $ip_address);

// Log data operations
log_data_creation('users', $user_id, 'USER MANAGEMENT', 'Created new student account');
log_data_update('sections', $section_id, 'SECTION MANAGEMENT', 'Updated section details');
log_data_deletion('subjects', $subject_id, 'SUBJECT MANAGEMENT', 'Removed outdated subject');

// Log report exports
log_report_export('REPORTS & LOGS', 'attendance report', 'CSV');
```

## Security Considerations

1. **Admin Only Access**: All endpoints require admin authentication
2. **IP Tracking**: All logs include IP addresses for security monitoring
3. **Comprehensive Logging**: Tracks user actions, modules, and specific record operations
4. **Data Integrity**: Logs are immutable and cannot be modified once created
5. **Export Control**: Export functionality is restricted to admin users only 