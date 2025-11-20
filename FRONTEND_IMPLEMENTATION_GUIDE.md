# Frontend Implementation Guide: Student Type Field (Regular/Irregular)

## Overview
The backend now supports a `student_type` field that allows marking students as either "regular" or "irregular". This field needs to be added to both:
1. **Admin Create Student Form** (Admin Dashboard)
2. **Student Self-Registration Form** (Public Registration Page)

---

## Backend API Details

### Field Information
- **Field Name:** `student_type`
- **Type:** String (ENUM)
- **Valid Values:** `"regular"` or `"irregular"`
- **Default Value:** `"regular"` (if not provided)
- **Required:** No (optional field, defaults to "regular")

### API Endpoints

#### 1. Create Student (POST)
- **Endpoint:** `/api/auth/register`
- **Method:** POST
- **Content-Type:** `application/json` or `multipart/form-data`

**JSON Request Body:**
```json
{
  "role": "student",
  "full_name": "John Doe",
  "email": "john@example.com",
  "password": "password123",
  "student_num": "2024000001",
  "program": "BSIT",
  "section_id": 1,
  "qr_code": "QR_CODE_DATA",
  "student_type": "regular"  // NEW FIELD: "regular" or "irregular"
}
```

**Form-Data Request:**
```
student_type: "regular"  // or "irregular"
```

#### 2. Update Student (PUT/POST)
- **Endpoint:** `/api/auth/update-user` or `/api/student/profile/update`
- **Method:** PUT or POST
- **Content-Type:** `application/json` or `multipart/form-data`

**JSON Request Body:**
```json
{
  "user_id": "STU123",
  "role": "student",
  "student_type": "irregular"  // Can be updated
}
```

---

## Frontend Implementation Instructions

### 1. Admin Create Student Form (Pictures 1 & 2)

#### Location
- **Page:** Admin Dashboard → User Management → Create New User
- **Form Section:** "STUDENT INFORMATION" section

#### Implementation Steps

1. **Add the field after the "Section" field in the STUDENT INFORMATION section:**

```jsx
// Example React/Next.js implementation
<div className="form-group">
  <label htmlFor="student_type">
    Student Type <span className="required">*</span>
  </label>
  <select
    id="student_type"
    name="student_type"
    value={formData.student_type || "regular"}
    onChange={handleChange}
    required
    className="form-control"
  >
    <option value="regular">Regular</option>
    <option value="irregular">Irregular</option>
  </select>
  <small className="form-text text-muted">
    Select whether the student is a regular or irregular student
  </small>
</div>
```

2. **Add to form state:**
```javascript
const [formData, setFormData] = useState({
  // ... existing fields
  student_type: "regular", // Default value
  // ... other fields
});
```

3. **Include in API request:**
```javascript
const createStudent = async () => {
  const payload = {
    role: "student",
    full_name: formData.full_name,
    email: formData.email,
    password: formData.password,
    student_num: formData.student_num,
    program: formData.program,
    section_id: formData.section_id,
    student_type: formData.student_type, // ADD THIS
    // ... other fields
  };
  
  // Send to /api/auth/register
};
```

#### Visual Placement
- **Position:** After the "Section" dropdown in the STUDENT INFORMATION section
- **Field Type:** Dropdown/Select
- **Options:** 
  - Regular (default)
  - Irregular
- **Required:** Yes (marked with asterisk)
- **Help Text:** "Select whether the student is a regular or irregular student"

---

### 2. Student Self-Registration Form (Picture 3)

#### Location
- **Page:** `/auth/register`
- **Form Section:** After "Select Program/Course" or before "Section ID (Optional)"

#### Implementation Steps

1. **Add the field in the registration form:**

```jsx
// Example React/Next.js implementation
<div className="form-group">
  <label htmlFor="student_type">
    Student Type <span className="required">*</span>
  </label>
  <select
    id="student_type"
    name="student_type"
    value={formData.student_type || "regular"}
    onChange={handleChange}
    required
    className="form-control"
  >
    <option value="regular">Regular</option>
    <option value="irregular">Irregular</option>
  </select>
  <small className="form-text text-muted">
    Are you a regular or irregular student?
  </small>
</div>
```

