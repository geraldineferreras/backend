# Frontend Manual Grading Integration Guide

## Problem Identified

The frontend is currently calling the wrong API endpoint for manual grading:

**❌ Current (Wrong) Endpoint:**
```
POST /api/tasks/submissions/manual_1756632015500_STU68B3F6580EFD1284/grade
```

**✅ Correct Endpoint:**
```
POST /api/tasks/{task_id}/manual-grade
```

## Root Cause

The frontend code is still using the old grading logic that expects a submission ID, but for manual grading, we don't have submissions yet - we're creating them automatically.

## Solution

Update the frontend code to use the new manual grading endpoint when grading students without submissions.

## Frontend Code Changes

### 1. **Update the API Call Function**

Replace the old `gradeSubmission` function with a new `manualGradeStudent` function:

```javascript
// OLD CODE (Remove this)
async function gradeSubmission(submissionId, grade, feedback) {
    try {
        const response = await api.makeRequest(
            `/tasks/submissions/${submissionId}/grade`,
            'POST',
            { grade, feedback }
        );
        return response;
    } catch (error) {
        console.error('Error grading submission:', error);
        throw error;
    }
}

// NEW CODE (Use this for manual grading)
async function manualGradeStudent(taskId, studentId, classCode, grade, feedback) {
    try {
        const response = await api.makeRequest(
            `/tasks/${taskId}/manual-grade`,
            'POST',
            {
                student_id: studentId,
                class_code: classCode,
                grade: grade,
                feedback: feedback || null
            }
        );
        return response;
    } catch (error) {
        console.error('Error manual grading student:', error);
        throw error;
    }
}
```

### 2. **Update the Manual Grading Handler**

Replace the `handleManualGradeSubmit` function:

```javascript
// OLD CODE (Remove this)
const handleManualGradeSubmit = async () => {
    try {
        const response = await api.gradeSubmission(
            `manual_${Date.now()}_${studentId}`, // This creates invalid submission ID
            grade,
            feedback
        );
        // Handle response...
    } catch (error) {
        // Handle error...
    }
};

// NEW CODE (Use this)
const handleManualGradeSubmit = async () => {
    try {
        // Validate inputs
        if (!studentId || !classCode || grade === null || grade === undefined) {
            setError('Please fill in all required fields');
            return;
        }

        if (grade < 0 || grade > maxPoints) {
            setError(`Grade must be between 0 and ${maxPoints}`);
            return;
        }

        setLoading(true);
        setError(null);

        const response = await api.manualGradeStudent(
            taskId,        // Current task ID
            studentId,     // Selected student ID
            classCode,     // Current class code
            grade,         // Grade value
            feedback       // Optional feedback
        );

        if (response.success) {
            setSuccess(`Successfully graded ${response.data.student_name}: ${response.data.grade}/${response.data.max_points}`);
            
            // Update UI to reflect the new grade
            updateGradeDisplay(response.data);
            
            // Close modal and refresh data
            onClose();
            onGradeSubmitted && onGradeSubmitted();
        } else {
            setError(response.message || 'Failed to submit grade');
        }
    } catch (error) {
        console.error('Manual grading error:', error);
        setError(error.message || 'Failed to submit grade');
    } finally {
        setLoading(false);
    }
};
```

### 3. **Update the API Service**

Add the new manual grading method to your API service:

```javascript
// In your api.js or similar service file
class ApiService {
    // ... existing methods ...

    async manualGradeStudent(taskId, studentId, classCode, grade, feedback) {
        return this.makeRequest(
            `/tasks/${taskId}/manual-grade`,
            'POST',
            {
                student_id: studentId,
                class_code: classCode,
                grade: grade,
                feedback: feedback || null
            }
        );
    }
}
```

### 4. **Update the Manual Grading Modal**

Ensure your modal form includes all required fields:

