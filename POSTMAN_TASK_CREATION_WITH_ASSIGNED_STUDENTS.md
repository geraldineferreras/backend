# Postman Testing Guide for Task Creation with Assigned Students

This guide provides step-by-step instructions for testing the task creation functionality with assigned students using Postman.

## Prerequisites

1. **Authentication**: You need a valid teacher token
2. **Class Codes**: Valid class codes from your system
3. **Student IDs**: Valid student IDs for assignment
4. **Files**: Test files to upload (optional)

## Getting Authentication Token

First, you need to get a teacher authentication token:

### 1. Login Request
```
POST {{base_url}}/api/auth/login
Content-Type: application/json

{
  "email": "teacher@example.com",
  "password": "password123"
}
```

### 2. Extract Token
From the response, copy the `token` value for use in subsequent requests.

## Method 1: JSON Request with Assigned Students

### Setup in Postman

1. **Request Type**: `POST`
2. **URL**: `{{base_url}}/api/tasks/create`
3. **Headers**:
   - `Authorization: Bearer YOUR_TOKEN_HERE`
   - `Content-Type: application/json`

### Body Configuration

1. **Body Type**: Select `raw`
2. **Format**: Select `JSON`
3. **Body Content**:

```json
{
  "title": "Research Paper Assignment",
  "type": "assignment",
  "points": 100,
  "instructions": "Write a comprehensive research paper on Object-Oriented Programming concepts. Include real-world examples and practical applications.",
  "class_codes": ["J56NHD", "ABC123"],
  "assignment_type": "individual",
  "assigned_students": [
    {
      "student_id": "S001",
      "class_code": "J56NHD"
    },
    {
      "student_id": "S002", 
      "class_code": "J56NHD"
    },
    {
      "student_id": "S003",
      "class_code": "ABC123"
    }
  ],
  "allow_comments": true,
  "is_draft": false,
  "is_scheduled": false,
  "scheduled_at": null,
  "attachment_type": "link",
  "attachment_url": "https://drive.google.com/file/d/your-file-id/view",
  "due_date": "2025-01-25 23:59:00"
}
```

### Classroom Assignment Example
```json
{
  "title": "Class Quiz on Programming",
  "type": "quiz",
  "points": 50,
  "instructions": "Complete the programming quiz covering basic concepts.",
  "class_codes": ["J56NHD"],
  "assignment_type": "classroom",
  "assigned_students": null,
  "allow_comments": true,
  "is_draft": false,
  "is_scheduled": false,
  "scheduled_at": null,
  "attachment_type": "link",
  "attachment_url": "https://example.com/quiz.pdf",
  "due_date": "2025-01-20 23:59:00"
}
```

## Method 2: Multipart Form Data with Assigned Students

### Setup in Postman

1. **Request Type**: `POST`
2. **URL**: `{{base_url}}/api/tasks/create`
3. **Headers**:
   - `Authorization: Bearer YOUR_TOKEN_HERE`
   - `Content-Type: multipart/form-data` (Postman will set this automatically)

### Body Configuration

1. **Body Type**: Select `form-data`
2. **Add Fields**:

| Key | Type | Value |
|-----|------|-------|
| `title` | Text | "Individual Research Assignment" |
| `type` | Text | "assignment" |
| `points` | Text | "100" |
| `instructions` | Text | "Write a research paper on your chosen topic." |
| `class_codes` | Text | `["J56NHD", "ABC123"]` |
| `assignment_type` | Text | "individual" |
| `assigned_students` | Text | `[{"student_id":"S001","class_code":"J56NHD"},{"student_id":"S002","class_code":"J56NHD"}]` |
| `allow_comments` | Text | "1" |
| `is_draft` | Text | "0" |
| `is_scheduled` | Text | "0" |
| `scheduled_at` | Text | "" |
| `due_date` | Text | "2025-01-25 23:59:00" |
| `attachment_type` | Text | "link" |
| `attachment_url` | Text | "https://drive.google.com/file/d/your-file-id/view" |
| `attachment` | File | [Select your file] (optional) |

