# Section Update Endpoint Documentation

## Updated Endpoint: PUT /api/admin/sections/{section_id}

### Description
Updates a section's information and optionally manages student assignments. This endpoint now handles both section information updates and student assignment changes in a single request.

### URL
```
PUT http://localhost/scms_new/index.php/api/admin/sections/{section_id}
```

### Headers
```
Authorization: Bearer YOUR_JWT_TOKEN
Content-Type: application/json
```

### Request Body

#### Required Fields (for section information):
```json
{
  "section_name": "BSIT 4Z",
  "program": "Bachelor of Science in Information Technology", 
  "year_level": "1st Year",
  "semester": "1st",
  "academic_year": "2022-2023",
  "adviser_id": "TEACH002"
}
```

#### Optional Fields (for student management):
```json
{
  "section_name": "BSIT 4Z",
  "program": "Bachelor of Science in Information Technology",
  "year_level": "1st Year", 
  "semester": "1st",
  "academic_year": "2022-2023",
  "adviser_id": "TEACH002",
  "student_ids": ["STU685651BF9DDCF988", "2021302596"]
}
```

### How Student Assignment Works

1. **If `student_ids` is provided:**
   - Removes ALL current students from the section
   - Assigns the new students specified in the array
   - If `student_ids` is empty array `[]`, removes all students

2. **If `student_ids` is NOT provided:**
   - Only updates section information
   - Keeps existing student assignments unchanged

### Response Examples

#### Success Response (with student assignments):
```json
{
  "success": true,
  "message": "Section updated successfully",
  "data": {
    "section_updated": true,
    "assigned_students_count": 2,
    "assigned_students": [
      {
        "user_id": "STU685651BF9DDCF988",
        "full_name": "John Doe",
        "email": "john@example.com",
        "student_num": "2024-0001"
      },
      {
        "user_id": "2021302596",
        "full_name": "Jane Smith", 
        "email": "jane@example.com",
        "student_num": "2024-0002"
      }
    ]
  }
}
```

#### Success Response (section info only):
```json
{
  "success": true,
  "message": "Section updated successfully",
  "data": {
    "section_updated": true,
    "assigned_students_count": 0,
    "assigned_students": []
  }
}
```

### Error Responses

#### Section Not Found:
```json
{
  "success": false,
  "message": "Section not found",
  "data": null
}
```

#### Invalid Adviser:
```json
{
  "success": false,
  "message": "Invalid adviser: must be an active teacher",
  "data": null
}
```

#### Missing Required Fields:
```json
{
  "success": false,
  "message": "section_name is required",
  "data": null
}
```

### Usage Examples

#### Example 1: Update section info only
```bash
curl -X PUT http://localhost/scms_new/index.php/api/admin/sections/15 \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "section_name": "BSIT 4Z",
    "program": "Bachelor of Science in Information Technology",
    "year_level": "1st Year",
    "semester": "1st", 
    "academic_year": "2022-2023",
    "adviser_id": "TEACH002"
  }'
```

#### Example 2: Update section info and assign students
```bash
curl -X PUT http://localhost/scms_new/index.php/api/admin/sections/15 \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "section_name": "BSIT 4Z",
    "program": "Bachelor of Science in Information Technology",
    "year_level": "1st Year",
    "semester": "1st",
    "academic_year": "2022-2023", 
    "adviser_id": "TEACH002",
    "student_ids": ["STU685651BF9DDCF988", "2021302596"]
  }'
```

#### Example 3: Update section info and remove all students
```bash
curl -X PUT http://localhost/scms_new/index.php/api/admin/sections/15 \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "section_name": "BSIT 4Z",
    "program": "Bachelor of Science in Information Technology",
    "year_level": "1st Year",
    "semester": "1st",
    "academic_year": "2022-2023",
    "adviser_id": "TEACH002", 
    "student_ids": []
  }'
```

### Important Notes

1. **Student Assignment is Atomic:** When `student_ids` is provided, ALL current students are removed and replaced with the new list.

2. **Validation:** The endpoint validates that:
   - All required fields are present
   - The adviser exists and is a teacher
   - The semester is valid ("1st" or "2nd")

3. **Database Changes:** 
   - Section information is updated in the `sections` table
   - Student assignments are updated in the `users` table (`section_id` field)

4. **Response Includes:** The response includes information about how many students were assigned and their details.

### Testing

You can test this endpoint using the provided test script:
```bash
php test_section_update.php
```

This will test the endpoint with the exact data you provided in your original request. 