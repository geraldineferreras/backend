# SCMS Notification API Documentation

## Overview

The SCMS Notification API provides endpoints for managing in-app notifications and email notifications. All endpoints require JWT authentication.

**Base URL**: `http://localhost/scms_new_backup/index.php`

## Authentication

All API endpoints require a valid JWT token in the Authorization header:

```
Authorization: Bearer <your-jwt-token>
```

## API Endpoints

### 1. Get User Notifications

Retrieve notifications for the authenticated user.

**Endpoint**: `GET /api/notifications`

**Headers**:
```
Authorization: Bearer <jwt-token>
Content-Type: application/json
```

**Query Parameters**:
- `limit` (optional): Number of notifications to return (default: 50)
- `offset` (optional): Number of notifications to skip (default: 0)
- `unread_only` (optional): Return only unread notifications (default: false)

**Example Request**:
```
GET /api/notifications?limit=20&offset=0&unread_only=true
```

**Response**:
```json
{
  "success": true,
  "data": {
    "notifications": [
      {
        "id": 1,
        "user_id": "STU001",
        "type": "announcement",
        "title": "New Class Announcement",
        "message": "There will be a class meeting tomorrow at 2 PM.",
        "related_id": 123,
        "related_type": "announcement",
        "class_code": "CS101",
        "is_read": false,
        "is_urgent": false,
        "created_at": "2024-01-15 10:30:00",
        "updated_at": "2024-01-15 10:30:00"
      }
    ],
    "unread_count": 5
  }
}
```

### 2. Mark Notification as Read

Mark a specific notification as read.

**Endpoint**: `PUT /api/notifications/{id}/read`

**Headers**:
```
Authorization: Bearer <jwt-token>
Content-Type: application/json
```

**Path Parameters**:
- `id`: Notification ID

**Example Request**:
```
PUT /api/notifications/1/read
```

**Response**:
```json
{
  "success": true,
  "message": "Notification marked as read"
}
```

### 3. Mark All Notifications as Read

Mark all notifications for the user as read.

**Endpoint**: `PUT /api/notifications/mark-all-read`

**Headers**:
```
Authorization: Bearer <jwt-token>
Content-Type: application/json
```

**Example Request**:
```
PUT /api/notifications/mark-all-read
```

**Response**:
```json
{
  "success": true,
  "message": "All notifications marked as read"
}
```

### 4. Delete Notification

Delete a specific notification.

**Endpoint**: `DELETE /api/notifications/{id}`

**Headers**:
```
Authorization: Bearer <jwt-token>
Content-Type: application/json
```

**Path Parameters**:
- `id`: Notification ID

**Example Request**:
```
DELETE /api/notifications/1
```

**Response**:
```json
{
  "success": true,
  "message": "Notification deleted"
}
```

### 5. Get Notification Settings

Retrieve notification settings for the authenticated user.

**Endpoint**: `GET /api/notifications/settings`

**Headers**:
```
Authorization: Bearer <jwt-token>
Content-Type: application/json
```

**Example Request**:
```
GET /api/notifications/settings
```

**Response**:
```json
{
  "success": true,
  "data": {
    "id": 1,
    "user_id": "STU001",
    "email_notifications": true,
    "push_notifications": true,
    "announcement_notifications": true,
    "task_notifications": true,
    "submission_notifications": true,
    "grade_notifications": true,
    "enrollment_notifications": true,
    "created_at": "2024-01-15 10:30:00",
    "updated_at": "2024-01-15 10:30:00"
  }
}
```

### 6. Update Notification Settings

Update notification settings for the authenticated user.

**Endpoint**: `PUT /api/notifications/settings`

**Headers**:
```
Authorization: Bearer <jwt-token>
Content-Type: application/json
```

**Request Body**:
```json
{
  "email_notifications": true,
  "push_notifications": true,
  "announcement_notifications": true,
  "task_notifications": true,
  "submission_notifications": true,
  "grade_notifications": true,
  "enrollment_notifications": true
}
```

**Example Request**:
```
PUT /api/notifications/settings
Content-Type: application/json

{
  "email_notifications": false,
  "task_notifications": false
}
```

**Response**:
```json
{
  "success": true,
  "message": "Settings updated successfully"
}
```

### 7. Get Unread Count

Get the count of unread notifications for the authenticated user.

**Endpoint**: `GET /api/notifications/unread-count`

**Headers**:
```
Authorization: Bearer <jwt-token>
Content-Type: application/json
```

**Example Request**:
```
GET /api/notifications/unread-count
```

**Response**:
```json
{
  "success": true,
  "data": {
    "unread_count": 5
  }
}
```

### 8. Get Recent Notifications

Get recent notifications for dashboard display.

**Endpoint**: `GET /api/notifications/recent`

**Headers**:
```
Authorization: Bearer <jwt-token>
Content-Type: application/json
```

