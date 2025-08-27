# 🎓 Student Profile Options API Documentation

## 📋 Overview

This API provides students with access to view available courses, year levels, and sections so they can update their profiles with the correct academic information.

## 🔐 Authentication

All endpoints require a valid JWT token in the Authorization header:
```
Authorization: Bearer YOUR_JWT_TOKEN_HERE
```

**Note**: Only students can access these endpoints. Other roles will receive a 403 Forbidden response.

## 📚 Available Endpoints

### 1. Get All Available Programs
**GET** `/api/student/programs`

Returns a list of all available academic programs/courses.

**Response:**
```json
{
  "status": true,
  "message": "Available programs retrieved successfully",
  "data": [
    {
      "id": "Bachelor of Science in Information Technology",
      "name": "Bachelor of Science in Information Technology",
      "short_name": "BSIT",
      "full_name": "Bachelor of Science in Information Technology"
    },
    {
      "id": "Bachelor of Science in Information Systems",
      "name": "Bachelor of Science in Information Systems",
      "short_name": "BSIS",
      "full_name": "Bachelor of Science in Information Systems"
    }
  ]
}
```

### 2. Get Year Levels for a Program
**GET** `/api/student/programs/{program}/years`

Returns available year levels for a specific program.

**Parameters:**
- `{program}` - URL-encoded program name (e.g., "Bachelor%20of%20Science%20in%20Information%20Technology")

**Response:**
```json
{
  "status": true,
  "message": "Year levels for Bachelor of Science in Information Technology retrieved successfully",
  "data": {
    "program": "Bachelor of Science in Information Technology",
    "program_short": "BSIT",
    "year_levels": [
      {
        "id": "1st year",
        "name": "1st year",
        "display_name": "1st year",
        "numeric_value": 1
      },
      {
        "id": "2nd year",
        "name": "2nd year",
        "display_name": "2nd year",
        "numeric_value": 2
      }
    ],
    "total_years": 2
  }
}
```

### 3. Get Sections for Program and Year
**GET** `/api/student/programs/{program}/years/{year}/sections`

Returns available sections for a specific program and year level.

**Parameters:**
- `{program}` - URL-encoded program name
- `{year}` - URL-encoded year level (e.g., "1st%20year")

**Response:**
```json
{
  "status": true,
  "message": "Sections for Bachelor of Science in Information Technology 1st year retrieved successfully",
  "data": {
    "program": "Bachelor of Science in Information Technology",
    "program_short": "BSIT",
    "year_level": "1st year",
    "year_display": "1st year",
    "sections": [
      {
        "id": 1,
        "name": "A",
        "section_name": "A",
        "program": "Bachelor of Science in Information Technology",
        "year_level": "1st year",
        "semester": "1st",
        "academic_year": "2024-2025",
        "enrolled_count": 25,
        "adviser": {
          "id": "TCH123456789",
          "name": "John Doe",
          "email": "john.doe@school.edu",
          "profile_pic": null
        }
      }
    ],
    "total_sections": 1
  }
}
```

### 4. Get All Profile Options
**GET** `/api/student/profile-options`

Returns all available options for profile updates in a single request.

**Response:**
```json
{
  "status": true,
  "message": "Profile options retrieved successfully",
  "data": {
    "programs": [...],
    "year_levels": [...],
    "semesters": [...],
    "academic_years": [...],
    "total_programs": 4,
    "total_years": 4,
    "total_semesters": 2,
    "total_academic_years": 2
  }
}
```

### 5. Get Sections Grouped by Program and Year
**GET** `/api/student/sections-grouped`

Returns all sections organized by program and year level for easy selection.

**Response:**
```json
{
  "status": true,
  "message": "Sections grouped by program and year level retrieved successfully",
  "data": [
    {
      "program": "Bachelor of Science in Information Technology",
      "program_short": "BSIT",
      "year_levels": [
        {
          "year_level": "1st year",
          "display_name": "1st year",
          "sections": [...],
          "total_sections": 2
        }
      ],
      "total_years": 4,
      "total_sections": 8
    }
  ]
}
```

### 6. Update Student Profile
**PUT/POST** `/api/student/profile/update`

Updates the student's academic profile with program, year level, and section information.

**Request Body:**
```json
{
  "program": "Bachelor of Science in Information Technology",
  "year_level": "1st year",
  "section_id": 1
}
```

**Response (Success):**
```json
{
  "status": true,
  "message": "Profile updated successfully",
  "data": {
    "user_id": "STD123456789",
    "email": "student@school.edu",
    "full_name": "John Doe",
    "program": "Bachelor of Science in Information Technology",
    "year_level": "1st year",
    "section_id": 1,
    "section_name": "A",
    "updated_at": "2024-01-15 10:30:00"
  }
}
```

