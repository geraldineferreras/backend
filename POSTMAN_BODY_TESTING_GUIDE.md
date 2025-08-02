# Postman Body Testing Guide for Attendance API

This guide shows you how to test attendance API endpoints with different request body types in Postman.

## 1. Teacher Authentication (JSON Body)

### Setup in Postman:
1. **Method**: `POST`
2. **URL**: `{{base_url}}/api/auth/login`
3. **Headers**:
   ```
   Content-Type: application/json
   ```
4. **Body Tab**: Select "raw" and choose "JSON" from dropdown

### JSON Body:
```json
{
    "email": "teacher@example.com",
    "password": "password123"
}
```

### Steps in Postman:
1. Click on the **Body** tab
2. Select **raw** radio button
3. Choose **JSON** from the dropdown
4. Paste the JSON body above
5. Click **Send**

---

## 2. Record Single Attendance (JSON Body)

### Setup in Postman:
1. **Method**: `POST`
2. **URL**: `{{base_url}}/api/attendance/record`
3. **Headers**:
   ```
   Authorization: Bearer {{jwt_token}}
   Content-Type: application/json
   ```
4. **Body Tab**: Select "raw" and choose "JSON"

### JSON Body:
```json
{
    "student_id": 2,
    "class_id": 1,
    "date": "2024-01-15",
    "status": "present",
    "time_in": "08:30:00",
    "notes": "Student arrived on time"
}
```

**Note:** `teacher_id` is automatically set from the JWT token - you don't need to include it in the request body.

### Alternative Status Values:
```json
{
    "student_id": 2,
    "class_id": 1,
    "date": "2024-01-15",
    "status": "late",
    "time_in": "08:45:00",
    "notes": "Student arrived late"
}
```

```json
{
    "student_id": 2,
    "class_id": 1,
    "date": "2024-01-15",
    "status": "absent",
    "time_in": null,
    "notes": "Student absent without excuse"
}
```

```json
{
    "student_id": 2,
    "class_id": 1,
    "date": "2024-01-15",
    "status": "excused",
    "time_in": null,
    "notes": "Student has medical excuse"
}
```

---

## 3. Record Bulk Attendance (JSON Body)

### Setup in Postman:
1. **Method**: `POST`
2. **URL**: `{{base_url}}/api/attendance/bulk-record`
3. **Headers**:
   ```
   Authorization: Bearer {{jwt_token}}
   Content-Type: application/json
   ```
4. **Body Tab**: Select "raw" and choose "JSON"

### JSON Body:
```json
{
    "class_id": 1,
    "date": "2024-01-15",
    "attendance_records": [
        {
            "student_id": 2,
            "status": "present",
            "time_in": "08:30:00",
            "notes": "On time"
        },
        {
            "student_id": 3,
            "status": "late",
            "time_in": "08:45:00",
            "notes": "Late arrival"
        },
        {
            "student_id": 4,
            "status": "absent",
            "time_in": null,
            "notes": "No excuse provided"
        },
        {
            "student_id": 5,
            "status": "excused",
            "time_in": null,
            "notes": "Medical appointment"
        }
    ]
}
```

**Note:** `teacher_id`, `subject_id`, and `section_name` are automatically set from the class data - you don't need to include them in the request body.

### Multiple Students Example:
```json
{
    "class_id": 1,
    "date": "2024-01-15",
    "attendance_records": [
        {
            "student_id": 2,
            "status": "present",
            "time_in": "08:30:00",
            "notes": "On time"
        },
        {
            "student_id": 3,
            "status": "present",
            "time_in": "08:32:00",
            "notes": "On time"
        },
        {
            "student_id": 4,
            "status": "present",
            "time_in": "08:35:00",
            "notes": "On time"
        },
        {
            "student_id": 5,
            "status": "late",
            "time_in": "08:45:00",
            "notes": "Late arrival"
        },
        {
            "student_id": 6,
            "status": "late",
            "time_in": "08:50:00",
            "notes": "Traffic delay"
        },
        {
            "student_id": 7,
            "status": "absent",
            "time_in": null,
            "notes": "No excuse provided"
        },
        {
            "student_id": 8,
            "status": "excused",
            "time_in": null,
            "notes": "Family emergency"
        }
    ]
}
```

---