### Example with File Upload
```json
{
  "title": "Project Submission with Files",
  "type": "project",
  "points": 150,
  "instructions": "Submit your project files and documentation.",
  "class_codes": ["J56NHD"],
  "assignment_type": "individual",
  "assigned_students": [
    {
      "student_id": "S001",
      "class_code": "J56NHD"
    }
  ],
  "allow_comments": true,
  "is_draft": false,
  "is_scheduled": false,
  "scheduled_at": null,
  "due_date": "2025-01-30 23:59:00"
}
```

## Field Descriptions

### Required Fields
- **title**: Task title (string)
- **type**: Task type - `assignment`, `quiz`, `activity`, `project`, `exam`, `midterm_exam`, `final_exam` (string)
- **points**: Maximum points for the task (integer)
- **instructions**: Task instructions (text)
- **class_codes**: Array of class codes where task is posted (array)

### Optional Fields
- **assignment_type**: `classroom` (all students) or `individual` (specific students) (string, default: `classroom`)
- **assigned_students**: Array of student assignments for individual tasks (array, required if `assignment_type` is `individual`)
- **allow_comments**: Whether students can comment (boolean, default: `true`)
- **is_draft**: Whether task is a draft (boolean, default: `false`)
- **is_scheduled**: Whether task is scheduled (boolean, default: `false`)
- **scheduled_at**: Scheduled date/time (datetime, required if `is_scheduled` is `true`)
- **due_date**: Task due date (datetime)
- **attachment_type**: Type of attachment - `file`, `link`, `youtube`, `google_drive` (string)
- **attachment_url**: URL or file path for attachment (string)

### Assigned Students Structure
```json
[
  {
    "student_id": "S001",
    "class_code": "J56NHD"
  },
  {
    "student_id": "S002",
    "class_code": "J56NHD"
  }
]
```

## Expected Responses

### Successful Task Creation
```json
{
  "status": "success",
  "message": "Task created successfully",
  "data": {
    "task_id": 123,
    "title": "Research Paper Assignment",
    "type": "assignment",
    "points": 100,
    "instructions": "Write a comprehensive research paper...",
    "class_codes": ["J56NHD", "ABC123"],
    "assignment_type": "individual",
    "assigned_students": [
      {
        "student_id": "S001",
        "class_code": "J56NHD"
      },
      {
        "student_id": "S002",
        "class_code": "J56NHD"
      }
    ],
    "attachment_type": "link",
    "attachment_url": "https://drive.google.com/file/d/your-file-id/view",
    "allow_comments": 1,
    "is_draft": 0,
    "is_scheduled": 0,
    "scheduled_at": null,
    "due_date": "2025-01-25 23:59:00",
    "teacher_id": "T001",
    "status": "active",
    "created_at": "2024-01-15 10:30:00",
    "updated_at": "2024-01-15 10:30:00"
  }
}
```

## Error Responses

### Missing Required Fields
```json
{
  "status": "error",
  "message": "title is required",
  "data": null
}
```

### Invalid Task Type
```json
{
  "status": "error",
  "message": "Invalid task type",
  "data": null
}
```

### Invalid Points
```json
{
  "status": "error",
  "message": "Points must be a positive number",
  "data": null
}
```

### Missing Class Codes
```json
{
  "status": "error",
  "message": "At least one class must be selected",
  "data": null
}
```

### Individual Assignment Without Students
```json
{
  "status": "error",
  "message": "Assigned students are required for individual assignments",
  "data": null
}
```

## Testing Different Scenarios

### 1. Classroom Assignment (All Students)
```json
{
  "title": "Class Quiz",
  "type": "quiz",
  "points": 50,
  "instructions": "Complete the quiz",
  "class_codes": ["J56NHD"],
  "assignment_type": "classroom",
  "assigned_students": null,
  "allow_comments": true,
  "is_draft": false,
  "due_date": "2025-01-20 23:59:00"
}
```

### 2. Individual Assignment (Specific Students)
```json
{
  "title": "Individual Project",
  "type": "project",
  "points": 100,
  "instructions": "Complete your individual project",
  "class_codes": ["J56NHD"],
  "assignment_type": "individual",
  "assigned_students": [
    {
      "student_id": "S001",
      "class_code": "J56NHD"
    },
    {
      "student_id": "S002",
      "class_code": "J56NHD"
    }
  ],
  "allow_comments": true,
  "is_draft": false,
  "due_date": "2025-01-25 23:59:00"
}
```

