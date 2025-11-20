# Message for Frontend Team: Student Type Feature Implementation

---

## 🎯 New Feature: Student Type (Regular/Irregular)

Hi Frontend Team,

We've added a new field `student_type` to the backend that allows marking students as either "regular" or "irregular". This needs to be added to **two forms**:

---

## 📋 What Needs to Be Added

### 1. **Admin Create Student Form** (Admin Dashboard)
- **Location:** User Management → Create New User → STUDENT INFORMATION section
- **Position:** After the "Section" dropdown field
- **Field Type:** Dropdown/Select
- **Options:** 
  - Regular (default, pre-selected)
  - Irregular
- **Required:** Yes (show asterisk)

### 2. **Student Self-Registration Form** (Public Registration Page)
- **Location:** `/auth/register` page
- **Position:** After "Select Program/Course" dropdown, before "Section ID (Optional)"
- **Field Type:** Dropdown/Select
- **Options:**
  - Regular (default, pre-selected)
  - Irregular
- **Required:** Yes (show asterisk)

---

## 🔧 Technical Details

### Field Information
- **Field Name:** `student_type` (exactly as shown, lowercase with underscore)
- **Type:** String
- **Valid Values:** `"regular"` or `"irregular"` (lowercase)
- **Default:** `"regular"` (if not provided, backend defaults to this)

### API Integration

**When creating a student, include in the request:**

```javascript
// JSON Request
{
  // ... existing fields
  "student_type": "regular"  // or "irregular"
}

// Form-Data Request
student_type: "regular"  // or "irregular"
```

**Endpoints:**
- **Create:** `POST /api/auth/register`
- **Update:** `PUT /api/auth/update-user` or `POST /api/student/profile/update`

---

## 📝 Implementation Example

```jsx
// Add to form state
const [formData, setFormData] = useState({
  // ... existing fields
  student_type: "regular", // Default value
});

// Add to form JSX
<select
  name="student_type"
  value={formData.student_type}
  onChange={handleChange}
  required
>
  <option value="regular">Regular</option>
  <option value="irregular">Irregular</option>
</select>

// Include in API request
const payload = {
  // ... existing fields
  student_type: formData.student_type,
};
```

---

## ✅ Validation

- Backend validates: Only accepts "regular" or "irregular"
- Frontend validation: Ensure dropdown only allows these two values
- Error message (if invalid): "Student type must be either 'regular' or 'irregular'"

---

## 📸 Visual Reference

Based on the current forms:

**Admin Form (Picture 1 & 2):**
- Add dropdown in STUDENT INFORMATION section
- Place after "Section" field
- Label: "Student Type *"

**Student Registration (Picture 3):**
- Add dropdown after "Select Program/Course"
- Place before "Section ID (Optional)"
- Label: "Student Type *"
- Help text: "Are you a regular or irregular student?"

---

## 📚 Full Documentation

See `FRONTEND_IMPLEMENTATION_GUIDE.md` for complete implementation details, examples, and testing checklist.

---

## ❓ Questions?

If you need clarification on:
- Field placement
- API integration
- Validation rules
- Error handling

Please let me know!

---

**Priority:** Medium
**Estimated Time:** 1-2 hours
**Backend Status:** ✅ Complete and deployed