**Response (Validation Error):**
```json
{
  "status": false,
  "message": "Missing required field: section_id"
}
```
**HTTP Status**: 400 Bad Request

**Response (Invalid Combination):**
```json
{
  "status": false,
  "message": "Program, year level, and section combination is invalid"
}
```
**HTTP Status**: 400 Bad Request

## 🎯 Use Cases

### Frontend Profile Update Form
```javascript
// 1. Load programs dropdown
const programs = await fetch('/api/student/programs', {
  headers: { 'Authorization': `Bearer ${token}` }
});

// 2. When program is selected, load year levels
const years = await fetch(`/api/student/programs/${program}/years`, {
  headers: { 'Authorization': `Bearer ${token}` }
});

// 3. When year is selected, load sections
const sections = await fetch(`/api/student/programs/${program}/years/${year}/sections`, {
  headers: { 'Authorization': `Bearer ${token}` }
});
```

### Quick Profile Options Load
```javascript
// Load all options at once for a comprehensive form
const allOptions = await fetch('/api/student/profile-options', {
  headers: { 'Authorization': `Bearer ${token}` }
});
```

### Hierarchical Section Selection
```javascript
// Load grouped sections for a tree-like interface
const groupedSections = await fetch('/api/student/sections-grouped', {
  headers: { 'Authorization': `Bearer ${token}` }
});
```

### Profile Update
```javascript
// Update student's academic profile
const updateProfile = async (program, yearLevel, sectionId) => {
  const response = await fetch('/api/student/profile/update', {
    method: 'PUT',
    headers: {
      'Content-Type': 'application/json',
      'Authorization': `Bearer ${token}`
    },
    body: JSON.stringify({
      program: program,
      year_level: yearLevel,
      section_id: sectionId
    })
  });
  
  const result = await response.json();
  if (result.status) {
    console.log('Profile updated successfully:', result.data);
  } else {
    console.error('Update failed:', result.message);
  }
};
```

## 🔧 Error Handling

### Authentication Errors
```json
{
  "status": false,
  "message": "Access denied. Students only."
}
```
**HTTP Status**: 403 Forbidden

### Missing Parameters
```json
{
  "status": false,
  "message": "Program parameter is required"
}
```
**HTTP Status**: 400 Bad Request

### Server Errors
```json
{
  "status": false,
  "message": "Failed to retrieve programs"
}
```
**HTTP Status**: 500 Internal Server Error

## 📊 Data Format Details

### Program Names
- Full names are used in the database
- Short names (BSIT, BSIS, BSCS, ACT) are provided for display
- URL encoding is required for special characters

### Year Levels
- Stored as "1st year", "2nd year", etc.
- Display names are formatted consistently
- Numeric values are extracted for sorting

### Sections
- Include enrollment counts
- Adviser information is provided
- Academic year and semester details are included

## 🚀 Implementation Notes

1. **Caching**: Consider caching program and year level data as they change infrequently
2. **Pagination**: For large datasets, implement pagination
3. **Search**: Add search functionality for programs with many options
4. **Validation**: Frontend should validate selections before submission

## 🔍 Testing

Use the provided test script `test_student_profile_options.php` to verify endpoint functionality:

```bash
php test_student_profile_options.php
```

**Note**: Replace `YOUR_JWT_TOKEN_HERE` with an actual student JWT token.

## 📝 Example Frontend Implementation

```javascript
class StudentProfileManager {
  constructor(token) {
    this.token = token;
    this.baseUrl = '/api/student';
  }

  async loadPrograms() {
    const response = await fetch(`${this.baseUrl}/programs`, {
      headers: { 'Authorization': `Bearer ${this.token}` }
    });
    return response.json();
  }

  async loadYearLevels(program) {
    const encodedProgram = encodeURIComponent(program);
    const response = await fetch(`${this.baseUrl}/programs/${encodedProgram}/years`, {
      headers: { 'Authorization': `Bearer ${this.token}` }
    });
    return response.json();
  }

  async loadSections(program, year) {
    const encodedProgram = encodeURIComponent(program);
    const encodedYear = encodeURIComponent(year);
    const response = await fetch(`${this.baseUrl}/programs/${encodedProgram}/years/${encodedYear}/sections`, {
      headers: { 'Authorization': `Bearer ${this.token}` }
    });
    return response.json();
  }

  async loadAllOptions() {
    const response = await fetch(`${this.baseUrl}/profile-options`, {
      headers: { 'Authorization': `Bearer ${this.token}` }
    });
    return response.json();
  }

  async updateProfile(program, yearLevel, sectionId) {
    const response = await fetch(`${this.baseUrl}/profile/update`, {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${this.token}`
      },
      body: JSON.stringify({
        program: program,
        year_level: yearLevel,
        section_id: sectionId
      })
    });
    return response.json();
  }
}
```

This API provides students with all the information they need to properly update their academic profiles with the correct program, year level, and section assignments.