### 3. Draft Task
```json
{
  "title": "Draft Assignment",
  "type": "assignment",
  "points": 75,
  "instructions": "This is a draft assignment",
  "class_codes": ["J56NHD"],
  "assignment_type": "classroom",
  "allow_comments": true,
  "is_draft": true,
  "due_date": "2025-01-30 23:59:00"
}
```

### 4. Scheduled Task
```json
{
  "title": "Scheduled Quiz",
  "type": "quiz",
  "points": 30,
  "instructions": "This quiz will be published later",
  "class_codes": ["J56NHD"],
  "assignment_type": "classroom",
  "allow_comments": true,
  "is_draft": false,
  "is_scheduled": true,
  "scheduled_at": "2025-01-20 09:00:00",
  "due_date": "2025-01-25 23:59:00"
}
```

### 5. Task with File Attachment
```json
{
  "title": "Assignment with File",
  "type": "assignment",
  "points": 80,
  "instructions": "Submit your work with the provided template",
  "class_codes": ["J56NHD"],
  "assignment_type": "individual",
  "assigned_students": [
    {
      "student_id": "S001",
      "class_code": "J56NHD"
    }
  ],
  "allow_comments": true,
  "is_draft": false,
  "attachment_type": "link",
  "attachment_url": "https://drive.google.com/file/d/template.docx/view",
  "due_date": "2025-01-28 23:59:00"
}
```

## Postman Collection Setup

### 1. Create Environment Variables
- `base_url`: Your API base URL (e.g., `http://localhost/scms_new_backup`)
- `teacher_token`: Your teacher authentication token
- `class_code`: A valid class code for testing
- `student_id`: A valid student ID for testing

### 2. Create Request Templates

#### Template for JSON Request
```
POST {{base_url}}/api/tasks/create
Authorization: Bearer {{teacher_token}}
Content-Type: application/json

Body (raw JSON):
{
  "title": "Test Assignment",
  "type": "assignment",
  "points": 100,
  "instructions": "Test instructions",
  "class_codes": ["{{class_code}}"],
  "assignment_type": "individual",
  "assigned_students": [
    {
      "student_id": "{{student_id}}",
      "class_code": "{{class_code}}"
    }
  ],
  "allow_comments": true,
  "is_draft": false,
  "due_date": "2025-01-25 23:59:00"
}
```

#### Template for Form Data Request
```
POST {{base_url}}/api/tasks/create
Authorization: Bearer {{teacher_token}}
Content-Type: multipart/form-data

Body (form-data):
- title: "Test Assignment"
- type: "assignment"
- points: "100"
- instructions: "Test instructions"
- class_codes: ["{{class_code}}"]
- assignment_type: "individual"
- assigned_students: [{"student_id":"{{student_id}}","class_code":"{{class_code}}"}]
- allow_comments: "1"
- is_draft: "0"
- due_date: "2025-01-25 23:59:00"
```

## Testing Checklist

- [ ] JSON request with classroom assignment
- [ ] JSON request with individual assignment
- [ ] Multipart form data with classroom assignment
- [ ] Multipart form data with individual assignment
- [ ] Draft task creation
- [ ] Scheduled task creation
- [ ] Task with file attachment
- [ ] Task with external link attachment
- [ ] Validation error handling
- [ ] Authentication error handling
- [ ] Missing required fields handling
- [ ] Invalid data type handling

## Tips for Testing

1. **Token Management**: Keep your authentication token updated
2. **Data Validation**: Test with invalid data to ensure proper error handling
3. **File Uploads**: Test with different file types and sizes
4. **Student Assignment**: Verify that assigned students receive notifications
5. **Cross-Platform**: Test both JSON and form-data methods

## Troubleshooting

### Common Issues

1. **"Invalid task type"**: Ensure type is one of: `assignment`, `quiz`, `activity`, `project`, `exam`, `midterm_exam`, `final_exam`
2. **"Points must be a positive number"**: Ensure points is a positive integer
3. **"At least one class must be selected"**: Ensure class_codes is a non-empty array
4. **"Assigned students are required for individual assignments"**: Provide assigned_students when assignment_type is "individual"
5. **Authentication errors**: Verify your token is valid and not expired

### Debug Steps

1. Check the request headers in Postman's console
2. Verify JSON syntax is valid
3. Ensure all required fields are provided
4. Check server logs for detailed error messages
5. Verify database table structure matches the expected schema