```jsx
const ManualGradingModal = ({ 
    isOpen, 
    onClose, 
    taskId, 
    classCode, 
    maxPoints, 
    onGradeSubmitted 
}) => {
    const [studentId, setStudentId] = useState('');
    const [grade, setGrade] = useState('');
    const [feedback, setFeedback] = useState('');
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState('');
    const [success, setSuccess] = useState('');

    const handleSubmit = async (e) => {
        e.preventDefault();
        
        try {
            setLoading(true);
            setError(null);

            const response = await api.manualGradeStudent(
                taskId,
                studentId,
                classCode,
                parseFloat(grade),
                feedback
            );

            if (response.success) {
                setSuccess(`Successfully graded student: ${response.data.grade}/${response.data.max_points}`);
                onGradeSubmitted && onGradeSubmitted();
                
                // Reset form
                setStudentId('');
                setGrade('');
                setFeedback('');
                
                // Close modal after delay
                setTimeout(() => {
                    onClose();
                }, 2000);
            } else {
                setError(response.message || 'Failed to submit grade');
            }
        } catch (error) {
            setError(error.message || 'Failed to submit grade');
        } finally {
            setLoading(false);
        }
    };

    return (
        <Modal isOpen={isOpen} onClose={onClose}>
            <div className="manual-grading-modal">
                <h3>Manual Grading</h3>
                <p>Perfect for face-to-face activities and manual assessment</p>
                
                <div className="important-note">
                    <span className="icon-hand">🤚</span>
                    <span>Face-to-Face Activity: Student assigned but no submission yet. Grading will create a manual submission record.</span>
                </div>
                
                <form onSubmit={handleSubmit}>
                    <div className="form-group">
                        <label htmlFor="studentId">Student ID:</label>
                        <input
                            type="text"
                            id="studentId"
                            value={studentId}
                            onChange={(e) => setStudentId(e.target.value)}
                            placeholder="Enter student ID"
                            required
                        />
                    </div>
                    
                    <div className="form-group">
                        <label htmlFor="grade">Score (out of {maxPoints}):</label>
                        <input
                            type="number"
                            id="grade"
                            value={grade}
                            onChange={(e) => setGrade(e.target.value)}
                            min="0"
                            max={maxPoints}
                            step="0.01"
                            placeholder="Enter score"
                            required
                        />
                    </div>
                    
                    <div className="form-group">
                        <label htmlFor="feedback">Feedback (Optional):</label>
                        <textarea
                            id="feedback"
                            value={feedback}
                            onChange={(e) => setFeedback(e.target.value)}
                            rows="3"
                            placeholder="Enter feedback for the student..."
                        />
                    </div>
                    
                    {error && <div className="error-message">{error}</div>}
                    {success && <div className="success-message">{success}</div>}
                    
                    <div className="modal-actions">
                        <button 
                            type="button" 
                            className="btn-secondary" 
                            onClick={onClose}
                            disabled={loading}
                        >
                            Cancel
                        </button>
                        <button 
                            type="submit" 
                            className="btn-primary"
                            disabled={loading}
                        >
                            {loading ? 'Submitting...' : 'Submit Manual Grade'}
                        </button>
                    </div>
                </form>
            </div>
        </Modal>
    );
};
```

## Testing the Integration

### 1. **Test with Postman First**
```bash
POST http://localhost/scms_new_backup/index.php/api/tasks/105/manual-grade
Headers:
  Authorization: Bearer your_teacher_token
  Content-Type: application/json

Body:
{
    "student_id": "STU68B3F6580EFD1284",
    "class_code": "A4V9TE",
    "grade": 8,
    "feedback": "Great participation in the group activity!"
}
```

### 2. **Test the Frontend**
1. Open your manual grading modal
2. Fill in student ID, grade, and feedback
3. Submit the grade
4. Verify the API call goes to the correct endpoint
5. Check that the grade is saved and displayed correctly

## Common Issues and Solutions

### Issue 1: Still Getting 404 Errors
**Cause**: Frontend still calling old endpoint
**Solution**: Ensure all references to `gradeSubmission` are replaced with `manualGradeStudent`

### Issue 2: Missing Required Fields
**Cause**: Frontend not sending all required data
**Solution**: Verify `student_id`, `class_code`, and `grade` are all included

### Issue 3: Authentication Errors
**Cause**: Invalid or expired JWT token
**Solution**: Check token validity and refresh if needed

### Issue 4: Student Not Found
**Cause**: Student ID doesn't exist or isn't enrolled
**Solution**: Verify student ID and class code are correct

## Complete Integration Checklist

- [ ] Replace `gradeSubmission` calls with `manualGradeStudent`
- [ ] Update API service to include new endpoint
- [ ] Ensure all required fields are collected and sent
- [ ] Add proper error handling and validation
- [ ] Test with Postman to verify backend works
- [ ] Test frontend integration
- [ ] Verify grades are saved correctly
- [ ] Check student notifications are sent
- [ ] Update UI to reflect new grades

## Example Complete Implementation

Here's a complete example of how the integration should work:

```javascript
// API Service
class ApiService {
    async manualGradeStudent(taskId, studentId, classCode, grade, feedback) {
        return this.makeRequest(
            `/tasks/${taskId}/manual-grade`,
            'POST',
            {
                student_id: studentId,
                class_code: classCode,
                grade: grade,
                feedback: feedback || null
            }
        );
    }
}

// Component Usage
const TaskDetail = ({ taskId, classCode }) => {
    const handleManualGrade = async (studentId, grade, feedback) => {
        try {
            const response = await api.manualGradeStudent(
                taskId,
                studentId,
                classCode,
                grade,
                feedback
            );
            
            if (response.success) {
                // Update UI
                updateGradeDisplay(response.data);
                showSuccess(`Graded ${response.data.student_name}`);
            }
        } catch (error) {
            showError(error.message);
        }
    };

    return (
        <div>
            {/* Your existing UI */}
            <ManualGradingModal
                taskId={taskId}
                classCode={classCode}
                onGradeSubmitted={handleManualGrade}
            />
        </div>
    );
};
```

This integration will ensure that manual grading works correctly without requiring student submissions or attachments.