2. **Add to registration form state:**
```javascript
const [registrationData, setRegistrationData] = useState({
  role: "student",
  full_name: "",
  email: "",
  student_num: "",
  program: "",
  section_id: "",
  student_type: "regular", // ADD THIS with default
  // ... other fields
});
```

3. **Include in registration API call:**
```javascript
const handleRegister = async () => {
  const payload = {
    role: "student",
    full_name: registrationData.full_name,
    email: registrationData.email,
    password: registrationData.password,
    student_num: registrationData.student_num,
    program: registrationData.program,
    section_id: registrationData.section_id,
    student_type: registrationData.student_type, // ADD THIS
    // ... other fields
  };
  
  // Send to /api/auth/register
};
```

#### Visual Placement
- **Position:** After "Select Program/Course" dropdown, before "Section ID (Optional)"
- **Field Type:** Dropdown/Select
- **Options:**
  - Regular (default, pre-selected)
  - Irregular
- **Required:** Yes (marked with asterisk)
- **Help Text:** "Are you a regular or irregular student?"

---

## UI/UX Recommendations

### Design Guidelines

1. **Field Label:**
   - Text: "Student Type"
   - Include asterisk (*) if making it required
   - Icon: Could use a user/student icon

2. **Dropdown Options:**
   - **Regular:** "Regular" (default selection)
   - **Irregular:** "Irregular"
   - Consider adding tooltips or help text explaining the difference

3. **Validation:**
   - Client-side: Ensure only "regular" or "irregular" values are sent
   - Backend will validate, but frontend validation provides better UX

4. **Error Handling:**
   - If backend returns error about invalid student_type, display: "Student type must be either 'regular' or 'irregular'"

### Example Styling (CSS/Tailwind)

```css
/* Regular styling */
.student-type-select {
  width: 100%;
  padding: 0.75rem;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-size: 1rem;
}

.student-type-select:focus {
  outline: none;
  border-color: #007bff;
  box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}
```

---

## Testing Checklist

### Admin Create Student Form
- [ ] Dropdown appears in STUDENT INFORMATION section
- [ ] Default value is "regular"
- [ ] Can select "irregular"
- [ ] Field is included in API request
- [ ] Student is created successfully with selected type
- [ ] Validation works (only accepts "regular" or "irregular")

### Student Self-Registration Form
- [ ] Dropdown appears in registration form
- [ ] Default value is "regular"
- [ ] Can select "irregular"
- [ ] Field is included in registration API request
- [ ] Student account is created with selected type
- [ ] Validation works

### Update Student (Bonus)
- [ ] Admin can update student_type when editing student
- [ ] Changes are saved correctly

---

## API Response Examples

### Success Response
```json
{
  "status": true,
  "message": "Student registered successfully!",
  "data": {
    "user_id": "STU123",
    "role": "student",
    "full_name": "John Doe",
    "email": "john@example.com",
    "student_type": "regular",
    // ... other fields
  }
}
```

### Error Response (Invalid student_type)
```json
{
  "status": false,
  "message": "Student type must be either \"regular\" or \"irregular\""
}
```

---

## Important Notes

1. **Default Value:** If `student_type` is not provided, backend defaults to "regular"
2. **Case Sensitivity:** Backend accepts lowercase ("regular", "irregular") - it will convert to lowercase automatically
3. **Optional Field:** While we recommend making it required in the UI, the backend will default to "regular" if omitted
4. **Update Capability:** The field can be updated later via the update user endpoint

---

## Questions or Issues?

If you encounter any issues during implementation:
1. Check browser console for API errors
2. Verify the field name is exactly `student_type` (lowercase, underscore)
3. Ensure values are exactly "regular" or "irregular" (lowercase)
4. Check network tab to confirm the field is being sent in the request

---

## Summary

**What to add:**
- A dropdown/select field labeled "Student Type"
- Options: "Regular" (default) and "Irregular"
- Include in both admin create form and student registration form
- Send as `student_type` in API requests

**Where to add:**
1. **Admin Form:** In STUDENT INFORMATION section, after "Section" field
2. **Student Registration:** After "Select Program/Course", before "Section ID"

**Field Name:** `student_type`
**Valid Values:** `"regular"` or `"irregular"`
**Default:** `"regular"`