## 4. Update Attendance Record (JSON Body)

### Setup in Postman:
1. **Method**: `PUT`
2. **URL**: `{{base_url}}/api/attendance/update/1`
3. **Headers**:
   ```
   Authorization: Bearer {{jwt_token}}
   Content-Type: application/json
   ```
4. **Body Tab**: Select "raw" and choose "JSON"

### JSON Body Examples:

#### Update Status to Late:
```json
{
    "status": "late",
    "time_in": "08:45:00",
    "notes": "Updated: Student arrived late"
}
```

#### Update Status to Present:
```json
{
    "status": "present",
    "time_in": "08:30:00",
    "notes": "Updated: Student was actually present"
}
```

#### Update Status to Absent:
```json
{
    "status": "absent",
    "time_in": null,
    "notes": "Updated: Student confirmed absent"
}
```

#### Update Status to Excused:
```json
{
    "status": "excused",
    "time_in": null,
    "notes": "Updated: Student provided valid excuse"
}
```

#### Update Only Notes:
```json
{
    "notes": "Updated notes: Student had permission to leave early"
}
```

---

## 5. Testing with Form-Data (Alternative Method)

### Setup in Postman:
1. **Method**: `POST`
2. **URL**: `{{base_url}}/api/attendance/record`
3. **Headers**:
   ```
   Authorization: Bearer {{jwt_token}}
   ```
4. **Body Tab**: Select "form-data"

### Form-Data Body:
| Key | Value | Description |
|-----|-------|-------------|
| student_id | 2 | Student ID |
| class_id | 1 | Class ID |
| date | 2024-01-15 | Date (YYYY-MM-DD) |
| status | present | Status (present/late/absent/excused) |
| time_in | 08:30:00 | Time (HH:MM:SS) |
| subject_id | 1 | Subject ID |
| section_name | A | Section name |
| notes | Student arrived on time | Optional notes |

### Steps in Postman:
1. Click on the **Body** tab
2. Select **form-data** radio button
3. Add each key-value pair in the table above
4. Click **Send**

---

## 6. Testing with x-www-form-urlencoded

### Setup in Postman:
1. **Method**: `POST`
2. **URL**: `{{base_url}}/api/attendance/record`
3. **Headers**:
   ```
   Authorization: Bearer {{jwt_token}}
   ```
4. **Body Tab**: Select "x-www-form-urlencoded"

### URL-Encoded Body:
```
student_id=2&class_id=1&date=2024-01-15&status=present&time_in=08:30:00&subject_id=1&section_name=A&notes=Student arrived on time
```

### Steps in Postman:
1. Click on the **Body** tab
2. Select **x-www-form-urlencoded** radio button
3. Add key-value pairs or paste the encoded string
4. Click **Send**

---

## 7. Testing Error Cases with Invalid Bodies

### Missing Required Fields:
```json
{
    "student_id": 2,
    "class_id": 1
    // Missing date and status
}
```

### Invalid Status:
```json
{
    "student_id": 2,
    "class_id": 1,
    "date": "2024-01-15",
    "status": "invalid_status",
    "time_in": "08:30:00",
    "subject_id": 1,
    "section_name": "A"
}
```

### Invalid Date Format:
```json
{
    "student_id": 2,
    "class_id": 1,
    "date": "15-01-2024",
    "status": "present",
    "time_in": "08:30:00",
    "subject_id": 1,
    "section_name": "A"
}
```

### Invalid Time Format:
```json
{
    "student_id": 2,
    "class_id": 1,
    "date": "2024-01-15",
    "status": "present",
    "time_in": "8:30 AM",
    "subject_id": 1,
    "section_name": "A"
}
```

### Non-existent Student:
```json
{
    "student_id": 999,
    "class_id": 1,
    "date": "2024-01-15",
    "status": "present",
    "time_in": "08:30:00",
    "subject_id": 1,
    "section_name": "A"
}
```

---

## 8. Testing Bulk Attendance with Errors

### Mixed Valid and Invalid Records:
```json
{
    "class_id": 1,
    "date": "2024-01-15",
    "subject_id": 1,
    "section_name": "A",
    "attendance_records": [
        {
            "student_id": 2,
            "status": "present",
            "time_in": "08:30:00",
            "notes": "Valid record"
        },
        {
            "student_id": 999,
            "status": "present",
            "time_in": "08:30:00",
            "notes": "Invalid student ID"
        },
        {
            "student_id": 3,
            "status": "invalid_status",
            "time_in": "08:30:00",
            "notes": "Invalid status"
        }
    ]
}
```

