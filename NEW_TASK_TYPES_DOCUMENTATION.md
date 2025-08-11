# New Task Types: Midterm Exam and Final Exam

## Overview
This document describes the newly added task types to the SCMS system: `midterm_exam` and `final_exam`. These types provide teachers with more specific categorization options for major assessments.

## New Task Types

### 1. **Midterm Exam** (`midterm_exam`)
- **Purpose**: Mid-semester comprehensive assessments
- **Use Case**: Halfway point evaluations, comprehensive knowledge checks
- **Grading Weight**: Typically weighted higher than regular assignments/quizzes
- **Example**: Midterm tests covering first half of course material

### 2. **Final Exam** (`final_exam`)
- **Purpose**: End-of-semester comprehensive assessments
- **Use Case**: Final course evaluations, comprehensive knowledge assessments
- **Grading Weight**: Usually the highest weighted assessment type
- **Example**: Final examinations covering entire course content

## Updated Task Type List

The complete list of available task types is now:

1. **Assignment** (`assignment`) - General homework/coursework
2. **Quiz** (`quiz`) - Short assessments
3. **Activity** (`activity`) - Interactive/hands-on tasks
4. **Project** (`project`) - Long-term comprehensive work
5. **Exam** (`exam`) - General examinations
6. **Midterm Exam** (`midterm_exam`) - **NEW** - Mid-semester assessments
7. **Final Exam** (`final_exam`) - **NEW** - End-of-semester assessments

## Database Changes

### Schema Update
The `class_tasks` table has been updated to include the new task types:

```sql
-- Before
`type` enum('assignment','quiz','activity','project','exam') NOT NULL DEFAULT 'assignment'

-- After  
`type` enum('assignment','quiz','activity','project','exam','midterm_exam','final_exam') NOT NULL DEFAULT 'assignment'
```

### Migration Script
Use the provided `add_midterm_final_exam_types.sql` file to update existing databases.

## API Usage

### Creating Tasks with New Types

#### JSON Request Example
```json
{
  "title": "Calculus Midterm Examination",
  "type": "midterm_exam",
  "points": 100,
  "instructions": "Complete the midterm examination covering chapters 1-5.",
  "class_codes": ["MATH101"],
  "assignment_type": "classroom",
  "allow_comments": true,
  "is_draft": false,
  "due_date": "2025-03-15 14:00:00"
}
```

#### Form Data Example
```javascript
const formData = new FormData();
formData.append('title', 'Final Project Presentation');
formData.append('type', 'final_exam');
formData.append('points', '150');
formData.append('instructions', 'Present your final project to the class.');
formData.append('class_codes', JSON.stringify(['CS101']));
formData.append('assignment_type', 'classroom');
formData.append('allow_comments', '1');
formData.append('is_draft', '0');
formData.append('due_date', '2025-05-20 16:00:00');
```

## Frontend Implementation

### HTML Select Options
```html
<select name="type" required>
    <option value="assignment">Assignment</option>
    <option value="quiz">Quiz</option>
    <option value="activity">Activity</option>
    <option value="project">Project</option>
    <option value="exam">Exam</option>
    <option value="midterm_exam">Midterm Exam</option>
    <option value="final_exam">Final Exam</option>
</select>
```

### React Component Example
```jsx
const [taskType, setTaskType] = useState('assignment');

const taskTypeOptions = [
    { value: 'assignment', label: 'Assignment' },
    { value: 'quiz', label: 'Quiz' },
    { value: 'activity', label: 'Activity' },
    { value: 'project', label: 'Project' },
    { value: 'exam', label: 'Exam' },
    { value: 'midterm_exam', label: 'Midterm Exam' },
    { value: 'final_exam', label: 'Final Exam' }
];

return (
    <select 
        value={taskType} 
        onChange={(e) => setTaskType(e.target.value)}
    >
        {taskTypeOptions.map(option => (
            <option key={option.value} value={option.value}>
                {option.label}
            </option>
        ))}
    </select>
);
```

## Grading and Weighting

### Suggested Weighting System
- **Assignments**: 15-20%
- **Quizzes**: 10-15%
- **Activities**: 10-15%
- **Projects**: 20-25%
- **Exams**: 15-20%
- **Midterm Exams**: 20-25%
- **Final Exams**: 30-40%

### Implementation Notes
- The system automatically categorizes tasks by type for grade calculations
- Teachers can set custom weightings per class
- New exam types integrate with existing grading algorithms

## Validation

### Backend Validation
The TaskController now validates these new types:

```php
$valid_types = ['assignment', 'quiz', 'activity', 'project', 'exam', 'midterm_exam', 'final_exam'];
if (!in_array($data->type, $valid_types)) {
    $this->send_error('Invalid task type', 400);
    return;
}
```

### Frontend Validation
Ensure your frontend forms include validation for the new task types.

## Testing

### Postman Testing
Test the new task types using these examples:

1. **Midterm Exam Creation**
```json
POST /api/tasks/create
{
  "title": "Physics Midterm",
  "type": "midterm_exam",
  "points": 100,
  "instructions": "Complete the physics midterm examination.",
  "class_codes": ["PHYS101"],
  "assignment_type": "classroom",
  "due_date": "2025-03-20 14:00:00"
}
```

2. **Final Exam Creation**
```json
POST /api/tasks/create
{
  "title": "Computer Science Final",
  "type": "final_exam",
  "points": 150,
  "instructions": "Complete the final examination for CS101.",
  "class_codes": ["CS101"],
  "assignment_type": "classroom",
  "due_date": "2025-05-25 16:00:00"
}
```

## Migration Steps

### For New Installations
1. Use the updated `create_class_tasks_table.sql` file
2. The new task types will be available immediately

### For Existing Installations
1. Run the `add_midterm_final_exam_types.sql` migration script
2. Verify the changes with: `DESCRIBE class_tasks;`
3. Test creating tasks with the new types

## Benefits

1. **Better Categorization**: More specific exam types for better organization
2. **Improved Grading**: Clear distinction between different assessment types
3. **Enhanced Reporting**: Better analytics and grade distribution reports
4. **Academic Standards**: Aligns with standard educational assessment types

## Support

For any issues or questions regarding the new task types:
1. Check the validation logs for "Invalid task type" errors
2. Verify the database schema has been updated
3. Ensure all frontend forms include the new options
4. Test with Postman before implementing in production