**Query Parameters**:
- `limit` (optional): Number of notifications to return (default: 10)

**Example Request**:
```
GET /api/notifications/recent?limit=5
```

**Response**:
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "user_id": "STU001",
      "type": "announcement",
      "title": "New Class Announcement",
      "message": "There will be a class meeting tomorrow at 2 PM.",
      "related_id": 123,
      "related_type": "announcement",
      "class_code": "CS101",
      "is_read": false,
      "is_urgent": false,
      "created_at": "2024-01-15 10:30:00",
      "updated_at": "2024-01-15 10:30:00"
    }
  ]
}
```

### 9. Get Urgent Notifications

Get urgent (unread) notifications for the authenticated user.

**Endpoint**: `GET /api/notifications/urgent`

**Headers**:
```
Authorization: Bearer <jwt-token>
Content-Type: application/json
```

**Example Request**:
```
GET /api/notifications/urgent
```

**Response**:
```json
{
  "success": true,
  "data": [
    {
      "id": 2,
      "user_id": "STU001",
      "type": "task",
      "title": "Urgent: Assignment Due Tomorrow",
      "message": "Your assignment is due tomorrow. Please submit it on time.",
      "related_id": 456,
      "related_type": "task",
      "class_code": "CS101",
      "is_read": false,
      "is_urgent": true,
      "created_at": "2024-01-15 11:00:00",
      "updated_at": "2024-01-15 11:00:00"
    }
  ]
}
```

## Notification Types

The system supports the following notification types:

| Type | Description | Icon |
|------|-------------|------|
| `announcement` | Class announcements | 📢 |
| `task` | Task assignments and updates | 📝 |
| `submission` | Task submissions | 📤 |
| `excuse_letter` | Excuse letter requests | 📄 |
| `grade` | Grade updates | 📊 |
| `enrollment` | Class enrollment | 👥 |
| `system` | System notifications | ⚙️ |

## Error Responses

### 401 Unauthorized
```json
{
  "error": "Unauthorized"
}
```

### 404 Not Found
```json
{
  "error": "Notification not found"
}
```

### 500 Internal Server Error
```json
{
  "error": "Failed to update settings"
}
```

## Helper Functions

### Creating Notifications

The system provides helper functions for creating notifications:

```php
// Create a simple notification
create_notification($user_id, $type, $title, $message, $related_id, $related_type, $class_code, $is_urgent);

// Create announcement notification
create_announcement_notification($user_id, $announcement_id, $title, $message, $class_code);

// Create task notification
create_task_notification($user_id, $task_id, $title, $message, $class_code, $is_urgent);

// Create notifications for multiple users
create_notifications_for_users($user_ids, $type, $title, $message, $related_id, $related_type, $class_code, $is_urgent);
```

### Email Notifications

Email notifications are automatically sent when enabled:

```php
// Send email notification
send_email_notification($user_id, $type, $title, $message, $related_id, $related_type, $class_code);

// Send announcement email
send_announcement_email($user_id, $announcement_id, $title, $message, $class_code);

// Test email configuration
test_email_configuration($to_email);
```

## Integration Examples

### Teacher Creating Announcement

```php
// In TeacherController
public function create_announcement() {
    // ... existing code ...
    
    // Create announcement in database
    $announcement_id = $this->Announcement_model->create($data);
    
    // Get students in the class
    $students = get_class_students($class_code);
    
    // Create notifications for all students
    foreach ($students as $student) {
        create_announcement_notification(
            $student->user_id,
            $announcement_id,
            "New Announcement: " . $title,
            $message,
            $class_code
        );
    }
    
    // ... rest of code ...
}
```

### Task Assignment

```php
// In TaskController
public function assign_task() {
    // ... existing code ...
    
    // Create task in database
    $task_id = $this->Task_model->create($data);
    
    // Get assigned students
    $assigned_students = $this->Task_model->get_assigned_students($task_id);
    
    // Create notifications for assigned students
    foreach ($assigned_students as $student) {
        create_task_notification(
            $student->user_id,
            $task_id,
            "New Task Assigned: " . $task_title,
            $task_description,
            $class_code,
            $is_urgent
        );
    }
    
    // ... rest of code ...
}
```

## Rate Limiting

The API implements basic rate limiting to prevent abuse. Users are limited to:
- 100 requests per minute for GET endpoints
- 50 requests per minute for POST/PUT/DELETE endpoints

## CORS Support

The API supports CORS for cross-origin requests. Configure your frontend to include the appropriate headers.

## Testing

Use the provided Postman collection to test all notification endpoints. See the `POSTMAN_TESTING_GUIDE.md` for detailed testing instructions.

## Version History

- **v1.0**: Initial release with basic notification functionality
- Support for in-app and email notifications
- User notification settings
- Multiple notification types
- JWT authentication integration 