---

## 9. Environment Variables in Body

### Using Variables in JSON Body:
```json
{
    "student_id": "{{student_id}}",
    "class_id": "{{class_id}}",
    "date": "{{$timestamp}}",
    "status": "present",
    "time_in": "08:30:00",
    "subject_id": 1,
    "section_name": "A",
    "notes": "Test attendance record"
}
```

### Dynamic Date Example:
```json
{
    "student_id": 2,
    "class_id": 1,
    "date": "{{$isoTimestamp}}",
    "status": "present",
    "time_in": "{{$timestamp}}",
    "subject_id": 1,
    "section_name": "A",
    "notes": "Dynamic timestamp test"
}
```

---

## 10. Pre-request Scripts for Dynamic Bodies

### Generate Random Student ID:
```javascript
// Pre-request Script
const studentIds = [2, 3, 4, 5, 6, 7, 8];
const randomStudentId = studentIds[Math.floor(Math.random() * studentIds.length)];
pm.environment.set("random_student_id", randomStudentId);
```

### Generate Current Date:
```javascript
// Pre-request Script
const today = new Date().toISOString().split('T')[0];
pm.environment.set("today_date", today);
```

### Use in Body:
```json
{
    "student_id": "{{random_student_id}}",
    "class_id": "{{class_id}}",
    "date": "{{today_date}}",
    "status": "present",
    "time_in": "08:30:00",
    "subject_id": 1,
    "section_name": "A",
    "notes": "Random student test"
}
```

---

## 11. Testing with Different Content Types

### JSON (Recommended):
```
Content-Type: application/json
```

### Form Data:
```
Content-Type: application/x-www-form-urlencoded
```

### Multipart Form Data:
```
Content-Type: multipart/form-data
```

---

## 12. Response Validation

### Expected Success Response:
```json
{
    "status": "success",
    "message": "Attendance recorded successfully",
    "data": {
        "attendance_id": 1,
        "student_id": 2,
        "class_id": 1,
        "date": "2024-01-15",
        "status": "present",
        "time_in": "08:30:00",
        "subject_id": 1,
        "section_name": "A",
        "notes": "Student arrived on time"
    }
}
```

### Expected Error Response:
```json
{
    "status": "error",
    "message": "Missing required fields: student_id, class_id, date"
}
```

---

## 10. Delete Attendance Record

### Request
```
DELETE {{base_url}}/api/attendance/delete/{{attendance_id}}
Authorization: Bearer {{jwt_token}}
```

**Explanation:**
- **Method**: `DELETE`
- **URL**: `{{base_url}}/api/attendance/delete/{{attendance_id}}`
    - Replace `{{attendance_id}}` with the actual `attendance_id` of a record you want to delete. You can get this ID from the response of a `POST /record` or `GET /records/{class_id}/{date}` request.
- **Headers**:
    - `Authorization: Bearer {{jwt_token}}` (Your JWT token obtained from login)

### Expected Response:
```json
{
    "status": "success",
    "message": "Attendance record deleted successfully"
}
```

### Steps in Postman:
1. Create a new request in your "Attendance API" collection.
2. Set the **Method** to `DELETE`.
3. Enter the **URL**: `{{base_url}}/api/attendance/delete/1` (replace `1` with an actual `attendance_id` from your database).
4. Go to the **Headers** tab and add:
   - Key: `Authorization`
   - Value: `Bearer {{jwt_token}}`
5. Click **Send**.

You should receive a success message if the record was deleted. If the record doesn't exist or you don't have permission, you'll receive an error.

---

## Tips for Body Testing:

1. **Always use JSON for complex data structures**
2. **Use form-data for simple key-value pairs**
3. **Test with both valid and invalid data**
4. **Use environment variables for dynamic values**
5. **Validate responses match expected format**
6. **Test all status values: present, late, absent, excused**
7. **Test with null values for optional fields**
8. **Use pre-request scripts for dynamic data generation**

This guide covers all the different ways to test your attendance API endpoints with request bodies in Postman! 